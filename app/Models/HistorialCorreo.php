<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialCorreo extends Model
{
    protected $table = 'historial_correos';

    protected $fillable = [
        'tipo_correo',
        'historial_cambio_id',
        'solicitud_id',
        'centro_costo_id',
        'origen_type',
        'origen_id',
        'destinatarios',
        'metadata',
        'estado',
        'error',
        'enviado_at',
    ];

    protected $casts = [
        'destinatarios' => 'array',
        'metadata' => 'array',
        'enviado_at' => 'datetime',
    ];

    public function historialCambio()
    {
        return $this->belongsTo(HistorialCambio::class, 'historial_cambio_id')->withTrashed();
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }
}
