@extends('layouts.app')

@section('title', 'Historial de Cambios')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>

        <h1 class="text-2xl font-bold text-gray-800">Historial de Cambios</h1>
        <p class="text-sm text-gray-500 mt-1">Registro completo e inmutable de todos los movimientos de stock</p>
    </div>
    <span class="text-sm text-gray-500">Total: {{ $historial->count() }} registros</span>
</div>

{{-- Buscador --}}
<div class="mb-4">
    <input id="buscador-productos" type="text" placeholder="🔍  Buscar en historial..."
        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

<div class="bg-white rounded-xl shadow overflow-hidden p-4">
    <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
    <table id="tabla-historial" class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Producto</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Cantidad</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Motivo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Solicitante</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Aprobado por</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Origen</th>
            </tr>
        </thead>
        <tbody>

            @foreach($historial as $registro)
            <tr class="{{ $registro->tipo === 'traslado' ? 'bg-blue-50 hover:bg-blue-100' : 'hover:bg-gray-50' }} transition">
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                    {{ $registro->created_at->format('d/m/Y H:i') }}
                </td>
                <td class="px-4 py-3 font-medium text-gray-900">
                    {{ $registro->producto->nombre }}
                    <span class="text-xs text-gray-400 ml-1">C{{ $registro->producto->contenedor }}</span>
                </td>
                <td class="px-4 py-3">
                    @if($registro->tipo === 'entrada')
                    <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">↑ Entrada</span>
                    @elseif($registro->tipo === 'salida')
                    <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs font-semibold px-2.5 py-1 rounded-full">↓ Salida</span>
                    @else
                    <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">⇄ Traslado</span>
                    @endif
                </td>
                <td class="px-4 py-3 font-bold text-gray-800">
                    @if($registro->tipo === 'traslado')
                    <span class="text-blue-600">{{ $registro->cantidad }}</span>
                    @else
                    {{ $registro->tipo === 'entrada' ? '+' : '−' }}{{ $registro->cantidad }}
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600 max-w-xs whitespace-normal break-words">
                    {{ $registro->motivo }}
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $registro->usuario->name }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $registro->aprobado_por ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if($registro->origen === 'sicd')
                    @if(auth()->user()->tienePermiso('sicd'))
                    <a href="{{ route('admin.sicd.show', $registro->origen_id) }}"
                        class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full hover:bg-indigo-200 transition">
                        {{ $registro->sicd?->codigo_sicd ?? 'SICD #' . $registro->origen_id }}
                    </a>
                    @else
                    <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                        {{ $registro->sicd?->codigo_sicd ?? 'SICD #' . $registro->origen_id }}
                    </span>
                    @endif
                    @elseif($registro->origen === 'solicitud')
                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-0.5 rounded-full">
                        Solicitud #{{ $registro->origen_id }}
                    </span>
                    @elseif($registro->origen === 'gasto_menor')
                    <a href="{{ route('admin.gastos-menores.index') }}{{ $registro->origen_id ? '?gm=' . $registro->origen_id : '' }}"
                       class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full font-mono transition"
                       style="background:#fef3c7; color:#b45309;"
                       onmouseover="this.style.background='#fde68a'"
                       onmouseout="this.style.background='#fef3c7'">
                        {{ $registro->origen_id ? 'GM-' . str_pad($registro->origen_id, 4, '0', STR_PAD_LEFT) : 'Gasto Menor' }}
                    </a>
                    @else
                    <span class="text-gray-400 text-xs">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@push('head')
<style>
@keyframes btn-breathe-green { 0%,100%{box-shadow:0 0 0 0 rgba(22,163,74,.7)} 50%{box-shadow:0 0 0 6px rgba(22,163,74,0)} }
    @keyframes btn-breathe-blue  { 0%,100%{box-shadow:0 0 0 0 rgba(37,99,235,.7)} 50%{box-shadow:0 0 0 6px rgba(37,99,235,0)} }
    @keyframes btn-breathe-red   { 0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.7)} 50%{box-shadow:0 0 0 6px rgba(220,38,38,0)} }
    .dt-btn-excel { background:#16a34a; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s,transform .15s; }
    .dt-btn-excel:hover { background:#15803d; transform:translateY(-1px); animation:btn-breathe-green 1.6s ease-in-out infinite; }
    .dt-btn { background:#2563eb; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s,transform .15s; }
    .dt-btn:hover { background:#1d4ed8; transform:translateY(-1px); animation:btn-breathe-blue 1.6s ease-in-out infinite; }
    .dt-btn-pdf { background:#dc2626; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s,transform .15s; }
    .dt-btn-pdf:hover { background:#b91c1c; transform:translateY(-1px); animation:btn-breathe-red 1.6s ease-in-out infinite; }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const table = $('#tabla-historial').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
            },
            order: [
                [0, 'desc']
            ],
            paging: false,
            layout: {
                topStart: 'buttons',
                topEnd: null,
                bottomStart: null,
                bottomEnd: null
            },
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'dt-btn-excel',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'dt-btn',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    className: 'dt-btn-pdf',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    },
                    orientation: 'landscape',
                    pageSize: 'A4'
                },
            ],
        });
        $('#buscador-productos').on('input', function() {
            table.search(this.value).draw();
        });
    });
</script>
@endpush

@endsection