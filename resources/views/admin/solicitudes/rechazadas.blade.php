@extends('layouts.app')

@section('title', 'Solicitudes Rechazadas')

@section('content')

<div class="mb-4 flex items-center gap-3 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Solicitudes Rechazadas</h1>
        <p class="text-sm text-gray-500 mt-1">Historial de solicitudes que fueron rechazadas</p>
    </div>
    <a href="{{ route('admin.solicitudes') }}" class="text-sm text-indigo-600 hover:underline font-medium ml-auto">
        ← Volver a pendientes
    </a>
</div>

<div class="mb-3 flex items-center gap-2">
    <button type="button" id="btn-filtros-rech" title="Filtrar"
        class="relative flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border rounded-lg shadow-sm transition bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600"
        style="white-space:nowrap;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M11 16h2"/>
        </svg>
        Filtros
        <span id="badge-rech" class="hidden absolute -top-1.5 -right-1.5 w-2.5 h-2.5 bg-indigo-600 rounded-full border-2 border-white"></span>
    </button>

    <input id="buscador-rechazadas" type="text" placeholder="🔍  Buscar solicitud rechazada..."
        class="flex-1 px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

{{-- Panel de filtros (flujo normal, todos los acordeones cerrados por defecto) --}}
<div id="panel-filtros-rech" class="hidden mb-4">
    <div class="bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">

        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50">
            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Filtros</span>
            <button type="button" id="btn-limpiar-rech" class="text-xs text-indigo-600 hover:underline font-medium">
                Limpiar todo
            </button>
        </div>

        <div class="grid grid-cols-2 gap-0 divide-x divide-gray-100 md:grid-cols-4">

            {{-- Producto (agrupado por familia) --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-rech w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1" data-target="acc-rech-productos">
                    <span>Producto</span>
                    <svg class="acc-rech-chevron w-3.5 h-3.5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="acc-rech-productos" class="hidden space-y-2 max-h-56 overflow-y-auto pr-1 mt-1">
                    @foreach($productosAgrupados as $familia => $productos)
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wide px-1 mb-0.5">{{ $familia }}</p>
                        @foreach($productos as $p)
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                            <input type="checkbox" class="fil-rech-producto w-3.5 h-3.5 accent-indigo-600 shrink-0"
                                   value="{{ $p->id }}">
                            <span class="text-xs text-gray-700 leading-tight">{{ $p->descripcion }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Tipo --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-rech w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1" data-target="acc-rech-tipo">
                    <span>Tipo</span>
                    <svg class="acc-rech-chevron w-3.5 h-3.5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="acc-rech-tipo" class="hidden space-y-1 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-rech-tipo w-3.5 h-3.5 accent-indigo-600 shrink-0" value="entrada">
                        <span class="inline-flex items-center bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">↑ Entrada</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-rech-tipo w-3.5 h-3.5 accent-indigo-600 shrink-0" value="salida">
                        <span class="inline-flex items-center bg-orange-100 text-orange-700 text-xs font-semibold px-2 py-0.5 rounded-full">↓ Salida</span>
                    </label>
                </div>
            </div>

            {{-- Solicitante --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-rech w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1" data-target="acc-rech-solicitante">
                    <span>Solicitante</span>
                    <svg class="acc-rech-chevron w-3.5 h-3.5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="acc-rech-solicitante" class="hidden space-y-1 max-h-56 overflow-y-auto pr-1 mt-1">
                    @foreach($fSolicitantes as $s)
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-rech-solicitante w-3.5 h-3.5 accent-indigo-600 shrink-0"
                               value="{{ strtolower($s) }}">
                        <span class="text-xs text-gray-700 leading-tight">{{ $s }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Fecha --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-rech w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1" data-target="acc-rech-fecha">
                    <span>Fecha rechazo</span>
                    <svg class="acc-rech-chevron w-3.5 h-3.5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="acc-rech-fecha" class="hidden space-y-2 mt-1">
                    <div>
                        <label class="text-xs text-gray-400 block mb-0.5">Desde</label>
                        <input type="date" id="fil-rech-desde"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 block mb-0.5">Hasta</label>
                        <input type="date" id="fil-rech-hasta"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
            </div>

        </div>
    </div>
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
            <tr class="hover:bg-gray-50 transition" data-producto-id="{{ $s->producto_id }}">
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

    @keyframes panel-drop-rech {
        from { opacity:0; transform:translateY(-8px) scale(.97); }
        to   { opacity:1; transform:translateY(0) scale(1); }
    }
    #panel-filtros-rech:not(.hidden) {
        animation: panel-drop-rech .2s cubic-bezier(.22,.68,0,1.2) both;
    }
    @keyframes badge-pulse-rech {
        0%,100% { box-shadow:0 0 0 0 rgba(79,70,229,.6); }
        50%      { box-shadow:0 0 0 5px rgba(79,70,229,0); }
    }
    #badge-rech:not(.hidden) {
        animation: badge-pulse-rech 1.8s ease-in-out infinite;
    }
    @keyframes acc-rech-open {
        from { opacity:0; transform:translateY(-4px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .acc-rech-body-open {
        animation: acc-rech-open .18s ease both;
    }

    /* ── Label activo cuando su checkbox está marcado ── */
    label:has(.fil-rech-producto:checked),
    label:has(.fil-rech-solicitante:checked) {
        background: #eef2ff !important;
        outline: 1px solid #c7d2fe;
        border-radius: 0.375rem;
    }
    label:has(.fil-rech-producto:checked) span,
    label:has(.fil-rech-solicitante:checked) span {
        color: #4338ca !important;
        font-weight: 600;
    }
    /* Entrada seleccionada */
    label:has(.fil-rech-tipo[value="entrada"]:checked) {
        background: #f0fdf4 !important;
        outline: 1px solid #bbf7d0;
    }
    /* Salida seleccionada */
    label:has(.fil-rech-tipo[value="salida"]:checked) {
        background: #fff7ed !important;
        outline: 1px solid #fed7aa;
    }

    /* ── Cabecera de acordeón: abierta o con filtros activos ── */
    .acc-rech {
        transition: background .15s, color .15s;
        border-radius: 0.375rem;
        padding: 0.25rem 0.375rem;
        margin: -0.25rem -0.375rem;
    }
    .acc-rech.is-open,
    .acc-rech.has-active {
        background: #eef2ff;
        color: #4338ca;
    }
    .acc-rech.is-open .acc-rech-chevron,
    .acc-rech.has-active .acc-rech-chevron {
        color: #4338ca;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {

        // ── Sets de filtros activos ───────────────────────────────────────
        var filProductoIds  = new Set();   // IDs de productos
        var filTipos        = new Set();   // 'entrada' | 'salida'
        var filSolicitantes = new Set();   // nombres en minúscula
        var filDesde = null, filHasta = null;

        // ── DataTable ────────────────────────────────────────────────────
        var table = $('#tabla-rechazadas').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
            order: [],
            paging: false,
            layout: {
                topStart: 'buttons',
                topEnd: null,
                bottomStart: null,
                bottomEnd: null
            },
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel', exportOptions: { columns: ':not(:last-child)' } },
                { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn',       exportOptions: { columns: ':not(:last-child)' } },
                { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf',   exportOptions: { columns: ':not(:last-child)' }, orientation: 'landscape', pageSize: 'A4' },
            ],
        });

        // ── Buscador de texto ────────────────────────────────────────────
        $('#buscador-rechazadas').on('input', function() {
            table.search(this.value).draw();
        });

        // ── Custom search ─────────────────────────────────────────────────
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'tabla-rechazadas') return true;

            // Producto (por ID en data attribute del <tr>)
            if (filProductoIds.size) {
                var tr  = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
                var pid = tr ? parseInt(tr.getAttribute('data-producto-id'), 10) : null;
                if (!pid || !filProductoIds.has(pid)) return false;
            }

            // Tipo (col 2 contiene "Entrada" o "Salida")
            if (filTipos.size) {
                var tipo = (data[2] || '').toLowerCase();
                var match = false;
                filTipos.forEach(function(t) { if (tipo.indexOf(t) !== -1) match = true; });
                if (!match) return false;
            }

            // Solicitante (col 6)
            if (filSolicitantes.size) {
                var sol = (data[6] || '').toLowerCase().trim();
                if (!filSolicitantes.has(sol)) return false;
            }

            // Fecha rechazo (col 8: "dd/mm/yyyy HH:mm")
            if (filDesde !== null || filHasta !== null) {
                var raw   = data[8] || '';
                var parts = raw.match(/(\d{2})\/(\d{2})\/(\d{4})/);
                if (!parts) return false;
                var rowMs = new Date(parts[3], parts[2] - 1, parts[1]).getTime();
                if (filDesde !== null && rowMs < filDesde) return false;
                if (filHasta !== null && rowMs > filHasta) return false;
            }

            return true;
        });

        function redibujar() {
            table.draw();
            var hay = filProductoIds.size || filTipos.size || filSolicitantes.size
                   || filDesde !== null || filHasta !== null;
            $('#badge-rech').toggleClass('hidden', !hay);

            // Resaltar encabezados de acordeón con filtros activos
            $('[data-target="acc-rech-productos"]').toggleClass('has-active', filProductoIds.size > 0);
            $('[data-target="acc-rech-tipo"]').toggleClass('has-active', filTipos.size > 0);
            $('[data-target="acc-rech-solicitante"]').toggleClass('has-active', filSolicitantes.size > 0);
            $('[data-target="acc-rech-fecha"]').toggleClass('has-active', filDesde !== null || filHasta !== null);
        }

        // ── Checkboxes producto ───────────────────────────────────────────
        $(document).on('change', '.fil-rech-producto', function() {
            var id = parseInt(this.value, 10);
            this.checked ? filProductoIds.add(id) : filProductoIds.delete(id);
            redibujar();
        });

        // ── Checkboxes tipo ───────────────────────────────────────────────
        $(document).on('change', '.fil-rech-tipo', function() {
            this.checked ? filTipos.add(this.value) : filTipos.delete(this.value);
            redibujar();
        });

        // ── Checkboxes solicitante ────────────────────────────────────────
        $(document).on('change', '.fil-rech-solicitante', function() {
            this.checked ? filSolicitantes.add(this.value) : filSolicitantes.delete(this.value);
            redibujar();
        });

        // ── Fecha ─────────────────────────────────────────────────────────
        $('#fil-rech-desde').on('change', function() {
            filDesde = this.value ? new Date(this.value + 'T00:00:00').getTime() : null;
            redibujar();
        });
        $('#fil-rech-hasta').on('change', function() {
            filHasta = this.value ? new Date(this.value + 'T23:59:59').getTime() : null;
            redibujar();
        });

        // ── Acordeón (todos cerrados por defecto) ─────────────────────────
        $('.acc-rech').on('click', function() {
            var bodyId  = $(this).data('target');
            var $body   = $('#' + bodyId);
            var opening = $body.hasClass('hidden');

            $body.toggleClass('hidden', !opening);
            if (opening) $body.addClass('acc-rech-body-open');
            $(this).find('.acc-rech-chevron').css('transform', opening ? 'rotate(180deg)' : '');
            $(this).toggleClass('is-open', opening);
        });

        // ── Toggle panel ──────────────────────────────────────────────────
        $('#btn-filtros-rech').on('click', function(e) {
            e.stopPropagation();
            $('#panel-filtros-rech').toggleClass('hidden');
        });
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#panel-filtros-rech, #btn-filtros-rech').length) {
                $('#panel-filtros-rech').addClass('hidden');
            }
        });

        // ── Limpiar todo ──────────────────────────────────────────────────
        $('#btn-limpiar-rech').on('click', function() {
            filProductoIds.clear(); filTipos.clear(); filSolicitantes.clear();
            filDesde = null; filHasta = null;
            $('.fil-rech-producto, .fil-rech-tipo, .fil-rech-solicitante').prop('checked', false);
            $('#fil-rech-desde, #fil-rech-hasta').val('');
            $('#buscador-rechazadas').val('');
            table.search('').draw();
            redibujar();
        });

    });
</script>
@endpush

@endsection
