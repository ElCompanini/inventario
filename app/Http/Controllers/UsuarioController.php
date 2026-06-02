<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('usuarios'), 403);

        $mostrarPendientes = auth()->user()->esDev() && $request->input('filtro') === 'reset_pendiente';
        $usuarios = User::with(['centroCosto', 'pendingPasswordResetRequest'])
            ->when($mostrarPendientes, fn($q) => $q->whereHas('pendingPasswordResetRequest'))
            ->orderBy('name')
            ->get();

        $resetPendientesCount = PasswordResetRequest::where('status', 'pending')->count();

        return view('admin.usuarios.index', compact('usuarios', 'mostrarPendientes', 'resetPendientesCount'));
    }

    public function create()
    {
        abort_unless(auth()->user()->tienePermiso('usuarios'), 403);
        $centrosCosto = CentroCosto::orderBy('acronimo')->get(['id', 'acronimo']);
        return view('admin.usuarios.crear', compact('centrosCosto'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('usuarios'), 403);
        $authUser = auth()->user();
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|max:255|unique:users,email',
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
            'name'                  => $data['name'],
            'email'                 => $data['email'],
            'password'              => Hash::make('123'),
            'password_reset_status' => 1,
            'rol'                   => $rol,
            'centro_costo_id'       => $ccId,
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario {$data['name']} creado.");
    }

    public function edit(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('usuarios'), 403);
        $usuario = User::with('pendingPasswordResetRequest')->findOrFail($id);
        $centrosCosto = CentroCosto::orderBy('acronimo')->get(['id', 'acronimo']);
        return view('admin.usuarios.editar', compact('usuario', 'centrosCosto'));
    }

    public function update(Request $request, int $id)
    {
        abort_unless(auth()->user()->tienePermiso('usuarios'), 403);
        abort_if(
            $request->filled('password') || $request->filled('password_confirmation'),
            403,
            'No se permite escribir manualmente la contrasena de otro usuario.'
        );

        $usuario = User::findOrFail($id);
        $authUser = auth()->user();

        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|max:255|unique:users,email,' . $id,
            'centro_costo_id' => 'nullable|integer|exists:centros_costo,id',
        ]);

        $usuario->name = $data['name'];
        $usuario->email = $data['email'];

        if ($authUser->esDev() && auth()->id() !== $usuario->id && (int) $usuario->rol !== 2) {
            $rol = (int) $request->input('rol', $usuario->rol);
            if (in_array($rol, [0, 1, 2])) {
                $usuario->rol = $rol;
            }
        }

        if ($authUser->esDev() || $authUser->esAdmin()) {
            $usuario->centro_costo_id = $data['centro_costo_id'] ?: null;
        }

        if ($authUser->esDev()) {
            if ($usuario->rol <= 1) {
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

    public function resetPasswordDefault(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('usuarios'), 403);
        abort_unless((int) auth()->user()->rol === 2, 403, 'Solo el Super Administrador puede resetear contrasenas.');

        $authUser = auth()->user();
        $usuario = User::findOrFail($id);
        $oldStatus = (int) ($usuario->password_reset_status ?? 0);

        DB::transaction(function () use ($usuario, $authUser, $oldStatus) {
            $usuario->forceFill([
                'password' => Hash::make('123'),
                'password_reset_status' => 1,
            ])->save();

            PasswordResetRequest::where('user_id', $usuario->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'resolved',
                    'resolved_by' => $authUser->id,
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::info('reset_password_default', [
                'accion' => 'reset_password_default',
                'auto_reset' => $authUser->id === $usuario->id,
                'super_administrador_id' => $authUser->id,
                'super_administrador_email' => $authUser->email,
                'usuario_afectado_id' => $usuario->id,
                'usuario_afectado_email' => $usuario->email,
                'fecha_reseteo' => now()->toDateTimeString(),
                'password_reset_status_anterior' => $oldStatus,
                'password_reset_status_nuevo' => 1,
            ]);
        });

        $mensaje = $authUser->id === $usuario->id
            ? 'Tu contrasena fue reseteada a la contrasena por defecto.'
            : "Contrasena de {$usuario->name} reseteada a la contrasena por defecto.";

        return redirect()->route('admin.usuarios.index')
            ->with('success', $mensaje);
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('usuarios'), 403);
        $usuario = User::findOrFail($id);

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->activo = 0;
        $usuario->save();

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario desactivado.');
    }
}
