<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Marca extends Model
{
    use SoftDeletes;

    protected $table = 'marcas';

    protected $fillable = ['nombre', 'categoria_id', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(fn(self $m) => $m->nombre = strtoupper(trim($m->nombre)));
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
