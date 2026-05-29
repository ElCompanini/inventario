<?php

namespace Tests\Unit;

use App\Http\Controllers\OrdenCompraController;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\Cache;
use ReflectionMethod;
use Tests\TestCase;

class OrdenCompraTipoAdquisicionTest extends TestCase
{
    private function clasificar(array $data, ?string $tipoBusqueda = 'oc', ?string $codigo = null): array
    {
        $controller = new OrdenCompraController();
        $method = new ReflectionMethod($controller, 'clasificarTipoAdquisicion');
        $method->setAccessible(true);

        return $method->invoke($controller, $data, $tipoBusqueda, $codigo);
    }

    private function aplicable(OrdenCompra $orden, array $clasificacion, bool $forzar = false): array
    {
        $controller = new OrdenCompraController();
        $method = new ReflectionMethod($controller, 'clasificacionAplicable');
        $method->setAccessible(true);

        return $method->invoke($controller, $orden, $clasificacion, $forzar);
    }

    public function test_oc_compra_agil_detectada_por_api_mp(): void
    {
        $resultado = $this->clasificar([
            'tipo' => 'Compra Agil',
            'codigo' => '1057510-123-AG26',
        ], 'oc', '1057510-123-AG26');

        $this->assertSame('compra_agil', $resultado['tipo_adquisicion']);
        $this->assertSame('api_mp', $resultado['tipo_adquisicion_origen']);
        $this->assertSame('alta', $resultado['tipo_adquisicion_confianza']);
    }

    public function test_oc_licitacion_detectada_por_api_mp(): void
    {
        $resultado = $this->clasificar([
            'tipo' => 'Licitacion publica',
            'codigo' => '1057510-4941-SE25',
        ], 'oc', '1057510-4941-SE25');

        $this->assertSame('licitacion', $resultado['tipo_adquisicion']);
        $this->assertSame('api_mp', $resultado['tipo_adquisicion_origen']);
        $this->assertSame('alta', $resultado['tipo_adquisicion_confianza']);
    }

    public function test_oc_sin_codigo_claro_queda_indeterminada(): void
    {
        $resultado = $this->clasificar([
            'tipo' => '',
            'codigo' => 'OC-SIN-FORMATO',
            'codigo_licitacion' => '',
            'total_neto' => null,
        ], 'oc', 'OC-SIN-FORMATO');

        $this->assertSame('indeterminado', $resultado['tipo_adquisicion']);
        $this->assertSame('manual', $resultado['tipo_adquisicion_origen']);
        $this->assertSame('baja', $resultado['tipo_adquisicion_confianza']);
    }

    public function test_oc_entre_treinta_y_cien_utm_usa_respaldo_utm_como_compra_agil(): void
    {
        Cache::put('utm_actual', 65000, now()->addHour());

        $resultado = $this->clasificar([
            'tipo' => '',
            'codigo' => '1057510-9999-SE26',
            'codigo_licitacion' => '',
            'total_neto' => 50 * 65000,
        ], 'oc', '1057510-9999-SE26');

        $this->assertSame('compra_agil', $resultado['tipo_adquisicion']);
        $this->assertSame('utm_estimado', $resultado['tipo_adquisicion_origen']);
        $this->assertSame('media', $resultado['tipo_adquisicion_confianza']);
    }

    public function test_oc_corregida_manualmente_no_se_sobrescribe_automaticamente(): void
    {
        $orden = new OrdenCompra([
            'tipo_adquisicion' => 'licitacion',
            'tipo_adquisicion_origen' => 'manual',
            'tipo_adquisicion_confianza' => 'alta',
        ]);

        $clasificacionAutomatica = [
            'tipo_adquisicion' => 'compra_agil',
            'tipo_adquisicion_origen' => 'api_mp',
            'tipo_adquisicion_confianza' => 'alta',
        ];

        $this->assertSame([], $this->aplicable($orden, $clasificacionAutomatica));
    }

    public function test_recalcular_tipo_fuerza_actualizacion_sobre_correccion_manual(): void
    {
        $orden = new OrdenCompra([
            'tipo_adquisicion' => 'licitacion',
            'tipo_adquisicion_origen' => 'manual',
            'tipo_adquisicion_confianza' => 'alta',
        ]);

        $clasificacionAutomatica = [
            'tipo_adquisicion' => 'compra_agil',
            'tipo_adquisicion_origen' => 'api_mp',
            'tipo_adquisicion_confianza' => 'alta',
        ];

        $this->assertSame($clasificacionAutomatica, $this->aplicable($orden, $clasificacionAutomatica, true));
    }

    public function test_oc_existente_con_tipo_manual_se_mantiene_sin_duplicar_clasificacion(): void
    {
        $ordenExistente = new OrdenCompra([
            'numero_oc' => '1057510-4941-SE25',
            'tipo_adquisicion' => 'licitacion',
            'tipo_adquisicion_origen' => 'manual',
            'tipo_adquisicion_confianza' => 'alta',
        ]);

        $nuevaEvaluacion = $this->clasificar([
            'tipo' => 'Compra Agil',
            'codigo' => '1057510-4941-SE25',
        ], 'oc', $ordenExistente->numero_oc);

        $this->assertSame([], $this->aplicable($ordenExistente, $nuevaEvaluacion));
        $this->assertSame('licitacion', $ordenExistente->tipo_adquisicion);
        $this->assertSame('manual', $ordenExistente->tipo_adquisicion_origen);
    }
}
