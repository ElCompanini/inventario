<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $fillable = ['nombre', 'familia_id', 'activo'];

    public function familia()
    {
        return $this->belongsTo(Familia::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class)->orderBy('descripcion');
    }
}
