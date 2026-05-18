<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Familia;
use App\Models\Marca;
use App\Models\Solicitud;
use App\Models\SolicitudDevolucion;
use App\Models\Producto;
use App\Models\Container;
use App\Models\HistorialCambio;
use App\Models\Precio;
use App\Models\Sicd;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminController extends Controller
{
    public function solicitudes()
    {
        abort_unless(auth()->user()->tienePermiso('solicitudes') || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);
        $user = auth()->user();
        $ccId = $user->ccFiltro();

        $solicitudes = Solicitud::with([
                'producto' => fn($q) => $q->withoutGlobalScopes()->with('container'),
                'usuario',
            ])
            ->where('estado', 'pendiente')
            ->whereHas('producto', fn($q) => $q->withoutGlobalScopes()
                ->when($ccId, fn($q2) => $q2->where('centro_costo_id', $ccId))
            )
            ->orderByDesc('created_at')
            ->get();

        // Solicitudes de salida en proceso de devolución (sin límite de tiempo)
        $solicitudesAprobadas = Solicitud::with([
                'producto' => fn($q) => $q->withoutGlobalScopes(),
                'usuario',
            ])
            ->whereIn('estado', ['aprobado', 'en_devolucion'])
            ->where('tipo', 'salida')
            ->whereHas('producto', fn($q) => $q->withoutGlobalScopes()
                ->where('es_servicio', false)
                ->when($ccId, fn($q2) => $q2->where('centro_costo_id', $ccId))
            )
            ->orderByDesc('created_at')
            ->get();

        // Total ya devuelto por solicitud
        $devolucionesPorSolicitud = HistorialCambio::where('tipo', 'devolucion')
            ->where('origen', 'solicitud')
            ->whereIn('origen_id', $solicitudesAprobadas->pluck('id'))
            ->selectRaw('origen_id, SUM(cantidad) as total_devuelto')
            ->groupBy('origen_id')
            ->pluck('total_devuelto', 'origen_id');

        $containers = Container::orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);

        // Solicitudes de devolución de usuarios pendientes de aprobación
        $solicitudesDevolucionPendientes = SolicitudDevolucion::with([
                'solicitud',
                'producto' => fn($q) => $q->withoutGlobalScopes(),
                'usuario',
            ])
            ->where('estado', 'pendiente')
            ->when($ccId, fn($q) => $q->whereHas('producto', fn($q2) => $q2->withoutGlobalScopes()
                ->where('centro_costo_id', $ccId)))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.solicitudes', compact(
            'solicitudes', 'containers',
            'solicitudesAprobadas', 'devolucionesPorSolicitud',
            'solicitudesDevolucionPendientes'
        ));
    }

    public function registrarDevolucion(int $id, Request $request)
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);

        $solicitud = Solicitud::with('producto')->findOrFail($id);

        if (!in_array($solicitud->estado, ['aprobado', 'en_devolucion']) || $solicitud->tipo !== 'salida') {
            return back()->with('error', 'Solo se pueden registrar devoluciones para solicitudes de salida aprobadas o en proceso de devolución.');
        }

        $producto = $solicitud->producto;
        if (!$producto || $producto->es_servicio) {
            return back()->with('error', 'El producto no es válido para devoluciones de stock físico.');
        }

        // Calcular máximo devolvible
        $yaDevuelto  = HistorialCambio::where('tipo', 'devolucion')
            ->where('origen', 'solicitud')
            ->where('origen_id', $solicitud->id)
            ->sum('cantidad');
        $maxDevolver = $solicitud->cantidad - (int) $yaDevuelto;

        if ($maxDevolver <= 0) {
            return back()->with('error', 'Ya se registró la devolución total de esta solicitud.');
        }

        $data = $request->validate([
            'cantidad_devolucion' => ['required', 'integer', 'min:1', 'max:' . $maxDevolver],
            'motivo_devolucion'   => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cantidad_devolucion.required' => 'La cantidad es obligatoria.',
            'cantidad_devolucion.integer'  => 'La cantidad debe ser un número entero.',
            'cantidad_devolucion.min'      => 'La cantidad mínima es 1.',
            'cantidad_devolucion.max'      => "No puede devolver más de {$maxDevolver} unidad(es) (entregadas: {$solicitud->cantidad}, ya devueltas: {$yaDevuelto}).",
            'motivo_devolucion.required'   => 'El motivo es obligatorio.',
            'motivo_devolucion.min'        => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        DB::transaction(function () use ($solicitud, $producto, $data) {
            $cantidad   = (int) $data['cantidad_devolucion'];
            $stockAntes = $producto->stock_actual;

            $producto->stock_actual += $cantidad;
            $producto->actualizarFechasStock();
            $producto->save();

            HistorialCambio::create([
                'producto_id'        => $producto->id,
                'nombre_producto'    => $producto->nombre,
                'contenedor_id'      => $producto->contenedor,
                'cantidad'           => $cantidad,
                'tipo'               => 'devolucion',
                'motivo'             => $data['motivo_devolucion'],
                'aprobado_por'       => Auth::user()->name,
                'usuario_id'         => $solicitud->usuario_id,
                'origen'             => 'solicitud',
                'origen_id'          => $solicitud->id,
                'origen_tipo'        => 'devolucion',
                'referencia_tipo'    => 'solicitud',
                'referencia_id'      => $solicitud->id,
                'doc_referencia'     => 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT),
                'stock_anterior'     => $stockAntes,
                'stock_posterior'    => $producto->stock_actual,
                'usuario_ejecutor_id'=> Auth::id(),
            ]);
        });

        $qty = (int) $data['cantidad_devolucion'];
        return back()->with('success', "Devolución de {$qty} unidad(es) de «{$producto->nombre}» registrada. Stock actualizado.");
    }

    public function abrirDevolucion(int $id)
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);

        $solicitud = Solicitud::with('producto')->findOrFail($id);

        if ($solicitud->estado !== 'aprobado') {
            return back()->with('error', 'Solo se puede abrir el proceso de devolución en solicitudes con estado "Aprobado".');
        }
        if ($solicitud->tipo !== 'salida') {
            return back()->with('error', 'Solo las solicitudes de salida pueden tener devoluciones.');
        }

        $solicitud->update(['estado' => 'en_devolucion']);

        return back()->with('success', "Proceso de devolución abierto para «{$solicitud->producto?->nombre}». Ya puedes registrar unidades devueltas.");
    }

    public function cerrarDevolucion(int $id)
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);

        $solicitud = Solicitud::findOrFail($id);

        if ($solicitud->estado !== 'en_devolucion') {
            return back()->with('error', 'Solo se pueden cerrar solicitudes que estén en proceso de devolución.');
        }

        $solicitud->update(['estado' => 'cerrada']);

        return back()->with('success', "Solicitud #{$id} cerrada correctamente. No se podrán registrar más devoluciones.");
    }

    public function aprobarDevolucion(int $id)
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);

        $solicitudDev = SolicitudDevolucion::with([
            'solicitud',
            'producto' => fn($q) => $q->withoutGlobalScopes(),
        ])->findOrFail($id);

        if ($solicitudDev->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud de devolución ya fue procesada.');
        }

        $producto = $solicitudDev->producto;
        if (!$producto || $producto->es_servicio) {
            return back()->with('error', 'Producto no válido para devolución de stock físico.');
        }

        // Recalcular máximo devolvible con aprobadas al momento de la acción
        $solicitud     = $solicitudDev->solicitud;
        $yaDevuelto    = SolicitudDevolucion::where('solicitud_id', $solicitudDev->solicitud_id)
            ->where('estado', 'aprobada')
            ->sum('cantidad');
        $maxDevolvible = $solicitud->cantidad - (int)$yaDevuelto;

        if ($solicitudDev->cantidad > $maxDevolvible) {
            $solicitudDev->update([
                'estado'         => 'rechazada',
                'aprobado_por_id'=> Auth::id(),
                'motivo_rechazo' => 'Excede el saldo devolvible al momento de la aprobación (ya se aprobaron otras devoluciones).',
            ]);
            return back()->with('error', 'La cantidad solicitada excede el saldo devolvible. La solicitud fue rechazada automáticamente.');
        }

        DB::transaction(function () use ($solicitudDev, $producto) {
            $stockAntes = $producto->stock_actual;

            $producto->stock_actual += $solicitudDev->cantidad;
            $producto->actualizarFechasStock();
            $producto->save();

            HistorialCambio::create([
                'producto_id'        => $producto->id,
                'nombre_producto'    => $producto->nombre,
                'contenedor_id'      => $producto->contenedor,
                'cantidad'           => $solicitudDev->cantidad,
                'tipo'               => 'devolucion',
                'motivo'             => $solicitudDev->motivo,
                'aprobado_por'       => Auth::user()->name,
                'usuario_id'         => $solicitudDev->usuario_id,
                'origen'             => 'solicitud',
                'origen_id'          => $solicitudDev->solicitud_id,
                'origen_tipo'        => 'devolucion',
                'referencia_tipo'    => 'solicitud',
                'referencia_id'      => $solicitudDev->solicitud_id,
                'stock_anterior'     => $stockAntes,
                'stock_posterior'    => $producto->stock_actual,
                'usuario_ejecutor_id'=> Auth::id(),
            ]);

            $solicitudDev->update([
                'estado'          => 'aprobada',
                'aprobado_por_id' => Auth::id(),
            ]);
        });

        $qty  = $solicitudDev->cantidad;
        $nom  = $producto->nombre;
        $docN = $solicitudDev->numeroDoc();
        return back()->with('success', "Devolución {$docN} aprobada: {$qty} unidad(es) de «{$nom}» restituidas al stock.");
    }

    public function rechazarDevolucion(int $id, Request $request)
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);

        $data = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'max:500'],
        ], [
            'motivo_rechazo.required' => 'El motivo de rechazo es obligatorio.',
        ]);

        $solicitudDev = SolicitudDevolucion::findOrFail($id);

        if ($solicitudDev->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud de devolución ya fue procesada.');
        }

        $solicitudDev->update([
            'estado'          => 'rechazada',
            'aprobado_por_id' => Auth::id(),
            'motivo_rechazo'  => $data['motivo_rechazo'],
        ]);

        $docN = $solicitudDev->numeroDoc();
        return back()->with('success', "Solicitud {$docN} rechazada.");
    }


    public function aprobar(int $id)
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);
        $solicitud = Solicitud::with('producto')->findOrFail($id);

        if ($solicitud->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $producto = $solicitud->producto;

        // Servicios no tienen stock físico — bloquear salidas de stock
        if ($producto->es_servicio && $solicitud->tipo === 'salida') {
            return back()->with('error', "«{$producto->nombre}» es un servicio y no tiene stock físico que descontar.");
        }

        // Validar stock negativo para salidas de productos físicos
        if (!$producto->es_servicio && $solicitud->tipo === 'salida') {
            if ($producto->stock_actual < $solicitud->cantidad) {
                return back()->with('error',
                    "Stock insuficiente. Stock actual: {$producto->stock_actual}, solicitado: {$solicitud->cantidad}.");
            }
        }

        DB::transaction(function () use ($solicitud, $producto) {
            $stockAntes = $producto->stock_actual;

            // Solo actualizar stock para productos físicos
            if (!$producto->es_servicio) {
                if ($solicitud->tipo === 'entrada') {
                    $producto->stock_actual += $solicitud->cantidad;
                } else {
                    $producto->stock_actual -= $solicitud->cantidad;
                }
                $producto->actualizarFechasStock();
                $producto->save();
            }

            // Cambiar estado de la solicitud
            $solicitud->estado = 'aprobado';
            $solicitud->save();

            // Registrar en historial
            HistorialCambio::create([
                'producto_id'        => $solicitud->producto_id,
                'nombre_producto'    => $producto->nombre,
                'contenedor_id'      => $producto->contenedor,
                'cantidad'           => $solicitud->cantidad,
                'tipo'               => $solicitud->tipo,
                'motivo'             => $solicitud->motivo,
                'aprobado_por'       => Auth::user()->name,
                'usuario_id'         => $solicitud->usuario_id,
                'origen'             => 'solicitud',
                'origen_id'          => $solicitud->id,
                'origen_tipo'        => 'solicitud',
                'doc_origen'         => 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT),
                'stock_anterior'     => $stockAntes,
                'stock_posterior'    => $producto->stock_actual,
                'usuario_ejecutor_id'=> Auth::id(),
            ]);
        });

        return back()->with('success', 'Solicitud aprobada y stock actualizado.');
    }

    public function rechazar(int $id, Request $request)
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);
        $data = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'max:500'],
        ], [
            'motivo_rechazo.required' => 'El motivo es obligatorio.',
            'motivo_rechazo.max'      => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $solicitud = Solicitud::findOrFail($id);

        if ($solicitud->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $solicitud->estado = 'rechazado';
        $solicitud->motivo_rechazo = $data['motivo_rechazo'];
        $solicitud->rechazado_por = Auth::user()->name;
        $solicitud->save();

        return back()->with('success', 'Solicitud rechazada. El stock no fue modificado.');
    }

    public function rechazadas()
    {
        abort_unless(auth()->user()->tienePermiso('rechazadas'), 403);
        $user = auth()->user();
        $ccId = $user->ccFiltro();

        $solicitudes = Solicitud::with([
                'producto' => fn($q) => $q->withoutGlobalScopes(),
                'usuario',
            ])
            ->where('estado', 'rechazado')
            ->whereHas('producto', fn($q) => $q->withoutGlobalScopes()
                ->when($ccId, fn($q2) => $q2->where('centro_costo_id', $ccId))
            )
            ->orderByDesc('created_at')
            ->get();

        $productosAgrupados = Producto::orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre'])
            ->groupBy('nombre');

        $fSolicitantes = $solicitudes->pluck('usuario.name')->filter()->unique()->sort()->values();

        return view('admin.solicitudes.rechazadas', compact('solicitudes', 'productosAgrupados', 'fSolicitantes'));
    }

    public function historial()
    {
        abort_unless(auth()->user()->tienePermiso('historial'), 403);
        $user  = auth()->user();
        $query = HistorialCambio::with(['usuario', 'container',
            'sicd'        => fn($q) => $q->withTrashed(),
            'producto'    => fn($q) => $q->withoutGlobalScopes(),
            'gastoMenor'  => fn($q) => $q->select('id', 'id_gm'),
            'ordenCompra' => fn($q) => $q->select('id', 'numero_oc'),
        ])
            ->orderByDesc('created_at');

        if ($user->tieneFiltroCC()) {
            $prefix = $user->centroCostoPrefix();
            // Entradas sin SICD son visibles; las ligadas a SICD solo si coincide el prefijo
            $query->where(function ($q) use ($prefix) {
                $q->where(function ($q2) {
                    $q2->where('origen', '!=', 'sicd')->orWhereNull('origen');
                })->orWhereHas('sicd', function ($q2) use ($prefix) {
                    $q2->whereRaw("REGEXP_REPLACE(codigo_sicd, '[^A-Za-z].*', '') = ?", [$prefix]);
                });
            });
        }

        $historial = $query->get();

        // Clave de agrupación: origen+id+tipo cuando existe, sino motivo+usuario+minuto exacto
        $lotes = $historial
            ->groupBy(function ($r) {
                if ($r->origen && $r->origen_id) {
                    return "origen:{$r->origen}-{$r->origen_id}-{$r->tipo}";
                }
                // Agrupar registros sin origen por motivo + usuario + minuto
                return "sinOrigen:{$r->usuario_id}:{$r->motivo}:{$r->created_at->format('Y-m-d H:i')}";
            })
            ->filter(fn($g) => $g->count() > 1);

        // Lista ordenada de filas: grupos colapsados + individuales
        $filas = collect();
        $vistos = [];
        foreach ($historial as $r) {
            if ($r->origen && $r->origen_id) {
                $key = "origen:{$r->origen}-{$r->origen_id}-{$r->tipo}";
            } else {
                $key = "sinOrigen:{$r->usuario_id}:{$r->motivo}:{$r->created_at->format('Y-m-d H:i')}";
            }

            if ($lotes->has($key)) {
                if (!in_array($key, $vistos)) {
                    $vistos[] = $key;
                    $filas->push(['tipo' => 'grupo', 'registros' => $lotes[$key]]);
                }
            } else {
                $filas->push(['tipo' => 'individual', 'registro' => $r]);
            }
        }

        return view('admin.historial', compact('historial', 'filas'));
    }

    public function editarStock(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('stock'), 403);
        $producto = Producto::with('container')->findOrFail($id);
        $containers = Container::orderBy('id')->get();
        return view('admin.productos.editar', compact('producto', 'containers'));
    }

    public function modificarStock(int $id, Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('stock'), 403);

        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
            'tipo'     => ['required', 'in:entrada,salida'],
            'motivo'   => ['required', 'string', 'max:500'],
        ], [
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer'  => 'La cantidad debe ser un número entero.',
            'cantidad.min'      => 'La cantidad debe ser al menos 1.',
            'tipo.required'     => 'El tipo es obligatorio.',
            'tipo.in'           => 'El tipo debe ser entrada o salida.',
            'motivo.required'   => 'El motivo es obligatorio.',
            'motivo.max'        => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $producto = Producto::findOrFail($id);

        if ($producto->es_servicio) {
            return back()->with('error', "«{$producto->nombre}» es un servicio y no tiene stock físico. Los servicios se registran únicamente a través de SICD, OC o Gastos Menores.");
        }

        if ($data['tipo'] === 'salida' && $producto->stock_actual < $data['cantidad']) {
            return back()->withErrors([
                'cantidad' => "Stock insuficiente. Stock actual: {$producto->stock_actual}.",
            ])->withInput();
        }

        DB::transaction(function () use ($producto, $data) {
            $stockAntes = $producto->stock_actual;

            if ($data['tipo'] === 'entrada') {
                $producto->stock_actual += $data['cantidad'];
            } else {
                $producto->stock_actual -= $data['cantidad'];
            }
            $producto->actualizarFechasStock();
            $producto->save();

            HistorialCambio::create([
                'producto_id'        => $producto->id,
                'nombre_producto'    => $producto->nombre,
                'contenedor_id'      => $producto->contenedor,
                'cantidad'           => $data['cantidad'],
                'tipo'               => $data['tipo'],
                'motivo'             => $data['motivo'],
                'aprobado_por'       => Auth::user()->name,
                'usuario_id'         => Auth::id(),
                'origen_tipo'        => 'ajuste',
                'stock_anterior'     => $stockAntes,
                'stock_posterior'    => $producto->stock_actual,
                'usuario_ejecutor_id'=> Auth::id(),
            ]);
        });

        return redirect()->route('dashboard')
            ->with('success', "Stock de '{$producto->nombre}' actualizado correctamente.");
    }

    public function trasladarContainer(int $id, Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('stock'), 403);
        $data = $request->validate([
            'contenedor_destino' => ['required', 'integer', 'exists:containers,id'],
            'motivo'             => ['required', 'string', 'max:500'],
        ], [
            'contenedor_destino.required' => 'Debes seleccionar un container de destino.',
            'contenedor_destino.exists'   => 'El container de destino no existe.',
            'motivo.required'             => 'El motivo es obligatorio.',
            'motivo.max'                  => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $producto = Producto::findOrFail($id);

        if ($producto->contenedor == $data['contenedor_destino']) {
            return back()->withErrors(['contenedor_destino' => 'El producto ya está en ese container.'])->withInput();
        }

        $containerOrigen  = $producto->contenedor
            ? Container::withoutGlobalScope('con_cc')->find($producto->contenedor)
            : null;
        $containerDestino = Container::withoutGlobalScope('con_cc')->findOrFail($data['contenedor_destino']);

        $origenNombre = $containerOrigen?->nombre ?? 'Sin container';

        DB::transaction(function () use ($producto, $data, $origenNombre, $containerDestino) {
            $stockActual = $producto->stock_actual;
            $producto->contenedor = $data['contenedor_destino'];
            $producto->save();

            HistorialCambio::create([
                'producto_id'        => $producto->id,
                'nombre_producto'    => $producto->nombre,
                'contenedor_id'      => $containerDestino->id,
                'cantidad'           => $stockActual,
                'tipo'               => 'traslado',
                'motivo'             => "Traslado de {$origenNombre} a {$containerDestino->nombre}: {$data['motivo']}",
                'aprobado_por'       => Auth::user()->name,
                'usuario_id'         => Auth::id(),
                'origen_tipo'        => 'traslado',
                'stock_anterior'     => $stockActual,
                'stock_posterior'    => $stockActual,
                'usuario_ejecutor_id'=> Auth::id(),
            ]);
        });

        return redirect()->route('dashboard')
            ->with('success', "Producto '{$producto->nombre}' trasladado a {$containerDestino->nombre} correctamente.");
    }

    public function deshabilitarProducto(int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $producto = Producto::withoutGlobalScope('activo')->findOrFail($id);
        $producto->activo = false;
        $producto->save();

        return back()->with('success', "Producto «{$producto->nombre}» deshabilitado. No aparecerá en el inventario activo.");
    }

    private static function normalizarTexto(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, [
            'á'=>'a','à'=>'a','ä'=>'a','â'=>'a',
            'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
            'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i',
            'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o',
            'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u',
            'ñ'=>'n',
            // Superíndices matemáticos comunes en Excel (m², m³)
            '²'=>'2', '³'=>'3',
        ]);
        return preg_replace('/[^a-z0-9]/u', '', $s);
    }

    /**
     * Resuelve variantes y alias de unidades antes del lookup en el índice.
     * Cubre plurales españoles, abreviaciones comunes y formatos de Excel.
     */
    private static function resolverVarianteUnidad(string $norm): string
    {
        static $mapa = [
            // ── Metro lineal ──────────────────────────────────────────────
            'ml'                => 'metrolineal',  // M.L. o ML (contexto construcción)
            'mls'               => 'metrolineal',
            'mlin'              => 'metrolineal',
            'mlineal'           => 'metrolineal',
            'metroslineales'    => 'metrolineal',
            'metrolineales'     => 'metrolineal',
            'metrolineale'      => 'metrolineal',  // typo frecuente
            // ── Metro cuadrado ────────────────────────────────────────────
            'metroscuadrados'   => 'metrocuadrado',
            'metrocuadrados'    => 'metrocuadrado',
            // ── Metro cúbico ──────────────────────────────────────────────
            'metroscubicos'     => 'metrocubico',
            'metrocubicos'      => 'metrocubico',
            // ── Metro genérico (plural) ───────────────────────────────────
            'metros'            => 'metro',
            // ── Unidad ────────────────────────────────────────────────────
            'unidades'          => 'unidad',
            'und'               => 'unidad',
            'un'                => 'unidad',
            'u'                 => 'unidad',
            'uds'               => 'unidad',
            'unds'              => 'unidad',
            'pza'               => 'unidad',
            'pzas'              => 'unidad',
            'pieza'             => 'unidad',
            'piezas'            => 'unidad',
            // ── Kilogramo ─────────────────────────────────────────────────
            'kilogramos'        => 'kilogramo',
            'kilo'              => 'kilogramo',
            'kilos'             => 'kilogramo',
            'kgs'               => 'kilogramo',
            // ── Gramo ─────────────────────────────────────────────────────
            'gramos'            => 'gramo',
            'grs'               => 'gramo',
            // ── Litro ─────────────────────────────────────────────────────
            'litros'            => 'litro',
            'lts'               => 'litro',
            'ltr'               => 'litro',
            // ── Mililitro ─────────────────────────────────────────────────
            'mililitros'        => 'mililitro',
            // ── Tonelada ──────────────────────────────────────────────────
            'toneladas'         => 'tonelada',
            'tns'               => 'tonelada',
            // ── Caja ──────────────────────────────────────────────────────
            'cajas'             => 'caja',
            // Caja de 10
            'cajax10'           => 'caja10',
            'cajade10'          => 'caja10',
            'cajasde10'         => 'caja10',
            'cajadex10'         => 'caja10',
            // Caja de 20
            'cajax20'           => 'caja20',
            'cajade20'          => 'caja20',
            'cajasde20'         => 'caja20',
            'cajadex20'         => 'caja20',
            // Caja de 50
            'cajax50'           => 'caja50',
            'cajade50'          => 'caja50',
            'cajasde50'         => 'caja50',
            'cajadex50'         => 'caja50',
            // ── Paquete ───────────────────────────────────────────────────
            'paquetes'          => 'paquete',
            'pack'              => 'paquete',
            'packs'             => 'paquete',
            // ── Rollo ─────────────────────────────────────────────────────
            'rollos'            => 'rollo',
            // ── Bolsa ─────────────────────────────────────────────────────
            'bolsas'            => 'bolsa',
            // ── Tubo ──────────────────────────────────────────────────────
            'tubos'             => 'tubo',
            // ── Saco ──────────────────────────────────────────────────────
            'sacos'             => 'saco',
            // ── Pallet ────────────────────────────────────────────────────
            'pallets'           => 'pallet',
            'paletes'           => 'pallet',
            'palets'            => 'pallet',
            // ── Juego / Set ───────────────────────────────────────────────
            'juegos'            => 'juego',
            'sets'              => 'set',
            // ── Kit ───────────────────────────────────────────────────────
            'kits'              => 'kit',
            // ── Par ───────────────────────────────────────────────────────
            'pares'             => 'par',
            // ── Docena ────────────────────────────────────────────────────
            'docenas'           => 'docena',
            // ── Hora ──────────────────────────────────────────────────────
            'horas'             => 'hora',
            'hrs'               => 'hora',
            // ── Servicio ──────────────────────────────────────────────────
            'servicios'         => 'servicio',
            // ── Bidón ─────────────────────────────────────────────────────
            'bidones'           => 'bidon',
            'bidons'            => 'bidon',
        ];
        return $mapa[$norm] ?? $norm;
    }

    private static function calcSimilitud(string $aNorm, string $bNorm): float
    {
        if ($aNorm === $bNorm) return 100.0;
        if ($aNorm === '' || $bNorm === '') return 0.0;
        $dist   = levenshtein($aNorm, $bNorm);
        $maxLen = max(strlen($aNorm), strlen($bNorm));
        return round((1 - $dist / $maxLen) * 100, 1);
    }

    public function cargaMasiva(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $request->validate([
            'excel_masivo'  => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'motivo_masivo' => ['nullable', 'string', 'max:500'],
            'codigo_sicd'   => ['required', 'string', 'max:100'],
            'descripcion'   => ['nullable', 'string', 'max:500'],
        ], [
            'excel_masivo.required' => 'El archivo Excel es obligatorio.',
            'excel_masivo.mimes'    => 'El archivo debe ser XLSX, XLS o CSV.',
            'codigo_sicd.required'  => 'El código SICD es obligatorio.',
        ]);

        $vincularOc  = $request->boolean('vincular_oc');
        $rows        = $this->leerExcelLigero($request->file('excel_masivo'));
        $motivo      = trim($request->input('motivo_masivo', '')) ?: 'Carga masiva de inventario';
        $codigoSicd  = strtoupper(trim($request->input('codigo_sicd', '')));

        // Guardar boleta en temp si viene y no es OC
        $boletaTempRuta   = null;
        $boletaNombreOrig = null;
        if (!$vincularOc && $request->hasFile('boleta_sicd')) {
            $boletaTempRuta   = $request->file('boleta_sicd')->store('temp/boletas', 'local');
            $boletaNombreOrig = $request->file('boleta_sicd')->getClientOriginalName();
        }

        // Verificar que el código SICD exista en la BD externa
        try {
            $sicdExterno = \App\Models\SicdExterno::buscar($codigoSicd);
        } catch (\Exception) {
            return back()->with('error', 'No se pudo conectar al sistema externo para validar el código SICD.');
        }
        if (!$sicdExterno) {
            return back()->with('error', "El código SICD \"{$codigoSicd}\" no existe en el sistema externo. Verifica el número e inténtalo de nuevo.");
        }

        // Verificar que el código pertenezca al centro de costo del usuario
        $user = auth()->user();
        if ($user->tieneFiltroCC()) {
            $prefix  = $user->centroCostoPrefix();
            $prefijo = strtoupper(trim(preg_replace('/[^A-Za-z].*$/u', '', $codigoSicd)));
            if ($prefijo !== strtoupper($prefix)) {
                return back()->with('error', "El código SICD \"{$codigoSicd}\" no pertenece a tu centro de costo ({$prefix}).");
            }
        }

        // Advertir si la SICD ya está ingresada en el sistema (tiene detalles)
        if (!$request->boolean('confirmar_duplicado')) {
            $sicdExistente = Sicd::where('codigo_sicd', $codigoSicd)->whereHas('detalles')->latest()->first();
            if ($sicdExistente) {
                return back()
                    ->withInput()
                    ->with('sicd_duplicada', [
                        'codigo' => $codigoSicd,
                        'id'     => $sicdExistente->id,
                        'estado' => $sicdExistente->estado,
                        'url'    => route('admin.sicd.show', $sicdExistente->id),
                    ]);
            }
        }

        $descripcion = $request->input('descripcion');
        $ccIdMasiva = $user->ccFiltro();

        // ── Precarga en memoria — elimina N+1 y timeouts ──────────────────
        $productosDB    = Producto::when($ccIdMasiva, fn($q) => $q->where('centro_costo_id', $ccIdMasiva))
                            ->get(['id', 'nombre', 'contenedor', 'unidad_medida_id']);
        $unidadesMedida = UnidadMedida::activas()->get(['id', 'nombre', 'abreviacion', 'descripcion']);

        // Índice normalizado → Producto (búsqueda O(1) sin query por fila)
        $prodIdx = [];
        foreach ($productosDB as $p) {
            $k = self::normalizarTexto($p->nombre);
            if ($k !== '') $prodIdx[$k] = $p;
        }

        // Mapa id → UnidadMedida para lookup rápido de nombre
        $unidMap = $unidadesMedida->keyBy('id');

        // Índice normalizado → UnidadMedida (abreviacion + nombre + descripcion)
        $unidIdx = [];
        foreach ($unidadesMedida as $u) {
            $kAbr = self::normalizarTexto($u->abreviacion);
            $kNom = self::normalizarTexto($u->nombre);
            $kDes = self::normalizarTexto($u->descripcion ?? '');
            if ($kAbr !== '') $unidIdx[$kAbr] = $u;
            if ($kNom !== '') $unidIdx[$kNom] = $u;
            if ($kDes !== '') $unidIdx[$kDes] = $u;
        }
        // ──────────────────────────────────────────────────────────────────

        $exactos    = [];
        $conflictos = [];

        foreach ($rows as $row) {
            $desc        = trim((string) ($row[0] ?? ''));
            $unidadExcel = trim((string) ($row[1] ?? ''));
            $cantidad    = (int) ($row[2] ?? 0);
            $precioNeto  = is_numeric($row[3] ?? '') ? (float) $row[3] : null;
            $totalNeto   = is_numeric($row[4] ?? '') ? (float) $row[4] : null;
            // Columna F (índice 5): TIPO — "SERVICIO" → servicio, cualquier otro → producto físico
            $tipoExcel   = strtoupper(trim((string) ($row[5] ?? '')));
            $esServicio  = $tipoExcel === 'SERVICIO';
            // Columnas G/H/I (índices 6/7/8): presentación (opcional, retrocompatible)
            $tipoPres    = trim((string) ($row[6] ?? ''));
            $cantPres    = is_numeric($row[7] ?? '') ? (int) $row[7] : 0;
            $unidadBase  = trim((string) ($row[8] ?? ''));
            $manejaPres  = !$esServicio && $tipoPres !== '' && $cantPres >= 2;

            if ($desc === '' || $cantidad <= 0) continue;

            // Si el item maneja presentación, la cantidad del Excel = presentaciones;
            // convertir a unidades reales para stock e historial
            $cantidadReal = $manejaPres ? ($cantidad * $cantPres) : $cantidad;

            // Auto-calculate total when Excel omits the column
            if ($totalNeto === null && $precioNeto !== null && $cantidad > 0) {
                $totalNeto = round($precioNeto * $cantidad);
            }

            $item = [
                'descripcion'          => $desc,
                'unidad'               => $unidadExcel,
                'cantidad'             => $cantidad,        // unidades comerciales (para SicdDetalle)
                'cantidad_real'        => $cantidadReal,    // unidades reales (para stock y BINCARD)
                'precioNeto'           => $precioNeto,
                'es_servicio'          => $esServicio,
                'totalNeto'            => $totalNeto,
                'unidad_medida_id'     => null,
                'unidad_warning'       => null,
                'unidad_discrepancia'  => null,
                'monto_warning'        => null,
                'maneja_presentacion'  => $manejaPres,
                'tipo_presentacion'    => $manejaPres ? $tipoPres : null,
                'cantidad_presentacion'=> $manejaPres ? $cantPres : null,
                'unidad_base'          => $manejaPres && $unidadBase !== '' ? $unidadBase : null,
            ];

            // ── Validación monetaria (usa cantidad comercial del Excel) ────
            if ($precioNeto !== null && $totalNeto !== null && $cantidad > 0) {
                $calculado = round($cantidad * $precioNeto);
                if (abs($calculado - $totalNeto) > 1) {
                    $item['monto_warning'] = [
                        'calculado' => $calculado,
                        'excel'     => $totalNeto,
                    ];
                }
            }

            // ── Detección de unidad (in-memory, soft-delete excluido) ──────
            if ($unidadExcel !== '') {
                $unidNorm = self::resolverVarianteUnidad(self::normalizarTexto($unidadExcel));
                if (isset($unidIdx[$unidNorm])) {
                    $u = $unidIdx[$unidNorm];
                    $item['unidad_medida_id']     = $u->id;
                    $item['unidad_medida_nombre'] = $u->abreviacion;
                } else {
                    $mejorUPct = 0;
                    $mejorUnid = null;
                    foreach ($unidadesMedida as $u) {
                        $pct = max(
                            self::calcSimilitud($unidNorm, self::normalizarTexto($u->abreviacion)),
                            self::calcSimilitud($unidNorm, self::normalizarTexto($u->nombre)),
                            self::calcSimilitud($unidNorm, self::normalizarTexto($u->descripcion ?? ''))
                        );
                        if ($pct > $mejorUPct) { $mejorUPct = $pct; $mejorUnid = $u; }
                    }
                    if ($mejorUPct >= 95 && $mejorUnid) {
                        $item['unidad_medida_id']     = $mejorUnid->id;
                        $item['unidad_medida_nombre'] = $mejorUnid->abreviacion;
                    } else {
                        $item['unidad_warning'] = [
                            'excel'         => $unidadExcel,
                            'sugerencia'    => $mejorUnid?->abreviacion,
                            'sugerencia_id' => $mejorUnid?->id,
                            'similitud'     => round(min($mejorUPct, 100), 1),
                        ];
                    }
                }
            }

            // ── Coincidencia de producto (in-memory, sin query por fila) ──
            $descNorm      = self::normalizarTexto($desc);
            $tieneWarnings = $item['unidad_warning'] !== null || $item['monto_warning'] !== null;

            if (isset($prodIdx[$descNorm])) {
                $producto = $prodIdx[$descNorm];
                $item['producto_id']       = $producto->id;
                $item['producto_nombre']   = $producto->nombre;
                $item['contenedor_id']     = $producto->contenedor;
                $item['similitud']         = 100.0;
                $item['sugerencia_id']     = $producto->id;
                $item['sugerencia_nombre'] = $producto->nombre;

                // Detectar discrepancia de unidad: Excel resolvió unidad X pero producto tiene unidad Y
                if ($item['unidad_medida_id'] !== null
                    && $producto->unidad_medida_id !== null
                    && $item['unidad_medida_id'] !== (int) $producto->unidad_medida_id) {
                    $item['unidad_discrepancia'] = [
                        'excel_id'       => $item['unidad_medida_id'],
                        'excel_nombre'   => $item['unidad_medida_nombre'] ?? $item['unidad'],
                        'producto_um_id' => (int) $producto->unidad_medida_id,
                        'producto_nombre'=> $unidMap->get($producto->unidad_medida_id)?->abreviacion ?? '—',
                    ];
                    $tieneWarnings = true;
                }

                $tieneWarnings ? ($conflictos[] = $item) : ($exactos[] = $item);
                continue;
            }

            // Fuzzy matching en memoria — un solo recorrido sin queries BD
            $mejorPct  = 0;
            $mejorProd = null;
            foreach ($productosDB as $p) {
                $pct = self::calcSimilitud($descNorm, self::normalizarTexto($p->nombre));
                if ($pct > $mejorPct) { $mejorPct = $pct; $mejorProd = $p; }
            }

            $item['similitud']         = round(min($mejorPct, 100), 1);
            $item['sugerencia_id']     = $mejorProd?->id;
            $item['sugerencia_nombre'] = $mejorProd?->nombre;

            // ≥95% sin advertencias → verificar discrepancia de unidad antes de clasificar
            if ($mejorPct >= 95 && !$tieneWarnings && $mejorProd) {
                $item['producto_id']     = $mejorProd->id;
                $item['producto_nombre'] = $mejorProd->nombre;
                $item['contenedor_id']   = $mejorProd->contenedor;

                if ($item['unidad_medida_id'] !== null
                    && $mejorProd->unidad_medida_id !== null
                    && $item['unidad_medida_id'] !== (int) $mejorProd->unidad_medida_id) {
                    $item['unidad_discrepancia'] = [
                        'excel_id'       => $item['unidad_medida_id'],
                        'excel_nombre'   => $item['unidad_medida_nombre'] ?? $item['unidad'],
                        'producto_um_id' => (int) $mejorProd->unidad_medida_id,
                        'producto_nombre'=> $unidMap->get($mejorProd->unidad_medida_id)?->abreviacion ?? '—',
                    ];
                    $conflictos[] = $item;
                } else {
                    $exactos[] = $item;
                }
            } else {
                $conflictos[] = $item;
            }
        }

        // Leer ID de SICD pre-enlazada desde el modal del dashboard (puede ser null)
        $sicdPreEnlazadoId = (int)($request->input('sicd_preenlazado_id')) ?: null;

        // Crear SICD temporal para rastrear el proceso — se activará al confirmar
        // o se eliminará (soft delete) si el usuario cancela/abandona.
        // Solo crear si no hay una SICD pre-enlazada que ya la cubra.
        $sicdTemporal = \App\Models\Sicd::withoutGlobalScope('sin_temporales')->create([
            'codigo_sicd' => $codigoSicd,
            'descripcion' => $descripcion,
            'estado'      => 'pendiente',
            'es_temporal' => true,
            'usuario_id'  => Auth::id(),
        ]);
        $sicdTempId = $sicdTemporal->id;

        // Si hay conflictos → guardar en sesión y resolver primero
        if (!empty($conflictos)) {
            session([
                'carga_masiva_pendiente' => [
                    'codigo_sicd'          => $codigoSicd,
                    'descripcion'          => $descripcion,
                    'motivo'               => $motivo,
                    'vincular_oc'          => $vincularOc,
                    'boleta_ruta'          => $boletaTempRuta,
                    'boleta_nombre'        => $boletaNombreOrig,
                    'sicd_id_temporal'     => $sicdTempId,
                    'sicd_id_preenlazado'  => $sicdPreEnlazadoId,
                    'exactos'              => $exactos,
                    'conflictos'           => $conflictos,
                ],
            ]);
            return redirect()->route('admin.productos.carga.masiva.resolver');
        }

        // Sin conflictos → ir a asignar contenedores
        session([
            'carga_masiva_items' => [
                'codigo_sicd'         => $codigoSicd,
                'descripcion'         => $descripcion,
                'motivo'              => $motivo,
                'vincular_oc'         => $vincularOc,
                'boleta_ruta'         => $boletaTempRuta,
                'boleta_nombre'       => $boletaNombreOrig,
                'sicd_id_temporal'    => $sicdTempId,
                'sicd_id_preenlazado' => $sicdPreEnlazadoId,
                'items'               => $exactos,
            ],
        ]);
        return redirect()->route('admin.productos.carga.masiva.contenedores');
    }

    public function resolverCargaMasiva()
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $pendiente = session('carga_masiva_pendiente');
        if (!$pendiente) {
            return redirect()->route('dashboard')->with('error', 'Sesión expirada. Vuelve a cargar el Excel.');
        }
        $ccId       = auth()->user()->ccFiltro();
        $productos  = Producto::orderBy('nombre')->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))->get(['id', 'nombre', 'categoria_id', 'marca_id']);

        // Familias: incluye siempre las de CC nulo (SIN FAMILIA, PARTES Y PIEZAS, etc.)
        $familias = Familia::with([
            'categorias' => fn($q) => $q->with(['marcas' => fn($q2) => $q2->activas()]),
        ])->where('activo', true)
          ->when($ccId, fn($q) => $q->where(function ($inner) use ($ccId) {
              $inner->where('centro_costo_id', $ccId)->orWhereNull('centro_costo_id');
          }))
          ->orderBy('nombre')
          ->get();

        $containers = Container::orderBy('nombre')->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))->get(['id', 'nombre']);
        $unidades   = UnidadMedida::activas()->noEsPresentacion()->orderBy('abreviacion')->get(['id', 'abreviacion', 'nombre']);
        // tipo column in familias drives SIN FAMILIA / PYP detection in JS (no hardcoded IDs)
        return view('admin.productos.resolver-carga-masiva',
            compact('pendiente', 'productos', 'familias', 'containers', 'unidades'));
    }

    public function confirmarCargaMasiva(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $pendiente = session('carga_masiva_pendiente');
        if (!$pendiente) {
            return redirect()->route('dashboard')->with('error', 'Sesión expirada. Vuelve a cargar el Excel.');
        }

        $resoluciones = $request->input('resoluciones', []);
        $items = $pendiente['exactos'];

        foreach ($pendiente['conflictos'] as $idx => $conflicto) {
            $res    = $resoluciones[$idx] ?? [];
            $accion = $res['accion'] ?? 'omitir';

            // ── Resolución de unidad ───────────────────────────────────────
            if ($conflicto['unidad_warning'] !== null) {
                $unidAccion = $res['unidad_accion'] ?? 'aceptar';
                if ($unidAccion === 'manual' && !empty($res['unidad_medida_id_manual'])) {
                    $conflicto['unidad_medida_id'] = (int) $res['unidad_medida_id_manual'];
                } elseif ($unidAccion === 'aceptar' && !empty($conflicto['unidad_warning']['sugerencia_id'])) {
                    $conflicto['unidad_medida_id'] = (int) $conflicto['unidad_warning']['sugerencia_id'];
                } else {
                    $conflicto['unidad_medida_id'] = null;
                }
            }

            // ── Resolución de producto ─────────────────────────────────────
            if ($accion === 'enlazar' && !empty($res['producto_id'])) {
                $linked = Producto::find((int) $res['producto_id']);
                $conflicto['producto_id']     = (int) $res['producto_id'];
                $conflicto['producto_nombre'] = $linked?->nombre;
                $conflicto['contenedor_id']   = $linked?->contenedor;
            } elseif ($accion === 'nuevo') {
                $conflicto['producto_id']        = null;
                $conflicto['accion']             = 'nuevo';
                $conflicto['nuevo_nombre']       = $conflicto['descripcion'];
                $conflicto['nuevo_categoria_id'] = !empty($res['nuevo_categoria_id']) ? (int) $res['nuevo_categoria_id'] : null;
                $conflicto['nuevo_marca_id']     = !empty($res['nuevo_marca_id'])     ? (int) $res['nuevo_marca_id']     : null;
                $conflicto['nuevo_stock_minimo']  = (int) ($res['nuevo_stock_minimo']  ?? 0);
                $conflicto['nuevo_stock_critico'] = (int) ($res['nuevo_stock_critico'] ?? 0);
                $conflicto['contenedor_id']      = null;
                // Package settings from modal (override any Excel-provided values)
                $manejaPres = !empty($res['nuevo_maneja_presentacion']);
                $conflicto['maneja_presentacion']   = $manejaPres;
                $conflicto['tipo_presentacion']     = $manejaPres ? ($res['nuevo_tipo_presentacion']     ?? null) : null;
                $conflicto['cantidad_presentacion'] = $manejaPres && !empty($res['nuevo_cantidad_presentacion'])
                    ? (int) $res['nuevo_cantidad_presentacion'] : null;
                $conflicto['unidad_base']           = $manejaPres ? ($res['nuevo_unidad_base'] ?? null) : null;
            } else {
                $conflicto['producto_id'] = null;
            }

            $items[] = $conflicto;
        }

        session()->forget('carga_masiva_pendiente');
        session([
            'carga_masiva_items' => [
                'codigo_sicd'         => $pendiente['codigo_sicd'],
                'descripcion'         => $pendiente['descripcion'],
                'motivo'              => $pendiente['motivo'],
                'vincular_oc'         => $pendiente['vincular_oc'],
                'boleta_ruta'         => $pendiente['boleta_ruta'] ?? null,
                'boleta_nombre'       => $pendiente['boleta_nombre'] ?? null,
                'sicd_id_temporal'    => $pendiente['sicd_id_temporal'] ?? null,
                'sicd_id_preenlazado' => $pendiente['sicd_id_preenlazado'] ?? null,
                'items'               => $items,
            ],
        ]);
        return redirect()->route('admin.productos.carga.masiva.contenedores');
    }

    public function cancelarCargaMasiva(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $pendientePasos = session('carga_masiva_pendiente');
        $pendienteItems = session('carga_masiva_items');
        $pendiente      = $pendientePasos ?? $pendienteItems;

        // Eliminar boleta temporal
        if ($pendiente && !empty($pendiente['boleta_ruta'])) {
            Storage::disk('local')->delete($pendiente['boleta_ruta']);
        }

        // Soft-delete la SICD temporal (puede estar en cualquiera de los dos pasos)
        $sicdTempId = $pendientePasos['sicd_id_temporal']
            ?? $pendienteItems['sicd_id_temporal']
            ?? null;
        if ($sicdTempId) {
            \App\Models\Sicd::withoutGlobalScope('sin_temporales')
                ->where('id', $sicdTempId)
                ->where('es_temporal', true)
                ->whereNull('deleted_at')
                ->first()
                ?->delete();
        }

        // Soft-delete la SICD pre-enlazada desde el modal (también es temporal)
        $sicdPreEnlazadoId = $pendientePasos['sicd_id_preenlazado']
            ?? $pendienteItems['sicd_id_preenlazado']
            ?? null;
        if ($sicdPreEnlazadoId) {
            \App\Models\Sicd::withoutGlobalScope('sin_temporales')
                ->where('id', $sicdPreEnlazadoId)
                ->where('es_temporal', true)
                ->whereNull('deleted_at')
                ->first()
                ?->delete();
        }

        session()->forget('carga_masiva_pendiente');
        session()->forget('carga_masiva_items');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('dashboard')->with('info', 'Carga masiva cancelada.');
    }

    public function asignarContenedoresMasiva()
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $pendiente = session('carga_masiva_items');
        if (!$pendiente) {
            return redirect()->route('dashboard')->with('error', 'Sesión expirada. Vuelve a cargar el Excel.');
        }
        $containers = Container::orderBy('nombre')->get(['id', 'nombre']);
        return view('admin.productos.asignar-contenedores-masiva', compact('pendiente', 'containers'));
    }

    public function confirmarContenedoresMasiva(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $pendiente = session('carga_masiva_items');
        if (!$pendiente) {
            return redirect()->route('dashboard')->with('error', 'Sesión expirada. Vuelve a cargar el Excel.');
        }

        $contenedores = $request->input('contenedores', []);
        $items = $pendiente['items'];

        foreach ($items as $i => &$item) {
            $item['contenedor_id'] = (int) ($contenedores[$i] ?? ($item['contenedor_id'] ?? 1));
        }
        unset($item);

        session()->forget('carga_masiva_items');

        return $this->procesarCargaMasiva(
            $items,
            $pendiente['codigo_sicd'],
            $pendiente['descripcion'],
            $pendiente['motivo'],
            $pendiente['vincular_oc'],
            $pendiente['boleta_ruta']         ?? null,
            $pendiente['boleta_nombre']       ?? null,
            $pendiente['sicd_id_temporal']    ?? null,
            $pendiente['sicd_id_preenlazado'] ?? null
        );
    }

    private function procesarCargaMasiva(array $items, string $codigoSicd, ?string $descripcion, string $motivo, bool $vincularOc, ?string $boletaTempRuta = null, ?string $boletaNombre = null, ?int $sicdTempId = null, ?int $sicdPreEnlazadoId = null)
    {
        $sicd         = null;
        $actualizados = 0;

        if ($codigoSicd) {
            $boletaId = null;
            if (!$vincularOc && $boletaTempRuta && Storage::disk('local')->exists($boletaTempRuta)) {
                $rutaAbsoluta = Storage::disk('local')->path($boletaTempRuta);
                \DB::unprepared('SET GLOBAL max_allowed_packet=67108864');
                $boleta   = \App\Models\Boleta::create([
                    'archivo_nombre' => $boletaNombre ?? basename($boletaTempRuta),
                    'archivo_blob'   => base64_encode(file_get_contents($rutaAbsoluta)),
                    'archivo_mime'   => mime_content_type($rutaAbsoluta) ?: 'application/octet-stream',
                ]);
                $boletaId = $boleta->id;
            }

            // Prioridad 1: SICD pre-enlazada por el modal del dashboard (tiene PDF y es temporal)
            // Buscar primero por ID directo, luego por código como fallback
            $sicdPreLinked = null;
            if ($sicdPreEnlazadoId) {
                $sicdPreLinked = Sicd::withoutGlobalScope('sin_temporales')
                    ->where('id', $sicdPreEnlazadoId)
                    ->whereNotNull('documento_blob')
                    ->whereDoesntHave('detalles')
                    ->first();
            }
            if (!$sicdPreLinked) {
                $sicdPreLinked = Sicd::withoutGlobalScope('sin_temporales')
                    ->where('codigo_sicd', $codigoSicd)
                    ->whereNotNull('documento_blob')
                    ->whereDoesntHave('detalles')
                    ->latest()
                    ->first();
            }

            if ($sicdPreLinked) {
                // Usar el pre-enlazado; eliminar la temporal para evitar duplicado
                if ($sicdTempId) {
                    Sicd::withoutGlobalScope('sin_temporales')
                        ->where('id', $sicdTempId)
                        ->where('es_temporal', true)
                        ->whereNull('deleted_at')
                        ->first()
                        ?->delete();
                }
                $sicd = $sicdPreLinked;
                $sicd->fill([
                    'es_temporal' => false,
                    'boleta_id'   => $boletaId,
                    'descripcion' => $descripcion,
                    'estado'      => $vincularOc ? 'pendiente' : 'recibido',
                    'usuario_id'  => Auth::id(),
                ])->save();
            } elseif ($sicdTempId) {
                // Prioridad 2: activar la SICD temporal creada al inicio del proceso
                $tempSicd = Sicd::withoutGlobalScope('sin_temporales')
                    ->where('id', $sicdTempId)
                    ->where('es_temporal', true)
                    ->first();
                if ($tempSicd) {
                    $tempSicd->fill([
                        'es_temporal' => false,
                        'boleta_id'   => $boletaId,
                        'descripcion' => $descripcion,
                        'estado'      => $vincularOc ? 'pendiente' : 'recibido',
                        'usuario_id'  => Auth::id(),
                    ])->save();
                    $sicd = $tempSicd;
                }
                // Fallback si la temporal ya fue eliminada por alguna razón
                if (!$sicd) {
                    $sicd = Sicd::create([
                        'codigo_sicd' => $codigoSicd,
                        'boleta_id'   => $boletaId,
                        'descripcion' => $descripcion,
                        'estado'      => $vincularOc ? 'pendiente' : 'recibido',
                        'usuario_id'  => Auth::id(),
                    ]);
                }
            } else {
                // Sin SICD temporal (ruta legacy) — crear nuevo
                $sicd = Sicd::create([
                    'codigo_sicd' => $codigoSicd,
                    'boleta_id'   => $boletaId,
                    'descripcion' => $descripcion,
                    'estado'      => $vincularOc ? 'pendiente' : 'recibido',
                    'usuario_id'  => Auth::id(),
                ]);
            }
        }

        $ccId = auth()->user()->centro_costo_id;

        DB::transaction(function () use ($items, $motivo, $sicd, $vincularOc, &$actualizados, $ccId) {
            foreach ($items as $item) {
                $productoId = $item['producto_id'] ?? null;

                // Crear producto nuevo si el usuario eligió esa opción
                if (!$productoId && ($item['accion'] ?? '') === 'nuevo' && !empty($item['nuevo_nombre'])) {
                    $manejaPres = !($item['es_servicio'] ?? false) && ($item['maneja_presentacion'] ?? false);
                    $nuevo = Producto::create([
                        'nombre'                => $item['nuevo_nombre'],
                        'categoria_id'          => $item['nuevo_categoria_id']  ?? null,
                        'marca_id'              => $item['nuevo_marca_id']      ?? Marca::idSinMarca(),
                        'unidad_medida_id'      => $item['unidad_medida_id']    ?? null,
                        'stock_actual'          => 0,
                        'stock_minimo'          => (int) ($item['nuevo_stock_minimo']  ?? 0),
                        'stock_critico'         => (int) ($item['nuevo_stock_critico'] ?? 0),
                        'contenedor'            => $item['contenedor_id'] ?? 1,
                        'centro_costo_id'       => $ccId,
                        'es_servicio'           => $item['es_servicio'] ?? false,
                        'maneja_presentacion'   => $manejaPres,
                        'tipo_presentacion'     => $manejaPres ? ($item['tipo_presentacion'] ?? null)     : null,
                        'cantidad_presentacion' => $manejaPres ? ($item['cantidad_presentacion'] ?? null) : null,
                        'unidad_base'           => $manejaPres ? ($item['unidad_base'] ?? null)           : null,
                    ]);
                    $productoId = $nuevo->id;
                }

                // Si el usuario cambió el contenedor para un producto existente,
                // buscar o crear el producto en el nuevo contenedor
                if ($productoId && !empty($item['contenedor_id'])) {
                    $prod = Producto::find($productoId);
                    if ($prod && $prod->contenedor != $item['contenedor_id']) {
                        $enDestino = Producto::where('nombre', $prod->nombre)
                            ->where('contenedor', $item['contenedor_id'])
                            ->first();
                        if ($enDestino) {
                            $productoId = $enDestino->id;
                        } else {
                            $contDest2 = Container::withoutGlobalScope('con_cc')->find($item['contenedor_id']);
                            $copia = Producto::create([
                                'nombre'          => $prod->nombre,
                                'stock_actual'    => 0,
                                'stock_minimo'    => $prod->stock_minimo,
                                'stock_critico'   => $prod->stock_critico,
                                'contenedor'      => $item['contenedor_id'],
                                'centro_costo_id' => $contDest2?->centro_costo_id ?? $ccId,
                            ]);
                            $productoId = $copia->id;
                        }
                    }
                }

                if ($sicd) {
                    $sicd->detalles()->create([
                        'producto_id'           => $productoId,
                        'nombre_producto_excel' => $item['descripcion'],
                        'unidad'                => $item['unidad'] ?: null,
                        'cantidad_solicitada'   => $item['cantidad'],
                        // Si va a OC, la recepción real ocurre después → cantidad_recibida = 0
                        'cantidad_recibida'     => (!$vincularOc && $productoId) ? $item['cantidad'] : 0,
                        'precio_neto'           => $item['precioNeto'] ?? null,
                        'total_neto'            => $item['totalNeto'] ?? null,
                    ]);
                }

                // Si va a OC no tocar el stock ahora; se actualizará al procesar la recepción
                if ($vincularOc || !$productoId) continue;

                $producto = Producto::find($productoId);
                if (!$producto) continue;

                // cantidad_real = real units (quantity × cantidad_presentacion, or quantity when no presentacion)
                $cantidadReal = (int) ($item['cantidad_real'] ?? $item['cantidad']);

                // Si el producto ya existe y tiene presentación, verificar si la cantidad es en presentaciones
                // (para producto existente con presentacion, el Excel puede traer en presentaciones)
                if ($cantidadReal === (int) $item['cantidad'] && $producto->tienePresentacion()) {
                    // Producto existente con presentación: multiplicar si el item no trajo su propio factor
                    if (!isset($item['maneja_presentacion']) || !$item['maneja_presentacion']) {
                        // Backwards compat: si el Excel no trae columnas G/H/I, cantidad = real units
                        $cantidadReal = (int) $item['cantidad'];
                    }
                }

                $stockAntes = $producto->stock_actual;

                // Servicios: registrar en historial para costos/BINCARD, pero NO tocar stock_actual
                if (!$producto->es_servicio) {
                    $producto->stock_actual += $cantidadReal;
                    // Actualizar presentación del producto si el Excel trae datos y el producto aún no la tiene
                    if (!$producto->maneja_presentacion && ($item['maneja_presentacion'] ?? false)) {
                        $producto->fill([
                            'maneja_presentacion'   => true,
                            'tipo_presentacion'     => $item['tipo_presentacion'] ?? null,
                            'cantidad_presentacion' => $item['cantidad_presentacion'] ?? null,
                            'unidad_base'           => $item['unidad_base'] ?? null,
                        ]);
                    }
                    $producto->actualizarFechasStock();
                    $producto->save();
                }

                HistorialCambio::create([
                    'producto_id'        => $producto->id,
                    'nombre_producto'    => $producto->nombre,
                    'contenedor_id'      => $producto->contenedor,
                    'cantidad'           => $cantidadReal,
                    'tipo'               => 'entrada',
                    'motivo'             => $sicd ? "Carga masiva – SICD {$sicd->codigo_sicd}" : $motivo,
                    'aprobado_por'       => Auth::user()->name,
                    'usuario_id'         => Auth::id(),
                    'origen'             => $sicd ? 'sicd' : null,
                    'origen_id'          => $sicd ? $sicd->id : null,
                    'origen_tipo'        => $sicd ? 'sicd' : 'entrada_manual',
                    'doc_origen'         => $sicd ? 'SICD ' . $sicd->codigo_sicd : null,
                    'stock_anterior'     => $stockAntes,
                    'stock_posterior'    => $producto->stock_actual,
                    'usuario_ejecutor_id'=> Auth::id(),
                ]);

                $actualizados++;
            }
        });

        if ($vincularOc && $sicd) {
            $msg = "SICD {$sicd->codigo_sicd} creado con {$sicd->detalles()->count()} producto(s). Stock pendiente — asígnalo a una Orden de Compra para recibirlo.";
            return redirect()->route('admin.ordenes.create')->with('success', $msg);
        }

        $msg = "Carga masiva completada: {$actualizados} producto(s) actualizado(s).";

        return redirect()->route('dashboard')->with('success', $msg);
    }

    public function cargaManual(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $request->validate([
            'items_manual'                  => ['required', 'array', 'min:1'],
            'items_manual.*.producto_id'    => ['required', 'integer', 'exists:productos,id'],
            'items_manual.*.cantidad'       => ['required', 'integer', 'min:1'],
            'items_manual.*.contenedor_id'  => ['nullable', 'integer', 'exists:containers,id'],
            'items_manual.*.unidad'         => ['nullable', 'string', 'max:50'],
            'items_manual.*.precio_neto'    => ['nullable', 'numeric', 'min:0'],
            'items_manual.*.precio_total'   => ['nullable', 'numeric', 'min:0'],
        ], [
            'items_manual.required'              => 'Agrega al menos un producto.',
            'items_manual.*.producto_id.required' => 'Cada fila debe tener un producto.',
            'items_manual.*.cantidad.min'         => 'La cantidad debe ser al menos 1.',
        ]);

        $codigoSicd      = strtoupper(trim($request->input('codigo_sicd', ''))) ?: null;
        $descripcion     = trim($request->input('descripcion', '')) ?: null;
        $vincularOc      = (bool) $request->input('vincular_oc_manual');
        $permiteMasOc    = (bool) $request->input('permite_mas_oc');
        $proveedorNombre = strtoupper(trim($request->input('proveedor_nombre', ''))) ?: null;
        $rutProveedor    = trim($request->input('rut_proveedor', '')) ?: null;
        $folio           = trim($request->input('folio', '')) ?: null;

        // Advertir si la SICD ya está ingresada en el sistema (tiene detalles)
        if ($codigoSicd && !$request->boolean('confirmar_duplicado')) {
            $sicdExistente = Sicd::where('codigo_sicd', $codigoSicd)->whereHas('detalles')->latest()->first();
            if ($sicdExistente) {
                return back()
                    ->withInput()
                    ->with('sicd_duplicada', [
                        'codigo' => $codigoSicd,
                        'id'     => $sicdExistente->id,
                        'estado' => $sicdExistente->estado,
                        'url'    => route('admin.sicd.show', $sicdExistente->id),
                    ]);
            }
        }

        // Crear registro SICD si viene con código externo
        $sicd = null;
        if ($codigoSicd) {
            $boletaId = null;
            if (!$vincularOc && $request->hasFile('boleta_sicd')) {
                $file = $request->file('boleta_sicd');
                \DB::unprepared('SET GLOBAL max_allowed_packet=67108864');
                $boleta   = \App\Models\Boleta::create([
                    'archivo_nombre' => $file->getClientOriginalName(),
                    'archivo_blob'   => base64_encode(file_get_contents($file->getRealPath())),
                    'archivo_mime'   => $file->getMimeType(),
                ]);
                $boletaId = $boleta->id;
            }
            // Reutilizar SICD pre-enlazado (usuario hizo clic en "Enlazar PDF" antes de confirmar)
            $sicd = Sicd::where('codigo_sicd', $codigoSicd)
                ->whereNotNull('documento_blob')
                ->whereDoesntHave('detalles')
                ->latest()
                ->first();

            if ($sicd) {
                $sicd->fill([
                    'boleta_id'       => $boletaId,
                    'descripcion'     => $descripcion,
                    'estado'          => $vincularOc ? 'pendiente' : 'recibido',
                    'permite_mas_oc'  => $permiteMasOc,
                    'proveedor_nombre'=> $proveedorNombre,
                    'rut_proveedor'   => $rutProveedor,
                    'folio'           => $folio,
                    'usuario_id'      => Auth::id(),
                ])->save();
            } else {
                $sicd = Sicd::create([
                    'codigo_sicd'     => $codigoSicd,
                    'boleta_id'       => $boletaId,
                    'descripcion'     => $descripcion,
                    'estado'          => $vincularOc ? 'pendiente' : 'recibido',
                    'permite_mas_oc'  => $permiteMasOc,
                    'proveedor_nombre'=> $proveedorNombre,
                    'rut_proveedor'   => $rutProveedor,
                    'folio'           => $folio,
                    'usuario_id'      => Auth::id(),
                ]);
            }
        }

        $ccIdManual = auth()->user()->centro_costo_id;

        DB::transaction(function () use ($request, $sicd, $codigoSicd, $ccIdManual, $vincularOc) {
            foreach ($request->items_manual as $item) {
                $producto     = Producto::withoutGlobalScopes()->findOrFail($item['producto_id']);
                $cantidad     = (int) $item['cantidad'];
                $unidad       = trim($item['unidad'] ?? '') ?: null;
                $motivo       = trim($item['motivo'] ?? '') ?: ($codigoSicd ? "Carga manual – SICD {$codigoSicd}" : 'Carga manual de inventario');
                $contenedorId = isset($item['contenedor_id']) ? (int) $item['contenedor_id'] : null;

                if (!$vincularOc && $contenedorId && $contenedorId !== $producto->contenedor) {
                    $enDestino = Producto::withoutGlobalScopes()->where('nombre', $producto->nombre)
                        ->where('contenedor', $contenedorId)
                        ->first();
                    if ($enDestino) {
                        $producto = $enDestino;
                    } else {
                        $contDest = Container::withoutGlobalScope('con_cc')->find($contenedorId);
                        $producto = Producto::create([
                            'nombre'          => $producto->nombre,
                            'unidad'          => $unidad ?? $producto->unidad,
                            'stock_actual'    => 0,
                            'stock_minimo'    => $producto->stock_minimo,
                            'stock_critico'   => $producto->stock_critico,
                            'contenedor'      => $contenedorId,
                            'centro_costo_id' => $contDest?->centro_costo_id ?? $ccIdManual,
                        ]);
                    }
                }

                if ($unidad && $producto->unidad !== $unidad) {
                    $producto->unidad = $unidad;
                }

                if ($sicd) {
                    $precioNeto  = isset($item['precio_neto'])  && $item['precio_neto']  !== '' ? (float) $item['precio_neto']  : null;
                    $precioTotal = isset($item['precio_total']) && $item['precio_total'] !== '' ? (float) $item['precio_total'] : null;
                    if ($precioNeto === null && $precioTotal !== null && $cantidad > 0) {
                        $precioNeto = round($precioTotal / $cantidad, 2);
                    }
                    $sicd->detalles()->create([
                        'producto_id'           => $producto->id,
                        'nombre_producto_excel' => $producto->nombre,
                        'unidad'                => trim($item['unidad'] ?? '') ?: null,
                        'cantidad_solicitada'   => $cantidad,
                        'cantidad_recibida'     => $cantidad,
                        'precio_neto'           => $precioNeto,
                        'total_neto'            => $precioTotal,
                    ]);
                }

                if (!$vincularOc) {
                    $stockAntes = $producto->stock_actual;
                    $producto->stock_actual += $cantidad;
                    $producto->actualizarFechasStock();
                    $producto->save();

                    HistorialCambio::create([
                        'producto_id'        => $producto->id,
                        'nombre_producto'    => $producto->nombre,
                        'contenedor_id'      => $producto->contenedor,
                        'cantidad'           => $cantidad,
                        'tipo'               => 'entrada',
                        'motivo'             => $motivo,
                        'aprobado_por'       => Auth::user()->name,
                        'usuario_id'         => Auth::id(),
                        'origen'             => $sicd ? 'sicd' : null,
                        'origen_id'          => $sicd ? $sicd->id : null,
                        'origen_tipo'        => $sicd ? 'sicd' : 'entrada_manual',
                        'doc_origen'         => $sicd ? 'SICD ' . $sicd->codigo_sicd : null,
                        'stock_anterior'     => $stockAntes,
                        'stock_posterior'    => $producto->stock_actual,
                        'usuario_ejecutor_id'=> Auth::id(),
                    ]);
                } else {
                    $producto->save();
                }

                if ($precioNeto && $precioNeto > 0) {
                    Precio::registrar(
                        producto:   $producto,
                        precioNeto: $precioNeto,
                        cantidad:   $cantidad,
                        fuente:     $sicd ? 'sicd_manual' : 'manual',
                        origenId:   $sicd?->id,
                        origenTipo: $sicd ? 'Sicd' : null,
                        precioTotal: $precioTotal,
                        notas:      $codigoSicd ? "SICD {$codigoSicd}" : 'Carga manual',
                    );
                }
            }
        });

        if ($sicd) {
            return redirect()->route('admin.sicd.show', $sicd->id)
                ->with('success', "SICD {$codigoSicd} creado y stock actualizado.");
        }

        return redirect()->route('dashboard')
            ->with('success', 'Stock actualizado correctamente.');
    }

    public function crearProductoRapido(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $request->validate([
            'categoria_id'    => ['required', 'integer', 'exists:categorias,id'],
            'marca_id'        => ['nullable', 'integer', 'exists:marcas,id'],
            'nombre'          => ['required', 'string', 'max:500'],
            'stock_minimo'    => ['nullable', 'integer', 'min:0'],
            'stock_critico'   => ['nullable', 'integer', 'min:0'],
            'unidad_medida_id'=> ['nullable', 'integer', 'exists:unidades_medida,id'],
            'es_servicio'     => ['nullable', 'boolean'],
        ]);

        $categoria          = Categoria::with('familia')->findOrFail($request->categoria_id);
        $esFamiliaServicios = $categoria->familia?->tipo === 'servicios';

        $producto = \App\Models\Producto::create([
            'nombre'           => trim($request->nombre),
            'stock_actual'     => 0,
            'stock_minimo'     => (int) ($request->stock_minimo ?? 0),
            'stock_critico'    => (int) ($request->stock_critico ?? 0),
            'contenedor'       => null,
            'categoria_id'     => $categoria->id,
            'marca_id'         => $esFamiliaServicios ? Marca::idSinMarca() : ($request->marca_id ?: Marca::idSinMarca()),
            'centro_costo_id'  => auth()->user()->centro_costo_id,
            'unidad_medida_id' => $request->unidad_medida_id ?: null,
            'es_servicio'      => $esFamiliaServicios || $request->boolean('es_servicio', false),
        ]);

        $producto->load('unidadMedida:id,abreviacion');

        return response()->json([
            'id'               => $producto->id,
            'nombre'           => $producto->nombre,
            'unidad'           => $producto->unidadMedida?->abreviacion ?? '',
            'contenedor_id'    => null,
            'contenedor_nombre'=> '',
            'stock'            => 0,
        ]);
    }

    /**
     * Lee un Excel con PhpSpreadsheet en modo datos-only (sin estilos ni fórmulas).
     * Usa 95% menos memoria que el modo estándar. Devuelve Collection de arrays.
     */
    private function leerExcelLigero(\Illuminate\Http\UploadedFile $file): \Illuminate\Support\Collection
    {
        $path   = $file->getRealPath();
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rawRows     = $sheet->toArray(null, true, true, false);

        // Liberar memoria del spreadsheet inmediatamente
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // Saltar fila 1 (cabeceras) y convertir a Collection igual que SicdDetallesImport
        $rows = collect(array_slice($rawRows, 1))
            ->map(fn($row) => collect(array_values($row)));

        return $rows;
    }
}
