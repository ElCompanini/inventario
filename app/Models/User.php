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
        'centro_costo_id',
        'permisos',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('activo', fn($q) => $q->where('activo', 1));
    }


    public function esAdmin(): bool
    {
        return $this->rol >= 1;
    }

    public function esDev(): bool
    {
        return $this->rol >= 2;
    }

    public function centroCosto()
    {
        return $this->belongsTo(\App\Models\CentroCosto::class, 'centro_costo_id');
    }

    /**
     * Retorna solo las letras iniciales del acrónimo del centro de costo.
     * "TIC(RAMO)" → "TIC", "TIC/83" → "TIC", "TIC" → "TIC", null → null
     */
    public function centroCostoPrefix(): ?string
    {
        $acronimo = $this->centroCosto?->acronimo;
        if (empty($acronimo)) return null;
        return trim(preg_replace('/[^A-Za-z].*$/u', '', $acronimo));
    }

    /**
     * True si el usuario tiene restricción de centro de costo.
     * Aplica a cualquier usuario con centro_costo_id asignado, excepto dev.
     */
    public function tieneFiltroCC(): bool
    {
        if ($this->esDev()) return false;
        return $this->centro_costo_id !== null;
    }

    /**
     * ID a usar en filtros de CC:
     * - dev          → null  (sin filtro, ve todo)
     * - tiene CC     → su centro_costo_id
     * - sin CC y no dev → -1  (ID imposible, no ve nada)
     */
    public function ccFiltro(): ?int
    {
        if ($this->esDev()) return null;
        return $this->centro_costo_id ?? -1;
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
            'rol'               => 'integer',
        ];
    }
}
