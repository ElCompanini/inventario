@extends('layouts.app')
@section('title', 'Reportes — BINCARD')

@push('head')
<style>
.bincard-th { background:#1e3a5f; color:#fff; font-size:.72rem; font-weight:700; padding:.6rem .75rem; white-space:nowrap; text-align:center; border:1px solid #2563eb; }
.bincard-td { font-size:.75rem; padding:.45rem .65rem; border:1px solid #e2e8f0; vertical-align:middle; }
.bincard-entrada { background:#f0fdf4; }
.bincard-salida  { background:#fff8f8; }
.bincard-neutral { background:#fff; }
.bincard-alt     { background:#f8fafc; }

/* Badges tipo movimiento */
.bc-mov-entrada { background:#dcfce7; color:#15803d; }
.bc-mov-salida  { background:#fee2e2; color:#dc2626; }
.bc-mov-neutral { background:#f3f4f6; color:#6b7280; }

/* Dark mode — BINCARD */
html.dark .bincard-th      { background:#1e1b4b; border-color:#4f46e5; }
html.dark .bincard-td      { border-color:#334155; color:#e2e8f0; }
html.dark .bincard-entrada { background:#052e16; }
html.dark .bincard-salida  { background:#2d0a0a; }
html.dark .bincard-neutral { background:#1e293b; }
html.dark .bincard-alt     { background:#162032; }
html.dark .bincard-td > *  { color:inherit; }
html.dark .bc-mov-entrada  { background:rgba(22,163,74,0.2); color:#4ade80; }
html.dark .bc-mov-salida   { background:rgba(220,38,38,0.2); color:#f87171; }
html.dark .bc-mov-neutral  { background:rgba(100,116,139,0.2); color:#94a3b8; }

/* Servicio: badges de acción operacional */
.bc-accion-recepcion  { background:#dbeafe; color:#1d4ed8; }
.bc-accion-ejecucion  { background:#fef9c3; color:#92400e; }
.bc-accion-baja       { background:#fee2e2; color:#dc2626; }
.bc-accion-admin      { background:#f3f4f6; color:#6b7280; }
html.dark .bc-accion-recepcion { background:rgba(59,130,246,0.2);  color:#93c5fd; }
html.dark .bc-accion-ejecucion { background:rgba(234,179,8,0.2);   color:#fde047; }
html.dark .bc-accion-baja      { background:rgba(220,38,38,0.2);   color:#f87171; }
html.dark .bc-accion-admin     { background:rgba(100,116,139,0.2); color:#94a3b8; }

/* Badges de origen del movimiento */
.bc-orig-sol      { background:#fef9c3; color:#92400e; }
.bc-orig-dev      { background:#d1fae5; color:#065f46; }
.bc-orig-compra   { background:#dbeafe; color:#1e40af; }
.bc-orig-armado   { background:#ffedd5; color:#9a3412; }
.bc-orig-ajuste   { background:#f3f4f6; color:#374151; }
.bc-orig-traslado { background:#ede9fe; color:#5b21b6; }
.bc-orig-manual   { background:#f8fafc; color:#64748b; }
html.dark .bc-orig-sol    { background:rgba(234,179,8,0.2);   color:#fde047; }
html.dark .bc-orig-dev    { background:rgba(16,185,129,0.2);  color:#6ee7b7; }
html.dark .bc-orig-compra { background:rgba(59,130,246,0.2);  color:#93c5fd; }
html.dark .bc-orig-armado { background:rgba(249,115,22,0.2);  color:#fdba74; }
html.dark .bc-orig-ajuste { background:rgba(100,116,139,0.2); color:#94a3b8; }
html.dark .bc-orig-traslado{ background:rgba(139,92,246,0.2); color:#c4b5fd; }
html.dark .bc-orig-manual { background:rgba(51,65,85,0.4);    color:#94a3b8; }

/* Saldo — columna destacada */
.bc-saldo-cell {
    background: linear-gradient(135deg,#1e3a5f,#1e40af) !important;
    color:#fff !important;
    font-weight:800 !important;
    font-size:.82rem !important;
    text-align:center;
    min-width:68px;
    border-color:#2563eb !important;
    letter-spacing:.02em;
}
.bc-saldo-positivo { color:#86efac !important; }
.bc-saldo-cero     { color:#fcd34d !important; }
.bc-saldo-negativo { color:#fca5a5 !important; }
html.dark .bc-saldo-cell { background:linear-gradient(135deg,#1e1b4b,#1e3a5f) !important; }

/* Tabs reportes Productos / Servicios */
.rpt-tab-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.45rem 1.1rem; font-size:.82rem; font-weight:700; border:none; border-radius:.5rem; cursor:pointer; transition:background .15s,color .15s; background:transparent; color:#6b7280; }
.rpt-tab-btn:hover { background:#f3f4f6; color:#374151; }
.rpt-tab-active { background:#7c3aed !important; color:#fff !important; }
.rpt-tab-active .rpt-tab-cnt { background:rgba(255,255,255,0.25) !important; color:#fff !important; }
.rpt-tab-btn:not(.rpt-tab-active) .rpt-tab-cnt { background:#e9d5ff; color:#7c3aed; }
html.dark .rpt-tab-btn { color:#94a3b8; }
html.dark .rpt-tab-btn:hover { background:#1e293b; color:#e2e8f0; }
html.dark .rpt-tab-active { background:#7c3aed !important; color:#fff !important; }
</style>
@endpush

@section('content')

<div class="mb-5">
    <h1 class="text-2xl font-bold text-gray-800">Reportes</h1>
    <p class="text-sm text-gray-500 mt-1">Reportería BINCARD — Trazabilidad y control de inventario</p>
</div>

{{-- Tab selector Productos / Servicios --}}
<div class="mb-6 flex items-center gap-2">
    <button type="button" id="tab-btn-rpt-productos" onclick="switchRptTab('productos')"
            class="rpt-tab-btn {{ isset($dataServicio) ? '' : 'rpt-tab-active' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
        </svg>
        Productos
        @if(($productos ?? collect())->count() > 0)
        <span class="rpt-tab-cnt text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[1.25rem] text-center">{{ ($productos ?? collect())->count() }}</span>
        @endif
    </button>
    <button type="button" id="tab-btn-rpt-servicios" onclick="switchRptTab('servicios')"
            class="rpt-tab-btn {{ isset($dataServicio) ? 'rpt-tab-active' : '' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
        </svg>
        Servicios
        @if(($serviciosF ?? collect())->count() > 0)
        <span class="rpt-tab-cnt text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[1.25rem] text-center">{{ ($serviciosF ?? collect())->count() }}</span>
        @endif
    </button>
</div>

<div id="tab-panel-rpt-productos" style="{{ isset($dataServicio) ? 'display:none' : '' }}">

{{-- ══ FILTROS ══════════════════════════════════════════════════════════ --}}
<form method="GET" action="{{ route('admin.reportes.bincard') }}" id="form-bincard">
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        Filtros del Reporte
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Producto <span class="text-red-500">*</span></label>
            <select name="producto_id" required
                    style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.8rem; background:#fff;">
                <option value="">— Selecciona un producto —</option>
                @foreach($productos as $p)
                    <option value="{{ $p->id }}" {{ (request('producto_id') == $p->id || (isset($data) && $data['producto']->id == $p->id)) ? 'selected' : '' }}>
                        {{ $p->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde', isset($data) ? ($data['filtros']['fecha_desde'] ?? '') : '') }}"
                   style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.8rem;">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta', isset($data) ? ($data['filtros']['fecha_hasta'] ?? '') : '') }}"
                   style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.8rem;">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de movimiento</label>
            <select name="tipo"
                    style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.8rem; background:#fff;">
                <option value="">— Todos —</option>
                @foreach(['entrada','salida','devolucion','ajuste','merma','traslado','retiro'] as $t)
                    <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Filtros avanzados colapsables --}}
    <div style="margin-top:.75rem;">
        <button type="button" onclick="toggleFiltrosAvanzados()"
                style="font-size:.75rem; font-weight:600; color:#4f46e5; background:none; border:none; cursor:pointer; padding:0; display:inline-flex; align-items:center; gap:.3rem;">
            <svg id="icon-filtros-adv" style="width:13px;height:13px; transition:transform .2s;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
            Filtros avanzados
            @if(request()->hasAny(['origen','registrado_por','usuario_filtro','proveedor_filtro','n_documento_filtro']))
                <span style="background:#ef4444; color:#fff; font-size:.65rem; font-weight:700; padding:1px 6px; border-radius:9999px;">activos</span>
            @endif
        </button>
        <div id="filtros-avanzados" style="{{ request()->hasAny(['origen','registrado_por','usuario_filtro','proveedor_filtro','n_documento_filtro']) ? '' : 'display:none;' }} margin-top:.75rem;">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Origen</label>
                    <select name="origen"
                            style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.78rem; background:#fff;">
                        <option value="">— Todos —</option>
                        @foreach(['sicd'=>'SICD / OC','gasto_menor'=>'Compra Directa','solicitud'=>'Solicitud','computador_armado'=>'Armado'] as $val => $label)
                            <option value="{{ $val }}" {{ request('origen') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Usuario (solicitante)</label>
                    <input type="text" name="usuario_filtro" value="{{ request('usuario_filtro') }}"
                           placeholder="Nombre..."
                           style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.78rem;">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Registrado por</label>
                    <input type="text" name="registrado_por" value="{{ request('registrado_por') }}"
                           placeholder="Admin que ejecutó..."
                           style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.78rem;">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Proveedor</label>
                    <input type="text" name="proveedor_filtro" value="{{ request('proveedor_filtro') }}"
                           placeholder="Nombre proveedor..."
                           style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.78rem;">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">N° Documento</label>
                    <input type="text" name="n_documento_filtro" value="{{ request('n_documento_filtro') }}"
                           placeholder="SOL-1, OC-xxx, SICD..."
                           style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.78rem;">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 flex items-center gap-3">
        <button type="submit"
                style="padding:.45rem 1.25rem; font-size:.82rem; font-weight:700; color:#fff; background:#4f46e5; border:none; border-radius:.5rem; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem;"
                onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Generar BINCARD
        </button>
        @if(isset($data))
        <a href="{{ route('admin.reportes.bincard.excel', request()->query()) }}"
           style="padding:.45rem 1.1rem; font-size:.82rem; font-weight:700; color:#fff; background:#16a34a; border:none; border-radius:.5rem; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; text-decoration:none;"
           onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar Excel
        </a>
        <a href="{{ route('admin.reportes.bincard.pdf', request()->query()) }}" target="_blank"
           style="padding:.45rem 1.1rem; font-size:.82rem; font-weight:700; color:#fff; background:#dc2626; border:none; border-radius:.5rem; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; text-decoration:none;"
           onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Exportar PDF
        </a>
        @endif
        <a href="{{ route('admin.reportes.index') }}"
           style="padding:.45rem .9rem; font-size:.82rem; font-weight:600; color:#6b7280; background:#f3f4f6; border:none; border-radius:.5rem; text-decoration:none;">
            Limpiar
        </a>
    </div>
</div>
</form>

@if(!isset($data))
{{-- Placeholder cuando no hay datos --}}
<div class="bg-white rounded-xl shadow py-24 text-center text-gray-400">
    <svg class="w-14 h-14 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <p class="text-base font-semibold text-gray-500">Selecciona un producto y genera el reporte BINCARD</p>
    <p class="text-sm mt-1 text-gray-400">Trazabilidad completa · Valorización · Documentos · Proveedores</p>
</div>

@else
{{-- ══ ENCABEZADO DEL REPORTE ══════════════════════════════════════════ --}}
@php $producto = $data['producto']; @endphp

<div class="bg-white rounded-xl shadow overflow-hidden mb-5 print:shadow-none">
    {{-- Header institucional --}}
    <div style="background:#1e3a5f; color:#fff; padding:1rem 1.5rem;">
        <div class="flex items-center justify-between">
            <div>
                <p style="font-size:0.7rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; opacity:.7; margin:0;">Sistema de Gestión de Inventario</p>
                <h2 style="font-size:1.25rem; font-weight:800; margin:.1rem 0 0;">
                    @if($data['es_servicio'])
                        HISTORIAL OPERACIONAL — SERVICIO ADMINISTRATIVO
                    @else
                        TARJETA BINCARD — CONTROL DE EXISTENCIAS
                    @endif
                </h2>
            </div>
            <div style="text-align:right; font-size:0.72rem; opacity:.8;">
                <p style="margin:0;">Emitido: {{ $data['generado_at'] }}</p>
                <p style="margin:.1rem 0 0;">Por: <strong>{{ $data['generado_por'] }}</strong></p>
            </div>
        </div>
    </div>

    {{-- Info producto --}}
    <div style="padding:1rem 1.5rem; background:#f8fafc; border-bottom:2px solid #e2e8f0;">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Producto</p>
                <p style="font-size:.95rem; font-weight:800; color:#1e293b; margin:.1rem 0 0;">{{ $producto->nombre }}</p>
            </div>
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Categoría / Familia</p>
                <p style="font-size:.85rem; font-weight:600; color:#374151; margin:.1rem 0 0;">
                    {{ $producto->categoria?->nombre ?? '—' }} / {{ $producto->categoria?->familia?->nombre ?? '—' }}
                </p>
            </div>
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Marca</p>
                <p style="font-size:.85rem; font-weight:600; color:#374151; margin:.1rem 0 0;">
                    {{ $producto->marca?->nombre ?? '—' }}
                </p>
            </div>
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Unidad / Ubicación</p>
                <p style="font-size:.85rem; font-weight:600; color:#374151; margin:.1rem 0 0;">
                    {{ $producto->unidadMedida?->abreviacion ?? $producto->unidad ?? '—' }} · {{ $producto->container?->nombre ?? '—' }}
                </p>
            </div>
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Centro de Costo</p>
                <p style="font-size:.85rem; font-weight:600; color:#374151; margin:.1rem 0 0;">
                    {{ $producto->centroCosto?->nombre_completo ?? '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- KPIs de stock (bienes físicos) / Resumen operacional (servicios) --}}
    <div style="padding:.75rem 1.5rem; background:#fff; border-bottom:1px solid #e2e8f0;">
    @if($data['es_servicio'])
        {{-- Servicio: resumen sin stock físico ni valorización --}}
        @php $ultimaFila = collect($data['filas'])->last(); @endphp
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach([
                ['Total Registros',          count($data['filas']),   '#4f46e5'],
                ['Solicitudes/Asociaciones', $data['total_entradas'], '#16a34a'],
                ['Ejecuciones/Consumos',     $data['total_salidas'],  '#f59e0b'],
            ] as [$label, $val, $color])
            <div style="text-align:center; padding:.5rem; background:#f8fafc; border-radius:.5rem; border:1px solid #e2e8f0;">
                <p style="font-size:.65rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin:0;">{{ $label }}</p>
                <p style="font-size:1.3rem; font-weight:800; color:{{ $color }}; margin:.1rem 0 0;">{{ $val ?: '—' }}</p>
            </div>
            @endforeach
        </div>
        @if($ultimaFila)
        <p style="margin:.6rem 0 0; font-size:.72rem; color:#6b7280;">
            Última actividad: <strong>{{ $ultimaFila['fecha'] }}</strong> — {{ $ultimaFila['usuario'] }}
        </p>
        @endif
    @else
        {{-- Bien físico: KPIs de inventario completos --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @php $estado = $producto->estadoStock(); @endphp
            @foreach([
                ['Stock Actual', $producto->stock_actual, $estado === 'critico' ? '#ef4444' : ($estado === 'minimo' ? '#f59e0b' : '#16a34a')],
                ['Mínimo', $producto->stock_minimo ?? '—', '#6b7280'],
                ['Crítico', $producto->stock_critico ?? '—', '#6b7280'],
                ['Total Entradas', $data['total_entradas'], '#16a34a'],
                ['Total Salidas', $data['total_salidas'], '#ef4444'],
                ['Saldo Calculado', $data['saldo_final'], '#4f46e5'],
            ] as [$label, $val, $color])
            <div style="text-align:center; padding:.5rem; background:#f8fafc; border-radius:.5rem; border:1px solid #e2e8f0;">
                <p style="font-size:.65rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin:0;">{{ $label }}</p>
                <p style="font-size:1.3rem; font-weight:800; color:{{ $color }}; margin:.1rem 0 0;">{{ $val }}</p>
            </div>
            @endforeach
        </div>

        @if(auth()->user()->esAdmin() && ($data['costo_promedio'] || $data['ultimo_costo']))
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-3">
            @foreach([
                ['Costo Prom. c/IVA', $data['costo_promedio'] ? '$' . number_format($data['costo_promedio'], 0, ',', '.') : '—'],
                ['Último Costo c/IVA', $data['ultimo_costo'] ? '$' . number_format($data['ultimo_costo'], 0, ',', '.') : '—'],
                ['Valor Inventario', $data['valor_inventario'] ? '$' . number_format($data['valor_inventario'], 0, ',', '.') : '—'],
            ] as [$label, $val])
            <div style="text-align:center; padding:.5rem; background:#eff6ff; border-radius:.5rem; border:1px solid #bfdbfe;">
                <p style="font-size:.65rem; font-weight:600; color:#1e40af; text-transform:uppercase; letter-spacing:.04em; margin:0;">{{ $label }}</p>
                <p style="font-size:1.1rem; font-weight:800; color:#1d4ed8; margin:.1rem 0 0;">{{ $val }}</p>
            </div>
            @endforeach
        </div>
        @endif
    @endif
    </div>
</div>

{{-- ══ TABLA PRINCIPAL ════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        @if($data['es_servicio'])
        <h3 class="text-sm font-semibold text-gray-700">Historial Operacional del Servicio</h3>
        @else
        <h3 class="text-sm font-semibold text-gray-700">Historial de Movimientos</h3>
        @endif
        <span class="text-xs text-gray-400">{{ count($data['filas']) }} {{ $data['es_servicio'] ? 'registro(s)' : 'movimiento(s)' }}</span>
    </div>
    <div class="overflow-x-auto">

    @if($data['es_servicio'])
    {{-- ── Tabla historial operacional (servicios) ─────────────────────── --}}
    <table style="width:100%; border-collapse:collapse; font-size:.75rem;">
        <thead>
            <tr>
                <th class="bincard-th" style="text-align:left;">Fecha</th>
                <th class="bincard-th">Acción</th>
                <th class="bincard-th" style="text-align:left;">N° Documento</th>
                <th class="bincard-th">RUT Proveedor</th>
                <th class="bincard-th" style="text-align:left;">Proveedor</th>
                <th class="bincard-th" style="text-align:left;">Usuario</th>
                <th class="bincard-th" style="text-align:left; min-width:180px;">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['filas'] as $idx => $fila)
            @php
                [$accionLabel, $accionCls] = match(strtolower($fila['tipo_movimiento'])) {
                    'entrada'            => ['Servicio asociado',     'bc-accion-recepcion'],
                    'salida', 'retiro'   => ['Servicio ejecutado',    'bc-accion-ejecucion'],
                    'merma'              => ['Baja administrativa',   'bc-accion-baja'],
                    default              => [ucfirst(strtolower($fila['tipo_movimiento'])), 'bc-accion-admin'],
                };
                $rowBg = $idx % 2 === 0 ? 'bincard-neutral' : 'bincard-alt';
            @endphp
            <tr class="{{ $rowBg }}">
                <td class="bincard-td whitespace-nowrap">{{ $fila['fecha'] }}</td>
                <td class="bincard-td text-center">
                    <span class="{{ $accionCls }}"
                          style="font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:9999px; white-space:nowrap; display:inline-block;">
                        {{ $accionLabel }}
                    </span>
                </td>
                <td class="bincard-td font-mono" style="color:#4f46e5; font-size:.72rem;">{{ $fila['n_documento'] }}</td>
                <td class="bincard-td text-center text-gray-600 whitespace-nowrap">{{ $fila['rut_proveedor'] }}</td>
                <td class="bincard-td text-gray-700" style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $fila['proveedor'] }}</td>
                <td class="bincard-td text-gray-600 whitespace-nowrap">{{ $fila['usuario'] }}</td>
                <td class="bincard-td text-gray-500" style="font-size:.72rem; max-width:200px;">{{ $fila['observaciones'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; padding:2rem; color:#9ca3af; font-size:.82rem;">
                    No hay registros operacionales para los filtros seleccionados.
                </td>
            </tr>
            @endforelse

            @if(count($data['filas']) > 0)
            <tr style="background:#1e3a5f; color:#fff; font-weight:700;">
                <td class="bincard-td" colspan="7" style="color:#bfdbfe; border-color:#2563eb; text-align:right; font-size:.8rem;">
                    TOTAL DE REGISTROS
                </td>
                <td class="bincard-td text-center" style="color:#a5b4fc; font-size:.9rem; border-color:#2563eb;">
                    {{ count($data['filas']) }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    @else
    {{-- ── Tabla BINCARD física (bienes físicos) ───────────────────────── --}}
    <table style="width:100%; border-collapse:collapse; font-size:.75rem;">
        <thead>
            <tr>
                <th class="bincard-th" style="text-align:left;">Fecha</th>
                <th class="bincard-th">Tipo Mov.</th>
                <th class="bincard-th">Origen</th>
                <th class="bincard-th" style="text-align:left;">N° Documento</th>
                <th class="bincard-th">RUT Proveedor</th>
                <th class="bincard-th" style="text-align:left;">Proveedor</th>
                <th class="bincard-th" style="color:#cbd5e1;">Stk. Ant.</th>
                <th class="bincard-th" style="color:#86efac;">Entrada</th>
                <th class="bincard-th" style="color:#fca5a5;">Salida</th>
                <th class="bincard-th" style="color:#a5b4fc;">Saldo</th>
                @if(auth()->user()->esAdmin())
                <th class="bincard-th">Costo Unit. c/IVA</th>
                <th class="bincard-th">Valor Mov.</th>
                <th class="bincard-th">Costo Prom. c/IVA</th>
                <th class="bincard-th">Valor Saldo</th>
                @endif
                <th class="bincard-th" style="text-align:left;">Usuario</th>
                <th class="bincard-th" style="text-align:left;">Ejecutado Por</th>
                <th class="bincard-th" style="text-align:left; min-width:160px;">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['filas'] as $idx => $fila)
            @php
                $esE = $fila['entrada'] !== null;
                $esS = $fila['salida']  !== null;
                $base = $idx % 2 === 0 ? '' : 'bincard-alt';
                $cls  = $esE ? 'bincard-entrada' : ($esS ? 'bincard-salida' : $base);
                $origCls = match($fila['origen_label']) {
                    'Solicitud'  => 'bc-orig-sol',
                    'Devolución' => 'bc-orig-dev',
                    'Compra'     => 'bc-orig-compra',
                    'Armado'     => 'bc-orig-armado',
                    'Ajuste'     => 'bc-orig-ajuste',
                    'Traslado'   => 'bc-orig-traslado',
                    default      => 'bc-orig-manual',
                };
                $saldoCls = $fila['saldo'] > 0 ? 'bc-saldo-positivo' : ($fila['saldo'] == 0 ? 'bc-saldo-cero' : 'bc-saldo-negativo');
                $esSolicitud = ($fila['_origen'] ?? '') === 'solicitud' && !empty($fila['_origen_id']);
            @endphp
            <tr class="{{ $cls }}">
                <td class="bincard-td whitespace-nowrap">{{ $fila['fecha'] }}</td>
                <td class="bincard-td text-center">
                    <span class="{{ $esE ? 'bc-mov-entrada' : ($esS ? 'bc-mov-salida' : 'bc-mov-neutral') }}"
                          style="font-size:.68rem; font-weight:700; padding:2px 7px; border-radius:9999px; white-space:nowrap; display:inline-block;">
                        {{ $fila['tipo_movimiento'] }}
                    </span>
                </td>
                <td class="bincard-td text-center">
                    <span class="{{ $origCls }}"
                          style="font-size:.65rem; font-weight:700; padding:2px 6px; border-radius:9999px; white-space:nowrap; display:inline-block;">
                        {{ $fila['origen_label'] }}
                    </span>
                </td>
                <td class="bincard-td font-mono" style="font-size:.72rem;">
                    @if($esSolicitud)
                        <a href="{{ route('admin.solicitudes') }}" style="color:#4f46e5; font-weight:700; text-decoration:none;"
                           title="Ver solicitudes">{{ $fila['n_documento'] }}</a>
                    @else
                        <span style="color:#4f46e5; font-weight:700;">{{ $fila['n_documento'] }}</span>
                    @endif
                    @if(!empty($fila['n_referencia']))
                        <span style="display:block; font-size:.65rem; color:#94a3b8; font-weight:400; margin-top:2px;">ref. {{ $fila['n_referencia'] }}</span>
                    @endif
                </td>
                <td class="bincard-td text-center text-gray-600 whitespace-nowrap">{{ $fila['rut_proveedor'] }}</td>
                <td class="bincard-td text-gray-700" style="max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $fila['proveedor'] }}</td>
                <td class="bincard-td text-center text-gray-400" style="font-size:.7rem;">{{ $fila['stock_anterior'] }}</td>
                <td class="bincard-td text-center font-bold" style="color:#16a34a;">
                    {{ $fila['entrada'] !== null ? $fila['entrada'] : '' }}
                </td>
                <td class="bincard-td text-center font-bold" style="color:#dc2626;">
                    {{ $fila['salida'] !== null ? $fila['salida'] : '' }}
                </td>
                <td class="bincard-td bc-saldo-cell">
                    <span class="{{ $saldoCls }}">{{ $fila['saldo'] }}</span>
                </td>
                @if(auth()->user()->esAdmin())
                <td class="bincard-td text-right text-gray-700">
                    {{ $fila['costo_unitario'] ? '$' . number_format($fila['costo_unitario'], 0, ',', '.') : '—' }}
                </td>
                <td class="bincard-td text-right font-semibold">
                    {{ $fila['valor_movimiento'] ? '$' . number_format($fila['valor_movimiento'], 0, ',', '.') : '—' }}
                </td>
                <td class="bincard-td text-right text-gray-500" style="font-size:.7rem;">
                    {{ $fila['costo_promedio'] ? '$' . number_format($fila['costo_promedio'], 0, ',', '.') : '—' }}
                </td>
                <td class="bincard-td text-right font-semibold" style="color:#1d4ed8;">
                    {{ $fila['valor_saldo'] ? '$' . number_format($fila['valor_saldo'], 0, ',', '.') : '—' }}
                </td>
                @endif
                <td class="bincard-td text-gray-600 whitespace-nowrap">{{ $fila['usuario'] }}</td>
                <td class="bincard-td text-gray-500 whitespace-nowrap" style="font-size:.7rem;">{{ $fila['registrado_por'] }}</td>
                <td class="bincard-td text-gray-500" style="font-size:.72rem; max-width:180px;">{{ $fila['observaciones'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="20" style="text-align:center; padding:2rem; color:#9ca3af; font-size:.82rem;">
                    No hay movimientos para los filtros seleccionados.
                </td>
            </tr>
            @endforelse

            {{-- Fila de totales --}}
            @if(count($data['filas']) > 0)
            <tr style="background:#1e3a5f; color:#fff; font-weight:700;">
                <td class="bincard-td" colspan="8" style="color:#fff; border-color:#2563eb; text-align:right; font-size:.8rem;">
                    TOTALES Y SALDO FINAL
                </td>
                <td class="bincard-td text-center" style="color:#86efac; font-size:.9rem; border-color:#2563eb;">{{ $data['total_entradas'] ?: '—' }}</td>
                <td class="bincard-td text-center" style="color:#fca5a5; font-size:.9rem; border-color:#2563eb;">{{ $data['total_salidas'] ?: '—' }}</td>
                <td class="bincard-td text-center" style="color:#a5b4fc; font-size:.9rem; border-color:#2563eb;">{{ $data['saldo_final'] }}</td>
                @if(auth()->user()->esAdmin())
                <td class="bincard-td" style="border-color:#2563eb;"></td>
                <td class="bincard-td" style="border-color:#2563eb;"></td>
                <td class="bincard-td text-right" style="color:#bfdbfe; border-color:#2563eb; font-size:.8rem;">
                    {{ $data['costo_promedio'] ? '$' . number_format($data['costo_promedio'], 0, ',', '.') : '—' }}
                </td>
                <td class="bincard-td text-right" style="color:#a5b4fc; border-color:#2563eb; font-size:.9rem;">
                    {{ $data['valor_inventario'] ? '$' . number_format($data['valor_inventario'], 0, ',', '.') : '—' }}
                </td>
                @endif
                <td class="bincard-td" colspan="3" style="border-color:#2563eb;"></td>
            </tr>
            @endif
        </tbody>
    </table>
    @endif

    </div>
</div>
@endif

{{-- Modal: advertencia bincard existente --}}
<div id="modal-bincard-existente" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 24px 60px rgba(0,0,0,.25);width:440px;max-width:calc(100vw - 2rem);padding:1.75rem;animation:bc-in .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="display:flex;align-items:flex-start;gap:.85rem;margin-bottom:1.25rem;">
            <div style="flex-shrink:0;width:2.75rem;height:2.75rem;border-radius:9999px;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                <svg style="width:1.35rem;height:1.35rem;" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <div style="flex:1;">
                <p style="font-size:.9375rem;font-weight:700;color:#1f2937;margin:0 0 .35rem;">Ya existe un BINCARD generado</p>
                <p style="font-size:.8125rem;color:#6b7280;margin:0;line-height:1.55;">
                    El producto <span id="bc-modal-nombre" style="font-weight:700;color:#374151;"></span> ya tiene un BINCARD generado anteriormente. ¿Qué deseas hacer?
                </p>
            </div>
        </div>
        <div style="display:flex;gap:.65rem;justify-content:flex-end;border-top:1px solid #f3f4f6;padding-top:1rem;">
            <button type="button" onclick="cerrarModalBincardExistente()"
                    style="padding:.45rem 1rem;font-size:.82rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:none;border-radius:.5rem;cursor:pointer;">
                Cancelar
            </button>
            <a id="bc-modal-ver-link" href="#"
               style="padding:.45rem 1.1rem;font-size:.82rem;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:.5rem;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Ver BINCARD
            </a>
            <button type="button" onclick="confirmarGenerarBincard()"
                    style="padding:.45rem 1.1rem;font-size:.82rem;font-weight:600;color:#fff;background:#16a34a;border:none;border-radius:.5rem;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Generar igual
            </button>
        </div>
    </div>
</div>

@push('head')
<style>
@keyframes bc-in { from{opacity:0;transform:scale(.94)} to{opacity:1;transform:scale(1)} }
</style>
@endpush

@push('scripts')
<script>
function toggleFiltrosAvanzados() {
    var panel = document.getElementById('filtros-avanzados');
    var icon  = document.getElementById('icon-filtros-adv');
    var open  = panel.style.display !== 'none';
    panel.style.display = open ? 'none' : 'block';
    icon.style.transform = open ? '' : 'rotate(180deg)';
}

const BINCARDS_POR_PRODUCTO = @json($bincardsPorProducto ?? []);
const BINCARD_URL_BASE = '{{ route('admin.reportes.bincard') }}';

let _submitForzado = false;

document.getElementById('form-bincard').addEventListener('submit', function(e) {
    if (_submitForzado) { _submitForzado = false; return; }

    const select = this.querySelector('[name="producto_id"]');
    const prodId = parseInt(select.value);
    if (!prodId) return;

    const existente = BINCARDS_POR_PRODUCTO[prodId];
    if (!existente) return;

    e.preventDefault();

    // Nombre del producto seleccionado
    const nombreOpt = select.options[select.selectedIndex].text;
    document.getElementById('bc-modal-nombre').textContent = nombreOpt;

    // Construir URL "Ver Bincard" con filtros del reporte existente
    const params = new URLSearchParams();
    if (existente.producto_id) params.set('producto_id', existente.producto_id);
    if (existente.fecha_desde)  params.set('fecha_desde',  existente.fecha_desde);
    if (existente.fecha_hasta)  params.set('fecha_hasta',  existente.fecha_hasta);
    if (existente.tipo)         params.set('tipo',         existente.tipo);
    params.set('solo_ver', '1');
    document.getElementById('bc-modal-ver-link').href = BINCARD_URL_BASE + '?' + params.toString();

    document.getElementById('modal-bincard-existente').style.display = 'flex';
});

function cerrarModalBincardExistente() {
    document.getElementById('modal-bincard-existente').style.display = 'none';
}

function confirmarGenerarBincard() {
    cerrarModalBincardExistente();
    _submitForzado = true;
    document.getElementById('form-bincard').submit();
}

document.getElementById('modal-bincard-existente').addEventListener('click', function(e) {
    if (e.target === e.currentTarget) cerrarModalBincardExistente();
});
</script>
@endpush

</div>{{-- #tab-panel-rpt-productos --}}

{{-- ══ TAB SERVICIOS ══════════════════════════════════════════════════════ --}}
<div id="tab-panel-rpt-servicios" style="{{ isset($dataServicio) ? '' : 'display:none' }}">

<form method="GET" action="{{ route('admin.reportes.bincard.servicio') }}" id="form-bincard-svc">
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Filtros del BINCARD Operacional
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Servicio <span class="text-red-500">*</span></label>
            <select name="producto_id" required
                    style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.8rem; background:#fff;">
                <option value="">— Selecciona un servicio —</option>
                @foreach($serviciosF ?? [] as $s)
                    <option value="{{ $s->id }}" {{ (request('producto_id') == $s->id || (isset($dataServicio) && $dataServicio['producto']->id == $s->id)) ? 'selected' : '' }}>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
            <input type="date" name="fecha_desde"
                   value="{{ request('fecha_desde', isset($dataServicio) ? ($dataServicio['filtros']['fecha_desde'] ?? '') : '') }}"
                   style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.8rem;">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
            <input type="date" name="fecha_hasta"
                   value="{{ request('fecha_hasta', isset($dataServicio) ? ($dataServicio['filtros']['fecha_hasta'] ?? '') : '') }}"
                   style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.8rem;">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Estado</label>
            <select name="estado"
                    style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.8rem; background:#fff;">
                <option value="">— Todos —</option>
                @foreach(['pendiente','aprobado','en_proceso','ejecutado','validado','cerrado','cancelado'] as $est)
                    <option value="{{ $est }}" {{ (request('estado', isset($dataServicio) ? ($dataServicio['filtros']['estado'] ?? '') : '') === $est) ? 'selected' : '' }}>
                        {{ \App\Models\ServicioEstado::label($est) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mt-4 flex items-center gap-3">
        <button type="submit"
                style="padding:.45rem 1.25rem; font-size:.82rem; font-weight:700; color:#fff; background:#7c3aed; border:none; border-radius:.5rem; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem;"
                onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Generar BINCARD Operacional
        </button>
        <a href="{{ route('admin.reportes.index') }}"
           style="padding:.45rem .9rem; font-size:.82rem; font-weight:600; color:#6b7280; background:#f3f4f6; border:none; border-radius:.5rem; text-decoration:none;">
            Limpiar
        </a>
    </div>
</div>
</form>

@if(!isset($dataServicio))
<div class="bg-white rounded-xl shadow py-24 text-center text-gray-400">
    <svg class="w-14 h-14 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <p class="text-base font-semibold text-gray-500">Selecciona un servicio y genera el BINCARD operacional</p>
    <p class="text-sm mt-1 text-gray-400">Historial de transiciones · Estados · Responsables · Documentos</p>
</div>

@else
{{-- ══ ENCABEZADO SERVICIO ════════════════════════════════════════════ --}}
@php
    $svcProd     = $dataServicio['producto'];
    $svcColores  = \App\Models\ServicioEstado::colores($dataServicio['estado_actual']);
    $svcProgreso = \App\Models\ServicioEstado::progreso($dataServicio['estado_actual']);
    $svcLabel    = \App\Models\ServicioEstado::label($dataServicio['estado_actual']);
    $svcFiltros  = $dataServicio['filtros'];
    $filasSvc    = $dataServicio['filas'];
    $svcUltima   = count($filasSvc) ? $filasSvc[count($filasSvc)-1]['fecha'] : '—';
@endphp

<div class="bg-white rounded-xl shadow overflow-hidden mb-5 print:shadow-none">
    <div style="background:#1e3a5f; color:#fff; padding:1rem 1.5rem;">
        <div class="flex items-center justify-between">
            <div>
                <p style="font-size:0.7rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; opacity:.7; margin:0;">Sistema de Gestión de Inventario</p>
                <h2 style="font-size:1.25rem; font-weight:800; margin:.1rem 0 0;">BINCARD OPERACIONAL — SERVICIO</h2>
            </div>
            <div style="text-align:right; font-size:0.72rem; opacity:.8;">
                <p style="margin:0;">Emitido: {{ $dataServicio['generado_at'] }}</p>
                <p style="margin:.1rem 0 0;">Por: <strong>{{ $dataServicio['generado_por'] }}</strong></p>
            </div>
        </div>
    </div>
    <div style="padding:1rem 1.5rem; background:#f8fafc; border-bottom:2px solid #e2e8f0;">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Servicio</p>
                <p style="font-size:.95rem; font-weight:800; color:#1e293b; margin:.1rem 0 0;">{{ $svcProd->nombre }}</p>
            </div>
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Categoría / Familia</p>
                <p style="font-size:.85rem; font-weight:600; color:#374151; margin:.1rem 0 0;">
                    {{ $svcProd->categoria?->nombre ?? '—' }} / {{ $svcProd->categoria?->familia?->nombre ?? '—' }}
                </p>
            </div>
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Centro de Costo</p>
                <p style="font-size:.85rem; font-weight:600; color:#374151; margin:.1rem 0 0;">
                    {{ $svcProd->centroCosto?->nombre_completo ?? '—' }}
                </p>
            </div>
            <div>
                <p style="font-size:.65rem; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.06em;">Período</p>
                <p style="font-size:.8rem; color:#6b7280; margin:.1rem 0 0;">
                    {{ isset($svcFiltros['fecha_desde']) ? 'Desde '.$svcFiltros['fecha_desde'] : 'Sin rango' }}{{ isset($svcFiltros['fecha_hasta']) ? ' — '.$svcFiltros['fecha_hasta'] : '' }}
                </p>
            </div>
        </div>
    </div>
    <div style="padding:.75rem 1.5rem; background:#fff; border-bottom:1px solid #e2e8f0;">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div style="text-align:center; padding:.5rem; background:#f8fafc; border-radius:.5rem; border:1px solid #e2e8f0;">
                <p style="font-size:.65rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin:0;">Estado Actual</p>
                <p style="margin:.3rem 0 0;">
                    <span style="font-size:.78rem; font-weight:700; padding:3px 10px; border-radius:9999px; background:{{ $svcColores['bg'] }}; color:{{ $svcColores['text'] }};">
                        {{ $svcLabel }}
                    </span>
                </p>
            </div>
            <div style="text-align:center; padding:.5rem; background:#f8fafc; border-radius:.5rem; border:1px solid #e2e8f0;">
                <p style="font-size:.65rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin:0;">Total Transiciones</p>
                <p style="font-size:1.3rem; font-weight:800; color:#4f46e5; margin:.1rem 0 0;">{{ $dataServicio['total_transiciones'] }}</p>
            </div>
            <div style="text-align:center; padding:.5rem; background:#f8fafc; border-radius:.5rem; border:1px solid #e2e8f0;">
                <p style="font-size:.65rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin:0;">Progreso</p>
                <p style="font-size:1.3rem; font-weight:800; color:{{ $svcColores['barra'] }}; margin:.1rem 0 0;">{{ $svcProgreso }}%</p>
            </div>
            <div style="text-align:center; padding:.5rem; background:#f8fafc; border-radius:.5rem; border:1px solid #e2e8f0;">
                <p style="font-size:.65rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin:0;">Última Fecha</p>
                <p style="font-size:.9rem; font-weight:700; color:#374151; margin:.1rem 0 0;">{{ $svcUltima }}</p>
            </div>
        </div>
    </div>
</div>

{{-- ══ TABLA OPERACIONAL ════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">Historial de Transiciones Operacionales</h3>
        <span class="text-xs text-gray-400">{{ $dataServicio['total_transiciones'] }} transición(es)</span>
    </div>
    <div class="overflow-x-auto">
    <table style="width:100%; border-collapse:collapse; font-size:.75rem;">
        <thead>
            <tr>
                <th class="bincard-th" style="text-align:left;">Fecha</th>
                <th class="bincard-th">Movimiento</th>
                <th class="bincard-th">Estado anterior</th>
                <th class="bincard-th">Estado nuevo</th>
                <th class="bincard-th" style="text-align:left;">Responsable</th>
                <th class="bincard-th" style="min-width:110px;">Avance</th>
                <th class="bincard-th" style="text-align:left;">Doc. referencia</th>
                <th class="bincard-th" style="text-align:left; min-width:180px;">Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filasSvc as $idx => $fila)
            @php
                $rowBg  = $idx % 2 === 0 ? 'bincard-neutral' : 'bincard-alt';
                $colNvo = \App\Models\ServicioEstado::colores($fila['estado_nuevo']);
                $colAnt = \App\Models\ServicioEstado::colores($fila['estado_anterior']);
            @endphp
            <tr class="{{ $rowBg }}">
                <td class="bincard-td whitespace-nowrap">{{ $fila['fecha'] }}</td>
                <td class="bincard-td text-center">
                    <span style="font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:9999px; white-space:nowrap; display:inline-block; background:{{ $colNvo['bg'] }}; color:{{ $colNvo['text'] }};">
                        {{ $fila['movimiento'] }}
                    </span>
                </td>
                <td class="bincard-td text-center">
                    <span style="font-size:.67rem; font-weight:600; padding:2px 7px; border-radius:9999px; white-space:nowrap; display:inline-block; background:{{ $colAnt['bg'] }}; color:{{ $colAnt['text'] }};">
                        {{ $fila['estado_label_ant'] }}
                    </span>
                </td>
                <td class="bincard-td text-center">
                    <span style="font-size:.67rem; font-weight:700; padding:2px 7px; border-radius:9999px; white-space:nowrap; display:inline-block; background:{{ $colNvo['bg'] }}; color:{{ $colNvo['text'] }};">
                        {{ $fila['estado_label_nvo'] }}
                    </span>
                </td>
                <td class="bincard-td text-gray-600 whitespace-nowrap">{{ $fila['responsable'] }}</td>
                <td class="bincard-td">
                    <div style="display:flex; align-items:center; gap:.4rem;">
                        <div style="flex:1; height:6px; background:#e2e8f0; border-radius:9999px; overflow:hidden;">
                            <div style="height:100%; border-radius:9999px; background:{{ $colNvo['barra'] }}; width:{{ $fila['progreso'] }}%;"></div>
                        </div>
                        <span style="font-size:.7rem; font-weight:700; color:{{ $colNvo['text'] }}; white-space:nowrap;">{{ $fila['progreso'] }}%</span>
                    </div>
                </td>
                <td class="bincard-td font-mono" style="color:#4f46e5; font-size:.72rem;">{{ $fila['documento_referencia'] }}</td>
                <td class="bincard-td text-gray-500" style="font-size:.72rem; max-width:200px;">{{ $fila['observacion'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; padding:2rem; color:#9ca3af; font-size:.82rem;">
                    No hay transiciones para los filtros seleccionados.
                </td>
            </tr>
            @endforelse
            @if($dataServicio['total_transiciones'] > 0)
            <tr style="background:#1e3a5f; color:#fff; font-weight:700;">
                <td class="bincard-td" colspan="5" style="color:#bfdbfe; border-color:#2563eb; text-align:right; font-size:.8rem;">
                    TOTAL DE TRANSICIONES
                </td>
                <td class="bincard-td text-center" style="color:#a5b4fc; font-size:.9rem; border-color:#2563eb;" colspan="3">
                    {{ $dataServicio['total_transiciones'] }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>
    </div>
</div>
@endif

</div>{{-- #tab-panel-rpt-servicios --}}

@push('scripts')
<script>
function switchRptTab(tab) {
    var panelProd = document.getElementById('tab-panel-rpt-productos');
    var panelSvc  = document.getElementById('tab-panel-rpt-servicios');
    var btnProd   = document.getElementById('tab-btn-rpt-productos');
    var btnSvc    = document.getElementById('tab-btn-rpt-servicios');
    if (tab === 'servicios') {
        panelProd.style.display = 'none';
        panelSvc.style.display  = '';
        btnProd.classList.remove('rpt-tab-active');
        btnSvc.classList.add('rpt-tab-active');
    } else {
        panelSvc.style.display  = 'none';
        panelProd.style.display = '';
        btnSvc.classList.remove('rpt-tab-active');
        btnProd.classList.add('rpt-tab-active');
    }
}
</script>
@endpush

@endsection
