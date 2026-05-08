@extends('layouts.app')
@section('title', 'Historial de Reporterías')

@section('content')

<div class="mb-5 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Historial de Reporterías</h1>
        <p class="text-sm text-gray-500 mt-1">Índice de todos los reportes generados — auditoría y trazabilidad completa.</p>
    </div>
    <a href="{{ route('admin.reportes.index') }}"
       class="text-sm text-indigo-600 hover:underline">← Volver a Reportes</a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('admin.reportes.historial') }}"
      class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
            <select name="tipo" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">— Todos —</option>
                @foreach($tipos as $val => $label)
                    <option value="{{ $val }}" {{ request('tipo') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Módulo</label>
            <select name="modulo" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">— Todos —</option>
                @foreach($modulos as $val => $label)
                    <option value="{{ $val }}" {{ request('modulo') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Formato</label>
            <select name="formato" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">— Todos —</option>
                <option value="EXCEL" {{ request('formato') === 'EXCEL' ? 'selected' : '' }}>Excel</option>
                <option value="PDF"   {{ request('formato') === 'PDF'   ? 'selected' : '' }}>PDF</option>
                <option value="CSV"   {{ request('formato') === 'CSV'   ? 'selected' : '' }}>CSV</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, usuario..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
    </div>

    <div class="mt-3 flex items-center gap-2">
        <button type="submit"
                class="px-4 py-1.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
            Filtrar
        </button>
        <a href="{{ route('admin.reportes.historial') }}"
           class="px-4 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Limpiar
        </a>
        <span class="text-xs text-gray-400 ml-2">{{ $reporterias->total() }} resultado(s)</span>
    </div>
</form>

{{-- Tabla --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
    @if($reporterias->isEmpty())
        <div class="px-5 py-12 text-center text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="font-medium">No hay reporterías registradas aún.</p>
            <p class="text-sm mt-1">Cada vez que exportes un reporte (Excel, PDF) quedará indexado aquí.</p>
        </div>
    @else
        <table class="min-w-full text-sm divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-sm">Reporte</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-sm">Tipo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-sm">Usuario</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-sm">Producto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-sm">Fechas</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 text-sm">Formato</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-sm whitespace-nowrap">Fecha</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 text-sm">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($reporterias as $r)
                @php
                    $prodId       = $r->filtros['producto_id'] ?? null;
                    $prodNombre   = $r->filtros['producto_nombre'] ?? null;
                    $fechaDesde   = $r->filtros['fecha_desde'] ?? null;
                    $fechaHasta   = $r->filtros['fecha_hasta'] ?? null;
                    $qParams      = array_filter([
                        'producto_id' => $prodId,
                        'fecha_desde' => $fechaDesde,
                        'fecha_hasta' => $fechaHasta,
                        'tipo'        => $r->filtros['tipo'] ?? null,
                    ]);
                    $fmtClass = match($r->formato) {
                        'EXCEL' => 'bg-green-100 text-green-700',
                        'PDF'   => 'bg-red-100 text-red-700',
                        'CSV'   => 'bg-blue-100 text-blue-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition">

                    {{-- Reporte --}}
                    <td class="px-4 py-3" style="max-width:260px;">
                        <p class="font-medium text-gray-800 truncate text-sm">{{ $r->nombre }}</p>
                    </td>

                    {{-- Tipo --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700">
                            {{ $r->tipo }}
                        </span>
                    </td>

                    {{-- Usuario --}}
                    <td class="px-4 py-3 text-gray-700 text-sm whitespace-nowrap">
                        {{ $r->usuario_nombre ?? '—' }}
                    </td>

                    {{-- Producto --}}
                    <td class="px-4 py-3 text-sm text-gray-600" style="max-width:220px;">
                        @if($prodNombre)
                            <span class="truncate block" title="{{ $prodNombre }}">{{ $prodNombre }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Fechas filtro --}}
                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                        @if($fechaDesde || $fechaHasta)
                            <span>{{ $fechaDesde ?? '…' }}</span>
                            @if($fechaHasta) <span class="text-gray-300 mx-1">→</span><span>{{ $fechaHasta }}</span> @endif
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Formato --}}
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full {{ $fmtClass }}">
                            {{ $r->formato }}
                        </span>
                    </td>

                    {{-- Fecha generación --}}
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-sm">
                        {{ $r->created_at->format('d/m/Y H:i') }}
                    </td>

                    {{-- Acciones --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">

                            @if($prodId)
                                {{-- Ver --}}
                                <a href="{{ route('admin.reportes.bincard') }}?{{ http_build_query(array_merge($qParams, ['solo_ver' => 1])) }}"
                                   title="Ver Bincard"
                                   class="p-2 rounded-lg text-indigo-600 border border-indigo-200 hover:bg-indigo-50 transition">
                                    <svg class="w-4.5 h-4.5" style="width:1.125rem;height:1.125rem" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                {{-- Excel --}}
                                <a href="{{ route('admin.reportes.bincard.excel') }}?{{ http_build_query($qParams) }}"
                                   title="Exportar Excel"
                                   class="p-2 rounded-lg text-green-700 border border-green-200 hover:bg-green-50 transition">
                                    <svg style="width:1.125rem;height:1.125rem" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                                {{-- PDF --}}
                                <a href="{{ route('admin.reportes.bincard.pdf') }}?{{ http_build_query($qParams) }}"
                                   title="Exportar PDF"
                                   class="p-2 rounded-lg text-red-600 border border-red-200 hover:bg-red-50 transition">
                                    <svg style="width:1.125rem;height:1.125rem" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </a>
                            @endif

                            @if($r->tieneArchivo() && !$prodId)
                                {{-- Descargar --}}
                                <a href="{{ route('admin.reportes.historial.descargar', $r->id) }}"
                                   title="Descargar archivo guardado"
                                   class="p-2 rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                                    <svg style="width:1.125rem;height:1.125rem" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                            @endif

                            {{-- Eliminar del índice --}}
                            <form method="POST" action="{{ route('admin.reportes.historial.destroy', $r->id) }}"
                                  onsubmit="return confirm('¿Eliminar del índice?')">
                                @csrf @method('DELETE')
                                <button type="submit" title="Eliminar del índice"
                                        class="p-2 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition">
                                    <svg style="width:1.125rem;height:1.125rem" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($reporterias->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $reporterias->links() }}
            </div>
        @endif
    @endif
</div>

@endsection
