<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionMovimientoInventarioMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $tipoCorreo,
        public array $contexto
    ) {
    }

    public function envelope(): Envelope
    {
        $producto = $this->contexto['producto'] ?? 'inventario';

        return new Envelope(
            subject: '[Inventario] ' . ($this->contexto['tipo_label'] ?? 'Movimiento ERP') . ' - ' . $producto
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inventario.movimiento',
            with: [
                'tipoCorreo' => $this->tipoCorreo,
                'contexto' => $this->contexto,
            ]
        );
    }
}
