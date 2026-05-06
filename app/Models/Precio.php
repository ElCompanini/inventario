<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Precio extends Model
{
    protected $table = 'precios';

    protected $fillable = [
        'producto_id',
        'familia_id',
        'categoria_id',
        'usuario_id',
        'precio_neto',
        'precio_total',
        'cantidad',
        'fuente',
        'origen_id',
        'origen_tipo',
        'notas',
    ];

    protected $casts = [
        'precio_neto'  => 'float',
        'precio_total' => 'float',
        'cantidad'     => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function familia()
    {
        return $this->belongsTo(Familia::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Registra un precio resolviendo familia/categoría desde el producto.
     */
    public static function registrar(
        Producto $producto,
        float $precioNeto,
        int $cantidad = 1,
        string $fuente = 'manual',
        ?int $origenId = null,
        ?string $origenTipo = null,
        ?float $precioTotal = null,
        ?string $notas = null,
        ?int $usuarioId = null,
    ): self {
        $categoriaId = $producto->categoria_id ?? null;
        $familiaId   = null;

        if ($categoriaId) {
            $cat = $producto->relationLoaded('categoria')
                ? $producto->categoria
                : Categoria::find($categoriaId);
            $familiaId = $cat?->familia_id;
        }

        return static::create([
            'producto_id'  => $producto->id,
            'familia_id'   => $familiaId,
            'categoria_id' => $categoriaId,
            'usuario_id'   => $usuarioId ?? auth()->id(),
            'precio_neto'  => $precioNeto,
            'precio_total' => $precioTotal ?? round($precioNeto * $cantidad, 2),
            'cantidad'     => $cantidad,
            'fuente'       => $fuente,
            'origen_id'    => $origenId,
            'origen_tipo'  => $origenTipo,
            'notas'        => $notas,
        ]);
    }
}
