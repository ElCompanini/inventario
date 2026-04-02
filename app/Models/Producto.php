<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Container;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'stock_actual',
        'stock_minimo',
        'stock_critico',
        'contenedor',
    ];

    public function container()
    {
        return $this->belongsTo(Container::class, 'contenedor');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }

    public function historialCambios()
    {
        return $this->hasMany(HistorialCambio::class);
    }

    public function estadoStock(): string
    {
        if ($this->stock_actual <= $this->stock_critico) {
            return 'critico';
        }
        if ($this->stock_actual <= $this->stock_minimo) {
            return 'minimo';
        }
        return 'normal';
    }
}
