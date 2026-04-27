@extends('layouts.app')

@section('title', 'Historial de Cambios')

@section('content')

@php
    // Pre-construir el HTML de cada grupo hijo (fuera del tbody para no contaminar DataTables)
    $grupoChildren = [];
    foreach ($filas as $idx => $fila) {
        if ($fila['tipo'] !== 'grupo') continue;
        $regs = $fila['registros'];
        $h  = '<div class="subtabla-wrap" style="border-top:1px solid #e5e7eb;background:#fafafa;">';
        $h .= '<table class="subtabla-grupo" style="border-collapse:collapse;font-size:0.8rem;table-layout:fixed;">';
        $h .= '<colgroup>';
        $h .= '<col class="sg-producto">';
        $h .= '<col class="sg-contenedor">';
        $h .= '<col class="sg-tipo">';
        $h .= '<col class="sg-cantidad">';
        $h .= '<col class="sg-motivo">';
        $h .= '<col class="sg-aprobado">';
        $h .= '</colgroup>';
        $h .= '<thead><tr style="background:#f1f5f9;">';
        $h .= '<th style="padding:5px 16px;text-align:left;font-weight:600;color:#6b7280;">Producto</th>';
        $h .= '<th style="padding:5px 16px;text-align:left;font-weight:600;color:#6b7280;">Contenedor</th>';
        $h .= '<th style="padding:5px 0;"></th>';
        $h .= '<th style="padding:5px 16px;text-align:left;font-weight:600;color:#6b7280;">Cant.</th>';
        $h .= '<th style="padding:5px 16px;text-align:left;font-weight:600;color:#6b7280;">Motivo</th>';
        $h .= '<th style="padding:5px 16px;text-align:left;font-weight:600;color:#6b7280;">Aprobado por</th>';
        $h .= '</tr></thead><tbody>';
        foreach ($regs as $r) {
            if ($r->tipo === 'entrada') {
                $color = '#16a34a'; $signo = '+';
            } elseif ($r->tipo === 'salida') {
                $color = '#ea580c'; $signo = '−';
            } else {
                $color = '#2563eb'; $signo = '';
            }
            $contNombre = $r->container?->nombre ?? ($r->contenedor_id ? 'C'.$r->contenedor_id : '—');
            $prodDesc = $r->producto?->nombre ?? '—';
            $prodNom  = '';
            $h .= '<tr style="border-top:1px solid #f1f5f9;">';
            $h .= '<td style="padding:6px 16px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;color:#111827;" title="' . e($prodDesc) . '">' . e($prodDesc) . $prodNom . '</td>';
            $h .= '<td style="padding:6px 16px;"><span style="font-size:0.72rem;background:#e5e7eb;color:#374151;padding:2px 8px;border-radius:999px;">' . e($contNombre) . '</span></td>';
            $h .= '<td style="padding:6px 0;"></td>';
            $h .= '<td style="padding:6px 16px;font-weight:700;color:' . $color . ';">' . $signo . $r->cantidad . '</td>';
            $h .= '<td style="padding:6px 16px;color:#4b5563;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">' . e($r->motivo) . '</td>';
            $h .= '<td style="padding:6px 16px;color:#6b7280;">' . e($r->aprobado_por ?? '—') . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table></div>';
        $grupoChildren[$idx] = $h;
    }
@endphp

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
                <th class="px-4 py-3 font-semibold text-gray-600" style="width:200px;max-width:200px;">Producto</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Contenedor</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Cantidad</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Motivo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Solicitante</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Aprobado por</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Origen</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $idx => $fila)

            @if($fila['tipo'] === 'grupo')
            @php
                $registros = $fila['registros'];
                $primero   = $registros->first();
                $total     = $registros->sum('cantidad');
            @endphp

            <tr class="fila-grupo cursor-pointer hover:bg-indigo-50 transition" data-gidx="{{ $idx }}">
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                    {{ $primero->created_at->format('d/m/Y H:i') }}
                </td>
                <td class="px-4 py-3 font-medium text-indigo-700" style="max-width:200px;">
                    <div style="display:flex;align-items:center;gap:0.4rem;">
                        <svg class="grupo-chevron" style="width:14px;height:14px;flex-shrink:0;transition:transform .2s;color:#818cf8;"
                             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span style="font-weight:600;">{{ $registros->count() }} productos</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-400 text-xs">—</td>
                <td class="px-4 py-3">
                    @if($primero->tipo === 'entrada')
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">↑ Entrada</span>
                    @elseif($primero->tipo === 'salida')
                        <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs font-semibold px-2.5 py-1 rounded-full">↓ Salida</span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">⇄ Traslado</span>
                    @endif
                </td>
                <td class="px-4 py-3 font-bold {{ $primero->tipo === 'entrada' ? 'text-green-700' : 'text-orange-600' }}">
                    {{ $primero->tipo === 'entrada' ? '+' : '−' }}{{ $total }}
                </td>
                <td class="px-4 py-3 text-gray-600 max-w-xs whitespace-normal break-words">
                    {{ $primero->motivo }}
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $primero->usuario?->name }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $primero->aprobado_por ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if($primero->origen === 'sicd')
                        <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
                        @if(auth()->user()->tienePermiso('sicd'))
                        <a href="{{ route('admin.sicd.show', $primero->origen_id) }}"
                           class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full hover:bg-indigo-200 transition">
                            {{ $primero->sicd?->codigo_sicd ?? 'SICD #' . $primero->origen_id }}
                        </a>
                        @else
                        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            {{ $primero->sicd?->codigo_sicd ?? 'SICD #' . $primero->origen_id }}
                        </span>
                        @endif
                        @if($primero->sicd?->boleta)
                        <a href="{{ route('admin.sicd.descargar', $primero->origen_id) }}"
                           class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700 transition whitespace-nowrap">
                            Ver boleta
                        </a>
                        @endif
                        </div>
                    @elseif($primero->origen === 'solicitud')
                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-0.5 rounded-full">
                            Solicitud #{{ $primero->origen_id }}
                        </span>
                    @elseif($primero->origen === 'gasto_menor')
                        <a href="{{ route('admin.gastos-menores.index') }}{{ $primero->origen_id ? '?gm=' . $primero->origen_id : '' }}"
                           class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full transition"
                           style="background:#fef3c7;color:#b45309;"
                           onmouseover="this.style.background='#fde68a'"
                           onmouseout="this.style.background='#fef3c7'">
                            {{ $primero->origen_id ? 'GM-' . str_pad($primero->origen_id, 4, '0', STR_PAD_LEFT) : 'Gasto Menor' }}
                        </a>
                    @else
                        <span class="text-gray-400 text-xs">—</span>
                    @endif
                </td>
            </tr>

            @else
            {{-- ── Fila individual (idéntica a antes) ── --}}
            @php $registro = $fila['registro']; @endphp
            <tr class="{{ $registro->tipo === 'traslado' ? 'bg-blue-50 hover:bg-blue-100' : 'hover:bg-gray-50' }} transition">
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                    {{ $registro->created_at->format('d/m/Y H:i') }}
                </td>
                <td class="px-4 py-3" style="max-width:200px;">
                    <p class="font-medium text-gray-900 truncate" title="{{ $registro->producto->nombre }}">{{ $registro->producto->nombre }}</p>
                </td>
                <td class="px-4 py-3">
                    @php
                        $cNombre = $registro->container?->nombre ?? ($registro->contenedor_id ? 'C'.$registro->contenedor_id : '—');
                    @endphp
                    <span class="inline-block text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $cNombre }}</span>
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
                    <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
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
                    @if($registro->sicd?->boleta)
                    <a href="{{ route('admin.sicd.descargar', $registro->origen_id) }}"
                       class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700 transition whitespace-nowrap">
                        Ver boleta
                    </a>
                    @endif
                    </div>
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
            @endif

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
    tr.fila-grupo { background:#fafafe; }
    tr.fila-grupo.shown, tr.dt-hasChild { background:#eef2ff !important; }
</style>
@endpush

@push('scripts')
<script>
// Contenido hijo de cada grupo, indexado por $idx del foreach
var grupoChildren = @json($grupoChildren);

$(document).ready(function() {
    const table = $('#tabla-historial').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
        order: [[0, 'desc']],
        paging: false,
        layout: { topStart: 'buttons', topEnd: null, bottomStart: null, bottomEnd: null },
        buttons: [
            { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn',       exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf',   exportOptions: { columns: ':not(:last-child)' }, orientation: 'landscape', pageSize: 'A4' },
        ],
    });

    $('#buscador-productos').on('input', function() {
        table.search(this.value).draw();
    });

    function alinearSubtabla(childNode) {
        const ths = document.querySelectorAll('#tabla-historial thead th');
        // Índices en la tabla padre: 0=Fecha,1=Producto,2=Contenedor,3=Tipo,4=Cantidad,5=Motivo,6=Solicitante,7=Aprobado,8=Origen
        const fechaW     = ths[0] ? ths[0].offsetWidth : 0;
        const productoW  = ths[1] ? ths[1].offsetWidth : 200;
        const contenW    = ths[2] ? ths[2].offsetWidth : 120;
        const tipoW      = ths[3] ? ths[3].offsetWidth : 90;
        const cantidadW  = ths[4] ? ths[4].offsetWidth : 90;
        const motivoW    = ths[5] ? ths[5].offsetWidth : 200;
        const aprobadoW  = ths[7] ? ths[7].offsetWidth : 120;

        const sgProductoW = fechaW + productoW;
        const totalW = sgProductoW + contenW + tipoW + cantidadW + motivoW + aprobadoW;

        const wrap = childNode.querySelector('.subtabla-wrap');
        const tbl  = childNode.querySelector('table.subtabla-grupo');
        if (!wrap || !tbl) return;

        wrap.style.paddingLeft = '0';
        tbl.style.width = totalW + 'px';

        tbl.querySelector('.sg-producto').style.width   = sgProductoW + 'px';
        tbl.querySelector('.sg-contenedor').style.width = contenW + 'px';
        tbl.querySelector('.sg-tipo').style.width       = tipoW + 'px';
        tbl.querySelector('.sg-cantidad').style.width   = cantidadW + 'px';
        tbl.querySelector('.sg-motivo').style.width     = motivoW + 'px';
        tbl.querySelector('.sg-aprobado').style.width   = aprobadoW + 'px';
    }

    // Toggle desplegable en filas de grupo
    $('#tabla-historial tbody').on('click', 'tr.fila-grupo', function () {
        const row  = table.row(this);
        const chev = $(this).find('.grupo-chevron')[0];
        const gidx = $(this).data('gidx');

        if (row.child.isShown()) {
            row.child.hide();
            $(this).removeClass('shown');
            chev.style.transform = '';
        } else {
            const childDom = $(grupoChildren[gidx]);
            row.child(childDom).show();
            alinearSubtabla(row.child()[0]);
            $(this).addClass('shown');
            chev.style.transform = 'rotate(90deg)';
        }
    });
});
</script>
@endpush

@endsection
