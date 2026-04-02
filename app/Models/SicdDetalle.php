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
        'precio_neto',
        'total_neto',
    ];

    public function sicd()
    {
        return $this->belongsTo(Sicd::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
