@extends('layouts.app')

@section('title', 'SICD ' . $sicd->codigo_sicd)

@section('content')

{{-- Header --}}
<div class="mb-6">
    <a href="{{ route('admin.sicd.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a SICD</a>
    <div class="flex items-start justify-between mt-1">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 font-mono">{{ $sicd->codigo_sicd }}</h1>
            @if($sicd->descripcion)
                <p class="text-sm text-gray-500 mt-0.5">{{ $sicd->descripcion }}</p>
            @endif
        </div>
        @if($sicd->estado === 'recibido')
            <span class="inline-flex items-center bg-green-100 text-green-700 text-sm font-semibold px-3 py-1.5 rounded-full">✓ Recibido</span>
        @elseif($sicd->estado === 'agrupado')
            <span class="inline-flex items-center bg-blue-100 text-blue-700 text-sm font-semibold px-3 py-1.5 rounded-full">📎 Agrupado en OC</span>
        @else
            <span class="inline-flex items-center bg-yellow-100 text-yellow-700 text-sm font-semibold px-3 py-1.5 rounded-full">⏳ Pendiente</span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLUMNA IZQUIERDA: detalles --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Documento SICD --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-700">Documento SICD</h2>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span>Subido por <strong>{{ $sicd->usuario->name }}</strong></span>
                    <span>{{ $sicd->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            @if($sicd->tieneDocumentoPersistido())
            {{-- PDF externo enlazado --}}
            <div class="px-5 py-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <span class="inline-flex items-center text-xs font-semibold rounded-full px-2.5 py-1"
                          style="background:#dcfce7;color:#166534;">
                        Documento adjuntado
                    </span>
                    @if($sicd->documento_adjuntado_at)
                        <span class="text-xs text-gray-400">
                            {{ $sicd->documento_nombre ?: 'SICD PDF' }} · {{ $sicd->documento_adjuntado_at->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
                <iframe src="{{ route('admin.sicd.ver-documento', $sicd->id) }}"
                        class="w-full rounded border border-gray-200"
                        style="height:540px;"
                        title="Documento SICD {{ $sicd->codigo_sicd }}">
                </iframe>
            </div>
            @else
            <div class="px-5 py-4 flex items-center gap-4">
                <div class="flex-1">
                    <p class="text-sm text-gray-400 italic">Sin documento SICD enlazado.</p>
                    @if(in_array($sicd->documento_estado, ['error', 'reintento_pendiente'], true) && $sicd->documento_error)
                        <p class="mt-1 text-xs text-red-500">{{ $sicd->documento_error }}</p>
                    @elseif($sicd->documento_estado)
                        <p class="mt-1 text-xs text-gray-400">Estado documental: {{ str_replace('_', ' ', $sicd->documento_estado) }}</p>
                    @endif
                </div>
                <button id="btn-enlazar-show"
                        onclick="enlazarDesdeShow()"
                        class="text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 px-3 py-1.5 rounded-lg transition">
                    {{ $sicd->documento_estado === 'reintento_pendiente' ? 'Reintentar PDF SICD' : 'Enlazar PDF SICD' }}
                </button>
                <span id="enlazar-show-msg" class="text-xs hidden"></span>
            </div>
            @endif

            @if($sicd->boleta)
            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-xs text-gray-500">{{ $sicd->boleta?->archivo_nombre ?: 'Boleta adjunta' }}</p>
                </div>
                <a href="{{ route('admin.sicd.descargar', $sicd->id) }}" target="_blank"
                   class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
                    Ver boleta
                </a>
            </div>
            @endif
        </div>

{{-- Tabla de productos (del Excel) --}}
        <div id="det-card" class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between" id="det-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-700" id="det-card-title">Detalle de productos</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Leídos desde el Excel adjunto al crear el SICD</p>
                </div>
                <div class="flex gap-2" id="det-botones">
                    <button id="btn-editar-det" onclick="detEditarOn()"
                            class="det-btn-edit inline-flex items-center gap-1.5 text-xs font-semibold border px-3 py-1.5 rounded-lg shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar SICD
                    </button>
                    <button id="btn-guardar-det" onclick="detGuardar()" style="display:none"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-1.5 rounded-lg transition-all duration-150 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Guardar
                    </button>
                    <button id="btn-cancelar-det" onclick="detEditarOff()" style="display:none"
                            class="text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-200 px-3 py-1.5 rounded-lg transition-all duration-150">
                        Cancelar
                    </button>
                </div>
            </div>

            <div id="det-msg" style="display:none" class="mx-5 mt-3 mb-1 px-3 py-2 rounded-lg text-xs font-medium"></div>

            <div class="overflow-x-auto">
            <table id="det-tabla" class="w-full text-sm" style="table-layout:fixed;">
                <colgroup>
                    <col style="width:40%">
                    <col style="width:10%">
                    <col style="width:13%">
                    <col style="width:14%">
                    <col style="width:14%">
                    <col class="det-edit-col" style="display:none; width:44px;">
                </colgroup>
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Descripción</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Unidad</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Cant. Solicitada</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Precio Neto</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Neto</th>
                        <th class="det-edit-col" style="display:none;"></th>
                    </tr>
                </thead>
                <tbody id="det-tbody" class="divide-y divide-gray-50">
                    @foreach($sicd->detalles as $det)
                    <tr class="det-row transition-colors duration-100 hover:bg-gray-50/70" data-id="{{ $det->id }}">
                        <td class="px-4 py-3 text-gray-800 text-sm">
                            <span class="det-view leading-snug block truncate">{{ $det->nombre_producto_excel }}</span>
                            <input class="det-input det-inp-text" type="text" style="display:none"
                                   data-field="nombre" value="{{ $det->nombre_producto_excel }}">
                        </td>
                        <td class="px-3 py-3 text-center text-gray-600">
                            <span class="det-view font-medium text-indigo-600">{{ $det->unidad ?? '—' }}</span>
                            <input class="det-input det-inp-sm" type="text" style="display:none; text-align:center;"
                                   data-field="unidad" value="{{ $det->unidad }}">
                        </td>
                        <td class="px-3 py-3 text-center font-semibold text-gray-700">
                            <span class="det-view">{{ $det->cantidad_solicitada }}</span>
                            <input class="det-input det-inp-sm det-cant" type="number" min="0" style="display:none; text-align:center;"
                                   data-field="cantidad_solicitada" value="{{ $det->cantidad_solicitada }}">
                        </td>
                        <td class="px-3 py-3 text-right text-gray-700">
                            <span class="det-view">{{ $det->precio_neto !== null ? '$' . number_format($det->precio_neto, 0, ',', '.') : '—' }}</span>
                            <input class="det-input det-inp-num det-pneto" type="number" min="0" step="any" style="display:none; text-align:right;"
                                   data-field="precio_neto" value="{{ $det->precio_neto }}">
                        </td>
                        <td class="px-3 py-3 text-right font-semibold text-gray-800">
                            <span class="det-view">{{ $det->total_neto !== null ? '$' . number_format($det->total_neto, 0, ',', '.') : '—' }}</span>
                            <input class="det-input det-inp-num det-ptotal" type="number" min="0" step="1" style="display:none; text-align:right;"
                                   data-field="total_neto" value="{{ $det->total_neto }}">
                        </td>
                        <td class="det-edit-col py-3 text-center" style="display:none; padding:0 8px;">
                            <button type="button" onclick="detEliminarFila(this)" title="Eliminar fila"
                                    class="w-7 h-7 inline-flex items-center justify-center rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors duration-150">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @php
                    $totalNeto  = $sicd->detalles->sum('total_neto');
                    $totalIva   = round($totalNeto * 0.19, 0);
                    $totalConIva = round($totalNeto * 1.19, 0);
                @endphp
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-200">
                        <td colspan="4" class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto Neto</td>
                        <td id="det-total-display" class="px-3 py-2.5 text-right text-sm font-bold text-gray-700">${{ number_format($totalNeto, 0, ',', '.') }}</td>
                        <td class="det-edit-col" style="display:none;"></td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td colspan="4" class="px-4 py-1.5 text-right text-xs text-gray-400">IVA 19%</td>
                        <td id="det-iva-display" class="px-3 py-1.5 text-right text-xs text-gray-500">${{ number_format($totalIva, 0, ',', '.') }}</td>
                        <td class="det-edit-col" style="display:none;"></td>
                    </tr>
                    <tr class="bg-indigo-50 border-t border-indigo-100">
                        <td colspan="4" class="px-4 py-3 text-right text-sm font-bold text-indigo-700">Total (con IVA)</td>
                        <td id="det-total-iva-display" class="px-3 py-3 text-right text-base font-bold text-indigo-900">${{ number_format($totalConIva, 0, ',', '.') }}</td>
                        <td class="det-edit-col" style="display:none;"></td>
                    </tr>
                    <tr id="det-row-agregar" style="display:none;">
                        <td colspan="7" class="px-4 py-2.5 bg-indigo-50/60 border-t border-indigo-100">
                            <button type="button" onclick="detAbrirPicker()"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors duration-150">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Agregar producto
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>

        {{-- Ítems pendientes de clasificación --}}
        @php $pendientesDet = $sicd->detalles->where('clasificacion_pendiente', true); @endphp
        @if($pendientesDet->isNotEmpty())
        <div id="pend-card" class="rounded-xl shadow overflow-hidden" style="background:#fffbeb; border:1.5px solid #fcd34d;">
            <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:#fde68a;">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="#d97706" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <h2 class="text-sm font-semibold" style="color:#92400e;">Ítems pendientes de clasificación</h2>
                </div>
                <span style="font-size:.68rem;font-weight:700;padding:2px 9px;border-radius:9999px;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;">
                    {{ $pendientesDet->count() }} pendiente{{ $pendientesDet->count() > 1 ? 's' : '' }}
                </span>
            </div>

            <div class="divide-y" style="border-color:#fde68a;">
                @foreach($pendientesDet as $pdet)
                <div class="px-5 py-3 flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate" style="color:#78350f;">{{ $pdet->nombre_producto_excel }}</p>
                        <p class="text-xs mt-0.5" style="color:#b45309;">
                            {{ $pdet->cantidad_solicitada }} {{ $pdet->unidad ?: 'ud.' }}
                            · Por {{ $pdet->pendienteUser?->name ?? '—' }}
                            el {{ $pdet->pendiente_at?->format('d/m/Y') }}
                        </p>
                    </div>
                    <button type="button"
                            onclick="abrirResolverPend({{ $pdet->id }}, {!! json_encode($pdet->nombre_producto_excel, JSON_HEX_TAG | JSON_HEX_AMP) !!})"
                            style="flex-shrink:0;padding:0.3rem 0.85rem;font-size:0.75rem;font-weight:700;color:#92400e;background:#fef3c7;border:1.5px solid #fcd34d;border-radius:0.5rem;cursor:pointer;transition:background .15s,border-color .15s;"
                            onmouseover="this.style.background='#fde68a';this.style.borderColor='#f59e0b';"
                            onmouseout="this.style.background='#fef3c7';this.style.borderColor='#fcd34d';">
                        Resolver →
                    </button>
                </div>
                @endforeach
            </div>

            <div class="px-5 py-3" style="background:#fef3c7; border-top:1px solid #fde68a;">
                <p class="text-xs" style="color:#92400e;">
                    ⚠ No se puede crear una Orden de Compra con este SICD hasta resolver todos los ítems pendientes.
                </p>
            </div>
        </div>
        @endif

    </div>

{{-- Modal: resolver ítem pendiente --}}
<div id="pend-modal" style="display:none;position:fixed;inset:0;z-index:9100;background:rgba(0,0,0,.55);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 24px 60px rgba(0,0,0,.25);width:100%;max-width:480px;animation:picker-in .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.2rem 1.25rem 0.9rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <p style="font-size:0.95rem;font-weight:700;color:#1e293b;margin:0;">Resolver ítem pendiente</p>
                <p id="pend-modal-desc" style="font-size:0.75rem;color:#92400e;margin:0.15rem 0 0;font-weight:600;"></p>
            </div>
            <button onclick="cerrarResolverPend()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1.3rem;line-height:1;">&#x2715;</button>
        </div>
        <div style="padding:1rem 1.25rem;">
            <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:0.4rem;">Buscar producto del catálogo:</label>
            <input id="pend-search" type="text" placeholder="Escribir nombre del producto..."
                   oninput="pendBuscar(this.value)"
                   style="width:100%;border:1.5px solid #d1d5db;border-radius:0.5rem;padding:0.45rem 0.85rem;font-size:0.85rem;outline:none;box-sizing:border-box;">
            <div id="pend-results" style="margin-top:0.5rem;max-height:240px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:0.5rem;"></div>
            <p id="pend-modal-msg" style="display:none;font-size:0.75rem;margin-top:0.5rem;"></p>
        </div>
        <form id="pend-form" method="POST" action="{{ route('admin.sicd.resolver-detalle-pendiente', $sicd->id) }}">
            @csrf
            <input type="hidden" id="pend-detalle-id" name="sicd_detalle_id">
            <input type="hidden" id="pend-producto-id" name="producto_id">
            <div style="padding:0 1.25rem 1.25rem;display:flex;justify-content:flex-end;gap:0.5rem;">
                <button type="button" onclick="cerrarResolverPend()"
                        style="padding:0.45rem 1rem;font-size:0.82rem;font-weight:600;background:#f3f4f6;color:#374151;border:none;border-radius:0.5rem;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit" id="pend-submit-btn" disabled
                        style="padding:0.45rem 1.1rem;font-size:0.82rem;font-weight:700;color:#fff;background:#9ca3af;border:none;border-radius:0.5rem;cursor:not-allowed;transition:background .15s;">
                    Enlazar producto
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal picker de productos --}}
<div id="det-modal-picker" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,.5); align-items:flex-start; justify-content:center; padding-top:5vh; overflow-y:auto;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:900px; max-width:calc(100vw - 2rem); max-height:85vh; display:flex; flex-direction:column; animation:picker-in .2s cubic-bezier(.22,.68,0,1.2) both;">

        {{-- Header --}}
        <div style="padding:1.1rem 1.25rem 0.9rem; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
            <div>
                <p style="font-size:1rem; font-weight:700; color:#1e293b; margin:0;">Agregar producto al SICD</p>
                <p style="font-size:0.75rem; color:#6b7280; margin:0.2rem 0 0;">Selecciona uno o varios productos del catálogo</p>
            </div>
            <button onclick="detCerrarPicker()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1.3rem;line-height:1;" title="Cerrar">&#x2715;</button>
        </div>

        {{-- Search --}}
        <div style="padding:0.75rem 1.25rem; flex-shrink:0; border-bottom:1px solid #f3f4f6;">
            <input id="det-picker-search" type="text" placeholder="Buscar producto..."
                   oninput="detPickerBuscar(this.value)"
                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.85rem; font-size:0.85rem; outline:none; box-sizing:border-box;">
        </div>

        {{-- Body: categorías izq + productos der --}}
        <div style="display:flex; flex:1; overflow:hidden; min-height:0;">

            {{-- Familias + categorías --}}
            <div style="width:210px; flex-shrink:0; border-right:1px solid #f3f4f6; overflow-y:auto; padding:0.5rem 0;">
                <div id="det-picker-cats"></div>
            </div>

            {{-- Productos --}}
            <div style="flex:1; overflow-y:auto; padding:0.75rem 1rem;" id="det-picker-prods">
                <p style="font-size:0.8rem; color:#9ca3af; text-align:center; margin-top:2rem;">Selecciona una categoría</p>
            </div>
        </div>

        {{-- Toast dentro del modal --}}
        <div id="det-picker-toast" style="display:none; margin:0.5rem 1.25rem; padding:0.5rem 0.75rem; background:#dcfce7; border:1px solid #86efac; border-radius:0.5rem; color:#166534; font-size:0.78rem; font-weight:600; flex-shrink:0;"></div>

        {{-- Footer --}}
        <div style="padding:0.75rem 1.25rem; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; flex-shrink:0;">
            <button onclick="detCerrarPicker()"
                    style="padding:0.4rem 1.1rem; font-size:0.82rem; font-weight:600; background:#f3f4f6; color:#374151; border:none; border-radius:0.5rem; cursor:pointer;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script id="det-familias-data" type="application/json">
{!! json_encode($familias->map(fn($f) => [
    'id'         => $f->id,
    'nombre'     => $f->nombre,
    'categorias' => $f->categorias->map(fn($c) => [
        'id'       => $c->id,
        'nombre'   => $c->nombre,
        'productos'=> $c->productos->map(fn($p) => [
            'id'     => $p->id,
            'nombre' => $p->nombre,
            'unidad' => $p->unidad,
        ])->values(),
    ])->values(),
])->values()) !!}
</script>

@push('head')
<style>
/* ── Picker modal ─────────────────────────────── */
@keyframes picker-in {
    from { opacity:0; transform:translateY(-16px) scale(.96); }
    to   { opacity:1; transform:none; }
}
@keyframes det-row-in {
    from { opacity:0; transform:translateX(-6px); }
    to   { opacity:1; transform:none; }
}
@keyframes det-fade-in {
    from { opacity:0; }
    to   { opacity:1; }
}

/* ── Edit inputs ──────────────────────────────── */
.det-inp-text, .det-inp-sm, .det-inp-num {
    border: 1.5px solid #e5e7eb;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 0.82rem;
    color: #1f2937;
    background: #fff;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.det-inp-text  { width: 100%; }
.det-inp-sm    { width: 68px; }
.det-inp-num   { width: 96px; }
.det-inp-text:focus, .det-inp-sm:focus, .det-inp-num:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
/* ── Edit row highlight ───────────────────────── */
#det-tabla.editing tbody tr.det-row {
    background: #fafbff;
}
#det-tabla.editing tbody tr.det-row:hover {
    background: #f0f4ff;
}
/* ── Picker category hover ────────────────────── */
.det-picker-cat-btn {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.45rem 1rem;
    font-size: 0.8rem;
    border: none;
    cursor: pointer;
    transition: background .12s, color .12s;
    background: transparent;
    color: #374151;
    border-radius: 0;
}
.det-picker-cat-btn:hover { background: #f3f4f6; }
.det-picker-cat-btn.active { background: #eef2ff; color: #4338ca; font-weight: 600; }

/* ── Picker product card ──────────────────────── */
.det-picker-prod {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.55rem 0.75rem;
    border-radius: 0.6rem;
    margin-bottom: 0.3rem;
    background: #f9fafb;
    border: 1px solid transparent;
    transition: background .12s, border-color .12s, transform .1s;
    animation: det-fade-in .18s ease both;
}
.det-picker-prod:hover {
    background: #eef2ff;
    border-color: #c7d2fe;
    transform: translateX(2px);
}
.det-picker-add-btn {
    flex-shrink: 0;
    padding: 0.3rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 600;
    background: #4f46e5;
    color: #fff;
    border: none;
    border-radius: 0.45rem;
    cursor: pointer;
    transition: background .15s, transform .1s;
}
.det-picker-add-btn:hover { background: #4338ca; transform: scale(1.04); }
.det-picker-add-btn:active { transform: scale(.97); }

/* ── New row animation ────────────────────────── */
.det-row-new {
    animation: det-row-in .22s ease both;
}

/* ── Edit button (replaces inline style) ─────── */
.det-btn-edit {
    background: #fff;
    color: #4f46e5;
    border-color: #a5b4fc;
    transition: background .15s, border-color .15s, transform .1s, box-shadow .15s;
    box-shadow: 0 1px 2px rgba(0,0,0,.05);
}
.det-btn-edit:hover {
    background: #eef2ff;
    border-color: #6366f1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99,102,241,.2);
}
.det-btn-edit:active { transform: scale(.97); }

/* ── Dark mode: card container ───────────────── */
html.dark #det-card { background: #1e293b; }
html.dark #det-card-header { border-color: #334155; }
html.dark #det-card-title { color: #e2e8f0; }

/* ── Dark mode: table header ─────────────────── */
html.dark #det-tabla thead { background: #0f172a; border-color: #334155; }
html.dark #det-tabla thead th { color: #94a3b8; border-color: #334155; }

/* ── Dark mode: table body ───────────────────── */
html.dark #det-tbody { border-color: #334155 !important; }
html.dark #det-tbody tr { border-color: #334155 !important; }
html.dark #det-tbody .det-row:hover { background: #1e3a5f !important; }
html.dark #det-tabla.editing tbody tr.det-row { background: #162032 !important; }
html.dark #det-tabla.editing tbody tr.det-row:hover { background: #1e3a5f !important; }

/* ── Dark mode: cell text ────────────────────── */
html.dark #det-tbody td { color: #cbd5e1; }
html.dark #det-tbody .det-view { color: #cbd5e1; }
html.dark #det-tbody .text-indigo-600 { color: #818cf8; }
html.dark #det-tbody .font-semibold { color: #e2e8f0; }

/* ── Dark mode: edit inputs ──────────────────── */
html.dark .det-inp-text,
html.dark .det-inp-sm,
html.dark .det-inp-num {
    background: #0f172a;
    color: #e2e8f0;
    border-color: #475569;
}
html.dark .det-inp-text:focus,
html.dark .det-inp-sm:focus,
html.dark .det-inp-num:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.2);
}

/* ── Dark mode: tfoot ────────────────────────── */
html.dark #det-tabla tfoot tr.bg-gray-50 { background: #0f172a; border-color: #334155; }
html.dark #det-tabla tfoot .text-gray-500 { color: #94a3b8; }
html.dark #det-tabla tfoot .text-gray-400 { color: #64748b; }
html.dark #det-tabla tfoot .text-gray-700 { color: #cbd5e1; }
html.dark #det-tabla tfoot tr.bg-indigo-50 { background: #1e1b4b; border-color: #3730a3; }
html.dark #det-tabla tfoot .text-indigo-700 { color: #a5b4fc; }
html.dark #det-tabla tfoot .text-indigo-900 { color: #c7d2fe; }
html.dark #det-row-agregar td { background: rgba(30,27,75,.6); border-color: #3730a3; }

/* ── Dark mode: delete row button ────────────── */
html.dark .det-row button.text-gray-400 { color: #64748b; }
html.dark .det-row button:hover { color: #f87171; background: rgba(239,68,68,.1); }

/* ── Dark mode: pending card ─────────────────── */
html.dark #pend-card { background: #451a03 !important; border-color: #d97706 !important; }
html.dark #pend-card .border-b { border-color: #b45309 !important; }
html.dark #pend-card p, html.dark #pend-card h2 { color: #fcd34d !important; }
html.dark #pend-card .divide-y > div { border-color: #92400e !important; }

/* ── Dark mode: edit button ──────────────────── */
html.dark .det-btn-edit {
    background: #0f172a;
    color: #a5b4fc;
    border-color: #6366f1;
}
html.dark .det-btn-edit:hover {
    background: #1e1b4b;
    border-color: #818cf8;
    box-shadow: 0 4px 12px rgba(99,102,241,.25);
}
/* ── Modales dark mode ── */
html.dark #pend-modal > div,
html.dark #det-modal-picker > div {
    background: #1e293b !important;
    border: 1px solid #334155;
}
html.dark #pend-modal h3,
html.dark #det-modal-picker h3 { color: #f1f5f9 !important; }
html.dark #pend-modal p,
html.dark #det-modal-picker p { color: #94a3b8 !important; }
html.dark #pend-modal label,
html.dark #det-modal-picker label { color: #cbd5e1 !important; }
html.dark #pend-modal input,
html.dark #pend-modal select,
html.dark #det-modal-picker input,
html.dark #det-modal-picker select {
    background: #0f172a !important; border-color: #475569 !important; color: #e2e8f0 !important;
}
html.dark #pend-card {
    background: #1c1a08 !important; border-color: #854d0e !important;
}
</style>
@endpush

    {{-- COLUMNA DERECHA: OCs asociadas (relación many-to-many) --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-700">Órdenes de Compra Asociadas</h2>
                @if($sicd->ordenesCompra->isNotEmpty())
                    <span style="font-size:.7rem; font-weight:700; padding:2px 8px; border-radius:9999px; background:#eff6ff; color:#2563eb;">
                        {{ $sicd->ordenesCompra->count() }} OC(s)
                    </span>
                @endif
            </div>

            @if($sicd->ordenesCompra->isEmpty())
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-400">Aún no asignado a ninguna OC.</p>
                    @if($sicd->estado === 'pendiente')
                        @if($sicd->tieneDocumentoPersistido())
                            <a href="{{ route('admin.ordenes.create') }}"
                               class="mt-3 inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline font-medium">
                                Crear OC y agrupar →
                            </a>
                        @else
                            <div class="mt-3 flex items-start gap-2 rounded-lg px-3 py-2.5" style="background:#fef3c7; border:1px solid #fcd34d;">
                                <svg style="width:0.875rem;height:0.875rem;flex-shrink:0;margin-top:0.1rem;color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <p style="font-size:0.75rem; color:#92400e; margin:0; line-height:1.4;">
                                    Debes enlazar el PDF del SICD antes de poder crear una Orden de Compra.
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($sicd->ordenesCompra as $oc)
                    @php
                        $estadoColor = match($oc->estado) {
                            'recibido' => ['bg' => '#dcfce7', 'color' => '#15803d', 'texto' => '✓ Recibido'],
                            'validado' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'texto' => '✓ Validado'],
                            default    => ['bg' => '#fef3c7', 'color' => '#92400e', 'texto' => '⏳ Pendiente'],
                        };
                    @endphp
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-mono font-bold text-indigo-700 truncate">{{ $oc->numero_oc }}</p>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span style="font-size:.68rem; font-weight:700; padding:1px 7px; border-radius:9999px; white-space:nowrap;
                                             background:{{ $estadoColor['bg'] }}; color:{{ $estadoColor['color'] }};">
                                    {{ $estadoColor['texto'] }}
                                </span>
                                @if($oc->api_proveedor_nombre)
                                    <span class="text-xs text-gray-500 truncate">{{ $oc->api_proveedor_nombre }}</span>
                                @endif
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $oc->tipoAdquisicionBadgeClasses() }}"
                                      title="{{ $oc->tipoAdquisicionMetadata() }}">
                                    {{ $oc->tipoAdquisicionLabel() }}
                                </span>
                            </div>
                            @if($oc->api_total)
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Total: <span class="font-semibold text-gray-600">{{ $oc->totalFormateado() }}</span>
                                </p>
                            @endif
                        </div>
                        <a href="{{ route('admin.ordenes.show', $oc->id) }}"
                           class="flex-shrink-0 text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                            Ver →
                        </a>
                    </div>
                    @endforeach
                </div>

                @if($sicd->permite_mas_oc)
                <div class="px-5 py-3 border-t border-gray-100 {{ $sicd->tieneDocumentoPersistido() ? 'bg-green-50' : 'bg-amber-50' }}">
                    <div class="flex items-center gap-2">
                        <span style="font-size:.65rem; font-weight:700; padding:1px 7px; border-radius:9999px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">
                            + OC habilitado
                        </span>
                        <span class="text-xs text-green-700">Se pueden asociar más órdenes de compra.</span>
                    </div>
                    @if($sicd->tieneDocumentoPersistido())
                        <a href="{{ route('admin.ordenes.create') }}"
                           class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline">
                            Crear nueva OC asociada →
                        </a>
                    @else
                        <p style="font-size:0.73rem; color:#92400e; margin:0.4rem 0 0; line-height:1.4;">
                            ⚠ Enlaza el PDF del SICD antes de crear una nueva OC.
                        </p>
                    @endif
                </div>
                @endif
            @endif
        </div>

        {{-- Progreso de recepción --}}
        @if($sicd->ordenesCompra->isNotEmpty())
        @php
            $totalOcs     = $sicd->ordenesCompra->count();
            $recibidasOcs = $sicd->ordenesCompra->where('estado', 'recibido')->count();
            $pctRecibidas = $totalOcs > 0 ? round($recibidasOcs / $totalOcs * 100) : 0;
        @endphp
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs font-semibold text-gray-600 mb-2">Progreso de recepción</p>
            <div class="flex items-center gap-3">
                <div style="flex:1; background:#f3f4f6; border-radius:9999px; height:8px; overflow:hidden;">
                    <div style="width:{{ $pctRecibidas }}%; height:100%; background:{{ $pctRecibidas === 100 ? '#22c55e' : '#4f46e5' }}; border-radius:9999px; transition:width .4s;"></div>
                </div>
                <span class="text-xs font-bold text-gray-600 whitespace-nowrap">{{ $recibidasOcs }}/{{ $totalOcs }}</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">
                {{ $recibidasOcs }} OC(s) recibida(s) de {{ $totalOcs }} asociada(s)
                @if($sicd->permite_mas_oc) · <span class="text-green-600">Abierta para más OCs</span> @endif
            </p>
        </div>

        {{-- Variación Presupuestaria SICD vs OC --}}
        @php
            $sicdNetoRef   = $sicd->detalles->sum(fn($d) => (float)($d->total_neto_original ?? $d->total_neto ?? 0));
            $sicdIvaRef    = round($sicdNetoRef * 0.19, 2);
            $sicdTotalRef  = round($sicdNetoRef * 1.19, 2);
            $ocTotalAdj    = (float) $sicd->ordenesCompra->sum('api_total');
            // Comparación homogénea: SICD total (neto+IVA) vs OC api_total (total con IVA)
            $difSicdOc     = $ocTotalAdj - $sicdTotalRef;
            $hayOcConTotal = $sicd->ordenesCompra->whereNotNull('api_total')->isNotEmpty();
            $fmt           = fn($v) => '$' . number_format(abs((float)$v), 0, ',', '.');
        @endphp
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                </svg>
                <p class="text-xs font-semibold text-gray-600">Variación Presupuestaria</p>
            </div>
            <div class="px-4 py-3 space-y-1.5">
                {{-- SICD: desglose neto / IVA / total --}}
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-indigo-300 shrink-0"></span>
                        <span class="text-xs text-gray-400">SICD Neto</span>
                    </div>
                    <span class="text-xs text-gray-500 tabular-nums">{{ $fmt($sicdNetoRef) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-gray-300 pl-3">+ IVA 19%</span>
                    <span class="text-xs text-gray-400 tabular-nums">{{ $fmt($sicdIvaRef) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2 pb-1.5 border-b border-gray-100">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                        <span class="text-xs font-semibold text-gray-600">SICD Total</span>
                    </div>
                    <span class="text-xs font-bold text-indigo-700 tabular-nums">{{ $fmt($sicdTotalRef) }}</span>
                </div>

                {{-- OC adjudicada (api_total = total con IVA) --}}
                <div class="flex items-center justify-between gap-2 pt-0.5">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $hayOcConTotal ? 'bg-emerald-400' : 'bg-gray-300' }} shrink-0"></span>
                        <span class="text-xs text-gray-500">
                            {{ $hayOcConTotal ? 'OC Total (con IVA)' : 'OC aún sin validar' }}
                        </span>
                    </div>
                    <span class="text-xs font-bold {{ $hayOcConTotal ? 'text-gray-900' : 'text-gray-400' }} tabular-nums">
                        {{ $hayOcConTotal ? $fmt($ocTotalAdj) : '—' }}
                    </span>
                </div>

                {{-- Diferencia (homogénea: SICD total vs OC total) --}}
                @if($sicd->ordenesCompra->isNotEmpty())
                <div class="pt-2 border-t border-gray-100">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Metadata documental OC</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($sicd->ordenesCompra as $oc)
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $oc->tipoAdquisicionBadgeClasses() }}"
                                  title="{{ $oc->numero_oc }} · {{ $oc->tipoAdquisicionMetadata() }}">
                                {{ $oc->numero_oc }} · {{ $oc->tipoAdquisicionLabel() }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($hayOcConTotal && $sicdTotalRef > 0)
                <div class="pt-2 border-t border-gray-100">
                    <div class="flex items-center justify-between gap-2">
                        @if(abs($difSicdOc) < 1)
                            <span class="text-xs font-semibold text-gray-500">Sin variación</span>
                            <span class="text-xs text-gray-400">$0</span>
                        @elseif($difSicdOc < 0)
                            <div class="flex items-center gap-1">
                                <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                <span class="text-xs font-bold text-green-600">Dif. Favorable</span>
                            </div>
                            <span class="text-xs font-bold text-green-600 tabular-nums">{{ $fmt($difSicdOc) }}</span>
                        @else
                            <div class="flex items-center gap-1">
                                <svg class="w-3 h-3 text-red-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                <span class="text-xs font-bold text-red-500">Dif. Desfavorable</span>
                            </div>
                            <span class="text-xs font-bold text-red-500 tabular-nums">{{ $fmt($difSicdOc) }}</span>
                        @endif
                    </div>
                    <p class="text-[10px] text-gray-300 mt-1">Comparación total vs total (ambos incluyen IVA)</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function enlazarDesdeShow() {
    var btn = document.getElementById('btn-enlazar-show');
    var msg = document.getElementById('enlazar-show-msg');
    if (!btn) return;

    btn.disabled = true;
    btn.textContent = 'Procesando PDF...';
    if (msg) { msg.className = 'text-xs text-gray-400'; msg.textContent = 'Conectando con SICD externa y guardando documento...'; msg.classList.remove('hidden'); }

    fetch('{{ route("admin.sicd.enlazar-pdf", $sicd->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(function(r) {
        if (!r.ok) {
            return r.json().then(function(e) { throw new Error(e.msg || 'Error ' + r.status); });
        }
        return r.json();
    })
    .then(function(res) {
        if (res.ok) {
            if (msg) { msg.className = 'text-xs text-green-600 font-semibold'; msg.textContent = '✓ Enlazado. Recarga para ver el PDF.'; }
            btn.textContent = '✓ Listo';
            btn.disabled = true;
            btn.className = 'text-xs font-semibold bg-green-50 text-green-700 border border-green-200 px-3 py-1.5 rounded-lg';
            setTimeout(function () { window.location.reload(); }, 650);
        } else {
            btn.disabled = false;
            btn.textContent = 'Reintentar PDF SICD';
            if (msg) { msg.className = 'text-xs text-red-500'; msg.textContent = res.msg || 'Error desconocido'; }
        }
    })
    .catch(function(e) {
        btn.disabled = false;
        btn.textContent = 'Reintentar PDF SICD';
        if (msg) { msg.className = 'text-xs text-red-500'; msg.textContent = e.message || 'Error de conexión'; }
    });
}

// ── Edición de detalles ───────────────────────────────────────────────────
var _detEditando  = false;
var _detNuevoIdx  = 0;
var _detSnapshot  = '';

function detEditarOn() {
    _detEditando = true;
    _detSnapshot = document.getElementById('det-tbody').innerHTML;
    document.getElementById('btn-editar-det').style.display   = 'none';
    document.getElementById('btn-guardar-det').style.display  = '';
    document.getElementById('btn-cancelar-det').style.display = '';
    document.getElementById('det-tabla').classList.add('editing');
    document.querySelectorAll('.det-view').forEach(function(el) { el.style.display = 'none'; });
    document.querySelectorAll('.det-input').forEach(function(el) { el.style.display = 'block'; });
    document.querySelectorAll('.det-edit-col').forEach(function(el) { el.style.display = 'table-cell'; });
    document.getElementById('det-row-agregar').style.display = '';
    detBindPrices();
}

function detEditarOff() {
    _detEditando = false;
    document.getElementById('btn-editar-det').style.display   = '';
    document.getElementById('btn-guardar-det').style.display  = 'none';
    document.getElementById('btn-cancelar-det').style.display = 'none';
    document.getElementById('det-tabla').classList.remove('editing');
    document.getElementById('det-tbody').innerHTML = _detSnapshot;
    _detSnapshot = '';
    document.querySelectorAll('.det-view').forEach(function(el) { el.style.display = ''; });
    document.querySelectorAll('.det-input').forEach(function(el) { el.style.display = 'none'; });
    document.querySelectorAll('.det-edit-col').forEach(function(el) { el.style.display = 'none'; });
    document.getElementById('det-row-agregar').style.display = 'none';
    detRecalcTotal();
    detOcultarMsg();
}

function detEliminarFila(btn) {
    btn.closest('tr').remove();
    detRecalcTotal();
}

var _detCatalogo = [];
var _detPickerCatId = null;

function detAbrirPicker() {
    if (!_detCatalogo.length) {
        var raw = document.getElementById('det-familias-data');
        if (raw) _detCatalogo = JSON.parse(raw.textContent);
    }
    document.getElementById('det-picker-search').value = '';
    document.getElementById('det-picker-toast').style.display = 'none';
    _detPickerCatId = null;
    detPickerRenderCats();
    detPickerRenderProds([]);
    document.getElementById('det-modal-picker').style.display = 'flex';
    setTimeout(function() { document.getElementById('det-picker-search').focus(); }, 60);
}

function detCerrarPicker() {
    document.getElementById('det-modal-picker').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    var overlay = document.getElementById('det-modal-picker');
    if (overlay) overlay.addEventListener('click', function(e) { if (e.target === overlay) detCerrarPicker(); });
});

function detPickerRenderCats() {
    var cont = document.getElementById('det-picker-cats');
    cont.innerHTML = '';
    _detCatalogo.forEach(function(f) {
        var fDiv = document.createElement('div');
        fDiv.style.cssText = 'padding:0.5rem 1rem 0.2rem; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#9ca3af;';
        fDiv.textContent = f.nombre;
        cont.appendChild(fDiv);
        f.categorias.forEach(function(c) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'det-picker-cat-btn' + (c.id === _detPickerCatId ? ' active' : '');
            btn.innerHTML = escDetHtml(c.nombre) + ' <span style="color:#9ca3af;font-weight:400;font-size:0.72rem;">(' + c.productos.length + ')</span>';
            btn.onclick = function() { _detPickerCatId = c.id; detPickerRenderCats(); detPickerRenderProds(c.productos); };
            cont.appendChild(btn);
        });
    });
}

function detPickerRenderProds(productos) {
    var cont = document.getElementById('det-picker-prods');
    if (!productos.length) {
        cont.innerHTML = '<p style="font-size:0.8rem;color:#9ca3af;text-align:center;margin-top:2.5rem;">' + (_detPickerCatId ? 'Sin productos en esta categoría.' : 'Selecciona una categoría para ver sus productos.') + '</p>';
        return;
    }
    cont.innerHTML = '';
    productos.forEach(function(p, i) {
        var row = document.createElement('div');
        row.className = 'det-picker-prod';
        row.style.animationDelay = (i * 0.03) + 's';
        var info = document.createElement('div');
        info.style.cssText = 'flex:1; min-width:0;';
        info.innerHTML = '<p style="font-size:0.84rem;font-weight:500;color:#1f2937;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escDetHtml(p.nombre) + '</p>'
            + (p.unidad ? '<span style="font-size:0.7rem;color:#6366f1;font-weight:600;background:#eef2ff;border-radius:4px;padding:1px 6px;margin-top:3px;display:inline-block;">' + escDetHtml(p.unidad) + '</span>' : '');
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'det-picker-add-btn';
        btn.innerHTML = '<svg style="width:12px;height:12px;display:inline;vertical-align:middle;margin-right:3px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>Agregar';
        btn.onclick = function() { detPickerAgregarProducto(p); };
        row.appendChild(info);
        row.appendChild(btn);
        cont.appendChild(row);
    });
}

function detPickerBuscar(q) {
    q = q.trim().toLowerCase();
    if (!q) {
        if (_detPickerCatId) {
            var cat = _detCatalogo.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === _detPickerCatId; });
            detPickerRenderProds(cat ? cat.productos : []);
        } else { detPickerRenderProds([]); }
        return;
    }
    var all = _detCatalogo.flatMap(function(f) { return f.categorias.flatMap(function(c) { return c.productos; }); });
    detPickerRenderProds(all.filter(function(p) { return p.nombre.toLowerCase().includes(q); }));
}

function detPickerAgregarProducto(p) {
    var tbody = document.getElementById('det-tbody');
    var tr = document.createElement('tr');
    tr.className = 'det-row det-row-new transition-colors duration-100';
    tr.dataset.id = '';
    tr.innerHTML =
        '<td class="px-4 py-2.5">'
        + '<input type="text" data-field="nombre" value="' + escDetHtml(p.nombre) + '" class="det-input det-inp-text" placeholder="Descripción"></td>'
        + '<td class="px-3 py-2.5" style="text-align:center;">'
        + '<input type="text" data-field="unidad" value="' + escDetHtml(p.unidad || '') + '" class="det-input det-inp-sm" style="text-align:center;" placeholder="—"></td>'
        + '<td class="px-3 py-2.5" style="text-align:center;">'
        + '<input type="number" data-field="cantidad_solicitada" value="1" min="0" class="det-input det-inp-sm det-cant" style="text-align:center;"></td>'
        + '<td class="px-3 py-2.5" style="text-align:right;">'
        + '<input type="number" data-field="precio_neto" value="" min="0" step="any" class="det-input det-inp-num det-pneto" style="text-align:right;" placeholder="0"></td>'
        + '<td class="px-3 py-2.5" style="text-align:right;">'
        + '<input type="number" data-field="total_neto" value="" min="0" step="1" class="det-input det-inp-num det-ptotal" style="text-align:right;" placeholder="0"></td>'
        + '<td class="py-2.5" style="text-align:center; padding:0 8px;">'
        + '<button type="button" onclick="detEliminarFila(this)" title="Quitar"'
        + ' class="w-7 h-7 inline-flex items-center justify-center rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors duration-150">'
        + '<svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button></td>';
    tbody.appendChild(tr);
    detBindPrices(tr);

    var toast = document.getElementById('det-picker-toast');
    toast.textContent = '✓ "' + p.nombre + '" agregado a la tabla.';
    toast.style.display = 'block';
    setTimeout(function() { toast.style.display = 'none'; }, 2500);
}

function escDetHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function detBindPrices(scope) {
    var rows = scope ? [scope] : document.querySelectorAll('.det-row');
    rows.forEach(function(row) {
        var cant  = row.querySelector('.det-cant');
        var pneto = row.querySelector('.det-pneto');
        var ptot  = row.querySelector('.det-ptotal');
        if (!cant || !pneto || !ptot) return;
        pneto.addEventListener('input', function() {
            var c = parseFloat(cant.value) || 1;
            var n = parseFloat(this.value);
            ptot.value = isNaN(n) ? '' : Math.round(n * c);
            detRecalcTotal();
        });
        ptot.addEventListener('input', function() {
            var c = parseFloat(cant.value) || 1;
            var t = parseFloat(this.value);
            pneto.value = (!isNaN(t) && c > 0) ? parseFloat((t / c).toFixed(2)) : '';
            detRecalcTotal();
        });
        cant.addEventListener('input', function() {
            var c = parseFloat(this.value) || 1;
            var n = parseFloat(pneto.value);
            if (!isNaN(n)) { ptot.value = Math.round(n * c); detRecalcTotal(); }
        });
    });
}

function detRecalcTotal() {
    var sum = 0;
    document.querySelectorAll('.det-row .det-ptotal').forEach(function(inp) {
        sum += parseFloat(inp.value) || 0;
    });
    var iva      = Math.round(sum * 0.19);
    var conIva   = Math.round(sum * 1.19);
    var fmt = function(n) { return '$' + n.toLocaleString('es-CL'); };
    var d1 = document.getElementById('det-total-display');
    var d2 = document.getElementById('det-iva-display');
    var d3 = document.getElementById('det-total-iva-display');
    if (d1) d1.textContent = fmt(Math.round(sum));
    if (d2) d2.textContent = fmt(iva);
    if (d3) d3.textContent = fmt(conIva);
}

function detMostrarMsg(msg, ok) {
    var el = document.getElementById('det-msg');
    if (!el) return;
    el.textContent = msg;
    el.className = 'mx-5 mt-3 mb-1 px-3 py-2 rounded-lg text-xs font-medium ' + (ok ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200');
    el.style.display = '';
    setTimeout(function() { el.style.display = 'none'; }, 4000);
}
function detOcultarMsg() { var el = document.getElementById('det-msg'); if (el) el.style.display = 'none'; }

// ── Resolver ítem pendiente ────────────────────────────────────────────────
var _pendDetalleId = null;

function abrirResolverPend(detalleId, desc) {
    _pendDetalleId = detalleId;
    document.getElementById('pend-detalle-id').value = detalleId;
    document.getElementById('pend-producto-id').value = '';
    document.getElementById('pend-modal-desc').textContent = desc;
    document.getElementById('pend-search').value = '';
    document.getElementById('pend-results').innerHTML = '<p style="padding:1rem;font-size:0.78rem;color:#9ca3af;text-align:center;">Escribe para buscar…</p>';
    document.getElementById('pend-submit-btn').disabled = true;
    document.getElementById('pend-submit-btn').style.background = '#9ca3af';
    document.getElementById('pend-submit-btn').style.cursor = 'not-allowed';
    var msg = document.getElementById('pend-modal-msg');
    if (msg) msg.style.display = 'none';
    document.getElementById('pend-modal').style.display = 'flex';
    setTimeout(function() { document.getElementById('pend-search').focus(); }, 60);
}

function cerrarResolverPend() {
    document.getElementById('pend-modal').style.display = 'none';
    _pendDetalleId = null;
}

function pendBuscar(q) {
    q = q.trim().toLowerCase();
    var cont = document.getElementById('pend-results');
    if (!q) {
        cont.innerHTML = '<p style="padding:1rem;font-size:0.78rem;color:#9ca3af;text-align:center;">Escribe para buscar…</p>';
        return;
    }
    if (!_detCatalogo.length) {
        var raw = document.getElementById('det-familias-data');
        if (raw) _detCatalogo = JSON.parse(raw.textContent);
    }
    var all = _detCatalogo.flatMap(function(f) {
        return f.categorias.flatMap(function(c) {
            return c.productos.map(function(p) { return { id: p.id, nombre: p.nombre, unidad: p.unidad }; });
        });
    });
    var matches = all.filter(function(p) { return p.nombre.toLowerCase().includes(q); }).slice(0, 30);
    if (!matches.length) {
        cont.innerHTML = '<p style="padding:1rem;font-size:0.78rem;color:#9ca3af;text-align:center;">Sin resultados.</p>';
        return;
    }
    cont.innerHTML = matches.map(function(p) {
        return '<div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;padding:0.5rem 0.75rem;border-bottom:1px solid #f3f4f6;cursor:pointer;"'
             + ' onmouseover="this.style.background=\'#f9fafb\'" onmouseout="this.style.background=\'\'">'
             + '<div style="flex:1;min-width:0;">'
             + '<p style="font-size:0.82rem;font-weight:500;color:#1f2937;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escDetHtml(p.nombre) + '</p>'
             + (p.unidad ? '<span style="font-size:0.68rem;color:#6366f1;font-weight:600;">' + escDetHtml(p.unidad) + '</span>' : '')
             + '</div>'
             + '<button type="button" onclick="pendSeleccionar(' + p.id + ', ' + JSON.stringify(p.nombre) + ')"'
             + ' style="flex-shrink:0;padding:0.25rem 0.7rem;font-size:0.72rem;font-weight:700;color:#4f46e5;background:#eef2ff;border:1px solid #c7d2fe;border-radius:0.4rem;cursor:pointer;">'
             + 'Seleccionar</button>'
             + '</div>';
    }).join('');
}

function pendSeleccionar(productoId, nombreProducto) {
    document.getElementById('pend-producto-id').value = productoId;
    var submitBtn = document.getElementById('pend-submit-btn');
    submitBtn.disabled = false;
    submitBtn.style.background = '#16a34a';
    submitBtn.style.cursor = 'pointer';
    submitBtn.textContent = '✓ Enlazar: ' + nombreProducto.substring(0, 30) + (nombreProducto.length > 30 ? '…' : '');
    document.getElementById('pend-results').innerHTML = '';
    document.getElementById('pend-search').value = nombreProducto;
}

document.addEventListener('DOMContentLoaded', function() {
    var pendOverlay = document.getElementById('pend-modal');
    if (pendOverlay) pendOverlay.addEventListener('click', function(e) { if (e.target === pendOverlay) cerrarResolverPend(); });
});

// ── Guardar detalles SICD ─────────────────────────────────────────────────
function detGuardar() {
    var detalles = [];
    document.querySelectorAll('#det-tbody .det-row').forEach(function(row) {
        var get = function(f) { var i = row.querySelector('[data-field="' + f + '"]'); return i ? i.value : ''; };
        detalles.push({
            id:                  row.dataset.id || null,
            nombre:              get('nombre'),
            unidad:              get('unidad'),
            cantidad_solicitada: get('cantidad_solicitada'),
            precio_neto:         get('precio_neto'),
            total_neto:          get('total_neto'),
        });
    });

    var btn = document.getElementById('btn-guardar-det');
    btn.disabled = true; btn.textContent = 'Guardando...';

    fetch('{{ route("admin.sicd.detalles.update", $sicd->id) }}', {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ detalles: detalles }),
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        btn.disabled = false; btn.textContent = 'Guardar';
        if (res.ok) {
            detMostrarMsg('Cambios guardados correctamente.', true);
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            detMostrarMsg(res.msg || 'Error al guardar.', false);
        }
    })
    .catch(function() {
        btn.disabled = false; btn.textContent = 'Guardar';
        detMostrarMsg('Error de conexión.', false);
    });
}
</script>
@endpush

@endsection
