<?php

namespace Tests\Unit;

use App\Models\ArriendoMovimiento;
use PHPUnit\Framework\TestCase;

class ArriendoMovimientoTest extends TestCase
{
    public function test_flujo_de_arriendo_solo_permite_transiciones_logicas(): void
    {
        $this->assertSame(['activo', 'cancelado'], ArriendoMovimiento::flujoPermitido('pendiente'));
        $this->assertSame(['proximo_vencer', 'finalizado', 'renovado', 'cancelado'], ArriendoMovimiento::flujoPermitido('activo'));
        $this->assertSame(['finalizado', 'renovado', 'cancelado'], ArriendoMovimiento::flujoPermitido('proximo_vencer'));
        $this->assertSame(['activo'], ArriendoMovimiento::flujoPermitido('renovado'));
        $this->assertSame([], ArriendoMovimiento::flujoPermitido('finalizado'));
        $this->assertSame([], ArriendoMovimiento::flujoPermitido('cancelado'));
    }

    public function test_labels_de_arriendo_son_institucionales(): void
    {
        $this->assertSame('Pendiente', ArriendoMovimiento::label('pendiente'));
        $this->assertSame('Proximo a vencer', ArriendoMovimiento::label('proximo_vencer'));
        $this->assertSame('Finalizado', ArriendoMovimiento::label('finalizado'));
    }
}
