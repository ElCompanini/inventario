<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicioEstado extends Model
{
    use SoftDeletes;

    protected $table = 'servicio_estados';

    protected $fillable = [
        'producto_id', 'estado', 'estado_anterior',
        'usuario_id', 'observacion',
        'sicd_id', 'orden_compra_id', 'documento_referencia',
        'proveedor_nombre',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function sicd()
    {
        return $this->belongsTo(Sicd::class)->withTrashed();
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class)->withTrashed();
    }

    public static function flujoSiguiente(string $estado): ?string
    {
        return [
            'pendiente'  => 'aprobado',
            'aprobado'   => 'en_proceso',
            'en_proceso' => 'ejecutado',
            'ejecutado'  => 'validado',
            'validado'   => 'cerrado',
        ][$estado] ?? null;
    }

    public static function progreso(string $estado): int
    {
        return [
            'pendiente'  => 0,
            'aprobado'   => 20,
            'en_proceso' => 50,
            'ejecutado'  => 80,
            'validado'   => 90,
            'cerrado'    => 100,
            'cancelado'  => 0,
        ][$estado] ?? 0;
    }

    public static function colores(string $estado): array
    {
        return [
            'pendiente'  => ['bg' => '#f3f4f6', 'text' => '#6b7280', 'dot' => '#9ca3af', 'barra' => '#9ca3af', 'dark_bg' => '#334155', 'dark_text' => '#cbd5e1'],
            'aprobado'   => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'dot' => '#3b82f6', 'barra' => '#3b82f6', 'dark_bg' => '#1e1b4b', 'dark_text' => '#93c5fd'],
            'en_proceso' => ['bg' => '#fefce8', 'text' => '#a16207', 'dot' => '#eab308', 'barra' => '#eab308', 'dark_bg' => '#422006', 'dark_text' => '#fde68a'],
            'ejecutado'  => ['bg' => '#f0fdf4', 'text' => '#15803d', 'dot' => '#22c55e', 'barra' => '#22c55e', 'dark_bg' => '#052e16', 'dark_text' => '#86efac'],
            'validado'   => ['bg' => '#f0fdf4', 'text' => '#166534', 'dot' => '#16a34a', 'barra' => '#16a34a', 'dark_bg' => '#064e3b', 'dark_text' => '#bbf7d0'],
            'cerrado'    => ['bg' => '#e0f2fe', 'text' => '#075985', 'dot' => '#0ea5e9', 'barra' => '#64748b', 'dark_bg' => '#0f172a', 'dark_text' => '#f8fafc'],
            'cancelado'  => ['bg' => '#fef2f2', 'text' => '#dc2626', 'dot' => '#ef4444', 'barra' => '#ef4444', 'dark_bg' => '#450a0a', 'dark_text' => '#fca5a5'],
        ][$estado] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280', 'dot' => '#9ca3af', 'barra' => '#9ca3af', 'dark_bg' => '#334155', 'dark_text' => '#cbd5e1'];
    }

    public static function label(string $estado): string
    {
        return [
            'pendiente'  => 'Pendiente',
            'aprobado'   => 'Aprobado',
            'en_proceso' => 'En proceso',
            'ejecutado'  => 'Ejecutado',
            'validado'   => 'Validado',
            'cerrado'    => 'Cerrado',
            'cancelado'  => 'Cancelado',
        ][$estado] ?? ucfirst($estado);
    }

    public static function transicionLabel(?string $estadoAnterior, string $estadoNuevo): string
    {
        if ($estadoNuevo === 'cancelado') return 'Servicio cancelado';
        return [
            'aprobado'   => 'Servicio aprobado',
            'en_proceso' => 'Inicio ejecución',
            'ejecutado'  => 'Servicio ejecutado',
            'validado'   => 'Servicio validado',
            'cerrado'    => 'Servicio cerrado',
        ][$estadoNuevo] ?? 'Cambio de estado';
    }
}
