<?php

namespace App\Listeners;

use App\Events\EventoErpNotificable;
use App\Jobs\EnviarNotificacionErpJob;

class ProgramarCorreoEventoErp
{
    public function handle(EventoErpNotificable $event): void
    {
        EnviarNotificacionErpJob::dispatch(
            $event->tipoCorreo,
            $event->historialCambioId,
            $event->solicitudId,
            $event->metadata
        )->afterCommit();
    }
}
