@extends('layouts.app')

@section('title', 'Productos')

@section('content')

<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Productos</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $productos->count() }} producto(s) físico(s)
            @if($servicios->count() > 0)
            · <span class="text-violet-600 font-medium">{{ $servicios->count() }} servicio(s)</span>
            @endif
        </p>
    </div>

    <div class="flex items-center gap-4 flex-wrap">

        @if(auth()->user()->esDev() && $centrosCostoConProductos->isNotEmpty())
        {{-- Filtro CC exclusivo para dev ─────────────────────────────── --}}
        <div class="relative" id="dev-cc-wrapper">
            <button type="button" id="dev-cc-btn"
                    onclick="toggleDevCC()"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-semibold border border-indigo-300 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                </svg>
                Centro de costo
                <span id="dev-cc-badge" class="hidden bg-indigo-600 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 leading-none"></span>
                <svg class="w-3.5 h-3.5 transition-transform duration-200" id="dev-cc-chevron" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Dropdown multi-select ──────────────────────────────── --}}
            <div id="dev-cc-dropdown"
                 style="position:absolute; right:0; top:calc(100% + 6px); width:460px; z-index:99999; display:none;
                        background:#fff; border:1px solid #e5e7eb; border-radius:0.75rem;
                        box-shadow:0 12px 40px rgba(0,0,0,.18);">

                <div style="padding:0.5rem 0.75rem; border-bottom:1px solid #f3f4f6;
                            display:flex; align-items:center; justify-content:space-between;
                            background:#fff; border-radius:0.75rem 0.75rem 0 0;">
                    <span style="font-size:0.6875rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.06em;">Centros de costo</span>
                    <button type="button" onclick="devCCLimpiar()"
                            style="font-size:0.6875rem; color:#4f46e5; background:none; border:none; cursor:pointer; font-weight:500;">Limpiar</button>
                </div>

                <div style="padding:0.375rem 0.625rem; border-bottom:1px solid #f3f4f6; background:#fff;">
                    <input type="text" id="dev-cc-search" oninput="devCCBuscar(this.value)"
                           placeholder="Buscar centro de costo..."
                           style="width:100%; box-sizing:border-box; padding:0.35rem 0.6rem; font-size:0.75rem; color:#374151;
                                  border:1px solid #e5e7eb; border-radius:0.5rem; outline:none; background:#f9fafb;">
                </div>

                <div id="dev-cc-list" style="max-height:260px; overflow-y:auto; padding:0.375rem 0.5rem; background:#fff;">
                    @foreach($centrosCostoConProductos as $cc)
                    <label style="display:flex; align-items:flex-start; gap:0.5rem;
                                  padding:0.375rem 0.5rem; border-radius:0.5rem; cursor:pointer;"
                           onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background=''">
                        <input type="checkbox" class="dev-cc-check" value="{{ $cc->id }}" onchange="devCCFiltrar()"
                               style="width:0.875rem; height:0.875rem; accent-color:#4f46e5; flex-shrink:0; margin-top:0.15rem; cursor:pointer;">
                        <span style="font-size:0.75rem; color:#374151; line-height:1.35; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $cc->nombre_completo }}</span>
                    </label>
                    @endforeach
                </div>

                <div style="padding:0.5rem 0.75rem; border-top:1px solid #f3f4f6;
                            background:#fff; border-radius:0 0 0.75rem 0.75rem;">
                    <button type="button" onclick="devCCMarcarTodos()"
                            style="font-size:0.6875rem; color:#9ca3af; background:none; border:none; cursor:pointer; font-weight:500; text-align:left; padding:0;">
                        Seleccionar todos
                    </button>
                </div>
            </div>
        </div>
        @endif

        @if(auth()->user()->esAdmin())
        <button type="button" id="btn-agregar-inventario"
            class="btn-agregar-inv inline-flex items-center gap-1.5 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Agregar Inventario
        </button>
        @endif

        {{-- Leyenda de colores — bg-red-50/yellow-50/white coinciden con los rowClass de la tabla --}}
        <div class="flex items-center gap-4 text-xs text-gray-600">
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-red-50 border border-red-300"></span>
                Stock crítico
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-yellow-50 border border-yellow-300"></span>
                Stock mínimo
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-white border border-gray-200"></span>
                Normal
            </span>
        </div>
    </div>
</div>

{{-- ═══ SELECTOR PRODUCTOS / SERVICIOS ══════════════════════════════════ --}}
@php $cntServsTab = $servicios->count(); @endphp
<div class="mb-5 flex items-center gap-1" id="tab-bar-prod-serv">
    <button type="button" id="tab-btn-productos"
            onclick="switchTab('productos')"
            class="tab-ps-btn tab-ps-active flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        Productos
        @if($productos->count() > 0)
        <span class="tab-ps-cnt bg-white/30 text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[1.25rem] text-center">{{ $productos->count() }}</span>
        @endif
    </button>
    <button type="button" id="tab-btn-servicios"
            onclick="switchTab('servicios')"
            class="tab-ps-btn flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
        </svg>
        Servicios
        @if($cntServsTab > 0)
        <span class="tab-ps-cnt text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[1.25rem] text-center">{{ $cntServsTab }}</span>
        @endif
    </button>
</div>

{{-- Errores de validación del formulario de solicitud --}}
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
    {{ $errors->first() }}
</div>
@endif

{{-- ═══ PANEL: PRODUCTOS ══════════════════════════════════════════════════ --}}
<div id="tab-panel-productos">

@php
    $fFamilias = $productos->groupBy('nombre')->sortKeys();
@endphp

{{-- Buscador + botón filtros --}}
<div class="mb-3 flex items-center gap-2">
    <button type="button" id="btn-filtros-prod"
        class="relative flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border rounded-lg shadow-sm transition bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600"
        style="white-space:nowrap;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M11 16h2"/>
        </svg>
        Filtros
        <span id="badge-prod" class="hidden absolute -top-1.5 -right-1.5 w-2.5 h-2.5 bg-indigo-600 rounded-full border-2 border-white"></span>
    </button>

    <input id="buscador-productos" type="text" placeholder="🔍  Buscar por categoría, descripción, contenedor, stock o estado..."
           class="flex-1 px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

{{-- Panel de filtros (flujo normal) --}}
<div id="panel-filtros-prod" class="hidden mb-4">
    <div class="bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">

        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50">
            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Filtros</span>
            <button type="button" id="btn-limpiar-prod" class="text-xs text-indigo-600 hover:underline font-medium">
                Limpiar todo
            </button>
        </div>

        {{-- Tipo: Todos / Productos / Servicios --}}
        <div class="px-4 py-2 border-b border-gray-100 flex items-center gap-3 flex-wrap">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Tipo:</span>
            <label class="flex items-center gap-1.5 cursor-pointer text-xs px-2 py-0.5 rounded-full border border-gray-200 hover:border-indigo-300 transition">
                <input type="radio" name="fil-tipo-prod" value="todos" class="fil-prod-tipo accent-indigo-600" checked> Todos
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer text-xs px-2 py-0.5 rounded-full border border-gray-200 hover:border-indigo-300 transition">
                <input type="radio" name="fil-tipo-prod" value="producto" class="fil-prod-tipo accent-indigo-600"> Productos físicos
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer text-xs px-2 py-0.5 rounded-full border border-violet-200 hover:border-violet-400 transition text-violet-700 font-semibold">
                <input type="radio" name="fil-tipo-prod" value="servicio" class="fil-prod-tipo accent-violet-600"> Servicios
            </label>
        </div>

        <div class="grid grid-cols-1 gap-0 divide-y divide-gray-100 md:grid-cols-3 md:divide-x md:divide-y-0">

            {{-- Contenedor --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-prod w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1"
                        data-target="acc-prod-contenedor">
                    <span>Contenedor</span>
                    <svg class="acc-prod-chevron w-3.5 h-3.5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="acc-prod-contenedor" class="hidden space-y-1 max-h-48 overflow-y-auto pr-1 mt-1">
                    @foreach($containers as $c)
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-prod-contenedor w-3.5 h-3.5 accent-indigo-600 shrink-0" value="{{ $c->id }}">
                        <span class="text-xs text-gray-700 leading-tight">{{ $c->nombre }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Estado --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-prod w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1"
                        data-target="acc-prod-estado">
                    <span>Estado</span>
                    <svg class="acc-prod-chevron w-3.5 h-3.5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="acc-prod-estado" class="hidden space-y-1 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-prod-estado w-3.5 h-3.5 accent-indigo-600 shrink-0" value="normal">
                        <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Normal
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-prod-estado w-3.5 h-3.5 accent-indigo-600 shrink-0" value="minimo">
                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> Mínimo
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-prod-estado w-3.5 h-3.5 accent-indigo-600 shrink-0" value="critico">
                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Crítico
                        </span>
                    </label>
                </div>
            </div>

            {{-- Familia + descripciones --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-prod w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1"
                        data-target="acc-prod-familia">
                    <span>Familia</span>
                    <svg class="acc-prod-chevron w-3.5 h-3.5 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="acc-prod-familia" class="hidden space-y-2 max-h-56 overflow-y-auto pr-1 mt-1">
                    @foreach($fFamilias as $familia => $prods)
                    @php $ids = $prods->pluck('id')->join(','); @endphp
                    <div>
                        {{-- Padre: toda la familia --}}
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                            <input type="checkbox" class="fil-prod-familia-padre w-3.5 h-3.5 accent-indigo-600 shrink-0"
                                   data-ids="{{ $ids }}">
                            <span class="text-xs font-bold text-gray-700 leading-tight">{{ $familia }}</span>
                        </label>
                        {{-- Hijos: cada descripción --}}
                        <div class="pl-4 mt-0.5 space-y-0.5">
                            @foreach($prods as $p)
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-0.5 rounded-md transition">
                                <input type="checkbox" class="fil-prod-desc w-3.5 h-3.5 accent-indigo-600 shrink-0"
                                       value="{{ $p->id }}">
                                <span class="text-xs text-gray-500 leading-tight">{{ $p->nombre }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Estado vacío --}}
@if($productos->isEmpty())
<div class="bg-white dark:bg-slate-800 rounded-xl shadow border border-gray-100 dark:border-slate-700
            flex flex-col items-center justify-center text-center gap-5 mb-6"
     style="min-height:340px; padding:3rem 2rem;">
    <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
    </svg>
    <div class="max-w-sm">
        <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">No hay productos registrados</p>
        <p class="text-sm text-gray-400 dark:text-slate-500 mt-2 leading-relaxed">
            Aún no se han agregado productos al inventario.<br>Comienza usando el botón <strong class="text-gray-500 dark:text-slate-400">+ Agregar Inventario</strong>.
        </p>
    </div>
</div>
@endif

{{-- Tabla de productos --}}
<div class="bg-white rounded-xl shadow overflow-hidden p-4" @if($productos->isEmpty()) style="display:none" @endif>

    <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
    <div class="overflow-x-auto">
    <table id="tabla-inventario" class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-3 py-2 font-semibold text-gray-600 text-xs">Producto</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs whitespace-nowrap">Unidad</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs whitespace-nowrap">Familia</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs whitespace-nowrap">Categoría</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs whitespace-nowrap">Marca</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs text-center whitespace-nowrap">Cont.</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs text-center whitespace-nowrap">Stock</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs text-center whitespace-nowrap">Mín.</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs text-center whitespace-nowrap">Crít.</th>
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs text-center whitespace-nowrap">Estado</th>
                @if(auth()->user()->esDev())
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs text-center">CC</th>
                @endif
                <th class="px-1.5 py-2 font-semibold text-gray-600 text-xs text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $producto)
            @php
            $estado = $producto->estadoStock();
            $rowClass = match($estado) {
                'critico' => 'bg-red-50',
                'minimo'  => 'bg-yellow-50',
                default   => 'bg-white hover:brightness-95',
            };
            @endphp
            @php
                $pendienteSalida = $producto->solicitudes->sum('cantidad');
                $esServicio = $producto->es_servicio;
            @endphp
            <tr class="{{ $rowClass }} transition"
                data-contenedor="{{ $producto->contenedor }}"
                data-estado="{{ $estado }}"
                data-tipo="{{ $esServicio ? 'servicio' : 'producto' }}"
                data-cc-id="{{ $producto->centro_costo_id }}"
                data-producto-id="{{ $producto->id }}">
                <td class="px-3 py-2 font-medium text-gray-900">
                    <div class="flex items-center gap-2">
                        @if($pendienteSalida > 0)
                            @if(auth()->user()->esAdmin())
                            <a href="{{ route('admin.solicitudes') }}"
                               style="position:relative; display:inline-flex; align-items:center; cursor:pointer; text-decoration:none;"
                               onmouseenter="this.querySelector('span').style.display='block'"
                               onmouseleave="this.querySelector('span').style.display='none'">
                            @else
                            <span style="position:relative; display:inline-flex; align-items:center; cursor:default;"
                                  onmouseenter="this.querySelector('span').style.display='block'"
                                  onmouseleave="this.querySelector('span').style.display='none'">
                            @endif
                                <svg width="20" height="20" viewBox="0 0 10 10" style="flex-shrink:0;">
                                    <circle cx="5" cy="5" r="5" fill="#f59e0b">
                                        <animate attributeName="opacity" values="1;0.4;1" dur="1.5s" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                                <span style="display:none; position:absolute; left:14px; top:50%; transform:translateY(-50%); z-index:9999;
                                             white-space:nowrap; background:#1f2937; color:#fff; font-size:11px; font-weight:500;
                                             padding:5px 10px; border-radius:6px; box-shadow:0 4px 8px rgba(0,0,0,.35);">
                                    @php $umDisplay = $producto->unidadMedida?->abreviacion ?? $producto->unidad ?? 'u.' @endphp
                                    ⏳ {{ $pendienteSalida }} {{ $umDisplay }} de salida pendiente(s)<br>
                                    @foreach($producto->solicitudes as $sol)
                                        · {{ $sol->usuario->name ?? '—' }}: {{ $sol->cantidad }} {{ $umDisplay }}<br>
                                    @endforeach
                                </span>
                            @if(auth()->user()->esAdmin())
                            </a>
                            @else
                            </span>
                            @endif
                        @endif

                        @if($esServicio)
                        <span class="inline-flex items-center gap-1 bg-violet-100 text-violet-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full whitespace-nowrap flex-shrink-0">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                            SERVICIO
                        </span>
                        @endif
                        <span>{{ $producto->nombre }}</span>
                    </div>
                </td>
                <td class="px-1.5 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $producto->unidadMedida?->nombre ?? $producto->unidad ?? '—' }}</td>
                <td class="px-1.5 py-2 text-gray-500 text-xs">{{ $producto->categoria->familia->nombre ?? '—' }}</td>
                <td class="px-1.5 py-2 text-gray-500 text-xs">{{ $producto->categoria->nombre ?? '—' }}</td>
                <td class="px-1.5 py-2 text-xs whitespace-nowrap">
                    @if($producto->marca)
                        <span class="inline-block bg-indigo-50 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $producto->marca->nombre }}</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-1.5 py-2" style="text-align:center; vertical-align:middle;">
                    @if($producto->container)
                        @if(auth()->user()->esAdmin())
                            <a href="{{ route('admin.containers.index') }}#container-{{ $producto->container->id }}"
                               style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:9999px; text-decoration:none;">
                                {{ strtoupper(substr($producto->container->nombre, 0, 1)) . preg_replace('/\D/', '', $producto->container->nombre) }}
                            </a>
                        @else
                            <span style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:9999px;">
                                {{ strtoupper(substr($producto->container->nombre, 0, 1)) . preg_replace('/\D/', '', $producto->container->nombre) }}
                            </span>
                        @endif
                    @else
                        <span style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:9999px;">—</span>
                    @endif
                </td>
                <td class="px-1.5 py-2 text-center font-bold text-xs
                        {{ $estado === 'critico' ? 'text-red-700' : ($estado === 'minimo' ? 'text-yellow-700' : 'text-gray-400') }}">
                    @if($esServicio)
                        —
                    @else
                        {{ $producto->stock_actual }}
                    @endif
                </td>
                <td class="px-1.5 py-2 text-center text-gray-400 text-xs whitespace-nowrap">
                    @if($esServicio)
                        —
                    @elseif($estado === 'minimo')
                        <span class="inline-block px-1.5 py-0.5 rounded-full estado-pulso-minimo">{{ $producto->stock_minimo }}</span>
                    @else
                        {{ $producto->stock_minimo }}
                    @endif
                </td>
                <td class="px-1.5 py-2 text-center text-gray-400 text-xs whitespace-nowrap">
                    @if($esServicio)
                        —
                    @elseif($estado === 'critico')
                        <span class="inline-block px-1.5 py-0.5 rounded-full estado-pulso-critico">{{ $producto->stock_critico }}</span>
                    @else
                        {{ $producto->stock_critico }}
                    @endif
                </td>
                <td class="px-1.5 py-2 text-center">
                    @if($estado === 'critico')
                    <span style="position:relative; display:inline-flex; cursor:default;"
                          onmouseenter="this.querySelector('.tt').style.display='block'"
                          onmouseleave="this.querySelector('.tt').style.display='none'">
                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full estado-pulso-critico">
                            <svg width="7" height="7" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="#ef4444"><animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/></circle></svg>
                            Crítico
                        </span>
                        @if($producto->stock_critico_desde)
                        <span class="tt" style="display:none; position:absolute; left:50%; bottom:calc(100% + 6px); transform:translateX(-50%); z-index:9999;
                                     white-space:nowrap; background:#1f2937; color:#fff; font-size:11px; font-weight:500;
                                     padding:5px 10px; border-radius:6px; box-shadow:0 4px 8px rgba(0,0,0,.35);">
                            🔴 Crítico desde {{ $producto->stock_critico_desde->format('d/m/Y H:i') }}
                        </span>
                        @endif
                    </span>
                    @elseif($estado === 'minimo')
                    <span style="position:relative; display:inline-flex; cursor:default;"
                          onmouseenter="this.querySelector('.tt').style.display='block'"
                          onmouseleave="this.querySelector('.tt').style.display='none'">
                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs font-semibold px-2 py-0.5 rounded-full estado-pulso-minimo">
                            <svg width="7" height="7" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="#eab308"><animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/></circle></svg>
                            Mínimo
                        </span>
                        @if($producto->stock_minimo_desde)
                        <span class="tt" style="display:none; position:absolute; left:50%; bottom:calc(100% + 6px); transform:translateX(-50%); z-index:9999;
                                     white-space:nowrap; background:#1f2937; color:#fff; font-size:11px; font-weight:500;
                                     padding:5px 10px; border-radius:6px; box-shadow:0 4px 8px rgba(0,0,0,.35);">
                            🟡 Mínimo desde {{ $producto->stock_minimo_desde->format('d/m/Y H:i') }}
                        </span>
                        @endif
                    </span>
                    @elseif($estado === 'servicio')
                    <span class="inline-flex items-center gap-1 bg-violet-100 text-violet-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                        Servicio
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Normal
                    </span>
                    @endif
                </td>
                @if(auth()->user()->esDev())
                <td class="px-1.5 py-2 text-center">
                    <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">
                        {{ $producto->centroCosto?->acronimo ?? '—' }}
                    </span>
                </td>
                @endif
                <td class="px-1.5 py-2 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                        @if(auth()->user()->esAdmin())
                        {{-- Ver detalle --}}
                        <a href="{{ route('admin.productos.show', $producto->id) }}"
                           title="Ver detalle"
                           class="p-act p-act-ver">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        {{-- Modificar stock --}}
                        <a href="{{ route('admin.productos.editar', $producto->id) }}"
                           title="Modificar stock"
                           class="p-act p-act-edit">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        {{-- Trasladar container --}}
                        <button type="button"
                            title="Trasladar container"
                            onclick="abrirModalTrasladar({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->contenedor }})"
                            class="p-act p-act-move">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </button>
                        @else
                        {{-- Solicitar salida --}}
                        <button type="button"
                            title="{{ $esServicio ? 'Servicio — sin stock físico' : 'Solicitar salida' }}"
                            @if(!$esServicio) onclick="abrirModal({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', 'salida', {{ $producto->stock_actual }}, {{ $producto->solicitudes->sum('cantidad') }}, '{{ addslashes($producto->unidadMedida?->abreviacion ?? $producto->unidad ?? 'u.') }}', '{{ $producto->tienePresentacion() ? addslashes($producto->cantidadVisual($producto->stock_actual)) : '' }}')" @endif
                            class="p-act p-act-out"
                            {{ ($esServicio || $producto->stock_actual <= 0) ? 'disabled' : '' }}>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7 7m0 0l7-7m-7 7V3"/>
                            </svg>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>

</div>{{-- /tab-panel-productos --}}

{{-- ═══ PANEL: SERVICIOS ══════════════════════════════════════════════════ --}}
<div id="tab-panel-servicios" style="display:none;">
@php
    use App\Models\ServicioEstado as SE;
    $estadoOrden = ['pendiente','aprobado','en_proceso','ejecutado','validado','cerrado','cancelado'];
@endphp

{{-- Barra de búsqueda servicios --}}
<div class="mb-3 flex items-center gap-2">
    <input id="buscador-servicios" type="text" placeholder="🔍  Buscar por nombre, categoría, estado..."
           class="flex-1 px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white">
</div>

{{-- Estado vacío --}}
@if($servicios->isEmpty())
<div class="bg-white rounded-xl shadow border border-gray-100 flex flex-col items-center justify-center text-center gap-5 mb-6"
     style="min-height:300px; padding:3rem 2rem;">
    <svg class="w-14 h-14 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
    </svg>
    <div class="max-w-sm">
        <p class="text-lg font-semibold text-gray-700">No hay servicios registrados</p>
        <p class="text-sm text-gray-400 mt-2 leading-relaxed">
            Los servicios se crean desde el Catálogo seleccionando la familia <strong>SERVICIOS</strong>.
        </p>
    </div>
</div>
@else

<div class="bg-white rounded-xl shadow overflow-hidden" @if($servicios->isEmpty()) style="display:none" @endif>
<div class="overflow-x-auto">
<table id="tabla-servicios" class="w-full text-sm">
    <thead class="bg-gray-50 text-left">
        <tr>
            <th class="px-3 py-2 font-semibold text-gray-600 text-xs">Servicio</th>
            <th class="px-2 py-2 font-semibold text-gray-600 text-xs">Categoría</th>
            @if(auth()->user()->esDev())
            <th class="px-2 py-2 font-semibold text-gray-600 text-xs">CC</th>
            @endif
            <th class="px-2 py-2 font-semibold text-gray-600 text-xs text-center" style="min-width:130px;">Estado operacional</th>
            <th class="px-2 py-2 font-semibold text-gray-600 text-xs text-center" style="min-width:120px;">Avance</th>
            <th class="px-2 py-2 font-semibold text-gray-600 text-xs text-center whitespace-nowrap">Responsable</th>
            <th class="px-2 py-2 font-semibold text-gray-600 text-xs text-center whitespace-nowrap">Actualizado</th>
            <th class="px-2 py-2 font-semibold text-gray-600 text-xs text-center">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($servicios as $serv)
        @php
            $ultimoSE    = $serv->servicioEstados->last();
            $estadoActual = $ultimoSE?->estado ?? 'pendiente';
            $progreso    = SE::progreso($estadoActual);
            $colores     = SE::colores($estadoActual);
            $label       = SE::label($estadoActual);
            $siguiente   = SE::flujoSiguiente($estadoActual);
            $timeline    = $serv->servicioEstados->map(fn($s) => [
                'estado'   => $s->estado,
                'label'    => SE::label($s->estado),
                'usuario'  => $s->usuario?->name ?? '—',
                'obs'      => $s->observacion,
                'fecha'    => $s->created_at->format('d/m/Y H:i'),
                'colores'  => SE::colores($s->estado),
            ])->values()->toArray();
        @endphp
        <tr class="border-b border-gray-100 hover:bg-violet-50/30 transition"
            data-nombre="{{ strtolower($serv->nombre) }}"
            data-categoria="{{ strtolower($serv->categoria?->nombre ?? '') }}"
            data-estado="{{ $estadoActual }}">
            <td class="px-3 py-2.5 font-medium text-gray-900">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 bg-violet-100 text-violet-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full whitespace-nowrap flex-shrink-0">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                        SERV.
                    </span>
                    <span>{{ $serv->nombre }}</span>
                </div>
            </td>
            <td class="px-2 py-2.5 text-xs text-gray-500">
                <div>{{ $serv->categoria?->familia?->nombre ?? '—' }}</div>
                <div class="text-gray-400">{{ $serv->categoria?->nombre ?? '—' }}</div>
            </td>
            @if(auth()->user()->esDev())
            <td class="px-2 py-2.5 text-center">
                <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">
                    {{ $serv->centroCosto?->acronimo ?? '—' }}
                </span>
            </td>
            @endif
            <td class="px-2 py-2.5 text-center">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                      style="background:{{ $colores['bg'] }}; color:{{ $colores['text'] }};">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:{{ $colores['dot'] }};"></span>
                    {{ $label }}
                </span>
            </td>
            <td class="px-2 py-2.5">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold" style="color:{{ $colores['text'] }};">{{ $progreso }}%</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-gray-200 overflow-hidden" style="min-width:90px;">
                        <div class="h-full rounded-full transition-all"
                             style="width:{{ $progreso }}%; background:{{ $colores['barra'] }};"></div>
                    </div>
                </div>
            </td>
            <td class="px-2 py-2.5 text-center text-xs text-gray-500">
                {{ $ultimoSE?->usuario?->name ?? '—' }}
            </td>
            <td class="px-2 py-2.5 text-center text-xs text-gray-400 whitespace-nowrap">
                {{ $ultimoSE?->created_at?->format('d/m/Y') ?? $serv->created_at->format('d/m/Y') }}
            </td>
            <td class="px-2 py-2.5 text-center">
                <div class="flex items-center justify-center gap-1.5">
                    @if(auth()->user()->esAdmin() && $estadoActual !== 'cerrado' && $estadoActual !== 'cancelado')
                    <button type="button"
                            title="Gestionar Estado"
                            onclick="abrirModalServicio({{ $serv->id }}, {{ json_encode($serv->nombre) }}, {{ json_encode($estadoActual) }}, {{ json_encode($siguiente) }}, {{ json_encode($timeline) }})"
                            class="p-act p-act-move" style="width:auto; padding:0.25rem 0.6rem; gap:0.3rem; font-size:0.7rem; font-weight:600; white-space:nowrap;">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        Estado
                    </button>
                    @elseif(auth()->user()->esAdmin())
                    <span class="text-xs text-gray-400 italic">{{ $estadoActual === 'cerrado' ? 'Cerrado' : 'Cancelado' }}</span>
                    @endif
                    <a href="{{ route('admin.reportes.bincard', $serv->id) }}"
                       title="BINCARD Operacional"
                       class="p-act p-act-ver">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
@endif

</div>{{-- /tab-panel-servicios --}}

{{-- ═══ MODAL: GESTIONAR ESTADO SERVICIO ═════════════════════════════════ --}}
@if(auth()->user()->esAdmin())
<div id="modal-gestionar-estado"
     style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,0.55); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 24px 64px rgba(0,0,0,.3); width:100%; max-width:520px; max-height:90vh; overflow-y:auto; position:relative;">

        {{-- Header --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.25rem 1.5rem; border-bottom:1px solid #f3f4f6; position:sticky; top:0; background:#fff; z-index:1;">
            <div>
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                    <span style="display:inline-flex; align-items:center; gap:0.3rem; background:#f3e8ff; color:#7c3aed; font-size:0.7rem; font-weight:700; padding:0.15rem 0.6rem; border-radius:9999px;">
                        <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                        SERVICIO
                    </span>
                </div>
                <h3 id="ge-titulo" style="font-size:1rem; font-weight:700; color:#1f2937; margin:0; word-break:break-word;"></h3>
            </div>
            <button type="button" onclick="cerrarModalServicio()" style="flex-shrink:0; color:#9ca3af; background:none; border:none; cursor:pointer; font-size:1.25rem; line-height:1; padding:0.1rem;">✕</button>
        </div>

        <div style="padding:1.25rem 1.5rem;">

            {{-- Estado actual + progreso --}}
            <div style="margin-bottom:1rem; padding:0.75rem; background:#f8fafc; border-radius:0.6rem; border:1px solid #e2e8f0;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem;">
                    <span style="font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em;">Estado actual</span>
                    <span id="ge-badge-estado" style="font-size:0.8rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:9999px; display:inline-flex; align-items:center; gap:0.4rem;"></span>
                </div>
                <div style="background:#e2e8f0; border-radius:9999px; height:6px; overflow:hidden;">
                    <div id="ge-barra" style="height:100%; border-radius:9999px; transition:width .4s ease;"></div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:0.3rem;">
                    <span id="ge-pct" style="font-size:0.7rem; color:#64748b; font-weight:600;"></span>
                    <span style="font-size:0.7rem; color:#9ca3af;">Completado</span>
                </div>
            </div>

            {{-- Timeline --}}
            <div style="margin-bottom:1rem;">
                <p style="font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; margin-bottom:0.6rem;">Historial operacional</p>
                <div id="ge-timeline" style="display:flex; flex-direction:column; gap:0;">
                    {{-- filled by JS --}}
                </div>
            </div>

            {{-- Formulario avanzar estado --}}
            <div style="border-top:1px solid #f1f5f9; padding-top:1rem;">
                <p style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:0.75rem;">Avanzar estado</p>
                <form id="form-gestionar-estado" method="POST" action="">
                    @csrf
                    <input type="hidden" name="estado" id="ge-estado-input">

                    {{-- Botones de transición --}}
                    <div id="ge-opciones-estado" style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.75rem;"></div>

                    {{-- Observación --}}
                    <div style="margin-bottom:0.75rem;">
                        <label style="display:block; font-size:0.8125rem; font-weight:600; color:#374151; margin-bottom:0.35rem;">
                            Observación <span style="font-size:0.72rem; color:#9ca3af; font-weight:400;">(opcional)</span>
                        </label>
                        <textarea name="observacion" id="ge-observacion" rows="3" maxlength="500"
                                  style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem; font-size:0.8rem; box-sizing:border-box; resize:vertical; outline:none;"
                                  placeholder="Describe el avance, resultado u observación relevante..."></textarea>
                    </div>

                    <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                        <button type="button" onclick="cerrarModalServicio()"
                                style="padding:0.45rem 1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:0.5rem; cursor:pointer;">
                            Cancelar
                        </button>
                        <button type="submit" id="ge-btn-submit"
                                style="padding:0.45rem 1.25rem; font-size:0.875rem; font-weight:600; color:#fff; background:#7c3aed; border:none; border-radius:0.5rem; cursor:pointer; display:none;">
                            Confirmar cambio
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endif

{{-- Modal solicitud (solo usuarios) --}}
@if(!auth()->user()->esAdmin())
<div id="modal-solicitud"
    class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div>
                <h3 class="text-lg font-semibold text-gray-800" id="modal-titulo">Solicitar movimiento</h3>
                <p class="text-sm text-gray-500" id="modal-subtitulo"></p>
            </div>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 transition cursor-pointer">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('solicitudes.store') }}" id="form-solicitud" novalidate>
            @csrf
            <input type="hidden" name="producto_id" id="modal-producto-id">
            <input type="hidden" name="tipo" id="modal-tipo">

            <div class="px-6 py-4 space-y-4">
                {{-- Stock actual info --}}
                <div class="bg-gray-50 rounded-lg px-4 py-2.5 text-sm text-gray-600 flex justify-between">
                    <span>Stock disponible:</span>
                    <span class="font-bold text-gray-800" id="modal-stock">—</span>
                </div>

                {{-- Advertencia pendientes --}}
                <div id="modal-aviso-pendiente" style="display:none;"></div>

                {{-- Cantidad --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cantidad <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="cantidad" id="modal-cantidad"
                        min="1" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Ej: 10">
                </div>

                {{-- Motivo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Motivo <span class="text-red-500">*</span>
                    </label>
                    <textarea name="motivo" id="modal-motivo"
                        rows="3" required maxlength="500"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Describe el motivo de esta solicitud..."></textarea>
                </div>

                <p id="modal-error" class="hidden px-2 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg"></p>
            </div>

            <div class="px-6 py-4 flex gap-3 justify-end" style="border-top:1px solid #f3f4f6;">
                <button type="button" onclick="cerrarModal()"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit" id="modal-btn-submit"
                    class="btn-primary px-4 py-2 text-sm font-medium text-white rounded-lg transition">
                    Enviar solicitud
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal confirmación descarte --}}
<div id="modal-descarte" style="display:none; position:fixed; inset:0; z-index:60; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.25); width:100%; max-width:360px; animation: traslado-in .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.5rem 1.5rem 1rem;">
            <p style="font-size:0.9375rem; font-weight:600; color:#1f2937; margin:0 0 0.25rem;">¿Descartar cambios?</p>
            <p style="font-size:0.875rem; color:#6b7280; margin:0;">Los datos ingresados se perderán.</p>
        </div>
        <div style="padding:0 1.5rem 1.25rem; display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button" onclick="cerrarDescarte()"
                class="btn-secondary"
                style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                Seguir editando
            </button>
            <button type="button" onclick="confirmarDescarte()"
                class="btn-danger"
                style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:500; color:#fff; background:#ef4444; border:none; border-radius:0.5rem; cursor:pointer;">
                Descartar
            </button>
        </div>
    </div>
</div>

{{-- Modal confirmación pendientes --}}
<div id="modal-confirmar-pendiente" style="display:none; position:fixed; inset:0; z-index:70; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.25); width:100%; max-width:400px; animation: traslado-in .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.5rem 1.5rem 0.75rem; display:flex; align-items:flex-start; gap:0.75rem;">
            <div style="flex-shrink:0; width:2rem; height:2rem; border-radius:9999px; background:#fef3c7; display:flex; align-items:center; justify-content:center; margin-top:0.1rem;">
                <svg style="width:1rem;height:1rem;color:#d97706;" fill="none" stroke="#d97706" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <div>
                <p style="font-size:0.9375rem; font-weight:600; color:#1f2937; margin:0 0 0.35rem;">Solicitudes pendientes sin confirmar</p>
                <p style="font-size:0.8125rem; color:#6b7280; margin:0;" id="modal-confirmar-texto">
                    Existen solicitudes pendientes para este producto. Sin un administrador disponible para verificar el stock real, la solicitud podría no ser atendida.
                </p>
            </div>
        </div>
        <div style="padding:1rem 1.5rem 1.25rem; display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button" onclick="cancelarConfirmarPendiente()"
                class="btn-secondary"
                style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                Cancelar
            </button>
            <button type="button" onclick="confirmarPendiente()"
                class="btn-primary"
                style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:500; color:#fff; background:#f97316; border:none; border-radius:0.5rem; cursor:pointer;">
                Enviar de todas formas
            </button>
        </div>
    </div>
</div>

<script>
    function mostrarErrorModal(msg) {
        var el = document.getElementById('modal-error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function ocultarErrorModal() {
        document.getElementById('modal-error').classList.add('hidden');
    }

    function tieneDatos() {
        var cantidad = document.getElementById('modal-cantidad').value.trim();
        var motivo   = document.getElementById('modal-motivo').value.trim();
        return cantidad !== '' || motivo !== '';
    }

    function cerrarDescarte() {
        document.getElementById('modal-descarte').style.display = 'none';
    }

    function confirmarDescarte() {
        document.getElementById('modal-descarte').style.display = 'none';
        document.getElementById('modal-solicitud').classList.add('hidden');
        ocultarErrorModal();
    }

    var _pendienteActual = 0;
    var _submitForzado   = false;
    var _unidad          = 'u.';

    function cancelarConfirmarPendiente() {
        document.getElementById('modal-confirmar-pendiente').style.display = 'none';
    }

    function confirmarPendiente() {
        document.getElementById('modal-confirmar-pendiente').style.display = 'none';
        _submitForzado = true;
        document.getElementById('form-solicitud').requestSubmit();
    }

    function abrirModal(productoId, nombre, tipo, stockActual, pendiente, unidad, visualStock) {
        _pendienteActual = pendiente || 0;
        _submitForzado   = false;
        _unidad          = unidad || 'u.';
        document.getElementById('modal-producto-id').value = productoId;
        document.getElementById('modal-tipo').value = tipo;
        document.getElementById('modal-cantidad').value = '';
        document.getElementById('modal-motivo').value = '';
        document.getElementById('modal-stock').textContent = stockActual + ' ' + unidad;
        ocultarErrorModal();

        var aviso = document.getElementById('modal-aviso-pendiente');
        if (pendiente > 0) {
            var critico = pendiente >= stockActual;
            aviso.style.display = 'block';
            aviso.innerHTML =
                '<div style="display:flex; align-items:flex-start; gap:0.5rem; padding:0.6rem 0.75rem; border-radius:0.5rem; font-size:0.8125rem; font-weight:500;'
                + (critico
                    ? 'background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c;'
                    : 'background:#fffbeb; border:1px solid #fcd34d; color:#92400e;')
                + '">'
                + '<svg style="width:14px;height:14px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">'
                + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>'
                + '</svg>'
                + '<span>Hay <strong>' + pendiente + '</strong> ' + _unidad + ' con solicitudes pendientes de aprobación'
                + (critico ? ' — superan el stock disponible.' : '.') + '</span>'
                + '</div>';
        } else {
            aviso.style.display = 'none';
        }

        document.getElementById('modal-titulo').textContent = 'Solicitar Salida — ' + nombre;
        document.getElementById('modal-subtitulo').textContent = 'Retirar ' + _unidad + ' del inventario';
        document.getElementById('modal-btn-submit').className =
            'btn-primary px-4 py-2 text-sm font-medium text-white rounded-lg transition bg-orange-500 hover:bg-orange-600';

        document.getElementById('modal-solicitud').classList.remove('hidden');
        document.getElementById('modal-cantidad').focus();
    }

    function cerrarModal() {
        if (tieneDatos()) {
            document.getElementById('modal-descarte').style.display = 'flex';
            return;
        }
        document.getElementById('modal-solicitud').classList.add('hidden');
        ocultarErrorModal();
    }


    // Validación del formulario antes de enviar
    document.getElementById('form-solicitud').addEventListener('submit', function(e) {
        const cantidad = parseInt(document.getElementById('modal-cantidad').value);
        const motivo = document.getElementById('modal-motivo').value.trim();
        const tipo = document.getElementById('modal-tipo').value;
        const stock = parseInt(document.getElementById('modal-stock').textContent);

        if (!cantidad || cantidad < 1) {
            e.preventDefault();
            mostrarErrorModal('La cantidad debe ser mayor a 0.');
            return;
        }
        if (!motivo) {
            e.preventDefault();
            mostrarErrorModal('El motivo es obligatorio.');
            return;
        }
        if (tipo === 'salida' && cantidad > stock) {
            e.preventDefault();
            mostrarErrorModal('La cantidad no puede superar el stock disponible.');
            return;
        }

        // Si hay pendientes y no se ha confirmado, mostrar aviso
        if (_pendienteActual > 0 && !_submitForzado) {
            e.preventDefault();
            var texto = 'Existen <strong>' + _pendienteActual + '</strong> ' + _unidad
                + ' con solicitudes pendientes para este producto. Sin un administrador disponible para verificar el stock real, la solicitud podría no ser atendida. ¿Desea continuar de todas formas?';
            document.getElementById('modal-confirmar-texto').innerHTML = texto;
            document.getElementById('modal-confirmar-pendiente').style.display = 'flex';
            return;
        }

        ocultarErrorModal();
    });
</script>
@endif

{{-- Modal traslado (solo admin) --}}
@if(auth()->user()->esAdmin())
<div id="modal-traslado"
    class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div id="modal-traslado-inner" class="bg-white rounded-2xl shadow-2xl w-full max-w-md" style="animation: traslado-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Trasladar container</h3>
                <p class="text-sm text-gray-500" id="traslado-subtitulo"></p>
            </div>
            <button onclick="cerrarModalTrasladar()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" id="form-traslado" action="" novalidate>
            @csrf

            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Container destino <span class="text-red-500">*</span>
                    </label>
                    <select name="contenedor_destino" id="traslado-destino" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Selecciona un container —</option>
                        @foreach($containers as $c)
                        <option value="{{ $c->id }}" class="opcion-traslado-destino">
                            {{ $c->nombre }}{{ $c->centroCosto ? ' — ' . $c->centroCosto->acronimo : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Motivo <span class="text-red-500">*</span>
                    </label>
                    <textarea name="motivo" id="traslado-motivo"
                        rows="3" maxlength="500"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                        placeholder="Describe el motivo del traslado..."></textarea>
                    <p id="traslado-motivo-error" class="text-red-500 text-xs mt-1 hidden">El motivo es obligatorio.</p>
                </div>
            </div>

            <div class="px-6 py-4 flex gap-3 justify-end" style="border-top:1px solid #f3f4f6;">
                <button type="button" onclick="pedirCancelarTrasladar()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button type="button" onclick="confirmarTraslado()"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    Confirmar traslado
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: confirmar cancelación traslado --}}
<div id="modal-traslado-cancelar"
    style="display:none; position:fixed; inset:0; z-index:10001; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:1rem;">
    <div id="modal-traslado-cancelar-inner" style="background:#fff; border-radius:0.75rem; box-shadow:0 20px 60px rgba(0,0,0,0.3); width:100%; max-width:420px; padding:1.5rem;">
        <h2 class="text-lg font-bold text-gray-800 mb-1">¿Cancelar traslado?</h2>
        <p class="text-sm text-gray-500 mb-6">Los datos ingresados se perderán. ¿Deseas cerrar de todas formas?</p>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="cerrarCancelarTrasladar()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Seguir editando
            </button>
            <button type="button" onclick="cerrarModalTrasladar(); cerrarCancelarTrasladar();"
                    class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                Sí, cancelar
            </button>
        </div>
    </div>
</div>

<script>
    function abrirModalTrasladar(productoId, nombre, contenedorActual) {
        document.getElementById('form-traslado').action = '/admin/productos/' + productoId + '/trasladar';
        document.getElementById('traslado-subtitulo').textContent = nombre + ' — Container actual: C' + contenedorActual;
        document.getElementById('traslado-motivo').value = '';
        document.getElementById('traslado-destino').value = '';

        // Ocultar el container actual del select
        document.querySelectorAll('.opcion-traslado-destino').forEach(function(opt) {
            opt.hidden = parseInt(opt.value) === contenedorActual;
        });

        var inner = document.getElementById('modal-traslado-inner');
        inner.style.animation = 'none';
        inner.offsetHeight;
        inner.style.animation = 'traslado-in .25s cubic-bezier(.22,.68,0,1.2) both';
        document.getElementById('modal-traslado').classList.remove('hidden');
    }

    function pedirCancelarTrasladar() {
        var destino = document.getElementById('traslado-destino').value;
        var motivo  = document.getElementById('traslado-motivo').value.trim();
        if (destino || motivo) {
            var m = document.getElementById('modal-traslado-cancelar');
            var inner = document.getElementById('modal-traslado-cancelar-inner');
            inner.style.animation = 'none';
            inner.offsetHeight;
            inner.style.animation = 'traslado-in .25s cubic-bezier(.22,.68,0,1.2) both';
            m.style.display = 'flex';
        } else {
            cerrarModalTrasladar();
        }
    }

    function cerrarCancelarTrasladar() {
        document.getElementById('modal-traslado-cancelar').style.display = 'none';
    }

    function confirmarTraslado() {
        const motivo = document.getElementById('traslado-motivo').value.trim();
        const destino = document.getElementById('traslado-destino').value;
        const errorMotivo = document.getElementById('traslado-motivo-error');

        if (!motivo) {
            errorMotivo.classList.remove('hidden');
            document.getElementById('traslado-motivo').focus();
            return;
        }
        errorMotivo.classList.add('hidden');
        document.getElementById('form-traslado').submit();
    }

    document.getElementById('traslado-motivo').addEventListener('input', function () {
        if (this.value.trim()) {
            document.getElementById('traslado-motivo-error').classList.add('hidden');
        }
    });

    function cerrarModalTrasladar() {
        document.getElementById('modal-traslado').classList.add('hidden');
        document.getElementById('form-traslado').reset();
        document.getElementById('traslado-motivo-error').classList.add('hidden');
    }

    document.getElementById('modal-traslado').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalTrasladar();
    });
</script>
@endif

{{-- Modal movido a gastos-menores/index.blade.php --}}
@if(false)
<div id="modal-gasto-menor"
     style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); overflow-y:auto;">
    <div style="min-height:100%; display:flex; align-items:flex-start; justify-content:center; padding:2rem 1rem;">
        <div class="gm-modal-inner" style="background:#fff; border-radius:1rem; width:100%; max-width:780px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">

            {{-- Header --}}
            <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb;">
                <div>
                    <p style="font-size:1rem; font-weight:700; color:#92400e;">Compra de Gasto Menor</p>
                    <p style="font-size:0.75rem; color:#6b7280; margin-top:0.1rem;">Registra la boleta y actualiza el stock de los productos comprados</p>
                </div>
                <button type="button" onclick="cerrarModalGastoMenor()"
                        style="color:#9ca3af; font-size:1.25rem; line-height:1; background:none; border:none; cursor:pointer;">✕</button>
            </div>

            <form method="POST" action="{{ route('admin.gastos-menores.store') }}"
                  enctype="multipart/form-data" id="form-gasto-menor">
                @csrf
                <div style="padding:1.25rem; display:flex; flex-direction:column; gap:1rem;">

                    {{-- Datos de boleta --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                RUT Proveedor <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" name="rut_proveedor" placeholder="Ej: 12.345.678-9" required
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Folio — Número de Boleta <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" name="folio" placeholder="Ej: 001234" required
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Fecha y hora de emisión <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="datetime-local" name="fecha_emision" required
                                   max="{{ date('Y-m-d\TH:i') }}"
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Boleta PDF <span style="font-weight:400; color:#9ca3af;">(opcional)</span>
                            </label>
                            <input type="file" name="documento" accept=".pdf"
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                        </div>
                    </div>

                    {{-- Buscador de productos --}}
                    <div style="border-top:1px solid #e5e7eb; padding-top:0.75rem;">
                        <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">
                            Agregar productos <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="position:relative;">
                            <input type="text" id="gm-buscador"
                                   placeholder="🔍 Buscar producto por nombre o descripción..."
                                   autocomplete="off"
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            <div id="gm-resultados"
                                 style="display:none; position:absolute; top:100%; left:0; right:0; z-index:10; background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 4px 16px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto; margin-top:2px;"></div>
                        </div>
                    </div>

                    {{-- Tabla de productos seleccionados --}}
                    <div id="gm-tabla-wrap" style="display:none;">
                        <table style="width:100%; font-size:0.78rem; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#fef3c7; color:#92400e;">
                                    <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600; border-radius:0.25rem 0 0 0;">Producto</th>
                                    <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:70px;">Cant.</th>
                                    <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:110px;">Monto ($)</th>
                                    <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:120px;">P. Neto s/IVA ($)</th>
                                    <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600; width:140px;">Contenedor</th>
                                    <th style="padding:0.4rem 0.6rem; width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="gm-items"></tbody>
                        </table>
                    </div>

                    <p id="gm-sin-items" style="font-size:0.75rem; color:#9ca3af; text-align:center; display:none;">
                        Agrega al menos un producto para continuar.
                    </p>

                </div>

                {{-- Footer --}}
                <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.5rem; padding:0.75rem 1.25rem; border-top:1px solid #e5e7eb; background:#fafafa; border-radius:0 0 1rem 1rem;">
                    <button type="button" onclick="cerrarModalGastoMenor()"
                            style="padding:0.4rem 1rem; font-size:0.8rem; font-weight:600; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="gm-btn-submit"
                            style="padding:0.4rem 1.1rem; font-size:0.8rem; font-weight:600; color:#fff; background:#d97706; border:none; border-radius:0.5rem; cursor:pointer; transition:opacity .15s;">
                        Registrar compra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Datos de productos y contenedores (JSON) ──────────────────────────────────
const gmProductos    = {!! json_encode($productos->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'stock' => $p->stock_actual])->values(), JSON_HEX_TAG | JSON_HEX_AMP) !!};
const gmContainers   = {!! json_encode($containers->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])->values(), JSON_HEX_TAG | JSON_HEX_AMP) !!};

let gmItems   = [];   // { idx, id, nombre }
let gmCounter = 0;

// ── Modal ─────────────────────────────────────────────────────────────────────
document.getElementById('btn-abrir-gasto-menor').addEventListener('click', function() {
    const modal = document.getElementById('modal-gasto-menor');
    const inner = modal.querySelector('.gm-modal-inner');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    // Reiniciar animación
    inner.style.animation = 'none';
    inner.offsetHeight; // reflow
    inner.style.animation = '';
});
function cerrarModalGastoMenor() {
    document.getElementById('modal-gasto-menor').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('modal-gasto-menor').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalGastoMenor();
});

// ── Buscador ──────────────────────────────────────────────────────────────────
document.getElementById('gm-buscador').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    const res = document.getElementById('gm-resultados');
    if (q.length < 1) { res.style.display = 'none'; return; }

    const matches = gmProductos.filter(p =>
        p.nombre.toLowerCase().includes(q)
    ).slice(0, 10);

    if (!matches.length) { res.style.display = 'none'; return; }

    res.innerHTML = matches.map(p => `
        <div onclick="gmAgregar(${p.id}, \`${p.nombre.replace(/`/g,'')}\`)"
             style="padding:0.5rem 0.75rem; cursor:pointer; border-bottom:1px solid #f3f4f6; transition:background .1s;"
             onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background=''">
            <p style="font-size:0.8rem; font-weight:600; color:#1f2937;">${escHtmlGm(p.nombre)}</p>
            <p style="font-size:0.72rem; color:#6b7280;">Stock: ${p.stock}</p>
        </div>
    `).join('');
    res.style.display = 'block';
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#gm-buscador') && !e.target.closest('#gm-resultados')) {
        document.getElementById('gm-resultados').style.display = 'none';
    }
});

// ── Agregar producto ──────────────────────────────────────────────────────────
function gmAgregar(id, nombre) {
    if (gmItems.find(i => i.id === id)) {
        document.getElementById('gm-buscador').value = '';
        document.getElementById('gm-resultados').style.display = 'none';
        return;
    }
    const idx = gmCounter++;
    gmItems.push({ idx, id, nombre });
    gmRenderFila(idx, id, nombre);
    document.getElementById('gm-buscador').value = '';
    document.getElementById('gm-resultados').style.display = 'none';
    gmActualizarTabla();
}

function gmRenderFila(idx, id, nombre) {
    const tbody = document.getElementById('gm-items');
    const tr = document.createElement('tr');
    tr.id = `gm-row-${idx}`;
    tr.style.borderBottom = '1px solid #f3f4f6';
    const contOptions = gmContainers.map(c =>
        `<option value="${c.id}">${escHtmlGm(c.nombre)}</option>`
    ).join('');
    tr.innerHTML = `
        <td style="padding:0.4rem 0.6rem;">
            <input type="hidden" name="items[${idx}][producto_id]" value="${id}">
            <span style="font-size:0.8rem; font-weight:500; color:#1f2937;">${escHtmlGm(nombre)}</span>
        </td>
        <td style="padding:0.4rem 0.4rem; text-align:center;">
            <input type="number" name="items[${idx}][cantidad]" value="1" min="1" required
                   style="width:60px; text-align:center; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.3rem 0.4rem; font-size:0.8rem;">
        </td>
        <td style="padding:0.4rem 0.4rem; text-align:center;">
            <input type="number" name="items[${idx}][monto]" placeholder="0" min="0" step="1" required
                   style="width:95px; text-align:center; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.3rem 0.4rem; font-size:0.8rem;">
        </td>
        <td style="padding:0.4rem 0.4rem; text-align:center;">
            <input type="number" name="items[${idx}][precio_neto]" placeholder="0" min="0" step="1"
                   style="width:105px; text-align:center; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.3rem 0.4rem; font-size:0.8rem;">
        </td>
        <td style="padding:0.4rem 0.4rem;">
            <select name="items[${idx}][contenedor_id]"
                    style="width:100%; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.3rem 0.4rem; font-size:0.78rem; background:#fff;">
                ${contOptions}
            </select>
        </td>
        <td style="padding:0.4rem 0.3rem; text-align:center;">
            <button type="button" onclick="gmQuitar(${idx})"
                    style="color:#ef4444; background:none; border:none; cursor:pointer; font-size:1rem; line-height:1;">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function gmQuitar(idx) {
    gmItems = gmItems.filter(i => i.idx !== idx);
    const row = document.getElementById(`gm-row-${idx}`);
    if (row) row.remove();
    gmActualizarTabla();
}

function gmActualizarTabla() {
    const wrap = document.getElementById('gm-tabla-wrap');
    const sin  = document.getElementById('gm-sin-items');
    wrap.style.display = gmItems.length ? '' : 'none';
    sin.style.display  = gmItems.length ? 'none' : '';
}

function escHtmlGm(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
@endif

@if(auth()->user()->esAdmin())
{{-- ══ MODAL AGREGAR INVENTARIO ══ --}}
<div id="modal-agregar-inv"
    style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); overflow-y:auto;">
    <div style="min-height:100%; display:flex; align-items:flex-start; justify-content:center; padding:2rem 1rem;">
        <div class="ai-modal-inner" style="background:#fff; border-radius:1rem; width:100%; max-width:820px; box-shadow:0 20px 60px rgba(0,0,0,0.3); position:relative;">

            {{-- Header --}}
            <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb;">
                <div>
                    <p style="font-size:1rem; font-weight:700; color:#1e40af;">Agregar Inventario</p>
                    <p style="font-size:0.75rem; color:#6b7280; margin-top:0.1rem;">Registra una entrada por boleta local o documento SICD externo</p>
                </div>
                <button type="button" onclick="cerrarConConfirmacion()"
                    style="color:#9ca3af; font-size:1.25rem; line-height:1; background:none; border:none; cursor:pointer;">✕</button>
            </div>

            <form method="POST" action="" id="form-agregar-inv" enctype="multipart/form-data"
                onsubmit="return false;"
                data-url-local="{{ route('admin.gastos-menores.store') }}"
                data-url-externa="{{ route('admin.sicd.recibir.directo') }}"
                data-url-masiva="{{ route('admin.productos.carga.masiva') }}"
                data-url-manual="{{ route('admin.productos.carga.manual') }}">
                <input type="hidden" name="_modo" value="nuevo">
                <input type="hidden" name="confirmar_duplicado" id="ai-confirmar-duplicado" value="0">
                <input type="hidden" name="sicd_preenlazado_id" id="ai-sicd-preenlazado-id" value="">
                @csrf
                <div style="padding:1.25rem; display:flex; flex-direction:column; gap:1rem;">


                    {{-- Selector de tipo --}}
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                            Tipo de ingreso <span style="color:#ef4444;">*</span>
                        </label>
                        <select id="ai-tipo" name="_tipo"
                            style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem; font-size:0.8rem; box-sizing:border-box; background:#fff;"
                            onchange="aiCambiarTipo(this.value)">
                            <option value="">— Selecciona el tipo de ingreso —</option>
                            <optgroup label="Licitación">
                                <option value="licitacion">Licitación</option>
                            </optgroup>
                            <optgroup label="Gasto Menor">
                                <option value="externa">Externa (Documento SICD)</option>
                                <option value="local">Local (Boleta de compra)</option>
                            </optgroup>
                        </select>
                    </div>

                    {{-- ══ SECCIÓN LOCAL ══ --}}
                    <div id="ai-seccion-local" style="display:none; flex-direction:column; gap:1rem;">

                        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:0.5rem; padding:0.5rem 0.75rem;">
                            <p style="font-size:0.72rem; color:#92400e; font-weight:600; margin:0;">
                                📄 Los productos se sumarán al stock del inventario al registrar la boleta.
                            </p>
                        </div>

                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Nombre del Proveedor <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" name="proveedor_nombre" id="ai-prov-nombre" placeholder="Ej: COMERCIALIZADORA TECNO SUR SPA"
                                style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box; text-transform:uppercase;">
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    RUT Proveedor <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="text" name="rut_proveedor" id="ai-rut" placeholder="Ej: 12.345.678-9"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Folio — Número de Boleta <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="text" name="folio" id="ai-folio" placeholder="Ej: 001234"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Fecha y hora de emisión <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="datetime-local" name="fecha_emision" id="ai-fecha"
                                    max="{{ date('Y-m-d\TH:i') }}"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Boleta PDF <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="file" name="documento" id="ai-doc" accept=".pdf"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                            </div>
                        </div>

                        <div style="border-top:1px solid #e5e7eb; padding-top:0.75rem;">
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">
                                Productos <span style="color:#ef4444;">*</span>
                            </label>
                            <div style="position:relative;">
                                <input type="text" id="ai-buscador"
                                    placeholder="🔍 Buscar producto por nombre o descripción..."
                                    autocomplete="off"
                                    oninput="this.value=this.value.toUpperCase()"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                                <div id="ai-resultados"
                                    style="display:none; position:absolute; top:100%; left:0; right:0; z-index:10; background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 4px 16px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto; margin-top:2px;"></div>
                            </div>
                        </div>

                        <div id="ai-tabla-wrap" style="display:none;">
                            <table style="width:100%; font-size:0.78rem; border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#f3e8ff; color:#6b21a8;">
                                        <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600;">Producto</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:80px;">Cantidad</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:80px;">Unidad</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:100px;">Precio Neto($)</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:110px;">Total Neto($)</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600; width:150px;">Contenedor</th>
                                        <th style="padding:0.4rem 0.6rem; width:60px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="ai-items"></tbody>
                            </table>
                        </div>
                        <p id="ai-sin-items" style="font-size:0.75rem; color:#9ca3af; text-align:center; display:none;">
                            Agrega al menos un producto para continuar.
                        </p>
                    </div>

                    {{-- ══ SECCIÓN EXTERNA ══ --}}
                    <div id="ai-seccion-externa" style="display:none; flex-direction:column; gap:0.75rem;">

                        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.5rem; padding:0.5rem 0.75rem;">
                            <p style="font-size:0.72rem; color:#1e40af; font-weight:600; margin:0;">
                                📋 El sistema actualizará el stock y marcará el SICD como recibido automáticamente.
                            </p>
                        </div>

                        {{-- Código SICD --}}
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Código SICD <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" name="codigo_sicd" id="ai-codigo-sicd" placeholder="Ej: TIC(S)/81"
                                style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            <span id="ai-codigo-hint" style="font-size:0.7rem; margin-top:0.35rem; display:flex; align-items:center; gap:0.3rem;"></span>
                            {{-- Advertencia leve: SICD ya ingresada --}}
                            <div id="ai-sicd-ya-ingresada" class="ai-warn-ya-ingresada" style="display:none; margin-top:0.4rem;">
                                <svg style="width:14px;height:14px;flex-shrink:0;color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <span style="font-size:0.72rem; font-weight:500;">Esta SICD ya fue ingresada · Estado: <strong id="ai-sicd-ya-estado-leve"></strong></span>
                            </div>
                            <div id="ai-sicd-info" style="display:none; margin-top:0.4rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.4rem; padding:0.35rem 0.6rem; font-size:0.72rem; color:#166534;"></div>
                        </div>


                        {{-- Sección de carga — oculta hasta que el código SICD sea válido --}}
                        <div id="ai-ext-carga-wrap" style="display:none; flex-direction:column; gap:0.75rem;">

                        {{-- Datos de boleta (ocultos para Licitación) --}}
                        <div id="ai-ext-boleta-datos">
                            <div style="margin-bottom:0.75rem;">
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Nombre del Proveedor <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="text" name="proveedor_nombre" id="ai-ext-prov-nombre" placeholder="Ej: COMERCIALIZADORA TECNO SUR SPA"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box; text-transform:uppercase;">
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
                                <div>
                                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                        RUT Proveedor <span style="color:#ef4444;">*</span>
                                    </label>
                                    <input type="text" name="rut_proveedor" id="ai-ext-rut" placeholder="Ej: 12.345.678-9"
                                        style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                                </div>
                                <div>
                                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                        Folio — Número de Boleta <span style="color:#ef4444;">*</span>
                                    </label>
                                    <input type="text" name="folio" id="ai-ext-folio" placeholder="Ej: 001234"
                                        style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                                </div>
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Fecha y hora de emisión <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="datetime-local" name="fecha_emision" id="ai-ext-fecha"
                                    max="{{ date('Y-m-d\TH:i') }}"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            </div>
                        </div>

                        {{-- Separador con selector de método de carga --}}
                        <div style="border-top:1px solid #e5e7eb; padding-top:0.75rem;">
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.35rem;">
                                Método de carga de productos <span style="color:#ef4444;">*</span>
                            </label>
                            <div style="display:flex; gap:0.5rem;">
                                <button type="button" id="ai-ext-btn-masiva"
                                    onclick="aiMetodoCarga('masiva')"
                                    style="flex:1; padding:0.45rem 0.5rem; font-size:0.78rem; font-weight:600; border:2px solid #2563eb; border-radius:0.5rem; background:#eff6ff; color:#1e40af; cursor:pointer; transition:all .15s;">
                                    📊 Carga masiva (Excel)
                                </button>
                                <button type="button" id="ai-ext-btn-manual"
                                    onclick="aiMetodoCarga('manual')"
                                    style="flex:1; padding:0.45rem 0.5rem; font-size:0.78rem; font-weight:600; border:2px solid #e5e7eb; border-radius:0.5rem; background:#fff; color:#6b7280; cursor:pointer; transition:all .15s;">
                                    ✏️ Carga manual
                                </button>
                            </div>
                        </div>

                        {{-- Panel Carga masiva --}}
                        <div id="ai-ext-panel-masiva" style="display:flex; flex-direction:column; gap:0.75rem;">
                            <div>
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.25rem;">
                                    <label style="font-size:0.75rem; font-weight:600; color:#374151;">
                                        Excel de productos <span style="color:#ef4444;">*</span>
                                        <span style="font-weight:400; color:#9ca3af;">(col A = descripción, col B = unidad, col C = cantidad — fila 1 es encabezado)</span>
                                    </label>
                                    <a href="{{ asset('templates/plantilla_carga_masiva.xlsx') }}" download
                                        style="font-size:0.78rem; font-weight:700; color:#fff; background:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:0.35rem; white-space:nowrap; padding:0.3rem 0.75rem; border-radius:0.4rem;">
                                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                                        </svg>
                                        Descargar plantilla
                                    </a>
                                </div>
                                <input type="file" name="excel_masivo" id="ai-excel-masivo" accept=".xlsx,.xls,.csv"
                                    style="display:none;"
                                    onchange="aiFileUpdate('ai-excel-masivo','ai-excel-masivo-txt',this)">
                                <label for="ai-excel-masivo" class="ai-file-lbl ai-file-lbl-xl">
                                    <svg class="ai-file-ico" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="ai-file-lbl-txt" id="ai-excel-masivo-txt">Seleccionar Excel (.xlsx, .xls, .csv)</span>
                                </label>
                            </div>
                            <div id="ai-boleta-masiva" style="display:flex; flex-direction:column; gap:0.25rem;">
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151;">
                                    Boleta / Factura <span style="color:#ef4444;">*</span>
                                    <span style="font-weight:400; color:#9ca3af;">(PDF)</span>
                                </label>
                                <input type="file" name="boleta_sicd" id="ai-boleta-masiva-input" accept=".pdf,.jpg,.jpeg,.png"
                                    style="display:none;"
                                    onchange="aiFileUpdate('ai-boleta-masiva-input','ai-boleta-masiva-txt',this)">
                                <label for="ai-boleta-masiva-input" class="ai-file-lbl">
                                    <svg class="ai-file-ico" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="ai-file-lbl-txt" id="ai-boleta-masiva-txt">Seleccionar PDF, JPG o PNG</span>
                                </label>
                            </div>
                            <input type="checkbox" name="vincular_oc" id="ai-vincular-oc" value="1" style="display:none;">
                        </div>

                        {{-- Panel Carga manual --}}
                        <div id="ai-ext-panel-manual" style="display:none; flex-direction:column; gap:0.75rem;">
                            <div style="position:relative;">
                                <input type="text" id="ai-buscador-manual"
                                    placeholder="🔍 Descripción del bien o servicio..."
                                    autocomplete="off"
                                    oninput="this.value=this.value.toUpperCase()"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                                <div id="ai-resultados-manual"
                                    style="display:none; position:absolute; top:100%; left:0; right:0; z-index:10; background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 4px 16px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto; margin-top:2px;"></div>
                            </div>
                            <div id="ai-boleta-manual" style="display:flex; flex-direction:column; gap:0.25rem;">
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151;">
                                    Boleta / Factura <span style="color:#ef4444;">*</span>
                                    <span style="font-weight:400; color:#9ca3af;">(PDF)</span>
                                </label>
                                <input type="file" name="boleta_sicd" id="ai-boleta-manual-input" accept=".pdf,.jpg,.jpeg,.png"
                                    style="display:none;"
                                    onchange="aiFileUpdate('ai-boleta-manual-input','ai-boleta-manual-txt',this)">
                                <label for="ai-boleta-manual-input" class="ai-file-lbl">
                                    <svg class="ai-file-ico" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="ai-file-lbl-txt" id="ai-boleta-manual-txt">Seleccionar PDF, JPG o PNG</span>
                                </label>
                            </div>
                            <div id="ai-tabla-manual-wrap" style="display:none;">
                                <table style="width:100%; font-size:0.78rem; border-collapse:collapse;">
                                    <thead>
                                        <tr style="background:#f3e8ff; color:#6b21a8;">
                                            <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600;">Producto</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:80px;">Cantidad</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:80px;">Unidad</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:100px;">Precio Neto($)</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:110px;">Total Neto($)</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600; width:150px;">Contenedor</th>
                                            <th style="padding:0.4rem 0.6rem; width:60px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="ai-items-manual"></tbody>
                                </table>
                            </div>
                            <p id="ai-sin-items-manual" style="font-size:0.75rem; color:#9ca3af; text-align:center; display:none;">
                                Agrega al menos un producto para continuar.
                            </p>
                            <input type="checkbox" name="vincular_oc_manual" id="ai-vincular-oc-manual" value="1" style="display:none;">
                        </div>

                        </div>{{-- /ai-ext-carga-wrap --}}

                    </div>

                </div>

                {{-- Toast de validación --}}
                <div id="ai-toast-error" class="ai-toast-err" style="display:none; margin:0 1.25rem 0.75rem; padding:0.6rem 1rem; background:#fef2f2; border:1px solid #fca5a5; border-radius:0.5rem; color:#991b1b; font-size:0.8rem; font-weight:500; align-items:center; gap:0.5rem;">
                    <span>⚠</span>
                    <span id="ai-toast-msg"></span>
                </div>

                {{-- Footer --}}
                <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.5rem; padding:0.75rem 1.25rem; border-top:1px solid #e5e7eb; background:#fafafa; border-radius:0 0 1rem 1rem;">
                    <button type="button" onclick="cerrarConConfirmacion()"
                        style="padding:0.4rem 1rem; font-size:0.8rem; font-weight:600; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="button" onclick="aiEnviar()" id="ai-btn-submit" disabled
                        style="padding:0.4rem 1.1rem; font-size:0.8rem; font-weight:600; color:#fff; background:#9ca3af; border:none; border-radius:0.5rem; cursor:not-allowed; transition:background .15s;">
                        Registrar
                    </button>
                </div>
            </form>

            {{-- Modal SICD ya ingresada --}}
            <div id="ai-modal-dup" style="display:none; position:absolute; inset:0; background:rgba(0,0,0,0.55); border-radius:1rem; z-index:20; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:1rem; padding:2rem; max-width:460px; width:92%; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:dupIn .25s cubic-bezier(.22,.68,0,1.2) both;">
                    {{-- Icono + título --}}
                    <div style="text-align:center; margin-bottom:1.25rem;">
                        <div style="width:3.5rem; height:3.5rem; border-radius:9999px; background:#fef3c7; display:flex; align-items:center; justify-content:center; margin:0 auto 0.75rem;">
                            <svg style="width:1.75rem;height:1.75rem;" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </div>
                        <p style="font-size:1.05rem; font-weight:700; color:#1e293b; margin:0;">SICD ya ingresada en el sistema</p>
                        <p style="font-size:0.82rem; color:#6b7280; margin:0.3rem 0 0;">Código: <strong id="ai-dup-codigo" style="color:#4f46e5; font-family:monospace;"></strong></p>
                    </div>
                    {{-- Cuerpo --}}
                    <div style="background:#fffbeb; border:1px solid #fcd34d; border-radius:0.625rem; padding:0.9rem 1rem; margin-bottom:1.25rem;">
                        <p style="font-size:0.85rem; color:#374151; margin:0 0 0.4rem; line-height:1.6;">
                            Esta SICD <strong>no puede ingresarse nuevamente</strong> porque ya existe en el sistema.
                        </p>
                        <p style="font-size:0.82rem; color:#92400e; margin:0 0 0.6rem;">
                            Estado actual: <strong id="ai-dup-estado"></strong>
                        </p>
                        <p style="font-size:0.82rem; color:#374151; margin:0; line-height:1.5;">
                            Si deseas <strong>agregar, editar o eliminar productos</strong> de esta SICD, puedes hacerlo desde el apartado de la SICD usando el botón <strong>"Ir a SICD"</strong>.
                        </p>
                    </div>
                    {{-- Botones --}}
                    <div style="display:flex; gap:0.6rem; justify-content:flex-end;">
                        <button type="button" onclick="document.getElementById('ai-modal-dup').style.display='none';"
                                style="padding:0.55rem 1.1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:0.5rem; cursor:pointer; transition:background .15s;"
                                onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                            Cerrar
                        </button>
                        <a id="ai-dup-ir-btn" href="#" target="_blank"
                           style="padding:0.55rem 1.25rem; font-size:0.875rem; font-weight:600; color:#fff; background:#4f46e5; border-radius:0.5rem; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; transition:background .15s;"
                           onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            Ir a SICD
                        </a>
                    </div>
                </div>
            </div>

            {{-- Confirmación salida con SICD enlazado --}}
            <div id="ai-confirm-salida" style="display:none; position:absolute; inset:0; background:rgba(0,0,0,0.45); border-radius:1rem; z-index:10; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:0.75rem; padding:1.5rem; max-width:360px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.2); text-align:center;">
                    <div style="font-size:2rem; margin-bottom:0.5rem;">⚠️</div>
                    <p style="font-size:0.95rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">¿Salir de Agregar Inventario?</p>
                    <p id="ai-confirm-salida-msg" style="font-size:0.8rem; color:#64748b; margin-bottom:1.25rem;">Si sales ahora, perderás los cambios y la acción de ingresar no se completará.</p>
                    <div style="display:flex; gap:0.6rem; justify-content:center;">
                        <button onclick="aiConfirmarSalida()" style="padding:0.45rem 1.1rem; font-size:0.8rem; font-weight:600; background:#ef4444; color:#fff; border:none; border-radius:0.5rem; cursor:pointer;">
                            Sí, salir
                        </button>
                        <button onclick="aiCancelarSalida()" style="padding:0.45rem 1.1rem; font-size:0.8rem; font-weight:600; background:#f1f5f9; color:#374151; border:none; border-radius:0.5rem; cursor:pointer;">
                            Quedarme
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@push('head')
<style>
    @keyframes traslado-in { from { opacity:0; transform:scale(.94); } to { opacity:1; transform:scale(1); } }
    @keyframes dupIn { from { opacity:0; transform:scale(.92) translateY(-16px); } to { opacity:1; transform:none; } }
    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes ai-spin { to { transform: rotate(360deg); } }
    @keyframes btn-breathe-green { 0%,100%{box-shadow:0 0 0 0 rgba(22,163,74,.7)} 50%{box-shadow:0 0 0 6px rgba(22,163,74,0)} }
    @keyframes btn-breathe-blue  { 0%,100%{box-shadow:0 0 0 0 rgba(37,99,235,.7)} 50%{box-shadow:0 0 0 6px rgba(37,99,235,0)} }
    @keyframes btn-breathe-red   { 0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.7)} 50%{box-shadow:0 0 0 6px rgba(220,38,38,0)} }

    .dt-btn-excel { background:#16a34a; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s, transform .15s; }
    .dt-btn-excel:hover { background:#15803d; transform:translateY(-1px); animation:btn-breathe-green 1.6s ease-in-out infinite; }

    .dt-btn { background:#2563eb; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s, transform .15s; }
    .dt-btn:hover { background:#1d4ed8; transform:translateY(-1px); animation:btn-breathe-blue 1.6s ease-in-out infinite; }

    .dt-btn-pdf { background:#dc2626; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s, transform .15s; }
    .dt-btn-pdf:hover { background:#b91c1c; transform:translateY(-1px); animation:btn-breathe-red 1.6s ease-in-out infinite; }

    /* ── Buscador manual (carga manual) dark mode ── */
    html.dark #ai-buscador-manual {
        background: #1e293b; border-color: #475569; color: #e2e8f0;
    }
    html.dark #ai-buscador-manual::placeholder { color: #64748b; }
    html.dark #ai-resultados-manual {
        background: #1e293b !important; border-color: #334155 !important;
        box-shadow: 0 4px 16px rgba(0,0,0,.45) !important;
    }
    /* ── File inputs estilizados (carga masiva/manual) ── */
    .ai-file-lbl {
        display:flex; align-items:center; gap:0.5rem;
        width:100%; padding:0.55rem 0.85rem; border-radius:0.5rem;
        border:2px dashed #d1d5db; background:#f9fafb; color:#6b7280;
        font-size:0.78rem; font-weight:500; cursor:pointer;
        transition:background .18s, border-color .18s, color .18s;
        box-sizing:border-box; overflow:hidden;
    }
    .ai-file-ico { width:15px; height:15px; flex-shrink:0; }
    .ai-file-lbl-txt { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .ai-file-lbl:hover { background:#f3f4f6; border-color:#9ca3af; color:#374151; }
    .ai-file-lbl.has-file { border-style:solid; color:#374151; }
    .ai-file-lbl-xl { border-color:#3b82f6; background:#eff6ff; color:#1d4ed8; }
    .ai-file-lbl-xl:hover { background:#dbeafe; border-color:#2563eb; color:#1e3a8a; }
    .ai-file-lbl-xl.has-file { color:#1e3a8a; }
    html.dark .ai-file-lbl { background:#1e293b; border-color:#475569; color:#94a3b8; }
    html.dark .ai-file-lbl:hover { background:#334155; border-color:#64748b; color:#cbd5e1; }
    html.dark .ai-file-lbl.has-file { color:#e2e8f0; }
    html.dark .ai-file-lbl-xl { background:#1e3a5f; border-color:#3b82f6; color:#93c5fd; }
    html.dark .ai-file-lbl-xl:hover { background:#172554; border-color:#60a5fa; color:#bfdbfe; }
    html.dark .ai-file-lbl-xl.has-file { color:#bfdbfe; }

    /* ── Paginación DataTables estilo Tailwind ── */
    .dt-paging { margin-top:1rem !important; display:flex !important; justify-content:flex-end !important; gap:4px !important; align-items:center !important; }
    .dt-paging button,
    .dt-paging .dt-paging-button {
        display:inline-flex !important; align-items:center !important; justify-content:center !important;
        min-width:2rem !important; height:2rem !important; padding:0 0.6rem !important;
        border-radius:0.375rem !important;
        font-size:0.8rem !important; font-weight:600 !important; cursor:pointer !important;
        border:1px solid #3b82f6 !important; background:#3b82f6 !important; color:#fff !important;
        transition:background .15s, transform .1s !important;
        line-height:1 !important; box-shadow:none !important;
    }
    .dt-paging button:hover:not([disabled]):not(.current),
    .dt-paging .dt-paging-button:hover:not(.disabled):not(.current) {
        background:#2563eb !important; border-color:#2563eb !important; transform:translateY(-1px) !important;
    }
    .dt-paging button.current,
    .dt-paging .dt-paging-button.current {
        background:#1d4ed8 !important; border-color:#1d4ed8 !important; color:#fff !important; font-weight:700 !important;
        box-shadow:0 0 0 3px rgba(59,130,246,.35) !important;
    }
    .dt-paging button[disabled],
    .dt-paging .dt-paging-button.disabled { opacity:0.35 !important; cursor:not-allowed !important; }
    .dt-info { font-size:0.78rem !important; color:#6b7280 !important; margin-top:1.1rem !important; }
    @keyframes gmFadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
    .gm-modal-inner { animation: gmFadeUp 0.35s cubic-bezier(.22,.68,0,1.2) both; }
    .ai-modal-inner { animation: gmFadeUp 0.35s cubic-bezier(.22,.68,0,1.2) both; }

    @keyframes panel-drop-prod {
        from { opacity:0; transform:translateY(-8px) scale(.97); }
        to   { opacity:1; transform:translateY(0) scale(1); }
    }
    #panel-filtros-prod:not(.hidden) { animation: panel-drop-prod .2s cubic-bezier(.22,.68,0,1.2) both; }

    @keyframes badge-pulse-prod {
        0%,100% { box-shadow:0 0 0 0 rgba(79,70,229,.6); }
        50%      { box-shadow:0 0 0 5px rgba(79,70,229,0); }
    }
    #badge-prod:not(.hidden) { animation: badge-pulse-prod 1.8s ease-in-out infinite; }

    @keyframes acc-prod-open {
        from { opacity:0; transform:translateY(-4px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .acc-prod-body-open { animation: acc-prod-open .18s ease both; }

    /* Botón acordeón: abierto o con filtros activos */
    .acc-prod {
        transition: background .15s, color .15s;
        border-radius: 0.375rem;
        padding: 0.25rem 0.375rem;
        margin: -0.25rem -0.375rem;
    }
    .acc-prod.is-open, .acc-prod.has-active { background:#eef2ff; color:#4338ca; }
    .acc-prod.is-open .acc-prod-chevron, .acc-prod.has-active .acc-prod-chevron { color:#4338ca; }

    /* Labels activos */
    label:has(.fil-prod-contenedor:checked),
    label:has(.fil-prod-familia-padre:checked),
    label:has(.fil-prod-desc:checked) {
        background: #eef2ff !important;
        outline: 1px solid #c7d2fe;
        border-radius: 0.375rem;
    }
    label:has(.fil-prod-contenedor:checked) span,
    label:has(.fil-prod-familia-padre:checked) span,
    label:has(.fil-prod-desc:checked) span {
        color: #4338ca !important;
        font-weight: 600;
    }
    label:has(.fil-prod-estado[value="normal"]:checked)  { background:#f0fdf4 !important; outline:1px solid #bbf7d0; }
    label:has(.fil-prod-estado[value="minimo"]:checked)  { background:#fefce8 !important; outline:1px solid #fde047; }
    label:has(.fil-prod-estado[value="critico"]:checked) { background:#fef2f2 !important; outline:1px solid #fca5a5; }

    /* Dark mode — filtros checked */
    html.dark label:has(.fil-prod-contenedor:checked),
    html.dark label:has(.fil-prod-familia-padre:checked),
    html.dark label:has(.fil-prod-desc:checked)          { background:#1e1b4b !important; outline:1px solid #3730a3; }
    html.dark label:has(.fil-prod-estado[value="normal"]:checked)  { background:#052e16 !important; outline:1px solid #166534; }
    html.dark label:has(.fil-prod-estado[value="minimo"]:checked)  { background:#1c1500 !important; outline:1px solid #854d0e; }
    html.dark label:has(.fil-prod-estado[value="critico"]:checked) { background:#2d0a0a !important; outline:1px solid #7f1d1d; }

    /* Dark mode — acordeón filtro activo */
    html.dark .acc-prod.is-open,
    html.dark .acc-prod.has-active { background:#1e1b4b; color:#a5b4fc; }

    /* Botón Agregar Inventario */
    .btn-agregar-inv { background:#2563eb; }
    .btn-agregar-inv:hover { background:#1d4ed8; }
    html.dark .btn-agregar-inv { background:#3b82f6; }
    html.dark .btn-agregar-inv:hover { background:#2563eb; }

    /* Dark mode — p-act */
    html.dark .p-act-ver  { color:#a5b4fc; border-color:rgba(99,102,241,.3); }
    html.dark .p-act-ver:hover  { background:rgba(99,102,241,.18); border-color:#6366f1; }
    html.dark .p-act-edit { color:#fcd34d; border-color:rgba(217,119,6,.3); }
    html.dark .p-act-edit:hover { background:rgba(217,119,6,.18); border-color:#f59e0b; }
    html.dark .p-act-move { color:#93c5fd; border-color:rgba(37,99,235,.3); }
    html.dark .p-act-move:hover { background:rgba(37,99,235,.18); border-color:#3b82f6; }
    html.dark .p-act-out  { color:#fb923c; border-color:rgba(234,88,12,.3); }
    html.dark .p-act-out:hover  { background:rgba(234,88,12,.18); border-color:#ea580c; }
    html.dark .p-act-out:disabled { opacity:.3; }

    html.dark .ai-toast-err { background:#450a0a !important; border-color:#7f1d1d !important; color:#fca5a5 !important; }
</style>
@endpush

@push('scripts')
<script>
    window.devCCActive = new Set();
    $(document).ready(function() {
        const table = $('#tabla-inventario').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
            },
            order: [],
            paging: true,
            pageLength: 20,
            pagingType: 'numbers',
            layout: {
                topStart: 'buttons',
                topEnd: null,
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel', exportOptions: { columns: ':not(:last-child)', modifier: { page: 'all' } } },
                { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn',       exportOptions: { columns: ':not(:last-child)', modifier: { page: 'all' } } },
                { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf',   exportOptions: { columns: ':not(:last-child)', modifier: { page: 'all' } }, orientation: 'landscape', pageSize: 'A4' },
            ],
            columnDefs: [{ orderable: false, searchable: false, targets: -1 }],
            drawCallback: function() {
                var isDark = document.documentElement.classList.contains('dark');
                var bg     = isDark ? '#4f46e5' : '#3b82f6';
                var bgCur  = isDark ? '#312e81' : '#1d4ed8';
                var shadow = isDark ? '0 0 0 3px rgba(99,102,241,.35)' : '0 0 0 3px rgba(59,130,246,.35)';
                $('#tabla-inventario_paginate button, #tabla-inventario_paginate .dt-paging-button').css({
                    'background': bg, 'color':'#fff', 'border':'1px solid ' + bg,
                    'border-radius':'0.375rem','font-weight':'600','min-width':'2rem',
                    'height':'2rem','padding':'0 0.6rem','font-size':'0.8rem'
                });
                $('#tabla-inventario_paginate button.current, #tabla-inventario_paginate .dt-paging-button.current').css({
                    'background': bgCur, 'border-color': bgCur, 'box-shadow': shadow
                });
            },
        });

        // ── Sets de filtros activos ─────────────────────────────────────
        var filContenedores  = new Set();
        var filEstados       = new Set();
        var filProductoIds   = new Set();  // IDs seleccionados (familia completa o desc individual)
        var filTipo          = 'todos';    // 'todos' | 'producto' | 'servicio'

        function redibujarProd() {
            table.draw();
            var hay = filContenedores.size || filEstados.size || filProductoIds.size || filTipo !== 'todos';
            $('#badge-prod').toggleClass('hidden', !hay);
            $('[data-target="acc-prod-contenedor"]').toggleClass('has-active', filContenedores.size > 0);
            $('[data-target="acc-prod-estado"]').toggleClass('has-active', filEstados.size > 0);
            $('[data-target="acc-prod-familia"]').toggleClass('has-active', filProductoIds.size > 0);
        }

        // Sincroniza el estado indeterminado del padre de un grupo
        function actualizarPadre($padre) {
            var ids    = ($padre.data('ids') || '').split(',').map(Number).filter(Boolean);
            var total  = ids.length;
            var marcados = ids.filter(function(id) { return filProductoIds.has(id); }).length;
            var el = $padre[0];
            if (marcados === 0)       { el.checked = false; el.indeterminate = false; }
            else if (marcados < total) { el.checked = false; el.indeterminate = true;  }
            else                       { el.checked = true;  el.indeterminate = false; }
        }

        // ── Filtro personalizado ────────────────────────────────────────
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'tabla-inventario') return true;

            var tr = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;

            // Texto libre
            var q = ($('#buscador-productos').val() || '').toLowerCase().trim();
            if (q) {
                var celdas = tr ? Array.from(tr.querySelectorAll('td')).map(function(td){ return td.innerText.toLowerCase(); }) : [];
                if (![0,1,2,3,6].some(function(i){ return (celdas[i]||'').includes(q); })) return false;
            }

            // Tipo (producto físico / servicio)
            if (filTipo !== 'todos') {
                var tipo = tr ? tr.getAttribute('data-tipo') : null;
                if (tipo !== filTipo) return false;
            }

            // Contenedor
            if (filContenedores.size) {
                var cont = tr ? parseInt(tr.getAttribute('data-contenedor'), 10) : null;
                if (!cont || !filContenedores.has(cont)) return false;
            }

            // Estado
            if (filEstados.size) {
                var est = tr ? tr.getAttribute('data-estado') : null;
                if (!est || !filEstados.has(est)) return false;
            }

            // Producto (familia completa o descripción individual)
            if (filProductoIds.size) {
                var pid = tr ? parseInt(tr.getAttribute('data-producto-id'), 10) : null;
                if (!pid || !filProductoIds.has(pid)) return false;
            }

            // Dev: filtro por centro de costo
            if (window.devCCActive && window.devCCActive.size) {
                var ccId = tr ? parseInt(tr.getAttribute('data-cc-id'), 10) : NaN;
                if (!window.devCCActive.has(ccId)) return false;
            }

            return true;
        });

        $('#buscador-productos').on('input', function () { table.draw(); });

        // ── Checkboxes contenedor y estado ──────────────────────────────
        $(document).on('change', '.fil-prod-contenedor', function() {
            var id = parseInt(this.value, 10);
            this.checked ? filContenedores.add(id) : filContenedores.delete(id);
            redibujarProd();
        });
        $(document).on('change', '.fil-prod-estado', function() {
            this.checked ? filEstados.add(this.value) : filEstados.delete(this.value);
            redibujarProd();
        });
        $(document).on('change', '.fil-prod-tipo', function() {
            filTipo = this.value;
            redibujarProd();
        });

        // ── Checkbox padre (familia completa) ───────────────────────────
        $(document).on('change', '.fil-prod-familia-padre', function() {
            var $padre = $(this);
            var ids = ($padre.data('ids') || '').split(',').map(Number).filter(Boolean);
            // Marcar/desmarcar todos los hijos del mismo grupo
            $padre.closest('div').find('.fil-prod-desc').each(function() {
                this.checked = $padre[0].checked;
            });
            ids.forEach(function(id) {
                $padre[0].checked ? filProductoIds.add(id) : filProductoIds.delete(id);
            });
            redibujarProd();
        });

        // ── Checkbox hijo (descripción individual) ──────────────────────
        $(document).on('change', '.fil-prod-desc', function() {
            var id = parseInt(this.value, 10);
            this.checked ? filProductoIds.add(id) : filProductoIds.delete(id);
            // Actualizar estado del padre
            var $padre = $(this).closest('div').prev('label').find('.fil-prod-familia-padre');
            actualizarPadre($padre);
            redibujarProd();
        });

        // ── Acordeón ───────────────────────────────────────────────────
        $('.acc-prod').on('click', function() {
            var $body   = $('#' + $(this).data('target'));
            var opening = $body.hasClass('hidden');
            $body.toggleClass('hidden', !opening);
            if (opening) $body.addClass('acc-prod-body-open');
            $(this).find('.acc-prod-chevron').css('transform', opening ? 'rotate(180deg)' : '');
            $(this).toggleClass('is-open', opening);
        });

        // ── Toggle panel ───────────────────────────────────────────────
        $('#btn-filtros-prod').on('click', function(e) {
            e.stopPropagation();
            $('#panel-filtros-prod').toggleClass('hidden');
        });
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#panel-filtros-prod, #btn-filtros-prod').length) {
                $('#panel-filtros-prod').addClass('hidden');
            }
        });

        // ── Limpiar todo ───────────────────────────────────────────────
        $('#btn-limpiar-prod').on('click', function() {
            filContenedores.clear(); filEstados.clear(); filProductoIds.clear(); filTipo = 'todos';
            $('.fil-prod-contenedor, .fil-prod-estado, .fil-prod-familia-padre, .fil-prod-desc').prop('checked', false);
            $('.fil-prod-tipo[value="todos"]').prop('checked', true);
            $('.fil-prod-familia-padre').each(function() { this.indeterminate = false; });
            $('#buscador-productos').val('');
            redibujarProd();
        });
    });

    @if(auth()->user()->esDev())
    window.toggleDevCC = function() {
        var dd = document.getElementById('dev-cc-dropdown');
        var open = dd.style.display === 'block';
        dd.style.display = open ? 'none' : 'block';
        document.getElementById('dev-cc-chevron').style.transform = open ? '' : 'rotate(180deg)';
    };
    window.devCCFiltrar = function() {
        window.devCCActive.clear();
        document.querySelectorAll('.dev-cc-check:checked').forEach(function(ch) {
            window.devCCActive.add(parseInt(ch.value, 10));
        });
        var badge = document.getElementById('dev-cc-badge');
        if (window.devCCActive.size) {
            badge.textContent = window.devCCActive.size;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
        $('#tabla-inventario').DataTable().draw();
    };
    window.devCCLimpiar = function() {
        document.querySelectorAll('.dev-cc-check').forEach(function(ch) { ch.checked = false; });
        window.devCCActive.clear();
        document.getElementById('dev-cc-badge').classList.add('hidden');
        var s = document.getElementById('dev-cc-search');
        if (s) { s.value = ''; window.devCCBuscar(''); }
        $('#tabla-inventario').DataTable().draw();
    };
    window.devCCBuscar = function(q) {
        var term = q.toLowerCase().trim();
        document.querySelectorAll('#dev-cc-list label').forEach(function(el) {
            var name = el.querySelector('span').textContent.toLowerCase();
            el.style.display = (!term || name.includes(term)) ? 'flex' : 'none';
        });
    };
    window.devCCCargarMas = function() {
        var hidden = document.querySelectorAll('#dev-cc-list label[data-more="1"]');
        var count = 0;
        hidden.forEach(function(el) {
            if (count < 20) {
                el.style.display = 'flex';
                el.removeAttribute('data-more');
                count++;
            }
        });
        var remaining = document.querySelectorAll('#dev-cc-list label[data-more="1"]').length;
        var btn = document.getElementById('dev-cc-load-more');
        if (btn) {
            if (remaining === 0) btn.style.display = 'none';
            else btn.textContent = 'Cargar más (' + remaining + ' restantes)';
        }
    };
    window.devCCMarcarTodos = function() {
        document.querySelectorAll('#dev-cc-list label[data-more="1"]').forEach(function(el) {
            el.style.display = 'flex';
            el.removeAttribute('data-more');
        });
        var btn = document.getElementById('dev-cc-load-more');
        if (btn) btn.style.display = 'none';
        document.querySelectorAll('.dev-cc-check').forEach(function(ch) { ch.checked = true; });
        window.devCCFiltrar();
    };
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#dev-cc-wrapper')) {
            var dd = document.getElementById('dev-cc-dropdown');
            if (dd && dd.style.display === 'block') {
                dd.style.display = 'none';
                document.getElementById('dev-cc-chevron').style.transform = '';
            }
        }
    });
    @endif

    // Sincronizar todas las animaciones de pulso al mismo tiempo
    document.addEventListener('DOMContentLoaded', function () {
        const els = document.querySelectorAll('.estado-pulso-critico, .estado-pulso-minimo');
        els.forEach(el => { el.style.animationDelay = '0s'; el.style.animationPlayState = 'paused'; });
        requestAnimationFrame(() => els.forEach(el => { el.style.animationPlayState = 'running'; }));
    });
</script>
@endpush

@push('head')
<style>
    /* ── Botones de acción en tabla de productos ─────────────────── */
    .p-act {
        display:inline-flex; align-items:center; justify-content:center;
        width:2rem; height:2rem; border-radius:.5rem; border:1px solid;
        transition:background .18s, border-color .18s, color .18s, transform .15s;
        cursor:pointer; background:transparent;
    }
    .p-act:hover { transform:scale(1.1); }
    /* Ver — violeta */
    .p-act-ver  { color:#6366f1; border-color:#c7d2fe; }
    .p-act-ver:hover  { background:#eef2ff; border-color:#818cf8; }
    /* Editar — ámbar */
    .p-act-edit { color:#d97706; border-color:#fde68a; }
    .p-act-edit:hover { background:#fffbeb; border-color:#f59e0b; }
    /* Trasladar — azul */
    .p-act-move { color:#2563eb; border-color:#bfdbfe; }
    .p-act-move:hover { background:#eff6ff; border-color:#60a5fa; }
    /* Solicitar salida — naranja */
    .p-act-out  { color:#ea580c; border-color:#fed7aa; }
    .p-act-out:hover  { background:#fff7ed; border-color:#fb923c; }
    .p-act-out:disabled { opacity:.35; cursor:not-allowed; transform:none; }
    @keyframes pulso-critico { 0%,100% { box-shadow:0 0 0 0 rgba(239,68,68,.5); } 50% { box-shadow:0 0 0 6px rgba(239,68,68,0); } }
    @keyframes pulso-minimo  { 0%,100% { box-shadow:0 0 0 0 rgba(234,179,8,.5); } 50% { box-shadow:0 0 0 6px rgba(234,179,8,0); } }
    .estado-pulso-critico { animation: pulso-critico 1.5s ease-in-out infinite; }
    .estado-pulso-minimo  { animation: pulso-minimo  1.5s ease-in-out infinite; }

    /* Dark mode — paginación DataTables */
    html.dark .dt-paging button,
    html.dark .dt-paging .dt-paging-button {
        background:#4f46e5 !important; border-color:#4f46e5 !important; color:#fff !important;
    }
    html.dark .dt-paging button:hover:not([disabled]):not(.current),
    html.dark .dt-paging .dt-paging-button:hover:not(.disabled):not(.current) {
        background:#6366f1 !important; border-color:#6366f1 !important;
    }
    html.dark .dt-paging button.current,
    html.dark .dt-paging .dt-paging-button.current {
        background:#312e81 !important; border-color:#312e81 !important;
    }
    html.dark .dt-info { color:#64748b !important; }
    .ai-warn-ya-ingresada { background:#fffbeb; border:1px solid #fcd34d; border-radius:0.4rem; padding:0.4rem 0.65rem; align-items:center; gap:0.5rem; }
    .ai-warn-ya-ingresada span { color:#92400e; }
    html.dark .ai-warn-ya-ingresada { background:rgba(120,53,15,0.25); border-color:#92400e; }
    html.dark .ai-warn-ya-ingresada span { color:#fde68a; }
    html.dark .ai-warn-ya-ingresada svg { color:#fbbf24; }
</style>
@endpush

{{-- Modal: crear producto rápido --}}
<div id="ai-modal-crear-producto" style="display:none; position:fixed; inset:0; z-index:99999; align-items:center; justify-content:center; background:rgba(0,0,0,.55);">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 24px 64px rgba(0,0,0,.3); width:480px; max-width:calc(100vw - 2rem); padding:1.5rem; max-height:88vh; overflow-y:auto; position:relative;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.1rem;">
            <div>
                <h3 id="ai-crear-titulo" style="font-size:1rem; font-weight:700; color:#1f2937; margin:0 0 0.25rem;">Nuevo producto</h3>

                <p id="ai-crear-nombre-display" style="font-size:0.8rem; color:#374151; margin:0; font-weight:500; word-break:break-word;"></p>
            </div>
            <button type="button" onclick="aiCerrarModalCrear()" style="flex-shrink:0; color:#9ca3af; background:none; border:none; cursor:pointer; font-size:1.25rem; line-height:1; padding:0.1rem;">✕</button>
        </div>

        {{-- Campo nombre (visible solo cuando se abre desde boleta local) --}}
        <div id="ai-crear-nombre-wrap" style="display:none; margin-bottom:0.85rem;">
            <label style="display:block; font-size:0.8125rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">Nombre del producto <span style="color:#ef4444;">*</span></label>
            <input type="text" id="ai-crear-nombre-input" placeholder="Ej: Disco Duro 1TB"
                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.875rem; outline:none; box-sizing:border-box;">
        </div>

        <div style="margin-bottom:0.85rem;">
            <label style="display:block; font-size:0.8125rem; font-weight:600; color:#374151; margin-bottom:0.5rem;">Familia <span style="color:#ef4444;">*</span></label>
            <div id="ai-crear-familias" style="display:flex; flex-wrap:wrap; gap:0.4rem; min-height:1.5rem;"></div>
            @if(auth()->user()->esDev())
            <button type="button" id="ai-btn-nueva-familia" onclick="aiMostrarNuevaFamilia()"
                    style="font-size:0.72rem; color:#7c3aed; background:none; border:none; cursor:pointer; padding:0.25rem 0; margin-top:0.4rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                + Nueva familia
            </button>
            <div id="ai-nueva-familia-wrap" style="display:none; margin-top:0.35rem;">
                <div style="display:flex; gap:0.35rem; align-items:center;">
                    <input type="text" id="ai-nueva-familia-input" placeholder="Nombre de la nueva familia"
                           style="flex:1; font-size:0.8rem; padding:0.3rem 0.6rem; border:1px solid #d1d5db; border-radius:0.4rem; outline:none; color:#374151;"
                           onkeydown="if(event.key==='Enter')aiGuardarNuevaFamilia(); if(event.key==='Escape')aiOcultarNuevaFamilia();">
                    <button type="button" onclick="aiGuardarNuevaFamilia()"
                            style="font-size:0.75rem; font-weight:600; padding:0.3rem 0.7rem; background:#7c3aed; color:#fff; border:none; border-radius:0.4rem; cursor:pointer; white-space:nowrap;">
                        Crear
                    </button>
                    <button type="button" onclick="aiOcultarNuevaFamilia()"
                            style="font-size:0.75rem; padding:0.3rem 0.5rem; background:#f3f4f6; color:#6b7280; border:1px solid #e5e7eb; border-radius:0.4rem; cursor:pointer;">
                        ✕
                    </button>
                </div>
                <p id="ai-nueva-familia-error" style="display:none; font-size:0.72rem; color:#dc2626; margin-top:0.2rem;"></p>
            </div>
            @endif
        </div>

        <div id="ai-crear-cat-wrapper" style="display:none; margin-bottom:0.85rem;">
            <label style="display:block; font-size:0.8125rem; font-weight:600; color:#374151; margin-bottom:0.5rem;">Categoría <span style="color:#ef4444;">*</span></label>
            <div id="ai-crear-categorias-btns" style="display:flex; flex-wrap:wrap; gap:0.4rem;"></div>
            @if(auth()->user()->esDev())
            <button type="button" id="ai-btn-nueva-categoria" onclick="aiMostrarNuevaCategoria()"
                    style="font-size:0.72rem; color:#7c3aed; background:none; border:none; cursor:pointer; padding:0.25rem 0; margin-top:0.4rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                + Nueva categoría
            </button>
            <div id="ai-nueva-categoria-wrap" style="display:none; margin-top:0.35rem;">
                <div style="display:flex; gap:0.35rem; align-items:center;">
                    <input type="text" id="ai-nueva-categoria-input" placeholder="Nombre de la nueva categoría"
                           style="flex:1; font-size:0.8rem; padding:0.3rem 0.6rem; border:1px solid #d1d5db; border-radius:0.4rem; outline:none; color:#374151;"
                           onkeydown="if(event.key==='Enter')aiGuardarNuevaCategoria(); if(event.key==='Escape')aiOcultarNuevaCategoria();">
                    <button type="button" onclick="aiGuardarNuevaCategoria()"
                            style="font-size:0.75rem; font-weight:600; padding:0.3rem 0.7rem; background:#7c3aed; color:#fff; border:none; border-radius:0.4rem; cursor:pointer; white-space:nowrap;">
                        Crear
                    </button>
                    <button type="button" onclick="aiOcultarNuevaCategoria()"
                            style="font-size:0.75rem; padding:0.3rem 0.5rem; background:#f3f4f6; color:#6b7280; border:1px solid #e5e7eb; border-radius:0.4rem; cursor:pointer;">
                        ✕
                    </button>
                </div>
                <p id="ai-nueva-categoria-error" style="display:none; font-size:0.72rem; color:#dc2626; margin-top:0.2rem;"></p>
            </div>
            @endif
        </div>

        <div id="ai-crear-marca-wrapper" style="display:none; margin-bottom:0.85rem;">
            <label style="display:block; font-size:0.8125rem; font-weight:600; color:#374151; margin-bottom:0.5rem;">Marca <span style="color:#ef4444;">*</span></label>
            <div id="ai-crear-marcas-btns" style="display:flex; flex-wrap:wrap; gap:0.4rem;"></div>
        </div>

        <div style="margin-bottom:0.85rem;">
            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">
                Unidad de Medida <span style="color:#ef4444;">*</span>
            </label>
            <select id="ai-crear-unidad-id"
                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.6rem; font-size:0.875rem; outline:none; box-sizing:border-box; background:#fff;">
                <option value="">— Selecciona —</option>
                @foreach(\App\Models\UnidadMedida::activas()->orderBy('nombre')->get() as $um)
                    <option value="{{ $um->id }}">{{ $um->nombre }} ({{ $um->abreviacion }})</option>
                @endforeach
            </select>
        </div>

        <div id="ai-crear-stock-wrap" style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.85rem;">
            <div>
                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">
                    Stock mínimo <span style="color:#ef4444;">*</span>
                </label>
                <input type="number" id="ai-crear-stock-minimo" min="0" value="0"
                       style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.6rem; font-size:0.875rem; outline:none; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">
                    Stock crítico <span style="color:#ef4444;">*</span>
                </label>
                <input type="number" id="ai-crear-stock-critico" min="0" value="0"
                       style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.6rem; font-size:0.875rem; outline:none; box-sizing:border-box;">
            </div>
        </div>

        <div id="ai-crear-error" style="display:none; font-size:0.8rem; color:#dc2626; margin-bottom:0.75rem; padding:0.4rem 0.6rem; background:#fef2f2; border-radius:0.375rem;"></div>

        <div style="display:flex; justify-content:flex-end; gap:0.5rem; border-top:1px solid #f3f4f6; padding-top:1rem; margin-top:0.5rem;">
            <button type="button" onclick="aiCerrarModalCrear()" style="padding:0.45rem 1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:0.5rem; cursor:pointer;">
                Cancelar
            </button>
            <button type="button" id="ai-crear-btn-guardar" onclick="aiConfirmarCrearProducto()"
                style="padding:0.45rem 1.1rem; font-size:0.875rem; font-weight:600; color:#fff; background:#7c3aed; border:none; border-radius:0.5rem; cursor:pointer;">
                Crear producto
            </button>
        </div>
    </div>
</div>

@endsection

@push('head')
<style>
/* ── Tabs Productos / Servicios ─────────────────────────────────────── */
.tab-ps-btn {
    color: #6b7280;
    background: transparent;
    border: none;
    cursor: pointer;
}
.tab-ps-btn:hover { background:#f3f4f6; color:#374151; }
.tab-ps-active { background:#7c3aed !important; color:#fff !important; }
.tab-ps-active .tab-ps-cnt { background:rgba(255,255,255,0.25); color:#fff; }
.tab-ps-btn:not(.tab-ps-active) .tab-ps-cnt { background:#e9d5ff; color:#7c3aed; }
html.dark .tab-ps-btn { color:#94a3b8; }
html.dark .tab-ps-btn:hover { background:#1e293b; color:#e2e8f0; }
html.dark .tab-ps-active { background:#7c3aed !important; color:#fff !important; }

/* ── Tabla servicios ────────────────────────────────────────────────── */
#tab-panel-servicios .bg-white { background:#fff; }
html.dark #tab-panel-servicios .bg-white { background:#1e293b; }
html.dark #tabla-servicios thead { background:#0f172a; }
html.dark #tabla-servicios thead th { color:#94a3b8; }
html.dark #tabla-servicios tbody tr { border-color:#334155; }
html.dark #tabla-servicios tbody tr:hover { background:rgba(124,58,237,.08) !important; }

/* ── Modal gestionar estado ─────────────────────────────────────────── */
html.dark #modal-gestionar-estado > div { background:#1e293b !important; }
html.dark #modal-gestionar-estado h3 { color:#f1f5f9 !important; }
html.dark #modal-gestionar-estado .sticky { background:#1e293b !important; border-color:#334155 !important; }
html.dark #modal-gestionar-estado [style*="background:#f8fafc"] { background:#0f172a !important; border-color:#334155 !important; }
html.dark #modal-gestionar-estado [style*="background:#f1f5f9"] { border-color:#334155 !important; }
html.dark #ge-observacion { background:#0f172a; color:#e2e8f0; border-color:#334155; }

/* Botón estado en tabla servicios */
.ge-opcion-btn {
    display:inline-flex; align-items:center; gap:0.35rem;
    padding:0.4rem 0.85rem; border-radius:0.5rem; font-size:0.8rem; font-weight:600;
    cursor:pointer; border:2px solid; transition:opacity .15s, transform .1s;
}
.ge-opcion-btn:hover { opacity:.85; transform:scale(1.02); }
.ge-opcion-cancelar { border-color:#ef4444 !important; color:#ef4444 !important; background:transparent !important; }
.ge-opcion-cancelar:hover { background:#fef2f2 !important; }
</style>
@endpush

@push('scripts')
<script>
// ── Tab switching ────────────────────────────────────────────────────
function switchTab(tab) {
    var isProd = tab === 'productos';
    document.getElementById('tab-panel-productos').style.display = isProd ? '' : 'none';
    document.getElementById('tab-panel-servicios').style.display = isProd ? 'none' : '';
    document.getElementById('tab-btn-productos').classList.toggle('tab-ps-active', isProd);
    document.getElementById('tab-btn-servicios').classList.toggle('tab-ps-active', !isProd);
}

// ── Buscador servicios ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var buscServ = document.getElementById('buscador-servicios');
    if (!buscServ) return;
    buscServ.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('#tabla-servicios tbody tr').forEach(function(tr) {
            if (!q) { tr.style.display = ''; return; }
            var nombre = (tr.dataset.nombre || '');
            var cat    = (tr.dataset.categoria || '');
            var est    = (tr.dataset.estado || '');
            tr.style.display = (nombre.includes(q) || cat.includes(q) || est.includes(q)) ? '' : 'none';
        });
    });
});

// ── Estado config (debe coincidir con ServicioEstado::colores/label) ─
var SE_COLORES = {
    pendiente:  { bg:'#f3f4f6', text:'#6b7280', dot:'#9ca3af', barra:'#9ca3af' },
    aprobado:   { bg:'#eff6ff', text:'#1d4ed8', dot:'#3b82f6', barra:'#3b82f6' },
    en_proceso: { bg:'#fefce8', text:'#a16207', dot:'#eab308', barra:'#eab308' },
    ejecutado:  { bg:'#f0fdf4', text:'#15803d', dot:'#22c55e', barra:'#22c55e' },
    validado:   { bg:'#f0fdf4', text:'#166534', dot:'#16a34a', barra:'#16a34a' },
    cerrado:    { bg:'#1e293b', text:'#f8fafc', dot:'#94a3b8', barra:'#1e293b' },
    cancelado:  { bg:'#fef2f2', text:'#dc2626', dot:'#ef4444', barra:'#ef4444' },
};
var SE_LABELS = {
    pendiente:'Pendiente', aprobado:'Aprobado', en_proceso:'En proceso',
    ejecutado:'Ejecutado', validado:'Validado', cerrado:'Cerrado', cancelado:'Cancelado'
};
var SE_PROGRESO = { pendiente:0, aprobado:20, en_proceso:50, ejecutado:80, validado:90, cerrado:100, cancelado:0 };
var SE_FLUJO    = { pendiente:'aprobado', aprobado:'en_proceso', en_proceso:'ejecutado', ejecutado:'validado', validado:'cerrado' };

// ── Modal gestionar estado ───────────────────────────────────────────
var _geServicioId   = null;
var _geEstadoActual = null;
var _geSiguiente    = null;
var _geEstadoElegido = null;

function abrirModalServicio(id, nombre, estadoActual, siguiente, timeline) {
    _geServicioId   = id;
    _geEstadoActual = estadoActual;
    _geSiguiente    = siguiente;
    _geEstadoElegido = null;

    document.getElementById('ge-titulo').textContent = nombre;
    document.getElementById('form-gestionar-estado').action = '/admin/productos/' + id + '/gestionar-estado';
    document.getElementById('ge-estado-input').value = '';
    document.getElementById('ge-observacion').value = '';
    document.getElementById('ge-btn-submit').style.display = 'none';

    // Badge estado actual
    var c = SE_COLORES[estadoActual] || SE_COLORES.pendiente;
    var badge = document.getElementById('ge-badge-estado');
    badge.style.background = c.bg;
    badge.style.color = c.text;
    badge.innerHTML = '<span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:' + c.dot + ';display:inline-block;"></span> ' + (SE_LABELS[estadoActual] || estadoActual);

    // Barra progreso
    var pct = SE_PROGRESO[estadoActual] || 0;
    document.getElementById('ge-barra').style.width = pct + '%';
    document.getElementById('ge-barra').style.background = c.barra;
    document.getElementById('ge-pct').textContent = pct + '%';

    // Timeline
    var tl = document.getElementById('ge-timeline');
    if (!timeline || timeline.length === 0) {
        tl.innerHTML = '<div style="text-align:center; padding:0.75rem; color:#9ca3af; font-size:0.8rem; font-style:italic;">Sin movimientos registrados — estado inicial: Pendiente</div>';
    } else {
        tl.innerHTML = '';
        timeline.forEach(function(s, i) {
            var tc = s.colores || SE_COLORES[s.estado] || SE_COLORES.pendiente;
            var isLast = (i === timeline.length - 1);
            var div = document.createElement('div');
            div.style.cssText = 'display:flex; gap:0.75rem; padding:0.5rem 0; position:relative;';
            div.innerHTML =
                '<div style="display:flex; flex-direction:column; align-items:center; flex-shrink:0;">' +
                    '<div style="width:10px;height:10px;border-radius:50%;background:' + tc.dot + ';margin-top:3px;flex-shrink:0;"></div>' +
                    (!isLast ? '<div style="width:2px;flex:1;background:#e2e8f0;margin-top:3px;min-height:20px;"></div>' : '') +
                '</div>' +
                '<div style="flex:1; padding-bottom:' + (!isLast ? '0.4rem' : '0') + ';">' +
                    '<div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">' +
                        '<span style="font-size:0.78rem; font-weight:700; color:' + tc.text + '; background:' + tc.bg + '; padding:0.15rem 0.5rem; border-radius:9999px;">' + s.label + '</span>' +
                        '<span style="font-size:0.7rem; color:#94a3b8;">' + s.fecha + '</span>' +
                    '</div>' +
                    '<div style="font-size:0.72rem; color:#64748b; margin-top:0.2rem;">' + escH(s.usuario) + '</div>' +
                    (s.obs ? '<div style="font-size:0.72rem; color:#94a3b8; margin-top:0.15rem; font-style:italic;">' + escH(s.obs) + '</div>' : '') +
                '</div>';
            tl.appendChild(div);
        });
    }

    // Opciones de estado
    var opts = document.getElementById('ge-opciones-estado');
    opts.innerHTML = '';
    if (siguiente) {
        var cs = SE_COLORES[siguiente] || SE_COLORES.pendiente;
        var btnSig = document.createElement('button');
        btnSig.type = 'button';
        btnSig.className = 'ge-opcion-btn';
        btnSig.style.cssText = 'border-color:' + cs.dot + '; color:' + cs.text + '; background:' + cs.bg + ';';
        btnSig.innerHTML = '<svg style="width:12px;height:12px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>' + (SE_LABELS[siguiente] || siguiente);
        btnSig.addEventListener('click', function() { elegirEstado(siguiente, this); });
        opts.appendChild(btnSig);
    }
    // Botón Cancelar servicio (rojo, siempre disponible si no está cerrado)
    var btnCancel = document.createElement('button');
    btnCancel.type = 'button';
    btnCancel.className = 'ge-opcion-btn ge-opcion-cancelar';
    btnCancel.innerHTML = '<svg style="width:12px;height:12px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>Cancelar servicio';
    btnCancel.addEventListener('click', function() { elegirEstado('cancelado', this); });
    opts.appendChild(btnCancel);

    if (!siguiente && estadoActual !== 'cancelado') {
        opts.innerHTML = '<p style="font-size:0.8rem; color:#94a3b8; font-style:italic;">No hay transición disponible desde este estado.</p>';
    }

    document.getElementById('modal-gestionar-estado').style.display = 'flex';
}

function elegirEstado(estado, btn) {
    _geEstadoElegido = estado;
    document.getElementById('ge-estado-input').value = estado;
    document.querySelectorAll('.ge-opcion-btn').forEach(function(b) { b.style.outline = ''; });
    btn.style.outline = '2px solid #7c3aed';
    var submitBtn = document.getElementById('ge-btn-submit');
    submitBtn.style.display = 'inline-flex';
    var c = SE_COLORES[estado] || SE_COLORES.pendiente;
    submitBtn.style.background = estado === 'cancelado' ? '#ef4444' : '#7c3aed';
    submitBtn.textContent = 'Confirmar → ' + (SE_LABELS[estado] || estado);
}

function cerrarModalServicio() {
    document.getElementById('modal-gestionar-estado').style.display = 'none';
    _geServicioId = null; _geEstadoElegido = null;
}

function escH(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush

@push('scripts')
@php
$aiProductosJson = json_encode(
    $productos->map(fn($p) => [
        'id'             => $p->id,
        'nombre'         => $p->nombre,
        'stock'          => $p->stock_actual,
        'contenedor_id'    => $p->contenedor,
        'contenedor_nombre'=> $p->container?->nombre ?? '—',
        'unidad'           => $p->unidadMedida?->abreviacion ?? $p->unidad ?? '',
    ])->values(),
    JSON_HEX_TAG | JSON_HEX_AMP
);
$aiContainersJson = json_encode(
    $containers->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])->values(),
    JSON_HEX_TAG | JSON_HEX_AMP
);
$aiFamiliasJson = json_encode(
    ($familias ?? collect())->map(fn($f) => [
        'id'         => $f->id,
        'nombre'     => $f->nombre,
        'tipo'       => $f->tipo,
        'categorias' => $f->categorias->map(fn($c) => [
            'id'     => $c->id,
            'nombre' => $c->nombre,
            'marcas' => $c->marcas->map(fn($m) => ['id' => $m->id, 'nombre' => $m->nombre])->values(),
        ])->values(),
    ])->values(),
    JSON_HEX_TAG | JSON_HEX_AMP
);
@endphp
<script type="application/json" id="ai-data">{!! $aiProductosJson !!}</script>
<script type="application/json" id="ai-containers-data">{!! $aiContainersJson !!}</script>
<script type="application/json" id="ai-familias-data">{!! $aiFamiliasJson !!}</script>
<script>
var aiFamilias = JSON.parse(document.getElementById('ai-familias-data').textContent);
// Derived from familia.tipo — no hardcoded IDs needed
var _aiSinFamFam      = aiFamilias.find(function(f) { return f.tipo === 'sin_familia'; });
var _aiPypFam         = aiFamilias.find(function(f) { return f.tipo === 'partes_piezas'; });
var _aiServiciosFam   = aiFamilias.find(function(f) { return f.tipo === 'servicios'; });
var AI_SIN_FAMILIA_ID     = _aiSinFamFam    ? _aiSinFamFam.id    : null;
var AI_SERVICIOS_FAMILIA_ID = _aiServiciosFam ? _aiServiciosFam.id : null;
if (document.getElementById('btn-agregar-inventario')) {
var aiProductos  = JSON.parse(document.getElementById('ai-data').textContent);
var aiContainers = JSON.parse(document.getElementById('ai-containers-data').textContent);
var aiItems = [];
var aiCounter = 0;
var aiForm = document.getElementById('form-agregar-inv');
var aiUrlLocal   = aiForm.dataset.urlLocal;
var aiUrlExterna = aiForm.dataset.urlExterna;
var aiUrlMasiva  = aiForm.dataset.urlMasiva;
var aiUrlManual  = aiForm.dataset.urlManual;
var AI_IS_DEV    = {{ auth()->user()->esDev() ? 'true' : 'false' }};
var AI_IS_ADMIN  = {{ auth()->user()->esAdmin() ? 'true' : 'false' }};
var AI_URL_CREAR         = '{{ route('admin.productos.crear.rapido') }}';
var AI_URL_PROD_DESTROY  = '{{ url('admin/catalogo/productos') }}/';
var AI_URL_FAMILIA   = '{{ route('admin.catalogo.familias.store') }}';
var AI_URL_CATEGORIA = '{{ route('admin.catalogo.categorias.store') }}';
var AI_CSRF          = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var aiMetodoCargaActual  = 'masiva';
var aiItemsManual        = [];
var aiCounterManual      = 0;
var _aiProductosCreados  = [];

function _aiEliminarProductosCreados(ids) {
    if (!ids || !ids.length) return;
    ids.forEach(function(id) {
        fetch(AI_URL_PROD_DESTROY + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': AI_CSRF, 'Accept': 'application/json' }
        });
    });
    _aiProductosCreados = [];
    try { sessionStorage.removeItem('ai_prods_creados'); } catch(e) {}
}

document.getElementById('btn-agregar-inventario').addEventListener('click', function() {
    var modal = document.getElementById('modal-agregar-inv');
    var inner = modal.querySelector('.ai-modal-inner');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    inner.style.animation = 'none';
    inner.offsetHeight;
    inner.style.animation = '';
});

// Abrir automáticamente si hubo un error de duplicado (withInput devuelve al dashboard)
@if(session('sicd_duplicada'))
window.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modal-agregar-inv');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        // Activar tab "externa"
        var sel = document.getElementById('ai-tipo');
        if (sel) { sel.value = 'externa'; sel.dispatchEvent(new Event('change')); }
    }
});
@endif

var _aiSicdEnlazadoId = null;
var _aiSicdDupUrl     = null;
var _aiSicdDupEstado  = null;
var _aiSicdDupCodigo  = null;

(function() {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var orphanSicd = sessionStorage.getItem('ai_sicd_pending');
    if (orphanSicd) {
        sessionStorage.removeItem('ai_sicd_pending');
        fetch('{{ url("admin/sicd") }}/' + orphanSicd + '/cancelar', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
    }
    var orphanProds = sessionStorage.getItem('ai_prods_creados');
    if (orphanProds) {
        sessionStorage.removeItem('ai_prods_creados');
        try {
            var ids = JSON.parse(orphanProds);
            ids.forEach(function(id) {
                fetch(AI_URL_PROD_DESTROY + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                });
            });
        } catch(e) {}
    }
})();

function cerrarConConfirmacion() {
    var tieneItems  = aiItemsManual && aiItemsManual.length > 0;
    var tieneSicd   = !!_aiSicdEnlazadoId;
    if (tieneSicd || tieneItems) {
        var msg = document.getElementById('ai-confirm-salida-msg');
        if (msg) {
            if (tieneSicd && tieneItems) {
                msg.textContent = 'Si sales ahora, se cancelará el SICD enlazado y se perderán los productos agregados.';
            } else if (tieneSicd) {
                msg.textContent = 'Si sales ahora, se cancelará el PDF SICD enlazado y la acción de ingresar no se completará.';
            } else {
                msg.textContent = 'Si sales ahora, se perderán los ' + aiItemsManual.length + ' producto(s) agregados a la carga manual.';
            }
        }
        var overlay = document.getElementById('ai-confirm-salida');
        if (overlay) overlay.style.display = 'flex';
    } else {
        cerrarModalAgregarInv();
    }
}

function aiCancelarSalida() {
    var overlay = document.getElementById('ai-confirm-salida');
    if (overlay) overlay.style.display = 'none';
}

function aiConfirmarSalida() {
    _aiEliminarProductosCreados(_aiProductosCreados.slice());
    var id = _aiSicdEnlazadoId;
    if (!id) { cerrarModalAgregarInv(); return; }

    fetch('{{ url("admin/sicd") }}/' + id + '/cancelar', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .finally(function() {
        _aiSicdEnlazadoId = null;
        cerrarModalAgregarInv();
    });
}

function cerrarModalAgregarInv() {
    _aiSicdEnlazadoId = null;
    sessionStorage.removeItem('ai_sicd_pending');
    var toast = document.getElementById('ai-toast-error');
    if (toast) toast.style.display = 'none';
    var overlay = document.getElementById('ai-confirm-salida');
    if (overlay) overlay.style.display = 'none';
    window._aiEnlazarUrl = null;
    window._aiSicdUrl = null;
    window._aiEnlazarCodigo = null;
    document.getElementById('modal-agregar-inv').style.display = 'none';
    document.body.style.overflow = '';
    aiForm.reset();
    [['ai-excel-masivo','ai-excel-masivo-txt','Seleccionar Excel (.xlsx, .xls, .csv)'],
     ['ai-boleta-masiva-input','ai-boleta-masiva-txt','Seleccionar PDF, JPG o PNG'],
     ['ai-boleta-manual-input','ai-boleta-manual-txt','Seleccionar PDF, JPG o PNG']
    ].forEach(function(p){
        var el=document.getElementById(p[1]); var lb=document.querySelector('label[for="'+p[0]+'"]');
        if(el) el.textContent=p[2]; if(lb) lb.classList.remove('has-file');
    });
    document.getElementById('ai-seccion-local').style.display = 'none';
    document.getElementById('ai-seccion-externa').style.display = 'none';

    // Reset SICD validation state
    aiSicdValido = false;
    clearTimeout(aiSicdValidTimer);
    var sicdInput = document.getElementById('ai-codigo-sicd');
    if (sicdInput) { sicdInput.style.borderColor = '#d1d5db'; }
    var hintEl = document.getElementById('ai-codigo-hint');
    if (hintEl) hintEl.innerHTML = '';
    var infoEl = document.getElementById('ai-sicd-info');
    if (infoEl) { infoEl.style.display = 'none'; infoEl.innerHTML = ''; }
    var warnEl = document.getElementById('ai-sicd-ya-ingresada');
    if (warnEl) warnEl.style.display = 'none';
    var cargaWrap = document.getElementById('ai-ext-carga-wrap');
    if (cargaWrap) cargaWrap.style.display = 'none';
    var pdfBanner = document.getElementById('ai-pdf-banner');
    if (pdfBanner) pdfBanner.remove();
    _aiSicdDupUrl = null; _aiSicdDupEstado = null; _aiSicdDupCodigo = null;

    aiItems = [];
    aiCounter = 0;
    aiItemsManual = [];
    aiCounterManual = 0;
    document.getElementById('ai-items').innerHTML = '';
    document.getElementById('ai-tabla-wrap').style.display = 'none';
    document.getElementById('ai-sin-items').style.display = 'none';
    document.getElementById('ai-items-manual').innerHTML = '';
    document.getElementById('ai-tabla-manual-wrap').style.display = 'none';
    document.getElementById('ai-sin-items-manual').style.display = 'none';
    aiMetodoCargaActual = 'masiva';
    aiMetodoCarga('masiva');
    var btn = document.getElementById('ai-btn-submit');
    btn.disabled = true;
    btn.style.background = '#9ca3af';
    btn.style.cursor = 'not-allowed';
    btn.textContent = 'Registrar';
}

document.getElementById('modal-agregar-inv').addEventListener('click', function(e) {
    if (e.target === this) cerrarConConfirmacion();
});

function aiFileUpdate(inputId, txtId, input) {
    var lbl = document.querySelector('label[for="' + inputId + '"]');
    var txt = document.getElementById(txtId);
    var defaults = {
        'ai-excel-masivo':       'Seleccionar Excel (.xlsx, .xls, .csv)',
        'ai-boleta-masiva-input':'Seleccionar PDF, JPG o PNG',
        'ai-boleta-manual-input':'Seleccionar PDF, JPG o PNG'
    };
    if (input.files.length > 0) {
        if (txt) txt.textContent = '✓ ' + input.files[0].name;
        if (lbl) lbl.classList.add('has-file');
    } else {
        if (txt) txt.textContent = defaults[inputId] || 'Seleccionar archivo';
        if (lbl) lbl.classList.remove('has-file');
    }
}

function aiMetodoCarga(metodo) {
    aiMetodoCargaActual = metodo;
    var btnM   = document.getElementById('ai-ext-btn-masiva');
    var btnMa  = document.getElementById('ai-ext-btn-manual');
    var panelM  = document.getElementById('ai-ext-panel-masiva');
    var panelMa = document.getElementById('ai-ext-panel-manual');
    var _dm = document.documentElement.classList.contains('dark');

    var offBg = _dm ? '#1e293b' : '#fff';
    var offBd = _dm ? '#334155' : '#e5e7eb';
    var offTx = _dm ? '#94a3b8' : '#6b7280';

    if (metodo === 'masiva') {
        var onBg = _dm ? '#1e3a5f' : '#eff6ff';
        var onBd = _dm ? '#3b82f6' : '#2563eb';
        var onTx = _dm ? '#93c5fd' : '#1e40af';
        btnM.style.borderColor  = onBd;  btnM.style.background  = onBg;  btnM.style.color  = onTx;
        btnMa.style.borderColor = offBd; btnMa.style.background = offBg; btnMa.style.color = offTx;
        panelM.style.display  = 'flex';
        panelMa.style.display = 'none';
    } else {
        var onBg = _dm ? '#2e1065' : '#faf5ff';
        var onBd = _dm ? '#7c3aed' : '#7c3aed';
        var onTx = _dm ? '#c4b5fd' : '#6b21a8';
        btnMa.style.borderColor = onBd;  btnMa.style.background = onBg;  btnMa.style.color = onTx;
        btnM.style.borderColor  = offBd; btnM.style.background  = offBg; btnM.style.color  = offTx;
        panelMa.style.display = 'flex';
        panelM.style.display  = 'none';
    }
    var aiTipo = document.getElementById('ai-tipo').value;
    if (aiTipo === 'externa') { aiActualizarBtnExterna(metodo); }
}

function aiActualizarBtnExterna(metodo) {
    var btn = document.getElementById('ai-btn-submit');
    btn.disabled = false;
    btn.style.cursor = 'pointer';
    if (metodo === 'masiva') {
        btn.style.background = '#2563eb';
        btn.textContent = 'Recibir SICD y cargar Excel';
    } else {
        btn.style.background = '#7c3aed';
        btn.textContent = 'Recibir SICD y registrar productos';
    }
}

function aiCambiarTipo(tipo) {
    var local = document.getElementById('ai-seccion-local');
    var externa = document.getElementById('ai-seccion-externa');
    local.style.display = (tipo === 'local') ? 'flex' : 'none';
    externa.style.display = (tipo === 'externa' || tipo === 'licitacion') ? 'flex' : 'none';

    // Ocultar sección de carga al cambiar tipo — se muestra solo tras validar SICD
    var cargaWrap = document.getElementById('ai-ext-carga-wrap');
    if (cargaWrap) cargaWrap.style.display = 'none';

    var boletaDatos  = document.getElementById('ai-ext-boleta-datos');
    var chkMasiva    = document.getElementById('ai-vincular-oc');
    var chkManual    = document.getElementById('ai-vincular-oc-manual');
    var boletaMasiva = document.getElementById('ai-boleta-masiva');
    var boletaManual = document.getElementById('ai-boleta-manual');

    if (tipo === 'licitacion') {
        if (boletaDatos)  boletaDatos.style.display  = 'none';
        if (chkMasiva)    { chkMasiva.checked = true;  }
        if (chkManual)    { chkManual.checked = true;  }
        if (boletaMasiva) boletaMasiva.style.display  = 'none';
        if (boletaManual) boletaManual.style.display  = 'none';
    } else if (tipo === 'externa') {
        if (boletaDatos)  boletaDatos.style.display  = '';
        if (chkMasiva)    { chkMasiva.checked = false; }
        if (chkManual)    { chkManual.checked = false; }
        if (boletaMasiva) boletaMasiva.style.display  = 'flex';
        if (boletaManual) boletaManual.style.display  = 'flex';
    }

    var btn = document.getElementById('ai-btn-submit');
    if (tipo === 'local') {
        btn.disabled = false;
        btn.style.background = '#d97706';
        btn.style.cursor = 'pointer';
        btn.textContent = 'Registrar compra local';
    } else if (tipo === 'externa' || tipo === 'licitacion') {
        aiActualizarBtnExterna(aiMetodoCargaActual);
    } else {
        btn.disabled = true;
        btn.style.background = '#9ca3af';
        btn.style.cursor = 'not-allowed';
        btn.textContent = 'Registrar';
    }
}

var _aiToastTimer = null;
function aiError(msg, focusId) {
    var toast = document.getElementById('ai-toast-error');
    var label = document.getElementById('ai-toast-msg');
    if (!toast || !label) { alert(msg); return; }
    label.textContent = msg;
    toast.style.display = 'flex';
    if (_aiToastTimer) clearTimeout(_aiToastTimer);
    _aiToastTimer = setTimeout(function() { toast.style.display = 'none'; }, 5000);
    if (focusId) { var el = document.getElementById(focusId); if (el) el.focus(); }
}

function aiEnviar() {
    var tipo = document.getElementById('ai-tipo').value;
    if (!tipo) { aiError('Selecciona el tipo de ingreso.'); return; }

    // Bloquear envío si la SICD ya está ingresada
    if (tipo === 'externa' && _aiSicdDupUrl) {
        document.getElementById('ai-dup-codigo').textContent  = _aiSicdDupCodigo || '';
        document.getElementById('ai-dup-estado').textContent  = _aiSicdDupEstado || '—';
        document.getElementById('ai-dup-ir-btn').href         = _aiSicdDupUrl;
        document.getElementById('ai-modal-dup').style.display = 'flex';
        return;
    }

    if (tipo === 'local') {
        var provNombre = document.getElementById('ai-prov-nombre').value.trim();
        var rut   = document.getElementById('ai-rut').value.trim();
        var folio = document.getElementById('ai-folio').value.trim();
        var fecha = document.getElementById('ai-fecha').value;
        var doc   = document.getElementById('ai-doc').files.length;
        if (!provNombre){ aiError('El nombre del proveedor es obligatorio.', 'ai-prov-nombre'); return; }
        if (!rut)   { aiError('El RUT del proveedor es obligatorio.', 'ai-rut'); return; }
        if (!folio) { aiError('El folio es obligatorio.', 'ai-folio'); return; }
        if (!fecha) { aiError('La fecha de emisión es obligatoria.', 'ai-fecha'); return; }
        if (!doc)   { aiError('La boleta PDF es obligatoria.'); return; }
        if (aiItems.length === 0) { aiError('Agrega al menos un producto.', 'ai-buscador'); return; }
        aiForm.action = aiUrlLocal;
    } else if (tipo === 'externa' || tipo === 'licitacion') {
        if (tipo === 'externa') {
            var extProvNombre = document.getElementById('ai-ext-prov-nombre').value.trim();
            var extRut        = document.getElementById('ai-ext-rut').value.trim();
            var extFolio      = document.getElementById('ai-ext-folio').value.trim();
            var extFecha      = document.getElementById('ai-ext-fecha').value;
            if (!extProvNombre){ aiError('El nombre del proveedor es obligatorio.', 'ai-ext-prov-nombre'); return; }
            if (!extRut)   { aiError('El RUT del proveedor es obligatorio.', 'ai-ext-rut'); return; }
            if (!extFolio) { aiError('El folio es obligatorio.', 'ai-ext-folio'); return; }
            if (!extFecha) { aiError('La fecha de emisión es obligatoria.', 'ai-ext-fecha'); return; }
        }
        var codigoSicd = document.getElementById('ai-codigo-sicd').value.trim();
        if (!codigoSicd) {
            aiError('El código SICD es obligatorio.', 'ai-codigo-sicd');
            return;
        }
        if (!aiSicdValido) {
            aiError('El código SICD "' + codigoSicd + '" no está validado en el sistema externo. Verifica el código antes de continuar.', 'ai-codigo-sicd');
            return;
        }
        if (aiMetodoCargaActual === 'masiva') {
            if (!document.getElementById('ai-excel-masivo').files.length) {
                aiError('El archivo Excel de productos es obligatorio.'); return;
            }
            var vincularOc = document.getElementById('ai-vincular-oc').checked;
            if (!vincularOc && !document.getElementById('ai-boleta-masiva-input').files.length) {
                aiError('La boleta/factura es obligatoria cuando no se asigna a una Orden de Compra.'); return;
            }
            aiForm.action = aiUrlMasiva;
        } else {
            if (aiItemsManual.length === 0) { aiError('Agrega al menos un producto.', 'ai-buscador-manual'); return; }
            var vincularOcManual = document.getElementById('ai-vincular-oc-manual').checked;
            if (!vincularOcManual && !document.getElementById('ai-boleta-manual-input').files.length) {
                aiError('La boleta/factura es obligatoria cuando no se asigna a una Orden de Compra.'); return;
            }
            aiForm.action = aiUrlManual;
        }
    }

    // Deshabilitar todos los inputs del panel inactivo para que no pisen los del activo
    var tipo = document.getElementById('ai-tipo').value;
    if (tipo === 'local') {
        // Deshabilitar campos de la sección externa que tienen el mismo name
        ['ai-ext-prov-nombre', 'ai-ext-rut', 'ai-ext-folio', 'ai-ext-fecha',
         'ai-boleta-masiva-input', 'ai-boleta-manual-input',
         'ai-codigo-sicd', 'ai-descripcion',
         'ai-vincular-oc', 'ai-vincular-oc-manual'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.disabled = true;
        });
    } else {
        // Deshabilitar campos de la sección local
        ['ai-prov-nombre', 'ai-rut', 'ai-folio', 'ai-fecha', 'ai-doc'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.disabled = true;
        });
        // Deshabilitar boleta del panel inactivo
        if (aiMetodoCargaActual === 'masiva') {
            var inactivo = document.getElementById('ai-boleta-manual-input');
            if (inactivo) inactivo.disabled = true;
        } else {
            var inactivo = document.getElementById('ai-boleta-masiva-input');
            if (inactivo) inactivo.disabled = true;
        }
    }

    var preenlazadoInput = document.getElementById('ai-sicd-preenlazado-id');
    if (preenlazadoInput) preenlazadoInput.value = _aiSicdEnlazadoId || '';
    sessionStorage.removeItem('ai_sicd_pending');
    sessionStorage.removeItem('ai_prods_creados');
    _aiProductosCreados = [];
    aiLimpiarPreciosManual();
    aiForm.submit();
}

// Envío cuando el usuario confirma un duplicado desde el banner de sesión
document.getElementById('form-agregar-inv').addEventListener('submit-confirmed', function() {
    var tipo = document.getElementById('ai-tipo').value;
    if (tipo === 'externa' || tipo === 'licitacion') {
        if (aiMetodoCargaActual === 'masiva') {
            aiForm.action = aiUrlMasiva;
        } else {
            aiForm.action = aiUrlManual;
        }
    }
    sessionStorage.removeItem('ai_sicd_pending');
    sessionStorage.removeItem('ai_prods_creados');
    _aiProductosCreados = [];
    aiLimpiarPreciosManual();
    aiForm.submit();
});

// ── Validación en tiempo real del código SICD contra sistema externo ──────
var aiSicdValidTimer = null;
var aiSicdValido = false;
var aiUrlValidar = '{{ route('admin.sicd.validar') }}';

function aiToggleBoleta(panel) {
    if (panel === 'masiva') {
        var chk = document.getElementById('ai-vincular-oc');
        var box = document.getElementById('ai-boleta-masiva');
        if (box) box.style.display = chk && chk.checked ? 'none' : 'flex';
    } else {
        var chk = document.getElementById('ai-vincular-oc-manual');
        var box = document.getElementById('ai-boleta-manual');
        if (box) box.style.display = chk && chk.checked ? 'none' : 'flex';
    }
}

function aiEnlazarSolicitud() {
    var btn = document.getElementById('ai-btn-enlazar');
    if (!btn) return;

    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;">'
        + '<svg style="width:13px;height:13px;animation:ai-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>'
        + 'Enlazando...</span>';

    function doPost(enlazarUrl, sicdUrl) {
        fetch(enlazarUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(function(r) {
            if (!r.ok) return r.json().then(function(e) { throw new Error(e.msg || 'HTTP ' + r.status); });
            return r.json();
        })
        .then(function(res) {
            btn.disabled = false;
            if (res.ok) {
                if (res.id) { _aiSicdEnlazadoId = res.id; sessionStorage.setItem('ai_sicd_pending', String(res.id)); }
                var url = sicdUrl || res.url || null;
                if (url) {
                    var link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    link.textContent = '✓ Ver SICD';
                    link.style.cssText = 'font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;padding:3px 12px;border-radius:5px;text-decoration:none;';
                    btn.replaceWith(link);
                } else {
                    btn.innerHTML = '✓ PDF SICD enlazado';
                    btn.style.cssText += ';background:#dcfce7;color:#166534;cursor:default;';
                }
            } else {
                btn.textContent = 'Enlazar PDF SICD';
                btn.style.background = '#fee2e2'; btn.style.color = '#991b1b';
                setTimeout(function() { btn.style.background = '#e0e7ff'; btn.style.color = '#3730a3'; btn.textContent = 'Enlazar PDF SICD'; btn.disabled = false; }, 3000);
            }
        })
        .catch(function(e) {
            btn.disabled = false;
            btn.textContent = 'Error: ' + (e.message || 'conexión');
            btn.style.background = '#fee2e2'; btn.style.color = '#991b1b';
            setTimeout(function() { btn.style.background = '#e0e7ff'; btn.style.color = '#3730a3'; btn.textContent = 'Enlazar PDF SICD'; }, 4000);
        });
    }

    // Si la URL ya fue resuelta por el pre-fetch, usar directamente
    if (window._aiEnlazarUrl) {
        doPost(window._aiEnlazarUrl, window._aiSicdUrl);
        return;
    }

    // Si no (SICD no existe aún), intentar buscar primero y si sigue sin existir, crear y enlazar
    var codigo = window._aiEnlazarCodigo;
    if (!codigo) {
        btn.disabled = false; btn.textContent = 'Enlazar PDF SICD';
        return;
    }
    fetch('{{ route("admin.sicd.buscar-por-codigo") }}?codigo=' + encodeURIComponent(codigo))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.encontrado) {
                window._aiEnlazarUrl = d.enlazar_url;
                window._aiSicdUrl    = d.url;
                doPost(d.enlazar_url, d.url);
                return;
            }
            // No existe: crear SICD y enlazar en un solo paso
            var fd = new FormData();
            fd.append('codigo', codigo);
            fetch('{{ route("admin.sicd.crear-y-enlazar") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: fd,
            })
            .then(function(r) {
                if (!r.ok) return r.json().then(function(e) { throw new Error(e.msg || 'HTTP ' + r.status); });
                return r.json();
            })
            .then(function(res) {
                btn.disabled = false;
                if (res.ok) {
                    if (res.id) { _aiSicdEnlazadoId = res.id; sessionStorage.setItem('ai_sicd_pending', String(res.id)); }
                    btn.innerHTML = '✓ PDF SICD enlazado';
                    btn.style.cssText += ';background:#dcfce7;color:#166534;cursor:default;';
                } else {
                    btn.textContent = 'Error: ' + (res.msg || 'desconocido');
                    btn.style.background = '#fee2e2'; btn.style.color = '#991b1b';
                    setTimeout(function() { btn.style.background = '#e0e7ff'; btn.style.color = '#3730a3'; btn.textContent = 'Enlazar PDF SICD'; btn.disabled = false; }, 4000);
                }
            })
            .catch(function(e) {
                btn.disabled = false;
                btn.textContent = 'Error: ' + (e.message || 'conexión');
                btn.style.background = '#fee2e2'; btn.style.color = '#991b1b';
                setTimeout(function() { btn.style.background = '#e0e7ff'; btn.style.color = '#3730a3'; btn.textContent = 'Enlazar PDF SICD'; }, 4000);
            });
        })
        .catch(function() {
            btn.disabled = false; btn.textContent = 'Error de conexión';
            setTimeout(function() { btn.textContent = 'Enlazar PDF SICD'; }, 3000);
        });
}

function aiValidarCodigo(codigo) {
    var hint = document.getElementById('ai-codigo-hint');
    var info = document.getElementById('ai-sicd-info');
    info.style.display = 'none';
    info.textContent = '';

    if (!codigo) {
        hint.innerHTML = '';
        aiSicdValido = false;
        return;
    }

    hint.innerHTML = '<span style="color:#6b7280;">⏳ Verificando...</span>';

    fetch(aiUrlValidar + '?codigo=' + encodeURIComponent(codigo))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            // Número de compra autorizada: requiere selección de año
            if (data.tipo === 'num_sol_compra_aut' && !data.resuelto) {
                aiSicdValido = false;
                var _dm2 = document.documentElement.classList.contains('dark');
                var btnBg = _dm2 ? '#1e1b4b' : '#e0e7ff';
                var btnTx = _dm2 ? '#a5b4fc' : '#3730a3';
                var h = '<div style="margin-top:0.3rem;display:flex;flex-direction:column;gap:0.4rem;">'
                      + '<span style="font-size:0.73rem;color:#f59e0b;font-weight:600;">N° compra <strong>' + data.num + '</strong> encontrado — selecciona el año:</span>'
                      + '<div style="display:flex;gap:0.4rem;flex-wrap:wrap;">';
                data.anios.forEach(function(anio) {
                    h += '<button onclick="aiResolverNumSolCompra(\'' + data.num + '\',\'' + anio + '\')" '
                       + 'style="font-size:0.75rem;font-weight:600;padding:4px 14px;border-radius:6px;border:none;cursor:pointer;background:' + btnBg + ';color:' + btnTx + ';">'
                       + anio + '</button>';
                });
                h += '</div></div>';
                hint.innerHTML = h;
                return;
            }

            if (data.valido) {
                aiSicdValido = true;
                var cargaWrap = document.getElementById('ai-ext-carga-wrap');
                if (cargaWrap) cargaWrap.style.display = 'flex';
                // Si se buscó por ID numérico, reemplazar el campo con el código real
                var codigoFinal = data.codigo_resuelto || codigo;
                if (codigoFinal !== codigo) {
                    document.getElementById('ai-codigo-sicd').value = codigoFinal;
                }
                hint.innerHTML = '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span style="color:#16a34a;font-weight:600;">Código válido en el sistema</span>';
                document.getElementById('ai-codigo-sicd').style.borderColor = '#16a34a';

                // Verificar si esta SICD ya existe en el sistema interno
                _aiSicdDupUrl    = null;
                _aiSicdDupEstado = null;
                _aiSicdDupCodigo = null;
                fetch('{{ route("admin.sicd.buscar-por-codigo") }}?codigo=' + encodeURIComponent(codigoFinal))
                    .then(function(r) { return r.json(); })
                    .then(function(interno) {
                        var warnEl = document.getElementById('ai-sicd-ya-ingresada');
                        if (interno.encontrado && interno.tiene_detalles) {
                            var estadosLabel = { recibido:'Recibida', pendiente:'Pendiente / En OC' };
                            _aiSicdDupUrl    = interno.url;
                            _aiSicdDupEstado = estadosLabel[interno.estado] || interno.estado || '—';
                            _aiSicdDupCodigo = codigoFinal;
                            var leve = document.getElementById('ai-sicd-ya-estado-leve');
                            if (leve) leve.textContent = _aiSicdDupEstado;
                            if (warnEl) warnEl.style.display = 'flex';
                        } else {
                            _aiSicdDupUrl = null; _aiSicdDupEstado = null; _aiSicdDupCodigo = null;
                            if (warnEl) warnEl.style.display = 'none';
                        }
                    })
                    .catch(function() { _aiSicdDupUrl = null; if (document.getElementById('ai-sicd-ya-ingresada')) document.getElementById('ai-sicd-ya-ingresada').style.display = 'none'; });

                info.style.display = 'block';

                // Paleta adaptativa según dark/light mode
                var _dm = document.documentElement.classList.contains('dark');
                var _sc = {
                    infoBg:    _dm ? '#162032'              : '#f0fdf4',
                    infoBd:    _dm ? '#334155'              : '#bbf7d0',
                    infoTx:    _dm ? '#94a3b8'              : '',
                    theadBg:   _dm ? '#052e16'              : '#dcfce7',
                    theadTx:   _dm ? '#86efac'              : '#14532d',
                    theadBd:   _dm ? '#166534'              : '#bbf7d0',
                    rowOdd:    _dm ? '#162032'              : '#f0fdf4',
                    rowEven:   _dm ? '#1e293b'              : '#ffffff',
                    estadoBg:  _dm ? '#1e1b4b'              : '#eff6ff',
                    estadoTx:  _dm ? '#93c5fd'              : '#1d4ed8',
                    estadoBd:  _dm ? '#3730a3'              : '#bfdbfe',
                    pdfBg:     _dm ? '#1c1100'              : '#fff7ed',
                    pdfBd:     _dm ? '#78350f'              : '#fed7aa',
                    pdfTx:     _dm ? '#fb923c'              : '#c2410c',
                };
                // Aplicar el fondo del info box
                info.style.background = _sc.infoBg;
                info.style.borderColor = _sc.infoBd;
                if (_sc.infoTx) info.style.color = _sc.infoTx;

                var sicdEstadoLabels = {
                    1: 'Ingresada', 2: 'Enviada', 3: 'Aprobada',
                    4: 'Rechazada', 5: 'En despacho', 6: 'Recibida'
                };
                var fmtEstado = function(val) {
                    var n = parseInt(val, 10);
                    var label = sicdEstadoLabels[n] || 'Desconocido';
                    return '<span style="background:' + _sc.estadoBg + ';color:' + _sc.estadoTx + ';border:1px solid ' + _sc.estadoBd + ';border-radius:4px;padding:1px 6px;font-weight:600;white-space:nowrap;">' + n + ' — ' + label + '</span>';
                };
                var estadoGeneral = data.estado != null ? fmtEstado(data.estado) : '—';
                var html = '<strong>Centro de costo:</strong> ' + (data.centro_costo || '—')
                         + ' &nbsp;·&nbsp; <strong>Fecha:</strong> ' + (data.fecha || '—').trim()
                         + ' &nbsp;·&nbsp; <strong>Estado:</strong> ' + estadoGeneral;
                if (data.detalles && data.detalles.length > 0) {
                    html += '<div style="margin-top:0.45rem;overflow-x:auto;">';
                    html += '<table style="width:100%;border-collapse:collapse;font-size:0.7rem;">';
                    html += '<thead><tr style="background:' + _sc.theadBg + ';color:' + _sc.theadTx + ';">'
                          + '<th style="padding:3px 6px;text-align:left;border-bottom:1px solid ' + _sc.theadBd + ';">Ítem</th>'
                          + '<th style="padding:3px 6px;text-align:left;border-bottom:1px solid ' + _sc.theadBd + ';">Detalle</th>'
                          + '<th style="padding:3px 6px;text-align:right;border-bottom:1px solid ' + _sc.theadBd + ';">Cant.</th>'
                          + '<th style="padding:3px 6px;text-align:left;border-bottom:1px solid ' + _sc.theadBd + ';">Unidad</th>'
                          + '<th style="padding:3px 6px;text-align:right;border-bottom:1px solid ' + _sc.theadBd + ';">V. Unit.</th>'
                          + '<th style="padding:3px 6px;text-align:right;border-bottom:1px solid ' + _sc.theadBd + ';">Total Neto</th>'
                          + '</tr></thead><tbody>';
                    data.detalles.forEach(function(d, i) {
                        var bg = i % 2 === 0 ? _sc.rowOdd : _sc.rowEven;
                        html += '<tr style="background:' + bg + ';">'
                              + '<td style="padding:3px 6px;">' + (d.item_presup || '—') + '</td>'
                              + '<td style="padding:3px 6px;">' + ((d.detalle || '').replace(/(\s+UND)+$/i, '').trim() || '—') + '</td>'
                              + '<td style="padding:3px 6px;text-align:right;">' + (d.cantidad ?? '—') + '</td>'
                              + '<td style="padding:3px 6px;">' + (d.unidad || '—') + '</td>'
                              + '<td style="padding:3px 6px;text-align:right;">' + (d.valor_unitario != null ? '$' + Number(d.valor_unitario).toLocaleString('es-CL') : '—') + '</td>'
                              + '<td style="padding:3px 6px;text-align:right;">' + (d.total_neto != null ? '$' + Number(d.total_neto).toLocaleString('es-CL') : '—') + '</td>'
                              + '</tr>';
                    });
                    html += '</tbody></table></div>';
                } else {
                    html += '<br><em style="color:#6b7280;">Sin detalles de compra registrados.</em>';
                }
                // Banner PDF: spinner mientras carga, luego resultado
                html += '<div id="ai-pdf-banner" style="margin-top:0.6rem;background:' + _sc.pdfBg + ';border:1px solid ' + _sc.pdfBd + ';border-radius:0.6rem;padding:0.5rem 0.85rem;display:flex;align-items:center;gap:0.5rem;">'
                      + '<svg style="width:15px;height:15px;flex-shrink:0;animation:spin 1s linear infinite;color:' + _sc.pdfTx + ';" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m6.364 1.636-2.121 2.121M21 12h-3m-1.636 6.364-2.121-2.121M12 21v-3m-6.364-1.636 2.121-2.121M3 12h3m1.636-6.364 2.121 2.121"/></svg>'
                      + '<span style="font-size:0.72rem;color:' + _sc.pdfTx + ';font-weight:500;">Verificando archivo asociado...</span>'
                      + '</div>';
                info.innerHTML = html;

                // Check PDF en paralelo sin bloquear la validación
                var urlVerificarPdf = '{{ route("admin.sicd.verificar.pdf") }}?codigo=' + encodeURIComponent(codigoFinal);
                var urlPdf = '{{ route("admin.sicd.pdf.externo") }}?codigo=' + encodeURIComponent(codigoFinal);
                fetch(urlVerificarPdf)
                    .then(function(r) { return r.json(); })
                    .then(function(pdf) {
                        var banner = document.getElementById('ai-pdf-banner');
                        if (!banner) return;
                        if (pdf.tiene_pdf) {
                            // Mostrar banner con botón siempre; resolver URL al hacer clic
                                window._aiEnlazarCodigo = codigoFinal;
                                window._aiEnlazarUrl    = null;
                                window._aiSicdUrl       = null;

                                // Pre-buscar SICD pero sin bloquear el banner
                                fetch('{{ route("admin.sicd.buscar-por-codigo") }}?codigo=' + encodeURIComponent(codigoFinal))
                                    .then(function(r) { return r.json(); })
                                    .then(function(sicdData) {
                                        if (sicdData.encontrado) {
                                            window._aiEnlazarUrl = sicdData.enlazar_url;
                                            window._aiSicdUrl    = sicdData.url;
                                            if (sicdData.ya_enlazado) {
                                                var btn = document.getElementById('ai-btn-enlazar');
                                                if (btn) {
                                                    var link = document.createElement('a');
                                                    link.href = sicdData.url;
                                                    link.target = '_blank';
                                                    link.textContent = '✓ Ver SICD enlazado';
                                                    var enlazBg = _dm ? '#052e16' : '#dcfce7';
                                                    var enlazTx = _dm ? '#86efac' : '#166534';
                                                    link.style.cssText = 'font-size:0.7rem;font-weight:600;background:' + enlazBg + ';color:' + enlazTx + ';padding:3px 12px;border-radius:5px;text-decoration:none;';
                                                    btn.replaceWith(link);
                                                }
                                            }
                                        }
                                    })
                                    .catch(function() {});

                                var enlazarBg = _dm ? '#1e1b4b' : '#e0e7ff';
                                var enlazarTx = _dm ? '#a5b4fc' : '#3730a3';
                                banner.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;width:100%;">'
                                    + '<div style="display:flex;align-items:center;gap:0.45rem;">'
                                    + '<svg style="width:15px;height:15px;flex-shrink:0;color:' + _sc.pdfTx + ';" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>'
                                    + '<span style="font-size:0.72rem;font-weight:700;color:' + _sc.pdfTx + ';">ARCHIVO ASOCIADO ENCONTRADO</span>'
                                    + '</div>'
                                    + '<div style="display:flex;gap:0.4rem;align-items:center;">'
                                    + '<a href="' + urlPdf + '" target="_blank" style="font-size:0.7rem;font-weight:600;background:#ea580c;color:#fff;padding:3px 12px;border-radius:5px;text-decoration:none;">Ver PDF</a>'
                                    + '<button id="ai-btn-enlazar" onclick="aiEnlazarSolicitud()" style="font-size:0.7rem;font-weight:600;background:' + enlazarBg + ';color:' + enlazarTx + ';padding:3px 12px;border-radius:5px;border:none;cursor:pointer;">Enlazar PDF SICD</button>'
                                    + '</div>'
                                    + '</div>';
                        } else {
                            banner.remove();
                        }
                    })
                    .catch(function() {
                        var banner = document.getElementById('ai-pdf-banner');
                        if (banner) banner.remove();
                    });
            } else {
                aiSicdValido = false;
                var cargaWrapErr = document.getElementById('ai-ext-carga-wrap');
                if (cargaWrapErr) cargaWrapErr.style.display = 'none';
                hint.innerHTML = '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" stroke="#dc2626" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg><span style="color:#dc2626;font-weight:600;">' + data.mensaje + '</span>';
                document.getElementById('ai-codigo-sicd').style.borderColor = '#dc2626';
            }
        })
        .catch(function() {
            aiSicdValido = false;
            var cargaWrapCatch = document.getElementById('ai-ext-carga-wrap');
            if (cargaWrapCatch) cargaWrapCatch.style.display = 'none';
            hint.innerHTML = '<span style="color:#d97706;">⚠️ Sin conexión al sistema externo.</span>';
            document.getElementById('ai-codigo-sicd').style.borderColor = '#d1d5db';
        });
}

function aiResolverNumSolCompra(num, anio) {
    var hint = document.getElementById('ai-codigo-hint');
    var info = document.getElementById('ai-sicd-info');
    info.style.display = 'none';
    hint.innerHTML = '<span style="color:#6b7280;">⏳ Verificando año ' + anio + '...</span>';

    fetch(aiUrlValidar + '?codigo=' + encodeURIComponent(num) + '&anio=' + encodeURIComponent(anio))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.codigo_resuelto) {
                document.getElementById('ai-codigo-sicd').value = data.codigo_resuelto;
                aiValidarCodigo(data.codigo_resuelto);
            } else {
                hint.innerHTML = '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" stroke="#dc2626" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>'
                              + '<span style="color:#dc2626;font-weight:600;">Sin SICD para el año ' + anio + '.</span>';
            }
        })
        .catch(function() {
            hint.innerHTML = '<span style="color:#d97706;">⚠️ Error de conexión.</span>';
        });
}

function aiOcultarAdvertenciaSicd() {
    _aiSicdDupUrl = null; _aiSicdDupEstado = null; _aiSicdDupCodigo = null;
}

document.getElementById('ai-codigo-sicd').addEventListener('input', function() {
    var codigo = this.value.trim();
    this.style.borderColor = '#d1d5db';
    document.getElementById('ai-codigo-hint').innerHTML = '';
    document.getElementById('ai-sicd-info').style.display = 'none';
    _aiSicdDupUrl = null; _aiSicdDupEstado = null; _aiSicdDupCodigo = null;
    var warnEl = document.getElementById('ai-sicd-ya-ingresada');
    if (warnEl) warnEl.style.display = 'none';
    aiSicdValido = false;
    var cargaWrapInput = document.getElementById('ai-ext-carga-wrap');
    if (cargaWrapInput) cargaWrapInput.style.display = 'none';
    clearTimeout(aiSicdValidTimer);
    if (codigo.length >= 3) {
        aiSicdValidTimer = setTimeout(function() { aiValidarCodigo(codigo); }, 600);
    }
});

document.getElementById('ai-buscador').addEventListener('input', function() {
    var qOrig = this.value.trim();
    var q     = qOrig.toLowerCase();
    var res   = document.getElementById('ai-resultados');
    if (q.length < 1) { res.style.display = 'none'; return; }
    var matches = aiProductos.filter(function(p) {
        return p.nombre.toLowerCase().indexOf(q) >= 0;
    }).slice(0, 10);

    var _dm        = document.documentElement.classList.contains('dark');
    var rowBorder  = _dm ? '#334155'  : '#f3f4f6';
    var rowHover   = _dm ? '#312e81'  : '#fef3c7';
    var rowText    = _dm ? '#e2e8f0'  : '#1f2937';
    var rowSub     = _dm ? '#94a3b8'  : '#6b7280';
    var crearBg    = _dm ? '#052e16'  : '#f0fdf4';
    var crearBd    = _dm ? '#166534'  : '#e5e7eb';
    var crearHover = _dm ? '#14532d'  : '#dcfce7';
    var crearTx    = _dm ? '#86efac'  : '#16a34a';

    var html = matches.map(function(p) {
        return '<div onclick="aiAgregar(' + p.id + ',\'' + p.nombre.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\')"'
            + ' style="padding:0.5rem 0.75rem;cursor:pointer;border-bottom:1px solid ' + rowBorder + ';"'
            + ' onmouseover="this.style.background=\'' + rowHover + '\'" onmouseout="this.style.background=\'\'">'
            + '<p style="font-size:0.8rem;font-weight:600;color:' + rowText + ';">' + escHtmlAi(p.nombre) + '</p>'
            + '<p style="font-size:0.72rem;color:' + rowSub + ';">Stock: ' + p.stock + '</p>'
            + '</div>';
    }).join('');

    if (AI_IS_ADMIN) {
        var qEsc = qOrig.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        html += '<div data-crear-nombre="' + qEsc + '" onclick="aiCrearContexto=\'local\'; aiAbrirModalCrear(this.dataset.crearNombre)"'
            + ' style="padding:0.5rem 0.75rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem;border-top:1px solid ' + crearBd + ';background:' + crearBg + ';"'
            + ' onmouseover="this.style.background=\'' + crearHover + '\'" onmouseout="this.style.background=\'' + crearBg + '\'">'
            + '<span style="font-size:1rem;line-height:1;">➕</span>'
            + '<p style="font-size:0.8rem;font-weight:700;color:' + crearTx + ';">Crear producto «' + qEsc + '»</p>'
            + '</div>';
    }

    if (!html) { res.style.display = 'none'; return; }
    res.innerHTML = html;
    res.style.display = 'block';
});

document.addEventListener('click', function(e) {
    var res = document.getElementById('ai-resultados');
    if (res && !e.target.closest('#ai-buscador') && !e.target.closest('#ai-resultados')) {
        res.style.display = 'none';
    }
    var resM = document.getElementById('ai-resultados-manual');
    if (resM && !e.target.closest('#ai-buscador-manual') && !e.target.closest('#ai-resultados-manual')) {
        resM.style.display = 'none';
    }
});

document.getElementById('ai-buscador-manual').addEventListener('input', function() {
    var qOrig = this.value.trim();
    var q     = qOrig.toLowerCase();
    var res = document.getElementById('ai-resultados-manual');
    if (q.length < 1) { res.style.display = 'none'; return; }
    var matches = aiProductos.filter(function(p) {
        return p.nombre.toLowerCase().indexOf(q) >= 0;
    }).slice(0, 10);

    var _dm        = document.documentElement.classList.contains('dark');
    var rowBorder  = _dm ? '#334155'  : '#f3f4f6';
    var rowHover   = _dm ? '#312e81'  : '#f3e8ff';
    var rowText    = _dm ? '#e2e8f0'  : '#1f2937';
    var rowSub     = _dm ? '#94a3b8'  : '#6b7280';
    var crearBg    = _dm ? '#052e16'  : '#f0fdf4';
    var crearBd    = _dm ? '#166534'  : '#e5e7eb';
    var crearHover = _dm ? '#14532d'  : '#dcfce7';
    var crearTx    = _dm ? '#86efac'  : '#16a34a';

    var html = matches.map(function(p) {
        return '<div data-pid="' + p.id + '" data-pnombre="' + escHtmlAi(p.nombre) + '" data-pcid="' + (p.contenedor_id || '') + '"'
            + ' onclick="aiAgregarManualDesdeDato(this)"'
            + ' style="padding:0.5rem 0.75rem;cursor:pointer;border-bottom:1px solid ' + rowBorder + ';"'
            + ' onmouseover="this.style.background=\'' + rowHover + '\'" onmouseout="this.style.background=\'\'">'
            + '<p style="font-size:0.8rem;font-weight:600;color:' + rowText + ';">' + escHtmlAi(p.nombre) + '</p>'
            + '<p style="font-size:0.72rem;color:' + rowSub + ';">' + escHtmlAi(p.contenedor_nombre || '') + ' &middot; Stock: ' + p.stock + '</p>'
            + '</div>';
    }).join('');

    if (AI_IS_ADMIN) {
        var qEsc = qOrig.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        html += '<div data-crear-nombre="' + qEsc + '" onclick="aiAbrirModalCrear(this.dataset.crearNombre)"'
            + ' style="padding:0.5rem 0.75rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem;border-top:1px solid ' + crearBd + ';background:' + crearBg + ';"'
            + ' onmouseover="this.style.background=\'' + crearHover + '\'" onmouseout="this.style.background=\'' + crearBg + '\'">'
            + '<span style="font-size:1rem;line-height:1;">➕</span>'
            + '<div>'
            + '<p style="font-size:0.8rem;font-weight:700;color:' + crearTx + ';">Crear producto «' + qEsc + '»</p>'
            + '</div>'
            + '</div>';
    }

    if (!html) { res.style.display = 'none'; return; }
    res.innerHTML = html;
    res.style.display = 'block';
});

function aiAgregarManual(id, nombre, contenedorId) {
    if (aiItemsManual.find(function(i) { return i.id === id; })) {
        document.getElementById('ai-buscador-manual').value = '';
        document.getElementById('ai-resultados-manual').style.display = 'none';
        return;
    }
    var idx = aiCounterManual++;
    aiItemsManual.push({ idx: idx, id: id, nombre: nombre, contenedorId: contenedorId });
    aiRenderFilaManual(idx, id, nombre, contenedorId || null);
    document.getElementById('ai-buscador-manual').value = '';
    document.getElementById('ai-resultados-manual').style.display = 'none';
    aiActualizarTablaManual();
}

function aiAgregarManualDesdeDato(el) {
    aiAgregarManual(parseInt(el.dataset.pid), el.dataset.pnombre, parseInt(el.dataset.pcid) || null);
}

function aiRenderFilaManual(idx, id, nombre, contenedorId) {
    var tbody = document.getElementById('ai-items-manual');
    var tr = document.createElement('tr');
    tr.id = 'ai-row-manual-' + idx;
    tr.style.borderBottom = '1px solid #f3f4f6';

    var contOptions = aiContainers.map(function(c) {
        var sel = (c.id == contenedorId) ? ' selected' : '';
        return '<option value="' + c.id + '"' + sel + '>' + escHtmlAi(c.nombre) + '</option>';
    }).join('');

    // Unidad: si el producto ya tiene asignada, mostrar como badge bloqueado
    var unidadValor = aiGetUnidad(id);
    var tienUnidad  = unidadValor && unidadValor !== '—';
    var unidadHtml;
    if (tienUnidad) {
        // Solo lectura — hidden input para enviar el valor, badge visual
        unidadHtml = '<input type="hidden" name="items_manual[' + idx + '][unidad]" value="' + escHtmlAi(unidadValor) + '">'
                   + '<span title="Unidad asignada al producto (no editable)"'
                   + ' style="display:inline-block;font-size:0.72rem;font-weight:700;font-family:monospace;'
                   + 'padding:2px 8px;border-radius:9999px;background:#eff6ff;color:#2563eb;'
                   + 'border:1px solid #bfdbfe;cursor:default;white-space:nowrap;">'
                   + escHtmlAi(unidadValor) + '</span>';
    } else {
        // Editable — el producto no tiene unidad de medida definida
        unidadHtml = '<input type="text" name="items_manual[' + idx + '][unidad]" value="" placeholder="—"'
                   + ' style="width:68px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">';
    }

    tr.innerHTML =
        '<td style="padding:0.4rem 0.6rem;">'
        + '<input type="hidden" name="items_manual[' + idx + '][producto_id]" value="' + id + '">'
        + '<span style="font-size:0.8rem;font-weight:500;color:#1f2937;">' + escHtmlAi(nombre) + '</span>'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" id="ai-cant-' + idx + '" name="items_manual[' + idx + '][cantidad]" value="1" min="1"'
        + ' style="width:62px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + unidadHtml
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="text" inputmode="numeric" id="ai-pneto-' + idx + '" name="items_manual[' + idx + '][precio_neto]" placeholder="$0"'
        + ' style="width:110px;text-align:right;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="text" inputmode="numeric" id="ai-ptotal-' + idx + '" name="items_manual[' + idx + '][precio_total]" placeholder="$0"'
        + ' style="width:110px;text-align:right;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;">'
        + '<select name="items_manual[' + idx + '][contenedor_id]"'
        + ' style="width:100%;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.78rem;background:#fff;">'
        + contOptions
        + '</select>'
        + '</td>'
        + '<td style="padding:0.4rem 0.3rem;text-align:center;white-space:nowrap;">'
        + '<button type="button" onclick="aiEditarManual(' + idx + ',\'' + escHtmlAi(nombre).replace(/'/g,"\\'") + '\')" title="Cambiar producto" style="color:#6b7280;background:none;border:none;cursor:pointer;font-size:0.95rem;line-height:1;margin-right:0.25rem;">&#9998;</button>'
        + '<button type="button" onclick="aiQuitarManual(' + idx + ')" title="Quitar" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;">&#x2715;</button>'
        + '</td>';
    tbody.appendChild(tr);

    var inpCant  = document.getElementById('ai-cant-'   + idx);
    var inpNeto  = document.getElementById('ai-pneto-'  + idx);
    var inpTotal = document.getElementById('ai-ptotal-' + idx);

    inpNeto.addEventListener('input', function() {
        var cant  = parseFloat(inpCant.value) || 1;
        var neto  = aiParseClp(this.value);
        inpTotal.value = neto > 0 ? aiFormatClp(Math.round(neto * cant)) : '';
    });
    inpNeto.addEventListener('blur', function() {
        var neto = aiParseClp(this.value);
        this.value = neto > 0 ? aiFormatClp(neto) : '';
    });
    inpTotal.addEventListener('input', function() {
        var cant  = parseFloat(inpCant.value) || 1;
        var total = aiParseClp(this.value);
        inpNeto.value = (total > 0 && cant > 0) ? aiFormatClp(total / cant) : '';
    });
    inpTotal.addEventListener('blur', function() {
        var total = aiParseClp(this.value);
        this.value = total > 0 ? aiFormatClp(total) : '';
    });
    inpCant.addEventListener('input', function() {
        var cant = parseFloat(this.value) || 1;
        var neto = aiParseClp(inpNeto.value);
        if (neto > 0) inpTotal.value = aiFormatClp(Math.round(neto * cant));
    });
}

function aiEditarManual(idx, nombre) {
    var row = document.getElementById('ai-row-manual-' + idx);
    if (row) row.style.opacity = '0.4';
    aiAbrirModalCrear(nombre, idx);
}

function aiQuitarManual(idx) {
    aiItemsManual = aiItemsManual.filter(function(i) { return i.idx !== idx; });
    var row = document.getElementById('ai-row-manual-' + idx);
    if (row) row.remove();
    aiActualizarTablaManual();
}


function aiActualizarTablaManual() {
    var wrap = document.getElementById('ai-tabla-manual-wrap');
    var sin  = document.getElementById('ai-sin-items-manual');
    wrap.style.display = aiItemsManual.length ? '' : 'none';
    sin.style.display  = aiItemsManual.length ? 'none' : '';
}

function aiAgregar(id, nombre) {
    if (aiItems.find(function(i) { return i.id === id; })) {
        document.getElementById('ai-buscador').value = '';
        document.getElementById('ai-resultados').style.display = 'none';
        return;
    }
    var idx = aiCounter++;
    aiItems.push({ idx: idx, id: id, nombre: nombre });
    aiRenderFila(idx, id, nombre);
    document.getElementById('ai-buscador').value = '';
    document.getElementById('ai-resultados').style.display = 'none';
    aiActualizarTabla();
}

function aiRenderFila(idx, id, nombre) {
    var tbody = document.getElementById('ai-items');
    var tr = document.createElement('tr');
    tr.id = 'ai-row-' + idx;
    tr.style.borderBottom = '1px solid #f3f4f6';
    var contOpts = aiContainers.map(function(c) {
        return '<option value="' + c.id + '">' + escHtmlAi(c.nombre) + '</option>';
    }).join('');
    tr.innerHTML =
        '<td style="padding:0.4rem 0.6rem;">'
        + '<input type="hidden" name="items[' + idx + '][producto_id]" value="' + id + '">'
        + '<span style="font-size:0.8rem;font-weight:500;color:#1f2937;">' + escHtmlAi(nombre) + '</span>'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" id="ai-loc-cant-' + idx + '" name="items[' + idx + '][cantidad]" value="1" min="1"'
        + ' style="width:62px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.6rem;text-align:center;">'
        + '<span style="font-size:.8rem;font-weight:600;color:#4f46e5;font-family:monospace;">' + escHtmlAi(aiGetUnidad(id)) + '</span>'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" id="ai-loc-neto-' + idx + '" name="items[' + idx + '][precio_neto]" placeholder="0" min="0" step="any"'
        + ' style="width:88px;text-align:right;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" id="ai-loc-total-' + idx + '" name="items[' + idx + '][monto]" placeholder="0" min="0" step="1"'
        + ' style="width:88px;text-align:right;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;">'
        + '<select name="items[' + idx + '][contenedor_id]"'
        + ' style="width:100%;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.78rem;background:#fff;">'
        + contOpts + '</select>'
        + '</td>'
        + '<td style="padding:0.4rem 0.3rem;text-align:center;white-space:nowrap;">'
        + '<button type="button" onclick="aiQuitar(' + idx + ')" title="Quitar" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;">&#x2715;</button>'
        + '</td>';
    tbody.appendChild(tr);

    var inpCant  = document.getElementById('ai-loc-cant-'  + idx);
    var inpNeto  = document.getElementById('ai-loc-neto-'  + idx);
    var inpTotal = document.getElementById('ai-loc-total-' + idx);

    inpNeto.addEventListener('input', function() {
        var cant = parseFloat(inpCant.value) || 1;
        var neto = parseFloat(this.value);
        inpTotal.value = isNaN(neto) ? '' : Math.round(neto * cant);
    });
    inpTotal.addEventListener('input', function() {
        var cant  = parseFloat(inpCant.value) || 1;
        var total = parseFloat(this.value);
        inpNeto.value = isNaN(total) ? '' : (Math.round(total / cant * 100) / 100);
    });
    inpCant.addEventListener('input', function() {
        var neto = parseFloat(inpNeto.value);
        if (!isNaN(neto)) inpTotal.value = Math.round(neto * (parseFloat(this.value) || 1));
    });
}

function aiQuitar(idx) {
    aiItems = aiItems.filter(function(i) { return i.idx !== idx; });
    var row = document.getElementById('ai-row-' + idx);
    if (row) row.remove();
    aiActualizarTabla();
}

function aiActualizarTabla() {
    var wrap = document.getElementById('ai-tabla-wrap');
    var sin  = document.getElementById('ai-sin-items');
    wrap.style.display = aiItems.length ? '' : 'none';
    sin.style.display  = aiItems.length ? 'none' : '';
}

function escHtmlAi(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function aiFormatClp(n) {
    if (n === '' || n === null || isNaN(n)) return '';
    return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function aiParseClp(s) {
    return parseFloat(String(s).replace(/\$|\./g, '').replace(',', '.')) || 0;
}
function aiLimpiarPreciosManual() {
    document.querySelectorAll('[id^="ai-pneto-"],[id^="ai-ptotal-"]').forEach(function(inp) {
        var raw = aiParseClp(inp.value);
        inp.value = raw > 0 ? String(raw) : '';
    });
}

function aiGetUnidad(productoId) {
    var p = (typeof aiProductos !== 'undefined' ? aiProductos : []).find(function(x) { return x.id === productoId; });
    return p ? (p.unidad || '—') : '—';
}
} // end if btn-agregar-inventario

// ── Modal crear producto rápido (dev) — fuera del if para ser global ──────────

var aiCrearFamiliaId = null;
var aiCrearCatId     = null;
var aiCrearMarcaId   = null;
var aiCrearNombre    = '';
var aiEditandoIdx    = null;

var aiCrearContexto = 'manual'; // 'manual' | 'local'

function aiAbrirModalCrearLocal() {
    if (!AI_IS_ADMIN) return;
    aiCrearContexto = 'local';
    aiAbrirModalCrear('', null);
    var wrap = document.getElementById('ai-crear-nombre-wrap');
    var display = document.getElementById('ai-crear-nombre-display');
    if (wrap) wrap.style.display = '';
    if (display) display.style.display = 'none';
    var input = document.getElementById('ai-crear-nombre-input');
    if (input) { input.value = document.getElementById('ai-buscador')?.value.trim() || ''; input.focus(); }
}

function aiAbrirModalCrear(nombre, editIdx) {
    if (!AI_IS_ADMIN) return;
    aiEditandoIdx    = (editIdx !== undefined) ? editIdx : null;
    aiCrearFamiliaId = null;
    aiCrearCatId     = null;
    aiCrearMarcaId   = null;
    aiCrearNombre    = nombre;
    document.getElementById('ai-crear-error').style.display = 'none';
    document.getElementById('ai-crear-cat-wrapper').style.display = 'none';
    document.getElementById('ai-crear-marca-wrapper').style.display = 'none';
    document.getElementById('ai-crear-stock-minimo').value  = '0';
    document.getElementById('ai-crear-stock-critico').value = '0';
    var selUm = document.getElementById('ai-crear-unidad-id');
    if (selUm) selUm.value = '';
    var nombreWrap = document.getElementById('ai-crear-nombre-wrap');
    if (nombreWrap) nombreWrap.style.display = 'none';
    var nombreEl = document.getElementById('ai-crear-nombre-display');
    if (nombreEl) { nombreEl.textContent = nombre; nombreEl.style.display = ''; }
    var tituloEl = document.getElementById('ai-crear-titulo');
    if (tituloEl) tituloEl.textContent = aiEditandoIdx !== null ? 'Editar producto' : 'Nuevo producto';
    var res = document.getElementById('ai-resultados-manual');
    if (res) res.style.display = 'none';
    aiRenderCrearFamilias();
    aiRenderCrearCategorias();
    aiToggleStockPorFamilia();
    document.getElementById('ai-modal-crear-producto').style.display = 'flex';
}


function aiCerrarModalCrear() {
    if (aiEditandoIdx !== null) {
        var row = document.getElementById('ai-row-manual-' + aiEditandoIdx);
        if (row) row.style.opacity = '1';
        aiEditandoIdx = null;
    }
    document.getElementById('ai-modal-crear-producto').style.display = 'none';
}

function aiToggleStockPorFamilia() {
    var esServicios = (aiCrearFamiliaId !== null && aiCrearFamiliaId === AI_SERVICIOS_FAMILIA_ID);
    var wrap = document.getElementById('ai-crear-stock-wrap');
    if (wrap) wrap.style.display = esServicios ? 'none' : 'grid';
}

function aiRenderCrearFamilias() {
    var cont = document.getElementById('ai-crear-familias');
    if (!cont) return;
    cont.innerHTML = '';
    var _dm = document.documentElement.classList.contains('dark');
    var lista = (typeof aiFamilias !== 'undefined') ? aiFamilias : [];
    lista.forEach(function(f) {
        var sel = f.id === aiCrearFamiliaId;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = f.nombre;
        var inactiveCss = _dm
            ? 'background:rgba(255,255,255,.06);color:#cbd5e1;border-color:#475569;'
            : 'background:#fff;color:#374151;border-color:#d1d5db;';
        btn.style.cssText = 'font-size:0.8rem;font-weight:600;padding:0.35rem 0.85rem;border-radius:0.5rem;border:1px solid;cursor:pointer;margin:0;'
            + (sel ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : inactiveCss);
        btn.onclick = function() {
            var same = (aiCrearFamiliaId === f.id);
            aiCrearFamiliaId = same ? null : f.id;
            aiCrearCatId     = null;
            aiCrearMarcaId   = null;
            aiRenderCrearFamilias();
            aiRenderCrearCategorias();
            aiToggleStockPorFamilia();
        };
        cont.appendChild(btn);
    });
}

function aiBuscarCatEnFamilias(catId) {
    var found = null;
    (aiFamilias || []).forEach(function(f) {
        if (!found) { found = (f.categorias || []).find(function(c) { return c.id === catId; }) || null; }
    });
    return found;
}

function aiEsCatPYP(catId) {
    return _aiPypFam ? _aiPypFam.categorias.some(function(c) { return c.id == catId; }) : false;
}

function aiRenderCrearCategorias() {
    var wrapper = document.getElementById('ai-crear-cat-wrapper');
    var cont    = document.getElementById('ai-crear-categorias-btns');
    if (!wrapper || !cont) return;

    var sinFamOrNone = (!aiCrearFamiliaId || aiCrearFamiliaId === AI_SIN_FAMILIA_ID);
    var cats = [];

    if (!sinFamOrNone) {
        // Familia real seleccionada → solo sus categorías
        var familia = (aiFamilias || []).find(function(f) { return f.id === aiCrearFamiliaId; });
        cats = (familia ? familia.categorias : []) || [];
    } else {
        // Sin familia o SIN FAMILIA → todas las categorías excepto SERVICIOS y PARTES Y PIEZAS
        (aiFamilias || []).forEach(function(f) {
            if (f.tipo === 'servicios' || f.tipo === 'partes_piezas') return;
            (f.categorias || []).forEach(function(c) { cats.push(c); });
        });
    }

    var _dmCat = document.documentElement.classList.contains('dark');
    cont.innerHTML = '';
    if (cats.length === 0) {
        var empty = document.createElement('p');
        empty.textContent = sinFamOrNone
            ? 'No hay categorías generales disponibles aún.'
            : 'Esta familia no tiene categorías aún. Crea una nueva usando el botón de abajo.';
        empty.style.cssText = 'font-size:0.75rem;color:#9ca3af;margin:0;';
        cont.appendChild(empty);
        aiCrearCatId = null;
    } else {
        cats.forEach(function(c) {
            var sel = c.id === aiCrearCatId;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = c.nombre;
            var inactiveCssCat = _dmCat
                ? 'background:rgba(255,255,255,.06);color:#cbd5e1;border-color:#475569;'
                : 'background:#fff;color:#374151;border-color:#d1d5db;';
            btn.style.cssText = 'font-size:0.8rem;font-weight:600;padding:0.35rem 0.85rem;border-radius:0.5rem;border:1px solid;cursor:pointer;'
                + (sel ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : inactiveCssCat);
            btn.onclick = function() { aiCrearCatId = c.id; aiCrearMarcaId = null; aiRenderCrearCategorias(); aiRenderCrearMarcas(); };
            cont.appendChild(btn);
        });
    }
    wrapper.style.display = 'block';
    aiRenderCrearMarcas();
}

function aiRenderCrearMarcas() {
    var wrapper = document.getElementById('ai-crear-marca-wrapper');
    var cont    = document.getElementById('ai-crear-marcas-btns');
    if (!wrapper || !cont) return;
    if (!aiCrearCatId) { wrapper.style.display = 'none'; return; }
    // Busca la categoría en cualquier familia (soporta SIN FAMILIA)
    var cat    = aiBuscarCatEnFamilias(aiCrearCatId);
    var marcas = (cat && cat.marcas ? cat.marcas : []);
    if (marcas.length === 0) { wrapper.style.display = 'none'; return; }
    cont.innerHTML = '';
    marcas.forEach(function(m) {
        var sel = m.id === aiCrearMarcaId;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = m.nombre;
        btn.style.cssText = 'font-size:0.8rem;font-weight:600;padding:0.35rem 0.85rem;border-radius:0.5rem;border:1px solid;cursor:pointer;'
            + (sel ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#374151;border-color:#d1d5db;');
        btn.onclick = function() { aiCrearMarcaId = m.id; aiRenderCrearMarcas(); };
        cont.appendChild(btn);
    });
    wrapper.style.display = 'block';
}

function aiConfirmarCrearProducto() {
    var errDiv = document.getElementById('ai-crear-error');
    errDiv.style.display = 'none';

    // Si contexto es local, leer el nombre solo si el input está visible
    // (cuando se abre via botón). Si se abrió desde el dropdown, aiCrearNombre
    // ya viene del texto de búsqueda y el input está oculto.
    if (aiCrearContexto === 'local') {
        var nombreWrap  = document.getElementById('ai-crear-nombre-wrap');
        var nombreInput = document.getElementById('ai-crear-nombre-input');
        if (nombreWrap && nombreWrap.style.display !== 'none' && nombreInput) {
            aiCrearNombre = nombreInput.value.trim();
        }
        if (!aiCrearNombre) { errDiv.textContent = 'Escribe el nombre del producto.'; errDiv.style.display = 'block'; return; }
    }

    if (!aiCrearCatId) { errDiv.textContent = 'Selecciona una categoría.'; errDiv.style.display = 'block'; return; }

    // Verificar si la categoría tiene marcas y exigir selección (busca en cualquier familia)
    var _marcasRequeridas = (function() {
        var c = aiBuscarCatEnFamilias(aiCrearCatId);
        return c && c.marcas && c.marcas.length > 0;
    })();
    if (_marcasRequeridas && !aiCrearMarcaId) { errDiv.textContent = 'Selecciona una marca.'; errDiv.style.display = 'block'; return; }

    var minimo  = parseInt(document.getElementById('ai-crear-stock-minimo').value)  || 0;
    var critico = parseInt(document.getElementById('ai-crear-stock-critico').value) || 0;

    var btn = document.getElementById('ai-crear-btn-guardar');
    btn.disabled = true; btn.textContent = 'Creando…';

    fetch(AI_URL_CREAR, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': AI_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ categoria_id: aiCrearCatId, marca_id: aiCrearMarcaId || null, nombre: aiCrearNombre, stock_minimo: minimo, stock_critico: critico, unidad_medida_id: parseInt(document.getElementById('ai-crear-unidad-id')?.value) || null }),
    })
    .then(function(res) { return res.json().then(function(p) { return { ok: res.ok, p: p }; }); })
    .then(function(data) {
        if (!data.ok) {
            errDiv.textContent = (data.p.errors ? Object.values(data.p.errors).flat().join(' ') : null) || data.p.message || 'Error al crear el producto.';
            errDiv.style.display = 'block';
        } else {
            var p = data.p;
            _aiProductosCreados.push(p.id);
            try { sessionStorage.setItem('ai_prods_creados', JSON.stringify(_aiProductosCreados)); } catch(e) {}
            if (typeof aiProductos !== 'undefined') {
                aiProductos.push({ id: p.id, nombre: p.nombre, unidad: p.unidad || '', contenedor_id: null, contenedor_nombre: '', stock: 0 });
            }
            if (aiEditandoIdx !== null) {
                var row = document.getElementById('ai-row-manual-' + aiEditandoIdx);
                if (row) {
                    var hidden = row.querySelector('input[type="hidden"]');
                    if (hidden) hidden.value = p.id;
                    var nameSpan = row.querySelector('span[style]');
                    if (nameSpan) nameSpan.textContent = p.nombre;
                    row.style.opacity = '1';
                }
                var item = aiItemsManual.find(function(i) { return i.idx === aiEditandoIdx; });
                if (item) { item.id = p.id; item.nombre = p.nombre; }
                aiEditandoIdx = null;
            } else {
                if (aiCrearContexto === 'local') {
                    if (typeof aiAgregar === 'function') aiAgregar(p.id, p.nombre);
                } else {
                    if (typeof aiAgregarManual === 'function') aiAgregarManual(p.id, p.nombre, null);
                }
            }
            var buscadorId = aiCrearContexto === 'local' ? 'ai-buscador' : 'ai-buscador-manual';
            aiCrearContexto = 'manual'; // reset
            aiCerrarModalCrear();
            var buscador = document.getElementById(buscadorId);
            if (buscador) buscador.value = '';
        }
    })
    .catch(function() {
        errDiv.textContent = 'Error de conexión.';
        errDiv.style.display = 'block';
    })
    .finally(function() {
        btn.disabled = false; btn.textContent = 'Crear producto';
    });
}

// No cerrar al hacer click en el overlay del modal de crear producto

// ── Crear nueva familia (solo dev) ─────────────────────────────────
function aiMostrarNuevaFamilia() {
    document.getElementById('ai-btn-nueva-familia').style.display = 'none';
    document.getElementById('ai-nueva-familia-wrap').style.display = 'block';
    document.getElementById('ai-nueva-familia-input').value = '';
    document.getElementById('ai-nueva-familia-error').style.display = 'none';
    setTimeout(function() { document.getElementById('ai-nueva-familia-input').focus(); }, 50);
}
function aiOcultarNuevaFamilia() {
    document.getElementById('ai-nueva-familia-wrap').style.display = 'none';
    document.getElementById('ai-btn-nueva-familia').style.display = 'inline-flex';
}
function aiGuardarNuevaFamilia() {
    var nombre = (document.getElementById('ai-nueva-familia-input').value || '').trim();
    var errEl  = document.getElementById('ai-nueva-familia-error');
    errEl.style.display = 'none';
    if (!nombre) { errEl.textContent = 'Escribe un nombre.'; errEl.style.display = 'block'; return; }

    fetch(AI_URL_FAMILIA, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': AI_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ nombre: nombre }),
    })
    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, d: d }; }); })
    .then(function(res) {
        if (!res.ok) {
            errEl.textContent = (res.d.errors ? Object.values(res.d.errors).flat().join(' ') : null) || res.d.message || 'Error al crear.';
            errEl.style.display = 'block';
            return;
        }
        aiFamilias.push({ id: res.d.id, nombre: res.d.nombre, categorias: [] });
        aiCrearFamiliaId = res.d.id;
        aiCrearCatId = null;
        aiOcultarNuevaFamilia();
        aiRenderCrearFamilias();
        aiRenderCrearCategorias();
    })
    .catch(function() { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}

// ── Crear nueva categoría (solo dev) ───────────────────────────────
function aiMostrarNuevaCategoria() {
    document.getElementById('ai-btn-nueva-categoria').style.display = 'none';
    document.getElementById('ai-nueva-categoria-wrap').style.display = 'block';
    document.getElementById('ai-nueva-categoria-input').value = '';
    document.getElementById('ai-nueva-categoria-error').style.display = 'none';
    setTimeout(function() { document.getElementById('ai-nueva-categoria-input').focus(); }, 50);
}
function aiOcultarNuevaCategoria() {
    document.getElementById('ai-nueva-categoria-wrap').style.display = 'none';
    document.getElementById('ai-btn-nueva-categoria').style.display = 'inline-flex';
}
function aiGuardarNuevaCategoria() {
    var nombre = (document.getElementById('ai-nueva-categoria-input').value || '').trim();
    var errEl  = document.getElementById('ai-nueva-categoria-error');
    errEl.style.display = 'none';
    if (!nombre) { errEl.textContent = 'Escribe un nombre.'; errEl.style.display = 'block'; return; }
    if (!aiCrearFamiliaId) { errEl.textContent = 'Selecciona una familia primero.'; errEl.style.display = 'block'; return; }

    fetch(AI_URL_CATEGORIA, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': AI_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ nombre: nombre, familia_id: aiCrearFamiliaId }),
    })
    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, d: d }; }); })
    .then(function(res) {
        if (!res.ok) {
            errEl.textContent = (res.d.errors ? Object.values(res.d.errors).flat().join(' ') : null) || res.d.message || 'Error al crear.';
            errEl.style.display = 'block';
            return;
        }
        var familia = aiFamilias.find(function(f) { return f.id === aiCrearFamiliaId; });
        if (familia) familia.categorias.push({ id: res.d.id, nombre: res.d.nombre, marcas: [] });
        aiCrearCatId = res.d.id;
        document.getElementById('ai-nueva-categoria-input').value = '';
        aiOcultarNuevaCategoria();
        aiRenderCrearCategorias();
    })
    .catch(function() { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}
</script>
@endpush