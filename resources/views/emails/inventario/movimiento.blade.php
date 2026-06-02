@php
    $fecha = $contexto['fecha'] ?? now();
    $fechaTexto = $fecha instanceof \Carbon\CarbonInterface
        ? $fecha->format('d/m/Y H:i')
        : (string) $fecha;

    $tipo = $tipoCorreo ?? '';

    // ── Color y estilo por tipo ──────────────────────────────────────
    $config = match($tipo) {
        'solicitud_productos' => [
            'color'   => '#1d4ed8',
            'light'   => '#dbeafe',
            'icon'    => '📋',
            'desc'    => 'Un usuario ha solicitado un retiro de stock. Revise y apruebe o rechace la solicitud desde el sistema.',
            'badge'   => 'SOLICITUD PENDIENTE',
        ],
        'retiro_productos' => [
            'color'   => '#b45309',
            'light'   => '#fef3c7',
            'icon'    => '📦',
            'desc'    => 'Se realizó un retiro de stock. El inventario ha sido descontado.',
            'badge'   => 'SALIDA DE STOCK',
        ],
        'devolucion_productos' => [
            'color'   => '#15803d',
            'light'   => '#dcfce7',
            'icon'    => '↩️',
            'desc'    => 'Se registró una devolución de productos. El stock ha sido reingresado al inventario.',
            'badge'   => 'DEVOLUCIÓN',
        ],
        'armado_equipos' => [
            'color'   => '#6d28d9',
            'light'   => '#ede9fe',
            'icon'    => '🖥️',
            'desc'    => 'Se registró un movimiento relacionado al armado de equipos. Los componentes fueron descontados del inventario.',
            'badge'   => 'ARMADO EQUIPOS',
        ],
        'ingreso_stock' => [
            'color'   => '#0e7490',
            'light'   => '#cffafe',
            'icon'    => '⬆️',
            'desc'    => 'Se registró una entrada de stock. El inventario ha sido actualizado.',
            'badge'   => 'ENTRADA DE STOCK',
        ],
        'stock_minimo' => [
            'color'   => '#92400e',
            'light'   => '#fef3c7',
            'icon'    => '⚠️',
            'desc'    => 'El stock de este producto ha alcanzado el nivel mínimo. Se recomienda gestionar una reposición.',
            'badge'   => 'STOCK MÍNIMO',
        ],
        'stock_critico' => [
            'color'   => '#991b1b',
            'light'   => '#fee2e2',
            'icon'    => '🚨',
            'desc'    => 'ALERTA: El stock de este producto ha alcanzado nivel crítico. Se requiere acción inmediata.',
            'badge'   => 'STOCK CRÍTICO',
        ],
        'carga_masiva' => [
            'color'   => '#0e7490',
            'light'   => '#cffafe',
            'icon'    => '📥',
            'desc'    => 'Se completó una carga masiva de inventario. A continuación el detalle de productos ingresados.',
            'badge'   => 'CARGA MASIVA',
        ],
        'recepcion_oc' => [
            'color'   => '#047857',
            'light'   => '#d1fae5',
            'icon'    => '✅',
            'desc'    => 'Se completó la recepción de una Orden de Compra. A continuación el detalle de productos ingresados al inventario.',
            'badge'   => 'RECEPCIÓN OC',
        ],
        default => [
            'color'   => '#0f172a',
            'light'   => '#f1f5f9',
            'icon'    => '📊',
            'desc'    => 'Se registró un movimiento en el inventario.',
            'badge'   => 'MOVIMIENTO ERP',
        ],
    };

    $esAlertaStock  = in_array($tipo, ['stock_minimo', 'stock_critico']);
    $esSolicitud    = $tipo === 'solicitud_productos';
    $esArmado       = $tipo === 'armado_equipos';
    $esCargaMasiva  = $tipo === 'carga_masiva';
    $esRecepcionOc  = $tipo === 'recepcion_oc';
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
            <table role="presentation" width="640" cellpadding="0" cellspacing="0"
                   style="width:640px; max-width:94%; background:#ffffff; border:1px solid #d1d5db; border-radius:6px; overflow:hidden;">

                {{-- ── Header ── --}}
                <tr>
                    <td style="padding:20px 24px; background:{{ $config['color'] }}; color:#ffffff;">
                        <div style="font-size:11px; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,.7); margin-bottom:4px;">
                            Sistema Inventario &nbsp;·&nbsp; {{ $config['badge'] }}
                        </div>
                        <div style="font-size:24px; font-weight:700; margin-top:4px;">
                            {{ $config['icon'] }}&nbsp; {{ $contexto['tipo_label'] ?? 'Movimiento ERP' }}
                        </div>
                    </td>
                </tr>

                {{-- ── Descripción ── --}}
                <tr>
                    <td style="padding:18px 24px 0;">
                        <p style="font-size:14px; line-height:1.6; margin:0; color:#374151;">
                            {{ $config['desc'] }}
                        </p>
                    </td>
                </tr>

                {{-- ── Alerta destacada para stock crítico/mínimo ── --}}
                @if($esAlertaStock)
                <tr>
                    <td style="padding:14px 24px 0;">
                        <div style="background:{{ $config['light'] }}; border-left:4px solid {{ $config['color'] }}; border-radius:4px; padding:12px 16px;">
                            <div style="font-size:13px; font-weight:700; color:{{ $config['color'] }}; margin-bottom:4px;">
                                {{ $config['icon'] }} Producto afectado: {{ $contexto['producto'] ?? 'No informado' }}
                            </div>
                            <div style="font-size:13px; color:#374151;">
                                Stock actual: <strong>{{ $contexto['stock_posterior'] ?? $contexto['stock_anterior'] ?? 'N/D' }}</strong>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif

                {{-- ── Detalle ── --}}
                <tr>
                    <td style="padding:18px 24px 22px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="border-collapse:collapse; font-size:13px; border:1px solid #e5e7eb; border-radius:4px; overflow:hidden;">

                            {{-- Fecha --}}
                            <tr style="background:#f9fafb;">
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; width:34%; font-weight:600;">Fecha</td>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb;">{{ $fechaTexto }}</td>
                            </tr>

                            {{-- Centro de costo --}}
                            <tr>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-weight:600;">Centro de costo</td>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb;">{{ $contexto['centro_costo'] ?? 'No informado' }}</td>
                            </tr>

                            {{-- Número OC (solo para recepcion_oc) --}}
                            @if($esRecepcionOc && !empty($contexto['numero_oc']))
                            <tr style="background:#f9fafb;">
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-weight:600;">Orden de Compra</td>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; font-family:monospace; font-weight:700; color:#047857;">{{ $contexto['numero_oc'] }}</td>
                            </tr>
                            @endif

                            {{-- Producto (solo si no es alerta ni recepcion_oc, ya se muestra en tabla) --}}
                            @if(!$esAlertaStock && !$esRecepcionOc)
                            <tr style="background:#f9fafb;">
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-weight:600;">Producto</td>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; font-weight:600;">{{ $contexto['producto'] ?? 'No informado' }}</td>
                            </tr>
                            @endif

                            {{-- Cantidad (no para recepcion_oc, se detalla por producto en la tabla) --}}
                            @if(!$esRecepcionOc)
                            <tr @if(!$esAlertaStock) style="background:#f9fafb;" @endif>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-weight:600;">Cantidad</td>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb;">{{ $contexto['cantidad'] ?? 'No informado' }}</td>
                            </tr>
                            @endif

                            {{-- Container (no para alertas de stock ni recepcion_oc —el detalle va por producto en la tabla) --}}
                            @if(!$esAlertaStock && !$esRecepcionOc)
                            <tr>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-weight:600;">Container</td>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb;">{{ $contexto['container'] ?? 'No informado' }}</td>
                            </tr>
                            @endif

                            {{-- Usuario (no para alertas de stock) --}}
                            @if(!$esAlertaStock)
                            <tr style="background:#f9fafb;">
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-weight:600;">Usuario</td>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb;">{{ $contexto['usuario'] ?? 'No informado' }}</td>
                            </tr>
                            @endif

                            {{-- Motivo (solo retiro/solicitud/devolución) --}}
                            @if(in_array($tipo, ['retiro_productos', 'solicitud_productos', 'devolucion_productos']) && !empty($contexto['motivo']))
                            <tr>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280; font-weight:600;">Motivo</td>
                                <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb;">{{ $contexto['motivo'] }}</td>
                            </tr>
                            @endif

                            {{-- Documento --}}
                            @if(!empty($contexto['documento']))
                            <tr @if($esAlertaStock) @else style="background:#f9fafb;" @endif>
                                <td style="padding:9px 12px; {{ !empty($contexto['referencia']) || ($contexto['stock_anterior'] !== null) ? 'border-bottom:1px solid #e5e7eb;' : '' }} color:#6b7280; font-weight:600;">Documento</td>
                                <td style="padding:9px 12px; {{ !empty($contexto['referencia']) || ($contexto['stock_anterior'] !== null) ? 'border-bottom:1px solid #e5e7eb;' : '' }} font-family:monospace; font-size:13px;">{{ $contexto['documento'] }}</td>
                            </tr>
                            @endif

                            {{-- Referencia --}}
                            @if(!empty($contexto['referencia']))
                            <tr>
                                <td style="padding:9px 12px; {{ ($contexto['stock_anterior'] !== null || $contexto['stock_posterior'] !== null) ? 'border-bottom:1px solid #e5e7eb;' : '' }} color:#6b7280; font-weight:600;">Referencia</td>
                                <td style="padding:9px 12px; {{ ($contexto['stock_anterior'] !== null || $contexto['stock_posterior'] !== null) ? 'border-bottom:1px solid #e5e7eb;' : '' }}">{{ $contexto['referencia'] }}</td>
                            </tr>
                            @endif

                            {{-- Stock (no para solicitudes pendientes, sí para el resto) --}}
                            @if(!$esSolicitud && ($contexto['stock_anterior'] !== null || $contexto['stock_posterior'] !== null))
                            <tr style="background:{{ $config['light'] }};">
                                <td style="padding:9px 12px; color:{{ $config['color'] }}; font-weight:700;">Stock</td>
                                <td style="padding:9px 12px; font-weight:700; color:{{ $config['color'] }};">
                                    {{ $contexto['stock_anterior'] ?? '-' }} &rarr; {{ $contexto['stock_posterior'] ?? '-' }}
                                </td>
                            </tr>
                            @endif

                        </table>

                        {{-- Mensaje especial por tipo --}}
                        @if($esSolicitud)
                        <div style="margin-top:16px; background:#dbeafe; border:1px solid #93c5fd; border-radius:4px; padding:12px 16px; font-size:13px; color:#1e40af;">
                            ℹ️ Esta solicitud está <strong>pendiente de aprobación</strong>. El stock no ha sido modificado aún.
                        </div>
                        @elseif($tipo === 'stock_critico')
                        <div style="margin-top:16px; background:#fee2e2; border:1px solid #fca5a5; border-radius:4px; padding:12px 16px; font-size:13px; color:#991b1b; font-weight:700;">
                            🚨 Se requiere reposición inmediata. El stock se encuentra en nivel crítico.
                        </div>
                        @elseif($tipo === 'stock_minimo')
                        <div style="margin-top:16px; background:#fef3c7; border:1px solid #fcd34d; border-radius:4px; padding:12px 16px; font-size:13px; color:#92400e;">
                            ⚠️ Se recomienda gestionar una orden de reposición a la brevedad.
                        </div>
                        @endif

                        {{-- Tabla de productos para carga masiva --}}
                        @if($esCargaMasiva && !empty($contexto['productos']))
                        <div style="margin-top:18px;">
                            <div style="font-size:12px; font-weight:700; color:#0e7490; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">
                                {{ $contexto['total'] ?? count($contexto['productos']) }} producto(s) ingresado(s)
                                @if(!empty($contexto['codigo_sicd'])) · SICD {{ $contexto['codigo_sicd'] }} @endif
                            </div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse; font-size:12px; border:1px solid #e5e7eb;">
                                <thead>
                                    <tr style="background:#0e7490;">
                                        <th style="padding:7px 10px; text-align:left; color:#fff; font-weight:700;">Producto</th>
                                        <th style="padding:7px 10px; text-align:center; color:#fff; font-weight:700; width:80px;">Cantidad</th>
                                        <th style="padding:7px 10px; text-align:center; color:#fff; font-weight:700; width:100px;">Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contexto['productos'] as $i => $prod)
                                    <tr style="background:{{ $i % 2 === 0 ? '#f9fafb' : '#ffffff' }};">
                                        <td style="padding:7px 10px; border-top:1px solid #e5e7eb; color:#111827;">{{ $prod['nombre'] }}</td>
                                        <td style="padding:7px 10px; border-top:1px solid #e5e7eb; text-align:center; color:#0e7490; font-weight:700;">+{{ $prod['cantidad'] }}</td>
                                        <td style="padding:7px 10px; border-top:1px solid #e5e7eb; text-align:center; color:#6b7280; font-size:11px;">{{ $prod['stock'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Tabla de productos para recepción OC --}}
                        @if($esRecepcionOc && !empty($contexto['productos']))
                        <div style="margin-top:18px;">
                            <div style="font-size:12px; font-weight:700; color:#047857; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">
                                {{ $contexto['total'] ?? count($contexto['productos']) }} producto(s) recibido(s)
                                @if(!empty($contexto['numero_oc'])) · OC {{ $contexto['numero_oc'] }} @endif
                            </div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse; font-size:12px; border:1px solid #e5e7eb;">
                                <thead>
                                    <tr style="background:#047857;">
                                        <th style="padding:7px 10px; text-align:left; color:#fff; font-weight:700;">Producto</th>
                                        <th style="padding:7px 10px; text-align:left; color:#fff; font-weight:700; width:120px;">Lugar</th>
                                        <th style="padding:7px 10px; text-align:center; color:#fff; font-weight:700; width:70px;">Cant.</th>
                                        <th style="padding:7px 10px; text-align:center; color:#fff; font-weight:700; width:90px;">Stock actual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contexto['productos'] as $i => $prod)
                                    <tr style="background:{{ $i % 2 === 0 ? '#f9fafb' : '#ffffff' }};">
                                        <td style="padding:7px 10px; border-top:1px solid #e5e7eb; color:#111827;">{{ $prod['nombre'] }}</td>
                                        <td style="padding:7px 10px; border-top:1px solid #e5e7eb; color:#6b7280; font-size:11px;">{{ $prod['container'] ?? '—' }}</td>
                                        <td style="padding:7px 10px; border-top:1px solid #e5e7eb; text-align:center; color:#047857; font-weight:700;">+{{ $prod['cantidad'] }}</td>
                                        <td style="padding:7px 10px; border-top:1px solid #e5e7eb; text-align:center; color:#6b7280; font-size:11px;">{{ $prod['stock'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Tabla de ítems pendientes de revisión --}}
                        @if(!empty($contexto['pendientes']))
                        <div style="margin-top:14px;">
                            <div style="font-size:12px; font-weight:700; color:#92400e; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">
                                {{ count($contexto['pendientes']) }} ítem(s) pendiente(s) de revisión
                            </div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse; font-size:12px; border:1px solid #fde68a;">
                                <thead>
                                    <tr style="background:#d97706;">
                                        <th style="padding:7px 10px; text-align:left; color:#fff; font-weight:700;">Producto</th>
                                        <th style="padding:7px 10px; text-align:left; color:#fff; font-weight:700; width:160px;">Motivo</th>
                                        <th style="padding:7px 10px; text-align:left; color:#fff; font-weight:700; width:180px;">Notas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contexto['pendientes'] as $i => $pend)
                                    <tr style="background:{{ $i % 2 === 0 ? '#fffbeb' : '#fef3c7' }};">
                                        <td style="padding:7px 10px; border-top:1px solid #fde68a; color:#111827;">{{ $pend['nombre'] }}</td>
                                        <td style="padding:7px 10px; border-top:1px solid #fde68a; color:#92400e; font-weight:600;">{{ $pend['motivo'] }}</td>
                                        <td style="padding:7px 10px; border-top:1px solid #fde68a; color:#6b7280; font-size:11px;">{{ $pend['notas'] ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        @endif

                        <p style="font-size:11px; color:#94a3b8; line-height:1.5; margin:20px 0 0; border-top:1px solid #f1f5f9; padding-top:14px;">
                            Este correo fue generado automáticamente por el ERP de inventario. La operación de inventario no depende del envío de correo.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
