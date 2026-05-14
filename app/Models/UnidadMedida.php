<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadMedida extends Model
{
    use SoftDeletes;
    protected $table = 'unidades_medida';

    protected $fillable = ['nombre', 'abreviacion', 'descripcion', 'factor_conversion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $u) {
            $u->nombre      = strtoupper(trim($u->nombre));
            $u->abreviacion = strtoupper(trim($u->abreviacion));
        });
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'unidad_medida_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
