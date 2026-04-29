<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroCosto extends Model
{
    protected $table    = 'centros_costo';
    protected $fillable = ['acronimo', 'nombre_completo'];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'centro_costo_id');
    }
}
