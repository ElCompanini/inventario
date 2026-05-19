<?php

namespace App\Http\Controllers;

use App\Exports\BincardExport;
use App\Models\Familia;
use App\Models\Producto;
use App\Models\ReporteriaIndexada;
use App\Models\ServicioEstado;
use App\Services\BincardService;
use App\Services\ReporteriaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function __construct(
        protected BincardService    $bincard,
        protected ReporteriaService $reporteria,
    ) {}

    public function index()
    {
        abort_unless(auth()->user()->tienePermiso('reportes'), 403);
        $user = auth()->user();
        $ccId = $user->ccFiltro();

        $productos = Producto::where('es_servicio', false)
            ->orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);

        $serviciosF = Producto::where('es_servicio', true)
            ->orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);

        $familias = Familia::orderBy('nombre')->get(['id', 'nombre']);

        // Mapa producto_id → filtros del BINCARD más reciente (para advertencia)
        $bincardsPorProducto = ReporteriaIndexada::whereNotNull('filtros')
            ->orderByDesc('created_at')
            ->get(['filtros'])
            ->filter(fn($r) => isset($r->filtros['producto_id']))
            ->groupBy(fn($r) => $r->filtros['producto_id'])
            ->map(fn($g) => $g->first()->filtros)
            ->toArray();

        return view('admin.reportes.bincard', compact('productos', 'familias', 'serviciosF', 'bincardsPorProducto'));
    }

    public function bincard(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('reportes'), 403);

        $request->validate([
            'producto_id'  => ['required', 'integer', 'exists:productos,id'],
            'fecha_desde'  => ['nullable', 'date'],
            'fecha_hasta'  => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'tipo'         => ['nullable', 'string'],
        ]);

        $producto = Producto::withoutGlobalScopes()->with([
            'categoria.familia',
            'marca:id,nombre',
            'container',
            'unidadMedida:id,nombre,abreviacion',
            'centroCosto:id,acronimo,nombre_completo',
        ])->findOrFail($request->producto_id);

        $filtros = array_filter([
            'fecha_desde'        => $request->fecha_desde,
            'fecha_hasta'        => $request->fecha_hasta,
            'tipo'               => $request->tipo,
            'origen'             => $request->origen,
            'registrado_por'     => $request->registrado_por,
            'usuario'            => $request->usuario_filtro,
            'proveedor_filtro'   => $request->proveedor_filtro,
            'n_documento_filtro' => $request->n_documento_filtro,
        ]);

        $data = $this->bincard->generarBincard($producto, $filtros);
        $data['mostrar_costos'] = auth()->user()->esAdmin();

        // Registrar solo cuando es una generación nueva, no al "ver" desde historial
        if (!$request->boolean('solo_ver')) {
            $this->reporteria->registrar(
                tipo:    'BINCARD_VISTA',
                nombre:  'BINCARD – ' . $producto->nombre,
                modulo:  'reportes',
                formato: 'HTML',
                filtros: array_merge(
                    ['producto_id' => $producto->id, 'producto_nombre' => $producto->nombre],
                    $filtros
                ),
                notas: 'Vista en pantalla · ' . count($data['movimientos'] ?? []) . ' movimiento(s)',
            );
        }

        $user = auth()->user();
        $ccId = $user->ccFiltro();
        $productos = Producto::where('es_servicio', false)
            ->orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);
        $serviciosF = Producto::where('es_servicio', true)
            ->orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);
        $familias = Familia::orderBy('nombre')->get(['id', 'nombre']);
        $bincardsPorProducto = [];

        return view('admin.reportes.bincard', compact('data', 'productos', 'familias', 'serviciosF', 'bincardsPorProducto'));
    }

    public function exportExcel(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('reportes'), 403);

        $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'tipo'        => ['nullable', 'string'],
        ]);

        $producto = Producto::withoutGlobalScopes()->with([
            'categoria.familia', 'container',
            'unidadMedida:id,nombre,abreviacion', 'centroCosto:id,acronimo',
        ])->findOrFail($request->producto_id);

        $filtros = array_filter([
            'fecha_desde'        => $request->fecha_desde,
            'fecha_hasta'        => $request->fecha_hasta,
            'tipo'               => $request->tipo,
            'origen'             => $request->origen,
            'registrado_por'     => $request->registrado_por,
            'usuario'            => $request->usuario_filtro,
            'proveedor_filtro'   => $request->proveedor_filtro,
            'n_documento_filtro' => $request->n_documento_filtro,
        ]);

        $data = $this->bincard->generarBincard($producto, $filtros);
        $data['mostrar_costos'] = true;

        $nombre   = 'BINCARD_' . str_replace([' ', '/', '\\'], '_', $producto->nombre) . '_' . now()->format('Ymd_His') . '.xlsx';
        $rutaRel  = 'reporterias/' . $nombre;

        // Almacenar en disco y registrar índice
        Excel::store(new BincardExport($data), $rutaRel, 'local');
        $this->reporteria->registrar(
            tipo:          'BINCARD_EXCEL',
            nombre:        'BINCARD – ' . $producto->nombre,
            modulo:        'reportes',
            formato:       'EXCEL',
            filtros:       array_merge(['producto_id' => $producto->id, 'producto_nombre' => $producto->nombre], $filtros),
            rutaArchivo:   $rutaRel,
            nombreArchivo: $nombre,
        );

        return Storage::disk('local')->download($rutaRel, $nombre);
    }

    public function exportPdf(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('reportes'), 403);

        $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'tipo'        => ['nullable', 'string'],
        ]);

        $producto = Producto::withoutGlobalScopes()->with([
            'categoria.familia', 'container',
            'unidadMedida:id,nombre,abreviacion', 'centroCosto:id,acronimo,nombre_completo',
        ])->findOrFail($request->producto_id);

        $filtros = array_filter([
            'fecha_desde'        => $request->fecha_desde,
            'fecha_hasta'        => $request->fecha_hasta,
            'tipo'               => $request->tipo,
            'origen'             => $request->origen,
            'registrado_por'     => $request->registrado_por,
            'usuario'            => $request->usuario_filtro,
            'proveedor_filtro'   => $request->proveedor_filtro,
            'n_documento_filtro' => $request->n_documento_filtro,
        ]);

        $data = $this->bincard->generarBincard($producto, $filtros);
        $data['mostrar_costos'] = true;

        $pdf = Pdf::loadView('admin.reportes.bincard_pdf', compact('data'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'  => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        $nombre  = 'BINCARD_' . str_replace([' ', '/', '\\'], '_', $producto->nombre) . '_' . now()->format('Ymd_His') . '.pdf';
        $rutaRel = 'reporterias/' . $nombre;

        // Almacenar en disco y registrar índice
        Storage::disk('local')->put($rutaRel, $pdf->output());
        $this->reporteria->registrar(
            tipo:          'BINCARD_PDF',
            nombre:        'BINCARD PDF – ' . $producto->nombre,
            modulo:        'reportes',
            formato:       'PDF',
            filtros:       array_merge(['producto_id' => $producto->id, 'producto_nombre' => $producto->nombre], $filtros),
            rutaArchivo:   $rutaRel,
            nombreArchivo: $nombre,
        );

        return $pdf->download($nombre);
    }

    public function bincardServicio(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('reportes'), 403);

        $user = auth()->user();
        $ccId = $user->ccFiltro();

        $productos    = Producto::where('es_servicio', false)->orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);
        $serviciosF   = Producto::where('es_servicio', true)->orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);
        $familias     = Familia::orderBy('nombre')->get(['id', 'nombre']);
        $bincardsPorProducto = [];

        if (!$request->filled('producto_id')) {
            return view('admin.reportes.bincard', compact('productos', 'familias', 'serviciosF', 'bincardsPorProducto'));
        }

        $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'estado'      => ['nullable', 'string'],
        ]);

        $producto = Producto::withoutGlobalScopes()
            ->where('es_servicio', true)
            ->with(['categoria.familia', 'centroCosto:id,acronimo,nombre_completo'])
            ->findOrFail($request->producto_id);

        $query = ServicioEstado::with('usuario:id,name')
            ->where('producto_id', $producto->id);

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $estados = $query->orderBy('created_at')->get();

        $filas = $estados->map(function ($se) {
            return [
                'fecha'                => $se->created_at->format('d/m/Y H:i'),
                'movimiento'           => ServicioEstado::transicionLabel($se->estado_anterior, $se->estado),
                'estado_anterior'      => $se->estado_anterior ?? 'pendiente',
                'estado_nuevo'         => $se->estado,
                'estado_label_ant'     => ServicioEstado::label($se->estado_anterior ?? 'pendiente'),
                'estado_label_nvo'     => ServicioEstado::label($se->estado),
                'responsable'          => $se->usuario?->name ?? '—',
                'observacion'          => $se->observacion ?? '—',
                'progreso'             => ServicioEstado::progreso($se->estado),
                'colores'              => ServicioEstado::colores($se->estado),
                'documento_referencia' => $se->documento_referencia ?? '—',
            ];
        })->toArray();

        $ultimoEstado = $estados->last()?->estado ?? 'pendiente';

        $filtros = array_filter([
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'estado'      => $request->estado,
        ]);

        $dataServicio = [
            'producto'           => $producto,
            'filas'              => $filas,
            'generado_at'        => now()->format('d/m/Y H:i'),
            'generado_por'       => auth()->user()->name,
            'estado_actual'      => $ultimoEstado,
            'total_transiciones' => count($filas),
            'filtros'            => $filtros,
        ];

        if (!$request->boolean('solo_ver')) {
            $this->reporteria->registrar(
                tipo:    'BINCARD_SERVICIO',
                nombre:  'BINCARD Operacional – ' . $producto->nombre,
                modulo:  'reportes',
                formato: 'HTML',
                filtros: array_merge(
                    ['producto_id' => $producto->id, 'producto_nombre' => $producto->nombre],
                    $filtros
                ),
                notas: 'Vista en pantalla · ' . count($filas) . ' transición(es)',
            );
        }

        return view('admin.reportes.bincard', compact(
            'productos', 'familias', 'serviciosF', 'bincardsPorProducto', 'dataServicio'
        ));
    }
}
