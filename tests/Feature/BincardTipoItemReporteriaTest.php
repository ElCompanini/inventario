<?php

namespace Tests\Feature;

use App\Models\ArriendoMovimiento;
use App\Models\Categoria;
use App\Models\Familia;
use App\Models\HistorialCambio;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ServicioEstado;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BincardTipoItemReporteriaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bincard_y_reporteria_respetan_un_item_por_tipo(): void
    {
        $user = new User();
        $user->forceFill([
            'name' => 'QA Reportes',
            'email' => 'qa-reportes-' . uniqid() . '@inventario.test',
            'password' => bcrypt('secret'),
            'rol' => 2,
            'activo' => 1,
        ])->save();

        $this->actingAs($user);

        $producto = $this->crearProductoTipo('producto', 'QA PRODUCTO');
        $servicio = $this->crearProductoTipo('servicio', 'QA SERVICIO');
        $mantencion = $this->crearProductoTipo('mantencion', 'QA MANTENCION');
        $arriendo = $this->crearProductoTipo('arriendo', 'QA ARRIENDO');

        HistorialCambio::create([
            'producto_id' => $producto->id,
            'nombre_producto' => $producto->nombre,
            'cantidad' => 5,
            'tipo' => 'entrada',
            'motivo' => 'Ingreso QA',
            'usuario_id' => $user->id,
            'aprobado_por' => $user->name,
            'stock_anterior' => 0,
            'stock_posterior' => 5,
            'origen_tipo' => 'entrada_manual',
        ]);

        ServicioEstado::create([
            'producto_id' => $servicio->id,
            'estado' => 'aprobado',
            'usuario_id' => $user->id,
            'documento_referencia' => 'DOC-SVC-QA',
            'observacion' => 'Servicio QA',
        ]);

        ServicioEstado::create([
            'producto_id' => $mantencion->id,
            'estado' => 'en_proceso',
            'usuario_id' => $user->id,
            'documento_referencia' => 'DOC-MANT-QA',
            'proveedor_nombre' => 'Proveedor Mantencion QA',
            'observacion' => 'Mantencion QA',
        ]);

        ArriendoMovimiento::create([
            'producto_id' => $arriendo->id,
            'proveedor_nombre' => 'Proveedor Arriendo QA',
            'estado_nuevo' => 'activo',
            'fecha_inicio' => now()->toDateString(),
            'condicion_termino' => 'sin_fecha',
            'monto_periodo' => 1000,
            'monto_total' => 1000,
            'responsable_id' => $user->id,
            'ejecutado_por' => $user->id,
            'documento_referencia' => 'DOC-ARR-QA',
            'observacion' => 'Arriendo QA',
        ]);

        $fisica = $this->get(route('admin.reportes.bincard', ['producto_id' => $producto->id, 'solo_ver' => 1]));
        $fisica->assertOk();
        $this->assertCount(1, $fisica->viewData('data')['filas']);
        $this->assertSame(5, $fisica->viewData('data')['total_entradas']);

        $operacional = $this->get(route('admin.reportes.bincard.servicio', ['producto_id' => $servicio->id, 'solo_ver' => 1]));
        $operacional->assertOk();
        $this->assertCount(1, $operacional->viewData('dataServicio')['filas']);
        $this->assertSame('DOC-SVC-QA', $operacional->viewData('dataServicio')['filas'][0]['documento_referencia']);

        $tecnica = $this->get(route('admin.reportes.bincard.mantencion', ['producto_id' => $mantencion->id, 'solo_ver' => 1]));
        $tecnica->assertOk();
        $this->assertCount(1, $tecnica->viewData('dataMantencion')['filas']);
        $this->assertSame('Proveedor Mantencion QA', $tecnica->viewData('dataMantencion')['filas'][0]['proveedor']);

        $contractual = $this->get(route('admin.reportes.bincard.arriendo', ['producto_id' => $arriendo->id, 'solo_ver' => 1]));
        $contractual->assertOk();
        $this->assertCount(1, $contractual->viewData('dataArriendo')['filas']);
        $this->assertSame('DOC-ARR-QA', $contractual->viewData('dataArriendo')['filas'][0]['documento_referencia']);
    }

    private function crearProductoTipo(string $tipo, string $nombre): Producto
    {
        $familia = Familia::create([
            'nombre' => $nombre . ' FAMILIA ' . uniqid(),
            'tipo_item' => $tipo,
            'tipo_catalogo' => $tipo === 'producto' ? 'bien' : 'servicio',
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'nombre' => $nombre . ' CATEGORIA ' . uniqid(),
            'familia_id' => $familia->id,
            'tipo_item' => $tipo,
            'activo' => true,
        ]);

        $marcaId = null;
        if ($tipo === 'producto') {
            $marca = Marca::create([
                'nombre' => $nombre . ' MARCA ' . uniqid(),
                'categoria_id' => $categoria->id,
                'tipo_item' => 'producto',
                'activo' => true,
            ]);
            $marcaId = $marca->id;
        }

        return Producto::create([
            'nombre' => $nombre . ' ITEM ' . uniqid(),
            'categoria_id' => $categoria->id,
            'marca_id' => $marcaId,
            'tipo_item' => $tipo,
            'es_servicio' => $tipo === 'servicio',
            'stock_actual' => $tipo === 'producto' ? 5 : 0,
            'stock_minimo' => $tipo === 'producto' ? 1 : 0,
            'stock_critico' => $tipo === 'producto' ? 1 : 0,
            'activo' => true,
        ]);
    }
}
