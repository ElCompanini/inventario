<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use SoftDeletes;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero_oc',
        'archivo_nombre',
        'archivo_ruta',
        'estado',
        'procesado_por',
        'procesado_at',
        'usuario_id',
    ];

    protected $casts = [
        'procesado_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sicds()
    {
        return $this->belongsToMany(Sicd::class, 'orden_compra_sicd');
    }

    public function factura()
    {
        return $this->hasOne(Factura::class);
    }

    public function guia()
    {
        return $this->hasOne(GuiaDespacho::class);
    }

    public function tieneFactura(): bool
    {
        return $this->factura !== null;
    }
}
