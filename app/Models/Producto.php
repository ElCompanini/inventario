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
        'marca_id',
        'centro_costo_id',
        'stock_minimo_desde',
        'stock_critico_desde',
        'activo',
        'es_servicio',
        'maneja_presentacion',
        'tipo_presentacion',
        'cantidad_presentacion',
        'unidad_base',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('activo', fn($q) => $q->where('productos.activo', true));
    }

    protected $casts = [
        'stock_minimo_desde'    => 'datetime',
        'stock_critico_desde'   => 'datetime',
        'es_servicio'           => 'boolean',
        'maneja_presentacion'   => 'boolean',
        'cantidad_presentacion' => 'integer',
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

    public function marca()
    {
        return $this->belongsTo(Marca::class);
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

    public function servicioEstados()
    {
        return $this->hasMany(ServicioEstado::class)->orderBy('created_at');
    }

    public function ultimoPrecio(): ?Precio
    {
        return $this->precios()->latest()->first();
    }

    public function esServicio(): bool
    {
        return (bool) $this->es_servicio;
    }

    /** True if the product tracks a multi-unit presentation (box, bag, etc.) */
    public function tienePresentacion(): bool
    {
        return (bool) $this->maneja_presentacion
            && $this->cantidad_presentacion > 0
            && $this->tipo_presentacion !== null;
    }

    /**
     * Returns visual breakdown of stock_actual into presentations + remainder.
     * e.g. 138 units with 50/box → ['tipo'=>'Caja','cajas'=>2,'resto'=>38,'por_pres'=>50,'unidad_base'=>'Unidad']
     */
    public function stockVisual(?int $stockOverride = null): ?array
    {
        if (!$this->tienePresentacion()) return null;

        $q = (int) $this->cantidad_presentacion;
        $s = $stockOverride ?? (int) $this->stock_actual;

        return [
            'tipo'        => $this->tipo_presentacion,
            'cajas'       => intdiv($s, $q),
            'resto'       => $s % $q,
            'por_pres'    => $q,
            'unidad_base' => $this->unidad_base ?: 'unidad',
        ];
    }

    /**
     * Human-readable visual of an arbitrary real-unit quantity.
     * e.g. 138 with Caja/50/Unidad → "2 Caja(s) + 38 Unidad(s)"
     */
    public function cantidadVisual(int $cantidadReal): string
    {
        if (!$this->tienePresentacion()) return (string) $cantidadReal;

        $q     = (int) $this->cantidad_presentacion;
        $cajas = intdiv($cantidadReal, $q);
        $resto = $cantidadReal % $q;
        $tipo  = $this->tipo_presentacion;
        $base  = $this->unidad_base ?: 'unidad';

        if ($cajas > 0 && $resto > 0) return "{$cajas} {$tipo} + {$resto} {$base}";
        if ($cajas > 0)               return "{$cajas} {$tipo}";
        return "{$resto} {$base}";
    }

    /**
     * Convert a "number of presentations" into real units.
     * e.g. 3 boxes × 50 = 150 real units
     */
    public function presentacionesToReal(int $cantPresentaciones): int
    {
        if (!$this->tienePresentacion()) return $cantPresentaciones;
        return $cantPresentaciones * (int) $this->cantidad_presentacion;
    }

    public function scopeSoloFisicos($query)
    {
        return $query->where('es_servicio', false);
    }

    public function scopeSoloServicios($query)
    {
        return $query->where('es_servicio', true);
    }

    public function estadoStock(): string
    {
        // Servicios no tienen stock físico — siempre neutral
        if ($this->es_servicio) {
            return 'servicio';
        }
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
