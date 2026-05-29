<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserPermissionTest extends TestCase
{
    public function test_admin_without_explicit_permissions_has_full_access(): void
    {
        $user = new User([
            'rol' => 1,
            'permisos' => null,
        ]);

        $this->assertTrue($user->esAdmin());
        $this->assertTrue($user->tienePermiso('usuarios'));
        $this->assertTrue($user->tieneAlgunPermiso());
    }

    public function test_user_with_granular_permission_only_has_that_permission(): void
    {
        $user = new User([
            'rol' => 0,
            'permisos' => ['sicd'],
        ]);

        $this->assertFalse($user->esAdmin());
        $this->assertTrue($user->tienePermiso('sicd'));
        $this->assertFalse($user->tienePermiso('usuarios'));
        $this->assertTrue($user->tieneAlgunPermiso());
    }

    public function test_role_two_is_super_administrator(): void
    {
        $user = new User(['rol' => 2]);

        $this->assertTrue($user->esDev());
        $this->assertTrue($user->esSuperAdministrador());
        $this->assertTrue($user->tienePermiso('stock'));
    }
}
