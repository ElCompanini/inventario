<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use SoftDeletes;

    protected $table = 'categorias';
    protected $fillable = ['nombre', 'familia_id', 'activo'];

    public function familia()
    {
        return $this->belongsTo(Familia::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class)->orderBy('nombre');
    }

    public function marcas()
    {
        return $this->hasMany(Marca::class)->orderBy('nombre');
    }

    /**
     * Categories available under SIN FAMILIA: all categories whose family
     * is a normal (non-special) family. Dynamically includes any new
     * families/categories added in the future without code changes.
     */
    public function scopeParaSinFamilia(Builder $query): Builder
    {
        return $query->whereHas('familia', fn($q) => $q->where('tipo', 'normal'));
    }
}
