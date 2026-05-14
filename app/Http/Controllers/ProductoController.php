<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use App\Models\Container;
use App\Models\Familia;
use App\Models\GastoMenor;
use App\Models\HistorialCambio;
use App\Models\Marca;
use App\Models\OrdenCompra;
use App\Models\Precio;
use App\Models\Producto;
use App\Models\Sicd;

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
        ->where('es_servicio', false)
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

        // tipo column drives SIN FAMILIA / PYP detection in JS (no hardcoded IDs needed)
        return view('dashboard', compact('productos', 'containers', 'familias', 'centrosCostoConProductos'));
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
            ])
            ->findOrFail($id);

        $user = auth()->user();

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
        // SICDs que tienen este producto en sus detalles
        $sicds = Sicd::whereHas('detalles', fn($q) => $q->where('producto_id', $id))
            ->with(['detalles' => fn($q) => $q->where('producto_id', $id)])
            ->orderByDesc('created_at')
            ->get();

        // OCs relacionadas (via SICDs)
        $sicdIds = $sicds->pluck('id');
        $ordenes = OrdenCompra::whereHas('sicds', fn($q) => $q->whereIn('sicds.id', $sicdIds))
            ->orderByDesc('created_at')
            ->get();

        // Gastos menores con este producto
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
            'producto', 'movimientos', 'precios',
            'ultimoCosto', 'costoPromedio',
            'sicds', 'ordenes', 'gastos',
            'totalEntradas', 'totalSalidas',
            'containers',
        ));
    }
}
