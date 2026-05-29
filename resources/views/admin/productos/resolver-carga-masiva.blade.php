@extends('layouts.app')
@section('title', 'Resolver conflictos — Carga masiva')

@section('content')

<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark-title">Resolver conflictos — Carga masiva</h1>
        <p class="text-sm text-gray-500 mt-1 dark-sub">
            {{ count($pendiente['conflictos']) }} producto(s) requieren revisión antes de confirmar.
        </p>
    </div>
    <button type="button" onclick="abrirModalCancelar()"
            class="text-sm text-red-600 hover:underline font-medium mt-1 bg-transparent border-none cursor-pointer p-0">← Cancelar y volver</button>
</div>

{{-- Modal: productos pendientes sin resolver --}}
<div id="modal-pendientes-carga" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55); align-items:center; justify-content:center; padding:1rem;">
    <div class="cm-modal-box" style="border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.3); width:490px; max-width:calc(100vw - 2rem); animation:resolverIn .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.5rem 1.5rem 1.25rem;">
            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.75rem;">
                <svg style="width:1.2rem;height:1.2rem;flex-shrink:0;color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="cm-modal-title" style="font-size:1.05rem; font-weight:700; margin:0;">Productos sin resolver</p>
            </div>
            <p class="cm-modal-sub" style="font-size:0.875rem; margin:0 0 0.6rem;">Los siguientes productos quedaron pendientes:</p>
            <ul id="modal-pendientes-lista" style="margin:0 0 0.75rem; padding:0 0 0 1.1rem; list-style:disc; max-height:180px; overflow-y:auto;"></ul>
            <div class="cm-pendientes-nota" style="font-size:0.82rem; padding:0.55rem 0.75rem; border-radius:0.5rem;">
                Si continúa sin resolver estos productos, quedarán <strong>pendientes</strong> y no afectarán el stock hasta ser asignados.
            </div>
        </div>
        <div class="cm-modal-footer" style="padding:0.75rem 1.5rem 1.25rem; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:0.6rem;">
            <button type="button" onclick="cerrarModalPendientes()"
                    class="cm-btn-cancel" style="padding:0.45rem 1.1rem; border-radius:0.5rem; font-size:0.875rem; font-weight:600; border:1px solid #d1d5db; cursor:pointer;">
                Volver y revisar
            </button>
            <button type="button" onclick="continuarConPendientes()"
                    class="cm-btn-continuar-pendiente" style="padding:0.45rem 1.1rem; border-radius:0.5rem; font-size:0.875rem; font-weight:600; border:none; cursor:pointer;">
                Continuar de todas formas
            </button>
        </div>
    </div>
</div>

{{-- Modal de confirmación de cancelación --}}
<div id="modal-cancelar-carga" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55); align-items:center; justify-content:center; padding:1rem;">
    <div class="cm-modal-box" style="border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.3); width:420px; max-width:calc(100vw - 2rem); animation:resolverIn .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.5rem 1.5rem 1.25rem;">
            <p class="cm-modal-title" style="font-size:1.05rem; font-weight:700; margin:0 0 0.5rem;">¿Cancelar carga masiva?</p>
            <p class="cm-modal-sub" style="font-size:0.875rem; margin:0; line-height:1.5;">
                Se eliminarán todos los datos de la sesión actual (conflictos, boleta temporal y configuración). Esta acción no se puede deshacer.
            </p>
        </div>
        <div class="cm-modal-footer" style="padding:0.75rem 1.5rem 1.25rem; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:0.6rem;">
            <button type="button" onclick="cerrarModalCancelar()"
                    class="cm-btn-cancel" style="padding:0.45rem 1.1rem; border-radius:0.5rem; font-size:0.875rem; font-weight:600; border:1px solid #d1d5db; cursor:pointer;">
                No, continuar
            </button>
            <button type="button" onclick="ejecutarCancelarCarga()"
                    style="padding:0.45rem 1.1rem; border-radius:0.5rem; font-size:0.875rem; font-weight:600; background:#dc2626; color:#fff; border:none; cursor:pointer;">
                Sí, cancelar
            </button>
        </div>
    </div>
</div>

{{-- Banner: productos listos --}}
<div id="cm-ready-banner" class="cm-ready-banner" style="display:none; position:sticky; top:0.5rem; z-index:50; margin-bottom:1rem; padding:0.55rem 1rem; border-radius:0.75rem; align-items:center; gap:0.5rem;">
    <svg style="width:1.1rem;height:1.1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span id="cm-ready-text" style="font-size:0.85rem; font-weight:700;">0 productos listos por confirmar</span>
</div>

{{-- Exactos --}}
@if(count($pendiente['exactos']) > 0)
<div class="rounded-xl p-4 mb-6 cm-exactos-box">
    <p class="text-sm font-semibold mb-2 cm-exactos-title">
        ✓ {{ count($pendiente['exactos']) }} producto(s) enlazados automáticamente (≥ 95 % similitud, sin advertencias)
    </p>
    <ul class="text-xs space-y-0.5 list-disc list-inside cm-exactos-list">
        @foreach($pendiente['exactos'] as $e)
            <li>{{ $e['descripcion'] }}
                <span class="cm-exactos-qty">× {{ $e['cantidad'] }}
                    @if(!empty($e['maneja_presentacion']))
                        <span style="background:#ede9fe;color:#6d28d9;font-size:0.65rem;font-weight:700;padding:1px 5px;border-radius:9999px;margin-left:3px;">
                            📦 {{ $e['tipo_presentacion'] }} × {{ $e['cantidad_presentacion'] }}
                            → {{ $e['cantidad_real'] ?? ($e['cantidad'] * $e['cantidad_presentacion']) }} {{ $e['unidad_base'] }}(s) reales
                        </span>
                    @elseif(!empty($e['unidad_medida_nombre'])) {{ $e['unidad_medida_nombre'] }}
                    @elseif(!empty($e['unidad'])) {{ $e['unidad'] }}
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
</div>
@endif

<form id="cm-form-confirmar" method="POST" action="{{ route('admin.productos.carga.masiva.confirmar') }}">
    @csrf

    <div id="cm-seccion-pendiente" class="space-y-5 mb-6">
        @foreach($pendiente['conflictos'] as $i => $c)
        @php
            $sim       = $c['similitud'] ?? 0;
            $autoEnl   = $sim >= 80 && !empty($c['sugerencia_id']);
            $autoNuevo = $sim < 80  && empty($c['sugerencia_id']);
            $initPid   = ($autoEnl || $sim >= 95) ? ($c['sugerencia_id'] ?? '') : '';

            // Severidad para borde izquierdo
            if ($sim < 80)       $borderColor = '#ef4444'; // rojo
            elseif ($sim < 95)   $borderColor = '#f97316'; // naranja
            else                 $borderColor = '#eab308'; // amarillo (warnings solamente)
        @endphp

        <div id="cm-conflict-wrap-{{ $i }}">

        {{-- Inputs hidden de resolución de unidad --}}
        <input type="hidden" name="resoluciones[{{ $i }}][unidad_accion]"
               id="unid-accion-{{ $i }}"
               value="{{ !empty($c['unidad_warning']['sugerencia_id']) ? 'aceptar' : 'excel' }}">
        <input type="hidden" name="resoluciones[{{ $i }}][unidad_medida_id_manual]"
               id="unid-id-manual-{{ $i }}" value="">

        {{-- Hidden producto --}}
        <input type="hidden" name="resoluciones[{{ $i }}][producto_id]"
               value="{{ $initPid }}" id="input-pid-{{ $i }}">

        <div class="rounded-xl shadow cm-card" style="border-left:4px solid {{ $borderColor }};">

            {{-- ── Cabecera ── --}}
            <div class="cm-card-header" style="padding:1rem 1.25rem 0.75rem;">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold cm-desc">{{ $c['descripcion'] }}</p>
                        <p class="text-xs mt-0.5 cm-meta">
                            Unidad Excel: <strong>{{ $c['unidad'] ?: '—' }}</strong>
                            · Cant: <strong>{{ $c['cantidad'] }}</strong>
                            @if(!empty($c['maneja_presentacion']))
                                <span style="display:inline-flex; align-items:center; gap:3px; background:#ede9fe; color:#6d28d9; font-size:0.7rem; font-weight:700; padding:1px 6px; border-radius:9999px; margin-left:4px;">
                                    📦 {{ $c['tipo_presentacion'] }} × {{ $c['cantidad_presentacion'] }}
                                    → {{ $c['cantidad_real'] ?? ($c['cantidad'] * $c['cantidad_presentacion']) }} {{ $c['unidad_base'] }}(s)
                                </span>
                            @endif
                            @if(!empty($c['precioNeto'])) · P. Neto: ${{ number_format($c['precioNeto'], 0, ',', '.') }} @endif
                            @if(!empty($c['totalNeto']))  · Total: ${{ number_format($c['totalNeto'], 0, ',', '.') }} @endif
                        </p>
                    </div>
                    @php
                        if ($sim >= 95)      [$badgeBg, $badgeColor] = ['#dcfce7','#15803d'];
                        elseif ($sim >= 80)  [$badgeBg, $badgeColor] = ['#fef3c7','#b45309'];
                        else                 [$badgeBg, $badgeColor] = ['#fef2f2','#b91c1c'];
                    @endphp
                    <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full cm-sim-badge"
                          style="background:{{ $badgeBg }}; color:{{ $badgeColor }};">
                        {{ $sim }}% similitud
                    </span>
                </div>
            </div>

            <div style="padding:0 1.25rem 1.25rem;">

                {{-- ── ADVERTENCIA SIN UNIDAD EN EXCEL ────────────────── --}}
                @if(empty($c['unidad']) || trim($c['unidad']) === '')
                <div class="cm-warning-sin-unidad" style="margin-bottom:0.75rem;">
                    <div style="display:flex; align-items:flex-start; gap:0.5rem;">
                        <svg style="width:1rem;height:1rem;flex-shrink:0;margin-top:0.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <p style="font-size:0.78rem; font-weight:700; margin:0;">No se encontró unidad de medida en el Excel para este producto.</p>
                    </div>
                </div>
                @endif

                {{-- ── ADVERTENCIA MONTO ─────────────────────────────── --}}
                @if(!empty($c['monto_warning']))
                <div class="cm-warning-monto" style="margin-bottom:0.75rem;">
                    <div style="display:flex; align-items:flex-start; gap:0.5rem;">
                        <svg style="width:1rem;height:1rem;flex-shrink:0;margin-top:0.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <div>
                            <p style="font-size:0.78rem; font-weight:700; margin:0 0 0.2rem;">Total neto no coincide con el cálculo</p>
                            <p style="font-size:0.75rem; margin:0;">
                                Calculado (cant × P.Neto): <strong>${{ number_format($c['monto_warning']['calculado'], 0, ',', '.') }}</strong>
                                &nbsp;·&nbsp; Excel: <strong>${{ number_format($c['monto_warning']['excel'], 0, ',', '.') }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ── ADVERTENCIA UNIDAD ────────────────────────────── --}}
                @if(!empty($c['unidad_warning']))
                @php $uw = $c['unidad_warning']; @endphp
                <div class="cm-warning-unidad" style="margin-bottom:0.75rem;">
                    <div style="display:flex; align-items:flex-start; gap:0.5rem; margin-bottom:0.5rem;">
                        <svg class="cm-warn-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <div>
                            <p style="font-size:0.78rem; font-weight:700; margin:0 0 0.2rem;">Unidad con diferencias</p>
                            <p style="font-size:0.75rem; margin:0;">
                                Excel: <strong>{{ $uw['excel'] }}</strong>
                                @if($uw['sugerencia']) &nbsp;·&nbsp; BD: <strong>{{ $uw['sugerencia'] }}</strong> @endif
                                @if($uw['similitud'] > 0) &nbsp;·&nbsp; {{ $uw['similitud'] }}% similitud @endif
                            </p>
                        </div>
                    </div>

                    {{-- 3 opciones de resolución de unidad --}}
                    <div style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-left:1.5rem;">
                        @if(!empty($uw['sugerencia_id']))
                        <button type="button"
                                id="unid-btn-aceptar-{{ $i }}"
                                onclick="setUnidAccion({{ $i }}, 'aceptar')"
                                class="cm-unid-btn cm-unid-btn-active"
                                style="font-size:0.73rem; font-weight:600; padding:0.25rem 0.65rem; border-radius:0.375rem; border:1px solid; cursor:pointer; transition:all .15s;">
                            ✓ Usar BD: {{ $uw['sugerencia'] }}
                        </button>
                        @endif
                        <button type="button"
                                id="unid-btn-excel-{{ $i }}"
                                onclick="setUnidAccion({{ $i }}, 'excel')"
                                class="cm-unid-btn {{ empty($uw['sugerencia_id']) ? 'cm-unid-btn-active' : '' }}"
                                style="font-size:0.73rem; font-weight:600; padding:0.25rem 0.65rem; border-radius:0.375rem; border:1px solid; cursor:pointer; transition:all .15s;">
                            Mantener Excel: {{ $uw['excel'] }}
                        </button>
                        <button type="button"
                                id="unid-btn-manual-{{ $i }}"
                                onclick="setUnidAccion({{ $i }}, 'manual')"
                                class="cm-unid-btn"
                                style="font-size:0.73rem; font-weight:600; padding:0.25rem 0.65rem; border-radius:0.375rem; border:1px solid; cursor:pointer; transition:all .15s;">
                            Seleccionar manualmente
                        </button>
                    </div>
                    <div id="unid-manual-wrap-{{ $i }}" style="display:none; margin:0.4rem 0 0 1.5rem;">
                        <select onchange="setUnidManual({{ $i }}, this)"
                                style="font-size:0.78rem; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.25rem 0.5rem; width:200px; outline:none;">
                            <option value="">— Selecciona unidad —</option>
                            @foreach($unidades as $u)
                            <option value="{{ $u->id }}">{{ $u->abreviacion }} — {{ $u->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif

                {{-- ── ADVERTENCIA DISCREPANCIA DE UNIDAD ──────────── --}}
                {{-- Se muestra cuando el Excel trae una unidad reconocida pero distinta a la del producto --}}
                @if(!empty($c['unidad_discrepancia']) && empty($c['unidad_warning']))
                @php $ud = $c['unidad_discrepancia']; @endphp
                <div class="cm-warning-disc" style="margin-bottom:0.75rem;">
                    <div style="display:flex; align-items:flex-start; gap:0.5rem;">
                        <svg style="width:1rem;height:1rem;flex-shrink:0;margin-top:0.1rem;color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <div>
                            <p style="font-size:0.78rem; font-weight:700; margin:0 0 0.25rem;">⚠ Diferencia de unidad detectada</p>
                            <p style="font-size:0.75rem; margin:0 0 0.15rem;">
                                Excel: <strong>{{ $ud['excel_nombre'] }}</strong>
                                &nbsp;·&nbsp;
                                Sistema: <strong>{{ $ud['producto_nombre'] }}</strong>
                            </p>
                            <p style="font-size:0.72rem; color:#92400e; margin:0;">
                                Se registrará con la unidad del Excel. La unidad del producto en el sistema no se modifica aquí.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ── OPCIONES DE PRODUCTO ─────────────────────────── --}}

                {{-- Opción 1: Enlazar a sugerencia --}}
                @if(!empty($c['sugerencia_id']))
                <label class="cm-opt-label cm-opt-hover-indigo" style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem; border-radius:0.5rem; border:1px solid; cursor:pointer; transition:background .15s; margin-bottom:0.5rem;">
                    <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                           class="shrink-0 accent-indigo-600"
                           data-idx="{{ $i }}" data-tipo="sugerencia"
                           data-pid="{{ $c['sugerencia_id'] }}"
                           {{ ($autoEnl || $sim >= 95) ? 'checked' : '' }}
                           onchange="onRadioChange({{ $i }}, 'sugerencia', {{ $c['sugerencia_id'] }})">
                    <div class="flex-1">
                        <p style="font-size:0.85rem; font-weight:600; margin:0;" class="cm-opt-title-indigo">Enlazar al producto más similar</p>
                        <p style="font-size:0.75rem; margin:0.15rem 0 0;" class="cm-opt-sub">{{ $c['sugerencia_nombre'] }}</p>
                    </div>
                    <span style="font-size:0.72rem; font-weight:600;" class="cm-sim-inline">{{ $sim }}%</span>
                </label>
                @endif

                {{-- Opción 2: Enlazar a otro producto --}}
                <label class="cm-opt-label cm-opt-hover-blue" style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem; border-radius:0.5rem; border:1px solid; cursor:pointer; transition:background .15s; margin-bottom:0.5rem;">
                    <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                           class="shrink-0 accent-blue-600"
                           data-idx="{{ $i }}" data-tipo="otro"
                           onchange="onRadioChange({{ $i }}, 'otro', 0)">
                    <div class="flex-1">
                        <p style="font-size:0.85rem; font-weight:600; margin:0;" class="cm-opt-title-blue">Enlazar a otro producto</p>

                        {{-- Live search: buscar producto directamente --}}
                        <div class="cm-search-wrap" id="cm-search-wrap-{{ $i }}" style="position:relative; margin-top:0.5rem;">
                            <div style="position:relative;">
                                <svg id="cm-search-icon-{{ $i }}"
                                     style="position:absolute; left:0.5rem; top:50%; transform:translateY(-50%); width:0.85rem; height:0.85rem; color:#9ca3af; pointer-events:none; flex-shrink:0;"
                                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                                </svg>
                                <input type="text"
                                       id="cm-search-{{ $i }}"
                                       class="cm-search-input"
                                       placeholder="Buscar producto por nombre o código de barras…"
                                       autocomplete="off"
                                       oninput="cmBuscarProducto({{ $i }}, this.value)"
                                       onfocus="cmBuscarProducto({{ $i }}, this.value)"
                                       style="width:100%; padding:0.35rem 0.5rem 0.35rem 1.75rem; font-size:0.78rem; border:1px solid #d1d5db; border-radius:0.375rem; outline:none; box-sizing:border-box;">
                            </div>
                            <div id="cm-search-sel-{{ $i }}" class="cm-search-sel-badge" style="display:none; margin-top:0.3rem; padding:0.3rem 0.5rem; border-radius:0.375rem; font-size:0.75rem; font-weight:600; display:none; align-items:center; gap:0.4rem; flex-wrap:wrap;">
                                <span id="cm-search-sel-name-{{ $i }}"></span>
                                <button type="button" onclick="cmLimpiarBusqueda({{ $i }})"
                                        style="background:none; border:none; cursor:pointer; font-size:0.7rem; padding:0; line-height:1; color:#9ca3af;" title="Quitar selección">✕</button>
                            </div>
                            <div id="cm-search-drop-{{ $i }}" class="cm-search-drop" style="display:none;"></div>
                        </div>

                        {{-- Chip Familia / Categoría / Marca (sólo informativo tras selección) --}}
                        <div id="cm-chips-{{ $i }}" style="display:none; margin-top:0.35rem; display:none; gap:0.3rem; flex-wrap:wrap; align-items:center;">
                            <span id="cm-chip-fam-{{ $i }}"  class="cm-chip"></span>
                            <span style="font-size:0.65rem; color:#9ca3af;">›</span>
                            <span id="cm-chip-cat-{{ $i }}"  class="cm-chip"></span>
                            <span style="font-size:0.65rem; color:#9ca3af;">›</span>
                            <span id="cm-chip-marca-{{ $i }}" class="cm-chip"></span>
                        </div>
                    </div>
                </label>

                {{-- Opción 3: Crear como nuevo producto --}}
                <label class="cm-opt-label cm-opt-hover-emerald" style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem; border-radius:0.5rem; border:1px solid; cursor:pointer; transition:background .15s;">
                    <input type="radio" name="resoluciones[{{ $i }}][accion]" value="nuevo"
                           class="shrink-0 accent-emerald-600"
                           data-idx="{{ $i }}" data-tipo="nuevo"
                           {{ $autoNuevo ? 'checked' : '' }}
                           onchange="onRadioChange({{ $i }}, 'nuevo', 0)">
                    <div class="flex-1 min-w-0">
                        <p style="font-size:0.85rem; font-weight:600; margin:0;" class="cm-opt-title-emerald">Crear como nuevo producto</p>
                        <p style="font-size:0.72rem; margin:0.1rem 0 0;" class="cm-opt-sub">Se agrega con la descripción del Excel.</p>
                        <div id="panel-nuevo-{{ $i }}" class="{{ $autoNuevo ? '' : 'hidden' }}" style="margin-top:0.6rem; display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                            <button type="button"
                                    onclick="resolverAbrirModal({{ $i }}, '{{ addslashes($c['descripcion']) }}')"
                                    style="display:inline-flex; align-items:center; gap:0.35rem; font-size:0.75rem; font-weight:600; color:#fff; background:#2563eb; border:none; border-radius:0.375rem; padding:0.3rem 0.75rem; cursor:pointer;">
                                <svg style="width:0.85rem;height:0.85rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Ingresar datos
                            </button>
                            <span id="resolver-resumen-{{ $i }}" class="text-xs font-medium hidden"
                                  style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; border-radius:0.375rem; padding:0.2rem 0.5rem;"></span>
                        </div>
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_nombre]"        id="resolver-nombre-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][tipo_item]"           id="resolver-tipo-hidden-{{ $i }}" value="{{ $c['tipo_item'] ?? 'producto' }}">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_categoria_id]" id="resolver-cat-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_marca_id]"     id="resolver-marca-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_stock_minimo]"  id="resolver-min-hidden-{{ $i }}"  value="0">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_stock_critico]" id="resolver-crit-hidden-{{ $i }}" value="0">
                        <input type="hidden" name="resoluciones[{{ $i }}][unidad_medida_id]"   id="resolver-unidad-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][contenedor_id]"      id="resolver-cont-hidden-{{ $i }}"  value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_maneja_presentacion]"   id="resolver-pkg-activo-{{ $i }}" value="0">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_tipo_presentacion]"     id="resolver-pkg-tipo-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_cantidad_presentacion]" id="resolver-pkg-cant-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_unidad_base]"           id="resolver-pkg-base-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][arriendo_proveedor_nombre]" id="resolver-arr-proveedor-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][arriendo_fecha_inicio]" id="resolver-arr-inicio-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][arriendo_condicion_termino]" id="resolver-arr-cond-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][arriendo_fecha_termino]" id="resolver-arr-termino-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][arriendo_monto_periodo]" id="resolver-arr-monto-periodo-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][arriendo_monto_total]" id="resolver-arr-monto-total-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][arriendo_documento_referencia]" id="resolver-arr-doc-hidden-{{ $i }}" value="{{ $pendiente['codigo_sicd'] ?? '' }}">
                        <input type="hidden" name="resoluciones[{{ $i }}][arriendo_observacion]" id="resolver-arr-obs-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][mantencion_estado]" id="resolver-mant-estado-hidden-{{ $i }}" value="pendiente">
                        <input type="hidden" name="resoluciones[{{ $i }}][mantencion_proveedor_nombre]" id="resolver-mant-proveedor-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][mantencion_documento_referencia]" id="resolver-mant-doc-hidden-{{ $i }}" value="{{ $pendiente['codigo_sicd'] ?? '' }}">
                        <input type="hidden" name="resoluciones[{{ $i }}][mantencion_observacion]" id="resolver-mant-obs-hidden-{{ $i }}" value="">
                    </div>
                </label>

            </div>{{-- /padding --}}
        </div>{{-- /cm-card --}}

        </div>{{-- /cm-conflict-wrap --}}
        @endforeach
    </div>

    {{-- Por confirmar --}}
    <div id="cm-seccion-confirmados" style="display:none; margin-bottom:1.5rem;">
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem; padding:0 0.25rem;">
            <svg style="width:1.1rem;height:1.1rem;flex-shrink:0;color:#16a34a;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="dark-title" style="font-size:0.875rem; font-weight:700; margin:0;">Por confirmar</p>
            <span id="cm-confirmados-count" style="font-size:0.72rem; font-weight:700; background:#dcfce7; color:#15803d; border-radius:9999px; padding:0.1rem 0.55rem;">0</span>
        </div>
        <div id="cm-seccion-confirmados-lista" style="display:flex; flex-direction:column; gap:1.25rem; opacity:0.65;"></div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('dashboard') }}"
           class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
            Confirmar carga →
        </button>
    </div>
</form>

{{-- ── Modal nuevo producto (wizard 4 pasos) ─────────────────────── --}}
<div id="resolver-modal-nuevo" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,.5); align-items:center; justify-content:center; padding:1rem;">
    <div class="cm-modal-box" style="border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:500px; max-width:calc(100vw - 2rem); max-height:90vh; display:flex; flex-direction:column; overflow:hidden; animation:resolverIn .2s cubic-bezier(.22,.68,0,1.2) both;">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-0 shrink-0">
            <div>
                <p class="cm-modal-title font-bold" style="font-size:1rem; margin:0;">Nuevo producto</p>
                <p id="resolver-modal-nombre" class="cm-modal-nombre-desc" style="font-size:0.8rem; margin:0.2rem 0 0; font-weight:600; word-break:break-word;"></p>
            </div>
            <button onclick="resolverCerrarModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer shrink-0" style="background:none;border:none;font-size:1.25rem;line-height:1;padding:0;">✕</button>
        </div>

        {{-- Step indicator --}}
        <div class="flex items-center gap-2 px-6 pt-4 shrink-0">
            @foreach([1=>'Familia',2=>'Categoría',3=>'Marca',4=>'Producto'] as $n => $label)
            <div class="flex items-center gap-2 {{ $n < 4 ? 'flex-1' : '' }}">
                <div id="rw-circle-{{ $n }}" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0" style="background:#e0e7ff; color:#4338ca;">{{ $n }}</div>
                <span id="rw-label-{{ $n }}" class="text-xs font-medium whitespace-nowrap" style="color:#9ca3af;">{{ $label }}</span>
                @if($n < 4)
                <div id="rw-line-{{ $n }}" class="flex-1 h-px mx-1" style="background:#e5e7eb;"></div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Scrollable content --}}
        <div style="flex:1; overflow-y:auto;">

            {{-- Step 1: Familia --}}
            <div id="rw-step-1" class="px-6 py-5">
                <p class="text-sm font-medium mb-3 cm-modal-label">Tipo de ítem:</p>
                <div style="display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:0.5rem; margin-bottom:1rem;">
                    <button type="button" class="rw-tile" data-rw-tipo="producto" onclick="resolverSeleccionarTipoItem('producto')">Producto</button>
                    <button type="button" class="rw-tile" data-rw-tipo="servicio" onclick="resolverSeleccionarTipoItem('servicio')">Servicio</button>
                    <button type="button" class="rw-tile" data-rw-tipo="mantencion" onclick="resolverSeleccionarTipoItem('mantencion')">Mantención</button>
                    <button type="button" class="rw-tile" data-rw-tipo="arriendo" onclick="resolverSeleccionarTipoItem('arriendo')">Arriendo</button>
                </div>
                <p class="text-sm font-medium mb-3 cm-modal-label">Selecciona la familia:</p>
                <div id="resolver-modal-familias" class="grid grid-cols-2 gap-2"></div>
                <button type="button" onclick="resolverToggleNuevaFam()" id="rw-btn-nueva-fam"
                        style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                    + Nueva familia
                </button>
                <div id="resolver-nueva-fam-form" style="display:none; margin-top:0.25rem;">
                    <div class="flex gap-2 items-center">
                        <input type="text" id="resolver-nueva-fam-input" placeholder="Nombre de la familia"
                               class="flex-1 cm-input border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                               onkeydown="if(event.key==='Enter')resolverCrearFamilia(); if(event.key==='Escape')resolverToggleNuevaFam();">
                        <button type="button" onclick="resolverCrearFamilia()"
                                class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">Guardar</button>
                        <button type="button" onclick="resolverToggleNuevaFam()"
                                class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">✕</button>
                    </div>
                </div>
                <div id="rw-step1-error" class="mt-2 text-xs text-red-600" style="display:none;"></div>
            </div>

            {{-- Step 2: Categoría --}}
            <div id="rw-step-2" class="px-6 py-5" style="display:none;">
                <p class="text-sm font-medium mb-3 cm-modal-label">Selecciona la categoría: <span class="text-red-500">*</span></p>
                <div id="resolver-modal-categorias" class="grid grid-cols-2 gap-2"></div>
                <button type="button" onclick="resolverToggleNuevaCat()" id="rw-btn-nueva-cat"
                        style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                    + Nueva categoría
                </button>
                <div id="resolver-nueva-cat-form" style="display:none; margin-top:0.25rem;">
                    <div class="flex gap-2 items-center">
                        <input type="text" id="resolver-nueva-cat-input" placeholder="Nombre de la categoría"
                               class="flex-1 cm-input border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                               onkeydown="if(event.key==='Enter')resolverCrearCategoria(); if(event.key==='Escape')resolverToggleNuevaCat();">
                        <button type="button" onclick="resolverCrearCategoria()"
                                class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">Guardar</button>
                        <button type="button" onclick="resolverToggleNuevaCat()"
                                class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">✕</button>
                    </div>
                </div>
                <div id="rw-step2-error" class="mt-2 text-xs text-red-600" style="display:none;"></div>
            </div>

            {{-- Step 3: Marca --}}
            <div id="rw-step-3" class="px-6 py-5" style="display:none;">
                <p class="text-sm font-medium mb-3 cm-modal-label">Selecciona la marca: <span class="text-gray-400 font-normal">(opcional)</span></p>
                <div id="resolver-modal-marcas" class="grid grid-cols-2 gap-2"></div>
                <button type="button" onclick="resolverToggleNuevaMarca()" id="rw-btn-nueva-marca"
                        style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                    + Nueva marca
                </button>
                <div id="resolver-nueva-marca-form" style="display:none; margin-top:0.25rem;">
                    <div class="flex gap-2 items-center">
                        <input type="text" id="resolver-nueva-marca-input" placeholder="Nombre de la marca"
                               class="flex-1 cm-input border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                               onkeydown="if(event.key==='Enter')resolverCrearMarca(); if(event.key==='Escape')resolverToggleNuevaMarca();">
                        <button type="button" onclick="resolverCrearMarca()"
                                class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">Guardar</button>
                        <button type="button" onclick="resolverToggleNuevaMarca()"
                                class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">✕</button>
                    </div>
                </div>
                <div id="rw-step3-error" class="mt-2 text-xs text-red-600" style="display:none;"></div>
            </div>

            {{-- Step 4: Datos del producto --}}
            <div id="rw-step-4" class="px-6 py-5" style="display:none;">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1 cm-modal-label">Nombre del producto <span class="text-red-500">*</span></label>
                        <input type="text" id="resolver-modal-nombre-edit" placeholder="EJ: CABLE HDMI 2.1"
                               class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                               style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1 cm-modal-label">Contenedor <span class="text-red-500">*</span></label>
                        <select id="resolver-modal-contenedor" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">— Sin asignar —</option>
                            @foreach($containers as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:inline-flex; align-items:center; gap:0.5rem; cursor:pointer; user-select:none; font-size:0.8rem; font-weight:600;" class="cm-modal-label">
                            <input type="checkbox" id="resolver-modal-pkg-toggle" onchange="resolverTogglePkg()"
                                   style="width:1rem; height:1rem; accent-color:#4338ca; cursor:pointer; flex-shrink:0;">
                            <span>¿Su producto viene en paquete?</span>
                        </label>
                        <div id="resolver-modal-pkg-fields" style="display:none; margin-top:0.5rem; padding:0.6rem 0.75rem; border-radius:0.5rem; background:#f5f3ff;">
                            <div class="grid grid-cols-2 gap-3 mb-2">
                                <div>
                                    <label class="block text-xs font-semibold mb-1 cm-modal-label">Tipo de paquete <span class="text-red-500">*</span></label>
                                    <select id="resolver-modal-pkg-tipo" oninput="resolverActualizarPkgPreview()" onchange="resolverActualizarPkgPreview()"
                                            class="w-full cm-input border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                        <option value="">— Selecciona —</option>
                                        @foreach(['Caja','Paquete','Bolsa','Pack','Kit','Rollo','Resma','Tubo','Bidón','Saco','Pallet','Otro'] as $tp)
                                        <option value="{{ $tp }}">{{ $tp }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1 cm-modal-label">Unidades por paquete <span class="text-red-500">*</span></label>
                                    <input type="number" id="resolver-modal-pkg-cant" min="1" max="9999" placeholder="Ej: 100"
                                           oninput="resolverActualizarPkgPreview()"
                                           class="w-full cm-input border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Unidad base <span class="text-red-500">*</span></label>
                                <select id="resolver-modal-pkg-base" onchange="resolverActualizarPkgPreview()"
                                        class="w-full cm-input border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                    <option value="">— Selecciona —</option>
                                    @foreach($unidades as $u)
                                        @php $nombre = ucwords(strtolower($u->nombre)); @endphp
                                        <option value="{{ $nombre }}">{{ $nombre }}{{ $u->abreviacion ? ' (' . $u->abreviacion . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p id="resolver-modal-pkg-preview" style="font-size:0.75rem; color:#4338ca; font-weight:700; margin:0;"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1 cm-modal-label">Stock mínimo</label>
                            <input type="number" id="resolver-modal-minimo" min="0" value="0"
                                   class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1 cm-modal-label">Stock crítico</label>
                            <input type="number" id="resolver-modal-critico" min="0" value="0"
                                   class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        </div>
                    </div>
                    <div id="resolver-modal-arriendo-fields" style="display:none; padding:0.75rem; border:1px solid #ddd6fe; border-radius:0.65rem; background:#f5f3ff;">
                        <p class="text-xs font-bold mb-2" style="color:#5b21b6;">Control Arriendo</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Proveedor <span class="text-red-500">*</span></label>
                                <input id="resolver-arr-proveedor" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Fecha inicio <span class="text-red-500">*</span></label>
                                <input type="date" id="resolver-arr-inicio" onchange="resolverActualizarDuracionArr()" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Condición de término <span class="text-red-500">*</span></label>
                                <select id="resolver-arr-cond" onchange="resolverToggleCondArr()" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="con_fecha">Con fecha de término definida</option>
                                    <option value="sin_fecha">Sin fecha de término definida</option>
                                </select>
                            </div>
                            <div id="resolver-arr-termino-wrap">
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Fecha término <span class="text-red-500">*</span></label>
                                <input type="date" id="resolver-arr-termino" onchange="resolverActualizarDuracionArr()" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Monto período <span class="text-red-500">*</span></label>
                                <input type="number" min="0" step="0.01" id="resolver-arr-monto-periodo" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Monto total estimado <span class="text-red-500">*</span></label>
                                <input type="number" min="0" step="0.01" id="resolver-arr-monto-total" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Documento referencia <span class="text-red-500">*</span></label>
                                <input id="resolver-arr-doc" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Observación</label>
                                <textarea id="resolver-arr-obs" rows="2" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>
                        <p id="resolver-arr-duracion" class="text-xs font-bold mt-2" style="color:#5b21b6;"></p>
                    </div>
                    <div id="resolver-modal-mantencion-fields" style="display:none; padding:0.75rem; border:1px solid #fde68a; border-radius:0.65rem; background:#fffbeb;">
                        <p class="text-xs font-bold mb-2" style="color:#b45309;">Control Mantención</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Estado inicial</label>
                                <select id="resolver-mant-estado" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="aprobado">Aprobada</option>
                                    <option value="en_proceso">En proceso</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Proveedor</label>
                                <input id="resolver-mant-proveedor" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Documento referencia <span class="text-red-500">*</span></label>
                                <input id="resolver-mant-doc" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold mb-1 cm-modal-label">Observación</label>
                                <textarea id="resolver-mant-obs" rows="2" class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div id="resolver-modal-error" class="text-xs text-red-600" style="display:none;"></div>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="cm-modal-footer flex justify-between items-center px-6 py-4 shrink-0" style="border-top:1px solid #f3f4f6;">
            <button type="button" id="rw-btn-atras" onclick="resolverAtras()" style="display:none;"
                    class="cm-btn-cancel px-4 py-2 text-sm font-medium rounded-lg border border-gray-200">← Atrás</button>
            <div class="ml-auto flex gap-2">
                <button type="button" onclick="resolverCerrarModal()"
                        class="cm-btn-cancel px-4 py-2 text-sm font-medium rounded-lg border border-gray-200">Cancelar</button>
                <button type="button" id="rw-btn-siguiente" onclick="resolverSiguiente()"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Siguiente →</button>
                <button type="button" id="rw-btn-confirmar" onclick="resolverConfirmarModal()" style="display:none;"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@push('head')
<style>
@keyframes resolverIn { from{opacity:0;transform:scale(.95) translateY(-8px)} to{opacity:1;transform:none} }

/* ── Día ─────────────────────────────────────────────────────────── */
.dark-title { color:#1e293b; }
.dark-sub   { color:#64748b; }

.cm-exactos-box   { background:#eff6ff; border:1px solid #bfdbfe; }
.cm-exactos-title { color:#1d4ed8; }
.cm-exactos-list  { color:#2563eb; }
.cm-exactos-qty   { color:#93c5fd; }

.cm-card        { background:#fff; }
.cm-card-header { }
.cm-desc        { color:#1e293b; }
.cm-meta        { color:#64748b; }
.cm-sim-badge   { }
.cm-sim-inline  { color:#6366f1; }

@keyframes cm-pulse-icon {
    0%, 100% { opacity:1;   transform:scale(1); }
    50%       { opacity:0.6; transform:scale(1.22); }
}
.cm-warn-icon {
    width:1.4rem; height:1.4rem; flex-shrink:0; margin-top:0.05rem;
    color:#ea580c;
    animation:cm-pulse-icon 1.6s ease-in-out infinite;
}
html.dark .cm-warn-icon { color:#fb923c; }

.cm-warning-monto {
    background:#fef3c7; border:1px solid #fcd34d; border-radius:0.5rem;
    padding:0.6rem 0.75rem; color:#92400e;
}
.cm-warning-unidad {
    background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.5rem;
    padding:0.6rem 0.75rem; color:#1e40af;
}
.cm-warning-disc {
    background:#fffbeb; border:1px solid #fcd34d; border-radius:0.5rem;
    padding:0.6rem 0.75rem; color:#92400e;
}
.cm-warning-sin-unidad {
    background:#fef3c7; border:1px solid #fbbf24; border-radius:0.5rem;
    padding:0.6rem 0.75rem; color:#92400e;
}

/* Botones unidad */
.cm-unid-btn {
    background:#f8fafc; border-color:#cbd5e1; color:#475569;
}
.cm-unid-btn-active {
    background:#dbeafe; border-color:#93c5fd; color:#1d4ed8;
}
.cm-unid-btn:hover { background:#e2e8f0; }

/* Opciones de producto */
.cm-opt-label { border-color:#e5e7eb; background:transparent; }
.cm-opt-hover-indigo:hover { background:#eef2ff; border-color:#a5b4fc; }
.cm-opt-hover-blue:hover   { background:#eff6ff; border-color:#93c5fd; }
.cm-opt-hover-emerald:hover{ background:#ecfdf5; border-color:#6ee7b7; }
.cm-opt-hover-amber:hover  { background:#fffbeb; border-color:#fbbf24; }
.cm-opt-title-indigo { color:#4338ca; }
.cm-opt-title-blue   { color:#1d4ed8; }
.cm-opt-title-emerald{ color:#065f46; }
.cm-opt-title-amber  { color:#d97706; }
.cm-opt-sub          { color:#64748b; }

.cm-sel   { background:#fff; color:#1e293b; }

/* Modal */
.cm-modal-box    { background:#fff; }
.cm-modal-title  { color:#1f2937; }
.cm-modal-sub    { color:#374151; }
.cm-modal-label       { color:#374151; }
.cm-modal-nombre-desc { color:#6d28d9; }
.cm-modal-footer { background:#fff; }
.cm-btn-cancel   { background:#f3f4f6; color:#374151; }
.cm-input        { background:#fff; color:#1f2937; }

/* ── Noche ───────────────────────────────────────────────────────── */
html.dark .dark-title { color:#f1f5f9; }
html.dark .dark-sub   { color:#94a3b8; }

html.dark .cm-exactos-box   { background:#0c1a2e; border-color:#1e40af; }
html.dark .cm-exactos-title { color:#93c5fd; }
html.dark .cm-exactos-list  { color:#60a5fa; }
html.dark .cm-exactos-qty   { color:#1e3a8a; }

html.dark .cm-card   { background:#1e293b; }
html.dark .cm-desc   { color:#f1f5f9; }
html.dark .cm-meta   { color:#94a3b8; }
html.dark .cm-sim-inline { color:#a5b4fc; }

html.dark .cm-warning-monto  {
    background:#422006; border-color:#92400e; color:#fcd34d;
}
html.dark .cm-warning-unidad {
    background:#172554; border-color:#1e40af; color:#93c5fd;
}
html.dark .cm-warning-disc {
    background:#422006; border-color:#92400e; color:#fcd34d;
}
html.dark .cm-warning-sin-unidad {
    background:#422006; border-color:#92400e; color:#fcd34d;
}

html.dark .cm-unid-btn        { background:#334155; border-color:#475569; color:#cbd5e1; }
html.dark .cm-unid-btn-active { background:#1e3a5f; border-color:#2563eb; color:#93c5fd; }
html.dark .cm-unid-btn:hover  { background:#475569; }

html.dark .cm-opt-label               { border-color:#334155; }
html.dark .cm-opt-hover-indigo:hover  { background:#1e1b4b; border-color:#4338ca; }
html.dark .cm-opt-hover-blue:hover    { background:#172554; border-color:#1d4ed8; }
html.dark .cm-opt-hover-emerald:hover { background:#052e16; border-color:#065f46; }
html.dark .cm-opt-hover-amber:hover   { background:#451a03; border-color:#d97706; }
html.dark .cm-opt-title-indigo  { color:#a5b4fc; }
html.dark .cm-opt-title-blue    { color:#93c5fd; }
html.dark .cm-opt-title-emerald { color:#6ee7b7; }
html.dark .cm-opt-title-amber   { color:#fbbf24; }
html.dark .cm-opt-sub           { color:#64748b; }

html.dark .cm-sel { background:#0f172a; color:#e2e8f0; border-color:#334155; }

html.dark .cm-modal-box    { background:#1e293b; }
html.dark .cm-modal-title  { color:#f1f5f9; }
html.dark .cm-modal-sub    { color:#cbd5e1; }
html.dark .cm-modal-label       { color:#94a3b8; }
html.dark .cm-modal-nombre-desc { color:#a5b4fc; }
html.dark .cm-modal-footer { background:#1e293b; border-color:#334155; }
html.dark .cm-btn-cancel   { background:#334155; color:#cbd5e1; border-color:#475569; }
html.dark .cm-input        { background:#0f172a; color:#e2e8f0; border-color:#334155; }

/* Package preview */
html.dark #resolver-modal-pkg-preview { color:#93c5fd; }

/* Cancel modal overlay close on backdrop */
#modal-cancelar-carga { cursor: default; }

/* "Por confirmar" count badge dark mode */
html.dark #cm-confirmados-count { background:#052e16; color:#86efac; }

/* ── Wizard tiles ───────────────────────────────────────────────── */
.rw-tile {
    font-size:0.875rem; font-weight:500; padding:.75rem 1rem;
    border-radius:.75rem; border:1px solid #d1d5db;
    background:#fff; color:#374151;
    cursor:pointer; transition:all .15s; text-align:left; width:100%;
}
.rw-tile:hover { border-color:#a5b4fc; color:#4338ca; }
.rw-tile.rw-tile-sel { background:#4f46e5; color:#fff; border-color:#4f46e5; }
.rw-tile.rw-tile-sel:hover { background:#4338ca; border-color:#4338ca; }

html.dark .rw-tile { background:#0f172a; color:#e2e8f0; border-color:#334155; }
html.dark .rw-tile:hover { border-color:#7c3aed; color:#c4b5fd; }
html.dark .rw-tile.rw-tile-sel { background:#4f46e5; color:#fff; border-color:#4f46e5; }
html.dark .rw-tile.rw-tile-sel:hover { background:#4338ca; border-color:#4338ca; }

/* Sin marca — dashed variant */
.rw-tile-sin {
    font-size:0.875rem; font-weight:500; padding:.75rem 1rem;
    border-radius:.75rem; border:1px dashed #d1d5db;
    background:transparent; color:#6b7280;
    cursor:pointer; transition:all .15s; text-align:left; width:100%;
}
.rw-tile-sin:hover { border-color:#a5b4fc; color:#4338ca; }
.rw-tile-sin.rw-tile-sel { border:2px solid #6366f1; background:#e0e7ff; color:#3730a3; }
html.dark .rw-tile-sin { border-color:#475569; color:#94a3b8; }
html.dark .rw-tile-sin:hover { border-color:#7c3aed; color:#c4b5fd; }
html.dark .rw-tile-sin.rw-tile-sel { border-color:#6366f1; background:#1e1b4b; color:#a5b4fc; }

/* ── Modal pendientes ─────────────────────────────────────────── */
.cm-pendientes-nota {
    background:rgba(251,191,36,.1); border:1px solid rgba(251,191,36,.35); color:#92400e;
}
.cm-btn-continuar-pendiente { background:#d97706; color:#fff; }
.cm-btn-continuar-pendiente:hover { background:#b45309; }
html.dark .cm-pendientes-nota {
    background:rgba(251,191,36,.07); border-color:rgba(251,191,36,.25); color:#fcd34d;
}
html.dark .cm-btn-continuar-pendiente { background:#d97706; color:#fff; }
html.dark .cm-btn-continuar-pendiente:hover { background:#b45309; }

/* ── Fade-out al mover card a confirmados ─────────────────────── */
@keyframes cmFadeOut {
    0%   { opacity:1; transform:translateY(0)   scale(1); }
    100% { opacity:0; transform:translateY(14px) scale(.98); }
}

/* ── Banner listos ────────────────────────────────────────────── */
.cm-ready-banner {
    background:#f0fdf4; border:1px solid #86efac; color:#15803d;
    box-shadow:0 2px 8px rgba(22,163,74,.12);
}
html.dark .cm-ready-banner {
    background:#052e16; border-color:#166534; color:#86efac;
    box-shadow:0 2px 8px rgba(0,0,0,.3);
}

/* ── Live search — Enlazar a otro producto ────────────────────── */
.cm-search-input { background:#fff; color:#1f2937; }
.cm-search-input:focus { border-color:#6366f1 !important; box-shadow:0 0 0 2px rgba(99,102,241,.15); }
.cm-search-drop {
    position:absolute; left:0; right:0; top:calc(100% + 3px); z-index:200;
    background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem;
    box-shadow:0 8px 24px rgba(0,0,0,.1); max-height:200px; overflow-y:auto;
}
.cm-search-drop-item {
    padding:0.4rem 0.65rem; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:0.78rem;
}
.cm-search-drop-item:last-child { border-bottom:none; }
.cm-search-drop-item:hover { background:#eff6ff; }
.cm-search-drop-empty { padding:0.5rem 0.65rem; color:#9ca3af; font-style:italic; font-size:0.78rem; }
.cm-search-sel-badge { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:0.375rem; }
.cm-chip { font-size:0.68rem; font-weight:600; padding:0.1rem 0.45rem; border-radius:0.25rem; background:#e0e7ff; color:#3730a3; }

html.dark .cm-search-input { background:#0f172a; color:#e2e8f0; border-color:#334155; }
html.dark .cm-search-input:focus { border-color:#6366f1 !important; }
html.dark .cm-search-drop { background:#1e293b; border-color:#334155; box-shadow:0 8px 24px rgba(0,0,0,.4); }
html.dark .cm-search-drop-item { border-color:#334155; color:#e2e8f0; }
html.dark .cm-search-drop-item:hover { background:#172554; }
html.dark .cm-search-drop-empty { color:#64748b; }
html.dark .cm-search-sel-badge { background:#172554; color:#93c5fd; border-color:#1d4ed8; }
html.dark .cm-chip { background:#1e1b4b; color:#a5b4fc; }
</style>
@endpush

@push('scripts')
<script>
var resolverFamilias = {!! json_encode($familias->map(fn($f) => [
    'id'         => $f->id,
    'nombre'     => $f->nombre,
    'tipo'       => $f->tipo,
    'categorias' => $f->categorias->map(fn($c) => [
        'id'     => $c->id,
        'nombre' => $c->nombre,
        'marcas' => $c->marcas->map(fn($m) => ['id' => $m->id, 'nombre' => $m->nombre])->values(),
    ])->values(),
])->values(), JSON_HEX_TAG | JSON_HEX_AMP) !!};

// Derived from familia.tipo — no hardcoded IDs
var _sinFamFam  = resolverFamilias.find(function(f) { return f.tipo === 'sin_familia'; });
var _pypFam     = resolverFamilias.find(function(f) { return f.tipo === 'partes_piezas'; });
var SIN_FAMILIA_ID = _sinFamFam ? _sinFamFam.id : null;

var _resolverModalIdx  = null;
var _resolverFamiliaId = null;
var _resolverCatId     = null;
var _resolverMarcaId   = null;
var _resolverTipoItem  = 'producto';
var _rwStep = 1;

function resolverTipoItemLabel(tipo) {
    if (tipo === 'servicio') return 'Servicio';
    if (tipo === 'mantencion') return 'Mantención';
    if (tipo === 'arriendo') return 'Arriendo';
    return 'Producto';
}

function resolverEsFisico() {
    return _resolverTipoItem === 'producto';
}

function resolverSeleccionarTipoItem(tipo) {
    _resolverTipoItem = ['producto', 'servicio', 'mantencion', 'arriendo'].indexOf(tipo) >= 0 ? tipo : 'producto';
    document.querySelectorAll('[data-rw-tipo]').forEach(function(btn) {
        btn.classList.toggle('rw-tile-sel', btn.dataset.rwTipo === _resolverTipoItem);
    });
    resolverToggleCamposPorTipo();
}

function resolverToggleCamposPorTipo() {
    var fisico = resolverEsFisico();
    var cont = document.getElementById('resolver-modal-contenedor')?.closest('div');
    var pkg = document.getElementById('resolver-modal-pkg-toggle')?.closest('div');
    var stocks = document.getElementById('resolver-modal-minimo')?.closest('.grid');
    var arr = document.getElementById('resolver-modal-arriendo-fields');
    var mant = document.getElementById('resolver-modal-mantencion-fields');
    if (cont) cont.style.display = fisico ? '' : 'none';
    if (pkg) pkg.style.display = fisico ? '' : 'none';
    if (stocks) stocks.style.display = fisico ? 'grid' : 'none';
    if (arr) arr.style.display = _resolverTipoItem === 'arriendo' ? 'block' : 'none';
    if (mant) mant.style.display = _resolverTipoItem === 'mantencion' ? 'block' : 'none';
    if (!fisico) {
        document.getElementById('resolver-modal-contenedor').value = '';
        document.getElementById('resolver-modal-minimo').value = '0';
        document.getElementById('resolver-modal-critico').value = '0';
        document.getElementById('resolver-modal-pkg-toggle').checked = false;
        resolverTogglePkg();
    }
    if (_resolverTipoItem === 'arriendo') {
        resolverPrepararArrCampos();
    }
    if (_resolverTipoItem === 'mantencion') {
        resolverPrepararMantCampos();
    }
}

function resolverToggleCondArr() {
    var cond = document.getElementById('resolver-arr-cond')?.value || 'con_fecha';
    var wrap = document.getElementById('resolver-arr-termino-wrap');
    var input = document.getElementById('resolver-arr-termino');
    if (wrap) wrap.style.display = cond === 'con_fecha' ? '' : 'none';
    if (input && cond !== 'con_fecha') input.value = '';
    resolverActualizarDuracionArr();
}

function resolverActualizarDuracionArr() {
    var cond = document.getElementById('resolver-arr-cond')?.value || 'con_fecha';
    var out = document.getElementById('resolver-arr-duracion');
    if (!out) return;
    if (cond !== 'con_fecha') {
        out.textContent = 'Estado inicial: Activo sin fecha de término';
        return;
    }
    var inicio = document.getElementById('resolver-arr-inicio')?.value;
    var termino = document.getElementById('resolver-arr-termino')?.value;
    if (!inicio || !termino) { out.textContent = ''; return; }
    var dias = Math.floor((new Date(termino + 'T00:00:00') - new Date(inicio + 'T00:00:00')) / 86400000) + 1;
    out.textContent = dias > 0 ? ('Duración estimada: ' + dias + ' día(s)') : '';
}

function resolverPrepararArrCampos() {
    var idx = _resolverModalIdx;
    if (idx === null) return;
    var doc = document.getElementById('resolver-arr-doc');
    if (doc && !doc.value) doc.value = document.getElementById('resolver-arr-doc-hidden-' + idx)?.value || '';
    if (doc) doc.readOnly = !!doc.value;
    resolverToggleCondArr();
}

function resolverPrepararMantCampos() {
    var idx = _resolverModalIdx;
    if (idx === null) return;
    var doc = document.getElementById('resolver-mant-doc');
    if (doc && !doc.value) doc.value = document.getElementById('resolver-mant-doc-hidden-' + idx)?.value || '';
    if (doc) doc.readOnly = !!doc.value;
}

function resolverGoStep(n) {
    _rwStep = n;
    var dark = document.documentElement.classList.contains('dark');
    [1,2,3,4].forEach(function(i) {
        document.getElementById('rw-step-' + i).style.display = i === n ? '' : 'none';
        var circle = document.getElementById('rw-circle-' + i);
        var label  = document.getElementById('rw-label-' + i);
        var line   = document.getElementById('rw-line-' + i);
        if (i < n) {
            circle.style.background = '#a5b4fc'; circle.style.color = '#3730a3';
            if (label) { label.style.color = '#9ca3af'; label.style.fontWeight = '500'; }
            if (line)  line.style.background = '#a5b4fc';
        } else if (i === n) {
            circle.style.background = '#7c3aed'; circle.style.color = '#fff';
            if (label) { label.style.color = '#7c3aed'; label.style.fontWeight = '700'; }
            if (line)  line.style.background = dark ? '#334155' : '#e5e7eb';
        } else {
            circle.style.background = dark ? '#1e293b' : '#e0e7ff';
            circle.style.color      = dark ? '#64748b' : '#4338ca';
            if (label) { label.style.color = '#9ca3af'; label.style.fontWeight = '500'; }
            if (line)  line.style.background = dark ? '#334155' : '#e5e7eb';
        }
    });
    document.getElementById('rw-btn-atras').style.display     = n > 1 ? '' : 'none';
    document.getElementById('rw-btn-siguiente').style.display = n < 4 ? '' : 'none';
    document.getElementById('rw-btn-confirmar').style.display = n === 4 ? '' : 'none';
    [1,2,3].forEach(function(i) {
        var e = document.getElementById('rw-step' + i + '-error');
        if (e) e.style.display = 'none';
    });
    document.getElementById('resolver-modal-error').style.display = 'none';
    if (n === 1) { resolverSeleccionarTipoItem(_resolverTipoItem); resolverRenderFamilias(); }
    if (n === 2) resolverRenderCategorias();
    if (n === 3) resolverRenderMarcas(resolverBuscarCat(_resolverCatId));
}

function resolverSiguiente() {
    if (_rwStep === 2 && !_resolverCatId) {
        var e2 = document.getElementById('rw-step2-error');
        e2.textContent = 'Selecciona una categoría para continuar.';
        e2.style.display = 'block';
        return;
    }
    resolverGoStep(_rwStep + 1);
}

function resolverAtras() {
    resolverGoStep(_rwStep - 1);
}

// Package data pre-loaded from Excel (indexed by conflict position)
var resolverPkgData = {!! json_encode(collect($pendiente['conflictos'])->mapWithKeys(fn($c, $i) => [$i => [
    'activo' => !empty($c['maneja_presentacion']),
    'tipo'   => $c['tipo_presentacion'] ?? '',
    'cant'   => $c['cantidad_presentacion'] ? (int)$c['cantidad_presentacion'] : null,
    'base'   => $c['unidad_base'] ?? '',
]])->all(), JSON_HEX_TAG | JSON_HEX_AMP) !!};

/* ── Resolución de unidad ─────────────────────────────────────────── */
function setUnidAccion(idx, accion) {
    document.getElementById('unid-accion-' + idx).value = accion;

    var map = { 'aceptar': 'unid-btn-aceptar-', 'excel': 'unid-btn-excel-', 'manual': 'unid-btn-manual-' };
    Object.values(map).forEach(function(pfx) {
        var el = document.getElementById(pfx + idx);
        if (el) { el.classList.remove('cm-unid-btn-active'); }
    });
    var active = document.getElementById(map[accion] + idx);
    if (active) active.classList.add('cm-unid-btn-active');

    var wrap = document.getElementById('unid-manual-wrap-' + idx);
    if (wrap) wrap.style.display = (accion === 'manual') ? 'block' : 'none';
}

function setUnidManual(idx, sel) {
    document.getElementById('unid-id-manual-' + idx).value = sel.value;
}

/* ── Live search — Enlazar a otro producto ───────────────────────── */
var CM_BUSCAR_URL     = '{{ route("admin.productos.buscar-enlace") }}';
var _cmSearchTimers   = {};
var _cmSelectedProduct = {};

function cmBuscarProducto(idx, val) {
    val = (val || '').trim();
    if (_cmSelectedProduct[idx]) return;
    clearTimeout(_cmSearchTimers[idx]);
    var drop = document.getElementById('cm-search-drop-' + idx);
    if (!drop) return;
    if (val.length < 2) { drop.style.display = 'none'; drop.innerHTML = ''; return; }
    _cmSearchTimers[idx] = setTimeout(function() {
        fetch(CM_BUSCAR_URL + '?q=' + encodeURIComponent(val), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) { return r.json(); })
          .then(function(data) { cmRenderDropdown(idx, data); })
          .catch(function() {});
    }, 280);
}

function cmRenderDropdown(idx, results) {
    var drop = document.getElementById('cm-search-drop-' + idx);
    if (!drop) return;
    drop.innerHTML = '';
    if (!results || !results.length) {
        drop.innerHTML = '<div class="cm-search-drop-empty">Sin resultados</div>';
        drop.style.display = 'block'; return;
    }
    results.forEach(function(prod) {
        var el = document.createElement('div');
        el.className = 'cm-search-drop-item';
        el.innerHTML = '<div style="font-weight:600;">' + cmEscHtml(prod.nombre) + '</div>' +
            '<div style="font-size:0.68rem;color:#6b7280;margin-top:1px;">' +
            cmEscHtml(prod.familia_nombre) + ' › ' + cmEscHtml(prod.categoria_nombre) + ' · ' + cmEscHtml(prod.marca_nombre) + '</div>';
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            cmSeleccionarProducto(idx, prod);
        });
        drop.appendChild(el);
    });
    drop.style.display = 'block';
}

function cmSeleccionarProducto(idx, prod) {
    _cmSelectedProduct[idx] = prod;
    var drop = document.getElementById('cm-search-drop-' + idx);
    if (drop) { drop.style.display = 'none'; drop.innerHTML = ''; }
    var input = document.getElementById('cm-search-' + idx);
    var icon  = document.getElementById('cm-search-icon-' + idx);
    if (input) { input.value = ''; input.style.display = 'none'; }
    if (icon)  icon.style.display = 'none';
    var badge = document.getElementById('cm-search-sel-' + idx);
    var badgeName = document.getElementById('cm-search-sel-name-' + idx);
    if (badge) badge.style.display = 'flex';
    if (badgeName) badgeName.textContent = prod.nombre;
    var chips = document.getElementById('cm-chips-' + idx);
    if (chips) chips.style.display = 'flex';
    var chipFam   = document.getElementById('cm-chip-fam-'   + idx);
    var chipCat   = document.getElementById('cm-chip-cat-'   + idx);
    var chipMarca = document.getElementById('cm-chip-marca-' + idx);
    if (chipFam)   chipFam.textContent   = prod.familia_nombre;
    if (chipCat)   chipCat.textContent   = prod.categoria_nombre;
    if (chipMarca) chipMarca.textContent = prod.marca_nombre;
    onSelectOtro(idx, prod.id);
}

function cmLimpiarBusqueda(idx) {
    _cmSelectedProduct[idx] = null;
    var badge = document.getElementById('cm-search-sel-' + idx);
    if (badge) badge.style.display = 'none';
    var input = document.getElementById('cm-search-' + idx);
    var icon  = document.getElementById('cm-search-icon-' + idx);
    if (input) { input.style.display = ''; input.value = ''; input.focus(); }
    if (icon)  icon.style.display = '';
    var chips = document.getElementById('cm-chips-' + idx);
    if (chips) chips.style.display = 'none';
    document.getElementById('input-pid-' + idx).value = '';
}

function cmEscHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('click', function(e) {
    document.querySelectorAll('[id^="cm-search-drop-"]').forEach(function(drop) {
        var idx  = drop.id.replace('cm-search-drop-', '');
        var wrap = document.getElementById('cm-search-wrap-' + idx);
        if (wrap && !wrap.contains(e.target)) drop.style.display = 'none';
    });
});

/* ── Mover card a "Por confirmar" ─────────────────────────────────── */
function resolverMarcarListo(idx, animate) {
    var wrap    = document.getElementById('cm-conflict-wrap-' + idx);
    var lista   = document.getElementById('cm-seccion-confirmados-lista');
    var seccion = document.getElementById('cm-seccion-confirmados');
    if (!wrap || !lista || !seccion) return;
    if (wrap.parentElement === lista) return;
    if (wrap.dataset.animating) return;

    function doMove() {
        wrap.style.animation = '';
        delete wrap.dataset.animating;
        lista.appendChild(wrap);
        seccion.style.display = '';
        var n = lista.children.length;
        document.getElementById('cm-confirmados-count').textContent = n;
        var banner     = document.getElementById('cm-ready-banner');
        var bannerText = document.getElementById('cm-ready-text');
        if (banner && bannerText) {
            bannerText.textContent = n + ' producto' + (n !== 1 ? 's' : '') + ' listo' + (n !== 1 ? 's' : '') + ' por confirmar';
            banner.style.display = 'flex';
        }
    }

    if (animate === false) { doMove(); return; }

    wrap.dataset.animating = '1';
    wrap.style.animation = 'cmFadeOut 0.38s ease forwards';
    wrap.addEventListener('animationend', doMove, { once: true });
}

/* ── Cambio de radio de acción de producto ────────────────────────── */
function onRadioChange(idx, tipo, pid) {
    document.getElementById('input-pid-' + idx).value = (tipo === 'sugerencia') ? pid : '';
    var panel = document.getElementById('panel-nuevo-' + idx);
    if (tipo === 'nuevo') {
        panel.classList.remove('hidden'); panel.style.display = 'flex';
    } else {
        panel.classList.add('hidden'); panel.style.display = 'none';
    }
    if (tipo === 'sugerencia' && pid) resolverMarcarListo(idx);
}

function onSelectOtro(idx, value) {
    var radios = document.querySelectorAll('input[name="resoluciones[' + idx + '][accion]"]');
    radios.forEach(function(r) { if (r.dataset.tipo === 'otro') r.checked = true; });
    document.getElementById('input-pid-' + idx).value = value;
    var panel = document.getElementById('panel-nuevo-' + idx);
    panel.classList.add('hidden'); panel.style.display = 'none';
    if (value) resolverMarcarListo(idx);
}

/* ── Modal nuevo producto ─────────────────────────────────────────── */
function resolverAbrirModal(idx, nombre) {
    _resolverModalIdx  = idx;
    _resolverFamiliaId = null;
    _resolverCatId     = null;
    _resolverMarcaId   = null;
    _resolverTipoItem  = document.getElementById('resolver-tipo-hidden-' + idx).value || 'producto';

    document.getElementById('resolver-modal-nombre').textContent = nombre;
    document.getElementById('resolver-modal-error').style.display = 'none';

    // Restore saved name or use Excel description
    var savedNombre = document.getElementById('resolver-nombre-hidden-' + idx).value;
    document.getElementById('resolver-modal-nombre-edit').value = savedNombre || nombre;

    // Restore step 4 fields
    document.getElementById('resolver-modal-minimo').value  = document.getElementById('resolver-min-hidden-'  + idx).value || '0';
    document.getElementById('resolver-modal-critico').value = document.getElementById('resolver-crit-hidden-' + idx).value || '0';
    document.getElementById('resolver-modal-contenedor').value = document.getElementById('resolver-cont-hidden-' + idx).value || '';
    document.getElementById('resolver-arr-proveedor').value = document.getElementById('resolver-arr-proveedor-hidden-' + idx).value || '';
    document.getElementById('resolver-arr-inicio').value = document.getElementById('resolver-arr-inicio-hidden-' + idx).value || '';
    document.getElementById('resolver-arr-cond').value = document.getElementById('resolver-arr-cond-hidden-' + idx).value || 'con_fecha';
    document.getElementById('resolver-arr-termino').value = document.getElementById('resolver-arr-termino-hidden-' + idx).value || '';
    document.getElementById('resolver-arr-monto-periodo').value = document.getElementById('resolver-arr-monto-periodo-hidden-' + idx).value || '';
    document.getElementById('resolver-arr-monto-total').value = document.getElementById('resolver-arr-monto-total-hidden-' + idx).value || '';
    document.getElementById('resolver-arr-doc').value = document.getElementById('resolver-arr-doc-hidden-' + idx).value || '';
    document.getElementById('resolver-arr-obs').value = document.getElementById('resolver-arr-obs-hidden-' + idx).value || '';

    // Package fields: restore previous user selection or pre-populate from Excel
    var savedActivo = document.getElementById('resolver-pkg-activo-' + idx).value === '1';
    var savedTipo   = document.getElementById('resolver-pkg-tipo-' + idx).value;
    var savedCant   = document.getElementById('resolver-pkg-cant-' + idx).value;
    var savedBase   = document.getElementById('resolver-pkg-base-' + idx).value;
    var pkgEx       = resolverPkgData[idx] || {};

    var useActivo = savedActivo || (!savedTipo && pkgEx.activo);
    var useTipo   = savedTipo   || (pkgEx.activo ? (pkgEx.tipo || '') : '');
    var useCant   = savedCant   || (pkgEx.activo ? (pkgEx.cant || '') : '');
    var useBase   = savedBase   || (pkgEx.activo ? (pkgEx.base || '') : '');

    document.getElementById('resolver-modal-pkg-toggle').checked = useActivo;
    document.getElementById('resolver-modal-pkg-tipo').value = useTipo;
    document.getElementById('resolver-modal-pkg-cant').value = useCant;
    document.getElementById('resolver-modal-pkg-base').value = useBase;
    document.getElementById('resolver-modal-pkg-fields').style.display = useActivo ? 'grid' : 'none';
    resolverActualizarPkgPreview();
    resolverSeleccionarTipoItem(_resolverTipoItem);

    document.getElementById('resolver-modal-nuevo').style.display = 'flex';
    resolverGoStep(1);
}

function resolverCerrarModal() {
    document.getElementById('resolver-modal-nuevo').style.display = 'none';
}

function resolverTogglePkg() {
    var activo = document.getElementById('resolver-modal-pkg-toggle').checked;
    document.getElementById('resolver-modal-pkg-fields').style.display = activo ? 'block' : 'none';
    if (!activo) {
        document.getElementById('resolver-modal-pkg-preview').textContent = '';
    } else {
        resolverActualizarPkgPreview();
        document.getElementById('resolver-modal-pkg-tipo').focus();
    }
}

function resolverActualizarPkgPreview() {
    var tipo = (document.getElementById('resolver-modal-pkg-tipo').value || '').trim();
    var cant = parseInt(document.getElementById('resolver-modal-pkg-cant').value) || 0;
    var base = (document.getElementById('resolver-modal-pkg-base').value || '').trim();
    var prev = document.getElementById('resolver-modal-pkg-preview');
    prev.textContent = (tipo && cant >= 1 && base)
        ? '1 ' + tipo + ' = ' + cant + ' ' + base + (cant !== 1 && base.slice(-1).toLowerCase() !== 's' ? 's' : '')
        : '';
}

function resolverRenderFamilias() {
    var cont = document.getElementById('resolver-modal-familias');
    cont.innerHTML = '';
    resolverFamilias.forEach(function(f) {
        var btn = document.createElement('button');
        btn.type = 'button'; btn.textContent = f.nombre;
        btn.className = 'rw-tile' + (f.id === _resolverFamiliaId ? ' rw-tile-sel' : '');
        btn.onclick = function() {
            _resolverFamiliaId = (_resolverFamiliaId === f.id) ? null : f.id;
            _resolverCatId = null; _resolverMarcaId = null;
            resolverRenderFamilias();
        };
        cont.appendChild(btn);
    });
}

function resolverBuscarCat(catId) {
    var found = null;
    resolverFamilias.forEach(function(f) {
        if (!found) { found = f.categorias.find(function(c) { return c.id === catId; }) || null; }
    });
    return found;
}

function esCatPYP(catId) {
    return _pypFam ? _pypFam.categorias.some(function(c) { return c.id == catId; }) : false;
}

function resolverRenderCategorias() {
    var cont = document.getElementById('resolver-modal-categorias');
    cont.innerHTML = '';
    var catsToShow = [];
    var sinFamOrNone = (!_resolverFamiliaId || _resolverFamiliaId === SIN_FAMILIA_ID);
    if (!sinFamOrNone) {
        // Familia real seleccionada → mostrar solo sus categorías
        var fam = resolverFamilias.find(function(f) { return f.id === _resolverFamiliaId; });
        if (fam) catsToShow = fam.categorias;
    } else {
        // Sin familia o SIN FAMILIA → todas las categorías EXCEPTO SERVICIOS
        resolverFamilias.forEach(function(f) {
            if (f.tipo === 'servicios') return;
            f.categorias.forEach(function(c) { catsToShow.push(c); });
        });
    }
    catsToShow.forEach(function(c) {
        var btn = document.createElement('button');
        btn.type = 'button'; btn.textContent = c.nombre;
        btn.className = 'rw-tile' + (c.id === _resolverCatId ? ' rw-tile-sel' : '');
        btn.onclick = function() {
            _resolverCatId = c.id; _resolverMarcaId = null;
            resolverRenderCategorias();
        };
        cont.appendChild(btn);
    });
}

function resolverRenderMarcas(cat) {
    var cont = document.getElementById('resolver-modal-marcas');
    cont.innerHTML = '';
    // "Sin marca" — dashed style, selected when _resolverMarcaId is null
    var btnSin = document.createElement('button');
    btnSin.type = 'button'; btnSin.textContent = 'Sin marca';
    btnSin.className = 'rw-tile-sin' + (_resolverMarcaId === null ? ' rw-tile-sel' : '');
    btnSin.onclick = function() { _resolverMarcaId = null; resolverRenderMarcas(cat); };
    cont.appendChild(btnSin);
    if (cat && cat.marcas) {
        cat.marcas.forEach(function(m) {
            var btn = document.createElement('button');
            btn.type = 'button'; btn.textContent = m.nombre;
            btn.className = 'rw-tile' + (m.id === _resolverMarcaId ? ' rw-tile-sel' : '');
            btn.onclick = function() { _resolverMarcaId = m.id; resolverRenderMarcas(cat); };
            cont.appendChild(btn);
        });
    }
}

function resolverConfirmarModal() {
    var errDiv = document.getElementById('resolver-modal-error');
    errDiv.style.display = 'none';
    if (!_resolverCatId) { errDiv.textContent = 'Selecciona una categoría.'; errDiv.style.display = 'block'; return; }

    // ── Validate package fields if toggle ON ──
    var pkgActivo = document.getElementById('resolver-modal-pkg-toggle').checked;
    var pkgTipo   = (document.getElementById('resolver-modal-pkg-tipo').value || '').trim();
    var pkgCant   = parseInt(document.getElementById('resolver-modal-pkg-cant').value) || 0;
    var pkgBase   = (document.getElementById('resolver-modal-pkg-base').value || '').trim();
    if (!resolverEsFisico()) {
        pkgActivo = false;
    }
    if (pkgActivo) {
        if (!pkgTipo || pkgCant < 1 || !pkgBase) {
            errDiv.textContent = 'Si el producto maneja paquetes, completa tipo, cantidad (≥ 1) y unidad base.';
            errDiv.style.display = 'block';
            return;
        }
    }

    var nombreEdit = (document.getElementById('resolver-modal-nombre-edit').value || '').trim();
    var min    = parseInt(document.getElementById('resolver-modal-minimo').value)    || 0;
    var crit   = parseInt(document.getElementById('resolver-modal-critico').value)   || 0;
    var cont   = document.getElementById('resolver-modal-contenedor').value || '';
    var idx    = _resolverModalIdx;

    if (!nombreEdit) { errDiv.textContent = 'El nombre del producto no puede estar vacío.'; errDiv.style.display = 'block'; return; }
    if (resolverEsFisico() && !cont) { errDiv.textContent = 'Debes seleccionar un contenedor.'; errDiv.style.display = 'block'; return; }
    if (_resolverTipoItem === 'arriendo') {
        var arrProv = document.getElementById('resolver-arr-proveedor').value.trim();
        var arrInicio = document.getElementById('resolver-arr-inicio').value;
        var arrCond = document.getElementById('resolver-arr-cond').value;
        var arrTermino = document.getElementById('resolver-arr-termino').value;
        var arrMontoPeriodo = document.getElementById('resolver-arr-monto-periodo').value;
        var arrMontoTotal = document.getElementById('resolver-arr-monto-total').value;
        var arrDoc = document.getElementById('resolver-arr-doc').value.trim();
        if (!arrProv) { errDiv.textContent = 'El proveedor es obligatorio para Arriendo.'; errDiv.style.display = 'block'; return; }
        if (!arrInicio) { errDiv.textContent = 'La fecha inicio es obligatoria.'; errDiv.style.display = 'block'; return; }
        if (!arrCond) { errDiv.textContent = 'La condición de término es obligatoria.'; errDiv.style.display = 'block'; return; }
        if (arrCond === 'con_fecha' && !arrTermino) { errDiv.textContent = 'La fecha término es obligatoria.'; errDiv.style.display = 'block'; return; }
        if (arrMontoPeriodo === '') { errDiv.textContent = 'El monto período es obligatorio.'; errDiv.style.display = 'block'; return; }
        if (arrMontoTotal === '') { errDiv.textContent = 'El monto total estimado es obligatorio.'; errDiv.style.display = 'block'; return; }
        if (!arrDoc) { errDiv.textContent = 'El documento referencia es obligatorio.'; errDiv.style.display = 'block'; return; }
    } else if (_resolverTipoItem === 'mantencion') {
        resolverPrepararMantCampos();
        var mantDoc = document.getElementById('resolver-mant-doc').value.trim();
        if (!mantDoc) { errDiv.textContent = 'El documento referencia es obligatorio para Mantención.'; errDiv.style.display = 'block'; return; }
    }

    // Save base fields
    document.getElementById('resolver-nombre-hidden-' + idx).value = nombreEdit;
    document.getElementById('resolver-tipo-hidden-'   + idx).value = _resolverTipoItem;
    document.getElementById('resolver-cat-hidden-'    + idx).value = _resolverCatId;
    document.getElementById('resolver-marca-hidden-'  + idx).value = _resolverMarcaId || '';
    document.getElementById('resolver-min-hidden-'    + idx).value = min;
    document.getElementById('resolver-crit-hidden-'   + idx).value = crit;
    document.getElementById('resolver-cont-hidden-'   + idx).value = cont;
    document.getElementById('resolver-arr-proveedor-hidden-' + idx).value = document.getElementById('resolver-arr-proveedor').value.trim();
    document.getElementById('resolver-arr-inicio-hidden-' + idx).value = document.getElementById('resolver-arr-inicio').value;
    document.getElementById('resolver-arr-cond-hidden-' + idx).value = document.getElementById('resolver-arr-cond').value;
    document.getElementById('resolver-arr-termino-hidden-' + idx).value = document.getElementById('resolver-arr-termino').value;
    document.getElementById('resolver-arr-monto-periodo-hidden-' + idx).value = document.getElementById('resolver-arr-monto-periodo').value;
    document.getElementById('resolver-arr-monto-total-hidden-' + idx).value = document.getElementById('resolver-arr-monto-total').value;
    document.getElementById('resolver-arr-doc-hidden-' + idx).value = document.getElementById('resolver-arr-doc').value.trim();
    document.getElementById('resolver-arr-obs-hidden-' + idx).value = document.getElementById('resolver-arr-obs').value.trim();
    document.getElementById('resolver-mant-estado-hidden-' + idx).value = document.getElementById('resolver-mant-estado').value;
    document.getElementById('resolver-mant-proveedor-hidden-' + idx).value = document.getElementById('resolver-mant-proveedor').value.trim();
    document.getElementById('resolver-mant-doc-hidden-' + idx).value = document.getElementById('resolver-mant-doc').value.trim();
    document.getElementById('resolver-mant-obs-hidden-' + idx).value = document.getElementById('resolver-mant-obs').value.trim();

    // Save package fields
    document.getElementById('resolver-pkg-activo-' + idx).value = pkgActivo ? '1' : '0';
    document.getElementById('resolver-pkg-tipo-'   + idx).value = pkgActivo ? pkgTipo : '';
    document.getElementById('resolver-pkg-cant-'   + idx).value = pkgActivo ? pkgCant : '';
    document.getElementById('resolver-pkg-base-'   + idx).value = pkgActivo ? pkgBase : '';

    // Clear any validation error for this item
    var panelNuevo = document.getElementById('panel-nuevo-' + idx);
    if (panelNuevo) {
        var errMsg = panelNuevo.querySelector('.resolver-nuevo-error');
        if (errMsg) errMsg.remove();
        var btn = panelNuevo.querySelector('button');
        if (btn) { btn.style.outline = ''; btn.style.outlineOffset = ''; }
    }

    var fam  = _resolverFamiliaId ? resolverFamilias.find(function(f) { return f.id === _resolverFamiliaId; }) : null;
    var cat  = resolverBuscarCat(_resolverCatId);
    var marca = (cat && cat.marcas && _resolverMarcaId) ? cat.marcas.find(function(m) { return m.id === _resolverMarcaId; }) : null;
    var resumen = document.getElementById('resolver-resumen-' + idx);
    if (resumen) {
        var famNombre = fam ? fam.nombre + ' › ' : '';
        var marcaTxt  = marca ? ' · ' + marca.nombre : ' · sin marca';
        var pkgStr    = pkgActivo ? (' · 📦 ' + pkgTipo + ' × ' + pkgCant) : '';
        resumen.textContent = '✓ ' + famNombre + (cat ? cat.nombre : '') + marcaTxt + ' · Mín:' + min + ' / Crít:' + crit + pkgStr;
        resumen.textContent = '✓ ' + resolverTipoItemLabel(_resolverTipoItem) + ' · ' + (cat ? cat.nombre : '') + (marca ? ' · ' + marca.nombre : ' · sin marca') + (resolverEsFisico() ? (' · Min:' + min + ' / Crit:' + crit) : '') + (pkgActivo ? (' · paquete ' + pkgTipo + ' x ' + pkgCant) : '');
        resumen.classList.remove('hidden');
    }
    resolverCerrarModal();
    resolverMarcarListo(idx);
}

/* ── Crear familia / categoría desde modal ───────────────────────── */
var RESOLVER_CSRF      = '{{ csrf_token() }}';
var RESOLVER_URL_FAM   = '{{ route("admin.catalogo.familias.store") }}';
var RESOLVER_URL_CAT   = '{{ route("admin.catalogo.categorias.store") }}';
var RESOLVER_URL_MARCA = '{{ route("admin.catalogo.marcas.store") }}';

function resolverToggleNuevaFam() {
    var form = document.getElementById('resolver-nueva-fam-form');
    var show = form.style.display === 'none';
    form.style.display = show ? 'block' : 'none';
    if (show) setTimeout(function() { document.getElementById('resolver-nueva-fam-input').focus(); }, 50);
}

function resolverToggleNuevaCat() {
    var form = document.getElementById('resolver-nueva-cat-form');
    var show = form.style.display === 'none';
    form.style.display = show ? 'block' : 'none';
    if (show) setTimeout(function() { document.getElementById('resolver-nueva-cat-input').focus(); }, 50);
}

function resolverCrearFamilia() {
    var nombre = document.getElementById('resolver-nueva-fam-input').value.trim();
    if (!nombre) return;
    fetch(RESOLVER_URL_FAM, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': RESOLVER_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ nombre: nombre }),
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok || data.id) {
            resolverFamilias.push({ id: data.id, nombre: data.nombre || nombre, categorias: [] });
            _resolverFamiliaId = data.id; _resolverCatId = null;
            resolverRenderFamilias(); resolverRenderCategorias();
            document.getElementById('resolver-nueva-fam-input').value = '';
            document.getElementById('resolver-nueva-fam-form').style.display = 'none';
        }
    }).catch(function() {});
}

function resolverCrearCategoria() {
    var nombre = document.getElementById('resolver-nueva-cat-input').value.trim();
    if (!nombre) return;
    if (!_resolverFamiliaId || _resolverFamiliaId === SIN_FAMILIA_ID) {
        var errDiv = document.getElementById('resolver-modal-error');
        errDiv.textContent = 'Para crear una categoría, primero selecciona una familia real.';
        errDiv.style.display = 'block'; return;
    }
    fetch(RESOLVER_URL_CAT, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': RESOLVER_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ nombre: nombre, familia_id: _resolverFamiliaId }),
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok || data.id) {
            var fam = resolverFamilias.find(function(f) { return f.id === _resolverFamiliaId; });
            if (fam) fam.categorias.push({ id: data.id, nombre: data.nombre || nombre, marcas: [] });
            _resolverCatId = data.id; _resolverMarcaId = null;
            document.getElementById('resolver-nueva-cat-input').value = '';
            document.getElementById('resolver-nueva-cat-form').style.display = 'none';
            resolverRenderCategorias();
            var cat = fam ? fam.categorias.find(function(c) { return c.id === data.id; }) : null;
            resolverRenderMarcas(cat || { marcas: [] });
        }
    }).catch(function() {});
}

function resolverToggleNuevaMarca() {
    var form = document.getElementById('resolver-nueva-marca-form');
    var show = form.style.display === 'none';
    form.style.display = show ? 'block' : 'none';
    if (show) setTimeout(function() { document.getElementById('resolver-nueva-marca-input').focus(); }, 50);
}

function resolverCrearMarca() {
    var nombre = document.getElementById('resolver-nueva-marca-input').value.trim();
    if (!nombre || !_resolverCatId) return;
    fetch(RESOLVER_URL_MARCA, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': RESOLVER_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ nombre: nombre, categoria_id: _resolverCatId }),
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok || data.id) {
            var cat = resolverBuscarCat(_resolverCatId);
            if (cat) {
                if (!cat.marcas) cat.marcas = [];
                cat.marcas.push({ id: data.id, nombre: data.nombre || nombre.toUpperCase() });
            }
            _resolverMarcaId = data.id;
            resolverRenderMarcas(cat);
            document.getElementById('resolver-nueva-marca-input').value = '';
            document.getElementById('resolver-nueva-marca-form').style.display = 'none';
        }
    }).catch(function() {});
}


document.getElementById('resolver-nueva-fam-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); resolverCrearFamilia(); }
});
document.getElementById('resolver-nueva-cat-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); resolverCrearCategoria(); }
});
document.getElementById('resolver-nueva-marca-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); resolverCrearMarca(); }
});

/* ── Cancelar carga masiva ───────────────────────────────────────── */
var _confirmingCarga = false;
var CANCELAR_URL  = '{{ route("admin.productos.carga.masiva.cancelar") }}';
var CANCELAR_CSRF = '{{ csrf_token() }}';

function abrirModalCancelar() {
    var modal = document.getElementById('modal-cancelar-carga');
    modal.style.display = 'flex';
}

function cerrarModalCancelar() {
    var modal = document.getElementById('modal-cancelar-carga');
    modal.style.display = 'none';
}

function ejecutarCancelarCarga() {
    _confirmingCarga = true;
    fetch(CANCELAR_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CANCELAR_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({}),
    }).then(function() {
        window.location.href = '{{ route("dashboard") }}';
    }).catch(function() {
        window.location.href = '{{ route("dashboard") }}';
    });
}

// Backdrop click closes the cancel modal
document.getElementById('modal-cancelar-carga').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCancelar();
});

// Escape key closes the cancel modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalCancelar();
});

/* ── Modal advertencia productos pendientes ──────────────────── */
var _bypassPendingCheck = false;
var _cmPendingItems     = []; // [{idx, desc}, ...]

function abrirModalPendientes(items) {
    var lista = document.getElementById('modal-pendientes-lista');
    lista.innerHTML = '';
    items.forEach(function(item) {
        var li = document.createElement('li');
        li.style.cssText = 'font-size:0.8rem; margin-bottom:0.25rem; display:flex; align-items:flex-start; gap:0.35rem;';
        var badge = document.createElement('span');
        badge.textContent = '(PENDIENTE)';
        badge.style.cssText = 'font-size:0.68rem; font-weight:700; color:#d97706; white-space:nowrap; flex-shrink:0; margin-top:1px;';
        var txt = document.createElement('span');
        txt.className = 'cm-modal-sub';
        txt.style.cssText = 'font-size:0.8rem;';
        txt.textContent = item.desc;
        li.appendChild(badge);
        li.appendChild(txt);
        lista.appendChild(li);
    });
    document.getElementById('modal-pendientes-carga').style.display = 'flex';
}

function cerrarModalPendientes() {
    document.getElementById('modal-pendientes-carga').style.display = 'none';
}

function continuarConPendientes() {
    _bypassPendingCheck = true;
    cerrarModalPendientes();
    var form = document.getElementById('cm-form-confirmar');
    // Para cada ítem sin resolver: forzar accion=pendiente (sin modificar nombre)
    _cmPendingItems.forEach(function(item) {
        var idx = item.idx;
        document.querySelectorAll('input[name="resoluciones[' + idx + '][accion]"]')
            .forEach(function(r) { r.checked = false; });
        var hAccion = document.createElement('input');
        hAccion.type = 'hidden';
        hAccion.name = 'resoluciones[' + idx + '][accion]';
        hAccion.value = 'pendiente';
        form.appendChild(hAccion);
    });
    _confirmingCarga = true;
    form.submit();
}

function onRadioPendiente(idx) {
    // Limpiar selección de producto enlazado
    var pid = document.getElementById('input-pid-' + idx);
    if (pid) pid.value = '';
    // Limpiar selección de nuevo producto
    var cat = document.getElementById('resolver-cat-hidden-' + idx);
    if (cat) cat.value = '';
    // Ocultar panel "nuevo producto"
    var panel = document.getElementById('panel-nuevo-' + idx);
    if (panel) { panel.classList.add('hidden'); panel.style.display = 'none'; }
    // Limpiar búsqueda de enlazar a otro
    if (typeof cmLimpiarBusqueda === 'function') cmLimpiarBusqueda(idx);
    // Mover card a sección "Por confirmar"
    resolverMarcarListo(idx);
}

document.getElementById('modal-pendientes-carga').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalPendientes();
});

// Interceptar submit: advertir si hay ítems sin resolver
document.getElementById('cm-form-confirmar').addEventListener('submit', function(e) {
    if (_bypassPendingCheck) { _confirmingCarga = true; return; }

    _cmPendingItems = [];
    @foreach($pendiente['conflictos'] as $i => $c)
    (function() {
        // Si el usuario eligió explícitamente "Dejar como pendiente" → no es un error, está OK
        var radPend = document.querySelector('input[name="resoluciones[{{ $i }}][accion]"][value="pendiente"]');
        if (radPend && radPend.checked) return;
        var pid = document.getElementById('input-pid-{{ $i }}');
        var cat = document.getElementById('resolver-cat-hidden-{{ $i }}');
        if ((!pid || !pid.value) && (!cat || !cat.value)) {
            _cmPendingItems.push({ idx: {{ $i }}, desc: {!! json_encode($c['descripcion'], JSON_HEX_TAG | JSON_HEX_AMP) !!} });
        }
    })();
    @endforeach

    if (_cmPendingItems.length > 0) {
        e.preventDefault();
        abrirModalPendientes(_cmPendingItems);
        return;
    }

    _confirmingCarga = true;
});

// Auto-mover cards pre-enlazadas (≥80% similitud) al "Por confirmar" — sin animación al cargar
@foreach($pendiente['conflictos'] as $i => $c)
@if(($c['similitud'] ?? 0) >= 80 && !empty($c['sugerencia_id']))
resolverMarcarListo({{ $i }}, false);
@endif
@endforeach

// On page unload (tab close, reload, navigate away), auto-cancel via sendBeacon
window.addEventListener('beforeunload', function() {
    if (_confirmingCarga) return;
    var fd = new FormData();
    fd.append('_token', CANCELAR_CSRF);
    navigator.sendBeacon(CANCELAR_URL, fd);
});
</script>
@endpush

@endsection
