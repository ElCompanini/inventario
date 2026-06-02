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
        'documento_ruta',
        'documento_nombre',
        'documento_estado',
        'documento_error',
        'documento_intentos',
        'documento_adjuntado_at',
        'documento_adjuntado_por',
        'descripcion',
        'rut_proveedor',
        'proveedor_nombre',
        'folio',
        'estado',
        'es_temporal',
        'permite_mas_oc',
        'usuario_id',
    ];

    protected $casts = [
        'documento_intentos' => 'integer',
        'documento_adjuntado_at' => 'datetime',
        'es_temporal' => 'boolean',
        'permite_mas_oc' => 'boolean',
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

    public function usuarioDocumento()
    {
        return $this->belongsTo(User::class, 'documento_adjuntado_por');
    }

    public function tieneDocumentoPersistido(): bool
    {
        return !empty($this->documento_ruta) || !empty($this->documento_blob);
    }

    public function detalles()
    {
        return $this->hasMany(SicdDetalle::class);
    }

    // ── Helpers financieros (neto viene de sicd_detalles, IVA = 19%) ─────────

    public function montoNeto(): float
    {
        $this->loadMissing('detalles');
        return (float) ($this->detalles->sum('total_neto') ?? 0);
    }

    public function montoIva(): float
    {
        return round($this->montoNeto() * 0.19, 2);
    }

    public function montoTotal(): float
    {
        return round($this->montoNeto() * 1.19, 2);
    }

    public function ordenesCompra()
    {
        return $this->belongsToMany(OrdenCompra::class, 'oc_sicds');
    }
}
