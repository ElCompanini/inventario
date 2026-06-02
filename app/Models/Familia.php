<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    protected $table = 'familias';
    protected $fillable = ['nombre', 'activo', 'centro_costo_id', 'protegido', 'tipo', 'tipo_catalogo', 'tipo_item', 'requiere_categoria', 'requiere_marca'];

    protected $casts = ['protegido' => 'boolean', 'activo' => 'boolean', 'requiere_categoria' => 'boolean', 'requiere_marca' => 'boolean'];

    protected static function booted(): void
    {
        static::deleting(function (self $f) {
            if ($f->protegido) {
                throw new \RuntimeException("El registro \"{$f->nombre}\" está protegido y no puede eliminarse.");
            }
        });

        static::saving(function (self $f) {
            if (!$f->tipo_item || !in_array($f->tipo_item, ['producto', 'servicio', 'mantencion', 'arriendo'], true)) {
                $f->tipo_item = $f->tipo === 'servicios' || $f->tipo_catalogo === 'servicio' ? 'servicio' : 'producto';
            }

            if ($f->protegido && $f->isDirty('activo') && !$f->activo) {
                $f->activo = true;
            }
        });
    }

    public function esSinFamilia(): bool
    {
        return $this->tipo === 'sin_familia';
    }

    public function esPartesYPiezas(): bool
    {
        return $this->tipo === 'partes_piezas';
    }

    public function esServicios(): bool
    {
        return $this->tipo === 'servicios';
    }

    public function esBien(): bool
    {
        return $this->tipo_catalogo === 'bien';
    }

    public function esServicioCatalogo(): bool
    {
        return $this->tipo_catalogo === 'servicio';
    }

    public function requiereCategoria(): bool
    {
        return (bool) $this->requiere_categoria;
    }

    public function requiereMarca(): bool
    {
        return (bool) $this->requiere_marca;
    }

    public static function idSinFamilia(): int
    {
        return (int) static::where('tipo', 'sin_familia')->value('id');
    }

    public static function idPartesYPiezas(): int
    {
        return (int) static::where('tipo', 'partes_piezas')->value('id');
    }

    public static function idServicios(): int
    {
        return (int) static::where('tipo', 'servicios')->value('id');
    }

    public function centroCosto()
    {
        return $this->belongsTo(\App\Models\CentroCosto::class, 'centro_costo_id');
    }

    public function categorias()
    {
        return $this->hasMany(Categoria::class)->orderBy('nombre');
    }

    public function productosDirectos()
    {
        return $this->hasMany(\App\Models\Producto::class)->whereNull('categoria_id');
    }

    public function scopeTipoItem($query, string $tipo)
    {
        return $query->where('tipo_item', $tipo);
    }
}
