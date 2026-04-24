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
