<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SicdDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'sicd_detalles';

    protected $fillable = [
        'sicd_id',
        'producto_id',
        'nombre_producto_excel',
        'unidad',
        'cantidad_solicitada',
        'cantidad_recibida',
        'motivo_recepcion',
        'precio_neto',
        'total_neto',
        'precio_neto_original',
        'total_neto_original',
    ];

    public function sicd()
    {
        return $this->belongsTo(Sicd::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ocDetalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'sicd_detalle_id');
    }

    /** Cantidad ya asignada a otras OCs */
    public function getCantidadAsignadaAttribute(): int
    {
        return (int) $this->ocDetalles->sum('cantidad_asignada');
    }

    /** Cantidad disponible para asignar a una nueva OC */
    public function getCantidadDisponibleAttribute(): int
    {
        return max(0, $this->cantidad_solicitada - $this->cantidad_asignada);
    }
}
