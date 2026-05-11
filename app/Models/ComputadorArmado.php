<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComputadorArmado extends Model
{
    use SoftDeletes;

    protected $table = 'computadores_armados';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'estado',
        'ubicacion',
        'usuario_asignado',
        'usuario_id',
        'notas',
    ];

    /** Estados disponibles */
    const ESTADOS = [
        'en_armado' => 'En armado',
        'listo'     => 'Listo',
        'en_uso'    => 'En uso',
        'desarmado' => 'Desarmado',
    ];

    /** Tipos de componente disponibles */
    const TIPOS_COMPONENTE = [
        'placa_madre'      => 'Placa Madre',
        'procesador'       => 'Procesador',
        'ram'              => 'RAM',
        'gpu'              => 'GPU',
        'fuente_poder'     => 'Fuente de Poder',
        'ssd'              => 'SSD',
        'hdd'              => 'HDD',
        'gabinete'         => 'Gabinete',
        'ventilador'       => 'Ventilador',
        'disipador'        => 'Disipador',
        'tarjeta_pci'      => 'Tarjeta PCI',
        'cable_hdmi'       => 'Cable HDMI',
        'cable_displayport'=> 'Cable DisplayPort',
        'cable_vga'        => 'Cable VGA',
        'extensor_usb'     => 'Extensor USB',
        'periferico'       => 'Periférico',
        'otro'             => 'Otro',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** Todos los componentes (incluye historial) */
    public function componentes()
    {
        return $this->hasMany(ComputadorComponente::class, 'computador_id');
    }

    /** Solo componentes actualmente instalados */
    public function componentesActivos()
    {
        return $this->hasMany(ComputadorComponente::class, 'computador_id')
                    ->where('activo', true);
    }

    /** Valorización total (suma de precios de componentes activos) */
    public function valorizacionTotal(): float
    {
        return $this->componentesActivos->sum(function ($comp) {
            $precio = $comp->producto?->ultimoPrecio();
            return ($precio?->precio_neto ?? 0) * $comp->cantidad;
        });
    }

    /** Genera el siguiente código libre (PC-001, PC-002...) */
    public static function siguienteCodigo(): string
    {
        $ultimo = static::withTrashed()->orderByDesc('id')->first();
        if (!$ultimo) return 'PC-001';
        preg_match('/(\d+)$/', $ultimo->codigo, $m);
        $n = isset($m[1]) ? (int)$m[1] + 1 : 1;
        return 'PC-' . str_pad($n, 3, '0', STR_PAD_LEFT);
    }
}
