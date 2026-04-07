<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin completo o usuario con al menos un permiso asignado
        if ($user->esAdmin() || $user->tieneAlgunPermiso()) {
            return $next($request);
        }

        return redirect()->route('dashboard')
            ->with('error', 'No tienes permisos para acceder a esa sección.');
    }
}
