<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SicdExterno extends Model
{
    protected $connection = 'sicd_externa';
    protected $table      = 'solicitud_full';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    /**
     * Verifica si un código SICD existe en el sistema externo.
     */
    public static function existe(string $codigo): bool
    {
        return static::where('num_int_sol', $codigo)->exists();
    }

    /**
     * Devuelve los datos de una solicitud por código.
     */
    public static function buscar(string $codigo): ?object
    {
        return static::where('num_int_sol', $codigo)
            ->select('id', 'num_int_sol', 'centro_costo', 'estado', 'fecha_creacion')
            ->first();
    }
}
