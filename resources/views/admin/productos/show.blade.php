@extends('layouts.app')
@section('title', $producto->nombre)

@push('head')
<style>
.tab-btn { transition: all .15s; border-bottom: 2px solid transparent; }
.tab-btn.active { border-bottom-color: #4f46e5; color: #4f46e5; font-weight: 700; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }
.stat-card { background:#fff; border-radius:.75rem; box-shadow:0 1px 4px rgba(0,0,0,.07); padding:1.25rem 1.5rem; }
.prod-avatar { background:#ede9fe; }
html.dark .prod-avatar { background:rgba(109,40,217,.2); }
html.dark .prod-avatar svg { color:#a78bfa !important; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
    <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition">Productos</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $producto->nombre }}</span>
</div>

{{-- Header del producto --}}
<div class="bg-white rounded-xl shadow overflow-hidden mb-6">
    <div class="px-6 py-5 flex items-start justify-between gap-4">
        <div class="flex items-start gap-4">
            {{-- Avatar --}}
            <div class="prod-avatar" style="width:3.5rem;height:3.5rem;border-radius:.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg style="width:1.75rem;height:1.75rem;color:#7c3aed;" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $producto->nombre }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-1">
                    @if($producto->categoria)
                        <span class="text-xs text-gray-500">{{ $producto->categoria->familia?->nombre }} › {{ $producto->categoria->nombre }}</span>
                    @endif
                    @if($producto->unidadMedida || $producto->unidad)
                        <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-mono">
                            {{ $producto->unidadMedida?->abreviacion ?? $producto->unidad }}
                        </span>
                    @endif
                    <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full
                        {{ $producto->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                        {{ $producto->activo ? '● Activo' : '● Inactivo' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.productos.editar', $producto->id) }}"
               style="padding:.4rem .9rem;font-size:.8rem;font-weight:600;color:#fff;background:#4f46e5;border-radius:.5rem;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;transition:background .15s;"
               onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Modificar stock
            </a>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-t border-gray-100 px-6 flex items-center gap-1 overflow-x-auto">
        @php $tabs = ['general' => 'General', 'stock' => 'Stock', 'movimientos' => 'Movimientos', 'documentos' => 'Documentos']; @endphp
        @if(auth()->user()->esAdmin())
            @php $tabs['costos'] = 'Costos'; @endphp
        @endif
        @foreach($tabs as $key => $label)
        <button onclick="switchTab('{{ $key }}')" id="tab-btn-{{ $key }}"
                class="tab-btn px-4 py-3 text-sm text-gray-500 whitespace-nowrap {{ $loop->first ? 'active' : '' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>
</div>

{{-- ═══════════════════ TAB: GENERAL ═══════════════════ --}}
<div id="tab-general" class="tab-panel active">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Información principal --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 border-b border-gray-100 pb-2">Información del Producto</h2>
            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">ID Interno</p>
                    <p class="text-gray-800 font-mono font-semibold mt-0.5">#{{ $producto->id }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Nombre</p>
                    <p class="text-gray-800 font-medium mt-0.5">{{ $producto->nombre }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Familia</p>
                    <p class="text-gray-700 mt-0.5">{{ $producto->categoria?->familia?->nombre ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Categoría</p>
                    <p class="text-gray-700 mt-0.5">{{ $producto->categoria?->nombre ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Unidad de Medida</p>
                    <p class="text-gray-700 mt-0.5">{{ $producto->unidadMedida?->abreviacion ?? $producto->unidad ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Centro de Costo</p>
                    <p class="text-gray-700 mt-0.5">{{ $producto->centroCosto?->nombre_completo ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Código de Barras</p>
                    <p class="text-gray-700 font-mono mt-0.5">{{ $producto->codigo_barras ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Estado</p>
                    <p class="mt-0.5">
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full
                            {{ $producto->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Ubicación + Stock rápido --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Ubicación Física</h3>
                    <button onclick="document.getElementById('form-traslado-prod').classList.toggle('hidden')"
                            style="font-size:.72rem;font-weight:600;color:#4f46e5;background:none;border:none;cursor:pointer;">
                        Cambiar →
                    </button>
                </div>
                <div class="px-5 py-4 space-y-2 text-sm">
                    <div>
                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Container actual</p>
                        <p class="text-gray-800 font-semibold mt-0.5">
                            {{ $producto->container?->nombre ?? '—' }}
                            @if($producto->container?->centroCosto)
                                <span class="text-xs text-gray-400 font-normal ml-1">· {{ $producto->container->centroCosto->acronimo }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Formulario de traslado --}}
                <form id="form-traslado-prod" method="POST"
                      action="{{ route('admin.productos.trasladar', $producto->id) }}"
                      class="hidden px-5 pb-4 space-y-3 border-t border-gray-100 pt-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Nuevo container <span class="text-red-500">*</span>
                        </label>
                        <select name="contenedor_destino" required
                                style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.8rem;background:#fff;">
                            <option value="">— Selecciona container —</option>
                            @foreach($containers as $c)
                                @if($c->id !== $producto->contenedor)
                                <option value="{{ $c->id }}">
                                    {{ $c->nombre }}{{ $c->centroCosto ? ' · ' . $c->centroCosto->acronimo : '' }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Motivo del traslado <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="motivo" required placeholder="Ej: Reorganización de bodega"
                               style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.8rem;box-sizing:border-box;">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                                style="flex:1;padding:.4rem;font-size:.8rem;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:.5rem;cursor:pointer;"
                                onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                            Confirmar traslado
                        </button>
                        <button type="button"
                                onclick="document.getElementById('form-traslado-prod').classList.add('hidden')"
                                style="padding:.4rem .75rem;font-size:.8rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:none;border-radius:.5rem;cursor:pointer;">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Stock</h3>
                @php $estado = $producto->estadoStock(); @endphp
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Actual</span>
                        <span class="text-lg font-bold
                            {{ $estado === 'critico' ? 'text-red-600' : ($estado === 'minimo' ? 'text-amber-600' : 'text-gray-900') }}">
                            {{ $producto->stock_actual }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span>Mínimo</span><span class="font-medium text-gray-600">{{ $producto->stock_minimo ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span>Crítico</span><span class="font-medium text-gray-600">{{ $producto->stock_critico ?? '—' }}</span>
                    </div>
                    @if($estado !== 'normal')
                    <div class="mt-2 rounded-lg px-3 py-2 text-xs font-semibold
                        {{ $estado === 'critico' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                        ⚠ Stock {{ $estado === 'critico' ? 'crítico' : 'bajo mínimo' }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ TAB: STOCK ═══════════════════ --}}
<div id="tab-stock" class="tab-panel">
    @php $estado = $producto->estadoStock(); @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card text-center">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Stock Actual</p>
            <p class="text-3xl font-bold {{ $estado === 'critico' ? 'text-red-600' : ($estado === 'minimo' ? 'text-amber-600' : 'text-gray-900') }}">
                {{ $producto->stock_actual }}
            </p>
            <p class="text-xs text-gray-400 mt-1">{{ $producto->unidadMedida?->nombre ?? $producto->unidad ?? 'unidades' }}</p>
        </div>
        <div class="stat-card text-center">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Entradas</p>
            <p class="text-3xl font-bold text-green-600">{{ $totalEntradas }}</p>
            <p class="text-xs text-gray-400 mt-1">acumulado</p>
        </div>
        <div class="stat-card text-center">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Salidas</p>
            <p class="text-3xl font-bold text-red-500">{{ $totalSalidas }}</p>
            <p class="text-xs text-gray-400 mt-1">acumulado</p>
        </div>
        <div class="stat-card text-center">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Estado</p>
            <p class="text-lg font-bold mt-1
                {{ $estado === 'critico' ? 'text-red-600' : ($estado === 'minimo' ? 'text-amber-600' : 'text-green-600') }}">
                {{ $estado === 'critico' ? '⛔ Crítico' : ($estado === 'minimo' ? '⚠ Mínimo' : '✓ Normal') }}
            </p>
        </div>
    </div>

    {{-- Barra visual --}}
    @if($producto->stock_minimo)
    <div class="bg-white rounded-xl shadow p-5 mb-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Nivel de Stock</h3>
        @php
            $max   = max($producto->stock_actual, $producto->stock_minimo * 2, 1);
            $pct   = min(100, round($producto->stock_actual / $max * 100));
            $color = $estado === 'critico' ? '#ef4444' : ($estado === 'minimo' ? '#f59e0b' : '#22c55e');
        @endphp
        <div style="background:#f3f4f6;border-radius:9999px;height:12px;overflow:hidden;">
            <div style="width:{{ $pct }}%;height:100%;background:{{ $color }};border-radius:9999px;transition:width .5s;"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-400 mt-1">
            <span>0</span>
            @if($producto->stock_critico) <span style="color:#ef4444;">Crít: {{ $producto->stock_critico }}</span> @endif
            @if($producto->stock_minimo)  <span style="color:#f59e0b;">Mín: {{ $producto->stock_minimo }}</span>  @endif
            <span>{{ $max }}</span>
        </div>
    </div>
    @endif

    {{-- Últimos 5 movimientos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Últimos Movimientos</h3>
        </div>
        <table class="min-w-full text-xs divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Tipo</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Motivo</th>
                    <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Cantidad</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($movimientos->take(5) as $mov)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2.5">
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full
                            {{ $mov->tipo === 'entrada' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ ucfirst($mov->tipo) }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-gray-600">{{ Str::limit($mov->motivo, 60) }}</td>
                    <td class="px-4 py-2.5 text-center font-bold {{ $mov->tipo === 'entrada' ? 'text-green-600' : 'text-red-500' }}">
                        {{ $mov->tipo === 'entrada' ? '+' : '-' }}{{ $mov->cantidad }}
                    </td>
                </tr>
                @endforeach
                @if($movimientos->isEmpty())
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Sin movimientos registrados.</td></tr>
                @endif
            </tbody>
        </table>
        @if($movimientos->count() > 5)
        <div class="px-5 py-3 border-t border-gray-100 text-center">
            <button onclick="switchTab('movimientos')" class="text-xs text-indigo-600 font-semibold hover:underline">
                Ver todos los movimientos →
            </button>
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════════ TAB: COSTOS (solo admin) ═══════════════════ --}}
@if(auth()->user()->esAdmin())
<div id="tab-costos" class="tab-panel">
    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Último Costo Unitario</p>
            <p class="text-2xl font-bold text-gray-900">
                {{ $ultimoCosto ? '$' . number_format($ultimoCosto, 0, ',', '.') : '—' }}
            </p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Costo Promedio</p>
            <p class="text-2xl font-bold text-indigo-700">
                {{ $costoPromedio ? '$' . number_format($costoPromedio, 0, ',', '.') : '—' }}
            </p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Valorización Actual</p>
            <p class="text-2xl font-bold text-green-700">
                {{ ($ultimoCosto && $producto->stock_actual) ? '$' . number_format($ultimoCosto * $producto->stock_actual, 0, ',', '.') : '—' }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">Último costo × stock actual</p>
        </div>
    </div>

    {{-- Historial de costos --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Historial de Ingresos y Costos</h3>
            <span class="text-xs text-gray-400">{{ $precios->total() }} registro(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600 whitespace-nowrap">Fecha</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Tipo Ingreso</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Documento / Referencia</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Cantidad</th>
                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">Costo Unit.</th>
                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600 whitespace-nowrap">Total</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($precios as $precio)
                    @php
                        $fuenteLabel = match($precio->fuente) {
                            'boleta_local' => ['texto' => 'Boleta Local',  'bg' => '#fef3c7', 'color' => '#92400e'],
                            'sicd_masiva'  => ['texto' => 'SICD OC',       'bg' => '#eff6ff', 'color' => '#1e40af'],
                            'sicd_manual'  => ['texto' => 'SICD Manual',   'bg' => '#f0fdf4', 'color' => '#166534'],
                            default        => ['texto' => 'Manual',        'bg' => '#f3f4f6', 'color' => '#6b7280'],
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $precio->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2.5">
                            <span style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:9999px;background:{{ $fuenteLabel['bg'] }};color:{{ $fuenteLabel['color'] }};white-space:nowrap;">
                                {{ $fuenteLabel['texto'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600">{{ $precio->notas ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-center font-semibold text-gray-700">{{ $precio->cantidad }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-gray-800 whitespace-nowrap">${{ number_format($precio->precio_neto, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-green-700 whitespace-nowrap">
                            {{ $precio->precio_total ? '$' . number_format($precio->precio_total, 0, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $precio->usuario?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Sin registros de costos aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Paginación costos --}}
        @if($precios->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-1">
            @foreach($precios->links()->offsetGet('elements') ?? [] as $el)
                @if(is_string($el)) <span class="px-2 py-1 text-xs text-gray-400">{{ $el }}</span>
                @elseif(is_array($el))
                    @foreach($el as $page => $url)
                        @if($page == $precios->currentPage())
                            <span style="padding:.2rem .6rem;font-size:.75rem;font-weight:700;background:#4f46e5;color:#fff;border-radius:.4rem;">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}#tab=costos" style="padding:.2rem .6rem;font-size:.75rem;background:#eff6ff;color:#4f46e5;border-radius:.4rem;text-decoration:none;">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>
        @endif
    </div>
</div>
@endif

{{-- ═══════════════════ TAB: MOVIMIENTOS ═══════════════════ --}}
<div id="tab-movimientos" class="tab-panel">
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Historial de Movimientos</h3>
            <span class="text-xs text-gray-400">{{ $movimientos->total() }} movimiento(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600 whitespace-nowrap">Fecha</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Tipo</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Motivo / Documento</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Usuario</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Cantidad</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Aprobado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($movimientos as $mov)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap
                                {{ $mov->tipo === 'entrada' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                {{ $mov->tipo === 'entrada' ? '↑' : '↓' }} {{ ucfirst($mov->tipo) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 max-w-xs">{{ $mov->motivo ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $mov->usuario?->name ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-center font-bold whitespace-nowrap
                            {{ $mov->tipo === 'entrada' ? 'text-green-600' : 'text-red-500' }}">
                            {{ $mov->tipo === 'entrada' ? '+' : '-' }}{{ $mov->cantidad }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $mov->aprobado_por ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Sin movimientos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Paginación movimientos --}}
        @if($movimientos->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-1">
            @foreach($movimientos->links()->offsetGet('elements') ?? [] as $el)
                @if(is_string($el)) <span class="px-2 py-1 text-xs text-gray-400">{{ $el }}</span>
                @elseif(is_array($el))
                    @foreach($el as $page => $url)
                        @if($page == $movimientos->currentPage())
                            <span style="padding:.2rem .6rem;font-size:.75rem;font-weight:700;background:#4f46e5;color:#fff;border-radius:.4rem;">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}#tab=movimientos" style="padding:.2rem .6rem;font-size:.75rem;background:#eff6ff;color:#4f46e5;border-radius:.4rem;text-decoration:none;">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════════ TAB: DOCUMENTOS ═══════════════════ --}}
<div id="tab-documentos" class="tab-panel">
    <div class="space-y-5">

        {{-- SICDs --}}
        @if($sicds->isNotEmpty())
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">SICDs asociados</h3>
            </div>
            <table class="min-w-full text-xs divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Código SICD</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Cant. Solicitada</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Cant. Recibida</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Fecha</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($sicds as $sicd)
                    @php $det = $sicd->detalles->first(); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-mono font-semibold text-indigo-700">{{ $sicd->codigo_sicd }}</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full
                                {{ $sicd->estado === 'recibido' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($sicd->estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center text-gray-700">{{ $det?->cantidad_solicitada ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-700">{{ $det?->cantidad_recibida ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $sicd->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5 text-right">
                            <a href="{{ route('admin.sicd.show', $sicd->id) }}" class="text-indigo-600 hover:underline text-xs font-medium">Ver →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Órdenes de Compra --}}
        @if($ordenes->isNotEmpty())
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Órdenes de Compra relacionadas</h3>
            </div>
            <table class="min-w-full text-xs divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">N° OC</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Proveedor MP</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Fecha</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($ordenes as $oc)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-mono font-semibold text-gray-800">{{ $oc->numero_oc }}</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full
                                {{ $oc->estado === 'recibido' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($oc->estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600">{{ $oc->api_proveedor_nombre ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ $oc->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5 text-right">
                            <a href="{{ route('admin.ordenes.show', $oc->id) }}" class="text-indigo-600 hover:underline text-xs font-medium">Ver →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Gastos Menores --}}
        @if($gastos->isNotEmpty())
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Gastos Menores (Boletas locales)</h3>
            </div>
            <table class="min-w-full text-xs divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Folio</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">RUT Proveedor</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Cantidad</th>
                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Monto</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Fecha</th>
                        @if(auth()->user()->esAdmin())
                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600">P. Neto</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($gastos as $gasto)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-mono text-gray-700">{{ $gasto->folio }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ $gasto->rut_proveedor }}</td>
                        <td class="px-4 py-2.5 text-center font-semibold text-gray-700">{{ $gasto->cantidad }}</td>
                        <td class="px-4 py-2.5 text-right text-gray-700">${{ number_format($gasto->monto, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($gasto->fecha_emision)->format('d/m/Y') }}</td>
                        @if(auth()->user()->esAdmin())
                        <td class="px-4 py-2.5 text-right text-gray-600">
                            {{ $gasto->precio_neto ? '$' . number_format($gasto->precio_neto, 0, ',', '.') : '—' }}
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($sicds->isEmpty() && $ordenes->isEmpty() && $gastos->isEmpty())
        <div class="bg-white rounded-xl shadow py-16 text-center text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm">No hay documentos asociados a este producto.</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function switchTab(name) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    var btn = document.getElementById('tab-btn-' + name);
    var panel = document.getElementById('tab-' + name);
    if (btn) btn.classList.add('active');
    if (panel) panel.classList.add('active');
    history.replaceState(null, '', window.location.pathname + '?tab=' + name);
}

// Restaurar tab desde URL
(function() {
    var params = new URLSearchParams(window.location.search);
    var tab = params.get('tab');
    if (tab && document.getElementById('tab-' + tab)) switchTab(tab);
})();
</script>
@endpush

@endsection
