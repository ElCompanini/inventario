<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudDevolucion extends Model
{
    protected $table = 'solicitudes_devolucion';

    protected $fillable = [
        'solicitud_id',
        'producto_id',
        'usuario_id',
        'cantidad',
        'motivo',
        'estado',
        'aprobado_por_id',
        'motivo_rechazo',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por_id');
    }

    public function numeroDoc(): string
    {
        return 'DEV-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function solRef(): string
    {
        return 'SOL-' . str_pad($this->solicitud_id, 6, '0', STR_PAD_LEFT);
    }
}
