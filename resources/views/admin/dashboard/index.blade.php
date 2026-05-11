@extends('layouts.app')

@section('title', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
    .kpi-card {
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,.10);
    }
    html.dark .kpi-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,.35);
    }
    .dash-card {
        transition: box-shadow .15s ease;
    }
    .dash-section-title {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: .5rem;
    }
    .alert-row { transition: background .12s; }
    .alert-row:hover { background: rgba(99,102,241,.06); }
    html.dark .alert-row:hover { background: rgba(99,102,241,.12); }
    /* KPI icon — emerald (Gastos Mes) */
    .kpi-icon-emerald { background: #ecfdf5; }
    html.dark .kpi-icon-emerald { background: rgba(6,78,59,0.22); }

    /* Bincard sub-cards — colores explícitos para no depender del build de Tailwind */
    .bincard-total {
        background: #eef2ff;        /* indigo-50 */
        border-radius: .75rem; padding: 1rem;
    }
    html.dark .bincard-total {
        background: rgba(67, 56, 202, 0.18);   /* indigo-900/18 */
    }
    .bincard-total .bc-label { color: #4f46e5; font-size:.75rem; font-weight:600; margin-bottom:.25rem; }
    html.dark .bincard-total .bc-label { color: #a5b4fc; }
    .bincard-total .bc-value { font-size:1.25rem; font-weight:700; color:#3730a3; }
    html.dark .bincard-total .bc-value { color: #c7d2fe; }
    .bincard-total .bc-sub { font-size:.75rem; color:#6366f1; margin-top:.125rem; }
    html.dark .bincard-total .bc-sub { color: #818cf8; }

    .bincard-entradas {
        background: #f0fdf4;        /* emerald-50 */
        border-radius: .75rem; padding: 1rem;
    }
    html.dark .bincard-entradas {
        background: rgba(6, 78, 59, 0.20);     /* emerald-900/20 */
    }
    .bincard-entradas .bc-label { color: #059669; font-size:.75rem; font-weight:600; margin-bottom:.25rem; }
    html.dark .bincard-entradas .bc-label { color: #6ee7b7; }
    .bincard-entradas .bc-value { font-size:1.25rem; font-weight:700; color:#065f46; }
    html.dark .bincard-entradas .bc-value { color: #a7f3d0; }
    .bincard-entradas .bc-sub { font-size:.75rem; color:#10b981; margin-top:.125rem; }
    html.dark .bincard-entradas .bc-sub { color: #34d399; }

    /* Skeleton / activity badge */
    .tipo-badge-entrada { background:#dcfce7; color:#15803d; }
    .tipo-badge-salida  { background:#fee2e2; color:#b91c1c; }
    html.dark .tipo-badge-entrada { background:rgba(22,163,74,.18); color:#86efac; }
    html.dark .tipo-badge-salida  { background:rgba(185,28,28,.18); color:#fca5a5; }

    .estado-pill {
        display: inline-block; font-size:.68rem; font-weight:600;
        padding:.15rem .55rem; border-radius:999px;
    }
</style>
@endpush

@section('content')

@php
    $moneda = fn($v) => '$' . number_format((float)$v, 0, ',', '.');
    $num    = fn($v) => number_format((int)$v, 0, ',', '.');
@endphp

{{-- ══════════════════════════════════════════════════════════
     HEADER
══════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 leading-tight">
        Bienvenido, {{ $user->name }}
    </h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
        {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
        &nbsp;·&nbsp;
        @if($user->centroCosto)
            <span class="font-mono font-semibold text-indigo-500 dark:text-indigo-400">{{ $user->centroCosto->acronimo }}</span>
        @else
            <span class="text-gray-400">Vista global</span>
        @endif
    </p>
</div>

{{-- ══════════════════════════════════════════════════════════
     KPI CARDS
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">

    {{-- STOCK TOTAL --}}
    <a href="{{ route('dashboard') }}" class="kpi-card block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="dash-section-title">Stock Total</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $num($stockStats->total_productos) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">productos activos</p>
        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700">
            <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $num($stockStats->total_unidades) }}</span>
            <span class="text-xs text-gray-400"> unidades en inventario</span>
        </div>
    </a>

    {{-- STOCK CRÍTICO --}}
    <a href="{{ route('dashboard') }}?filtro=critico" class="kpi-card block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="dash-section-title">Stock Crítico</span>
            <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/25 flex items-center justify-center">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold {{ ($stockStats->criticos + $stockStats->agotados) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
            {{ $num($stockStats->criticos + $stockStats->agotados) }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">bajo mínimo crítico</p>
        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700 flex gap-2 text-xs">
            <span class="text-red-500 font-semibold">{{ $num($stockStats->agotados) }} agotados</span>
            <span class="text-amber-500 font-semibold">{{ $num($stockStats->en_minimo) }} mínimo</span>
        </div>
    </a>

    {{-- SOLICITUDES --}}
    <a href="{{ route('admin.solicitudes') }}" class="kpi-card block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="dash-section-title">Solicitudes</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/25 flex items-center justify-center">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold {{ $solicitudesStats->pendientes > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100' }}">
            {{ $num($solicitudesStats->pendientes) }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">pendientes</p>
        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700 flex gap-2 text-xs">
            <span class="text-green-600 dark:text-green-400 font-semibold">{{ $num($solicitudesStats->aprobadas) }} aprob.</span>
            <span class="text-red-500 font-semibold">{{ $num($solicitudesStats->rechazadas) }} rechaz.</span>
        </div>
    </a>

    {{-- ÓRDENES DE COMPRA --}}
    <a href="{{ route('admin.ordenes.index') }}" class="kpi-card block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="dash-section-title">Órd. Compra</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/25 flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $num($ocStats->total) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">total registradas</p>
        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700 flex gap-2 text-xs">
            <span class="text-amber-500 font-semibold">{{ $num($ocStats->pendientes) }} pend.</span>
            <span class="text-green-600 dark:text-green-400 font-semibold">{{ $num($ocStats->recibidas) }} recib.</span>
        </div>
    </a>

    {{-- SICD vs OC --}}
    <a href="{{ route('admin.ordenes.index') }}" class="kpi-card block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="dash-section-title">SICD vs OC</span>
            <div class="kpi-icon-emerald w-8 h-8 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            </div>
        </div>
        {{-- SICD referencial --}}
        <div class="flex items-baseline justify-between gap-1">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide shrink-0">SICD</span>
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 truncate text-right">{{ $moneda($sicdRefMes) }}</span>
        </div>
        {{-- OC adjudicada --}}
        <div class="flex items-baseline justify-between gap-1 mt-0.5">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide shrink-0">OC</span>
            <span class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate text-right">{{ $moneda($ocFinalMes) }}</span>
        </div>
        {{-- Diferencia --}}
        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700">
            @if($sicdRefMes > 0 || $ocFinalMes > 0)
                @if($difFinanciera < 0)
                    <span class="text-xs font-bold text-green-600 dark:text-green-400">↓ Ahorro {{ $moneda(abs($difFinanciera)) }}</span>
                @elseif($difFinanciera > 0)
                    <span class="text-xs font-bold text-red-500">↑ Sobrecosto {{ $moneda($difFinanciera) }}</span>
                @else
                    <span class="text-xs text-gray-400">Sin diferencia</span>
                @endif
            @else
                <span class="text-xs text-gray-400">Sin movimientos este mes</span>
            @endif
        </div>
    </a>

    {{-- EQUIPOS ARMADOS --}}
    <a href="{{ route('admin.computadores.index') }}" class="kpi-card block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="dash-section-title">Equipos</span>
            <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/25 flex items-center justify-center">
                <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $num($equiposStats->total) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">total armados</p>
        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-slate-700 flex gap-2 text-xs">
            <span class="text-green-600 dark:text-green-400 font-semibold">{{ $num($equiposStats->completos) }} listos</span>
            <span class="text-amber-500 font-semibold">{{ $num($equiposStats->en_armado) }} en armado</span>
        </div>
    </a>

</div>

{{-- ══════════════════════════════════════════════════════════
     ROW 2: GRÁFICO MOVIMIENTO + ALERTAS
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

    {{-- Gráfico Movimiento 30 días --}}
    <div class="lg:col-span-2 dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="dash-section-title mb-0">Movimiento de Inventario</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Últimos 30 días</p>
            </div>
            <div class="flex gap-3 text-xs">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#6366f1"></span><span class="text-gray-500 dark:text-gray-400">Entradas</span></span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#ef4444"></span><span class="text-gray-500 dark:text-gray-400">Salidas</span></span>
            </div>
        </div>
        <div class="relative" style="height:220px">
            <canvas id="chartMovimiento"></canvas>
        </div>
        <div class="mt-3 flex gap-6 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-slate-700 pt-3">
            <span>Entradas mes: <strong class="text-indigo-600 dark:text-indigo-400">{{ $num($movMes->entradas) }} un.</strong></span>
            <span>Salidas mes: <strong class="text-red-500">{{ $num($movMes->salidas) }} un.</strong></span>
        </div>
    </div>

    {{-- Panel Alertas --}}
    <div class="dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5 flex flex-col">
        <p class="dash-section-title">Alertas Operacionales</p>

        {{-- Stock Crítico --}}
        @if($alertasStockCritico->isNotEmpty())
        <div class="mb-3">
            <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-1.5 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                Stock Crítico ({{ $alertasStockCritico->count() }})
            </p>
            @foreach($alertasStockCritico->take(4) as $p)
            <div class="alert-row rounded-lg px-2 py-1.5 flex items-center justify-between gap-2 cursor-pointer" onclick="window.location='{{ route('admin.productos.show', $p->id) }}'">
                <span class="text-xs text-gray-700 dark:text-gray-300 truncate flex-1">{{ $p->nombre }}</span>
                <span class="text-xs font-bold {{ $p->stock_actual <= 0 ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }} whitespace-nowrap">
                    {{ $p->stock_actual <= 0 ? 'AGOTADO' : $p->stock_actual . ' u.' }}
                </span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- SICD Pendientes --}}
        @if($alertasSicd->isNotEmpty())
        <div class="mb-3">
            <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mb-1.5 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                SICD Pendientes ({{ $alertasSicd->count() }})
            </p>
            @foreach($alertasSicd->take(3) as $s)
            <div class="alert-row rounded-lg px-2 py-1.5 flex items-center justify-between gap-2 cursor-pointer" onclick="window.location='{{ route('admin.sicd.show', $s->id) }}'">
                <span class="text-xs font-mono text-indigo-600 dark:text-indigo-400">{{ $s->codigo_sicd }}</span>
                <span class="text-xs text-gray-400">{{ $s->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- OC Pendientes --}}
        @if($alertasOC->isNotEmpty())
        <div class="mb-3">
            <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 mb-1.5 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                OC Pendientes ({{ $alertasOC->count() }})
            </p>
            @foreach($alertasOC->take(3) as $oc)
            <div class="alert-row rounded-lg px-2 py-1.5 flex items-center justify-between gap-2 cursor-pointer" onclick="window.location='{{ route('admin.ordenes.show', $oc->id) }}'">
                <span class="text-xs font-mono text-blue-600 dark:text-blue-400 truncate">{{ $oc->numero_oc ?? 'Sin nº' }}</span>
                <span class="text-xs text-gray-400">{{ $oc->created_at->format('d/m') }}</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Sin categoría --}}
        @if($sinCategoria > 0)
        <div class="mt-auto pt-2 border-t border-gray-100 dark:border-slate-700">
            <a href="{{ route('dashboard') }}" class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    Productos sin categoría
                </span>
                <span class="font-bold text-amber-500">{{ $sinCategoria }}</span>
            </a>
        </div>
        @endif

        @if($alertasStockCritico->isEmpty() && $alertasSicd->isEmpty() && $alertasOC->isEmpty() && $sinCategoria === 0)
        <div class="flex-1 flex flex-col items-center justify-center text-center py-6">
            <svg class="w-10 h-10 text-green-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium text-green-600 dark:text-green-400">Sin alertas activas</p>
            <p class="text-xs text-gray-400 mt-1">Sistema operando normalmente</p>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     ROW 3: BINCARD + PANEL LOGÍSTICO
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    {{-- BINCARD / Valorización --}}
    <div class="dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="dash-section-title mb-0">Valorización BINCARD</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Resumen financiero inventario</p>
            </div>
            @if($user->tienePermiso('reportes'))
            <a href="{{ route('admin.reportes.bincard') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Ver reporte →</a>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bincard-total">
                <p class="bc-label">Valor Total Inventario</p>
                <p class="bc-value">{{ $moneda($valorInventario) }}</p>
                <p class="bc-sub">costo promedio × stock</p>
            </div>
            <div class="bincard-entradas">
                <p class="bc-label">Entradas Valorizadas Mes</p>
                <p class="bc-value">{{ $moneda($entradasMesValor) }}</p>
                <p class="bc-sub">{{ now()->isoFormat('MMM YYYY') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-3">
                <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $num($movMes->entradas) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Entradas mes</p>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-3">
                <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $num($movMes->salidas) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Salidas mes</p>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-3">
                <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ $moneda($costoPromedioGlobal) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Costo prom.</p>
            </div>
        </div>
    </div>

    {{-- Panel Logístico SICD/OC --}}
    <div class="dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="dash-section-title mb-0">Panel Logístico</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">SICD &amp; Órdenes de Compra</p>
            </div>
            <div class="flex gap-3 text-xs">
                <span class="font-semibold text-amber-500">{{ $num($sicdStats->pendientes) }} pend.</span>
                <span class="font-semibold text-blue-500">{{ $num($sicdStats->agrupadas) }} agrup.</span>
                <span class="font-semibold text-green-600 dark:text-green-400">{{ $num($sicdStats->recibidas) }} recib.</span>
            </div>
        </div>

        <div class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
            @foreach($sicdRecientes as $sicd)
            @php
                $estSicd = match($sicd->estado) {
                    'recibido' => ['txt' => 'Recibido',  'cls' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                    'agrupado' => ['txt' => 'Agrupado',  'cls' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
                    default    => ['txt' => 'Pendiente', 'cls' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                };
            @endphp
            <div class="alert-row rounded-lg px-3 py-2 flex items-center gap-3 cursor-pointer" onclick="window.location='{{ route('admin.sicd.show', $sicd->id) }}'">
                <span class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 w-28 shrink-0">{{ $sicd->codigo_sicd }}</span>
                <span class="estado-pill {{ $estSicd['cls'] }}">{{ $estSicd['txt'] }}</span>
                <span class="text-xs text-gray-400 ml-auto whitespace-nowrap">{{ $sicd->created_at->format('d/m/Y') }}</span>
                @if($sicd->ordenesCompra->isNotEmpty())
                    <span class="text-xs text-gray-400">· {{ $sicd->ordenesCompra->count() }} OC</span>
                @endif
            </div>
            @endforeach
        </div>

        @if($user->tienePermiso('sicd'))
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700">
            <a href="{{ route('admin.sicd.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Ver todos los SICD →</a>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     ROW 4: ACTIVIDAD RECIENTE + GRÁFICO COMPRAS
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

    {{-- Actividad Reciente --}}
    <div class="lg:col-span-2 dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="dash-section-title mb-0">Actividad Reciente</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Últimos movimientos del sistema</p>
            </div>
            @if($user->tienePermiso('historial'))
            <a href="{{ route('admin.historial') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Ver historial →</a>
            @endif
        </div>

        <div class="space-y-1.5 max-h-72 overflow-y-auto pr-1">
            @forelse($actividadReciente as $mov)
            @php
                $modulo = match($mov->origen) {
                    'gasto_menor'   => 'Gasto Menor',
                    'orden_compra'  => 'OC',
                    'sicd'          => 'SICD',
                    'solicitud'     => 'Solicitud',
                    'retiro'        => 'Retiro',
                    default         => 'Manual',
                };
            @endphp
            <div class="flex items-center gap-3 px-3 py-2 rounded-lg alert-row">
                <span class="tipo-badge-{{ $mov->tipo }} text-[10px] font-bold px-1.5 py-0.5 rounded uppercase w-14 text-center shrink-0">
                    {{ $mov->tipo === 'entrada' ? '↑ Ent.' : '↓ Sal.' }}
                </span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 flex-1 truncate">{{ $mov->nombre_producto }}</span>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 shrink-0">{{ $num(abs($mov->cantidad)) }} u.</span>
                <span class="hidden sm:inline text-xs text-indigo-500 dark:text-indigo-400 w-20 shrink-0">{{ $modulo }}</span>
                <span class="hidden sm:inline text-xs text-gray-400 w-20 shrink-0">{{ $mov->usuario?->name ?? '—' }}</span>
                <span class="text-xs text-gray-400 whitespace-nowrap shrink-0">{{ $mov->created_at->format('d/m H:i') }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-8">Sin actividad registrada</p>
            @endforelse
        </div>
    </div>

    {{-- Gráfico Compras por Fuente --}}
    <div class="dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5 flex flex-col">
        <p class="dash-section-title mb-1">Compras por Tipo</p>
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Distribución de ingresos</p>
        <div class="flex-1 flex items-center justify-center" style="min-height:180px;">
            <canvas id="chartCompras" style="max-height:200px;"></canvas>
        </div>
        <div class="mt-3 space-y-1.5 text-xs">
            @foreach($graficoCom['labels'] as $i => $label)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-sm shrink-0" id="compra-dot-{{ $i }}"></span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                </div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $moneda($graficoCom['data'][$i] ?? 0) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     ROW 5: EQUIPOS ARMADOS + REPORTERÍAS
══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    {{-- Últimos Equipos Armados --}}
    @if($user->tienePermiso('computadores'))
    <div class="dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="dash-section-title mb-0">Equipos Armados</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Últimos registros</p>
            </div>
            <a href="{{ route('admin.computadores.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Ver todos →</a>
        </div>

        <div class="space-y-2">
            @forelse($ultimosEquipos as $eq)
            @php
                $estEq = match($eq->estado) {
                    'listo'     => ['txt' => 'Listo',      'cls' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                    'en_uso'    => ['txt' => 'En uso',     'cls' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
                    'en_armado' => ['txt' => 'En armado',  'cls' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                    'desarmado' => ['txt' => 'Desarmado',  'cls' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'],
                    default     => ['txt' => $eq->estado,  'cls' => 'bg-gray-100 text-gray-500'],
                };
                $nComp = $eq->componentesActivos->count();
            @endphp
            <div class="alert-row rounded-lg px-3 py-2.5 flex items-center gap-3 cursor-pointer" onclick="window.location='{{ route('admin.computadores.show', $eq->id) }}'">
                <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ $eq->codigo }} — {{ $eq->nombre }}</p>
                    <p class="text-[11px] text-gray-400">{{ $nComp }} componentes · {{ $eq->created_at->format('d/m/Y') }}</p>
                </div>
                <span class="estado-pill {{ $estEq['cls'] }}">{{ $estEq['txt'] }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-6">Sin equipos registrados</p>
            @endforelse
        </div>

        {{-- Resumen contadores --}}
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700 grid grid-cols-3 text-center text-xs gap-2">
            <div><p class="font-bold text-green-600 dark:text-green-400">{{ $num($equiposStats->completos) }}</p><p class="text-gray-400">Listos / En uso</p></div>
            <div><p class="font-bold text-amber-500">{{ $num($equiposStats->en_armado) }}</p><p class="text-gray-400">En armado</p></div>
            <div><p class="font-bold text-gray-400">{{ $num($equiposStats->desarmados) }}</p><p class="text-gray-400">Desarmados</p></div>
        </div>
    </div>
    @endif

    {{-- Últimas Reporterías --}}
    @if($user->tienePermiso('reportes'))
    <div class="dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="dash-section-title mb-0">Reportería</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Últimas exportaciones</p>
            </div>
            <a href="{{ route('admin.reportes.historial') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Ver historial →</a>
        </div>

        <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
            @forelse($ultimasReporterias as $rep)
            @php
                $fmtIcon = match(strtolower($rep->formato ?? '')) {
                    'pdf'   => ['color' => '#ef4444', 'label' => 'PDF'],
                    'excel', 'xlsx' => ['color' => '#16a34a', 'label' => 'XLS'],
                    'csv'   => ['color' => '#0891b2', 'label' => 'CSV'],
                    default => ['color' => '#6b7280', 'label' => strtoupper($rep->formato ?? '?')],
                };
            @endphp
            <div class="alert-row rounded-lg px-3 py-2.5 flex items-center gap-3">
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded text-white shrink-0" style="background:{{ $fmtIcon['color'] }}">{{ $fmtIcon['label'] }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ $rep->nombre ?? $rep->modulo }}</p>
                    <p class="text-[11px] text-gray-400">{{ $rep->usuario_nombre ?? ($rep->usuario?->name ?? '—') }}</p>
                </div>
                <span class="text-xs text-gray-400 whitespace-nowrap shrink-0">{{ $rep->created_at->format('d/m H:i') }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-6">Sin reportes generados</p>
            @endforelse
        </div>

        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700 flex gap-3">
            <a href="{{ route('admin.reportes.bincard') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Generar Bincard</a>
            <a href="{{ route('admin.reportes.index') }}" class="text-xs text-gray-500 dark:text-gray-400 hover:underline">Ver reportes</a>
        </div>
    </div>
    @endif

</div>

{{-- ══════════════════════════════════════════════════════════
     ROW 6: PRODUCTOS MÁS MOVIDOS
══════════════════════════════════════════════════════════ --}}
@if(!empty($graficoProductos['labels']))
<div class="dash-card bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 p-5 mb-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <p class="dash-section-title mb-0">Productos Más Utilizados</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Salidas últimos 90 días</p>
        </div>
    </div>
    <div style="height:220px">
        <canvas id="chartProductos"></canvas>
    </div>
</div>
@endif

@push('scripts')
<script>
(function () {
    'use strict';

    const MOVIMIENTO = @json($graficoMovimiento);
    const COMPRAS    = @json($graficoCom);
    const PRODUCTOS  = @json($graficoProductos);

    // Paleta colores gráficos por fuente
    const COM_COLORS = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];

    function isDark() {
        return document.documentElement.classList.contains('dark');
    }

    function themeColors() {
        const dark = isDark();
        return {
            grid:    dark ? 'rgba(148,163,184,0.08)' : 'rgba(107,114,128,0.10)',
            text:    dark ? '#94a3b8' : '#6b7280',
            tooltip: dark ? '#1e293b' : '#ffffff',
        };
    }

    // ── Chart.js global defaults ──────────────────────────────────────────
    function applyTheme() {
        const t = themeColors();
        Chart.defaults.color = t.text;
        Chart.defaults.borderColor = t.grid;
    }
    applyTheme();

    // ── Gráfico Movimiento ─────────────────────────────────────────────────
    const ctxMov = document.getElementById('chartMovimiento');
    let chartMov = null;
    if (ctxMov) {
        function buildMovData() {
            const t = themeColors();
            return {
                type: 'bar',
                data: {
                    labels: MOVIMIENTO.labels30d,
                    datasets: [
                        {
                            label: 'Entradas',
                            data: MOVIMIENTO.entradas30d,
                            backgroundColor: 'rgba(99,102,241,0.75)',
                            borderRadius: 3,
                            borderSkipped: false,
                        },
                        {
                            label: 'Salidas',
                            data: MOVIMIENTO.salidas30d,
                            backgroundColor: 'rgba(239,68,68,0.65)',
                            borderRadius: 3,
                            borderSkipped: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: t.tooltip,
                            titleColor: isDark() ? '#e2e8f0' : '#111827',
                            bodyColor: isDark() ? '#94a3b8' : '#6b7280',
                            borderColor: isDark() ? 'rgba(148,163,184,0.2)' : 'rgba(0,0,0,0.08)',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 8,
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: t.text, font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
                        },
                        y: {
                            grid: { color: t.grid },
                            ticks: { color: t.text, font: { size: 10 } },
                            beginAtZero: true,
                        },
                    },
                },
            };
        }
        chartMov = new Chart(ctxMov, buildMovData());
    }

    // ── Gráfico Compras (Doughnut) ─────────────────────────────────────────
    const ctxCom = document.getElementById('chartCompras');
    let chartCom = null;
    if (ctxCom && COMPRAS.labels.length > 0) {
        const dots = document.querySelectorAll('[id^="compra-dot-"]');
        function setDotColors() {
            dots.forEach((el, i) => { el.style.background = COM_COLORS[i % COM_COLORS.length]; });
        }
        setDotColors();

        function buildComData() {
            const t = themeColors();
            return {
                type: 'doughnut',
                data: {
                    labels: COMPRAS.labels,
                    datasets: [{
                        data: COMPRAS.data,
                        backgroundColor: COM_COLORS.slice(0, COMPRAS.labels.length),
                        borderWidth: 2,
                        borderColor: isDark() ? '#1e293b' : '#ffffff',
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: t.tooltip,
                            titleColor: isDark() ? '#e2e8f0' : '#111827',
                            bodyColor: isDark() ? '#94a3b8' : '#6b7280',
                            borderColor: isDark() ? 'rgba(148,163,184,0.2)' : 'rgba(0,0,0,0.08)',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(ctx) {
                                    const fmt = (v) => '$' + v.toLocaleString('es-CL');
                                    return ' ' + fmt(ctx.raw);
                                }
                            }
                        },
                    },
                },
            };
        }
        chartCom = new Chart(ctxCom, buildComData());
    }

    // ── Gráfico Productos (Horizontal Bar) ────────────────────────────────
    const ctxProd = document.getElementById('chartProductos');
    let chartProd = null;
    if (ctxProd && PRODUCTOS.labels && PRODUCTOS.labels.length > 0) {
        function buildProdData() {
            const t = themeColors();
            return {
                type: 'bar',
                data: {
                    labels: PRODUCTOS.labels,
                    datasets: [{
                        label: 'Unidades retiradas',
                        data: PRODUCTOS.data,
                        backgroundColor: 'rgba(99,102,241,0.7)',
                        borderRadius: 4,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: t.tooltip,
                            titleColor: isDark() ? '#e2e8f0' : '#111827',
                            bodyColor: isDark() ? '#94a3b8' : '#6b7280',
                            borderColor: isDark() ? 'rgba(148,163,184,0.2)' : 'rgba(0,0,0,0.08)',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 8,
                        },
                    },
                    scales: {
                        x: {
                            grid: { color: t.grid },
                            ticks: { color: t.text, font: { size: 11 } },
                            beginAtZero: true,
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: t.text, font: { size: 11 } },
                        },
                    },
                },
            };
        }
        chartProd = new Chart(ctxProd, buildProdData());
    }

    // ── Actualizar charts al cambiar dark mode ─────────────────────────────
    new MutationObserver(function () {
        applyTheme();
        function updateChart(chart, buildFn) {
            if (!chart) return;
            const cfg = buildFn();
            chart.data = cfg.data;
            chart.options = cfg.options;
            chart.update('none');
        }
        if (chartMov) updateChart(chartMov, buildMovData);
        if (chartCom) updateChart(chartCom, buildComData);
        if (chartProd) updateChart(chartProd, buildProdData);
    }).observe(document.documentElement, { attributeFilter: ['class'] });

})();
</script>
@endpush

@endsection
