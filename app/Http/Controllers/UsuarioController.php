<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $usuarios = User::with('centroCosto')->orderBy('name')->get();
        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $centrosCosto = CentroCosto::orderBy('acronimo')->get(['id', 'acronimo']);
        return view('admin.usuarios.crear', compact('centrosCosto'));
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|max:255|unique:users,email',
            'password'        => 'required|string|min:6|confirmed',
            'centro_costo_id' => 'nullable|integer|exists:centros_costo,id',
        ]);

        $rol = $authUser->esDev() ? (int) $request->input('rol', 0) : 0;
        if ($authUser->esDev()) {
            abort_unless(in_array($rol, [0, 1, 2]), 422);
        }

        $ccId = ($authUser->esDev() || $authUser->esAdmin())
            ? ($data['centro_costo_id'] ?: null)
            : null;

        User::create([
            'name'            => $data['name'],
            'email'           => $data['email'],
            'password'        => Hash::make($data['password']),
            'rol'             => $rol,
            'centro_costo_id' => $ccId,
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario {$data['name']} creado.");
    }

    public function edit(int $id)
    {
        $usuario      = User::findOrFail($id);
        $centrosCosto = CentroCosto::orderBy('acronimo')->get(['id', 'acronimo']);
        return view('admin.usuarios.editar', compact('usuario', 'centrosCosto'));
    }

    public function update(Request $request, int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $usuario = User::findOrFail($id);

        $authUser = auth()->user();
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|max:255|unique:users,email,' . $id,
            'centro_costo_id' => 'nullable|integer|exists:centros_costo,id',
        ]);

        $usuario->name  = $data['name'];
        $usuario->email = $data['email'];

        if ($authUser->esDev() && auth()->id() !== $usuario->id) {
            $rol = (int) $request->input('rol', $usuario->rol);
            if (in_array($rol, [0, 1, 2])) {
                $usuario->rol = $rol;
            }
        }

        if ($authUser->esDev() || $authUser->esAdmin()) {
            $usuario->centro_costo_id = $data['centro_costo_id'] ?: null;
        }

        // Permisos: solo dev puede modificarlos
        if ($authUser->esDev()) {
            if ($usuario->rol === 0) {
                $permisos = array_keys(array_filter(
                    $request->only(array_keys(User::PERMISOS_DISPONIBLES))
                ));
                $usuario->permisos = count($permisos) ? $permisos : null;
            } else {
                $usuario->permisos = null;
            }
        }

        $usuario->save();

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario {$usuario->name} actualizado.");
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $usuario = User::findOrFail($id);

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->activo = 0;
        $usuario->save();

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario desactivado.");
    }
}
