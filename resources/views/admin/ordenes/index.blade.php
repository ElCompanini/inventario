@extends('layouts.app')

@section('title', 'Órdenes de Compra')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Órdenes de Compra</h1>
        <p class="text-sm text-gray-500 mt-1">Agrupación de SICDs y recepción de mercadería</p>
    </div>
    <div class="flex items-center gap-3">
        <span id="mp-api-badge" class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-gray-400">● API MP…</span>
        <a href="{{ route('admin.ordenes.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva OC
        </a>
    </div>
</div>

<div class="mb-4">
    <input id="buscador-ordenes" type="text" placeholder="🔍  Buscar orden de compra..."
           class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

<div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden p-4">
    <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
    <table id="tabla-ordenes" class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">N° OC</th>
                <th class="px-4 py-3 font-semibold text-gray-600">SICDs</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Mercado Público</th>
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
                <tr id="oc-row-{{ $oc->id }}" data-id="{{ $oc->id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono font-semibold text-indigo-700">{{ $oc->numero_oc }}</td>
                    <td class="px-4 py-2 text-gray-600">
                        <div class="text-xs font-medium text-gray-600 mb-1">{{ $oc->sicds->count() }} SICD(s)</div>
                        @foreach($oc->sicds as $s)
                            @php
                                $en = $estadosExternos[$s->codigo_sicd] ?? null;
                                $et = \App\Models\SicdExterno::etiquetaEstado($en);
                            @endphp
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="font-mono text-xs text-indigo-600">{{ $s->codigo_sicd }}</span>
                                <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full whitespace-nowrap"
                                      style="background:{{ $et['bg'] }}; color:{{ $et['color'] }};">
                                    {{ $et['texto'] }}
                                </span>
                            </div>
                        @endforeach
                    </td>
                    <td class="px-4 py-2" id="estado-cell-{{ $oc->id }}">
                        @if($oc->estado === 'recibido')
                            <span class="inline-flex items-center bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">✓ Recibido</span>
                        @elseif($oc->estado === 'validado')
                            <span class="inline-flex items-center bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">✓ Validado</span>
                        @else
                            <a href="{{ route('admin.ordenes.show', $oc->id) }}"
                               class="inline-flex items-center bg-yellow-100 text-yellow-700 text-xs font-semibold px-2.5 py-1 rounded-full hover:bg-yellow-200 transition">⏳ Pendiente</a>
                        @endif
                    </td>
                    <td class="px-4 py-2" id="mp-cell-{{ $oc->id }}">
                        @if($oc->api_validado_at)
                            <div class="text-xs text-green-700 font-medium">✓ {{ Str::limit($oc->api_proveedor_nombre ?? 'Validado', 28) }}</div>
                            <div class="text-xs text-gray-400">{{ $oc->api_validado_at->format('d/m/Y H:i') }}</div>
                        @elseif($oc->api_error)
                            <div class="text-xs text-red-600 font-medium" title="{{ $oc->api_error }}">✗ Error ({{ $oc->api_intentos }} int.)</div>
                            @if($oc->estado !== 'recibido')
                                <button onclick="validarMP({{ $oc->id }})"
                                        id="btn-validar-{{ $oc->id }}"
                                        class="text-xs text-indigo-600 hover:underline mt-0.5">Reintentar</button>
                            @endif
                        @else
                            @if($oc->estado !== 'recibido')
                                <button onclick="validarMP({{ $oc->id }})"
                                        id="btn-validar-{{ $oc->id }}"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1 rounded-lg transition">
                                    Validar MP
                                </button>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
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

{{-- Toast MP --}}
<div id="mp-toast" style="display:none; position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
     max-width:380px; padding:0.875rem 1.125rem; border-radius:0.75rem;
     box-shadow:0 4px 20px rgba(0,0,0,.18); font-size:0.8125rem; font-weight:500; color:#fff;"></div>

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
const URL_API_STATUS  = '{{ route("admin.ordenes.api-status") }}';
const URL_ORDENES_BASE = '{{ url("/admin/ordenes") }}';
const CSRF_TOKEN       = '{{ csrf_token() }}';

$(document).ready(function () {
    const table = $('#tabla-ordenes').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
        order: [[7, 'desc']],
        paging: false,
        layout: { topStart: 'buttons', topEnd: null, bottomStart: null, bottomEnd: null },
        buttons: [
            { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel' },
            { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn' },
            { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf' },
        ],
        columnDefs: [{ orderable: false, targets: [3, -1] }],
    });
    $('#buscador-ordenes').on('input', function () { table.search(this.value).draw(); });

    // Verificar estado API MP
    verificarEstadoApi();
});

function verificarEstadoApi() {
    fetch(URL_API_STATUS, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('mp-api-badge');
            if (data.activa) {
                badge.className = 'text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-700';
                badge.textContent = '● API MP activa';
            } else {
                badge.className = 'text-xs font-medium px-2 py-1 rounded-full bg-red-100 text-red-600';
                badge.textContent = '● API MP inactiva';
            }
        })
        .catch(() => {
            const badge = document.getElementById('mp-api-badge');
            badge.className = 'text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-gray-400';
            badge.textContent = '● API MP sin verificar';
        });
}

function validarMP(ocId) {
    const btn = document.getElementById('btn-validar-' + ocId);
    if (btn) { btn.disabled = true; btn.textContent = 'Validando…'; }

    fetch(URL_ORDENES_BASE + '/' + ocId + '/validar-mp', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        actualizarFilaMP(ocId, data.fila);
        mostrarToast(data.mensaje, ok && data.ok ? 'green' : 'red');
    })
    .catch(() => {
        mostrarToast('Error de conexión al validar la OC.', 'red');
        if (btn) { btn.disabled = false; btn.textContent = 'Validar MP'; }
    });
}

function actualizarFilaMP(ocId, fila) {
    if (!fila) return;

    const estadoCell = document.getElementById('estado-cell-' + ocId);
    if (estadoCell) {
        if (fila.estado === 'validado') {
            estadoCell.innerHTML = '<span class="inline-flex items-center bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">✓ Validado</span>';
        } else if (fila.estado === 'pendiente') {
            estadoCell.innerHTML = '<span class="inline-flex items-center bg-yellow-100 text-yellow-700 text-xs font-semibold px-2.5 py-1 rounded-full">⏳ Pendiente</span>';
        }
    }

    const mpCell = document.getElementById('mp-cell-' + ocId);
    if (mpCell) {
        if (fila.api_validado_at) {
            const prov = fila.api_proveedor_nombre ? esc(fila.api_proveedor_nombre).substring(0, 30) : 'Validado';
            mpCell.innerHTML =
                '<div class="text-xs text-green-700 font-medium">✓ ' + prov + '</div>' +
                '<div class="text-xs text-gray-400">' + esc(fila.api_validado_at) + '</div>';
        } else if (fila.api_error) {
            mpCell.innerHTML =
                '<div class="text-xs text-red-600 font-medium" title="' + esc(fila.api_error) + '">✗ Error (' + fila.api_intentos + ' int.)</div>' +
                '<button onclick="validarMP(' + ocId + ')" id="btn-validar-' + ocId + '" class="text-xs text-indigo-600 hover:underline mt-0.5">Reintentar</button>';
        }
    }
}

function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

let _toastTimer = null;
function mostrarToast(mensaje, tipo) {
    const t = document.getElementById('mp-toast');
    t.style.background = tipo === 'green' ? '#16a34a' : '#dc2626';
    t.textContent = mensaje;
    t.style.display = 'block';
    if (_toastTimer) clearTimeout(_toastTimer);
    _toastTimer = setTimeout(() => { t.style.display = 'none'; }, 5000);
}
</script>
@endpush

@endsection
