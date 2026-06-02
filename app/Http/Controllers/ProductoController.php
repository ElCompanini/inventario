<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use App\Models\ArriendoMovimiento;
use App\Models\Container;
use App\Models\Familia;
use App\Models\GastoMenor;
use App\Models\HistorialCambio;
use App\Models\Marca;
use App\Models\OrdenCompra;
use App\Models\Precio;
use App\Models\Producto;
use App\Models\ServicioEstado;
use App\Models\Sicd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $isDev = $user->esDev();
        $ccId  = $user->ccFiltro();

        $productos = Producto::with([
            'container',
            'categoria.familia',
            'marca:id,nombre',
            'centroCosto:id,acronimo',
            'unidadMedida:id,nombre,abreviacion',
            'solicitudes' => fn($q) => $q->where('tipo', 'salida')->where('estado', 'pendiente')->with('usuario:id,name'),
        ])
        ->soloFisicos()
        ->whereDoesntHave('categoria.familia', fn($q) => $q->where('tipo', 'servicios'))
        ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
        ->orderBy('nombre')->get();

        $containers = Container::with('centroCosto:id,acronimo')
            ->orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get();

        // Incluye familias de CC nulo (SIN FAMILIA, PARTES Y PIEZAS) para todos los usuarios
        $familias = Familia::with([
            'categorias' => fn($q) => $q->with(['marcas' => fn($q2) => $q2->activas()]),
        ])->where('activo', true)
            ->when($ccId, fn($q) => $q->where(function ($inner) use ($ccId) {
                $inner->where('centro_costo_id', $ccId)->orWhereNull('centro_costo_id');
            }))
            ->orderBy('nombre')->get();

        $centrosCostoConProductos = $isDev
            ? CentroCosto::orderBy('nombre_completo')->get(['id', 'nombre_completo'])
            : collect();

        $servicios = Producto::with([
            'categoria.familia',
            'centroCosto:id,acronimo',
            'servicioEstados' => fn($q) => $q->orderBy('created_at')->with('usuario:id,name'),
        ])->soloServicios()
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->orderBy('nombre')->get();

        $mantenciones = Producto::with([
            'categoria.familia',
            'centroCosto:id,acronimo',
            'servicioEstados' => fn($q) => $q->orderBy('created_at')->with('usuario:id,name'),
        ])->soloMantenciones()
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->orderBy('nombre')->get();

        $arriendos = Producto::with([
            'categoria.familia',
            'centroCosto:id,acronimo,nombre_completo',
            'arriendoMovimientos' => fn($q) => $q->latest()->with(['responsable:id,name', 'ejecutor:id,name', 'ordenCompra:id,numero_oc', 'sicd:id,codigo_sicd']),
        ])->soloArriendos()
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->orderBy('nombre')
            ->get();

        // tipo column drives SIN FAMILIA / PYP detection in JS (no hardcoded IDs needed)
        $todosCentrosCosto = CentroCosto::orderBy('acronimo')->get(['id', 'acronimo']);

        return view('dashboard', compact('productos', 'containers', 'familias', 'centrosCostoConProductos', 'servicios', 'mantenciones', 'arriendos', 'todosCentrosCosto'));
    }

    public function gestionarArriendo(Request $request, int $id): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $producto = Producto::withoutGlobalScopes()
            ->where('tipo_item', 'arriendo')
            ->findOrFail($id);

        $data = $request->validate([
            'estado' => ['required', Rule::in(ArriendoMovimiento::ESTADOS)],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_termino' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'unidad_tiempo' => ['nullable', 'string', 'max:20'],
            'duracion' => ['nullable', 'integer', 'min:1'],
            'monto_periodo' => ['nullable', 'numeric', 'min:0'],
            'monto_total' => ['nullable', 'numeric', 'min:0'],
            'proveedor_nombre' => ['nullable', 'string', 'max:255'],
            'documento_referencia' => ['nullable', 'string', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        $movimiento = DB::transaction(function () use ($producto, $data) {
            $ultimo = ArriendoMovimiento::where('producto_id', $producto->id)->latest()->lockForUpdate()->first();
            $actual = $ultimo?->estado_nuevo ?? 'pendiente';
            $nuevo = $data['estado'];

            $permitidos = ArriendoMovimiento::flujoPermitido($actual);
            if ($nuevo !== $actual && !in_array($nuevo, $permitidos, true) && !auth()->user()->esSuperAdministrador()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'estado' => 'Transicion de arriendo no permitida desde ' . ArriendoMovimiento::label($actual) . ' hacia ' . ArriendoMovimiento::label($nuevo) . '.',
                ]);
            }

            $fechaInicio = $data['fecha_inicio'] ?? $ultimo?->fecha_inicio?->format('Y-m-d');
            $fechaTermino = $data['fecha_termino'] ?? $ultimo?->fecha_termino?->format('Y-m-d');
            $duracion = $data['duracion'] ?? null;

            if (!$duracion && $fechaInicio && $fechaTermino) {
                $duracion = \Carbon\Carbon::parse($fechaInicio)->diffInDays(\Carbon\Carbon::parse($fechaTermino)) + 1;
            }

            return ArriendoMovimiento::create([
                'producto_id' => $producto->id,
                'estado_anterior' => $actual,
                'estado_nuevo' => $nuevo,
                'fecha_inicio' => $fechaInicio,
                'fecha_termino' => $fechaTermino,
                'duracion' => $duracion,
                'unidad_tiempo' => $data['unidad_tiempo'] ?? $ultimo?->unidad_tiempo ?? 'dias',
                'monto_periodo' => $data['monto_periodo'] ?? $ultimo?->monto_periodo,
                'monto_total' => $data['monto_total'] ?? $ultimo?->monto_total,
                'proveedor_nombre' => $data['proveedor_nombre'] ?? $ultimo?->proveedor_nombre,
                'responsable_id' => auth()->id(),
                'ejecutado_por' => auth()->id(),
                'observacion' => $data['observacion'] ?? null,
                'documento_referencia' => $data['documento_referencia'] ?? $ultimo?->documento_referencia,
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'estado' => $movimiento->estado_nuevo,
                'label' => ArriendoMovimiento::label($movimiento->estado_nuevo),
                'fecha' => $movimiento->created_at->format('d/m/Y H:i'),
            ]);
        }

        return back()->with('success', 'Arriendo actualizado a "' . ArriendoMovimiento::label($movimiento->estado_nuevo) . '".');
    }

    public function gestionarEstado(Request $request, int $id): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $producto = Producto::withoutGlobalScopes()
            ->where(fn($q) => $q->where('es_servicio', true)->orWhereIn('tipo_item', ['servicio', 'mantencion']))
            ->findOrFail($id);

        $nuevoEstado = $request->input('estado');
        $observacion = trim($request->input('observacion', ''));
        $proveedor = trim($request->input('proveedor_nombre', ''));

        $estadosValidos = ['aprobado', 'en_proceso', 'ejecutado', 'validado', 'cerrado', 'cancelado'];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            if ($request->expectsJson()) return response()->json(['ok' => false, 'error' => 'Estado no válido.'], 422);
            return back()->with('error', 'Estado no válido.');
        }

        $ultimoEstado = ServicioEstado::where('producto_id', $id)
            ->latest()->value('estado') ?? 'pendiente';

        $flujoSiguiente = ServicioEstado::flujoSiguiente($ultimoEstado);
        if ($nuevoEstado !== 'cancelado' && $nuevoEstado !== $flujoSiguiente) {
            $msg = 'Transición no permitida. Estado actual en BD: ' . $ultimoEstado . '. Siguiente esperado: ' . ($flujoSiguiente ?? 'ninguno') . '.';
            if ($request->expectsJson()) return response()->json(['ok' => false, 'error' => $msg], 422);
            return back()->with('error', $msg);
        }

        // ── Observación automática por tipo de transición ────────────────────
        $autoObs = [
            'aprobado'   => 'Servicio aprobado por supervisor',
            'en_proceso' => 'Inicio ejecución operacional',
            'ejecutado'  => 'Servicio ejecutado correctamente',
            'validado'   => 'Validación técnica completada',
            'cerrado'    => 'Servicio cerrado operacionalmente',
            'cancelado'  => 'Servicio cancelado operacionalmente',
        ];
        $observacionFinal = $observacion !== '' ? $observacion : ($autoObs[$nuevoEstado] ?? null);

        // ── Documento de referencia automático por transición ────────────────
        $docRef  = null;
        $sicdId  = null;
        $ocId    = null;

        if ($nuevoEstado === 'aprobado') {
            // Buscar SICD más reciente asociado al producto via SicdDetalle
            $detalle = \App\Models\SicdDetalle::withTrashed()
                ->where('producto_id', $id)
                ->latest()
                ->first();
            if ($detalle?->sicd_id) {
                $sicd = Sicd::withTrashed()->find($detalle->sicd_id);
                if ($sicd) {
                    $docRef = 'SICD ' . $sicd->codigo_sicd;
                    $sicdId = $sicd->id;
                }
            }

        } elseif ($nuevoEstado === 'en_proceso') {
            // Heredar SICD del estado aprobado; usar la OC vinculada
            $seAprobado = ServicioEstado::where('producto_id', $id)
                ->where('estado', 'aprobado')
                ->latest()
                ->first();
            $sicd = $seAprobado?->sicd_id ? Sicd::withTrashed()->find($seAprobado->sicd_id) : null;
            if ($sicd) {
                $oc = $sicd->ordenesCompra()->latest()->first();
                if ($oc) {
                    $docRef = 'OC ' . $oc->numero_oc;
                    $ocId   = $oc->id;
                } else {
                    // Fallback: mantener referencia SICD si no hay OC aún
                    $docRef = 'SICD ' . $sicd->codigo_sicd;
                    $sicdId = $sicd->id;
                }
            }

        } elseif ($nuevoEstado === 'ejecutado') {
            $seq    = str_pad(ServicioEstado::where('estado', 'ejecutado')->count() + 1, 6, '0', STR_PAD_LEFT);
            $docRef = 'SER-' . $seq;

        } elseif ($nuevoEstado === 'validado') {
            $seq    = str_pad(ServicioEstado::where('estado', 'validado')->count() + 1, 6, '0', STR_PAD_LEFT);
            $docRef = 'VAL-' . $seq;

        } elseif ($nuevoEstado === 'cerrado') {
            $seq    = str_pad(ServicioEstado::where('estado', 'cerrado')->count() + 1, 6, '0', STR_PAD_LEFT);
            $docRef = 'CER-' . $seq;
        }

        ServicioEstado::create([
            'producto_id'          => $producto->id,
            'estado'               => $nuevoEstado,
            'estado_anterior'      => $ultimoEstado,
            'usuario_id'           => auth()->id(),
            'observacion'          => $observacionFinal,
            'sicd_id'              => $sicdId,
            'orden_compra_id'      => $ocId,
            'documento_referencia' => $docRef,
            'proveedor_nombre'     => $proveedor !== '' ? $proveedor : null,
        ]);

        $siguiente = ServicioEstado::flujoSiguiente($nuevoEstado);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => true,
                'estado'   => $nuevoEstado,
                'label'    => ServicioEstado::label($nuevoEstado),
                'progreso' => ServicioEstado::progreso($nuevoEstado),
                'colores'  => ServicioEstado::colores($nuevoEstado),
                'siguiente'=> $siguiente,
                'usuario'  => auth()->user()->name,
                'fecha'    => now()->format('d/m/Y H:i'),
            ]);
        }

        return back()->with('success', 'Estado actualizado a "' . ServicioEstado::label($nuevoEstado) . '".');
    }

    public function apiSeleccion(\Illuminate\Http\Request $request)
    {
        $ccId = auth()->user()->ccFiltro();
        return response()->json(
            Producto::query()
                ->when($request->categoria_id, fn($q) => $q->where('categoria_id', $request->categoria_id))
                ->when($request->marca_id, fn($q) => $q->where('marca_id', $request->marca_id))
                ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
        );
    }

    public function show(int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $producto = Producto::withoutGlobalScopes()
            ->with([
                'categoria.familia',
                'container.centroCosto:id,acronimo,nombre_completo',
                'centroCosto:id,acronimo,nombre_completo',
                'unidadMedida:id,nombre,abreviacion',
                'servicioEstados.usuario:id,name',
                'arriendoMovimientos.responsable:id,name',
                'arriendoMovimientos.ejecutor:id,name',
                'arriendoMovimientos.ordenCompra:id,numero_oc',
                'arriendoMovimientos.sicd:id,codigo_sicd',
            ])
            ->findOrFail($id);

        $user = auth()->user();

        // ── Servicio: solo carga el historial de estados ──────────────────────
        if ($producto->esServicio() || $producto->isMantencion()) {
            $servicioEstados = $producto->servicioEstados()
                ->with(['usuario:id,name', 'sicd:id,codigo_sicd', 'ordenCompra:id,numero_oc'])
                ->orderBy('created_at')
                ->get();
            $movimientos     = collect();
            $precios         = collect();
            $ultimoCosto     = null;
            $costoPromedio   = null;
            $sicds           = collect();
            $ordenes         = collect();
            $gastos          = collect();
            $totalEntradas   = 0;
            $totalSalidas    = 0;
            $containers      = collect();

            return view('admin.productos.show', compact(
                'producto', 'servicioEstados',
                'movimientos', 'precios',
                'ultimoCosto', 'costoPromedio',
                'sicds', 'ordenes', 'gastos',
                'totalEntradas', 'totalSalidas',
                'containers',
            ));
        }

        if ($producto->isArriendo()) {
            $arriendoMovimientos = $producto->arriendoMovimientos()
                ->with(['responsable:id,name', 'ejecutor:id,name', 'ordenCompra:id,numero_oc', 'sicd:id,codigo_sicd'])
                ->orderBy('created_at')
                ->get();

            $servicioEstados = collect();
            $movimientos     = collect();
            $precios         = collect();
            $ultimoCosto     = null;
            $costoPromedio   = null;
            $sicds           = collect();
            $ordenes         = collect();
            $gastos          = collect();
            $totalEntradas   = 0;
            $totalSalidas    = 0;
            $containers      = collect();

            return view('admin.productos.show', compact(
                'producto', 'servicioEstados', 'arriendoMovimientos',
                'movimientos', 'precios',
                'ultimoCosto', 'costoPromedio',
                'sicds', 'ordenes', 'gastos',
                'totalEntradas', 'totalSalidas',
                'containers',
            ));
        }

        $servicioEstados = collect();
        $arriendoMovimientos = collect();

        // ── Tab: Movimientos ─────────────────────────────────────────────────
        $movimientos = HistorialCambio::where('producto_id', $id)
            ->with('usuario:id,name')
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'mov_page');

        // ── Tab: Costos (solo admins) ─────────────────────────────────────────
        $precios       = collect();
        $ultimoCosto   = null;
        $costoPromedio = null;

        if ($user->esAdmin()) {
            $precios = Precio::where('producto_id', $id)
                ->with('usuario:id,name')
                ->orderByDesc('created_at')
                ->paginate(20, ['*'], 'cost_page');

            $ultimoCosto   = Precio::where('producto_id', $id)->latest()->value('precio_neto');
            $costoPromedio = Precio::where('producto_id', $id)->avg('precio_neto');
        }

        // ── Tab: Documentos ──────────────────────────────────────────────────
        $sicds = Sicd::whereHas('detalles', fn($q) => $q->where('producto_id', $id))
            ->with(['detalles' => fn($q) => $q->where('producto_id', $id)])
            ->orderByDesc('created_at')
            ->get();

        $sicdIds = $sicds->pluck('id');
        $ordenes = OrdenCompra::whereHas('sicds', fn($q) => $q->whereIn('sicds.id', $sicdIds))
            ->orderByDesc('created_at')
            ->get();

        $gastos = GastoMenor::where('producto_id', $id)
            ->orderByDesc('created_at')
            ->get();

        // ── Estadísticas de stock ─────────────────────────────────────────────
        $totalEntradas = HistorialCambio::where('producto_id', $id)->where('tipo', 'entrada')->sum('cantidad');
        $totalSalidas  = HistorialCambio::where('producto_id', $id)->where('tipo', 'salida')->sum('cantidad');

        // ── Containers disponibles para traslado ─────────────────────────────
        $containers = \App\Models\Container::withoutGlobalScope('con_cc')
            ->with('centroCosto:id,acronimo')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'centro_costo_id']);

        return view('admin.productos.show', compact(
            'producto', 'servicioEstados', 'movimientos', 'precios',
            'ultimoCosto', 'costoPromedio',
            'sicds', 'ordenes', 'gastos',
            'totalEntradas', 'totalSalidas',
            'containers',
        ));
    }
}
