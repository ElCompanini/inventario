<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenCompraDetalle extends Model
{
    protected $table = 'oc_detalles';

    protected $fillable = [
        'orden_compra_id',
        'sicd_detalle_id',
        'cantidad_asignada',
        'cantidad_recibida',
        'precio_neto',
        'total_neto',
    ];

    protected $casts = [
        'cantidad_asignada' => 'integer',
        'cantidad_recibida' => 'integer',
        'precio_neto'       => 'float',
        'total_neto'        => 'float',
    ];

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function sicdDetalle()
    {
        return $this->belongsTo(SicdDetalle::class, 'sicd_detalle_id');
    }
}
