<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Producto;
use App\Models\Container;
use App\Models\HistorialCambio;
use App\Models\Sicd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SicdDetallesImport;

class AdminController extends Controller
{
    public function solicitudes()
    {
        abort_unless(auth()->user()->tienePermiso('solicitudes') || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);
        $solicitudes = Solicitud::with(['producto.container', 'usuario'])
            ->where('estado', 'pendiente')
            ->orderByDesc('created_at')
            ->get();

        $containers = Container::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.solicitudes', compact('solicitudes', 'containers'));
    }

    public function aprobar(int $id)
    {
        abort_unless(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'), 403);
        $solicitud = Solicitud::with('producto')->findOrFail($id);

        if ($solicitud->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $producto = $solicitud->producto;

        // Validar stock negativo para salidas
        if ($solicitud->tipo === 'salida') {
            if ($producto->stock_actual < $solicitud->cantidad) {
                return back()->with('error',
                    "Stock insuficiente. Stock actual: {$producto->stock_actual}, solicitado: {$solicitud->cantidad}.");
            }
        }

        DB::transaction(function () use ($solicitud, $producto) {
            // Actualizar stock
            if ($solicitud->tipo === 'entrada') {
                $producto->stock_actual += $solicitud->cantidad;
            } else {
                $producto->stock_actual -= $solicitud->cantidad;
            }
            $producto->actualizarFechasStock();
            $producto->save();

            // Cambiar estado de la solicitud
            $solicitud->estado = 'aprobado';
            $solicitud->save();

            // Registrar en historial
            HistorialCambio::create([
                'producto_id'  => $solicitud->producto_id,
                'cantidad'     => $solicitud->cantidad,
                'tipo'         => $solicitud->tipo,
                'motivo'       => $solicitud->motivo,
                'aprobado_por' => Auth::user()->name,
                'usuario_id'   => $solicitud->usuario_id,
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
        $solicitudes = Solicitud::with(['producto', 'usuario'])
            ->where('estado', 'rechazado')
            ->orderByDesc('created_at')
            ->get();

        $productosAgrupados = Producto::orderBy('nombre')->orderBy('descripcion')
            ->get(['id', 'nombre', 'descripcion'])
            ->groupBy('nombre');

        $fSolicitantes = $solicitudes->pluck('usuario.name')->filter()->unique()->sort()->values();

        return view('admin.solicitudes.rechazadas', compact('solicitudes', 'productosAgrupados', 'fSolicitantes'));
    }

    public function historial()
    {
        abort_unless(auth()->user()->tienePermiso('historial'), 403);
        $historial = HistorialCambio::with(['producto', 'usuario', 'sicd'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.historial', compact('historial'));
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

        if ($data['tipo'] === 'salida' && $producto->stock_actual < $data['cantidad']) {
            return back()->withErrors([
                'cantidad' => "Stock insuficiente. Stock actual: {$producto->stock_actual}.",
            ])->withInput();
        }

        DB::transaction(function () use ($producto, $data) {
            if ($data['tipo'] === 'entrada') {
                $producto->stock_actual += $data['cantidad'];
            } else {
                $producto->stock_actual -= $data['cantidad'];
            }
            $producto->actualizarFechasStock();
            $producto->save();

            HistorialCambio::create([
                'producto_id'  => $producto->id,
                'cantidad'     => $data['cantidad'],
                'tipo'         => $data['tipo'],
                'motivo'       => $data['motivo'],
                'aprobado_por' => Auth::user()->name,
                'usuario_id'   => Auth::id(),
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

        $containerOrigen = Container::find($producto->contenedor);
        $containerDestino = Container::findOrFail($data['contenedor_destino']);

        DB::transaction(function () use ($producto, $data, $containerOrigen, $containerDestino) {
            $producto->contenedor = $data['contenedor_destino'];
            $producto->save();

            HistorialCambio::create([
                'producto_id'  => $producto->id,
                'cantidad'     => $producto->stock_actual,
                'tipo'         => 'traslado',
                'motivo'       => "Traslado de {$containerOrigen->nombre} a {$containerDestino->nombre}: {$data['motivo']}",
                'aprobado_por' => Auth::user()->name,
                'usuario_id'   => Auth::id(),
            ]);
        });

        return redirect()->route('dashboard')
            ->with('success', "Producto '{$producto->nombre}' trasladado a {$containerDestino->nombre} correctamente.");
    }

    public function cargaMasiva(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $request->validate([
            'excel_masivo'  => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'motivo_masivo' => ['nullable', 'string', 'max:500'],
            'codigo_sicd'   => ['nullable', 'string', 'max:100'],
            'descripcion'   => ['nullable', 'string', 'max:500'],
        ], [
            'excel_masivo.required' => 'El archivo Excel es obligatorio.',
            'excel_masivo.mimes'    => 'El archivo debe ser XLSX, XLS o CSV.',
        ]);

        $vincularOc  = $request->boolean('vincular_oc');
        $rows        = Excel::toCollection(new SicdDetallesImport, $request->file('excel_masivo'))->first();
        $motivo      = trim($request->input('motivo_masivo', '')) ?: 'Carga masiva de inventario';
        $codigoSicd  = strtoupper(trim($request->input('codigo_sicd', '')));
        $descripcion = $request->input('descripcion');

        $productosDB = Producto::whereNotNull('descripcion')->get(['id', 'nombre', 'descripcion']);
        $exactos     = [];
        $conflictos  = [];

        foreach ($rows as $row) {
            $desc       = trim((string) ($row[0] ?? ''));
            $unidad     = trim((string) ($row[1] ?? ''));
            $cantidad   = (int) ($row[2] ?? 0);
            $precioNeto = is_numeric($row[3] ?? '') ? (float) $row[3] : null;
            $totalNeto  = is_numeric($row[4] ?? '') ? (float) $row[4] : null;

            if ($desc === '' || $cantidad <= 0) continue;

            $item = [
                'descripcion' => $desc,
                'unidad'      => $unidad,
                'cantidad'    => $cantidad,
                'precioNeto'  => $precioNeto,
                'totalNeto'   => $totalNeto,
            ];

            // Coincidencia exacta por descripcion o nombre
            $producto = Producto::where('descripcion', $desc)->orWhere('nombre', $desc)->first();
            if ($producto) {
                $item['producto_id'] = $producto->id;
                $exactos[] = $item;
                continue;
            }

            // Fuzzy matching
            $descNorm  = strtolower($desc);
            $mejorPct  = 0;
            $mejorProd = null;
            foreach ($productosDB as $p) {
                $dist   = levenshtein($descNorm, strtolower($p->descripcion));
                $maxLen = max(strlen($descNorm), strlen($p->descripcion));
                $pct    = $maxLen > 0 ? (1 - $dist / $maxLen) * 100 : 0;
                if ($pct > $mejorPct) { $mejorPct = $pct; $mejorProd = $p; }
            }

            $item['similitud']          = round($mejorPct, 1);
            $item['sugerencia_id']      = $mejorProd?->id;
            $item['sugerencia_nombre']  = $mejorProd?->descripcion;
            $conflictos[] = $item;
        }

        // Si hay conflictos → guardar en sesión y resolver
        if (!empty($conflictos)) {
            session([
                'carga_masiva_pendiente' => [
                    'codigo_sicd' => $codigoSicd,
                    'descripcion' => $descripcion,
                    'motivo'      => $motivo,
                    'vincular_oc' => $vincularOc,
                    'exactos'     => $exactos,
                    'conflictos'  => $conflictos,
                ],
            ]);
            return redirect()->route('admin.productos.carga.masiva.resolver');
        }

        // Sin conflictos → procesar directamente
        return $this->procesarCargaMasiva($exactos, $codigoSicd, $descripcion, $motivo, $vincularOc);
    }

    public function resolverCargaMasiva()
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $pendiente = session('carga_masiva_pendiente');
        if (!$pendiente) {
            return redirect()->route('dashboard')->with('error', 'Sesión expirada. Vuelve a cargar el Excel.');
        }
        $productos  = Producto::whereNotNull('descripcion')->orderBy('descripcion')->get(['id', 'nombre', 'descripcion']);
        $familias   = Producto::select('nombre')->distinct()->orderBy('nombre')->pluck('nombre');
        $containers = Container::orderBy('nombre')->get(['id', 'nombre']);
        return view('admin.productos.resolver-carga-masiva', compact('pendiente', 'productos', 'familias', 'containers'));
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

            if ($accion === 'enlazar' && !empty($res['producto_id'])) {
                $conflicto['producto_id'] = (int) $res['producto_id'];
            } elseif ($accion === 'nuevo') {
                $conflicto['producto_id']       = null;
                $conflicto['accion']            = 'nuevo';
                $conflicto['nuevo_nombre']      = trim($res['nuevo_nombre'] ?? '');
                $conflicto['nuevo_descripcion'] = trim($res['nuevo_descripcion'] ?? $conflicto['descripcion']);
                $conflicto['nuevo_contenedor']  = (int) ($res['nuevo_contenedor'] ?? 1);
            } else {
                $conflicto['producto_id'] = null;
            }

            $items[] = $conflicto;
        }

        session()->forget('carga_masiva_pendiente');

        return $this->procesarCargaMasiva(
            $items,
            $pendiente['codigo_sicd'],
            $pendiente['descripcion'],
            $pendiente['motivo'],
            $pendiente['vincular_oc']
        );
    }

    private function procesarCargaMasiva(array $items, string $codigoSicd, ?string $descripcion, string $motivo, bool $vincularOc)
    {
        $sicd         = null;
        $actualizados = 0;

        if ($codigoSicd) {
            $sicd = Sicd::create([
                'codigo_sicd'    => $codigoSicd,
                'archivo_nombre' => '',
                'archivo_ruta'   => '',
                'descripcion'    => $descripcion,
                'estado'         => $vincularOc ? 'pendiente' : 'recibido',
                'usuario_id'     => Auth::id(),
            ]);
        }

        DB::transaction(function () use ($items, $motivo, $sicd, &$actualizados) {
            foreach ($items as $item) {
                $productoId = $item['producto_id'] ?? null;

                // Crear producto nuevo si el usuario eligió esa opción
                if (!$productoId && ($item['accion'] ?? '') === 'nuevo' && !empty($item['nuevo_nombre'])) {
                    $nuevo = Producto::create([
                        'nombre'       => $item['nuevo_nombre'],
                        'descripcion'  => $item['nuevo_descripcion'],
                        'stock_actual' => 0,
                        'stock_minimo' => 0,
                        'stock_critico'=> 0,
                        'contenedor'   => $item['nuevo_contenedor'],
                    ]);
                    $productoId = $nuevo->id;
                }

                if ($sicd) {
                    $sicd->detalles()->create([
                        'producto_id'           => $productoId,
                        'nombre_producto_excel' => $item['descripcion'],
                        'unidad'                => $item['unidad'] ?: null,
                        'cantidad_solicitada'   => $item['cantidad'],
                        'cantidad_recibida'     => $productoId ? $item['cantidad'] : 0,
                        'precio_neto'           => $item['precioNeto'] ?? null,
                        'total_neto'            => $item['totalNeto'] ?? null,
                    ]);
                }

                if (!$productoId) continue;

                $producto = Producto::find($productoId);
                if (!$producto) continue;

                $producto->stock_actual += $item['cantidad'];
                $producto->actualizarFechasStock();
                $producto->save();

                HistorialCambio::create([
                    'producto_id'  => $producto->id,
                    'cantidad'     => $item['cantidad'],
                    'tipo'         => 'entrada',
                    'motivo'       => $sicd ? "Carga masiva – SICD {$sicd->codigo_sicd}" : $motivo,
                    'aprobado_por' => Auth::user()->name,
                    'usuario_id'   => Auth::id(),
                ]);

                $actualizados++;
            }
        });

        $msg = "Carga masiva completada: {$actualizados} producto(s) actualizado(s).";

        if ($vincularOc && $sicd) {
            return redirect()->route('admin.ordenes.create')->with('success', $msg);
        }

        return redirect()->route('dashboard')->with('success', $msg);
    }

    public function cargaManual(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $request->validate([
            'items_manual'                => ['required', 'array', 'min:1'],
            'items_manual.*.producto_id'  => ['required', 'integer', 'exists:productos,id'],
            'items_manual.*.cantidad'     => ['required', 'integer', 'min:1'],
            'items_manual.*.motivo'       => ['nullable', 'string', 'max:500'],
        ], [
            'items_manual.required'              => 'Agrega al menos un producto.',
            'items_manual.*.producto_id.required' => 'Cada fila debe tener un producto.',
            'items_manual.*.cantidad.min'         => 'La cantidad debe ser al menos 1.',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items_manual as $item) {
                $producto = Producto::findOrFail($item['producto_id']);
                $cantidad = (int) $item['cantidad'];
                $motivo   = trim($item['motivo'] ?? '') ?: 'Carga manual de inventario';

                $producto->stock_actual += $cantidad;
                $producto->actualizarFechasStock();
                $producto->save();

                HistorialCambio::create([
                    'producto_id'  => $producto->id,
                    'cantidad'     => $cantidad,
                    'tipo'         => 'entrada',
                    'motivo'       => $motivo,
                    'aprobado_por' => Auth::user()->name,
                    'usuario_id'   => Auth::id(),
                ]);
            }
        });

        return redirect()->route('dashboard')
            ->with('success', 'Stock actualizado correctamente.');
    }
}
