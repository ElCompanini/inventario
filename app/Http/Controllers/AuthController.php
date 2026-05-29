<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->session()->forget('password_default_warning_dismissed');
            $destino = Auth::user()->esAdmin() ? route('admin.dashboard') : route('dashboard');
            return redirect()->intended($destino);
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function submitForgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $genericMessage = 'Si el correo existe en el sistema, se notificara al administrador.';
        $email = mb_strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            PasswordResetRequest::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'pending'],
                ['email' => $user->email, 'requested_at' => now()]
            );

            Log::info('password_reset_requested', [
                'accion' => 'password_reset_requested',
                'usuario_id' => $user->id,
                'usuario_email' => $user->email,
                'fecha_solicitud' => now()->toDateTimeString(),
            ]);
        }

        return back()->with('status', $genericMessage);
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function updateOwnPassword(Request $request)
    {
        $data = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->numbers()->symbols(),
                'regex:/[A-Z]/',
            ],
        ]);

        $user = $request->user();
        $oldStatus = (int) ($user->password_reset_status ?? 0);
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'password_reset_status' => 2,
        ])->save();

        $request->session()->forget('password_default_warning_dismissed');

        Log::info('password_changed_by_owner', [
            'accion' => 'password_changed_by_owner',
            'usuario_id' => $user->id,
            'usuario_email' => $user->email,
            'password_reset_status_anterior' => $oldStatus,
            'password_reset_status_nuevo' => 2,
            'fecha_cambio' => now()->toDateTimeString(),
        ]);

        return redirect($user->esAdmin() ? route('admin.dashboard') : route('dashboard'))
            ->with('success', 'Contrasena actualizada correctamente.');
    }

    public function dismissDefaultPasswordWarning(Request $request)
    {
        $request->session()->put('password_default_warning_dismissed', true);

        return response()->json(['ok' => true]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
