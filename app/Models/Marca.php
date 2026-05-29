<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Marca extends Model
{
    use SoftDeletes;

    protected $table = 'marcas';

    protected $fillable = ['nombre', 'categoria_id', 'tipo_item', 'activo', 'protegido'];

    protected $casts = ['activo' => 'boolean', 'protegido' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            $m->nombre = strtoupper(trim($m->nombre));
            if (!$m->tipo_item && $m->categoria_id) {
                $m->tipo_item = $m->categoria?->tipo_item ?? 'producto';
            }
            if ($m->protegido && $m->isDirty('activo') && !$m->activo) {
                $m->activo = true;
            }
        });

        static::deleting(function (self $m) {
            if ($m->protegido) {
                throw new \RuntimeException("El registro \"{$m->nombre}\" está protegido y no puede eliminarse.");
            }
        });
    }

    public static function idSinMarca(): int
    {
        return (int) static::withTrashed()->where('nombre', 'SIN MARCA')->value('id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true);
    }
}
