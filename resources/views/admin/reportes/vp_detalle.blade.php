@extends('layouts.app')
@section('title', 'Variación Presupuestaria — Detalle')

@push('head')
<style>
/* ── KPI cards ───────────────────────────────────────────────── */
.vp-kpi { border-radius:.75rem; padding:1.1rem 1.25rem; border:1px solid; }
.vp-kpi-sicd  { background:#f0fdf4; border-color:#bbf7d0; }
.vp-kpi-oc    { background:#eff6ff; border-color:#bfdbfe; }
.vp-kpi-var   { background:#f8fafc; border-color:#e2e8f0; }
.vp-kpi label { font-size:.65rem; font-weight:700; text-transform:uppercase;
                letter-spacing:.05em; display:block; margin-bottom:.2rem; }
.vp-kpi .val  { font-size:1.45rem; font-weight:800; line-height:1; }
.vp-kpi .sub  { font-size:.68rem; margin-top:.3rem; }

html.dark .vp-kpi-sicd { background:rgba(6,95,70,.18);   border-color:rgba(52,211,153,.25); }
html.dark .vp-kpi-sicd label, html.dark .vp-kpi-sicd .val, html.dark .vp-kpi-sicd .sub { color:#34d399; }
html.dark .vp-kpi-oc   { background:rgba(29,78,216,.18); border-color:rgba(147,197,253,.25); }
html.dark .vp-kpi-oc   label, html.dark .vp-kpi-oc   .val, html.dark .vp-kpi-oc   .sub { color:#93c5fd; }

/* ── Tabla ───────────────────────────────────────────────────── */
.vp-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.vp-table thead th {
    background:#1e3a5f; color:#fff;
    padding:.55rem .75rem; text-align:left;
    font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em;
    white-space:nowrap;
}
.vp-table thead th.num { text-align:right; }
.vp-table tbody td { padding:.5rem .75rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.vp-table tbody tr:nth-child(even) td { background:#f8fafc; }
.vp-table tbody td.num { text-align:right; font-weight:600; }

html.dark .vp-table thead th { background:#1e1b4b; }
html.dark .vp-table tbody td { border-color:#1e293b; color:#e2e8f0; }
html.dark .vp-table tbody tr:nth-child(even) td { background:#162032; }

/* ── Badges estado SICD ──────────────────────────────────────── */
.badge-pendiente { background:#fef3c7; color:#92400e; }
.badge-agrupado  { background:#dbeafe; color:#1e40af; }
.badge-recibido  { background:#d1fae5; color:#065f46; }
html.dark .badge-pendiente { background:rgba(146,64,14,.25);  color:#fcd34d; }
html.dark .badge-agrupado  { background:rgba(29,78,216,.22);  color:#93c5fd; }
html.dark .badge-recibido  { background:rgba(6,95,70,.25);    color:#6ee7b7; }

/* ── Badge variación ─────────────────────────────────────────── */
.badge-sobre { background:#fef2f2; color:#dc2626; }
.badge-bajo  { background:#f0fdf4; color:#16a34a; }
.badge-igual { background:#f8fafc; color:#64748b; }
html.dark .badge-sobre { background:rgba(220,38,38,.18);  color:#f87171; }
html.dark .badge-bajo  { background:rgba(6,95,70,.18);    color:#34d399; }
html.dark .badge-igual { background:rgba(51,65,85,.3);    color:#94a3b8; }

/* ── Meta bar ────────────────────────────────────────────────── */
.meta-bar { background:#f8fafc; border:1px solid #e2e8f0; border-radius:.5rem; padding:.6rem 1rem; font-size:.78rem; color:#64748b; }
html.dark .meta-bar { background:#0f172a; border-color:#334155; color:#94a3b8; }
</style>
@endpush

@section('content')

{{-- ── HEADER ──────────────────────────────────────────────────────── --}}
<div class="mb-5 flex items-start justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('admin.reportes.historial') }}"
               class="text-sm text-indigo-500 hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Historial Reporterías
            </a>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Variación Presupuestaria</h1>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">
            Período:
            <span class="font-semibold text-gray-700 dark:text-slate-300">
                {{ \Carbon\Carbon::parse($r->filtros['fecha_desde'])->format('d/m/Y') }}
                →
                {{ \Carbon\Carbon::parse($r->filtros['fecha_hasta'])->format('d/m/Y') }}
            </span>
        </p>
    </div>

    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold"
          style="background:rgba(99,102,241,.12);color:#6366f1;border:1px solid rgba(99,102,241,.2);">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        Auditoría Financiera
    </span>
</div>

{{-- ── META ─────────────────────────────────────────────────────────── --}}
<div class="meta-bar mb-5 flex flex-wrap gap-x-6 gap-y-1">
    <span><strong>Generado por:</strong> {{ $r->usuario_nombre ?? '—' }}</span>
    <span><strong>Fecha:</strong> {{ $r->created_at->format('d/m/Y H:i') }}</span>
    <span><strong>Registro N°:</strong> #{{ $r->id }}</span>
    @if(!empty($r->filtros['estado_sicd']))
        <span><strong>Filtro estado SICD:</strong> {{ ucfirst($r->filtros['estado_sicd']) }}</span>
    @endif
    @if(!empty($r->filtros['proveedor']))
        <span><strong>Filtro proveedor:</strong> {{ $r->filtros['proveedor'] }}</span>
    @endif
    <span class="ml-auto text-xs italic opacity-70">Snapshot guardado — datos al momento de la consulta</span>
</div>

{{-- ── KPIs ─────────────────────────────────────────────────────────── --}}
@php
    $totalSicd = (float)($r->filtros['total_sicd'] ?? 0);
    $totalOc   = (float)($r->filtros['total_oc']   ?? 0);
    $variacion = (float)($r->filtros['variacion']  ?? 0);
    $estVar    = $r->filtros['estado_variacion'] ?? 'igual';
    $nSicds    = $r->filtros['n_sicds'] ?? 0;
    $nOcs      = $r->filtros['n_ocs']   ?? 0;

    $varBadgeClass = match($estVar) {
        'sobre' => 'vp-kpi-var badge-sobre',
        'bajo'  => 'vp-kpi-var badge-bajo',
        default => 'vp-kpi-var badge-igual',
    };
    $varLabel = match($estVar) {
        'sobre' => 'Sobre presupuesto',
        'bajo'  => 'Bajo presupuesto',
        default => 'Sin variación',
    };
    $varColor = match($estVar) {
        'sobre' => '#dc2626',
        'bajo'  => '#16a34a',
        default => '#64748b',
    };
    $varStr = $variacion > 0
        ? '+$' . number_format($variacion, 0, ',', '.')
        : ($variacion < 0 ? '-$' . number_format(abs($variacion), 0, ',', '.') : '$0');
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <div class="vp-kpi vp-kpi-sicd">
        <label style="color:#15803d;">Total SICD (con IVA)</label>
        <div class="val" style="color:#15803d;">${{ number_format($totalSicd, 0, ',', '.') }}</div>
        <div class="sub" style="color:#15803d;">{{ $nSicds }} SICD</div>
    </div>
    <div class="vp-kpi vp-kpi-oc">
        <label style="color:#1d4ed8;">Total OC Consolidado</label>
        <div class="val" style="color:#1d4ed8;">${{ number_format($totalOc, 0, ',', '.') }}</div>
        <div class="sub" style="color:#1d4ed8;">{{ $nOcs }} OC</div>
    </div>
    <div class="vp-kpi {{ $varBadgeClass }}" style="border-color:{{ $estVar === 'sobre' ? '#fecaca' : ($estVar === 'bajo' ? '#bbf7d0' : '#e2e8f0') }};">
        <label style="color:{{ $varColor }};">Variación (OC − SICD)</label>
        <div class="val" style="color:{{ $varColor }};">{{ $varStr }}</div>
        <div class="sub" style="color:{{ $varColor }};">{{ $varLabel }}</div>
    </div>
</div>

{{-- ── TABLA DETALLE ────────────────────────────────────────────────── --}}
@php $filas = $vp?->detalle ?? []; @endphp

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-x-auto">

    @if(empty($filas))
        <div class="px-5 py-10 text-center text-gray-400 dark:text-slate-500">
            <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm">Sin detalle de SICDs disponible para este registro.</p>
        </div>
    @else
        <table class="vp-table">
            <thead>
                <tr>
                    <th>Código SICD</th>
                    <th>Proveedor</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th class="num">Total SICD</th>
                    <th class="num">Total OC</th>
                    <th class="num" title="N° Órdenes de Compra">OCs</th>
                    <th class="num">Variación</th>
                    <th>Estado Var.</th>
                    <th>OC(s)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $fila)
                @php
                    $v2      = (float)($fila['variacion'] ?? 0);
                    $estFila = $v2 > 0 ? 'sobre' : ($v2 < 0 ? 'bajo' : 'igual');
                    $varFilaStr = $v2 > 0
                        ? '+$' . number_format($v2, 0, ',', '.')
                        : ($v2 < 0 ? '-$' . number_format(abs($v2), 0, ',', '.') : '$0');
                    $estadoSicdCls = 'badge-' . ($fila['estado'] ?? 'igual');
                    $ocsStr = collect($fila['ocs'] ?? [])->map(fn($o) => $o['numero'] ? 'OC-'.$o['numero'] : '—')->join(', ') ?: '—';
                @endphp
                <tr>
                    <td class="font-mono font-bold" style="color:#4f46e5;">
                        {{ $fila['codigo'] ?? ('SICD-' . str_pad($fila['sicd_id'], 6, '0', STR_PAD_LEFT)) }}
                    </td>
                    <td class="text-gray-700 dark:text-slate-300" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $fila['proveedor'] ?? '' }}">
                        {{ $fila['proveedor'] ?? '—' }}
                    </td>
                    <td>
                        <span class="inline-block text-xs font-bold px-2.5 py-0.5 rounded-full {{ $estadoSicdCls }}">
                            {{ ucfirst($fila['estado'] ?? '—') }}
                        </span>
                    </td>
                    <td class="text-gray-500 dark:text-slate-400 whitespace-nowrap text-sm">{{ $fila['fecha'] ?? '—' }}</td>
                    <td class="num" style="color:#15803d;">${{ number_format($fila['sicd_total'] ?? 0, 0, ',', '.') }}</td>
                    <td class="num" style="color:#1d4ed8;">${{ number_format($fila['oc_total'] ?? 0, 0, ',', '.') }}</td>
                    <td class="num text-gray-500 dark:text-slate-400">{{ $fila['n_ocs'] ?? 0 }}</td>
                    <td class="num font-bold" style="color:{{ $estFila === 'sobre' ? '#dc2626' : ($estFila === 'bajo' ? '#16a34a' : '#64748b') }};">
                        {{ $varFilaStr }}
                    </td>
                    <td>
                        <span class="inline-block text-xs font-bold px-2.5 py-0.5 rounded-full badge-{{ $estFila }}">
                            {{ $estFila === 'sobre' ? 'Sobre ppto.' : ($estFila === 'bajo' ? 'Bajo ppto.' : 'Sin variación') }}
                        </span>
                    </td>
                    <td class="text-xs text-gray-500 dark:text-slate-400" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $ocsStr }}">
                        {{ $ocsStr }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#1e3a5f;">
                    <td colspan="4" class="font-bold text-right text-white text-sm" style="padding:.5rem .75rem;">TOTALES</td>
                    <td class="num font-bold text-sm" style="padding:.5rem .75rem;color:#86efac;">${{ number_format($totalSicd, 0, ',', '.') }}</td>
                    <td class="num font-bold text-sm" style="padding:.5rem .75rem;color:#93c5fd;">${{ number_format($totalOc, 0, ',', '.') }}</td>
                    <td class="num font-bold text-sm" style="padding:.5rem .75rem;color:#cbd5e1;">{{ $nOcs }}</td>
                    <td class="num font-bold text-sm" style="padding:.5rem .75rem;color:{{ $estVar === 'sobre' ? '#fca5a5' : ($estVar === 'bajo' ? '#86efac' : '#94a3b8') }};">{{ $varStr }}</td>
                    <td colspan="2" style="padding:.5rem .75rem;"></td>
                </tr>
            </tfoot>
        </table>
    @endif
</div>

{{-- ── FOOTER NOTE ──────────────────────────────────────────────────── --}}
<div class="mt-4 flex items-center justify-between text-xs text-gray-400 dark:text-slate-500">
    <span>
        <svg class="w-3.5 h-3.5 inline mr-1 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Este informe es un snapshot guardado automáticamente. Los datos reflejan el estado del sistema al momento de la consulta.
    </span>
    <a href="{{ route('admin.reportes.historial') }}" class="text-indigo-500 hover:underline">← Volver al historial</a>
</div>

@endsection
