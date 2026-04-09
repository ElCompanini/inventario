<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\Producto;
use App\Models\Sicd;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with([
            'container',
            'solicitudes' => fn($q) => $q->where('tipo', 'salida')->where('estado', 'pendiente')->with('usuario:id,name'),
        ])->orderBy('nombre')->get();
        $containers = Container::orderBy('id')->get();
        $sicds = Sicd::orderByDesc('created_at')->get(['id', 'codigo_sicd', 'descripcion', 'estado']);
        return view('dashboard', compact('productos', 'containers', 'sicds'));
    }
}
