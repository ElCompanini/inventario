<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Container;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'codigo_barras',
        'stock_actual',
        'stock_minimo',
        'stock_critico',
        'contenedor',
        'categoria_id',
        'stock_minimo_desde',
        'stock_critico_desde',
    ];

    protected $casts = [
        'stock_minimo_desde'  => 'datetime',
        'stock_critico_desde' => 'datetime',
    ];

    public function container()
    {
        return $this->belongsTo(Container::class, 'contenedor');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
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

    public function actualizarFechasStock(): void
    {
        $ahora = now();

        if ($this->stock_actual <= $this->stock_critico) {
            if (!$this->stock_critico_desde) {
                $this->stock_critico_desde = $ahora;
            }
            // Si sale de crítico, limpiar
        } else {
            $this->stock_critico_desde = null;
        }

        if ($this->stock_actual <= $this->stock_minimo && $this->stock_actual > $this->stock_critico) {
            if (!$this->stock_minimo_desde) {
                $this->stock_minimo_desde = $ahora;
            }
        } else {
            $this->stock_minimo_desde = null;
        }
    }
}
