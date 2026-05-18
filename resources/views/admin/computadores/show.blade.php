@extends('layouts.app')
@section('title', $computador->codigo . ' — Armar Equipo')

@section('content')

@php
    $estados     = \App\Models\ComputadorArmado::ESTADOS;
    // Cantidad por categoría (usando categoria_id del componente o del producto como fallback)
    $cantidadesPorCat = $computador->componentesActivos
        ->groupBy(fn($c) => $c->categoria_id ?? $c->producto?->categoria_id)
        ->filter(fn($g, $k) => $k !== null && $k !== '')
        ->map(fn($g) => $g->sum('cantidad'));

    $catIdsInstaladas  = $cantidadesPorCat->keys()->toArray();
    $componentesPorCat = $computador->componentesActivos
        ->groupBy(fn($c) => $c->categoria_id ?? $c->producto?->categoria_id);
    $statusClass = match($computador->estado) {
        'listo'     => 'bg-green-100 text-green-700 border-green-300',
        'en_uso'    => 'bg-blue-100 text-blue-700 border-blue-300',
        'desarmado' => 'bg-gray-100 text-gray-500 border-gray-300',
        default     => 'bg-yellow-100 text-yellow-700 border-yellow-300',
    };
@endphp

{{-- Header ─────────────────────────────────────────────────────── --}}
<div class="mb-5 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <a href="{{ route('admin.computadores.index') }}" class="text-sm text-indigo-600 hover:underline">← Armar Equipo</a>
        <div class="flex items-center gap-3 mt-1 flex-wrap">
            <h1 class="text-2xl font-bold text-gray-800 font-mono">{{ $computador->codigo }}</h1>
            <span class="text-lg font-medium text-gray-600">{{ $computador->nombre }}</span>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full border {{ $statusClass }}">
                {{ $estados[$computador->estado] ?? $computador->estado }}
            </span>
        </div>
        @if($computador->ubicacion || $computador->usuario_asignado)
        <p class="text-xs text-gray-400 mt-0.5">
            @if($computador->ubicacion) 📍 {{ $computador->ubicacion }} @endif
            @if($computador->usuario_asignado) · 👤 {{ $computador->usuario_asignado }} @endif
        </p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.computadores.edit', $computador->id) }}"
           class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
           style="background:#374151; color:#fff;"
           onmouseover="this.style.background='#1f2937'" onmouseout="this.style.background='#374151'">
            Editar equipo
        </a>
        @if($computador->estado === 'en_armado' && $computador->componentesActivos->isNotEmpty())
        <button type="button" onclick="document.getElementById('modal-listo').style.display='flex'"
                class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
            ✓ Equipo terminado
        </button>
        @endif
        @if(in_array($computador->estado, ['listo', 'en_uso']))
        <button type="button" onclick="document.getElementById('modal-reabrir').style.display='flex'"
                class="btn-modificar px-3 py-1.5 text-xs font-semibold rounded-lg transition">
            ✎ Modificar componentes
        </button>
        @endif
        @if($computador->componentesActivos->isNotEmpty() && $computador->estado !== 'desarmado')
        <form method="POST" action="{{ route('admin.computadores.desarmar', $computador->id) }}"
              onsubmit="return confirm('¿Desarmar completamente? Todos los componentes vuelven al stock.')">
            @csrf
            <button type="submit" class="btn-desarmar px-3 py-1.5 text-xs font-semibold rounded-lg transition">
                Desarmar todo
            </button>
        </form>
        @endif
    </div>
</div>

@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<div class="flex gap-5 items-start">

    {{-- ══ PANEL IZQUIERDO: Componentes instalados ══ --}}
    <div class="space-y-4 flex-shrink-0" style="width:260px;">

        {{-- Resumen visual --}}
        <div class="bg-white rounded-xl shadow border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-700">Componentes instalados</h2>
                <span class="text-xs font-bold text-indigo-600">{{ $computador->componentesActivos->count() }} piezas</span>
            </div>
            <div class="space-y-1">
                @forelse($familiaCategorias as $cat)
                @php
                    $instalado  = in_array($cat->id, $catIdsInstaladas);
                    $cantidad   = $cantidadesPorCat[$cat->id] ?? 0;
                @endphp
                @if($instalado)
                @php
                    $compsEnCat  = ($computador->estado === 'en_armado') ? ($componentesPorCat[$cat->id] ?? collect()) : collect();
                    $collapsible = $compsEnCat->count() >= 2;
                @endphp
                <div class="border-b border-gray-50 dark:border-slate-700/60 last:border-0">
                    <div class="flex items-center gap-2 py-1 {{ $collapsible ? 'cursor-pointer select-none' : '' }}"
                         @if($collapsible) onclick="toggleClist({{ $cat->id }})" @endif>
                        <span class="w-4 h-4 text-green-500 flex-shrink-0">
                            <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-100 flex-1">{{ $cat->nombre }}</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 shrink-0">
                            {{ $cantidad }}
                        </span>
                        @if($collapsible)
                        <svg id="chev-{{ $cat->id }}" class="w-3 h-3 text-gray-400 dark:text-slate-500 flex-shrink-0 transition-transform duration-150" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @endif
                    </div>
                    @if($compsEnCat->isNotEmpty())
                    <div id="clist-{{ $cat->id }}">
                        @foreach($compsEnCat as $comp)
                        <div class="flex items-center gap-1.5 pl-6 pb-1.5">
                            <span class="text-[11px] text-gray-600 dark:text-slate-300 flex-1 truncate leading-tight">
                                {{ $comp->producto?->nombre ?? '—' }}@if($comp->cantidad > 1) <span class="font-semibold">×{{ $comp->cantidad }}</span>@endif
                            </span>
                            <button type="button"
                                    onclick="abrirRetirar({{ $comp->id }}, '{{ addslashes($comp->producto?->nombre ?? 'componente') }}', EQ_RETIRAR_BASE)"
                                    class="text-[10px] text-red-500 hover:text-red-700 font-semibold border border-red-200 hover:border-red-400 rounded px-1.5 py-0.5 transition flex-shrink-0">
                                Retirar
                            </button>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @else
                <div class="flex items-center gap-2 py-1 border-b border-gray-50 dark:border-slate-700/60 last:border-0">
                    <span class="w-4 h-4 text-gray-500 dark:text-slate-400 flex-shrink-0">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" stroke-dasharray="3"/>
                        </svg>
                    </span>
                    <span class="text-xs text-gray-600 dark:text-slate-300 flex-1">{{ $cat->nombre }}</span>
                </div>
                @endif
                @empty
                <p class="text-xs text-gray-400 dark:text-slate-500 py-2">Sin categorías en familia Partes y Piezas.</p>
                @endforelse
            </div>

        </div>

        {{-- Historial reciente --}}
        @if($computador->componentes->isNotEmpty())
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Historial de movimientos</h2>
            </div>
            <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                @foreach($computador->componentes->take(20) as $comp)
                <div class="px-4 py-2.5 {{ $comp->activo ? '' : 'opacity-60' }}">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $comp->activo ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                        <p class="text-xs font-medium text-gray-800 truncate flex-1">{{ $comp->producto?->nombre ?? '—' }}</p>
                        <span class="text-xs {{ $comp->activo ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $comp->activo ? 'Instalado' : 'Retirado' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5 ml-3">
                        {{ $comp->activo ? $comp->fecha_instalacion?->format('d/m/Y H:i') : $comp->fecha_retiro?->format('d/m/Y H:i') }}
                        @if(!$comp->activo && $comp->motivo_retiro)
                            · {{ $comp->motivo_retiro }}
                        @endif
                    </p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ══ PANEL DERECHO: Browser Categoría → Productos ══ --}}
    <div class="flex-1 min-w-0">
        @if($computador->estado === 'desarmado')
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                <p class="font-semibold">Equipo desarmado — no se pueden agregar componentes.</p>
            </div>
        @else
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">

            {{-- Tabs de tipos de componente --}}
            <div class="border-b border-gray-100">
                <div class="px-4 pt-3 pb-0">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Selecciona el tipo de componente a agregar
                    </p>
                    <div id="cat-tabs" class="flex items-center gap-1.5 flex-wrap mb-2">
                        {{-- Generado por JS --}}
                    </div>
                </div>
            </div>

            {{-- Grid de productos --}}
            <div id="prod-grid" class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 max-h-[60vh] overflow-y-auto">
                {{-- Generado por JS --}}
            </div>

            <div id="prod-vacio" class="p-10 text-center text-sm text-gray-400" style="display:none;">
                Sin productos disponibles en esta categoría.
            </div>

        </div>
        @endif
    </div>

</div>

{{-- ══ MODAL: Agregar componente ══ --}}
<div id="modal-agregar"
     style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55);
            align-items:center; justify-content:center; padding:1rem;">
    <div id="modal-agregar-inner"
         style="background:#fff; border-radius:1rem; box-shadow:0 24px 60px rgba(0,0,0,.3);
                width:100%; max-width:480px; animation:eqIn .2s cubic-bezier(.22,.68,0,1.2) both;">

        {{-- Header --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between;
                    padding:1.1rem 1.25rem 0.75rem; border-bottom:1px solid #f3f4f6;">
            <div>
                <p style="font-size:0.7rem; font-weight:700; color:#6b7280; text-transform:uppercase;
                           letter-spacing:0.05em; margin:0 0 0.2rem;">Armar Equipo · {{ $computador->codigo }}</p>
                <p id="mag-nombre" style="font-size:0.95rem; font-weight:700; color:#111827; margin:0;"></p>
                <div id="mag-info" style="font-size:0.75rem; color:#6b7280; margin-top:0.2rem; display:flex; gap:0.75rem; flex-wrap:wrap;"></div>
            </div>
            <button type="button" onclick="cerrarAgregar()"
                    style="color:#9ca3af; font-size:1.25rem; border:none; background:none; cursor:pointer; line-height:1;">✕</button>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.computadores.componentes.agregar', $computador->id) }}"
              id="form-agregar">
            @csrf
            <input type="hidden" id="mag-producto-id" name="producto_id">

            <div style="padding:1rem 1.25rem; display:flex; flex-direction:column; gap:0.85rem;">

                {{-- Categoría (auto desde tab seleccionado por ID) --}}
                <input type="hidden" name="categoria_id" id="mag-categoria-id">
                <div style="background:#eef2ff; border:1px solid #c7d2fe; border-radius:0.5rem; padding:0.5rem 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                    <span style="font-size:0.7rem; font-weight:700; color:#6366f1; text-transform:uppercase; letter-spacing:0.04em;">Categoría:</span>
                    <span id="mag-cat-display" style="font-size:0.82rem; font-weight:700; color:#312e81;">—</span>
                </div>

                {{-- Cantidad + Serial --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.65rem;">
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">
                            Cantidad <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="number" name="cantidad" id="mag-cantidad" value="1" min="1"
                               style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem;
                                      font-size:0.82rem; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">
                            N° Serie <span style="color:#9ca3af; font-weight:400;">(opcional)</span>
                        </label>
                        <input type="text" name="serial" placeholder="SN12345"
                               style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem;
                                      font-size:0.82rem; font-family:monospace; box-sizing:border-box;">
                    </div>
                </div>

                {{-- Motivo obligatorio --}}
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">
                        Motivo del movimiento <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-bottom:0.45rem;" id="mag-motivos-chips">
                        @foreach($motivos as $m)
                        <button type="button" onclick="selMotivo('{{ $m }}')"
                                class="motivo-chip"
                                style="padding:3px 10px; font-size:0.7rem; font-weight:600; border-radius:9999px;
                                       border:1px solid #e5e7eb; background:#f9fafb; color:#374151; cursor:pointer; transition:all .12s;">
                            {{ $m }}
                        </button>
                        @endforeach
                    </div>
                    <input type="text" name="motivo" id="mag-motivo" required maxlength="200"
                           placeholder="Selecciona o escribe el motivo..."
                           style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem;
                                  font-size:0.82rem; box-sizing:border-box;">
                </div>

                {{-- Notas --}}
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">
                        Notas <span style="color:#9ca3af; font-weight:400;">(opcional)</span>
                    </label>
                    <input type="text" name="notas" maxlength="500" placeholder="Observaciones adicionales..."
                           style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem;
                                  font-size:0.82rem; box-sizing:border-box;">
                </div>
            </div>

            <div style="padding:0.75rem 1.25rem 1.25rem; display:flex; gap:0.5rem; justify-content:flex-end;">
                <button type="button" onclick="cerrarAgregar()"
                        style="padding:0.45rem 1rem; font-size:0.82rem; font-weight:600; color:#374151;
                               background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:0.45rem 1.25rem; font-size:0.82rem; font-weight:700; color:#fff;
                               background:#4f46e5; border:none; border-radius:0.5rem; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                    ✓ Instalar componente
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL: Retirar componente ══ --}}
<div id="modal-retirar"
     style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55);
            align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 24px 60px rgba(0,0,0,.3);
                width:100%; max-width:400px; animation:eqIn .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.1rem 1.25rem 0.75rem; border-bottom:1px solid #f3f4f6;">
            <p style="font-size:0.95rem; font-weight:700; color:#111827; margin:0 0 0.15rem;">Retirar componente</p>
            <p id="retirar-nombre" style="font-size:0.82rem; color:#6b7280; margin:0;"></p>
        </div>
        <form id="form-retirar" method="POST" action="">
            @csrf @method('PATCH')
            <div style="padding:1rem 1.25rem;">
                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                    Motivo del retiro <span style="color:#ef4444;">*</span>
                </label>
                <div style="display:flex; flex-wrap:wrap; gap:0.35rem; margin-bottom:0.45rem;">
                    @foreach(['REEMPLAZO COMPONENTE','MANTENIMIENTO','DIAGNÓSTICO','UPGRADE','DEVOLUCIÓN STOCK','OTRO'] as $mr)
                    <button type="button" onclick="selMotivoRetiro('{{ $mr }}')"
                            class="motivo-chip-r"
                            style="padding:3px 10px; font-size:0.7rem; font-weight:600; border-radius:9999px;
                                   border:1px solid #e5e7eb; background:#f9fafb; color:#374151; cursor:pointer; transition:all .12s;">
                        {{ $mr }}
                    </button>
                    @endforeach
                </div>
                <input type="text" name="motivo_retiro" id="ret-motivo" required maxlength="500"
                       placeholder="Selecciona o escribe el motivo..."
                       style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem;
                              font-size:0.82rem; box-sizing:border-box;">
            </div>
            <div style="padding:0.5rem 1.25rem 1.25rem; display:flex; gap:0.5rem; justify-content:flex-end;">
                <button type="button" onclick="cerrarRetirar()"
                        style="padding:0.45rem 1rem; font-size:0.82rem; font-weight:600; color:#374151;
                               background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">Cancelar</button>
                <button type="submit"
                        style="padding:0.45rem 1.1rem; font-size:0.82rem; font-weight:700; color:#fff;
                               background:#ef4444; border:none; border-radius:0.5rem; cursor:pointer;">
                    Confirmar retiro
                </button>
            </div>
        </form>
    </div>
</div>

@push('head')
<style>
@keyframes eqIn { from { opacity:0; transform:scale(.93) translateY(-10px); } to { opacity:1; transform:none; } }
.cat-tab          { border:1px solid #e5e7eb; background:#f9fafb; color:#374151; cursor:pointer; }
.cat-tab.activo   { background:#4f46e5; color:#fff; border-color:#4f46e5; }
.cat-tab:hover:not(.activo) { background:#eef2ff; border-color:#c7d2fe; color:#4338ca; }
.prod-card        { border:1px solid #e5e7eb; border-radius:0.75rem; padding:0.85rem; background:#fff;
                    transition:border-color .15s, box-shadow .15s; cursor:default; }
.prod-card:hover  { border-color:#c7d2fe; box-shadow:0 2px 8px rgba(99,102,241,.1); }
.btn-agregar-prod { border:none; border-radius:0.5rem; padding:0.35rem 0.85rem; font-size:0.72rem;
                    font-weight:700; color:#fff; background:#4f46e5; cursor:pointer; transition:background .15s; white-space:nowrap; }
.btn-agregar-prod:hover { background:#4338ca; }
.btn-agregar-prod:disabled { background:#d1d5db; cursor:not-allowed; }
.motivo-chip.sel, .motivo-chip-r.sel { background:#4f46e5; color:#fff; border-color:#4f46e5; }

/* Dark mode modales */
html.dark #modal-agregar-inner,
html.dark #modal-retirar > div { background:var(--c-surface) !important; }
html.dark .prod-card { background:var(--c-surface); border-color:var(--c-border); }
html.dark .cat-tab   { background:var(--c-surface-2); color:var(--c-text-3); border-color:var(--c-border); }
html.dark .cat-tab.activo { background:#4f46e5; color:#fff; border-color:#4f46e5; }

/* Botones de acción header */
.btn-modificar       { background:#d97706; color:#fff; }
.btn-modificar:hover { background:#b45309; }
html.dark .btn-modificar       { background:#78350f; color:#fed7aa; }
html.dark .btn-modificar:hover { background:#92400e; }

.btn-desarmar       { background:#ef4444; color:#fff; }
.btn-desarmar:hover { background:#dc2626; }
html.dark .btn-desarmar       { background:#7f1d1d; color:#fca5a5; }
html.dark .btn-desarmar:hover { background:#991b1b; }
</style>
@endpush

@push('scripts')
<script>
var EQ_CATS          = @json($categoriasJson);
var EQ_AJAX          = '{{ route('admin.computadores.productos-categoria') }}';
var EQ_RETIRAR_BASE  = '{{ route('admin.computadores.componentes.retirar', [$computador->id, '__ID__']) }}'.replace('/__ID__/retirar', '');
var EQ_COMPUTADOR_ID = {{ $computador->id }};
var catActiva        = null;
var cargando         = false;

// ── Renderizar tabs de categorías (por ID) ────────────────────────────────
function renderTabs() {
    var tabs = document.getElementById('cat-tabs');
    if (!tabs) return;
    if (!EQ_CATS.length) {
        tabs.innerHTML = '<p class="text-xs text-gray-400 py-1">Sin categorías en familia Partes y Piezas.</p>';
        return;
    }
    tabs.innerHTML = EQ_CATS.map(function(c) {
        return '<button type="button" class="cat-tab px-3 py-1.5 text-xs font-semibold rounded-lg border transition mb-2"'
             + ' data-cat-id="' + c.id + '"'
             + ' onclick="seleccionarCategoria(' + c.id + ',\'' + c.nombre.replace(/'/g,"\\'") + '\')">'
             + c.nombre
             + '</button>';
    }).join('');
}

// ── Seleccionar categoría → fetch productos desde backend ─────────────────
function seleccionarCategoria(catId, catNombre) {
    catActiva = {id: catId, nombre: catNombre};

    document.querySelectorAll('.cat-tab').forEach(function(t) { t.classList.remove('activo'); });
    var tab = document.querySelector('.cat-tab[data-cat-id="' + catId + '"]');
    if (tab) tab.classList.add('activo');

    document.getElementById('mag-categoria-id').value = catId;
    document.getElementById('mag-cat-display').textContent = catNombre;

    fetchProductos(catId);
}

// ── AJAX: cargar productos de la categoría desde backend ──────────────────
function fetchProductos(catId) {
    if (cargando) return;
    cargando = true;

    var grid  = document.getElementById('prod-grid');
    var vacio = document.getElementById('prod-vacio');

    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem 0;">'
        + '<div style="display:inline-block;width:1.5rem;height:1.5rem;border:2px solid #c7d2fe;border-top-color:#4f46e5;border-radius:50%;animation:eq-spin .7s linear infinite;"></div>'
        + '</div>';
    vacio.style.display = 'none';

    fetch(EQ_AJAX + '?cat_id=' + catId + '&computador_id=' + EQ_COMPUTADOR_ID, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(prods) { renderProductos(prods); })
    .catch(function() {
        grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#ef4444;font-size:0.82rem;padding:2rem;">Error al cargar productos.</p>';
    })
    .finally(function() { cargando = false; });
}

// ── Render de cards de producto ───────────────────────────────────────────
function renderProductos(prods) {
    var grid  = document.getElementById('prod-grid');
    var vacio = document.getElementById('prod-vacio');
    var dm    = document.documentElement.classList.contains('dark');

    if (!prods || !prods.length) {
        grid.innerHTML = '';
        vacio.style.display = '';
        return;
    }
    vacio.style.display = 'none';

    grid.innerHTML = prods.map(function(p) {
        var sinStock    = p.stock <= 0;
        var stockClr    = sinStock ? (dm ? '#f87171' : '#dc2626') : (dm ? '#4ade80' : '#16a34a');
        var precio      = p.precio > 0 ? '$' + Number(p.precio).toLocaleString('es-CL') : '—';
        var nombre      = p.nombre.replace(/'/g,"\\'").replace(/"/g,'&quot;');
        var instaladoBadge = p.instalado > 0
            ? '<span style="font-size:0.65rem;font-weight:700;color:' + (dm?'#818cf8':'#4f46e5') + ';background:' + (dm?'rgba(79,70,229,.18)':'#eef2ff') + ';border-radius:9999px;padding:1px 7px;">Instalado: ' + p.instalado + '</span>'
            : '';
        return '<div class="prod-card" style="position:relative;animation:eq-fade-in .18s ease both;">'
             + '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.4rem;margin-bottom:0.35rem;">'
             + '<p style="font-size:0.8rem;font-weight:700;color:' + (dm?'#f1f5f9':'#111827') + ';margin:0;line-height:1.3;flex:1;">' + p.nombre + '</p>'
             + instaladoBadge
             + '</div>'
             + '<div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;">'
             + '<div style="display:flex;flex-direction:column;gap:1px;">'
             + '<span style="font-size:0.7rem;color:' + stockClr + ';font-weight:700;">Stock: ' + p.stock + ' ' + p.unidad + '</span>'
             + '<span style="font-size:0.7rem;color:' + (dm?'#94a3b8':'#9ca3af') + ';">Precio: ' + precio + '</span>'
             + '</div>'
             + '<button type="button" class="btn-agregar-prod" ' + (sinStock ? 'disabled' : '')
             + ' onclick="abrirAgregar(' + p.id + ',\'' + nombre + '\',' + p.stock + ',\'' + p.unidad + '\',' + p.precio + ')">'
             + (sinStock ? 'Sin stock' : '＋ Agregar')
             + '</button>'
             + '</div>'
             + '</div>';
    }).join('');
}

// ── Modal Agregar ─────────────────────────────────────────────────────────
function abrirAgregar(id, nombre, stock, unidad, precio) {
    if (!catActiva) {
        alert('Selecciona una categoría en los tabs de arriba.');
        return;
    }
    document.getElementById('mag-producto-id').value          = id;
    document.getElementById('mag-categoria-id').value         = catActiva.id;
    document.getElementById('mag-cat-display').textContent    = catActiva.nombre;
    document.getElementById('mag-nombre').textContent         = nombre;
    document.getElementById('mag-cantidad').max               = stock;
    document.getElementById('mag-cantidad').value             = 1;
    document.getElementById('mag-motivo').value               = '';
    document.querySelectorAll('.motivo-chip').forEach(function(c) { c.classList.remove('sel'); });

    var info = '<span>Stock: <strong>' + stock + ' ' + unidad + '</strong></span>';
    if (precio > 0) info += '<span>Precio: <strong>$' + Number(precio).toLocaleString('es-CL') + '</strong></span>';
    document.getElementById('mag-info').innerHTML = info;

    document.getElementById('modal-agregar').style.display = 'flex';
    setTimeout(function() { document.getElementById('mag-motivo').focus(); }, 200);
}
function cerrarAgregar() {
    document.getElementById('modal-agregar').style.display = 'none';
}
function selMotivo(val) {
    document.getElementById('mag-motivo').value = val;
    document.querySelectorAll('.motivo-chip').forEach(function(c) {
        c.classList.toggle('sel', c.textContent.trim() === val);
    });
}

// ── Modal Retirar ─────────────────────────────────────────────────────────
function abrirRetirar(componenteId, nombre, routeBase) {
    document.getElementById('retirar-nombre').textContent = nombre;
    document.getElementById('ret-motivo').value = '';
    document.querySelectorAll('.motivo-chip-r').forEach(function(c) { c.classList.remove('sel'); });
    document.getElementById('form-retirar').action = routeBase + '/' + componenteId + '/retirar';
    document.getElementById('modal-retirar').style.display = 'flex';
}
function cerrarRetirar() {
    document.getElementById('modal-retirar').style.display = 'none';
}
function selMotivoRetiro(val) {
    document.getElementById('ret-motivo').value = val;
    document.querySelectorAll('.motivo-chip-r').forEach(function(c) {
        c.classList.toggle('sel', c.textContent.trim() === val);
    });
}

// Cerrar modales con click fuera
document.getElementById('modal-agregar').addEventListener('click', function(e) {
    if (e.target === this) cerrarAgregar();
});
document.getElementById('modal-retirar').addEventListener('click', function(e) {
    if (e.target === this) cerrarRetirar();
});
['modal-listo','modal-reabrir'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
});

// ── Desplegable de componentes por categoría ──────────────────────────────
function toggleClist(catId) {
    var list = document.getElementById('clist-' + catId);
    var chev = document.getElementById('chev-' + catId);
    if (!list) return;
    var hidden = list.style.display === 'none';
    list.style.display = hidden ? '' : 'none';
    if (chev) chev.style.transform = hidden ? '' : 'rotate(-90deg)';
}

// ── Init: cargar primera categoría ───────────────────────────────────────
renderTabs();
if (EQ_CATS.length) {
    seleccionarCategoria(EQ_CATS[0].id, EQ_CATS[0].nombre);
}
</script>
@push('head')
<style>
@keyframes eq-spin    { to { transform: rotate(360deg); } }
@keyframes eq-fade-in { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }
</style>
@endpush
@endpush

{{-- Modal: Marcar como Listo --}}
@if($computador->estado === 'en_armado' && $computador->componentesActivos->isNotEmpty())
<div id="modal-listo"
     style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55);
            align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 24px 60px rgba(0,0,0,.3);
                width:100%; max-width:380px; animation:eqIn .2s cubic-bezier(.22,.68,0,1.2) both;">

        <div style="display:flex; flex-direction:column; align-items:center; padding:1.75rem 1.75rem 1.25rem; text-align:center;">
            <div style="width:3.5rem; height:3.5rem; border-radius:9999px; background:#dcfce7;
                        display:flex; align-items:center; justify-content:center; margin-bottom:1rem;">
                <svg style="width:1.75rem; height:1.75rem; color:#16a34a;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p style="font-size:0.65rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.08em; margin:0 0 0.25rem;">{{ $computador->codigo }}</p>
            <h3 style="font-size:1.1rem; font-weight:700; color:#111827; margin:0 0 0.5rem;">¿Marcar equipo como Listo?</h3>
            <p style="font-size:0.82rem; color:#6b7280; margin:0; line-height:1.5;">
                El equipo <strong style="color:#374151;">{{ $computador->nombre }}</strong>
                pasará al estado <strong style="color:#16a34a;">Listo</strong>.
                Podrás seguir editándolo después.
            </p>
        </div>

        <div style="display:flex; gap:0.6rem; padding:0 1.5rem 1.5rem;">
            <button type="button"
                    onclick="document.getElementById('modal-listo').style.display='none'"
                    style="flex:1; padding:0.6rem 1rem; font-size:0.85rem; font-weight:600; color:#374151;
                           background:#f3f4f6; border:none; border-radius:0.6rem; cursor:pointer;">
                Cancelar
            </button>
            <form method="POST" action="{{ route('admin.computadores.marcar-listo', $computador->id) }}" style="flex:1;">
                @csrf
                <button type="submit"
                        style="width:100%; padding:0.6rem 1rem; font-size:0.85rem; font-weight:700; color:#fff;
                               background:#16a34a; border:none; border-radius:0.6rem; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                    Confirmar
                </button>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal: Modificar componentes (reabrir) --}}
@if(in_array($computador->estado, ['listo', 'en_uso']))
<div id="modal-reabrir"
     style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55);
            align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 24px 60px rgba(0,0,0,.3);
                width:100%; max-width:380px; animation:eqIn .2s cubic-bezier(.22,.68,0,1.2) both;">

        <div style="display:flex; flex-direction:column; align-items:center; padding:1.75rem 1.75rem 1.25rem; text-align:center;">
            <div style="width:3.5rem; height:3.5rem; border-radius:9999px; background:#fef3c7;
                        display:flex; align-items:center; justify-content:center; margin-bottom:1rem;">
                <svg style="width:1.75rem; height:1.75rem; color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <p style="font-size:0.65rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.08em; margin:0 0 0.25rem;">{{ $computador->codigo }}</p>
            <h3 style="font-size:1.1rem; font-weight:700; color:#111827; margin:0 0 0.5rem;">¿Modificar componentes?</h3>
            <p style="font-size:0.82rem; color:#6b7280; margin:0; line-height:1.5;">
                El equipo <strong style="color:#374151;">{{ $computador->nombre }}</strong>
                volverá al estado <strong style="color:#d97706;">En armado</strong>
                para que puedas agregar o retirar componentes.
            </p>
        </div>

        <div style="display:flex; gap:0.6rem; padding:0 1.5rem 1.5rem;">
            <button type="button"
                    onclick="document.getElementById('modal-reabrir').style.display='none'"
                    style="flex:1; padding:0.6rem 1rem; font-size:0.85rem; font-weight:600; color:#374151;
                           background:#f3f4f6; border:none; border-radius:0.6rem; cursor:pointer;">
                Cancelar
            </button>
            <form method="POST" action="{{ route('admin.computadores.reabrir', $computador->id) }}" style="flex:1;">
                @csrf
                <button type="submit"
                        style="width:100%; padding:0.6rem 1rem; font-size:0.85rem; font-weight:700; color:#fff;
                               background:#d97706; border:none; border-radius:0.6rem; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='#b45309'" onmouseout="this.style.background='#d97706'">
                    Sí, modificar
                </button>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
