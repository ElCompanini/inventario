<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use App\Models\Container;
use App\Models\Familia;
use App\Models\Producto;

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
            'centroCosto:id,acronimo',
            'solicitudes' => fn($q) => $q->where('tipo', 'salida')->where('estado', 'pendiente')->with('usuario:id,name'),
        ])
        ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
        ->orderBy('nombre')->get();

        $containers = Container::orderBy('id')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get();

        $familias = Familia::with('categorias')->where('activo', true)
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->orderBy('nombre')->get();

        $centrosCostoConProductos = $isDev
            ? CentroCosto::orderBy('nombre_completo')->get(['id', 'nombre_completo'])
            : collect();

        return view('dashboard', compact('productos', 'containers', 'familias', 'centrosCostoConProductos'));
    }
}
