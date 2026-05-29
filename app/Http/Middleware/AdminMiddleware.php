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

        if (!$user->esAdmin() && !$user->tieneAlgunPermiso()) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes permisos para acceder a esa seccion.');
        }

        $routeName = $request->route()?->getName();

        if ($routeName && str_starts_with($routeName, 'admin.')) {
            if ($this->requiresDev($routeName)) {
                abort_unless($user->esDev(), 403);
            }

            $requiresAdmin = $this->requiresAdmin($routeName);
            if ($requiresAdmin) {
                abort_unless($user->esAdmin(), 403);
            }

            $permissions = $requiresAdmin ? [] : $this->permissionsForRoute($routeName);
            if ($permissions) {
                abort_unless($this->hasAnyPermission($user, $permissions), 403);
            }
        }

        return $next($request);
    }

    private function requiresDev(string $routeName): bool
    {
        return str_starts_with($routeName, 'admin.dev.');
    }

    private function requiresAdmin(string $routeName): bool
    {
        $adminOnly = [
            'admin.dashboard',
            'admin.precios.',
            'admin.productos.carga.',
            'admin.productos.crear.rapido',
            'admin.productos.show',
            'admin.productos.deshabilitar',
            'admin.productos.gestionar-estado',
            'admin.sicd.recibir.directo',
            'admin.reportes.historial',
            'admin.containers.create',
            'admin.containers.store',
            'admin.containers.destroy',
            'admin.containers.trasladar',
            'admin.containers.asignar-cc',
        ];

        foreach ($adminOnly as $prefix) {
            if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function permissionsForRoute(string $routeName): array
    {
        $map = [
            'admin.solicitudes.rechazadas' => ['rechazadas'],
            'admin.solicitudes.aprobar' => ['aprobar_solicitudes'],
            'admin.solicitudes.rechazar' => ['aprobar_solicitudes'],
            'admin.solicitudes.devolucion' => ['aprobar_solicitudes'],
            'admin.solicitudes.abrir' => ['aprobar_solicitudes'],
            'admin.solicitudes.cerrar' => ['aprobar_solicitudes'],
            'admin.devoluciones.' => ['aprobar_solicitudes'],
            'admin.solicitudes' => ['solicitudes', 'aprobar_solicitudes'],
            'admin.historial' => ['historial'],
            'admin.productos.editar' => ['stock'],
            'admin.productos.stock' => ['stock'],
            'admin.productos.trasladar' => ['stock'],
            'admin.sicd.' => ['sicd'],
            'admin.ordenes.' => ['ordenes'],
            'admin.usuarios.' => ['usuarios'],
            'admin.gastos-menores.' => ['gastos_menores'],
            'admin.reportes.' => ['reportes'],
            'admin.computadores.' => ['computadores'],
            'admin.containers.' => ['containers'],
            'admin.productos.catalogo' => ['catalogo'],
            'admin.catalogo.' => ['catalogo'],
        ];

        foreach ($map as $prefix => $permissions) {
            if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                return $permissions;
            }
        }

        return [];
    }

    private function hasAnyPermission($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->tienePermiso($permission)) {
                return true;
            }
        }

        return false;
    }
}
