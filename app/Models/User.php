<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Solicitud;
use App\Models\HistorialCambio;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Permisos disponibles para usuarios no-admin
    public const PERMISOS_DISPONIBLES = [
        'historial'           => 'Ver historial de cambios',
        'solicitudes'         => 'Ver solicitudes pendientes',
        'aprobar_solicitudes' => 'Aprobar y rechazar solicitudes',
        'rechazadas'          => 'Ver solicitudes rechazadas',
        'sicd'                => 'Ver y gestionar SICD',
        'ordenes'             => 'Ver y gestionar órdenes de compra',
        'containers'          => 'Ver contenedores',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'centro_costo',
        'permisos',
    ];


    public function esAdmin(): bool
    {
        // dev tiene todos los permisos de admin y más
        return $this->rol === 'admin' || $this->rol === 'dev';
    }

    public function esDev(): bool
    {
        return $this->rol === 'dev';
    }

    /**
     * Retorna solo las letras iniciales del centro de costo.
     * "TIC(RAMO)" → "TIC", "TIC/83" → "TIC", "TIC" → "TIC", null → null
     */
    public function centroCostoPrefix(): ?string
    {
        if (empty($this->centro_costo)) return null;
        return trim(preg_replace('/[^A-Za-z].*$/u', '', $this->centro_costo));
    }

    /**
     * True si el usuario tiene restricción de centro de costo.
     * Aplica a cualquier usuario con centro_costo asignado, excepto dev.
     */
    public function tieneFiltroCC(): bool
    {
        if ($this->esDev()) return false;
        return !empty($this->centro_costo);
    }

    public function tienePermiso(string $permiso): bool
    {
        if ($this->esAdmin()) return true;
        $permisos = $this->permisos ?? [];
        return in_array($permiso, $permisos);
    }

    public function tieneAlgunPermiso(): bool
    {
        if ($this->esAdmin()) return true;
        $permisos = $this->permisos ?? [];
        return count($permisos) > 0;
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'usuario_id');
    }

    public function historialCambios()
    {
        return $this->hasMany(HistorialCambio::class, 'usuario_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'permisos'          => 'array',
        ];
    }
}
