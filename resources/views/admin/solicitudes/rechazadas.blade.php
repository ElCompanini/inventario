@extends('layouts.app')

@section('title', 'Solicitudes Rechazadas')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Solicitudes Rechazadas</h1>
        <p class="text-sm text-gray-500 mt-1">Historial de solicitudes que fueron rechazadas</p>
    </div>
    <a href="{{ route('admin.solicitudes') }}" class="text-sm text-indigo-600 hover:underline font-medium">
        ← Volver a pendientes
    </a>
</div>

<div class="mb-4">
    <input id="buscador-rechazadas" type="text" placeholder="🔍  Buscar solicitud rechazada..."
        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

<div class="bg-white rounded-xl shadow overflow-hidden p-4">
    <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
    <table id="tabla-rechazadas" class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">#</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Producto</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Cantidad</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Motivo solicitud</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Motivo rechazo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Solicitante</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Rechazado por</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Fecha rechazo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($solicitudes as $s)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ $s->id }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $s->producto->nombre }}</td>
                <td class="px-4 py-3">
                    @if($s->tipo === 'entrada')
                    <span class="inline-flex items-center bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">↑ Entrada</span>
                    @else
                    <span class="inline-flex items-center bg-orange-100 text-orange-700 text-xs font-semibold px-2.5 py-1 rounded-full">↓ Salida</span>
                    @endif
                </td>
                <td class="px-4 py-3 font-bold text-gray-700">{{ $s->cantidad }}</td>
                <td class="px-4 py-3 text-gray-600 max-w-xs whitespace-normal break-words">{{ $s->motivo }}</td>
                <td class="px-4 py-3 text-red-700 max-w-xs whitespace-normal break-words">{{ $s->motivo_rechazo ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $s->usuario->name }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $s->rechazado_por ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $s->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
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
        const table = $('#tabla-rechazadas').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
            },
            order: [],
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
        $('#buscador-rechazadas').on('input', function() {
            table.search(this.value).draw();
        });
    });
</script>
@endpush

@endsection