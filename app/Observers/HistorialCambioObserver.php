<?php

namespace App\Observers;

use App\Events\EventoErpNotificable;
use App\Models\HistorialCambio;
use App\Models\Producto;

class HistorialCambioObserver
{
    public function created(HistorialCambio $movimiento): void
    {
        $tipo = $this->tipoCorreoParaMovimiento($movimiento);

        if ($tipo !== null) {
            EventoErpNotificable::dispatch(
                $tipo,
                (int) $movimiento->id,
                null,
                ['evento' => $tipo]
            );
        }

        $this->notificarEstadoStock($movimiento);
    }

    private function tipoCorreoParaMovimiento(HistorialCambio $movimiento): ?string
    {
        $origenTipo = (string) ($movimiento->origen_tipo ?? '');
        $origen = (string) ($movimiento->origen ?? '');

        if ($origenTipo === 'computador_armado' || $origen === 'computador_armado') {
            return 'armado_equipos';
        }

        if ($movimiento->tipo === 'devolucion') {
            return 'devolucion_productos';
        }

        if ($movimiento->tipo === 'entrada') {
            return 'ingreso_stock';
        }

        if ($movimiento->tipo === 'salida') {
            return 'retiro_productos';
        }

        return null;
    }

    private function notificarEstadoStock(HistorialCambio $movimiento): void
    {
        if (!$movimiento->producto_id) {
            return;
        }

        $producto = Producto::withoutGlobalScopes()
            ->whereKey($movimiento->producto_id)
            ->first();

        if (!$producto || !$producto->isProducto()) {
            return;
        }

        $estado = $producto->estadoStock();

        if ($estado === 'critico') {
            EventoErpNotificable::dispatch(
                'stock_critico',
                (int) $movimiento->id,
                null,
                ['evento' => 'stock_critico']
            );
            return;
        }

        if ($estado === 'minimo') {
            EventoErpNotificable::dispatch(
                'stock_minimo',
                (int) $movimiento->id,
                null,
                ['evento' => 'stock_minimo']
            );
        }
    }
}
