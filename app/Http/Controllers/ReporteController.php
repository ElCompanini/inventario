<?php

namespace App\Http\Controllers;

use App\Exports\BincardExport;
use App\Models\Categoria;
use App\Models\Familia;
use App\Models\Producto;
use App\Services\BincardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function __construct(protected BincardService $bincard) {}

    public function index()
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $user = auth()->user();
        $ccId = $user->ccFiltro();

        $productos = Producto::orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);

        $familias = Familia::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.reportes.bincard', compact('productos', 'familias'));
    }

    public function bincard(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $request->validate([
            'producto_id'  => ['required', 'integer', 'exists:productos,id'],
            'fecha_desde'  => ['nullable', 'date'],
            'fecha_hasta'  => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'tipo'         => ['nullable', 'string'],
        ]);

        $producto = Producto::withoutGlobalScopes()->with([
            'categoria.familia',
            'container',
            'unidadMedida:id,nombre,abreviacion',
            'centroCosto:id,acronimo,nombre_completo',
        ])->findOrFail($request->producto_id);

        $filtros = array_filter([
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'tipo'        => $request->tipo,
        ]);

        $data = $this->bincard->generarBincard($producto, $filtros);
        $data['mostrar_costos'] = auth()->user()->esAdmin();

        $user = auth()->user();
        $ccId = $user->ccFiltro();
        $productos = Producto::orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);
        $familias = Familia::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.reportes.bincard', compact('data', 'productos', 'familias'));
    }

    public function exportExcel(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

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
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'tipo'        => $request->tipo,
        ]);

        $data = $this->bincard->generarBincard($producto, $filtros);
        $data['mostrar_costos'] = true;

        $nombre = 'BINCARD_' . str_replace([' ', '/', '\\'], '_', $producto->nombre) . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new BincardExport($data), $nombre);
    }

    public function exportPdf(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

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
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'tipo'        => $request->tipo,
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

        $nombre = 'BINCARD_' . str_replace([' ', '/', '\\'], '_', $producto->nombre) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($nombre);
    }
}
