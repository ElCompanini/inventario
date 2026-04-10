@extends('layouts.app')

@section('title', 'Solicitudes Pendientes')

@section('content')

@php
    $fProductos    = $solicitudes->pluck('producto.nombre')->unique()->sort()->values();
    $fSolicitantes = $solicitudes->pluck('usuario.name')->unique()->sort()->values();
@endphp

<div class="mb-4 flex items-center gap-3 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Solicitudes Pendientes</h1>
        <p class="text-sm text-gray-500 mt-1" id="contador-sol">
            {{ $solicitudes->count() }} solicitud(es) esperando revisión
        </p>
    </div>

    @if($solicitudes->isNotEmpty())
    <div class="ml-auto flex items-center gap-2">
        {{-- Botón filtros --}}
        <button type="button" id="btn-filtros-sol"
            class="relative flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border rounded-lg shadow-sm transition bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M11 16h2"/>
            </svg>
            Filtros
            <span id="badge-sol" class="hidden absolute -top-1.5 -right-1.5 w-2.5 h-2.5 bg-indigo-600 rounded-full border-2 border-white"></span>
        </button>

        {{-- Buscador --}}
        <input type="text" id="buscador-solicitudes" placeholder="🔍  Buscar..."
               class="px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm bg-white
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-56">
    </div>
    @endif
</div>

{{-- Panel de filtros (flujo normal, debajo del header) --}}
@if($solicitudes->isNotEmpty())
<div id="panel-filtros-sol" class="hidden mb-4">
    <div class="bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">

        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50">
            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Filtros</span>
            <button type="button" id="btn-limpiar-filtros-sol" class="text-xs text-indigo-600 hover:underline font-medium">
                Limpiar todo
            </button>
        </div>

        <div class="grid grid-cols-2 gap-0 divide-x divide-gray-100 md:grid-cols-4">

            {{-- Productos --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-header w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2" data-target="acc-productos">
                    <span>Producto</span>
                    <svg class="acc-chevron w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="acc-productos" class="acc-body space-y-1 max-h-44 overflow-y-auto pr-1">
                    @foreach($fProductos as $p)
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-producto w-3.5 h-3.5 accent-indigo-600 shrink-0" value="{{ strtolower($p) }}">
                        <span class="text-xs text-gray-700 leading-tight">{{ $p }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Solicitante --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-header w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2" data-target="acc-solicitante">
                    <span>Solicitante</span>
                    <svg class="acc-chevron w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="acc-solicitante" class="acc-body space-y-1 max-h-44 overflow-y-auto pr-1">
                    @foreach($fSolicitantes as $s)
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-solicitante w-3.5 h-3.5 accent-indigo-600 shrink-0" value="{{ strtolower($s) }}">
                        <span class="text-xs text-gray-700 leading-tight">{{ $s }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Contenedor --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-header w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2" data-target="acc-contenedor">
                    <span>Contenedor</span>
                    <svg class="acc-chevron w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="acc-contenedor" class="acc-body space-y-1 max-h-44 overflow-y-auto pr-1">
                    @foreach($containers as $c)
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-contenedor w-3.5 h-3.5 accent-indigo-600 shrink-0" value="{{ strtolower($c->nombre) }}">
                        <span class="text-xs text-gray-700 leading-tight">{{ $c->nombre }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Fecha --}}
            <div class="px-4 py-3">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Fecha</p>
                <div class="space-y-2">
                    <div>
                        <label class="text-xs text-gray-400 block mb-0.5">Desde</label>
                        <input type="date" id="fil-fecha-desde"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 block mb-0.5">Hasta</label>
                        <input type="date" id="fil-fecha-hasta"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endif

@if($solicitudes->isEmpty())
    <div class="bg-white rounded-xl shadow p-12 text-center">
        <div class="text-5xl mb-4">✅</div>
        <p class="text-gray-500 font-medium">No hay solicitudes pendientes.</p>
    </div>
@else
    <div class="space-y-4" id="lista-solicitudes">
        @foreach($solicitudes as $solicitud)
            @php
                $esEntrada = $solicitud->tipo === 'entrada';
                $stockActual = $solicitud->producto->stock_actual;
                $stockTras = $esEntrada
                    ? $stockActual + $solicitud->cantidad
                    : $stockActual - $solicitud->cantidad;
                $stockInsuficiente = !$esEntrada && $stockActual < $solicitud->cantidad;
            @endphp

            <div class="bg-white rounded-xl shadow overflow-hidden border-l-4 sol-card
                {{ $esEntrada ? 'border-green-500' : 'border-orange-500' }}"
                 data-buscar="{{ strtolower($solicitud->producto->nombre . ' ' . $solicitud->motivo . ' ' . $solicitud->usuario->name . ' ' . ($solicitud->producto->container->nombre ?? '')) }}"
                 data-producto="{{ strtolower($solicitud->producto->nombre) }}"
                 data-solicitante="{{ strtolower($solicitud->usuario->name) }}"
                 data-contenedor="{{ strtolower($solicitud->producto->container->nombre ?? '') }}"
                 data-fecha="{{ $solicitud->created_at->format('Y-m-d') }}">
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        {{-- Info solicitud --}}
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-lg font-bold" style="color: {{ $esEntrada ? '#15803d' : '#ea580c' }}">
                                    {{ $solicitud->producto->nombre }}
                                </span>
                                @if($esEntrada)
                                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                        ↑ ENTRADA +{{ $solicitud->cantidad }}
                                    </span>
                                @else
                                    <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                        ↓ SALIDA −{{ $solicitud->cantidad }}
                                    </span>
                                @endif
                                @if($stockInsuficiente)
                                    <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                        ⚠ Stock insuficiente
                                    </span>
                                @endif
                            </div>

                            <p class="text-base text-gray-700 mb-3">
                                <span class="font-semibold text-gray-800">Motivo:</span>
                                <span style="background:{{ $esEntrada ? '#dcfce7' : '#ffedd5' }}; color:{{ $esEntrada ? '#15803d' : '#c2410c' }}; border-radius:0.5rem; padding:2px 10px;">{{ $solicitud->motivo }}</span>
                            </p>

                            <div class="flex items-center gap-6 text-base text-gray-600">
                                <span>
                                    <span class="font-semibold text-gray-800">Solicitante:</span>
                                    {{ $solicitud->usuario->name }}
                                </span>
                                <span>
                                    <span class="font-semibold text-gray-800">Fecha:</span>
                                    {{ $solicitud->created_at->format('d/m/Y H:i') }}
                                </span>
                                <span>
                                    <span class="font-semibold text-gray-800">Contenedor:</span>
                                    {{ $solicitud->producto->container->nombre ?? '—' }}
                                </span>
                            </div>
                        </div>

                        {{-- Previsualización de stock --}}
                        <div class="flex-shrink-0 text-center bg-gray-50 rounded-xl px-5 py-3 min-w-[140px]">
                            <p class="text-xs text-gray-500 mb-1">Stock actual</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $stockActual }}</p>
                            <div class="my-1 text-gray-400 text-lg">↓</div>
                            <p class="text-xs text-gray-500 mb-1">Tras aprobar</p>
                            <p class="text-2xl font-bold {{ $stockTras < 0 ? 'text-red-600' : ($stockTras <= $solicitud->producto->stock_critico ? 'text-red-500' : ($stockTras <= $solicitud->producto->stock_minimo ? 'text-yellow-600' : 'text-green-600')) }}">
                                {{ $stockTras }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Botones aprobar / rechazar (solo admin) --}}
                <div class="px-6 py-3 bg-gray-50 border-t flex items-center gap-3">
                    @if(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'))
                        @if(!$stockInsuficiente)
                            <form method="POST" action="{{ route('admin.solicitudes.aprobar', $solicitud->id) }}">
                                @csrf
                                <button type="submit"
                                        class="btn-aprobar inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
                                               text-white text-sm font-semibold px-4 py-2 rounded-lg"
                                        onclick="return confirm('¿Aprobar esta solicitud?')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Aprobar
                                </button>
                            </form>
                        @endif

                        <button type="button"
                                onclick="abrirModalRechazo({{ $solicitud->id }}, '{{ route('admin.solicitudes.rechazar', $solicitud->id) }}')"
                                class="btn-rechazar inline-flex items-center gap-2 bg-red-600 hover:bg-red-700
                                       text-white text-sm font-semibold px-4 py-2 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Rechazar
                        </button>
                    @else
                        <span class="text-xs text-gray-400 italic">Solo lectura — sin permisos para aprobar o rechazar</span>
                    @endif

                    <span class="text-xs text-gray-400 ml-auto">#{{ $solicitud->id }}</span>
                </div>
            </div>
        @endforeach
    </div>
    <p id="sin-resultados" class="hidden text-center text-gray-400 py-10">Sin resultados para tu búsqueda.</p>
@endif

{{-- Modal de rechazo con motivo --}}
<div id="modalRechazo" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Rechazar solicitud</h2>
        <p class="text-sm text-gray-500 mb-4">Ingresa el motivo del rechazo para informar al solicitante.</p>

        <form id="formRechazo" method="POST" action="" onsubmit="return validarRechazo()">
            @csrf
            <div class="mb-4">
                <label for="motivo_rechazo" class="block text-sm font-medium text-gray-700 mb-1">
                    Motivo de rechazo <span class="text-red-500">*</span>
                </label>
                <textarea id="motivo_rechazo" name="motivo_rechazo" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                          placeholder="Ej: Stock insuficiente, solicitud duplicada, error en la cantidad..."
                          oninput="limpiarErrorRechazo()"></textarea>
                <p id="error-motivo-rechazo" class="hidden mt-1.5 text-sm font-medium text-red-600 flex items-center gap-1">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    Debes ingresar el motivo del rechazo antes de continuar.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalRechazo()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                    Confirmar rechazo
                </button>
            </div>
        </form>
    </div>
</div>

@push('head')
<style>
    /* ── Entrada de cards ── */
    @keyframes sol-fade-up {
        from { opacity:0; transform:translateY(18px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .sol-card {
        animation: sol-fade-up .35s cubic-bezier(.22,.68,0,1.15) both;
    }
    .sol-card:nth-child(1)  { animation-delay:.04s }
    .sol-card:nth-child(2)  { animation-delay:.08s }
    .sol-card:nth-child(3)  { animation-delay:.12s }
    .sol-card:nth-child(4)  { animation-delay:.16s }
    .sol-card:nth-child(5)  { animation-delay:.20s }
    .sol-card:nth-child(6)  { animation-delay:.24s }
    .sol-card:nth-child(7)  { animation-delay:.28s }
    .sol-card:nth-child(8)  { animation-delay:.32s }
    .sol-card:nth-child(9)  { animation-delay:.36s }
    .sol-card:nth-child(10) { animation-delay:.40s }

    /* ── Hover card ── */
    .sol-card { transition: box-shadow .2s, transform .2s; }
    .sol-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.10); transform: translateY(-2px); }

    /* ── Panel de filtros dropdown ── */
    @keyframes panel-drop {
        from { opacity:0; transform:translateY(-8px) scale(.97); }
        to   { opacity:1; transform:translateY(0)   scale(1); }
    }
    #panel-filtros-sol:not(.hidden) {
        animation: panel-drop .2s cubic-bezier(.22,.68,0,1.2) both;
    }

    /* ── Acordeón body ── */
    @keyframes acc-open {
        from { opacity:0; transform:translateY(-4px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .acc-body:not(.hidden) {
        animation: acc-open .18s ease both;
    }

    /* ── Badge pulso ── */
    @keyframes badge-pulse {
        0%,100% { box-shadow:0 0 0 0 rgba(79,70,229,.6); }
        50%      { box-shadow:0 0 0 5px rgba(79,70,229,0); }
    }
    #badge-sol:not(.hidden) {
        animation: badge-pulse 1.8s ease-in-out infinite;
    }

    /* ── Modal rechazo ── */
    @keyframes modal-in {
        from { opacity:0; transform:scale(.94); }
        to   { opacity:1; transform:scale(1); }
    }
    #modalRechazo > div {
        animation: modal-in .25s cubic-bezier(.22,.68,0,1.2) both;
    }

    /* ── Botones aprobar/rechazar ── */
    .btn-aprobar  { transition: background .15s, transform .15s, box-shadow .15s; }
    .btn-aprobar:hover  { transform:translateY(-1px); box-shadow:0 4px 12px rgba(22,163,74,.35); }
    .btn-rechazar { transition: background .15s, transform .15s, box-shadow .15s; }
    .btn-rechazar:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(220,38,38,.35); }

    /* ── Chevron ── */
    .acc-chevron { transition: transform .2s ease; }

    /* ── Label activo cuando su checkbox está marcado ── */
    label:has(.fil-producto:checked),
    label:has(.fil-solicitante:checked),
    label:has(.fil-contenedor:checked) {
        background: #eef2ff !important;
        outline: 1px solid #c7d2fe;
        border-radius: 0.375rem;
    }
    label:has(.fil-producto:checked) span,
    label:has(.fil-solicitante:checked) span,
    label:has(.fil-contenedor:checked) span {
        color: #4338ca !important;
        font-weight: 600;
    }

    /* ── Cabecera de acordeón resaltada si tiene filtros activos ── */
    .acc-header.has-active > span:first-child {
        color: #4338ca;
    }
    .acc-header.has-active .acc-chevron {
        color: #4338ca;
    }
</style>
@endpush

@push('scripts')
<script>
    // ── Filtro de solicitudes pendientes ─────────────────────────────────
    var filProductos    = new Set();
    var filSolicitantes = new Set();
    var filContenedores = new Set();
    var filDesde = null, filHasta = null;

    function aplicarFiltrosSol() {
        var q = (document.getElementById('buscador-solicitudes')?.value || '').toLowerCase().trim();
        var cards = document.querySelectorAll('.sol-card');
        var visibles = 0;

        cards.forEach(function(card) {
            var ok = true;
            if (q && !card.dataset.buscar.includes(q)) ok = false;
            if (filProductos.size    && !filProductos.has(card.dataset.producto))    ok = false;
            if (filSolicitantes.size && !filSolicitantes.has(card.dataset.solicitante)) ok = false;
            if (filContenedores.size && !filContenedores.has(card.dataset.contenedor))  ok = false;
            if (filDesde || filHasta) {
                var fMs = new Date(card.dataset.fecha + 'T00:00:00').getTime();
                if (filDesde && fMs < filDesde) ok = false;
                if (filHasta && fMs > filHasta) ok = false;
            }
            card.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });

        document.getElementById('sin-resultados').classList.toggle('hidden', visibles > 0);
        document.getElementById('contador-sol').textContent = visibles + ' solicitud(es) esperando revisión';

        // Badge activo
        var hayFiltro = filProductos.size || filSolicitantes.size || filContenedores.size || filDesde || filHasta;
        var badge = document.getElementById('badge-sol');
        if (badge) badge.classList.toggle('hidden', !hayFiltro);

        // Resaltar encabezados de acordeón con filtros activos
        var btnProductos   = document.querySelector('[data-target="acc-productos"]');
        var btnSolicitante = document.querySelector('[data-target="acc-solicitante"]');
        var btnContenedor  = document.querySelector('[data-target="acc-contenedor"]');
        if (btnProductos)   btnProductos.classList.toggle('has-active',   filProductos.size > 0);
        if (btnSolicitante) btnSolicitante.classList.toggle('has-active', filSolicitantes.size > 0);
        if (btnContenedor)  btnContenedor.classList.toggle('has-active',  filContenedores.size > 0);
        // Fecha no tiene acc-header propio, no aplica
    }

    // Buscador
    var buscadorSol = document.getElementById('buscador-solicitudes');
    if (buscadorSol) buscadorSol.addEventListener('input', aplicarFiltrosSol);

    // Checkboxes
    document.querySelectorAll('.fil-producto').forEach(function(cb) {
        cb.addEventListener('change', function() {
            this.checked ? filProductos.add(this.value) : filProductos.delete(this.value);
            aplicarFiltrosSol();
        });
    });
    document.querySelectorAll('.fil-solicitante').forEach(function(cb) {
        cb.addEventListener('change', function() {
            this.checked ? filSolicitantes.add(this.value) : filSolicitantes.delete(this.value);
            aplicarFiltrosSol();
        });
    });
    document.querySelectorAll('.fil-contenedor').forEach(function(cb) {
        cb.addEventListener('change', function() {
            this.checked ? filContenedores.add(this.value) : filContenedores.delete(this.value);
            aplicarFiltrosSol();
        });
    });

    // Fecha
    document.getElementById('fil-fecha-desde')?.addEventListener('change', function() {
        filDesde = this.value ? new Date(this.value + 'T00:00:00').getTime() : null;
        aplicarFiltrosSol();
    });
    document.getElementById('fil-fecha-hasta')?.addEventListener('change', function() {
        filHasta = this.value ? new Date(this.value + 'T23:59:59').getTime() : null;
        aplicarFiltrosSol();
    });

    // Toggle panel
    var btnFiltros = document.getElementById('btn-filtros-sol');
    var panelFiltros = document.getElementById('panel-filtros-sol');
    if (btnFiltros) {
        btnFiltros.addEventListener('click', function(e) {
            e.stopPropagation();
            panelFiltros.classList.toggle('hidden');
        });
        document.addEventListener('click', function(e) {
            if (!panelFiltros.contains(e.target) && e.target !== btnFiltros) {
                panelFiltros.classList.add('hidden');
            }
        });
    }

    // Acordeón
    document.querySelectorAll('.acc-header').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var body = document.getElementById(this.dataset.target);
            var chevron = this.querySelector('.acc-chevron');
            body.classList.toggle('hidden');
            chevron.style.transform = body.classList.contains('hidden') ? '' : 'rotate(180deg)';
        });
    });

    // Limpiar filtros
    document.getElementById('btn-limpiar-filtros-sol')?.addEventListener('click', function() {
        filProductos.clear(); filSolicitantes.clear(); filContenedores.clear();
        filDesde = null; filHasta = null;
        document.querySelectorAll('.fil-producto,.fil-solicitante,.fil-contenedor').forEach(function(cb) { cb.checked = false; });
        var d = document.getElementById('fil-fecha-desde'), h = document.getElementById('fil-fecha-hasta');
        if (d) d.value = ''; if (h) h.value = '';
        if (buscadorSol) buscadorSol.value = '';
        aplicarFiltrosSol();
    });

    function abrirModalRechazo(id, url) {
        const modal = document.getElementById('modalRechazo');
        const form  = document.getElementById('formRechazo');
        form.action = url || `/admin/solicitudes/${id}/rechazar`;
        document.getElementById('motivo_rechazo').value = '';
        limpiarErrorRechazo();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(function() { document.getElementById('motivo_rechazo').focus(); }, 50);
    }

    function cerrarModalRechazo() {
        const modal = document.getElementById('modalRechazo');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        limpiarErrorRechazo();
    }

    function validarRechazo() {
        const motivo = document.getElementById('motivo_rechazo').value.trim();
        if (!motivo) {
            const textarea = document.getElementById('motivo_rechazo');
            const error    = document.getElementById('error-motivo-rechazo');
            textarea.classList.add('border-red-500', 'ring-1', 'ring-red-400');
            error.classList.remove('hidden');
            error.classList.add('flex');
            textarea.focus();
            return false;
        }
        return true;
    }

    function limpiarErrorRechazo() {
        const textarea = document.getElementById('motivo_rechazo');
        const error    = document.getElementById('error-motivo-rechazo');
        textarea.classList.remove('border-red-500', 'ring-1', 'ring-red-400');
        error.classList.add('hidden');
        error.classList.remove('flex');
    }

    // Cerrar al hacer clic fuera del modal
    document.getElementById('modalRechazo').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalRechazo();
    });
</script>
@endpush

@endsection
