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

@if($sicds->isEmpty())
<div class="bg-white dark:bg-slate-800 rounded-xl shadow border border-gray-100 dark:border-slate-700
            flex flex-col items-center justify-center text-center gap-5 mb-6"
     style="min-height:340px; padding:3rem 2rem;">
    <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
    </svg>
    <div class="max-w-sm">
        <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">No hay SICDs registradas</p>
        <p class="text-sm text-gray-400 dark:text-slate-500 mt-2 leading-relaxed">
            Aún no se han creado Solicitudes de Información de Compra Directa.
        </p>
    </div>
</div>
@endif

<div class="bg-white rounded-xl shadow overflow-hidden p-4" @if($sicds->isEmpty()) style="display:none" @endif>
    <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
    <table id="tabla-sicds" class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">Código SICD</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Productos</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Est. Externo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">OC</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Creado por</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($sicds as $sicd)
                @php $ocs = $sicd->ordenesCompra; @endphp
                <tr>
                    <td class="px-4 py-3 font-mono font-semibold text-indigo-700">{{ $sicd->codigo_sicd }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $sicd->detalles_count }} producto(s)</td>
                    <td class="px-4 py-3">
                        @if($sicd->estado === 'recibido')
                            <span class="inline-flex items-center bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">✓ Recibido</span>
                        @elseif($sicd->estado === 'agrupado')
                            <span class="inline-flex items-center bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">📎 Agrupado</span>
                        @else
                            <span class="inline-flex items-center bg-yellow-100 text-yellow-700 text-xs font-semibold px-2.5 py-1 rounded-full">⏳ Pendiente</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="sicd-est-externo inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                              data-codigo="{{ $sicd->codigo_sicd }}"
                              style="background:#f3f4f6; color:#9ca3af;">
                            …
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        @if($ocs->isNotEmpty())
                            <div class="flex flex-col gap-0.5">
                                @foreach($ocs as $oc)
                                    <a href="{{ route('admin.ordenes.show', $oc->id) }}"
                                       class="text-indigo-600 hover:underline font-mono text-xs whitespace-nowrap">
                                        {{ $oc->numero_oc }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $sicd->usuario->name }}</td>
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $sicd->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            @if($sicd->boleta)
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

{{-- Paginación --}}
@if($sicds->hasPages())
<div class="mt-4 flex justify-center gap-1">
    @foreach($sicds->links()->offsetGet('elements') ?? [] as $element)
        @if(is_string($element))
            <span style="padding:0.35rem 0.6rem; font-size:0.78rem; color:#9ca3af;">{{ $element }}</span>
        @elseif(is_array($element))
            @foreach($element as $page => $url)
                @if($page == $sicds->currentPage())
                    <span style="padding:0.35rem 0.75rem; font-size:0.78rem; font-weight:700; background:#2563eb; color:#fff; border-radius:0.4rem;">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" style="padding:0.35rem 0.75rem; font-size:0.78rem; font-weight:600; background:#eff6ff; color:#2563eb; border-radius:0.4rem; text-decoration:none;"
                       onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach
</div>
@endif

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

    // Cargar estados externos de forma asíncrona para no bloquear el render
    (function () {
        var badges = document.querySelectorAll('.sicd-est-externo');
        if (!badges.length) return;

        function isDark() {
            return document.documentElement.classList.contains('dark');
        }

        function aplicarColores(badgesData) {
            badges.forEach(function (el) {
                var est = badgesData[el.dataset.codigo];
                if (est && est.texto) {
                    el.textContent      = est.texto;
                    el.style.background = isDark() ? (est.dark_bg    || '#374151') : est.bg;
                    el.style.color      = isDark() ? (est.dark_color || '#9ca3af') : est.color;
                } else {
                    el.textContent      = '—';
                    el.style.background = isDark() ? '#374151' : '#f3f4f6';
                    el.style.color      = isDark() ? '#9ca3af' : '#6b7280';
                }
            });
        }

        var codigos = Array.from(badges).map(function (el) { return el.dataset.codigo; });
        var params  = codigos.map(function (c) { return 'codigos[]=' + encodeURIComponent(c); }).join('&');
        var badgeCache = {};

        fetch('{{ route("admin.sicd.estados-externos") }}?' + params)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                badgeCache = data;
                aplicarColores(data);

                // Re-aplicar colores si el usuario cambia de modo claro/oscuro
                var observer = new MutationObserver(function () {
                    aplicarColores(badgeCache);
                });
                observer.observe(document.documentElement, { attributeFilter: ['class'] });
            })
            .catch(function () {
                badges.forEach(function (el) { el.textContent = '—'; });
            });
    })();
</script>
@endpush

@endsection
