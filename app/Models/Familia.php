<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    protected $table = 'familias';
    protected $fillable = ['nombre', 'activo', 'centro_costo_id'];

    public function centroCosto()
    {
        return $this->belongsTo(\App\Models\CentroCosto::class, 'centro_costo_id');
    }

    public function categorias()
    {
        return $this->hasMany(Categoria::class)->orderBy('nombre');
    }
}
