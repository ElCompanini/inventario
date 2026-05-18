<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistorialCambio extends Model
{
    use SoftDeletes;

    protected $table = 'historial_cambios';

    protected $fillable = [
        'producto_id',
        'nombre_producto',
        'contenedor_id',
        'cantidad',
        'tipo',
        'motivo',
        'aprobado_por',
        'usuario_id',
        'origen',
        'origen_id',
        'orden_compra_id',
        'codigo_movimiento',
        'doc_origen',
        'doc_referencia',
        'stock_anterior',
        'stock_posterior',
        'usuario_ejecutor_id',
        'origen_tipo',
        'referencia_tipo',
        'referencia_id',
    ];

    protected static function booted(): void
    {
        static::created(function (self $mov) {
            $updates = [];

            if (empty($mov->codigo_movimiento)) {
                $updates['codigo_movimiento'] = 'MOV-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT);
            }

            if (empty($mov->doc_origen)) {
                $tipo = $mov->origen_tipo;
                $generated = match(true) {
                    $tipo === 'devolucion'
                        => 'DEV-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    in_array($tipo, ['ajuste', 'entrada_manual', 'merma'])
                        => 'AJU-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    $tipo === 'traslado'
                        => 'MOV-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    $tipo === 'retiro_directo'
                        => 'RET-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    // Legacy fallback (origen_tipo not set on old records)
                    $tipo === null && $mov->tipo === 'devolucion' && $mov->origen === 'solicitud' && $mov->origen_id
                        => 'DEV-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    $tipo === null && $mov->tipo === 'ajuste'
                        => 'AJU-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    $tipo === null && $mov->tipo === 'traslado'
                        => 'MOV-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    $tipo === null && in_array($mov->tipo, ['entrada', 'merma']) && empty($mov->origen)
                        => 'AJU-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    $tipo === null && $mov->tipo === 'salida'
                        => 'RET-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT),
                    default => null,
                };
                if ($generated !== null) {
                    $updates['doc_origen'] = $generated;
                }
            }

            // Auto-generate doc_referencia from referencia_tipo + referencia_id if not set by caller
            if (empty($mov->doc_referencia) && !empty($mov->referencia_tipo) && !empty($mov->referencia_id)) {
                $ref = match($mov->referencia_tipo) {
                    'solicitud' => 'SOL-' . str_pad($mov->referencia_id, 6, '0', STR_PAD_LEFT),
                    default     => null,
                };
                if ($ref !== null) {
                    $updates['doc_referencia'] = $ref;
                }
            }

            if (!empty($updates)) {
                \Illuminate\Support\Facades\DB::table('historial_cambios')
                    ->where('id', $mov->id)
                    ->update($updates);
            }
        });
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function container()
    {
        return $this->belongsTo(\App\Models\Container::class, 'contenedor_id')
                    ->withoutGlobalScope('con_cc');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function usuarioEjecutor()
    {
        return $this->belongsTo(User::class, 'usuario_ejecutor_id');
    }

    public function sicd()
    {
        return $this->belongsTo(\App\Models\Sicd::class, 'origen_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(\App\Models\OrdenCompra::class, 'orden_compra_id');
    }

    public function gastoMenor()
    {
        return $this->belongsTo(\App\Models\GastoMenor::class, 'origen_id');
    }
}
