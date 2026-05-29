<?php

namespace Tests\Unit;

use App\Http\Middleware\AdminMiddleware;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AdminMiddlewareTest extends TestCase
{
    public function test_admin_only_routes_are_mapped(): void
    {
        $middleware = new AdminMiddleware();
        $method = (new ReflectionClass($middleware))->getMethod('requiresAdmin');

        $this->assertTrue($method->invoke($middleware, 'admin.dashboard'));
        $this->assertTrue($method->invoke($middleware, 'admin.productos.carga.masiva'));
        $this->assertTrue($method->invoke($middleware, 'admin.sicd.recibir.directo'));
        $this->assertFalse($method->invoke($middleware, 'admin.sicd.index'));
    }

    public function test_granular_route_permissions_are_mapped(): void
    {
        $middleware = new AdminMiddleware();
        $method = (new ReflectionClass($middleware))->getMethod('permissionsForRoute');

        $this->assertSame(['sicd'], $method->invoke($middleware, 'admin.sicd.index'));
        $this->assertSame(['ordenes'], $method->invoke($middleware, 'admin.ordenes.index'));
        $this->assertSame(['stock'], $method->invoke($middleware, 'admin.productos.stock'));
        $this->assertSame(['solicitudes', 'aprobar_solicitudes'], $method->invoke($middleware, 'admin.solicitudes'));
    }
}
