<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sicd extends Model
{
    use SoftDeletes;

    protected $table = 'sicds';

    protected $fillable = [
        'codigo_sicd',
        'archivo_nombre',
        'archivo_ruta',
        'archivo_blob',
        'archivo_mime',
        'documento_blob',
        'documento_mime',
        'descripcion',
        'estado',
        'usuario_id',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(SicdDetalle::class);
    }

    public function ordenesCompra()
    {
        return $this->belongsToMany(OrdenCompra::class, 'orden_compra_sicd');
    }
}
