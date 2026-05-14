@extends('layouts.app')

@section('title', 'OC ' . $oc->numero_oc)

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.ordenes.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a Órdenes</a>
    <div class="flex items-start justify-between mt-1">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 font-mono">{{ $oc->numero_oc }}</h1>
            <p class="text-xs text-gray-400 mt-0.5">
                Creada por <strong>{{ $oc->usuario->name }}</strong> el {{ $oc->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        @if($oc->estado === 'recibido')
            <span class="inline-flex items-center bg-green-100 text-green-700 text-sm font-semibold px-3 py-1.5 rounded-full">✓ Recibido</span>
        @else
            <span class="inline-flex items-center bg-yellow-100 text-yellow-700 text-sm font-semibold px-3 py-1.5 rounded-full">⏳ Pendiente</span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLUMNA IZQUIERDA: SICDs agrupados --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Archivo OC (opcional) --}}
        @if($oc->archivo_ruta)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-700">Documento OC</h2>
                    <a href="{{ route('admin.ordenes.descargar', $oc->id) }}"
                       class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        {{ $oc->archivo_nombre }}
                    </a>
                </div>
            </div>
        @endif

        {{-- SICDs y sus detalles --}}
        @foreach($oc->sicds as $sicd)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-700 font-mono">{{ $sicd->codigo_sicd }}</h2>
                        @if($sicd->descripcion)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $sicd->descripcion }}</p>
                        @endif
                    </div>
                    @if(auth()->user()->tienePermiso('sicd'))
                    <a href="{{ route('admin.sicd.show', $sicd->id) }}" class="text-xs text-indigo-600 hover:underline">Ver SICD →</a>
                    @endif
                </div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600 text-xs">Descripción</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600 text-xs">Unidad</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600 text-xs">Solicitado</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600 text-xs">Recibido</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-600 text-xs">Precio Neto</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-600 text-xs">Total Neto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sicd->detalles as $det)
                            @php
                                $pendiente     = $oc->estado === 'pendiente';
                                $diferente     = !$pendiente && $det->cantidad_recibida != $det->cantidad_solicitada;

                                // Estado de adjudicación de este detalle
                                $esDeEstaOc  = in_array($det->id, $idsEstaOc);
                                $otraOcNum   = $otraOcPorDetalle[$det->id] ?? null;
                                $adjOtraOc   = !$esDeEstaOc && $otraOcNum;
                                $sinAsignOc  = !$esDeEstaOc && !$otraOcNum;
                            @endphp
                            <tr class="{{ $adjOtraOc ? 'opacity-60' : ($diferente ? 'bg-orange-50' : 'hover:bg-gray-50') }}">
                                <td class="px-4 py-2 text-gray-800">
                                    {{-- Nombre del producto --}}
                                    @if($det->producto)
                                        {{ $det->producto->nombre }}
                                        @if($det->producto->nombre !== $det->nombre_producto_excel)
                                            <span class="block text-xs text-gray-400 mt-0.5">Excel: {{ $det->nombre_producto_excel }}</span>
                                        @endif
                                    @else
                                        {{ $det->nombre_producto_excel }}
                                        <span class="ml-1 text-xs text-amber-500">(sin enlace)</span>
                                    @endif

                                    {{-- Badge de adjudicación --}}
                                    @if($adjOtraOc)
                                        <span class="inline-flex items-center gap-1 mt-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 border border-purple-200">
                                            🔒 Adjudicada a OC: {{ $otraOcNum }}
                                        </span>
                                    @elseif($sinAsignOc && count($idsEstaOc) > 0)
                                        {{-- Solo mostrar si la OC ya tiene oc_detalles definidos (nuevo sistema) --}}
                                        <span class="inline-flex items-center gap-1 mt-1 text-xs text-gray-400 italic">
                                            Sin asignar a ninguna OC
                                        </span>
                                    @endif

                                    {{-- Motivo diferencia recepción --}}
                                    @if($diferente && $det->motivo_recepcion)
                                        <span class="block mt-1 text-xs font-semibold" style="color:#c2410c;">
                                            ⚠ Motivo: {{ $det->motivo_recepcion }}
                                        </span>
                                    @elseif($diferente)
                                        <span class="block mt-1 text-xs" style="color:#f97316;">Sin motivo registrado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center text-gray-600">{{ $det->unidad ?? '—' }}</td>
                                <td class="px-4 py-2 text-center font-semibold {{ $adjOtraOc ? 'text-gray-400' : 'text-gray-700' }}">
                                    {{ $det->cantidad_solicitada }}
                                </td>
                                <td class="px-4 py-2 text-center font-semibold">
                                    @if($adjOtraOc)
                                        <span class="text-xs text-purple-500 italic">En otra OC</span>
                                    @elseif($pendiente)
                                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> Pendiente
                                        </span>
                                    @elseif($diferente)
                                        <span class="text-orange-500">{{ $det->cantidad_recibida }}</span>
                                        <span class="text-xs font-normal text-orange-500">(solicitado: {{ $det->cantidad_solicitada }})</span>
                                    @else
                                        <span class="text-green-600">{{ $det->cantidad_recibida }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-gray-700">
                                    {{ $det->precio_neto ? '$' . number_format($det->precio_neto, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-800">
                                    @php
                                        $cant = $det->cantidad_recibida ?: $det->cantidad_solicitada;
                                        $totalNeto = $det->total_neto
                                            ?: ($det->precio_neto && $cant ? round($det->precio_neto * $cant) : null);
                                    @endphp
                                    {{ $totalNeto ? '$' . number_format($totalNeto, 0, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach


        {{-- Botón validar cuando aún no hay datos MP --}}
        @if(!$oc->api_validado_at && $oc->estado !== 'recibido')
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 flex items-center gap-3">
                @if($oc->api_error)
                <div class="flex-1 bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-xs text-red-700">
                    <p class="font-semibold mb-0.5">Error en última validación:</p>
                    <p>{{ $oc->api_error }}</p>
                </div>
                @else
                <p class="text-sm text-gray-400 flex-1">Esta OC aún no ha sido validada en Mercado Público.</p>
                @endif
                <div id="mp-show-error" style="display:none;"
                     class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-xs text-red-700"></div>
                <button id="btn-validar-mp-show" onclick="validarMPShow()"
                        style="padding:0.45rem 1rem; font-size:0.78rem; font-weight:600; color:#fff; background:{{ $oc->api_error ? '#dc2626' : '#4f46e5' }}; border:none; border-radius:0.5rem; cursor:pointer; white-space:nowrap;"
                        onmouseover="this.style.background='{{ $oc->api_error ? '#b91c1c' : '#4338ca' }}'"
                        onmouseout="this.style.background='{{ $oc->api_error ? '#dc2626' : '#4f46e5' }}'">
                    🔄 Validar en Mercado Público
                </button>
                <p id="mp-api-badge" class="text-xs text-gray-400 whitespace-nowrap">Verificando…</p>
            </div>
        </div>
        @endif

        {{-- ── Detalle de ítems Mercado Público ── --}}
        @if($oc->api_validado_at && !empty($oc->api_items))

        {{-- Info MP encima de la tabla --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-700 shrink-0">Mercado Público</h2>
                <div class="flex items-center gap-2 ml-auto">
                    <span class="text-[10px] text-gray-400 whitespace-nowrap">Validado {{ $oc->api_validado_at->format('d/m/Y H:i') }}</span>
                    <p id="mp-api-badge" class="text-xs text-gray-400 whitespace-nowrap">Verificando…</p>
                    @if($oc->estado !== 'recibido')
                    <div id="mp-show-error" style="display:none;"
                         class="bg-red-50 border border-red-200 rounded-lg px-2 py-1 text-xs text-red-700"></div>
                    <button id="btn-validar-mp-show" onclick="validarMPShow()"
                            style="padding:0.3rem 0.75rem; font-size:0.72rem; font-weight:600; color:#fff; background:#4f46e5; border:none; border-radius:0.4rem; cursor:pointer; white-space:nowrap;"
                            onmouseover="this.style.background='#4338ca'"
                            onmouseout="this.style.background='#4f46e5'">
                        🔄 Re-validar
                    </button>
                    @else
                    <div id="mp-show-error" style="display:none;"></div>
                    @endif
                </div>
            </div>
            <div class="px-5 py-3 grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
                <div>
                    <p class="text-gray-400 uppercase tracking-wide text-[10px] font-medium">Proveedor</p>
                    <p class="text-gray-800 font-semibold mt-0.5">{{ $oc->api_proveedor_nombre ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 uppercase tracking-wide text-[10px] font-medium">Estado</p>
                    <p class="text-gray-700 mt-0.5">{{ $oc->api_estado_mp ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 uppercase tracking-wide text-[10px] font-medium">RUT</p>
                    <p class="text-gray-700 mt-0.5">{{ $oc->api_proveedor_rut ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 uppercase tracking-wide text-[10px] font-medium">Total</p>
                    <p class="text-green-700 font-bold mt-0.5">{{ $oc->totalFormateado() }}</p>
                </div>
                <div>
                    <p class="text-gray-400 uppercase tracking-wide text-[10px] font-medium">Tipo</p>
                    <p class="text-gray-700 mt-0.5">{{ $oc->api_tipo ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 uppercase tracking-wide text-[10px] font-medium">Fecha envío</p>
                    <p class="text-gray-700 mt-0.5">{{ $oc->api_fecha_envio ? \Carbon\Carbon::parse($oc->api_fecha_envio)->format('d/m/Y H:i') : '—' }}</p>
                </div>
                @if($oc->api_licitacion_codigo)
                <div>
                    <p class="text-gray-400 uppercase tracking-wide text-[10px] font-medium">Licitación</p>
                    <p class="font-mono font-semibold text-indigo-600 mt-0.5">{{ $oc->api_licitacion_codigo }}</p>
                </div>
                @endif
                @if($oc->api_contacto)
                <div>
                    <p class="text-gray-400 uppercase tracking-wide text-[10px] font-medium">Contacto</p>
                    <p class="text-gray-700 mt-0.5">{{ $oc->api_contacto }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-700">Detalle de ítems — Mercado Público</h2>
                    @if($oc->api_nombre)
                        <p class="text-xs text-gray-400 italic mt-0.5">"{{ $oc->api_nombre }}"</p>
                    @endif
                </div>
                <span class="text-xs text-gray-400">{{ count($oc->api_items) }} ítem(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-semibold text-gray-600 whitespace-nowrap">Código</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-gray-600">Producto</th>
                            <th class="px-3 py-2.5 text-center font-semibold text-gray-600">Cantidad</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-gray-600">Esp. Comprador</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-gray-600">Esp. Proveedor</th>
                            <th class="px-3 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">P. Unit.</th>
                            <th class="px-3 py-2.5 text-right font-semibold text-gray-600">Dcto.</th>
                            <th class="px-3 py-2.5 text-right font-semibold text-gray-600">Cargos</th>
                            <th class="px-3 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($oc->api_items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2.5 font-mono text-gray-500 whitespace-nowrap">{{ $item['codigo'] ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-800">{{ $item['nombre'] ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-center font-semibold text-gray-800">{{ $item['cantidad'] ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $item['especificacion_comprador'] ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $item['especificacion_proveedor'] ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-right text-gray-700 whitespace-nowrap">{{ isset($item['precio_unitario']) ? '$ ' . number_format($item['precio_unitario'], 0, ',', '.') : '—' }}</td>
                            <td class="px-3 py-2.5 text-right text-gray-700">{{ number_format($item['descuento'] ?? 0, 2, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right text-gray-700">{{ number_format($item['cargo'] ?? 0, 2, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right font-semibold text-gray-800 whitespace-nowrap">{{ isset($item['total']) ? '$ ' . number_format($item['total'], 0, ',', '.') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @php
                $total     = $oc->api_total     ?? 0;
                $impuestos = $oc->api_impuestos ?? 0;
                $neto      = $total - $impuestos;
                $dcto      = collect($oc->api_items)->sum(fn($i) => $i['descuento'] ?? 0);
                $cargos    = collect($oc->api_items)->sum(fn($i) => $i['cargo'] ?? 0);
            @endphp
            <div class="flex justify-end px-5 py-4 border-t border-gray-100">
                <table class="text-xs border border-gray-200 rounded-lg overflow-hidden" style="min-width:220px;">
                    <tr class="border-b border-gray-100"><td class="px-4 py-1.5 text-gray-500">Neto</td><td class="px-4 py-1.5 text-right font-semibold text-gray-700">$ {{ number_format($neto, 0, ',', '.') }}</td></tr>
                    <tr class="border-b border-gray-100"><td class="px-4 py-1.5 text-gray-500">Dcto.</td><td class="px-4 py-1.5 text-right text-gray-700">$ {{ number_format($dcto, 0, ',', '.') }}</td></tr>
                    <tr class="border-b border-gray-100"><td class="px-4 py-1.5 text-gray-500">Cargos</td><td class="px-4 py-1.5 text-right text-gray-700">$ {{ number_format($cargos, 0, ',', '.') }}</td></tr>
                    <tr class="border-b border-gray-200 bg-gray-50"><td class="px-4 py-1.5 font-semibold text-gray-700">Subtotal</td><td class="px-4 py-1.5 text-right font-semibold text-gray-700">$ {{ number_format($neto, 0, ',', '.') }}</td></tr>
                    <tr class="border-b border-gray-100"><td class="px-4 py-1.5 text-gray-500">19% IVA</td><td class="px-4 py-1.5 text-right text-gray-700">$ {{ number_format($impuestos, 0, ',', '.') }}</td></tr>
                    <tr class="bg-gray-50"><td class="px-4 py-2 font-bold text-gray-800">Total</td><td class="px-4 py-2 text-right font-bold text-green-700">$ {{ number_format($total, 0, ',', '.') }}</td></tr>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- COLUMNA DERECHA: documentos + recepción --}}
    <div class="space-y-4">

        {{-- MÁS ÓRDENES DE COMPRA --}}
        @if($oc->sicds->contains('permite_mas_oc', true))
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                <span style="font-size:0.9rem;">➕</span>
                <h3 class="text-sm font-semibold text-gray-700">Más Órdenes de Compra</h3>
                <span style="font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:9999px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; margin-left:auto;">+ OC habilitado</span>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs text-gray-500 mb-3">
                    Los SICDs de esta OC permiten asociar más Órdenes de Compra.
                    Al crear la nueva OC, selecciona los mismos SICDs.
                </p>
                <a href="{{ route('admin.ordenes.create') }}"
                   style="display:block; width:100%; padding:0.5rem; font-size:0.8rem; font-weight:700; color:#fff; background:#4f46e5; border-radius:0.5rem; text-align:center; text-decoration:none; transition:background .15s;"
                   onmouseover="this.style.background='#4338ca'"
                   onmouseout="this.style.background='#4f46e5'">
                    Crear nueva OC asociada →
                </a>
                <div style="margin-top:0.75rem; border-top:1px solid #f3f4f6; padding-top:0.75rem;">
                    <p style="font-size:0.7rem; font-weight:600; color:#6b7280; margin-bottom:0.4rem;">SICDs disponibles:</p>
                    @foreach($oc->sicds->where('permite_mas_oc', true) as $sicdOc)
                        <div style="display:flex; align-items:center; gap:0.4rem; margin-bottom:0.3rem;">
                            <span style="font-size:0.72rem; font-weight:700; color:#4f46e5; font-family:monospace;">{{ $sicdOc->codigo_sicd }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- FACTURA --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-700">Factura</h2>
                    @if(!$oc->factura)
                    <p class="text-xs text-red-500">Obligatoria para recepción</p>
                    @endif
                </div>
                <span class="w-2.5 h-2.5 rounded-full {{ $oc->factura ? 'bg-green-500' : 'bg-gray-300' }}"></span>
            </div>
            <div class="px-5 py-4">
                @if($oc->factura)
                    <p class="text-sm text-gray-700 font-medium truncate mb-1">{{ $oc->factura->nombre_original }}</p>
                    <p class="text-xs text-gray-400 mb-3">
                        {{ $oc->factura->subido_por }} · {{ $oc->factura->created_at->format('d/m/Y H:i') }}
                    </p>
                    <a href="{{ route('admin.ordenes.factura.descargar', $oc->id) }}"
                       class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar factura
                    </a>
                @elseif($oc->estado !== 'recibido')
                    <form method="POST" action="{{ route('admin.ordenes.factura.subir', $oc->id) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" id="input-factura" name="factura" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                               onchange="document.getElementById('label-factura').textContent = this.files[0]?.name ?? 'Ningún archivo seleccionado'">
                        <label for="input-factura"
                               class="flex items-center justify-center gap-2 w-full py-2 text-xs font-semibold border-2 border-dashed border-blue-300 text-blue-600 rounded-lg cursor-pointer hover:bg-blue-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Seleccionar factura
                        </label>
                        <p id="label-factura" class="text-xs text-gray-400 text-center truncate">Ningún archivo seleccionado</p>
                        @error('factura')
                            <p class="text-red-500 text-xs">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="w-full py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                            Subir factura
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-400">No se subió factura.</p>
                @endif
            </div>
        </div>

        {{-- GUÍA DE DESPACHO --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-700">Guía de Despacho</h2>
                    <p class="text-xs text-gray-400">Opcional</p>
                </div>
                <span class="w-2.5 h-2.5 rounded-full {{ $oc->guia ? 'bg-green-500' : 'bg-gray-300' }}"></span>
            </div>
            <div class="px-5 py-4">
                @if($oc->guia)
                    <p class="text-sm text-gray-700 font-medium truncate mb-1">{{ $oc->guia->nombre_original }}</p>
                    <p class="text-xs text-gray-400 mb-3">
                        {{ $oc->guia->subido_por }} · {{ $oc->guia->created_at->format('d/m/Y H:i') }}
                    </p>
                    <a href="{{ route('admin.ordenes.guia.descargar', $oc->id) }}"
                       class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar guía
                    </a>
                @elseif(!$oc->guia)
                    <form method="POST" action="{{ route('admin.ordenes.guia.subir', $oc->id) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" id="input-guia" name="guia" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                               onchange="document.getElementById('label-guia').textContent = this.files[0]?.name ?? 'Ningún archivo seleccionado'">
                        <label for="input-guia"
                               class="flex items-center justify-center gap-2 w-full py-2 text-xs font-semibold border-2 border-dashed border-gray-300 text-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Seleccionar guía de despacho
                        </label>
                        <p id="label-guia" class="text-xs text-gray-400 text-center truncate">Ningún archivo seleccionado</p>
                        @error('guia')
                            <p class="text-red-500 text-xs">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                style="width:100%; padding:0.5rem; font-size:0.75rem; font-weight:600; color:#fff; background:#374151; border:none; border-radius:0.5rem; cursor:pointer;"
                                onmouseover="this.style.background='#1f2937'"
                                onmouseout="this.style.background='#374151'">
                            Subir guía de despacho
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-400">No se subió guía de despacho.</p>
                @endif
            </div>
        </div>

        {{-- BOTÓN RECEPCIÓN --}}
        @if($oc->estado !== 'recibido')
            @php
                $ocValidada       = $oc->estado === 'validado';
                $puedeRecepcionar = $ocValidada && (bool) $oc->factura;
            @endphp
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Registrar Recepción</h3>

                @if(!$ocValidada)
                    <div class="mb-3 text-xs text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                        <p class="font-semibold mb-0.5">OC no validada en Mercado Público</p>
                        <p>Valida la OC correctamente antes de registrar la recepción.</p>
                    </div>
                @elseif(!$oc->factura)
                    <div class="mb-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Sube la <strong>factura</strong> antes de registrar la recepción.
                    </div>
                @endif

                <a href="{{ $puedeRecepcionar ? route('admin.ordenes.recepcion', $oc->id) : '#' }}"
                   class="block w-full py-2 text-sm font-semibold rounded-lg text-center transition
                          {{ $puedeRecepcionar ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-100 text-gray-400 pointer-events-none cursor-not-allowed' }}">
                    Iniciar recepción →
                </a>
            </div>
        @endif

        @if($oc->estado === 'recibido')
            <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                <p class="text-sm font-semibold text-green-800 mb-1">OC procesada</p>
                <p class="text-xs text-green-700">
                    Recepción registrada el {{ $oc->procesado_at?->format('d/m/Y \a \l\a\s H:i') }}
                    por <strong>{{ $oc->procesado_por }}</strong>.
                </p>
            </div>
        @endif

    </div>

</div>

@push('scripts')
<script>
const RUTA_VALIDAR_MP = '{{ route("admin.ordenes.validar-mp", $oc->id) }}';
const RUTA_API_STATUS = '{{ route("admin.ordenes.api-status") }}';
const CSRF = '{{ csrf_token() }}';

let _mpValidando = false;

// Verificar estado API con delay para no colisionar con validaciones en vuelo
setTimeout(function () {
    if (_mpValidando) return;
    fetch(RUTA_API_STATUS, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('mp-api-badge');
            if (badge) {
                badge.textContent = data.activa ? '● API activa' : '● API inactiva';
                badge.style.color = data.activa ? '#15803d' : '#dc2626';
            }
        })
        .catch(() => {});
}, 3000);

function validarMPShow() {
    if (_mpValidando) return;
    _mpValidando = true;

    const btn    = document.getElementById('btn-validar-mp-show');
    const errDiv = document.getElementById('mp-show-error');
    errDiv.style.display = 'none';
    btn.disabled    = true;
    btn.textContent = 'Validando…';

    fetch(RUTA_VALIDAR_MP, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (ok && data.ok) {
            window.location.reload();
        } else {
            errDiv.style.display = 'block';
            errDiv.textContent   = data.mensaje || 'Error al validar.';
            btn.disabled    = false;
            btn.textContent = 'Reintentar validación';
            _mpValidando    = false;
        }
    })
    .catch(() => {
        errDiv.style.display = 'block';
        errDiv.textContent   = 'Error de conexión.';
        btn.disabled    = false;
        btn.textContent = 'Validar en Mercado Público';
        _mpValidando    = false;
    });
}
</script>
@endpush

@endsection
