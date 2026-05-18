<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VpHistorial extends Model
{
    use SoftDeletes;

    protected $table = 'vp_historial';

    protected $fillable = [
        'usuario_id', 'fecha_desde', 'fecha_hasta', 'filtros',
        'total_sicd', 'total_oc', 'variacion', 'n_sicds', 'n_ocs', 'detalle',
    ];

    protected $casts = [
        'filtros'     => 'array',
        'detalle'     => 'array',
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'total_sicd'  => 'float',
        'total_oc'    => 'float',
        'variacion'   => 'float',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
