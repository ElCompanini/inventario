@extends('layouts.app')

@section('title', 'Órdenes de Compra')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Órdenes de Compra</h1>
        <p class="text-sm text-gray-500 mt-1">Agrupación de SICDs y recepción de mercadería</p>
    </div>
    <a href="{{ route('admin.ordenes.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva OC
    </a>
</div>

<div class="mb-4">
    <input id="buscador-ordenes" type="text" placeholder="🔍  Buscar orden de compra..."
           class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

<div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden p-4">
    <table id="tabla-ordenes" class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">N° OC</th>
                <th class="px-4 py-3 font-semibold text-gray-600">SICDs</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 font-semibold text-gray-600 text-center">Factura</th>
                <th class="px-4 py-3 font-semibold text-gray-600 text-center">Guía</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Creado por</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($ordenes as $oc)
                @php
                    $tieneFactura = $oc->factura !== null;
                    $tieneGuia    = $oc->guia !== null;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono font-semibold text-indigo-700">{{ $oc->numero_oc }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $oc->sicds->count() }} SICD(s)</td>
                    <td class="px-4 py-2">
                        @if($oc->estado === 'recibido')
                            <span class="inline-flex items-center bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">✓ Recibido</span>
                        @else
                            <a href="{{ route('admin.ordenes.show', $oc->id) }}"
                               class="inline-flex items-center bg-yellow-100 text-yellow-700 text-xs font-semibold px-2.5 py-1 rounded-full hover:bg-yellow-200 transition">⏳ Pendiente</a>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        <span class="w-2.5 h-2.5 rounded-full inline-block {{ $tieneFactura ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <span class="w-2.5 h-2.5 rounded-full inline-block {{ $tieneGuia ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                    </td>
                    <td class="px-4 py-2 text-gray-600">{{ $oc->usuario->name }}</td>
                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $oc->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('admin.ordenes.show', $oc->id) }}"
                           class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition">
                            Ver →
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@push('head')
<style>
    .dt-btn-excel { background:#16a34a; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn-excel:hover { background:#15803d; }
    .dt-btn { background:#2563eb; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn:hover { background:#1d4ed8; }
    .dt-btn-pdf { background:#dc2626; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn-pdf:hover { background:#b91c1c; }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {
        const table = $('#tabla-ordenes').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
            order: [[6, 'desc']],
            paging: false,
            layout: { topStart: 'buttons', topEnd: null, bottomStart: null, bottomEnd: null },
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel' },
                { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn' },
                { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf' },
            ],
            columnDefs: [{ orderable: false, targets: -1 }],
        });
        $('#buscador-ordenes').on('input', function () { table.search(this.value).draw(); });
    });
</script>
@endpush

@endsection
