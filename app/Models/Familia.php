<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    protected $table = 'familias';
    protected $fillable = ['nombre', 'activo', 'centro_costo_id', 'protegido', 'tipo', 'tipo_catalogo'];

    protected $casts = ['protegido' => 'boolean', 'activo' => 'boolean'];

    protected static function booted(): void
    {
        static::deleting(function (self $f) {
            if ($f->protegido) {
                throw new \RuntimeException("El registro \"{$f->nombre}\" está protegido y no puede eliminarse.");
            }
        });

        static::saving(function (self $f) {
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

    public static function idSinFamilia(): int
    {
        return (int) static::where('tipo', 'sin_familia')->value('id');
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
}
