<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OcItemPendiente extends Model
{
    use SoftDeletes;

    protected $table = 'oc_items_pendientes';

    protected $fillable = [
        'orden_compra_id',
        'oc_detalle_id',
        'sicd_detalle_id',
        'motivo',
        'notas',
        'pendiente_user_id',
        'pendiente_at',
        'producto_id_final',
        'container_id_final',
        'cantidad_resolucion',
        'resuelto_user_id',
        'resuelto_at',
        'eliminado_motivo',
    ];

    protected $casts = [
        'pendiente_at'       => 'datetime',
        'resuelto_at'        => 'datetime',
        'cantidad_resolucion' => 'integer',
    ];

    public static array $motivos = [
        'cambio'            => 'Cambio de producto',
        'producto_erroneo'  => 'Producto erróneo',
        'faltante'          => 'Faltante en despacho',
        'desistimiento'     => 'Desistimiento del proveedor',
        'otro'              => 'Otro motivo',
    ];

    public function motivoLabel(): string
    {
        return static::$motivos[$this->motivo] ?? ucfirst(str_replace('_', ' ', $this->motivo));
    }

    public function estaResuelto(): bool
    {
        return $this->resuelto_at !== null;
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function ocDetalle()
    {
        return $this->belongsTo(OrdenCompraDetalle::class, 'oc_detalle_id');
    }

    public function sicdDetalle()
    {
        return $this->belongsTo(\App\Models\SicdDetalle::class, 'sicd_detalle_id');
    }

    public function productoFinal()
    {
        return $this->belongsTo(Producto::class, 'producto_id_final');
    }

    public function containerFinal()
    {
        return $this->belongsTo(Container::class, 'container_id_final');
    }

    public function pendienteUser()
    {
        return $this->belongsTo(User::class, 'pendiente_user_id');
    }

    public function resueltoUser()
    {
        return $this->belongsTo(User::class, 'resuelto_user_id');
    }
}
