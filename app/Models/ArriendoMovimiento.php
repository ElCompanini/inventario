<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArriendoMovimiento extends Model
{
    protected $table = 'arriendos_movimientos';

    public const ESTADOS = [
        'pendiente',
        'activo',
        'proximo_vencer',
        'finalizado',
        'renovado',
        'cancelado',
    ];

    protected $fillable = [
        'producto_id',
        'oc_id',
        'sicd_id',
        'proveedor_id',
        'proveedor_nombre',
        'estado_anterior',
        'estado_nuevo',
        'fecha_inicio',
        'fecha_termino',
        'condicion_termino',
        'duracion',
        'unidad_tiempo',
        'monto_periodo',
        'monto_total',
        'responsable_id',
        'ejecutado_por',
        'observacion',
        'documento_referencia',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_termino' => 'date',
        'duracion' => 'integer',
        'monto_periodo' => 'decimal:2',
        'monto_total' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'oc_id')->withTrashed();
    }

    public function sicd()
    {
        return $this->belongsTo(Sicd::class, 'sicd_id')->withTrashed();
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function ejecutor()
    {
        return $this->belongsTo(User::class, 'ejecutado_por');
    }

    public static function flujoPermitido(?string $actual): array
    {
        return [
            null => ['pendiente', 'activo'],
            'pendiente' => ['activo', 'cancelado'],
            'activo' => ['proximo_vencer', 'finalizado', 'renovado', 'cancelado'],
            'proximo_vencer' => ['finalizado', 'renovado', 'cancelado'],
            'renovado' => ['activo'],
            'finalizado' => [],
            'cancelado' => [],
        ][$actual] ?? [];
    }

    public static function label(?string $estado): string
    {
        return [
            'pendiente' => 'Pendiente',
            'activo' => 'Activo',
            'proximo_vencer' => 'Proximo a vencer',
            'finalizado' => 'Finalizado',
            'renovado' => 'Renovado',
            'cancelado' => 'Cancelado',
        ][$estado] ?? 'Pendiente';
    }

    public static function transicionLabel(?string $anterior, string $nuevo): string
    {
        if (!$anterior) {
            return 'Registro arriendo';
        }

        return self::label($anterior) . ' -> ' . self::label($nuevo);
    }

    public static function colores(?string $estado): array
    {
        return [
            'pendiente' => ['bg' => '#fef3c7', 'text' => '#92400e'],
            'activo' => ['bg' => '#dcfce7', 'text' => '#166534'],
            'proximo_vencer' => ['bg' => '#ffedd5', 'text' => '#c2410c'],
            'finalizado' => ['bg' => '#e0f2fe', 'text' => '#075985'],
            'renovado' => ['bg' => '#ede9fe', 'text' => '#5b21b6'],
            'cancelado' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
        ][$estado] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
    }
}
