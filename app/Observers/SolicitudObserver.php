<?php

namespace App\Observers;

use App\Events\EventoErpNotificable;
use App\Models\Solicitud;

class SolicitudObserver
{
    public function created(Solicitud $solicitud): void
    {
        if ($solicitud->tipo !== 'salida') {
            return;
        }

        EventoErpNotificable::dispatch(
            'solicitud_productos',
            null,
            (int) $solicitud->id,
            ['evento' => 'solicitud_productos']
        );
    }
}
