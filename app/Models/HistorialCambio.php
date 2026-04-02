<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistorialCambio extends Model
{
    use SoftDeletes;

    protected $table = 'historial_cambios';

    protected $fillable = [
        'producto_id',
        'cantidad',
        'tipo',
        'motivo',
        'aprobado_por',
        'usuario_id',
        'origen',
        'origen_id',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
