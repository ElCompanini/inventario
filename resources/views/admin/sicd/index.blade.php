@extends('layouts.app')

@section('title', 'SICD')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">SICD</h1>
    <p class="text-sm text-gray-500 mt-1">Gestión documental de SICDs</p>
</div>

<div class="mb-4">
    <input id="buscador-sicds" type="text" placeholder="🔍  Buscar SICD..."
           class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

<div class="bg-white rounded-xl shadow overflow-hidden p-4">
    <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
    <table id="tabla-sicds" class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">Código SICD</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Productos</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 font-semibold text-gray-600">OC</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Creado por</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($sicds as $sicd)
                @php
                    $oc = $sicd->ordenesCompra->first();
                @endphp
                <tr>
                    <td class="px-4 py-3 font-mono font-semibold text-indigo-700">{{ $sicd->codigo_sicd }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $sicd->detalles->count() }} producto(s)</td>
                    <td class="px-4 py-3">
                        @if($sicd->estado === 'recibido')
                            <span class="inline-flex items-center bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">✓ Recibido</span>
                        @elseif($sicd->estado === 'agrupado')
                            <span class="inline-flex items-center bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">📎 Agrupado</span>
                        @else
                            <span class="inline-flex items-center bg-yellow-100 text-yellow-700 text-xs font-semibold px-2.5 py-1 rounded-full">⏳ Pendiente</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        @if($oc)
                            <a href="{{ route('admin.ordenes.show', $oc->id) }}" class="text-indigo-600 hover:underline font-mono text-xs">{{ $oc->numero_oc }}</a>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $sicd->usuario->name }}</td>
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $sicd->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            @if($sicd->archivo_ruta)
                                <a href="{{ route('admin.sicd.descargar', $sicd->id) }}"
                                   class="text-xs font-medium text-gray-500 hover:text-gray-700 transition whitespace-nowrap">
                                    Ver boleta
                                </a>
                            @endif
                            <a href="{{ route('admin.sicd.show', $sicd->id) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition">
                                Ver →
                            </a>
                        </div>
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
    $(document).ready(function () {
        const table = $('#tabla-sicds').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
            order: [[5, 'desc']],
            paging: false,
            layout: { topStart: 'buttons', topEnd: null, bottomStart: null, bottomEnd: null },
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel' },
                { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn' },
                { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf' },
            ],
            columnDefs: [{ orderable: false, targets: -1 }],
        });
        $('#buscador-sicds').on('input', function () { table.search(this.value).draw(); });
    });
</script>
@endpush

@endsection
