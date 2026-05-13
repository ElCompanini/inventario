<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Familia;
use App\Models\Solicitud;
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
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SicdDetallesImport;

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

        $containers = Container::orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);

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
                'producto_id'     => $solicitud->producto_id,
                'nombre_producto' => $producto->nombre,
                'contenedor_id'   => $producto->contenedor,
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
            'sicd'       => fn($q) => $q->withTrashed(),
            'producto'   => fn($q) => $q->withoutGlobalScopes(),
            'gastoMenor' => fn($q) => $q->select('id', 'id_gm'),
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

        // Clave de agrupación: origen+id cuando existe, sino motivo+usuario+minuto exacto
        $lotes = $historial
            ->groupBy(function ($r) {
                if ($r->origen && $r->origen_id) {
                    return "origen:{$r->origen}-{$r->origen_id}";
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
                $key = "origen:{$r->origen}-{$r->origen_id}";
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
                'producto_id'     => $producto->id,
                'nombre_producto' => $producto->nombre,
                'contenedor_id'   => $producto->contenedor,
                'cantidad'        => $data['cantidad'],
                'tipo'            => $data['tipo'],
                'motivo'          => $data['motivo'],
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

        $containerOrigen  = $producto->contenedor
            ? Container::withoutGlobalScope('con_cc')->find($producto->contenedor)
            : null;
        $containerDestino = Container::withoutGlobalScope('con_cc')->findOrFail($data['contenedor_destino']);

        $origenNombre = $containerOrigen?->nombre ?? 'Sin container';

        DB::transaction(function () use ($producto, $data, $origenNombre, $containerDestino) {
            $producto->contenedor = $data['contenedor_destino'];
            $producto->save();

            HistorialCambio::create([
                'producto_id'     => $producto->id,
                'nombre_producto' => $producto->nombre,
                'contenedor_id'   => $containerDestino->id,
                'cantidad'        => $producto->stock_actual,
                'tipo'            => 'traslado',
                'motivo'          => "Traslado de {$origenNombre} a {$containerDestino->nombre}: {$data['motivo']}",
                'aprobado_por' => Auth::user()->name,
                'usuario_id'   => Auth::id(),
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
        ]);
        return preg_replace('/[^a-z0-9]/u', '', $s);
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
        $rows        = Excel::toCollection(new SicdDetallesImport, $request->file('excel_masivo'))->first();
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

        set_time_limit(300);
        $ccIdMasiva = $user->ccFiltro();

        // ── Precarga en memoria — elimina N+1 y timeouts ──────────────────
        $productosDB    = Producto::when($ccIdMasiva, fn($q) => $q->where('centro_costo_id', $ccIdMasiva))
                            ->get(['id', 'nombre', 'contenedor']);
        $unidadesMedida = UnidadMedida::activas()->get(['id', 'nombre', 'abreviacion']);

        // Índice normalizado → Producto (búsqueda O(1) sin query por fila)
        $prodIdx = [];
        foreach ($productosDB as $p) {
            $k = self::normalizarTexto($p->nombre);
            if ($k !== '') $prodIdx[$k] = $p;
        }

        // Índice normalizado → UnidadMedida (abreviacion + nombre)
        $unidIdx = [];
        foreach ($unidadesMedida as $u) {
            $kAbr = self::normalizarTexto($u->abreviacion);
            $kNom = self::normalizarTexto($u->nombre);
            if ($kAbr !== '') $unidIdx[$kAbr] = $u;
            if ($kNom !== '') $unidIdx[$kNom] = $u;
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

            if ($desc === '' || $cantidad <= 0) continue;

            $item = [
                'descripcion'      => $desc,
                'unidad'           => $unidadExcel,
                'cantidad'         => $cantidad,
                'precioNeto'       => $precioNeto,
                'totalNeto'        => $totalNeto,
                'unidad_medida_id' => null,
                'unidad_warning'   => null,
                'monto_warning'    => null,
            ];

            // ── Validación monetaria ───────────────────────────────────────
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
                $unidNorm = self::normalizarTexto($unidadExcel);
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
                            self::calcSimilitud($unidNorm, self::normalizarTexto($u->nombre))
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

            // ≥95% sin advertencias → auto-enlazar como exacto
            if ($mejorPct >= 95 && !$tieneWarnings && $mejorProd) {
                $item['producto_id']     = $mejorProd->id;
                $item['producto_nombre'] = $mejorProd->nombre;
                $item['contenedor_id']   = $mejorProd->contenedor;
                $exactos[] = $item;
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
        $familias   = Familia::with([
            'categorias' => fn($q) => $q->with(['marcas' => fn($q2) => $q2->activas()]),
        ])->where('activo', true)->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))->orderBy('nombre')->get();
        $containers = Container::orderBy('nombre')->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))->get(['id', 'nombre']);
        $unidades   = UnidadMedida::activas()->orderBy('abreviacion')->get(['id', 'abreviacion', 'nombre']);
        return view('admin.productos.resolver-carga-masiva', compact('pendiente', 'productos', 'familias', 'containers', 'unidades'));
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
                $conflicto['contenedor_id']      = null;
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
                    $nuevo = Producto::create([
                        'nombre'           => $item['nuevo_nombre'],
                        'categoria_id'     => $item['nuevo_categoria_id']  ?? null,
                        'marca_id'         => $item['nuevo_marca_id']      ?? null,
                        'unidad_medida_id' => $item['unidad_medida_id']    ?? null,
                        'stock_actual'     => 0,
                        'stock_minimo'     => (int) ($item['nuevo_stock_minimo']  ?? 0),
                        'stock_critico'    => (int) ($item['nuevo_stock_critico'] ?? 0),
                        'contenedor'       => $item['contenedor_id'] ?? 1,
                        'centro_costo_id'  => $ccId,
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

                $producto->stock_actual += $item['cantidad'];
                $producto->actualizarFechasStock();
                $producto->save();

                HistorialCambio::create([
                    'producto_id'     => $producto->id,
                    'nombre_producto' => $producto->nombre,
                    'contenedor_id'   => $producto->contenedor,
                    'cantidad'        => $item['cantidad'],
                    'tipo'            => 'entrada',
                    'motivo'          => $sicd ? "Carga masiva – SICD {$sicd->codigo_sicd}" : $motivo,
                    'aprobado_por' => Auth::user()->name,
                    'usuario_id'   => Auth::id(),
                    'origen'       => $sicd ? 'sicd' : null,
                    'origen_id'    => $sicd ? $sicd->id : null,
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
                    $producto->stock_actual += $cantidad;
                    $producto->actualizarFechasStock();
                    $producto->save();

                    HistorialCambio::create([
                        'producto_id'     => $producto->id,
                        'nombre_producto' => $producto->nombre,
                        'contenedor_id'   => $producto->contenedor,
                        'cantidad'        => $cantidad,
                        'tipo'            => 'entrada',
                        'motivo'          => $motivo,
                        'aprobado_por' => Auth::user()->name,
                        'usuario_id'   => Auth::id(),
                        'origen'       => $sicd ? 'sicd' : null,
                        'origen_id'    => $sicd ? $sicd->id : null,
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
        ]);

        $categoria = Categoria::findOrFail($request->categoria_id);

        $producto = \App\Models\Producto::create([
            'nombre'           => trim($request->nombre),
            'stock_actual'     => 0,
            'stock_minimo'     => (int) ($request->stock_minimo ?? 0),
            'stock_critico'    => (int) ($request->stock_critico ?? 0),
            'contenedor'       => null,
            'categoria_id'     => $categoria->id,
            'marca_id'         => $request->marca_id ?: null,
            'centro_costo_id'  => auth()->user()->centro_costo_id,
            'unidad_medida_id' => $request->unidad_medida_id ?: null,
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
}
