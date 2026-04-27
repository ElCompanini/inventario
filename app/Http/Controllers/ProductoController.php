<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\Familia;
use App\Models\Producto;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with([
            'container',
            'categoria.familia',
            'solicitudes' => fn($q) => $q->where('tipo', 'salida')->where('estado', 'pendiente')->with('usuario:id,name'),
        ])->orderBy('nombre')->get();
        $containers = Container::orderBy('id')->get();
        $familias   = Familia::with('categorias')->where('activo', true)->orderBy('nombre')->get();
        return view('dashboard', compact('productos', 'containers', 'familias'));
    }
}
