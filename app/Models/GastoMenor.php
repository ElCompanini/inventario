<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoMenor extends Model
{
    protected $table = 'gastos_menores';

    protected $fillable = [
        'id_gm',
        'producto_id',
        'historial_cambio_id',
        'user_id',
        'rut_proveedor',
        'folio',
        'monto',
        'cantidad',
        'precio_neto',
        'fecha_emision',
        'documento_path',
    ];

    protected $casts = [
        'fecha_emision'  => 'datetime',
        'monto'         => 'integer',
        'precio_neto'   => 'integer',

    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function historialCambio()
    {
        return $this->belongsTo(HistorialCambio::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
