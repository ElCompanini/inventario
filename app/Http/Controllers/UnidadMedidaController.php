<?php

namespace App\Http\Controllers;

use App\Models\UnidadMedida;
use Illuminate\Http\Request;

class UnidadMedidaController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $unidades = UnidadMedida::orderBy('nombre')->get();
        return view('admin.catalogo.unidades.index', compact('unidades'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:80'],
            'abreviacion' => ['required', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string', 'max:200'],
        ], [
            'nombre.required'      => 'El nombre es obligatorio.',
            'abreviacion.required' => 'La abreviación es obligatoria.',
        ]);

        $data['nombre']      = strtoupper(trim($data['nombre']));
        $data['abreviacion'] = strtoupper(trim($data['abreviacion']));

        if (UnidadMedida::where('nombre', $data['nombre'])->exists()) {
            return back()->withErrors(['nombre' => "Ya existe una unidad llamada \"{$data['nombre']}\"."])->withInput();
        }
        if (UnidadMedida::where('abreviacion', $data['abreviacion'])->exists()) {
            return back()->withErrors(['abreviacion' => "Ya existe la abreviación \"{$data['abreviacion']}\"."])->withInput();
        }

        UnidadMedida::create($data);
        return redirect()->route('admin.catalogo.unidades.index')->with('success', "Unidad \"{$data['nombre']}\" creada correctamente.");
    }

    public function update(Request $request, UnidadMedida $unidad)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:80'],
            'abreviacion' => ['required', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'activo'      => ['boolean'],
        ]);

        $data['nombre']      = strtoupper(trim($data['nombre']));
        $data['abreviacion'] = strtoupper(trim($data['abreviacion']));

        if (UnidadMedida::where('nombre', $data['nombre'])->where('id', '!=', $unidad->id)->exists()) {
            return back()->withErrors(['nombre' => "Ya existe una unidad con ese nombre."]);
        }

        $unidad->update($data);
        return back()->with('success', "Unidad actualizada.");
    }

    public function destroy(UnidadMedida $unidad)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        if ($unidad->productos()->count() > 0) {
            return back()->withErrors(['error' => "No se puede desactivar: tiene {$unidad->productos()->count()} producto(s) asignados."]);
        }
        // Desactivación lógica: soft delete + marcar inactivo
        $unidad->update(['activo' => false]);
        $unidad->delete(); // sets deleted_at via SoftDeletes
        return back()->with('success', "Unidad \"{$unidad->nombre}\" desactivada.");
    }

    // API: listado para dropdowns JS
    public function listar()
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        return response()->json(
            UnidadMedida::activas()->orderBy('nombre')->get(['id', 'nombre', 'abreviacion'])
        );
    }
}
