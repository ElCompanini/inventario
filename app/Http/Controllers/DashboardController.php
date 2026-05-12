<?php

namespace App\Http\Controllers;

use App\Exports\ActividadExport;
use App\Models\Categoria;
use App\Models\ComputadorArmado;
use App\Models\Familia;
use App\Models\GastoMenor;
use App\Models\HistorialCambio;
use App\Models\Marca;
use App\Models\OrdenCompra;
use App\Models\Precio;
use App\Models\Producto;
use App\Models\ReporteriaIndexada;
use App\Models\Sicd;
use App\Services\ReporteriaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $user = auth()->user();
        $ccId = $user->ccFiltro();

        // ── KPI 1: Stock (una sola query agregada) ─────────────────────────────
        $stockStats = DB::table('productos')
            ->where('activo', true)
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->selectRaw('
                COUNT(*) as total_productos,
                COALESCE(SUM(stock_actual), 0) as total_unidades,
                SUM(CASE WHEN stock_actual <= 0 THEN 1 ELSE 0 END) as agotados,
                SUM(CASE WHEN stock_actual > 0 AND stock_critico > 0 AND stock_actual <= stock_critico THEN 1 ELSE 0 END) as criticos,
                SUM(CASE WHEN stock_actual > stock_critico AND stock_actual <= stock_minimo THEN 1 ELSE 0 END) as en_minimo
            ')->first();

        // ── KPI 2: Solicitudes ─────────────────────────────────────────────────
        $solicitudesStats = DB::table('solicitudes')
            ->selectRaw("
                SUM(CASE WHEN estado = 'pendiente'  THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'aprobado'   THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estado = 'rechazado'  THEN 1 ELSE 0 END) as rechazadas
            ")->first();

        // ── KPI 3: Órdenes de Compra ──────────────────────────────────────────
        $ocStats = OrdenCompra::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado NOT IN ('recibido','validado') THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'recibido' THEN 1 ELSE 0 END) as recibidas,
                SUM(CASE WHEN estado = 'validado' THEN 1 ELSE 0 END) as validadas
            ")->first();

        // ── KPI 4: SICD ───────────────────────────────────────────────────────
        $sicdStats = Sicd::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente'  THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'agrupado'   THEN 1 ELSE 0 END) as agrupadas,
                SUM(CASE WHEN estado = 'recibido'   THEN 1 ELSE 0 END) as recibidas
            ")->first();

        // ── KPI 5: Comparativa financiera SICD vs OC del mes ─────────────────
        $inicioMes = now()->startOfMonth();

        // Valor referencial SICD: suma de total_neto_original (o total_neto como fallback) de
        // los detalles de SICDs creados este mes.
        $sicdRefMes = (float) DB::table('sicd_detalles')
            ->join('sicds', 'sicd_detalles.sicd_id', '=', 'sicds.id')
            ->whereNull('sicd_detalles.deleted_at')
            ->whereNull('sicds.deleted_at')
            ->where('sicds.created_at', '>=', $inicioMes)
            ->sum(DB::raw('COALESCE(sicd_detalles.total_neto_original, sicd_detalles.total_neto, 0)'));

        // Valor final adjudicado OC: suma de api_total de OCs validadas/recibidas este mes.
        $ocFinalMes = (float) DB::table('ordenes_compra')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $inicioMes)
            ->whereIn('estado', ['validado', 'recibido'])
            ->whereNotNull('api_total')
            ->sum('api_total');

        // Diferencia: positivo = sobrecosto, negativo = ahorro
        $difFinanciera = $ocFinalMes - $sicdRefMes;

        // Mantener gastos menores para el resto del sistema (no se muestra en el card ya)
        $gastosBase  = fn() => GastoMenor::where('created_at', '>=', $inicioMes)
            ->when($ccId, fn($q) => $q->whereHas('user', fn($u) => $u->where('centro_costo_id', $ccId)));
        $gastosStats   = $gastosBase()->selectRaw('COUNT(*) as total_registros, COALESCE(SUM(monto), 0) as total_monto')->first();
        $gastosUltimos = $gastosBase()->with('producto:id,nombre', 'user:id,name')
            ->orderByDesc('created_at')->take(5)->get();

        // ── KPI 6: Equipos armados ────────────────────────────────────────────
        $equiposStats = DB::table('computadores_armados')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado IN ('listo','en_uso') THEN 1 ELSE 0 END) as completos,
                SUM(CASE WHEN estado = 'en_armado'         THEN 1 ELSE 0 END) as en_armado,
                SUM(CASE WHEN estado = 'desarmado'         THEN 1 ELSE 0 END) as desarmados
            ")->first();

        $piezasTotal = (int) DB::table('computador_componentes')
            ->join('computadores_armados', 'computador_componentes.computador_id', '=', 'computadores_armados.id')
            ->whereNull('computador_componentes.deleted_at')
            ->whereNull('computadores_armados.deleted_at')
            ->sum('computador_componentes.cantidad');

        // ── BINCARD / Valorización ─────────────────────────────────────────────
        // Valor total inventario (costo promedio × stock actual, por producto)
        $valorInventario = DB::table('productos')
            ->join(
                DB::raw('(SELECT producto_id, AVG(precio_neto) as avg_neto FROM precios GROUP BY producto_id) as cp'),
                'productos.id', '=', 'cp.producto_id'
            )
            ->where('productos.activo', true)
            ->when($ccId, fn($q) => $q->where('productos.centro_costo_id', $ccId))
            ->sum(DB::raw('FLOOR(productos.stock_actual * cp.avg_neto)'));

        // Entradas valorizadas este mes
        $entradasMesValor = Precio::where('created_at', '>=', $inicioMes)->sum('precio_total');

        // Entradas/salidas este mes (unidades)
        $movMes = HistorialCambio::where('created_at', '>=', $inicioMes)
            ->when($ccId, fn($q) => $q->whereHas('producto', fn($p) => $p->withoutGlobalScopes()->where('centro_costo_id', $ccId)))
            ->selectRaw("
                SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo = 'salida'  THEN cantidad ELSE 0 END) as salidas
            ")->first();

        // Costo promedio global
        $costoPromedioGlobal = (float) (Precio::avg('precio_neto') ?? 0);

        // ── Gráfico: Movimiento inventario últimos 30 días ────────────────────
        $inicio30d = now()->subDays(29)->startOfDay();
        $movRaw = HistorialCambio::where('created_at', '>=', $inicio30d)
            ->when($ccId, fn($q) => $q->whereHas('producto', fn($p) => $p->withoutGlobalScopes()->where('centro_costo_id', $ccId)))
            ->selectRaw("DATE(created_at) as fecha,
                SUM(CASE WHEN tipo='entrada' THEN cantidad ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo='salida'  THEN cantidad ELSE 0 END) as salidas")
            ->groupBy('fecha')->orderBy('fecha')->get()->keyBy('fecha');

        $labels30d = $entradas30d = $salidas30d = [];
        for ($i = 29; $i >= 0; $i--) {
            $f = now()->subDays($i)->format('Y-m-d');
            $labels30d[]   = now()->subDays($i)->format('d/m');
            $entradas30d[] = (int) ($movRaw[$f]->entradas ?? 0);
            $salidas30d[]  = (int) ($movRaw[$f]->salidas  ?? 0);
        }

        $graficoMovimiento = compact('labels30d', 'entradas30d', 'salidas30d');

        // ── Gráfico: Compras por fuente ───────────────────────────────────────
        $fuenteLabels = [
            'orden_compra'  => 'OC Licitación',
            'gasto_menor'   => 'Gasto Menor',
            'manual'        => 'Manual',
        ];
        $comprasFuente = Precio::select('fuente', DB::raw('COUNT(*) as registros'), DB::raw('COALESCE(SUM(precio_total), 0) as total'))
            ->groupBy('fuente')->get()->keyBy('fuente');

        $graficoCom = [
            'labels' => array_values(array_map(fn($k) => $fuenteLabels[$k] ?? Str::title($k), $comprasFuente->keys()->toArray())),
            'data'   => $comprasFuente->pluck('total')->map(fn($v) => round($v))->values()->toArray(),
            'counts' => $comprasFuente->pluck('registros')->values()->toArray(),
        ];

        // ── Gráfico: Productos más utilizados (salidas 90 días) ───────────────
        $prodMovidos = HistorialCambio::where('tipo', 'salida')
            ->where('created_at', '>=', now()->subDays(30))
            ->when($ccId, fn($q) => $q->whereHas('producto', fn($p) => $p->withoutGlobalScopes()->where('centro_costo_id', $ccId)))
            ->select('nombre_producto', DB::raw('SUM(cantidad) as total'))
            ->groupBy('nombre_producto')
            ->orderByDesc('total')
            ->take(8)->get();

        $graficoProductos = [
            'labels' => $prodMovidos->pluck('nombre_producto')->map(fn($n) => Str::limit($n, 30))->toArray(),
            'data'   => $prodMovidos->pluck('total')->map(fn($v) => (int) $v)->toArray(),
        ];

        // ── Actividad reciente ─────────────────────────────────────────────────
        $actividadReciente = HistorialCambio::with('usuario:id,name')
            ->when($ccId, fn($q) => $q->whereHas('producto', fn($p) => $p->withoutGlobalScopes()->where('centro_costo_id', $ccId)))
            ->latest()->take(12)->get();

        // ── Alertas ────────────────────────────────────────────────────────────
        $alertasStockCritico = Producto::where('stock_actual', '<=', DB::raw('stock_critico'))
            ->where('stock_critico', '>', 0)
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->with('centroCosto:id,acronimo')
            ->orderBy('stock_actual')
            ->take(6)
            ->get(['id', 'nombre', 'stock_actual', 'stock_critico', 'centro_costo_id']);

        $alertasSicd = Sicd::where('estado', 'pendiente')
            ->with('usuario:id,name')
            ->latest()->take(5)->get(['id', 'codigo_sicd', 'usuario_id', 'created_at']);

        $alertasOC = OrdenCompra::whereNotIn('estado', ['recibido', 'validado'])
            ->latest()->take(5)->get(['id', 'numero_oc', 'estado', 'created_at']);

        $sinCategoria = Producto::whereNull('categoria_id')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->count();

        // ── Panel logístico ───────────────────────────────────────────────────
        $sicdRecientes = Sicd::with(['usuario:id,name', 'ordenesCompra:id,numero_oc'])
            ->latest()->take(8)->get(['id', 'codigo_sicd', 'estado', 'usuario_id', 'created_at']);

        $ocRecientes = OrdenCompra::latest()->take(6)
            ->get(['id', 'numero_oc', 'estado', 'api_proveedor_nombre', 'created_at']);

        // ── Total utilizado — filter data ─────────────────────────────────────
        $tuFamilias   = Familia::orderBy('nombre')->get(['id', 'nombre']);
        $tuCategorias = Categoria::orderBy('nombre')->get(['id', 'nombre']);
        $tuMarcas     = Marca::orderBy('nombre')->get(['id', 'nombre']);

        // ── Total utilizado — métricas de stock ───────────────────────────────
        $tuMasStock    = Producto::withoutGlobalScope('activo')
            ->where('activo', true)->where('stock_actual', '>', 0)
            ->orderByDesc('stock_actual')->first(['nombre', 'stock_actual']);

        $tuMenosStock  = Producto::withoutGlobalScope('activo')
            ->where('activo', true)->where('stock_actual', '>', 0)
            ->orderBy('stock_actual')->take(4)
            ->get(['nombre', 'stock_actual']);

        // ── Equipos armados recientes ─────────────────────────────────────────
        $ultimosEquipos = ComputadorArmado::with('componentesActivos')
            ->latest()->take(5)->get(['id', 'codigo', 'nombre', 'estado', 'usuario_asignado', 'created_at']);

        // ── Últimas reporterías ───────────────────────────────────────────────
        $ultimasReporterias = ReporteriaIndexada::with('usuario:id,name')
            ->latest()->take(8)->get(['id', 'nombre', 'formato', 'modulo', 'usuario_id', 'usuario_nombre', 'tamaño_bytes', 'created_at']);

        return view('admin.dashboard.index', compact(
            'user',
            'stockStats',
            'solicitudesStats',
            'ocStats',
            'sicdStats',
            'gastosStats',
            'gastosUltimos',
            'sicdRefMes',
            'ocFinalMes',
            'difFinanciera',
            'equiposStats',
            'valorInventario',
            'entradasMesValor',
            'movMes',
            'costoPromedioGlobal',
            'graficoMovimiento',
            'graficoCom',
            'graficoProductos',
            'actividadReciente',
            'alertasStockCritico',
            'alertasSicd',
            'alertasOC',
            'sinCategoria',
            'sicdRecientes',
            'ocRecientes',
            'ultimosEquipos',
            'piezasTotal',
            'ultimasReporterias',
            'tuFamilias',
            'tuCategorias',
            'tuMarcas',
            'tuMasStock',
            'tuMenosStock',
        ));
    }

    /** Opciones en cascada para los filtros de Total Utilizado */
    public function tuOpciones(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $familiaIds  = array_filter((array) $request->input('familia_ids', []), 'is_numeric');
        $categoriaIds = array_filter((array) $request->input('categoria_ids', []), 'is_numeric');

        $categorias = !empty($familiaIds)
            ? Categoria::orderBy('nombre')->whereIn('familia_id', $familiaIds)->get(['id', 'nombre'])
            : collect();

        $marcas = !empty($categoriaIds)
            ? Marca::orderBy('nombre')
                ->whereHas('productos', fn($q) => $q->withoutGlobalScope('activo')->whereIn('categoria_id', $categoriaIds))
                ->get(['id', 'nombre'])
            : collect();

        return response()->json(compact('categorias', 'marcas'));
    }

    /** Construye la query y el array de filas para los exports de Actividad */
    private function buildActividadData(Request $request): array
    {
        $desde = $request->filled('desde')
            ? \Carbon\Carbon::parse($request->desde)->startOfDay()
            : now()->startOfMonth();
        $hasta = $request->filled('hasta')
            ? \Carbon\Carbon::parse($request->hasta)->endOfDay()
            : now()->endOfDay();

        $ccId = auth()->user()->ccFiltro();

        $rows = DB::table('historial_cambios as hc')
            ->leftJoin('users as u',       'hc.usuario_id', '=', 'u.id')
            ->leftJoin('productos as p',   'hc.producto_id', '=', 'p.id')
            ->leftJoin('categorias as cat', 'p.categoria_id', '=', 'cat.id')
            ->leftJoin('marcas as m',       'p.marca_id',     '=', 'm.id')
            ->whereNull('hc.deleted_at')
            ->whereBetween('hc.created_at', [$desde, $hasta])
            ->when($ccId, fn($q) => $q->where('p.centro_costo_id', $ccId))
            ->orderByDesc('hc.created_at')
            ->select(
                'hc.created_at', 'hc.tipo', 'hc.nombre_producto',
                'hc.cantidad', 'hc.origen', 'hc.origen_id',
                'hc.orden_compra_id', 'hc.motivo', 'hc.aprobado_por',
                'cat.nombre as categoria_nombre',
                'm.nombre as marca_nombre',
                'u.name as usuario_nombre'
            )
            ->get();

        $origenLabel = [
            'gasto_menor'       => 'Gasto Menor',
            'orden_compra'      => 'OC',
            'sicd'              => 'SICD',
            'solicitud'         => 'Solicitud',
            'retiro'            => 'Retiro',
            'computador_armado' => 'Armado Equipo',
        ];

        $filas = $rows->map(function ($r) use ($origenLabel) {
            $dt      = \Carbon\Carbon::parse($r->created_at);
            $modulo  = $origenLabel[$r->origen] ?? 'Manual';
            $doc     = $r->orden_compra_id ? 'OC#' . $r->orden_compra_id
                     : ($r->origen_id       ? '#' . $r->origen_id : '—');
            return [
                'fecha'         => $dt->format('d/m/Y'),
                'hora'          => $dt->format('H:i'),
                'tipo'          => $r->tipo,
                'tipo_label'    => $r->tipo === 'entrada' ? 'Entrada' : 'Salida',
                'producto'      => $r->nombre_producto,
                'categoria'     => $r->categoria_nombre ?? '—',
                'marca'         => $r->marca_nombre      ?? '—',
                'cantidad'      => (int) $r->cantidad,
                'modulo'        => $modulo,
                'documento'     => $doc,
                'usuario'       => $r->aprobado_por ?? $r->usuario_nombre ?? '—',
                'observaciones' => $r->motivo ?? '',
            ];
        })->toArray();

        return [
            'filas'        => $filas,
            'total'        => count($filas),
            'desde'        => $desde->format('d/m/Y'),
            'hasta'        => $hasta->format('d/m/Y'),
            'generado_por' => auth()->user()->name,
            'generado_at'  => now()->format('d/m/Y H:i'),
            'filtros'      => [
                'fecha_desde' => $desde->toDateString(),
                'fecha_hasta' => $hasta->toDateString(),
            ],
        ];
    }

    public function exportarActividadExcel(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $data     = $this->buildActividadData($request);
        $filename = 'actividad_' . now()->format('Ymd_His') . '.xlsx';
        $ruta     = 'reportes/' . $filename;

        Excel::store(new ActividadExport($data), $ruta, 'local');

        (new ReporteriaService())->registrar(
            tipo:          'ACTIVIDAD_EXCEL',
            nombre:        'Actividad Reciente ' . $data['desde'] . ' → ' . $data['hasta'],
            modulo:        'dashboard',
            formato:       'EXCEL',
            filtros:       $data['filtros'],
            rutaArchivo:   $ruta,
            nombreArchivo: $filename,
        );

        return Excel::download(new ActividadExport($data), $filename);
    }

    public function exportarActividadPdf(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $data     = $this->buildActividadData($request);
        $filename = 'actividad_' . now()->format('Ymd_His') . '.pdf';
        $ruta     = 'reportes/' . $filename;

        $pdf = Pdf::loadView('admin.reportes.actividad_pdf', compact('data'))
            ->setPaper('a4', 'landscape');

        $content = $pdf->output();
        Storage::disk('local')->put($ruta, $content);

        (new ReporteriaService())->registrar(
            tipo:          'ACTIVIDAD_PDF',
            nombre:        'Actividad Reciente ' . $data['desde'] . ' → ' . $data['hasta'],
            modulo:        'dashboard',
            formato:       'PDF',
            filtros:       $data['filtros'],
            rutaArchivo:   $ruta,
            nombreArchivo: $filename,
        );

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function actividadFiltro(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $desde = $request->filled('desde')
            ? \Carbon\Carbon::parse($request->desde)->startOfDay()
            : now()->startOfMonth();

        $hasta = $request->filled('hasta')
            ? \Carbon\Carbon::parse($request->hasta)->endOfDay()
            : now()->endOfDay();

        $ccId = auth()->user()->ccFiltro();

        $actividad = HistorialCambio::with('usuario:id,name')
            ->whereBetween('created_at', [$desde, $hasta])
            ->when($ccId, fn($q) => $q->whereHas('producto', fn($p) => $p->withoutGlobalScopes()->where('centro_costo_id', $ccId)))
            ->latest()
            ->take(12)
            ->get();

        $origenLabel = [
            'gasto_menor'       => 'Gasto Menor',
            'orden_compra'      => 'OC',
            'sicd'              => 'SICD',
            'solicitud'         => 'Solicitud',
            'retiro'            => 'Retiro',
            'computador_armado' => 'Armado Equipo',
        ];

        return response()->json([
            'actividad' => $actividad->map(fn($mov) => [
                'tipo'    => $mov->tipo,
                'nombre'  => $mov->nombre_producto,
                'cantidad'=> (int) abs($mov->cantidad),
                'origen'  => $origenLabel[$mov->origen] ?? 'Manual',
                'usuario' => $mov->usuario?->name ?? '—',
                'fecha'   => $mov->created_at->format('d/m H:i'),
            ])->values(),
        ]);
    }

    public function equiposFiltro(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $desde = $request->filled('desde')
            ? \Carbon\Carbon::parse($request->desde)->startOfDay()
            : now()->startOfMonth();

        $hasta = $request->filled('hasta')
            ? \Carbon\Carbon::parse($request->hasta)->endOfDay()
            : now()->endOfDay();

        $stats = DB::table('computadores_armados')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado IN ('listo','en_uso') THEN 1 ELSE 0 END) as completos,
                SUM(CASE WHEN estado = 'en_armado'         THEN 1 ELSE 0 END) as en_armado,
                SUM(CASE WHEN estado = 'desarmado'         THEN 1 ELSE 0 END) as desarmados
            ")->first();

        // Piezas utilizadas en el período (suma cantidad de componentes de armados creados en rango)
        $piezasPeriodo = (int) DB::table('computador_componentes')
            ->join('computadores_armados', 'computador_componentes.computador_id', '=', 'computadores_armados.id')
            ->whereNull('computador_componentes.deleted_at')
            ->whereNull('computadores_armados.deleted_at')
            ->whereBetween('computadores_armados.created_at', [$desde, $hasta])
            ->sum('computador_componentes.cantidad');

        // Total histórico acumulado (independiente del filtro)
        $piezasTotal = (int) DB::table('computador_componentes')
            ->join('computadores_armados', 'computador_componentes.computador_id', '=', 'computadores_armados.id')
            ->whereNull('computador_componentes.deleted_at')
            ->whereNull('computadores_armados.deleted_at')
            ->sum('computador_componentes.cantidad');

        $equipos = ComputadorArmado::with('componentesActivos')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$desde, $hasta])
            ->latest()
            ->take(5)
            ->get(['id', 'codigo', 'nombre', 'estado', 'created_at']);

        return response()->json([
            'stats'         => $stats,
            'piezas_periodo' => $piezasPeriodo,
            'piezas_total'  => $piezasTotal,
            'equipos'       => $equipos->map(fn($eq) => [
                'id'          => $eq->id,
                'codigo'      => $eq->codigo,
                'nombre'      => $eq->nombre,
                'estado'      => $eq->estado,
                'componentes' => $eq->componentesActivos->count(),
                'fecha'       => $eq->created_at->format('d/m/Y'),
                'url'         => route('admin.computadores.show', $eq->id),
            ]),
        ]);
    }

    public function totalUtilizado(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $desde = $request->filled('desde')
            ? \Carbon\Carbon::parse($request->desde)->startOfDay()
            : now()->startOfMonth();

        $hasta = $request->filled('hasta')
            ? \Carbon\Carbon::parse($request->hasta)->endOfDay()
            : now()->endOfDay();

        $query = DB::table('historial_cambios as hc')
            ->leftJoin('productos as p', 'hc.producto_id', '=', 'p.id')
            ->leftJoin('categorias as cat', 'p.categoria_id', '=', 'cat.id')
            ->leftJoin('familias as fam', 'cat.familia_id', '=', 'fam.id')
            ->leftJoin('marcas as m', 'p.marca_id', '=', 'm.id')
            ->leftJoin(
                DB::raw('(SELECT producto_id, AVG(precio_neto) as avg_precio FROM precios GROUP BY producto_id) as pr'),
                'p.id', '=', 'pr.producto_id'
            )
            ->where('hc.tipo', 'salida')
            ->whereBetween('hc.created_at', [$desde, $hasta]);

        $familiaIds  = array_filter((array) $request->input('familia_id', []), 'is_numeric');
        $catIds      = array_filter((array) $request->input('categoria_id', []), 'is_numeric');
        $marcaIds    = array_filter((array) $request->input('marca_id', []), 'is_numeric');
        $origenes    = (array) $request->input('origen', []);

        if (!empty($familiaIds))  { $query->whereIn('fam.id', $familiaIds); }
        if (!empty($catIds))      { $query->whereIn('cat.id', $catIds); }
        if (!empty($marcaIds))    { $query->whereIn('m.id', $marcaIds); }
        if (!empty($origenes))    { $query->whereIn('hc.origen', $origenes); }

        $totals = (clone $query)->selectRaw('
            COALESCE(SUM(hc.cantidad), 0) as total_cantidad
        ')->first();

        $ultimoProducto = (clone $query)
            ->select('hc.nombre_producto', 'hc.cantidad', 'hc.created_at')
            ->orderByDesc('hc.created_at')
            ->first();

        return response()->json([
            'total_cantidad'  => (int) ($totals->total_cantidad ?? 0),
            'ultimo_producto' => $ultimoProducto ? [
                'nombre'   => $ultimoProducto->nombre_producto,
                'cantidad' => (int) $ultimoProducto->cantidad,
                'fecha'    => \Carbon\Carbon::parse($ultimoProducto->created_at)->format('d/m/Y H:i'),
            ] : null,
        ]);
    }
}
