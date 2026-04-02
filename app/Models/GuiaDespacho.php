<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuiaDespacho extends Model
{
    use SoftDeletes;

    protected $table = 'guias_despacho';

    protected $fillable = [
        'orden_compra_id',
        'nombre_original',
        'ruta',
        'subido_por',
        'usuario_id',
    ];

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }
}
