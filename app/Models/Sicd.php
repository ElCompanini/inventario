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
        'boleta_id',
        'documento_blob',
        'documento_mime',
        'descripcion',
        'rut_proveedor',
        'proveedor_nombre',
        'folio',
        'estado',
        'es_temporal',
        'permite_mas_oc',
        'usuario_id',
    ];

    protected static function booted(): void
    {
        // Exclude in-progress temporal SICDs from all normal queries.
        // Abandoned temporals are soft-deleted so already invisible.
        static::addGlobalScope('sin_temporales', function ($query) {
            $query->where('es_temporal', false);
        });
    }

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
        return $this->belongsToMany(OrdenCompra::class, 'oc_sicds');
    }
}
