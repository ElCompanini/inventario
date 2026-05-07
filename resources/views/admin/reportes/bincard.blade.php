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

/* Dark mode — BINCARD */
html.dark .bincard-th      { background:#1e1b4b; border-color:#4f46e5; }
html.dark .bincard-td      { border-color:#334155; color:#e2e8f0; }
html.dark .bincard-entrada { background:#052e16; }
html.dark .bincard-salida  { background:#2d0a0a; }
html.dark .bincard-neutral { background:#1e293b; }
html.dark .bincard-alt     { background:#162032; }
html.dark .bincard-td > *  { color:inherit; }
</style>
@endpush

@section('content')

<div class="mb-5">
    <h1 class="text-2xl font-bold text-gray-800">Reportes</h1>
    <p class="text-sm text-gray-500 mt-1">Reportería BINCARD — Trazabilidad y control de inventario</p>
</div>

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
                @foreach(['entrada','salida','ajuste','merma','transferencia','retiro','devolucion'] as $t)
                    <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
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
                <h2 style="font-size:1.25rem; font-weight:800; margin:.1rem 0 0;">TARJETA BINCARD — CONTROL DE EXISTENCIAS</h2>
            </div>
            <div style="text-align:right; font-size:0.72rem; opacity:.8;">
                <p style="margin:0;">Emitido: {{ $data['generado_at'] }}</p>
                <p style="margin:.1rem 0 0;">Por: <strong>{{ $data['generado_por'] }}</strong></p>
            </div>
        </div>
    </div>

    {{-- Info producto --}}
    <div style="padding:1rem 1.5rem; background:#f8fafc; border-bottom:2px solid #e2e8f0;">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
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

    {{-- KPIs de stock --}}
    <div style="padding:.75rem 1.5rem; background:#fff; border-bottom:1px solid #e2e8f0;">
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
                ['Costo Promedio', $data['costo_promedio'] ? '$' . number_format($data['costo_promedio'], 0, ',', '.') : '—'],
                ['Último Costo', $data['ultimo_costo'] ? '$' . number_format($data['ultimo_costo'], 0, ',', '.') : '—'],
                ['Valor Inventario', $data['valor_inventario'] ? '$' . number_format($data['valor_inventario'], 0, ',', '.') : '—'],
            ] as [$label, $val])
            <div style="text-align:center; padding:.5rem; background:#eff6ff; border-radius:.5rem; border:1px solid #bfdbfe;">
                <p style="font-size:.65rem; font-weight:600; color:#1e40af; text-transform:uppercase; letter-spacing:.04em; margin:0;">{{ $label }}</p>
                <p style="font-size:1.1rem; font-weight:800; color:#1d4ed8; margin:.1rem 0 0;">{{ $val }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ══ TABLA PRINCIPAL BINCARD ════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">Historial de Movimientos</h3>
        <span class="text-xs text-gray-400">{{ count($data['filas']) }} movimiento(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table style="width:100%; border-collapse:collapse; font-size:.75rem;">
            <thead>
                <tr>
                    <th class="bincard-th" style="text-align:left;">Fecha</th>
                    <th class="bincard-th">Tipo Mov.</th>
                    <th class="bincard-th">Tipo Doc.</th>
                    <th class="bincard-th" style="text-align:left;">N° Documento</th>
                    <th class="bincard-th">RUT Proveedor</th>
                    <th class="bincard-th" style="text-align:left;">Proveedor</th>
                    <th class="bincard-th" style="color:#86efac;">Entrada</th>
                    <th class="bincard-th" style="color:#fca5a5;">Salida</th>
                    <th class="bincard-th" style="color:#a5b4fc;">Saldo</th>
                    @if(auth()->user()->esAdmin())
                    <th class="bincard-th">Costo Unit.</th>
                    <th class="bincard-th">Valor Mov.</th>
                    <th class="bincard-th">Costo Prom.</th>
                    <th class="bincard-th">Valor Saldo</th>
                    @endif
                    <th class="bincard-th" style="text-align:left;">Usuario</th>
                    <th class="bincard-th" style="text-align:left; min-width:180px;">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['filas'] as $idx => $fila)
                @php
                    $esE = $fila['entrada'] !== null;
                    $esS = $fila['salida']  !== null;
                    $base = $idx % 2 === 0 ? '' : 'bincard-alt';
                    $cls  = $esE ? 'bincard-entrada' : ($esS ? 'bincard-salida' : $base);
                @endphp
                <tr class="{{ $cls }}">
                    <td class="bincard-td whitespace-nowrap">{{ $fila['fecha'] }}</td>
                    <td class="bincard-td text-center">
                        <span style="font-size:.68rem; font-weight:700; padding:2px 7px; border-radius:9999px; white-space:nowrap;
                            background:{{ $esE ? '#dcfce7' : ($esS ? '#fee2e2' : '#f3f4f6') }};
                            color:{{ $esE ? '#15803d' : ($esS ? '#dc2626' : '#6b7280') }};">
                            {{ $fila['tipo_movimiento'] }}
                        </span>
                    </td>
                    <td class="bincard-td text-center text-gray-600">{{ $fila['tipo_documento'] }}</td>
                    <td class="bincard-td font-mono" style="color:#4f46e5; font-size:.72rem;">{{ $fila['n_documento'] }}</td>
                    <td class="bincard-td text-center text-gray-600 whitespace-nowrap">{{ $fila['rut_proveedor'] }}</td>
                    <td class="bincard-td text-gray-700" style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $fila['proveedor'] }}</td>
                    <td class="bincard-td text-center font-bold" style="color:#16a34a;">
                        {{ $fila['entrada'] !== null ? $fila['entrada'] : '' }}
                    </td>
                    <td class="bincard-td text-center font-bold" style="color:#dc2626;">
                        {{ $fila['salida'] !== null ? $fila['salida'] : '' }}
                    </td>
                    <td class="bincard-td text-center font-bold" style="color:#4f46e5;">{{ $fila['saldo'] }}</td>
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
                    <td class="bincard-td text-gray-500" style="font-size:.72rem; max-width:200px;">{{ $fila['observaciones'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="15" style="text-align:center; padding:2rem; color:#9ca3af; font-size:.82rem;">
                        No hay movimientos para los filtros seleccionados.
                    </td>
                </tr>
                @endforelse

                {{-- Fila de totales --}}
                @if(count($data['filas']) > 0)
                <tr style="background:#1e3a5f; color:#fff; font-weight:700;">
                    <td class="bincard-td" colspan="6" style="color:#fff; border-color:#2563eb; text-align:right; font-size:.8rem;">
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
                    <td class="bincard-td" colspan="2" style="border-color:#2563eb;"></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
