<?php

namespace Tests\Unit;

use App\Models\Producto;
use PHPUnit\Framework\TestCase;

class ProductoTipoItemTest extends TestCase
{
    public function test_producto_helpers_distinguen_producto_servicio_mantencion_y_arriendo(): void
    {
        $producto = new Producto(['tipo_item' => 'producto', 'es_servicio' => false]);
        $servicio = new Producto(['tipo_item' => 'servicio', 'es_servicio' => true]);
        $mantencion = new Producto(['tipo_item' => 'mantencion', 'es_servicio' => false]);
        $arriendo = new Producto(['tipo_item' => 'arriendo', 'es_servicio' => false]);

        $this->assertTrue($producto->isProducto());
        $this->assertFalse($producto->isServicio());
        $this->assertFalse($producto->isMantencion());
        $this->assertFalse($producto->isArriendo());

        $this->assertTrue($servicio->isServicio());
        $this->assertFalse($servicio->isProducto());
        $this->assertFalse($servicio->isMantencion());
        $this->assertFalse($servicio->isArriendo());

        $this->assertTrue($mantencion->isMantencion());
        $this->assertFalse($mantencion->isProducto());
        $this->assertFalse($mantencion->isServicio());
        $this->assertFalse($mantencion->isArriendo());

        $this->assertTrue($arriendo->isArriendo());
        $this->assertFalse($arriendo->isProducto());
        $this->assertFalse($arriendo->isServicio());
        $this->assertFalse($arriendo->isMantencion());
    }

    public function test_estado_stock_no_expone_arriendos_como_stock_fisico(): void
    {
        $arriendo = new Producto([
            'tipo_item' => 'arriendo',
            'es_servicio' => false,
            'stock_actual' => 0,
            'stock_minimo' => 10,
            'stock_critico' => 5,
        ]);

        $this->assertSame('arriendo', $arriendo->estadoStock());
    }

    public function test_estado_stock_no_expone_mantenciones_como_stock_fisico(): void
    {
        $mantencion = new Producto([
            'tipo_item' => 'mantencion',
            'es_servicio' => false,
            'stock_actual' => 0,
            'stock_minimo' => 10,
            'stock_critico' => 5,
        ]);

        $this->assertSame('mantencion', $mantencion->estadoStock());
    }
}
