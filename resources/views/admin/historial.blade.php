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

@if($historial->isEmpty())
<div class="bg-white dark:bg-slate-800 rounded-xl shadow border border-gray-100 dark:border-slate-700
            flex flex-col items-center justify-center text-center gap-5 mb-6"
     style="min-height:340px; padding:3rem 2rem;">
    <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div class="max-w-sm">
        <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">Sin movimientos registrados</p>
        <p class="text-sm text-gray-400 dark:text-slate-500 mt-2 leading-relaxed">
            Aún no hay movimientos de stock en el historial de cambios.
        </p>
    </div>
</div>
@endif

<div class="bg-white rounded-xl shadow overflow-hidden p-4" @if($historial->isEmpty()) style="display:none" @endif>
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
                <th class="px-4 py-3 font-semibold text-gray-600">Referencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historial as $registro)
            <tr class="{{ $registro->tipo === 'traslado' ? 'bg-blue-50 hover:bg-blue-100' : 'hover:bg-gray-50' }} transition">
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                    {{ $registro->created_at->format('d/m/Y H:i') }}
                </td>
                <td class="px-4 py-3" style="max-width:200px;">
                    @php
                        $nombreP    = $registro->producto?->nombre ?? $registro->nombre_producto ?? '—';
                        $esServicio = (bool) ($registro->producto?->es_servicio);
                    @endphp
                    <p class="font-medium text-gray-900 truncate" title="{{ $nombreP }}">{{ $nombreP }}</p>
                    @if($esServicio)
                    <span class="badge-servicio">SERVICIO</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @php
                        $cNombre = $registro->container?->nombre ?? ($registro->contenedor_id ? 'C'.$registro->contenedor_id : '—');
                    @endphp
                    <span class="inline-block text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $cNombre }}</span>
                </td>
                <td class="px-4 py-3">
                    @php
                        [$tipoLabel, $tipoStyle] = match($registro->tipo) {
                            'entrada'    => ['Entrada',    'bg-green-100 text-green-700'],
                            'devolucion' => ['Devolución', 'bg-green-100 text-green-700'],
                            'salida'     => ['Salida',     'bg-orange-100 text-orange-700'],
                            'retiro'     => ['Retiro',     'bg-orange-100 text-orange-700'],
                            'merma'      => ['Merma',      'bg-red-100 text-red-700'],
                            'traslado'   => ['Traslado',   'bg-blue-100 text-blue-700'],
                            'ajuste'     => ['Ajuste',     'bg-gray-100 text-gray-600'],
                            default      => [ucfirst($registro->tipo), 'bg-gray-100 text-gray-600'],
                        };
                        $esPositivo = in_array($registro->tipo, ['entrada', 'devolucion']);
                        $esNeutro   = $registro->tipo === 'traslado';
                    @endphp
                    <span class="inline-flex items-center gap-1 {{ $tipoStyle }} text-xs font-semibold px-2.5 py-1 rounded-full">
                        @if($registro->tipo === 'entrada')
                        <svg style="width:10px;height:10px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        @elseif($registro->tipo === 'salida' || $registro->tipo === 'retiro')
                        <svg style="width:10px;height:10px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        @elseif($registro->tipo === 'devolucion')
                        <svg style="width:10px;height:10px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                        </svg>
                        @endif
                        {{ $tipoLabel }}
                    </span>
                </td>
                <td class="px-4 py-3 font-bold text-gray-800">
                    @if($esNeutro)
                    <span class="text-blue-600">{{ $registro->cantidad }}</span>
                    @elseif($esPositivo)
                    <span class="text-green-600">+{{ $registro->cantidad }}</span>
                    @else
                    <span class="text-red-600">−{{ $registro->cantidad }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600 max-w-xs whitespace-normal break-words">
                    {{ $registro->motivo }}
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $registro->usuario->name }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $registro->aprobado_por ?? '—' }}</td>
                @php
                    $histRef    = null;
                    $origenTipo = $registro->origen_tipo ?: null;

                    if (!empty($registro->referencia_tipo) && !empty($registro->referencia_id)) {
                        $histRef = match($registro->referencia_tipo) {
                            'solicitud' => 'SOL-' . str_pad($registro->referencia_id, 6, '0', STR_PAD_LEFT),
                            default     => $registro->doc_referencia,
                        };
                    } elseif (!empty($registro->doc_referencia)) {
                        $histRef = $registro->doc_referencia;
                    }
                @endphp
                <td class="px-4 py-3">
                    {{-- 1. SOLICITUD --}}
                    @if($origenTipo === 'solicitud' || ($origenTipo === null && $registro->origen === 'solicitud' && $registro->origen_id && $registro->tipo !== 'devolucion'))
                    @php $solCode = $registro->doc_origen ?: 'SOL-' . str_pad($registro->origen_id, 6, '0', STR_PAD_LEFT); @endphp
                    <span class="inline-flex items-center bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $solCode }}</span>

                    {{-- 2. DEVOLUCIÓN --}}
                    @elseif($origenTipo === 'devolucion' || ($origenTipo === null && $registro->tipo === 'devolucion' && $registro->origen === 'solicitud' && $registro->origen_id))
                    @php
                        $devCode = $registro->doc_origen ?: ('DEV-' . str_pad($registro->id, 6, '0', STR_PAD_LEFT));
                        if (empty($histRef) && $registro->origen_id) {
                            $histRef = 'SOL-' . str_pad($registro->origen_id, 6, '0', STR_PAD_LEFT);
                        }
                    @endphp
                    <span class="inline-flex items-center bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $devCode }}</span>

                    {{-- 3. RETIRO DIRECTO --}}
                    @elseif($origenTipo === 'retiro_directo')
                    @php $retCode = $registro->doc_origen ?: ('RET-' . str_pad($registro->id, 6, '0', STR_PAD_LEFT)); @endphp
                    <span class="inline-flex items-center bg-red-100 text-red-600 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $retCode }}</span>

                    {{-- 4. SICD / ORDEN COMPRA --}}
                    @elseif($registro->origen === 'sicd' || $origenTipo === 'sicd' || $origenTipo === 'orden_compra')
                    @php
                        $sicdCodigo = $registro->sicd?->codigo_sicd;
                        if (!$sicdCodigo && $registro->motivo && str_contains($registro->motivo, 'SICD ')) {
                            $after = trim(substr($registro->motivo, strpos($registro->motivo, 'SICD ') + 5));
                            $pos   = strpos($after, ' (');
                            $sicdCodigo = $pos !== false ? rtrim(substr($after, 0, $pos)) : $after;
                        }
                        $sicdLabel = $sicdCodigo ? 'SICD ' . $sicdCodigo : ('SICD #' . $registro->origen_id);
                        $ocNumero  = $registro->ordenCompra?->numero_oc;
                        if (empty($histRef)) $histRef = $ocNumero ? $sicdLabel : null;
                    @endphp
                    @if($ocNumero)
                    <span class="inline-flex items-center bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">OC {{ $ocNumero }}</span>
                    @elseif(auth()->user()->tienePermiso('sicd'))
                    <a href="{{ route('admin.sicd.show', $registro->origen_id) }}"
                       class="inline-flex items-center bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full hover:bg-indigo-200 transition font-mono">
                        {{ $sicdLabel }}
                    </a>
                    @else
                    <span class="inline-flex items-center bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $sicdLabel }}</span>
                    @endif
                    @if($registro->sicd?->boleta)
                    <a href="{{ route('admin.sicd.descargar', $registro->origen_id) }}"
                       class="text-xs font-medium text-gray-400 hover:text-gray-600 transition" style="display:block; margin-top:2px; font-size:.68rem;">
                        Ver boleta
                    </a>
                    @endif

                    {{-- 5. GASTO MENOR --}}
                    @elseif($registro->origen === 'gasto_menor' || $origenTipo === 'gasto_menor')
                    @php $gmNum = $registro->gastoMenor?->id_gm ?? null; @endphp
                    <a href="{{ route('admin.gastos-menores.index') }}{{ $gmNum ? '?gm=' . $gmNum : '' }}"
                       class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full font-mono transition"
                       style="background:#fef3c7; color:#b45309;"
                       onmouseover="this.style.background='#fde68a'"
                       onmouseout="this.style.background='#fef3c7'">
                        {{ $gmNum ? 'GM-' . str_pad($gmNum, 4, '0', STR_PAD_LEFT) : 'Gasto Menor' }}
                    </a>

                    {{-- 6. COMPUTADOR ARMADO --}}
                    @elseif($registro->origen === 'computador_armado' || $origenTipo === 'computador_armado')
                    @php $compCode = $registro->doc_origen ?: ('ARM-' . str_pad($registro->origen_id, 6, '0', STR_PAD_LEFT)); @endphp
                    <span class="inline-flex items-center bg-cyan-100 text-cyan-700 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $compCode }}</span>

                    {{-- 7. AJUSTE / ENTRADA MANUAL / MERMA --}}
                    @elseif(in_array($origenTipo, ['ajuste', 'entrada_manual', 'merma']) || (in_array($registro->tipo, ['ajuste', 'entrada']) && !$registro->origen))
                    @php $ajuCode = $registro->doc_origen ?: ('AJU-' . str_pad($registro->id, 6, '0', STR_PAD_LEFT)); @endphp
                    <span class="inline-flex items-center bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $ajuCode }}</span>

                    {{-- 8. TRASLADO --}}
                    @elseif($origenTipo === 'traslado' || $registro->tipo === 'traslado')
                    @php $movCode = $registro->doc_origen ?: ('MOV-' . str_pad($registro->id, 6, '0', STR_PAD_LEFT)); @endphp
                    <span class="inline-flex items-center bg-blue-50 text-blue-600 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $movCode }}</span>

                    {{-- 9. SALIDA / RETIRO SIN ORIGEN (fallback legacy) --}}
                    @elseif(in_array($registro->tipo, ['salida', 'retiro', 'merma']) || ($registro->origen === 'solicitud' && !$registro->origen_id))
                    @php $retCode = $registro->doc_origen ?: ('RET-' . str_pad($registro->id, 6, '0', STR_PAD_LEFT)); @endphp
                    <span class="inline-flex items-center bg-red-100 text-red-600 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $retCode }}</span>

                    @else
                    <span class="text-gray-400 text-xs">—</span>
                    @endif
                </td>
                @php
                    if (empty($histRef) && !empty($registro->doc_referencia)) {
                        $histRef = $registro->doc_referencia;
                    }
                @endphp
                <td class="px-4 py-3">
                    @if($histRef)
                    @if(($registro->origen === 'sicd' || $origenTipo === 'sicd') && auth()->user()->tienePermiso('sicd'))
                    <a href="{{ route('admin.sicd.show', $registro->origen_id) }}"
                       class="inline-flex items-center bg-indigo-50 text-indigo-500 text-xs font-semibold px-2 py-0.5 rounded-full hover:bg-indigo-100 transition font-mono">
                        {{ $histRef }}
                    </a>
                    @else
                    <span class="inline-flex items-center bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full font-mono">{{ $histRef }}</span>
                    @endif
                    @else
                    <span class="text-gray-300 text-xs">—</span>
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
    .badge-servicio { font-size:.6rem; font-weight:700; letter-spacing:.04em; background:#ede9fe; color:#5b21b6; padding:1px 6px; border-radius:9999px; display:inline-block; margin-top:2px; }
    html.dark .badge-servicio { background:rgba(139,92,246,0.2); color:#c4b5fd; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#tabla-historial').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
        order: [[]],
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
});
</script>
@endpush

@endsection
