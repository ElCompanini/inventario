<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with([
            'container',
            'solicitudes' => fn($q) => $q->where('tipo', 'salida')->where('estado', 'pendiente'),
        ])->orderBy('nombre')->get();
        $containers = Container::orderBy('id')->get();
        return view('dashboard', compact('productos', 'containers'));
    }
}
