<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Container;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'unidad',
        'unidad_medida_id',
        'codigo_barras',
        'stock_actual',
        'stock_minimo',
        'stock_critico',
        'contenedor',
        'categoria_id',
        'centro_costo_id',
        'stock_minimo_desde',
        'stock_critico_desde',
        'activo',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('activo', fn($q) => $q->where('productos.activo', true));
    }

    protected $casts = [
        'stock_minimo_desde'  => 'datetime',
        'stock_critico_desde' => 'datetime',
    ];

    public function centroCosto()
    {
        return $this->belongsTo(\App\Models\CentroCosto::class, 'centro_costo_id');
    }

    public function container()
    {
        // withoutGlobalScope('con_cc') para que containers sin CC asignado
        // también se carguen correctamente en la relación
        return $this->belongsTo(Container::class, 'contenedor')
                    ->withoutGlobalScope('con_cc');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    /**
     * Accessor backward-compatible: devuelve la abreviación de la unidad normalizada
     * si existe el FK, de lo contrario el texto libre legacy.
     */
    public function getUnidadDisplayAttribute(): string
    {
        return $this->unidadMedida?->abreviacion ?? $this->attributes['unidad'] ?? '—';
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }

    public function historialCambios()
    {
        return $this->hasMany(HistorialCambio::class);
    }

    public function precios()
    {
        return $this->hasMany(Precio::class);
    }

    public function ultimoPrecio(): ?Precio
    {
        return $this->precios()->latest()->first();
    }

    public function estadoStock(): string
    {
        if ($this->stock_actual <= $this->stock_critico) {
            return 'critico';
        }
        if ($this->stock_actual <= $this->stock_minimo) {
            return 'minimo';
        }
        return 'normal';
    }

    public function actualizarFechasStock(): void
    {
        $ahora = now();

        if ($this->stock_actual <= $this->stock_critico) {
            if (!$this->stock_critico_desde) {
                $this->stock_critico_desde = $ahora;
            }
            // Si sale de crítico, limpiar
        } else {
            $this->stock_critico_desde = null;
        }

        if ($this->stock_actual <= $this->stock_minimo && $this->stock_actual > $this->stock_critico) {
            if (!$this->stock_minimo_desde) {
                $this->stock_minimo_desde = $ahora;
            }
        } else {
            $this->stock_minimo_desde = null;
        }
    }
}
