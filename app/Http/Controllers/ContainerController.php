<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\HistorialCambio;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContainerController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->tienePermiso('containers'), 403);
        $containers = Container::withCount('productos')
            ->with('productos:id,nombre,descripcion,stock_actual,contenedor')
            ->orderBy('id')->get();
        return view('admin.containers.index', compact('containers'));
    }

    public function create()
    {
        return view('admin.containers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);

        Container::create($data);

        return redirect()->route('admin.containers.index')
            ->with('success', 'Container creado correctamente.');
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $container = Container::withCount('productos')->findOrFail($id);

        if ($container->productos_count > 0) {
            return back()->with('error',
                "No se puede eliminar el container \"{$container->nombre}\" porque tiene {$container->productos_count} producto(s) asignado(s).");
        }

        $container->delete();

        return back()->with('success', "Container \"{$container->nombre}\" eliminado correctamente.");
    }

    public function trasladar(int $id, Request $request)
    {
        $data = $request->validate([
            'contenedor_destino_id' => ['required', 'integer', 'exists:containers,id'],
            'motivo'                => ['required', 'string', 'max:500'],
        ], [
            'contenedor_destino_id.required' => 'Debes seleccionar un container de destino.',
            'contenedor_destino_id.exists'   => 'El container de destino no existe.',
            'motivo.required'                => 'El motivo es obligatorio.',
        ]);

        $origen = Container::findOrFail($id);

        if ((int) $data['contenedor_destino_id'] === $id) {
            return back()->with('error', 'El container de destino debe ser diferente al de origen.');
        }

        $destino = Container::findOrFail($data['contenedor_destino_id']);

        $productos = Producto::where('contenedor', $id)->get();

        if ($productos->isEmpty()) {
            return back()->with('error', "El container \"{$origen->nombre}\" no tiene productos para trasladar.");
        }

        DB::transaction(function () use ($productos, $destino, $data, $origen) {
            foreach ($productos as $producto) {
                $producto->contenedor = $destino->id;
                $producto->save();

                HistorialCambio::create([
                    'producto_id'  => $producto->id,
                    'contenedor_id'=> $destino->id,
                    'cantidad'     => $producto->stock_actual,
                    'tipo'         => 'traslado',
                    'motivo'       => "Traslado de {$origen->nombre} a {$destino->nombre}: {$data['motivo']}",
                    'aprobado_por' => Auth::user()->name,
                    'usuario_id'   => Auth::id(),
                ]);
            }
        });

        $total = $productos->count();

        return redirect()->route('admin.containers.index')
            ->with('success', "Se trasladaron {$total} producto(s) de \"{$origen->nombre}\" a \"{$destino->nombre}\" correctamente.");
    }
}
