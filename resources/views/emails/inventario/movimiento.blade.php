@php
    $fecha = $contexto['fecha'] ?? now();
    $fechaTexto = $fecha instanceof \Carbon\CarbonInterface
        ? $fecha->format('d/m/Y H:i')
        : (string) $fecha;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $contexto['tipo_label'] ?? 'Movimiento ERP' }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px; max-width:94%; background:#ffffff; border:1px solid #d1d5db;">
                    <tr>
                        <td style="padding:20px 24px; background:#0f172a; color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#93c5fd;">Sistema Inventario</div>
                            <div style="font-size:22px; font-weight:700; margin-top:6px;">{{ $contexto['tipo_label'] ?? 'Movimiento ERP' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="font-size:15px; line-height:1.5; margin:0 0 18px;">
                                Se registro un movimiento relevante en el inventario. Revise el detalle logistico para trazabilidad interna.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                                <tr>
                                    <td style="padding:10px; border:1px solid #e5e7eb; width:34%; color:#475569;">Fecha</td>
                                    <td style="padding:10px; border:1px solid #e5e7eb;">{{ $fechaTexto }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Centro de costo</td>
                                    <td style="padding:10px; border:1px solid #e5e7eb;">{{ $contexto['centro_costo'] ?? 'No informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Producto</td>
                                    <td style="padding:10px; border:1px solid #e5e7eb;">{{ $contexto['producto'] ?? 'No informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Cantidad</td>
                                    <td style="padding:10px; border:1px solid #e5e7eb;">{{ $contexto['cantidad'] ?? 'No informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Container</td>
                                    <td style="padding:10px; border:1px solid #e5e7eb;">{{ $contexto['container'] ?? 'No informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Usuario</td>
                                    <td style="padding:10px; border:1px solid #e5e7eb;">{{ $contexto['usuario'] ?? 'No informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Motivo</td>
                                    <td style="padding:10px; border:1px solid #e5e7eb;">{{ $contexto['motivo'] ?? 'No informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Documento</td>
                                    <td style="padding:10px; border:1px solid #e5e7eb;">{{ $contexto['documento'] ?? 'No informado' }}</td>
                                </tr>
                                @if(!empty($contexto['referencia']))
                                    <tr>
                                        <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Referencia</td>
                                        <td style="padding:10px; border:1px solid #e5e7eb;">{{ $contexto['referencia'] }}</td>
                                    </tr>
                                @endif
                                @if($contexto['stock_anterior'] !== null || $contexto['stock_posterior'] !== null)
                                    <tr>
                                        <td style="padding:10px; border:1px solid #e5e7eb; color:#475569;">Stock</td>
                                        <td style="padding:10px; border:1px solid #e5e7eb;">
                                            {{ $contexto['stock_anterior'] ?? '-' }} &rarr; {{ $contexto['stock_posterior'] ?? '-' }}
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <p style="font-size:12px; color:#64748b; line-height:1.5; margin:20px 0 0;">
                                Este correo fue generado automaticamente por el ERP de inventario. La operacion de inventario no depende del envio de correo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
