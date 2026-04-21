<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $table = 'containers';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected static function booted(): void
    {
        static::addGlobalScope('activo', fn($q) => $q->where('activo', 1));
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'contenedor', 'id');
    }
}
