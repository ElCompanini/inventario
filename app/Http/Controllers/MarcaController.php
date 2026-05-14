<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarcaController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);
        $marcas = Marca::withTrashed()->with('categoria.familia')->withCount('productos')->orderBy('nombre')->get();
        return view('admin.catalogo.marcas.index', compact('marcas'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $data = $request->validate([
            'nombre'      => [
                'required', 'string', 'max:100',
                Rule::unique('marcas', 'nombre')
                    ->where('categoria_id', $request->categoria_id)
                    ->whereNull('deleted_at'),
            ],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
        ], [
            'nombre.unique'         => 'Ya existe una marca con ese nombre en esta categoría.',
            'categoria_id.required' => 'Debes seleccionar una categoría para la marca.',
        ]);

        $marca = Marca::create([
            'nombre'      => strtoupper(trim($data['nombre'])),
            'categoria_id' => $data['categoria_id'],
        ]);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'id' => $marca->id, 'nombre' => $marca->nombre]);
        }

        return back()->with('success', "Marca \"{$marca->nombre}\" creada correctamente.");
    }

    public function update(Request $request, Marca $marca)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $data = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('marcas', 'nombre')
                    ->where('categoria_id', $marca->categoria_id)
                    ->whereNull('deleted_at')
                    ->ignore($marca->id),
            ],
        ], [
            'nombre.unique' => 'Ya existe una marca con ese nombre en esta categoría.',
        ]);

        $oldNombre = $marca->nombre;
        $marca->update(['nombre' => strtoupper(trim($data['nombre']))]);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'nombre' => $marca->nombre]);
        }

        return back()->with('success', 'Marca actualizada.');
    }

    public function destroy(Marca $marca)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        if ($marca->protegido) {
            $msg = "El registro \"{$marca->nombre}\" está protegido y no puede eliminarse.";
            return request()->ajax()
                ? response()->json(['ok' => false, 'message' => $msg], 403)
                : back()->withErrors(['error' => $msg]);
        }

        $count = $marca->productos()->count();
        if ($count > 0) {
            if (request()->ajax()) {
                return response()->json(['ok' => false, 'message' => "No se puede eliminar: tiene {$count} producto(s) asignados."], 422);
            }
            return back()->withErrors(['error' => "No se puede eliminar: tiene {$count} producto(s) asignados."]);
        }

        $marca->update(['activo' => false]);
        $marca->delete();

        if (request()->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', "Marca \"{$marca->nombre}\" eliminada.");
    }

    public function toggle(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $marca = Marca::withTrashed()->findOrFail($id);

        if ($marca->protegido) {
            $msg = "El registro \"{$marca->nombre}\" está protegido y no puede deshabilitarse.";
            return request()->ajax()
                ? response()->json(['ok' => false, 'message' => $msg], 403)
                : back()->withErrors(['error' => $msg]);
        }

        if ($marca->trashed()) {
            $marca->restore();
            $marca->update(['activo' => true]);
        } else {
            $nuevo = !$marca->activo;
            $marca->update(['activo' => $nuevo]);
            if (!$nuevo) {
                $marca->delete();
            }
        }

        if (request()->ajax()) {
            return response()->json(['ok' => true, 'activo' => (bool) $marca->activo]);
        }

        return back()->with('success', $marca->activo ? "Marca \"{$marca->nombre}\" activada." : "Marca \"{$marca->nombre}\" desactivada.");
    }

    public function listar()
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);
        return response()->json(
            Marca::activas()->orderBy('nombre')->get(['id', 'nombre', 'categoria_id'])
        );
    }
}
