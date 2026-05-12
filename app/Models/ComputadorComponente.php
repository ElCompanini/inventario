<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComputadorComponente extends Model
{
    use SoftDeletes;

    protected $table = 'computador_componentes';

    protected $fillable = [
        'computador_id',
        'producto_id',
        'categoria_id',
        'tipo_componente',
        'cantidad',
        'serial',
        'activo',
        'fecha_instalacion',
        'usuario_instalacion_id',
        'fecha_retiro',
        'usuario_retiro_id',
        'motivo_retiro',
        'notas',
    ];

    protected $casts = [
        'activo'             => 'boolean',
        'cantidad'           => 'integer',
        'fecha_instalacion'  => 'datetime',
        'fecha_retiro'       => 'datetime',
    ];

    public function computador()
    {
        return $this->belongsTo(ComputadorArmado::class, 'computador_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function categoria()
    {
        return $this->belongsTo(\App\Models\Categoria::class);
    }

    public function usuarioInstalacion()
    {
        return $this->belongsTo(User::class, 'usuario_instalacion_id');
    }

    public function usuarioRetiro()
    {
        return $this->belongsTo(User::class, 'usuario_retiro_id');
    }
}
