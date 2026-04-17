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
        $usuarios = User::orderBy('name')->get();
        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $centrosCosto = CentroCosto::orderBy('nombre')->pluck('nombre');
        return view('admin.usuarios.crear', compact('centrosCosto'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|max:255|unique:users,email',
            'password'     => 'required|string|min:6|confirmed',
            'rol'          => 'required|in:admin,usuario',
            'centro_costo' => 'nullable|string|max:100',
        ]);

        $authUser = auth()->user();
        $cc = ($authUser->esDev() || $authUser->esAdmin()) ? (trim($data['centro_costo'] ?? '') ?: null) : null;

        // Si dev escribe un centro de costo nuevo, guardarlo en la tabla
        if ($cc && $authUser->esDev()) {
            CentroCosto::firstOrCreate(['nombre' => strtoupper($cc)]);
        }

        User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
            'rol'          => $data['rol'],
            'centro_costo' => $cc,
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario {$data['name']} creado.");
    }

    public function edit(int $id)
    {
        $usuario      = User::findOrFail($id);
        $centrosCosto = CentroCosto::orderBy('nombre')->pluck('nombre');
        return view('admin.usuarios.editar', compact('usuario', 'centrosCosto'));
    }

    public function update(Request $request, int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $usuario = User::findOrFail($id);

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|max:255|unique:users,email,' . $id,
            'rol'          => 'required|in:admin,usuario',
            'centro_costo' => 'nullable|string|max:100',
        ]);

        $usuario->name         = $data['name'];
        $usuario->email        = $data['email'];
        $usuario->rol          = $data['rol'];
        $authUser = auth()->user();
        if ($authUser->esDev() || $authUser->esAdmin()) {
            $cc = trim($data['centro_costo'] ?? '') ?: null;
            // Si dev escribe un valor nuevo, guardarlo en la tabla
            if ($cc && $authUser->esDev()) {
                CentroCosto::firstOrCreate(['nombre' => strtoupper($cc)]);
            }
            $usuario->centro_costo = $cc;
        }

        // Permisos: solo si rol es usuario (admin tiene todo)
        if ($data['rol'] === 'usuario') {
            $permisos = array_keys(array_filter(
                $request->only(array_keys(User::PERMISOS_DISPONIBLES))
            ));
            $usuario->permisos = count($permisos) ? $permisos : null;
        } else {
            $usuario->permisos = null;
        }

        $usuario->save();

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario {$usuario->name} actualizado.");
    }

    public function destroy(int $id)
    {
        $usuario = User::findOrFail($id);

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario eliminado.");
    }
}
