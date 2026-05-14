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
        // Campos API Mercado Público
        'api_codigo',
        'api_licitacion_codigo',
        'api_items',
        'api_nombre',
        'api_descripcion',
        'api_tipo',
        'api_tipo_moneda',
        'api_estado_mp',
        'api_fecha_envio',
        'api_total',
        'api_impuestos',
        'api_proveedor_nombre',
        'api_proveedor_rut',
        'api_contacto',
        'api_validado_at',
        'api_error',
        'api_intentos',
    ];

    protected $casts = [
        'procesado_at'    => 'datetime',
        'api_validado_at' => 'datetime',
        'api_total'       => 'integer',
        'api_impuestos'   => 'integer',
        'api_intentos'    => 'integer',
        'api_items'       => 'array',
    ];

    public function estaValidada(): bool
    {
        return $this->estado === 'validado' || $this->estado === 'recibido';
    }

    // ── Helpers financieros unificados ────────────────────────────────────────
    // Para OCs validadas en MP: api_total = total con IVA, api_impuestos = IVA.
    // Para OCs internas sin validación: calculado desde oc_detalles + 19%.

    public function montoTotal(): float
    {
        if ($this->api_total !== null) {
            return (float) $this->api_total;
        }
        return round($this->montoNeto() * 1.19, 2);
    }

    public function montoIva(): float
    {
        if ($this->api_impuestos !== null && $this->api_impuestos > 0) {
            return (float) $this->api_impuestos;
        }
        return round($this->montoNeto() * 0.19, 2);
    }

    public function montoNeto(): float
    {
        if ($this->api_total !== null) {
            return (float) max(0, $this->api_total - ($this->api_impuestos ?? 0));
        }
        return (float) ($this->detalles?->sum('total_neto') ?? 0);
    }

    public function totalFormateado(): string
    {
        if ($this->api_total === null) return '—';
        return '$' . number_format($this->api_total, 0, ',', '.');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sicds()
    {
        return $this->belongsToMany(Sicd::class, 'oc_sicds');
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

    public function tieneGuia(): bool
    {
        return $this->guia !== null;
    }

    /** Productos asignados explícitamente a esta OC */
    public function detalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class);
    }
}
