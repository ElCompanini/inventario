<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            [
                'nombre'        => 'Tornillos M8 x 30mm',
                'descripcion'   => 'Tornillos de acero inoxidable M8 largo 30mm',
                'stock_actual'  => 50,
                'stock_minimo'  => 20,
                'stock_critico' => 10,
                'contenedor'    => 1,
            ],
            [
                'nombre'        => 'Aceite Hidráulico 46',
                'descripcion'   => 'Aceite hidráulico ISO VG 46, bidón 20L',
                'stock_actual'  => 50,
                'stock_minimo'  => 20,
                'stock_critico' => 10,
                'contenedor'    => 2,
            ],
            [
                'nombre'        => 'Filtro de Aire A1234',
                'descripcion'   => 'Filtro de aire compatible con compresor Atlas',
                'stock_actual'  => 50,
                'stock_minimo'  => 20,
                'stock_critico' => 10,
                'contenedor'    => 1,
            ],
            [
                'nombre'        => 'Guantes de Seguridad T-9',
                'descripcion'   => 'Guantes anticorte nivel 4, talla 9',
                'stock_actual'  => 50,
                'stock_minimo'  => 20,
                'stock_critico' => 10,
                'contenedor'    => 2,
            ],
            [
                'nombre'        => 'Rodamiento 6205-2RS',
                'descripcion'   => 'Rodamiento de bolas sellado 25x52x15mm',
                'stock_actual'  => 50,
                'stock_minimo'  => 20,
                'stock_critico' => 10,
                'contenedor'    => 1,
            ],
            [
                'nombre'        => 'Cable Eléctrico 2.5mm²',
                'descripcion'   => 'Cable flexible 2.5mm² rollo 100m, color negro',
                'stock_actual'  => 50,
                'stock_minimo'  => 20,
                'stock_critico' => 10,
                'contenedor'    => 2,
            ],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
