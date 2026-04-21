<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sicd extends Model
{
    protected $table = 'sicds';

    protected $fillable = [
        'codigo_sicd',
        'boleta_id',
        'documento_blob',
        'documento_mime',
        'descripcion',
        'estado',
        'usuario_id',
    ];

    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

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
