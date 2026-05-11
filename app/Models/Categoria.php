<?php

namespace App\Models;

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
}
