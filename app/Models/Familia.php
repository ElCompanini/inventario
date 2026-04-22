<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    protected $table = 'familias';
    protected $fillable = ['nombre', 'activo'];

    public function categorias()
    {
        return $this->hasMany(Categoria::class)->orderBy('nombre');
    }
}
