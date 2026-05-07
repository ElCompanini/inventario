<?php

namespace App\Http\Controllers;

use App\Models\Familia;
use App\Models\Precio;
use App\Models\Producto;
use Illuminate\Http\Request;

class PrecioController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $user = auth()->user();
        $ccId = $user->ccFiltro();

        $query = Precio::with([
            'producto:id,nombre,unidad,categoria_id,centro_costo_id',
            'familia:id,nombre',
            'categoria:id,nombre',
            'usuario:id,name',
        ])
        ->when($ccId, fn($q) => $q->whereHas('producto', fn($p) => $p->where('centro_costo_id', $ccId)))
        ->when($request->filled('producto'), fn($q) => $q->where('producto_id', $request->producto))
        ->when($request->filled('familia'),  fn($q) => $q->where('familia_id',  $request->familia))
        ->when($request->filled('fuente'),   fn($q) => $q->where('fuente',      $request->fuente))
        ->orderByDesc('created_at');

        $precios   = $query->paginate(50)->withQueryString();
        $productos = Producto::orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);
        $familias  = Familia::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.precios.index', compact('precios', 'productos', 'familias'));
    }
}
