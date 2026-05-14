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
                    @if(!empty($e['unidad_medida_nombre'])) {{ $e['unidad_medida_nombre'] }}
                    @elseif(!empty($e['unidad'])) {{ $e['unidad'] }}
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.productos.carga.masiva.confirmar') }}">
    @csrf

    <div class="space-y-5 mb-6">
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
                        <svg style="width:1rem;height:1rem;flex-shrink:0;margin-top:0.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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

                        {{-- Cascade Familia → Cat → Marca → select-otro --}}
                        <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-top:0.5rem;" id="cm-cascade-{{ $i }}">
                            <select id="cm-sel-fam-{{ $i }}"
                                    onchange="cmFiltrarCat({{ $i }}, this.value)"
                                    class="cm-sel"
                                    style="font-size:0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.25rem 0.4rem; outline:none; max-width:130px;">
                                <option value="">— Familia —</option>
                                @foreach($familias as $fam)
                                <option value="{{ $fam->id }}">{{ $fam->nombre }}</option>
                                @endforeach
                            </select>
                            <select id="cm-sel-cat-{{ $i }}"
                                    onchange="cmFiltrarMarca({{ $i }}, this.value)"
                                    class="cm-sel"
                                    style="font-size:0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.25rem 0.4rem; outline:none; max-width:130px;" disabled>
                                <option value="">— Categoría —</option>
                            </select>
                            <select id="cm-sel-marca-{{ $i }}"
                                    onchange="cmFiltrarProductos({{ $i }}, this.value)"
                                    class="cm-sel"
                                    style="font-size:0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.25rem 0.4rem; outline:none; max-width:120px;" disabled>
                                <option value="">— Marca —</option>
                            </select>
                        </div>
                        <select id="select-otro-{{ $i }}"
                                onchange="onSelectOtro({{ $i }}, this.value)"
                                style="margin-top:0.4rem; width:100%; font-size:0.78rem; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.3rem 0.5rem; outline:none;">
                            <option value="">— Selecciona un producto —</option>
                            @foreach($productos as $p)
                            <option value="{{ $p->id }}"
                                    data-cat="{{ $p->categoria_id ?? '' }}"
                                    data-marca="{{ $p->marca_id ?? '' }}">
                                {{ $p->nombre }}
                            </option>
                            @endforeach
                        </select>
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
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_categoria_id]" id="resolver-cat-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_marca_id]"     id="resolver-marca-hidden-{{ $i }}" value="">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_stock_minimo]"  id="resolver-min-hidden-{{ $i }}"  value="0">
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_stock_critico]" id="resolver-crit-hidden-{{ $i }}" value="0">
                    </div>
                </label>

            </div>{{-- /padding --}}
        </div>{{-- /cm-card --}}
        @endforeach
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

{{-- ── Modal nuevo producto ─────────────────────────────────────────── --}}
<div id="resolver-modal-nuevo" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,.5); align-items:center; justify-content:center; padding:1rem;">
    <div class="cm-modal-box" style="border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:500px; max-width:calc(100vw - 2rem); max-height:88vh; overflow-y:auto; animation:resolverIn .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.25rem 1.25rem 0; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;">
            <div>
                <p class="cm-modal-title" style="font-size:1rem; font-weight:700; margin:0;">Nuevo producto</p>
                <p id="resolver-modal-nombre" class="cm-modal-sub" style="font-size:0.8rem; margin:0.2rem 0 0; font-weight:500;"></p>
            </div>
            <button onclick="resolverCerrarModal()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1.25rem;line-height:1;flex-shrink:0;">✕</button>
        </div>
        <div style="padding:1rem 1.25rem;">

            <p class="cm-modal-label" style="font-size:0.75rem; font-weight:600; margin:0 0 0.5rem;">Familia <span style="color:#6b7280; font-weight:400;">(opcional)</span></p>
            <div id="resolver-modal-familias" style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-bottom:0.5rem;"></div>
            <div id="resolver-nueva-fam-wrap" style="display:flex; gap:0.4rem; align-items:center; margin-bottom:1rem;">
                <button type="button" onclick="resolverToggleNuevaFam()"
                        style="font-size:0.75rem; font-weight:600; color:#7c3aed; background:none; border:none; cursor:pointer; padding:0;">+ Nueva familia</button>
                <span id="resolver-nueva-fam-form" style="display:none; gap:0.4rem; align-items:center;">
                    <input type="text" id="resolver-nueva-fam-input" placeholder="Nombre familia"
                           class="cm-input" style="border:1px solid #d1d5db; border-radius:0.375rem; padding:3px 8px; font-size:0.78rem; outline:none; width:150px;">
                    <button type="button" onclick="resolverCrearFamilia()"
                            style="font-size:0.75rem; font-weight:600; background:#7c3aed; color:#fff; border:none; border-radius:0.375rem; padding:3px 10px; cursor:pointer;">Crear</button>
                    <button type="button" onclick="resolverToggleNuevaFam()"
                            style="font-size:0.75rem; color:#9ca3af; background:none; border:none; cursor:pointer;">✕</button>
                </span>
            </div>

            <div id="resolver-modal-cat-wrap" style="margin-bottom:0.75rem;">
                <p class="cm-modal-label" style="font-size:0.75rem; font-weight:600; margin:0 0 0.5rem;">Categoría <span style="color:#ef4444;">*</span></p>
                <div id="resolver-modal-categorias" style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-bottom:0.5rem;"></div>
                <div style="display:flex; gap:0.4rem; align-items:center;">
                    <button type="button" onclick="resolverToggleNuevaCat()"
                            style="font-size:0.75rem; font-weight:600; color:#7c3aed; background:none; border:none; cursor:pointer; padding:0;">+ Nueva categoría</button>
                    <span id="resolver-nueva-cat-form" style="display:none; gap:0.4rem; align-items:center;">
                        <input type="text" id="resolver-nueva-cat-input" placeholder="Nombre categoría"
                               class="cm-input" style="border:1px solid #d1d5db; border-radius:0.375rem; padding:3px 8px; font-size:0.78rem; outline:none; width:150px;">
                        <button type="button" onclick="resolverCrearCategoria()"
                                style="font-size:0.75rem; font-weight:600; background:#7c3aed; color:#fff; border:none; border-radius:0.375rem; padding:3px 10px; cursor:pointer;">Crear</button>
                        <button type="button" onclick="resolverToggleNuevaCat()"
                                style="font-size:0.75rem; color:#9ca3af; background:none; border:none; cursor:pointer;">✕</button>
                    </span>
                </div>
            </div>

            <div id="resolver-modal-marca-wrap" style="display:none; margin-bottom:0.75rem;">
                <p class="cm-modal-label" style="font-size:0.75rem; font-weight:600; margin:0 0 0.5rem;">Marca <span style="color:#6b7280; font-weight:400;">(opcional — se usará "SIN MARCA" si no se elige)</span></p>
                <div id="resolver-modal-marcas" style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-bottom:0.5rem;"></div>
                <div style="display:flex; gap:0.4rem; align-items:center;">
                    <button type="button" onclick="resolverToggleNuevaMarca()"
                            style="font-size:0.75rem; font-weight:600; color:#7c3aed; background:none; border:none; cursor:pointer; padding:0;">+ Nueva marca</button>
                    <span id="resolver-nueva-marca-form" style="display:none; gap:0.4rem; align-items:center;">
                        <input type="text" id="resolver-nueva-marca-input" placeholder="Nombre marca"
                               class="cm-input" style="border:1px solid #d1d5db; border-radius:0.375rem; padding:3px 8px; font-size:0.78rem; outline:none; width:150px;">
                        <button type="button" onclick="resolverCrearMarca()"
                                style="font-size:0.75rem; font-weight:600; background:#7c3aed; color:#fff; border:none; border-radius:0.375rem; padding:3px 10px; cursor:pointer;">Crear</button>
                        <button type="button" onclick="resolverToggleNuevaMarca()"
                                style="font-size:0.75rem; color:#9ca3af; background:none; border:none; cursor:pointer;">✕</button>
                    </span>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
                <div>
                    <label class="cm-modal-label" style="display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.3rem;">Stock mínimo</label>
                    <input type="number" id="resolver-modal-minimo" min="0" value="0"
                           class="cm-input" style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.6rem; font-size:0.875rem; outline:none; box-sizing:border-box;">
                </div>
                <div>
                    <label class="cm-modal-label" style="display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.3rem;">Stock crítico</label>
                    <input type="number" id="resolver-modal-critico" min="0" value="0"
                           class="cm-input" style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.6rem; font-size:0.875rem; outline:none; box-sizing:border-box;">
                </div>
            </div>
            <div id="resolver-modal-error" style="display:none; font-size:0.8rem; color:#dc2626; background:#fef2f2; border-radius:0.375rem; padding:0.4rem 0.6rem; margin-bottom:0.75rem;"></div>
        </div>
        <div class="cm-modal-footer" style="padding:0.75rem 1.25rem 1.25rem; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:0.5rem;">
            <button type="button" onclick="resolverCerrarModal()"
                    class="cm-btn-cancel" style="padding:0.45rem 1rem; font-size:0.875rem; font-weight:500; border-radius:0.5rem; cursor:pointer; border:1px solid #e5e7eb;">
                Cancelar
            </button>
            <button type="button" onclick="resolverConfirmarModal()"
                    style="padding:0.45rem 1.1rem; font-size:0.875rem; font-weight:600; color:#fff; background:#7c3aed; border:none; border-radius:0.5rem; cursor:pointer;">
                Confirmar
            </button>
        </div>
    </div>
</div>

@push('head')
<style>
@keyframes resolverIn { from{opacity:0;transform:scale(.95) translateY(-8px)} to{opacity:1;transform:none} }

/* ── Día ─────────────────────────────────────────────────────────── */
.dark-title { color:#1e293b; }
.dark-sub   { color:#64748b; }

.cm-exactos-box   { background:#f0fdf4; border:1px solid #bbf7d0; }
.cm-exactos-title { color:#15803d; }
.cm-exactos-list  { color:#16a34a; }
.cm-exactos-qty   { color:#86efac; }

.cm-card        { background:#fff; }
.cm-card-header { }
.cm-desc        { color:#1e293b; }
.cm-meta        { color:#64748b; }
.cm-sim-badge   { }
.cm-sim-inline  { color:#6366f1; }

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
.cm-opt-title-indigo { color:#4338ca; }
.cm-opt-title-blue   { color:#1d4ed8; }
.cm-opt-title-emerald{ color:#065f46; }
.cm-opt-sub          { color:#64748b; }

.cm-sel   { background:#fff; color:#1e293b; }

/* Modal */
.cm-modal-box    { background:#fff; }
.cm-modal-title  { color:#1f2937; }
.cm-modal-sub    { color:#374151; }
.cm-modal-label  { color:#374151; }
.cm-modal-footer { background:#fff; }
.cm-btn-cancel   { background:#f3f4f6; color:#374151; }
.cm-input        { background:#fff; color:#1f2937; }

/* ── Noche ───────────────────────────────────────────────────────── */
html.dark .dark-title { color:#f1f5f9; }
html.dark .dark-sub   { color:#94a3b8; }

html.dark .cm-exactos-box   { background:#052e16; border-color:#166534; }
html.dark .cm-exactos-title { color:#86efac; }
html.dark .cm-exactos-list  { color:#4ade80; }
html.dark .cm-exactos-qty   { color:#166534; }

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

html.dark .cm-unid-btn        { background:#334155; border-color:#475569; color:#cbd5e1; }
html.dark .cm-unid-btn-active { background:#1e3a5f; border-color:#2563eb; color:#93c5fd; }
html.dark .cm-unid-btn:hover  { background:#475569; }

html.dark .cm-opt-label               { border-color:#334155; }
html.dark .cm-opt-hover-indigo:hover  { background:#1e1b4b; border-color:#4338ca; }
html.dark .cm-opt-hover-blue:hover    { background:#172554; border-color:#1d4ed8; }
html.dark .cm-opt-hover-emerald:hover { background:#052e16; border-color:#065f46; }
html.dark .cm-opt-title-indigo  { color:#a5b4fc; }
html.dark .cm-opt-title-blue    { color:#93c5fd; }
html.dark .cm-opt-title-emerald { color:#6ee7b7; }
html.dark .cm-opt-sub           { color:#64748b; }

html.dark .cm-sel { background:#0f172a; color:#e2e8f0; border-color:#334155; }

html.dark .cm-modal-box    { background:#1e293b; }
html.dark .cm-modal-title  { color:#f1f5f9; }
html.dark .cm-modal-sub    { color:#cbd5e1; }
html.dark .cm-modal-label  { color:#94a3b8; }
html.dark .cm-modal-footer { background:#1e293b; border-color:#334155; }
html.dark .cm-btn-cancel   { background:#334155; color:#cbd5e1; border-color:#475569; }
html.dark .cm-input        { background:#0f172a; color:#e2e8f0; border-color:#334155; }

/* Cancel modal overlay close on backdrop */
#modal-cancelar-carga { cursor: default; }
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

/* ── Cascade familia→cat→marca para "Enlazar a otro" ─────────────── */
function cmBuscarCatEnFamilias(catId) {
    for (var fi = 0; fi < resolverFamilias.length; fi++) {
        var cats = resolverFamilias[fi].categorias;
        for (var ci = 0; ci < cats.length; ci++) {
            if (cats[ci].id == catId) return cats[ci];
        }
    }
    return null;
}

function cmEsCatPYP(catId) {
    return _pypFam ? _pypFam.categorias.some(function(c) { return c.id == catId; }) : false;
}

function cmFiltrarCat(idx, famId) {
    var catSel   = document.getElementById('cm-sel-cat-'   + idx);
    var marcaSel = document.getElementById('cm-sel-marca-' + idx);
    catSel.innerHTML   = '<option value="">— Categoría —</option>';
    marcaSel.innerHTML = '<option value="">— Marca —</option>';
    catSel.disabled    = true;
    marcaSel.disabled  = true;
    if (!famId) { cmMostrarTodosProductos(idx); return; }
    var iSinFam = SIN_FAMILIA_ID && (parseInt(famId) === SIN_FAMILIA_ID);
    var cats = [];
    if (iSinFam) {
        resolverFamilias.forEach(function(f) {
            if (f.tipo === 'servicios') return;
            f.categorias.forEach(function(c) {
                if (!cmEsCatPYP(c.id)) cats.push(c);
            });
        });
        cats.sort(function(a, b) { return a.nombre.localeCompare(b.nombre); });
    } else {
        var fam = resolverFamilias.find(function(f) { return f.id == famId; });
        if (fam) cats = fam.categorias;
    }
    cats.forEach(function(c) {
        var o = document.createElement('option');
        o.value = c.id; o.textContent = c.nombre;
        catSel.appendChild(o);
    });
    if (cats.length) catSel.disabled = false;
    cmMostrarTodosProductos(idx);
}

function cmFiltrarMarca(idx, catId) {
    var marcaSel = document.getElementById('cm-sel-marca-' + idx);
    marcaSel.innerHTML = '<option value="">— Marca —</option>';
    marcaSel.disabled  = true;
    if (!catId) { cmFiltrarPorCat(idx, null); return; }
    var cat = cmBuscarCatEnFamilias(catId);
    if (cat && cat.marcas && cat.marcas.length) {
        cat.marcas.forEach(function(m) {
            var o = document.createElement('option');
            o.value = m.id; o.textContent = m.nombre;
            marcaSel.appendChild(o);
        });
        marcaSel.disabled = false;
    }
    cmFiltrarPorCat(idx, catId);
}

function cmFiltrarProductos(idx, marcaId) {
    var catId = document.getElementById('cm-sel-cat-' + idx).value;
    cmFiltrarPorCatMarca(idx, catId || null, marcaId || null);
}

function cmMostrarTodosProductos(idx) {
    var sel = document.getElementById('select-otro-' + idx);
    Array.from(sel.options).forEach(function(o) { o.style.display = ''; });
}

function cmFiltrarPorCat(idx, catId) {
    var sel = document.getElementById('select-otro-' + idx);
    Array.from(sel.options).forEach(function(o) {
        if (!o.value) { o.style.display = ''; return; }
        o.style.display = (!catId || o.dataset.cat == catId) ? '' : 'none';
    });
    if (sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].style.display === 'none') {
        sel.value = '';
        document.getElementById('input-pid-' + idx).value = '';
    }
}

function cmFiltrarPorCatMarca(idx, catId, marcaId) {
    var sel = document.getElementById('select-otro-' + idx);
    Array.from(sel.options).forEach(function(o) {
        if (!o.value) { o.style.display = ''; return; }
        var okCat   = !catId   || o.dataset.cat   == catId;
        var okMarca = !marcaId || o.dataset.marca  == marcaId;
        o.style.display = (okCat && okMarca) ? '' : 'none';
    });
    if (sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].style.display === 'none') {
        sel.value = '';
        document.getElementById('input-pid-' + idx).value = '';
    }
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
}

function onSelectOtro(idx, value) {
    var radios = document.querySelectorAll('input[name="resoluciones[' + idx + '][accion]"]');
    radios.forEach(function(r) { if (r.dataset.tipo === 'otro') r.checked = true; });
    document.getElementById('input-pid-' + idx).value = value;
    var panel = document.getElementById('panel-nuevo-' + idx);
    panel.classList.add('hidden'); panel.style.display = 'none';
}

/* ── Modal nuevo producto ─────────────────────────────────────────── */
function resolverAbrirModal(idx, nombre) {
    _resolverModalIdx  = idx;
    _resolverFamiliaId = null;
    _resolverCatId     = null;
    _resolverMarcaId   = null;
    document.getElementById('resolver-modal-nombre').textContent = nombre;
    document.getElementById('resolver-modal-error').style.display = 'none';
    document.getElementById('resolver-modal-marca-wrap').style.display = 'none';
    document.getElementById('resolver-modal-minimo').value  = document.getElementById('resolver-min-hidden-'  + idx).value || '0';
    document.getElementById('resolver-modal-critico').value = document.getElementById('resolver-crit-hidden-' + idx).value || '0';
    resolverRenderFamilias();
    resolverRenderCategorias();
    document.getElementById('resolver-modal-nuevo').style.display = 'flex';
}

function resolverCerrarModal() {
    document.getElementById('resolver-modal-nuevo').style.display = 'none';
}

function resolverRenderFamilias() {
    var cont = document.getElementById('resolver-modal-familias');
    cont.innerHTML = '';
    resolverFamilias.forEach(function(f) {
        var btn = document.createElement('button');
        btn.type = 'button'; btn.textContent = f.nombre;
        var sel = f.id === _resolverFamiliaId;
        btn.style.cssText = 'font-size:.8rem;font-weight:600;padding:.35rem .85rem;border-radius:.5rem;border:1px solid ' +
            (sel ? '#7c3aed;background:#7c3aed;color:#fff' : '#d1d5db;background:#fff;color:#374151') + ';cursor:pointer;transition:background .15s;';
        btn.onclick = function() {
            var same = (_resolverFamiliaId === f.id);
            _resolverFamiliaId = same ? null : f.id;
            _resolverCatId = null; _resolverMarcaId = null;
            var nmf = document.getElementById('resolver-nueva-marca-form');
            if (nmf) nmf.style.display = 'none';
            document.getElementById('resolver-modal-marca-wrap').style.display = 'none';
            resolverRenderFamilias(); resolverRenderCategorias();
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
        // Sin familia o SIN FAMILIA → todas las categorías excepto PARTES Y PIEZAS y SERVICIOS
        resolverFamilias.forEach(function(f) {
            if (f.tipo === 'servicios') return;
            f.categorias.forEach(function(c) {
                if (!esCatPYP(c.id)) catsToShow.push(c);
            });
        });
    }
    catsToShow.forEach(function(c) {
        var btn = document.createElement('button');
        btn.type = 'button'; btn.textContent = c.nombre;
        var sel = c.id === _resolverCatId;
        btn.style.cssText = 'font-size:.8rem;font-weight:600;padding:.35rem .85rem;border-radius:.5rem;border:1px solid ' +
            (sel ? '#7c3aed;background:#7c3aed;color:#fff' : '#d1d5db;background:#fff;color:#374151') + ';cursor:pointer;transition:background .15s;';
        btn.onclick = function() {
            _resolverCatId = c.id; _resolverMarcaId = null;
            var nmf = document.getElementById('resolver-nueva-marca-form');
            if (nmf) nmf.style.display = 'none';
            resolverRenderCategorias(); resolverRenderMarcas(c);
        };
        cont.appendChild(btn);
    });
}

function resolverRenderMarcas(cat) {
    var wrap  = document.getElementById('resolver-modal-marca-wrap');
    var cont  = document.getElementById('resolver-modal-marcas');
    cont.innerHTML = '';
    if (!cat) { wrap.style.display = 'none'; return; }
    if (cat.marcas && cat.marcas.length) {
        cat.marcas.forEach(function(m) {
            var btn = document.createElement('button');
            btn.type = 'button'; btn.textContent = m.nombre;
            var sel = m.id === _resolverMarcaId;
            btn.style.cssText = 'font-size:.8rem;font-weight:600;padding:.35rem .85rem;border-radius:.5rem;border:1px solid ' +
                (sel ? '#7c3aed;background:#7c3aed;color:#fff' : '#d1d5db;background:#fff;color:#374151') + ';cursor:pointer;transition:background .15s;';
            btn.onclick = function() { _resolverMarcaId = m.id; resolverRenderMarcas(cat); };
            cont.appendChild(btn);
        });
    }
    wrap.style.display = 'block';
}

function resolverConfirmarModal() {
    var errDiv = document.getElementById('resolver-modal-error');
    errDiv.style.display = 'none';
    if (!_resolverCatId) { errDiv.textContent = 'Selecciona una categoría.'; errDiv.style.display = 'block'; return; }
    // Bloquear categorías de PARTES Y PIEZAS cuando no hay familia real seleccionada
    var sinFamOrNone = (!_resolverFamiliaId || _resolverFamiliaId === SIN_FAMILIA_ID);
    if (sinFamOrNone && esCatPYP(_resolverCatId)) {
        errDiv.textContent = 'Las categorías de PARTES Y PIEZAS requieren una familia real.';
        errDiv.style.display = 'block'; return;
    }
    var min  = parseInt(document.getElementById('resolver-modal-minimo').value)  || 0;
    var crit = parseInt(document.getElementById('resolver-modal-critico').value) || 0;
    var idx  = _resolverModalIdx;
    document.getElementById('resolver-cat-hidden-'  + idx).value = _resolverCatId;
    document.getElementById('resolver-marca-hidden-'+ idx).value = _resolverMarcaId || '';
    document.getElementById('resolver-min-hidden-'  + idx).value = min;
    document.getElementById('resolver-crit-hidden-' + idx).value = crit;
    var fam  = _resolverFamiliaId ? resolverFamilias.find(function(f) { return f.id === _resolverFamiliaId; }) : null;
    var cat  = resolverBuscarCat(_resolverCatId);
    var marca = (cat && cat.marcas && _resolverMarcaId) ? cat.marcas.find(function(m) { return m.id === _resolverMarcaId; }) : null;
    var resumen = document.getElementById('resolver-resumen-' + idx);
    if (resumen) {
        var famNombre = fam ? fam.nombre + ' › ' : '';
        var marcaTxt  = marca ? ' · ' + marca.nombre : ' · sin marca';
        resumen.textContent = '✓ ' + famNombre + (cat ? cat.nombre : '') + marcaTxt + ' · Mín:' + min + ' / Crít:' + crit;
        resumen.classList.remove('hidden');
    }
    resolverCerrarModal();
}

/* ── Crear familia / categoría desde modal ───────────────────────── */
var RESOLVER_CSRF      = '{{ csrf_token() }}';
var RESOLVER_URL_FAM   = '{{ route("admin.catalogo.familias.store") }}';
var RESOLVER_URL_CAT   = '{{ route("admin.catalogo.categorias.store") }}';
var RESOLVER_URL_MARCA = '{{ route("admin.catalogo.marcas.store") }}';

function resolverToggleNuevaFam() {
    var form = document.getElementById('resolver-nueva-fam-form');
    var show = form.style.display === 'none' || form.style.display === '';
    form.style.display = show ? 'flex' : 'none';
    if (show) setTimeout(function() { document.getElementById('resolver-nueva-fam-input').focus(); }, 50);
}

function resolverToggleNuevaCat() {
    var form = document.getElementById('resolver-nueva-cat-form');
    var show = form.style.display === 'none' || form.style.display === '';
    form.style.display = show ? 'flex' : 'none';
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
    var show = form.style.display === 'none' || form.style.display === '';
    form.style.display = show ? 'flex' : 'none';
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

// Mark form submit so unload doesn't double-cancel
document.querySelector('form[action="{{ route("admin.productos.carga.masiva.confirmar") }}"]').addEventListener('submit', function() {
    _confirmingCarga = true;
});

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
