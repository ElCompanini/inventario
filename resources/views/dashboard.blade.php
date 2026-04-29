@extends('layouts.app')

@section('title', 'Productos')

@section('content')

<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Inventario de Productos</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $productos->count() }} producto(s) registrado(s)
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
            style="background:#2563eb; color:#fff; font-size:0.82rem; font-weight:600; padding:0.5rem 1.1rem; border-radius:0.5rem; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:0.4rem; transition:background .15s; white-space:nowrap;"
            onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Agregar Inventario
        </button>
        @endif

        {{-- Leyenda de colores --}}
        <div class="flex items-center gap-4 text-xs text-gray-600">
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-red-200 border border-red-300"></span>
                Stock crítico
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-yellow-100 border border-yellow-300"></span>
                Stock mínimo
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-white border border-gray-200"></span>
                Normal
            </span>
        </div>
    </div>
</div>

{{-- Errores de validación del formulario de solicitud --}}
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
    {{ $errors->first() }}
</div>
@endif

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

{{-- Tabla de productos --}}
<div class="bg-white rounded-xl shadow overflow-hidden p-4">

    <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
    <table id="tabla-inventario" class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">Producto</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Familia</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Categoría</th>
                <th class="px-4 py-3 font-semibold text-gray-600" style="text-align:center;">Contenedor</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Stock Actual</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Mínimo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Crítico</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Estado</th>
                @if(auth()->user()->esDev())
                <th class="px-4 py-3 font-semibold text-gray-600 text-center">CC</th>
                @endif
                <th class="px-4 py-3 font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $producto)
            @php
            $estado = $producto->estadoStock();
            $rowClass = match($estado) {
            'critico' => 'bg-red-50',
            'minimo' => 'bg-yellow-50',
            default => 'bg-white',
            };
            @endphp
            @php
                $pendienteSalida = $producto->solicitudes->sum('cantidad');
            @endphp
            <tr class="{{ $rowClass }} hover:brightness-95 transition"
                data-contenedor="{{ $producto->contenedor }}"
                data-estado="{{ $estado }}"
                data-cc-id="{{ $producto->centro_costo_id }}"
                data-producto-id="{{ $producto->id }}">
                <td class="px-4 py-3 font-medium text-gray-900">
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
                                    ⏳ {{ $pendienteSalida }} unidad(es) de salida pendiente(s)<br>
                                    @foreach($producto->solicitudes as $sol)
                                        · {{ $sol->usuario->name ?? '—' }}: {{ $sol->cantidad }} u.<br>
                                    @endforeach
                                </span>
                            @if(auth()->user()->esAdmin())
                            </a>
                            @else
                            </span>
                            @endif
                        @endif

                        <span>{{ $producto->nombre }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $producto->categoria->familia->nombre ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $producto->categoria->nombre ?? '—' }}</td>
                <td class="px-4 py-3" style="text-align:center; vertical-align:middle;">
                    @if($producto->container)
                        @if(auth()->user()->esAdmin())
                            <a href="{{ route('admin.containers.index') }}#container-{{ $producto->container->id }}"
                               style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.875rem; font-weight:700; padding:2px 12px; border-radius:9999px; text-decoration:none;">
                                {{ str_replace('Contenedor ', 'C', $producto->container->nombre) }}
                            </a>
                        @else
                            <span style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.875rem; font-weight:700; padding:2px 12px; border-radius:9999px;">
                                {{ str_replace('Contenedor ', 'C', $producto->container->nombre) }}
                            </span>
                        @endif
                    @else
                        <span style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.875rem; font-weight:700; padding:2px 12px; border-radius:9999px;">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center font-bold
                        {{ $estado === 'critico' ? 'text-red-700' : ($estado === 'minimo' ? 'text-yellow-700' : 'text-gray-800') }}">
                    {{ $producto->stock_actual }}
                </td>
                <td class="px-4 py-3 text-center text-gray-600">
                    @if($estado === 'minimo')
                        <span class="inline-block px-2 py-0.5 rounded-full estado-pulso-minimo">{{ $producto->stock_minimo }}</span>
                    @else
                        {{ $producto->stock_minimo }}
                    @endif
                </td>
                <td class="px-4 py-3 text-center text-gray-600">
                    @if($estado === 'critico')
                        <span class="inline-block px-2 py-0.5 rounded-full estado-pulso-critico">{{ $producto->stock_critico }}</span>
                    @else
                        {{ $producto->stock_critico }}
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($estado === 'critico')
                    <span style="position:relative; display:inline-flex; cursor:default;"
                          onmouseenter="this.querySelector('.tt').style.display='block'"
                          onmouseleave="this.querySelector('.tt').style.display='none'">
                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-sm font-semibold px-3 py-1.5 rounded-full estado-pulso-critico">
                            <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="#ef4444"><animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/></circle></svg>
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
                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-sm font-semibold px-3 py-1.5 rounded-full estado-pulso-minimo">
                            <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="#eab308"><animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/></circle></svg>
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
                    @else
                    <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-sm font-semibold px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Normal
                    </span>
                    @endif
                </td>
                @if(auth()->user()->esDev())
                <td class="px-4 py-3 text-center">
                    <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">
                        {{ $producto->centroCosto?->acronimo ?? '—' }}
                    </span>
                </td>
                @endif
                <td class="px-4 py-3 text-center">
                    <div class="flex flex-col items-center gap-1.5">
                        @if(auth()->user()->esAdmin())
                        {{-- Admin: modificar stock directamente --}}
                        <a href="{{ route('admin.productos.editar', $producto->id) }}"
                            class="btn-accion-indigo inline-flex items-center gap-1 text-white text-xs font-medium px-2.5 py-1.5 rounded-lg whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Modificar
                        </a>
                        {{-- Admin: trasladar container --}}
                        <button type="button"
                            onclick="abrirModalTrasladar({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->contenedor }})"
                            class="btn-accion-blue inline-flex items-center gap-1 text-white text-xs font-medium px-2.5 py-1.5 rounded-lg whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Trasladar
                        </button>
                        @else
                        {{-- Usuario: solicitar salida --}}
                        <button type="button"
                            onclick="abrirModal({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', 'salida', {{ $producto->stock_actual }}, {{ $producto->solicitudes->sum('cantidad') }})"
                            class="btn-accion-orange inline-flex items-center gap-1 text-white text-xs font-medium px-3 py-1.5 rounded-lg"
                            {{ $producto->stock_actual <= 0 ? 'disabled' : '' }}>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                            </svg>
                            Salida
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

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

    function cancelarConfirmarPendiente() {
        document.getElementById('modal-confirmar-pendiente').style.display = 'none';
    }

    function confirmarPendiente() {
        document.getElementById('modal-confirmar-pendiente').style.display = 'none';
        _submitForzado = true;
        document.getElementById('form-solicitud').requestSubmit();
    }

    function abrirModal(productoId, nombre, tipo, stockActual, pendiente) {
        _pendienteActual = pendiente || 0;
        _submitForzado   = false;
        document.getElementById('modal-producto-id').value = productoId;
        document.getElementById('modal-tipo').value = tipo;
        document.getElementById('modal-cantidad').value = '';
        document.getElementById('modal-motivo').value = '';
        document.getElementById('modal-stock').textContent = stockActual;
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
                + '<span>Hay <strong>' + pendiente + '</strong> unidad' + (pendiente !== 1 ? 'es' : '') + ' con solicitudes pendientes de aprobación'
                + (critico ? ' — superan el stock disponible.' : '.') + '</span>'
                + '</div>';
        } else {
            aviso.style.display = 'none';
        }

        document.getElementById('modal-titulo').textContent = 'Solicitar Salida — ' + nombre;
        document.getElementById('modal-subtitulo').textContent = 'Retirar unidades del inventario';
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
            var texto = 'Existen <strong>' + _pendienteActual + '</strong> unidad' + (_pendienteActual !== 1 ? 'es' : '')
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
                        <option value="{{ $c->id }}" class="opcion-traslado-destino">{{ $c->nombre }}</option>
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
                                Folio <span style="color:#ef4444;">*</span>
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
                @csrf
                <div style="padding:1.25rem; display:flex; flex-direction:column; gap:1rem;">

                    @if(session('sicd_duplicada'))
                    @php $dup = session('sicd_duplicada'); @endphp
                    <div style="background:#fffbeb; border:1.5px solid #f59e0b; border-radius:0.6rem; padding:0.75rem 1rem;">
                        <div style="display:flex; align-items:flex-start; gap:0.6rem;">
                            <svg style="width:18px;height:18px;flex-shrink:0;margin-top:1px;color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <div style="flex:1;">
                                <p style="font-size:0.82rem; font-weight:700; color:#92400e; margin:0 0 0.2rem;">
                                    La SICD <strong>{{ $dup['codigo'] }}</strong> ya fue ingresada al sistema
                                </p>
                                <p style="font-size:0.75rem; color:#b45309; margin:0 0 0.55rem;">
                                    Estado actual: <strong>{{ $dup['estado'] }}</strong>
                                </p>
                                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                                    <a href="{{ $dup['url'] }}" target="_blank"
                                       style="font-size:0.78rem; font-weight:600; background:#d97706; color:#fff; padding:4px 14px; border-radius:6px; text-decoration:none;">
                                        Ver SICD ingresada
                                    </a>
                                    <button type="button"
                                            onclick="document.getElementById('ai-confirmar-duplicado').value='1'; document.getElementById('form-agregar-inv').dispatchEvent(new Event('submit-confirmed'));"
                                            style="font-size:0.78rem; font-weight:600; background:#fef3c7; color:#92400e; border:1px solid #f59e0b; padding:4px 14px; border-radius:6px; cursor:pointer;">
                                        Ingresar de todas formas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Selector de tipo --}}
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                            Tipo de ingreso <span style="color:#ef4444;">*</span>
                        </label>
                        <select id="ai-tipo" name="_tipo"
                            style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem; font-size:0.8rem; box-sizing:border-box; background:#fff;"
                            onchange="aiCambiarTipo(this.value)">
                            <option value="">— Selecciona el tipo de ingreso —</option>
                            <option value="local">Local (Boleta de compra)</option>
                            <option value="externa">Externa (Documento SICD)</option>
                        </select>
                    </div>

                    {{-- ══ SECCIÓN LOCAL ══ --}}
                    <div id="ai-seccion-local" style="display:none; flex-direction:column; gap:1rem;">

                        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:0.5rem; padding:0.5rem 0.75rem;">
                            <p style="font-size:0.72rem; color:#92400e; font-weight:600; margin:0;">
                                📄 Los productos se sumarán al stock del inventario al registrar la boleta.
                            </p>
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
                                    Folio <span style="color:#ef4444;">*</span>
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
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                                <div id="ai-resultados"
                                    style="display:none; position:absolute; top:100%; left:0; right:0; z-index:10; background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 4px 16px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto; margin-top:2px;"></div>
                            </div>
                        </div>

                        <div id="ai-tabla-wrap" style="display:none;">
                            <table style="width:100%; font-size:0.78rem; border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#fef3c7; color:#92400e;">
                                        <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600;">Producto</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:70px;">Cant.</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:110px;">Monto ($)</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:120px;">P. Neto s/IVA</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600; width:140px;">Contenedor</th>
                                        <th style="padding:0.4rem 0.6rem; width:36px;"></th>
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
                            {{-- Advertencia: SICD ya ingresada en el sistema interno --}}
                            <div id="ai-sicd-ya-ingresada" style="display:none; margin-top:0.5rem; background:#fffbeb; border:1.5px solid #f59e0b; border-radius:0.5rem; padding:0.6rem 0.75rem;">
                                <div style="display:flex; align-items:flex-start; gap:0.5rem;">
                                    <svg style="width:16px;height:16px;flex-shrink:0;margin-top:1px;color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                    </svg>
                                    <div style="flex:1; min-width:0;">
                                        <p style="font-size:0.78rem; font-weight:700; color:#92400e; margin:0 0 0.2rem;">Esta SICD ya fue ingresada al sistema</p>
                                        <p id="ai-sicd-ya-estado" style="font-size:0.72rem; color:#b45309; margin:0 0 0.45rem;"></p>
                                        <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                            <a id="ai-sicd-ya-ver" href="#" target="_blank"
                                               style="font-size:0.72rem; font-weight:600; background:#d97706; color:#fff; padding:3px 12px; border-radius:5px; text-decoration:none; white-space:nowrap;">
                                                Ver SICD ingresada
                                            </a>
                                            <button type="button" id="ai-sicd-ya-continuar"
                                                    onclick="aiOcultarAdvertenciaSicd()"
                                                    style="font-size:0.72rem; font-weight:600; background:#fef3c7; color:#92400e; border:1px solid #f59e0b; padding:3px 12px; border-radius:5px; cursor:pointer; white-space:nowrap;">
                                                Ingresar de todas formas
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="ai-sicd-info" style="display:none; margin-top:0.4rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.4rem; padding:0.35rem 0.6rem; font-size:0.72rem; color:#166534;"></div>
                        </div>

                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Descripción <span style="font-weight:400; color:#9ca3af;">(opcional)</span>
                            </label>
                            <textarea name="descripcion" id="ai-descripcion" rows="2" maxlength="500"
                                placeholder="Notas o descripción del documento SICD..."
                                style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box; resize:vertical;"></textarea>
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
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                            </div>
                            <div id="ai-boleta-masiva" style="display:flex; flex-direction:column; gap:0.25rem;">
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151;">
                                    Boleta / Factura <span style="color:#ef4444;">*</span>
                                    <span style="font-weight:400; color:#9ca3af;">(PDF)</span>
                                </label>
                                <input type="file" name="boleta_sicd" id="ai-boleta-masiva-input" accept=".pdf,.jpg,.jpeg,.png"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                            </div>
                            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; padding:0.5rem 0.65rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.5rem;">
                                <input type="checkbox" name="vincular_oc" id="ai-vincular-oc" value="1"
                                    onchange="aiToggleBoleta('masiva')"
                                    style="width:1rem; height:1rem; accent-color:#16a34a; cursor:pointer;">
                                <span style="font-size:0.78rem; font-weight:600; color:#166534;">
                                    Continuar con asignación a Orden de Compra
                                </span>
                                <span style="font-size:0.72rem; color:#4ade80; margin-left:auto;">Opcional</span>
                            </label>
                        </div>

                        {{-- Panel Carga manual --}}
                        <div id="ai-ext-panel-manual" style="display:none; flex-direction:column; gap:0.75rem;">
                            <div style="position:relative;">
                                <input type="text" id="ai-buscador-manual"
                                    placeholder="🔍 Buscar producto por nombre o descripción..."
                                    autocomplete="off"
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
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                            </div>
                            <div id="ai-tabla-manual-wrap" style="display:none;">
                                <table style="width:100%; font-size:0.78rem; border-collapse:collapse;">
                                    <thead>
                                        <tr style="background:#f3e8ff; color:#6b21a8;">
                                            <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600;">Producto</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:80px;">Cantidad</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:120px;">Precio Total ($)</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600; width:150px;">Contenedor</th>
                                            <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600;">Motivo</th>
                                            <th style="padding:0.4rem 0.6rem; width:60px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="ai-items-manual"></tbody>
                                </table>
                            </div>
                            <p id="ai-sin-items-manual" style="font-size:0.75rem; color:#9ca3af; text-align:center; display:none;">
                                Agrega al menos un producto para continuar.
                            </p>
                            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; padding:0.5rem 0.65rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.5rem;">
                                <input type="checkbox" name="vincular_oc_manual" id="ai-vincular-oc-manual" value="1"
                                    onchange="aiToggleBoleta('manual')"
                                    style="width:1rem; height:1rem; accent-color:#16a34a; cursor:pointer;">
                                <span style="font-size:0.78rem; font-weight:600; color:#166534;">
                                    Continuar con asignación a Orden de Compra
                                </span>
                                <span style="font-size:0.72rem; color:#4ade80; margin-left:auto;">Opcional</span>
                            </label>
                        </div>

                    </div>

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

            {{-- Confirmación salida con SICD enlazado --}}
            <div id="ai-confirm-salida" style="display:none; position:absolute; inset:0; background:rgba(0,0,0,0.45); border-radius:1rem; z-index:10; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:0.75rem; padding:1.5rem; max-width:360px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.2); text-align:center;">
                    <div style="font-size:2rem; margin-bottom:0.5rem;">⚠️</div>
                    <p style="font-size:0.95rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">¿Salir de Agregar Inventario?</p>
                    <p style="font-size:0.8rem; color:#64748b; margin-bottom:1.25rem;">Si sales ahora, se cancelará el PDF SICD enlazado y la acción de ingresar no se completará.</p>
                    <div style="display:flex; gap:0.6rem; justify-content:center;">
                        <button onclick="aiConfirmarSalida()" style="padding:0.45rem 1.1rem; font-size:0.8rem; font-weight:600; background:#ef4444; color:#fff; border:none; border-radius:0.5rem; cursor:pointer;">
                            Sí, salir y cancelar
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
            paging: false,
            layout: {
                topStart: 'buttons',
                topEnd: null,
                bottomStart: null,
                bottomEnd: null,
            },
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel', exportOptions: { columns: ':not(:last-child)' } },
                { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn',       exportOptions: { columns: ':not(:last-child)' } },
                { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf',   exportOptions: { columns: ':not(:last-child)' }, orientation: 'landscape', pageSize: 'A4' },
            ],
            columnDefs: [{ orderable: false, searchable: false, targets: -1 }],
        });

        // ── Sets de filtros activos ─────────────────────────────────────
        var filContenedores  = new Set();
        var filEstados       = new Set();
        var filProductoIds   = new Set();  // IDs seleccionados (familia completa o desc individual)

        function redibujarProd() {
            table.draw();
            var hay = filContenedores.size || filEstados.size || filProductoIds.size;
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
            filContenedores.clear(); filEstados.clear(); filProductoIds.clear();
            $('.fil-prod-contenedor, .fil-prod-estado, .fil-prod-familia-padre, .fil-prod-desc').prop('checked', false);
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
    .btn-accion-indigo { background:#4f46e5; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-accion-indigo:hover { background:#a5b4fc; box-shadow:0 0 14px 4px rgba(165,180,252,0.75); transform:scale(1.05); }
    .btn-accion-blue { background:#2563eb; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-accion-blue:hover { background:#93c5fd; box-shadow:0 0 14px 4px rgba(147,197,253,0.75); transform:scale(1.05); }
    .btn-accion-green { background:#16a34a; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-accion-green:hover { background:#86efac; box-shadow:0 0 14px 4px rgba(134,239,172,0.75); transform:scale(1.05); }
    .btn-accion-orange { background:#f97316; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-accion-orange:hover { background:#fdba74; box-shadow:0 0 14px 4px rgba(253,186,116,0.75); transform:scale(1.05); }
    .btn-accion-orange:disabled { opacity:0.5; cursor:not-allowed; transform:none; box-shadow:none; }
    @keyframes pulso-critico { 0%,100% { box-shadow:0 0 0 0 rgba(239,68,68,.5); } 50% { box-shadow:0 0 0 6px rgba(239,68,68,0); } }
    @keyframes pulso-minimo  { 0%,100% { box-shadow:0 0 0 0 rgba(234,179,8,.5); } 50% { box-shadow:0 0 0 6px rgba(234,179,8,0); } }
    .estado-pulso-critico { animation: pulso-critico 1.5s ease-in-out infinite; }
    .estado-pulso-minimo  { animation: pulso-minimo  1.5s ease-in-out infinite; }
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

@push('scripts')
@php
$aiProductosJson = json_encode(
    $productos->map(fn($p) => [
        'id'             => $p->id,
        'nombre'         => $p->nombre,
        'stock'          => $p->stock_actual,
        'contenedor_id'  => $p->contenedor,
        'contenedor_nombre' => $p->container?->nombre ?? '—',
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
        'categorias' => $f->categorias->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])->values(),
    ])->values(),
    JSON_HEX_TAG | JSON_HEX_AMP
);
@endphp
<script type="application/json" id="ai-data">{!! $aiProductosJson !!}</script>
<script type="application/json" id="ai-containers-data">{!! $aiContainersJson !!}</script>
<script type="application/json" id="ai-familias-data">{!! $aiFamiliasJson !!}</script>
<script>
var aiFamilias = JSON.parse(document.getElementById('ai-familias-data').textContent);
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
var AI_URL_CREAR     = '{{ route('admin.productos.crear.rapido') }}';
var AI_URL_FAMILIA   = '{{ route('admin.catalogo.familias.store') }}';
var AI_URL_CATEGORIA = '{{ route('admin.catalogo.categorias.store') }}';
var AI_CSRF          = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var aiMetodoCargaActual = 'masiva';
var aiItemsManual = [];
var aiCounterManual = 0;

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

function cerrarConConfirmacion() {
    if (_aiSicdEnlazadoId) {
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
    var overlay = document.getElementById('ai-confirm-salida');
    if (overlay) overlay.style.display = 'none';
    window._aiEnlazarUrl = null;
    window._aiSicdUrl = null;
    window._aiEnlazarCodigo = null;
    document.getElementById('modal-agregar-inv').style.display = 'none';
    document.body.style.overflow = '';
    aiForm.reset();
    document.getElementById('ai-seccion-local').style.display = 'none';
    document.getElementById('ai-seccion-externa').style.display = 'none';
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

function aiMetodoCarga(metodo) {
    aiMetodoCargaActual = metodo;
    var btnM = document.getElementById('ai-ext-btn-masiva');
    var btnMa = document.getElementById('ai-ext-btn-manual');
    var panelM  = document.getElementById('ai-ext-panel-masiva');
    var panelMa = document.getElementById('ai-ext-panel-manual');
    if (metodo === 'masiva') {
        btnM.style.borderColor  = '#2563eb'; btnM.style.background  = '#eff6ff'; btnM.style.color  = '#1e40af';
        btnMa.style.borderColor = '#e5e7eb'; btnMa.style.background = '#fff';    btnMa.style.color = '#6b7280';
        panelM.style.display  = 'flex';
        panelMa.style.display = 'none';
    } else {
        btnMa.style.borderColor = '#7c3aed'; btnMa.style.background = '#faf5ff'; btnMa.style.color = '#6b21a8';
        btnM.style.borderColor  = '#e5e7eb'; btnM.style.background  = '#fff';    btnM.style.color  = '#6b7280';
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
    externa.style.display = (tipo === 'externa') ? 'flex' : 'none';
    var btn = document.getElementById('ai-btn-submit');
    if (tipo === 'local') {
        btn.disabled = false;
        btn.style.background = '#d97706';
        btn.style.cursor = 'pointer';
        btn.textContent = 'Registrar compra local';
    } else if (tipo === 'externa') {
        aiActualizarBtnExterna(aiMetodoCargaActual);
    } else {
        btn.disabled = true;
        btn.style.background = '#9ca3af';
        btn.style.cursor = 'not-allowed';
        btn.textContent = 'Registrar';
    }
}

function aiEnviar() {
    var tipo = document.getElementById('ai-tipo').value;
    if (!tipo) { alert('Selecciona el tipo de ingreso.'); return; }

    if (tipo === 'local') {
        var rut = document.getElementById('ai-rut').value.trim();
        var folio = document.getElementById('ai-folio').value.trim();
        var fecha = document.getElementById('ai-fecha').value;
        var doc = document.getElementById('ai-doc').files.length;
        if (!rut)   { alert('El RUT del proveedor es obligatorio.'); document.getElementById('ai-rut').focus(); return; }
        if (!folio) { alert('El folio es obligatorio.'); document.getElementById('ai-folio').focus(); return; }
        if (!fecha) { alert('La fecha de emisión es obligatoria.'); document.getElementById('ai-fecha').focus(); return; }
        if (!doc)   { alert('La boleta PDF es obligatoria.'); return; }
        if (aiItems.length === 0) { alert('Agrega al menos un producto.'); document.getElementById('ai-buscador').focus(); return; }
        aiForm.action = aiUrlLocal;
    } else if (tipo === 'externa') {
        // Validar que el código SICD esté verificado contra el sistema externo
        var codigoSicd = document.getElementById('ai-codigo-sicd').value.trim();
        if (!codigoSicd) {
            alert('El código SICD es obligatorio.');
            document.getElementById('ai-codigo-sicd').focus();
            return;
        }
        if (!aiSicdValido) {
            alert('El código SICD "' + codigoSicd + '" no está validado en el sistema externo. Verifica el código antes de continuar.');
            document.getElementById('ai-codigo-sicd').focus();
            return;
        }
        if (aiMetodoCargaActual === 'masiva') {
            if (!document.getElementById('ai-excel-masivo').files.length) {
                alert('El archivo Excel de productos es obligatorio.'); return;
            }
            var vincularOc = document.getElementById('ai-vincular-oc').checked;
            if (!vincularOc && !document.getElementById('ai-boleta-masiva-input').files.length) {
                alert('La boleta/factura es obligatoria cuando no se asigna a una Orden de Compra.'); return;
            }
            aiForm.action = aiUrlMasiva;
        } else {
            if (aiItemsManual.length === 0) { alert('Agrega al menos un producto.'); document.getElementById('ai-buscador-manual').focus(); return; }
            var vincularOcManual = document.getElementById('ai-vincular-oc-manual').checked;
            if (!vincularOcManual && !document.getElementById('ai-boleta-manual-input').files.length) {
                alert('La boleta/factura es obligatoria cuando no se asigna a una Orden de Compra.'); return;
            }
            aiForm.action = aiUrlManual;
        }
    }

    // Deshabilitar el input de boleta del panel inactivo para que no pise el del panel activo
    if (aiMetodoCargaActual === 'masiva') {
        var inactivo = document.getElementById('ai-boleta-manual-input');
        if (inactivo) inactivo.disabled = true;
    } else {
        var inactivo = document.getElementById('ai-boleta-masiva-input');
        if (inactivo) inactivo.disabled = true;
    }

    aiForm.submit();
}

// Envío cuando el usuario confirma un duplicado desde el banner de sesión
document.getElementById('form-agregar-inv').addEventListener('submit-confirmed', function() {
    var tipo = document.getElementById('ai-tipo').value;
    if (tipo === 'externa') {
        if (aiMetodoCargaActual === 'masiva') {
            aiForm.action = aiUrlMasiva;
        } else {
            aiForm.action = aiUrlManual;
        }
    }
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
                if (res.id) _aiSicdEnlazadoId = res.id;
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
                    if (res.id) _aiSicdEnlazadoId = res.id;
                    window._aiSicdUrl = res.url;
                    var link = document.createElement('a');
                    link.href = res.url;
                    link.target = '_blank';
                    link.textContent = '✓ Ver SICD';
                    link.style.cssText = 'font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;padding:3px 12px;border-radius:5px;text-decoration:none;';
                    btn.replaceWith(link);
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
            if (data.valido) {
                aiSicdValido = true;
                // Si se buscó por ID numérico, reemplazar el campo con el código real
                var codigoFinal = data.codigo_resuelto || codigo;
                if (codigoFinal !== codigo) {
                    document.getElementById('ai-codigo-sicd').value = codigoFinal;
                }
                hint.innerHTML = '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span style="color:#16a34a;font-weight:600;">Código válido en el sistema</span>';
                document.getElementById('ai-codigo-sicd').style.borderColor = '#16a34a';

                // Verificar si esta SICD ya existe en el sistema interno
                var advertenciaEl  = document.getElementById('ai-sicd-ya-ingresada');
                var advertenciaOk  = false;
                fetch('{{ route("admin.sicd.buscar-por-codigo") }}?codigo=' + encodeURIComponent(codigoFinal))
                    .then(function(r) { return r.json(); })
                    .then(function(interno) {
                        if (interno.encontrado && interno.tiene_detalles) {
                            var estadosLabel = { recibido:'Recibida', pendiente:'Pendiente / En OC' };
                            var estadoText   = estadosLabel[interno.estado] || interno.estado || '—';
                            document.getElementById('ai-sicd-ya-estado').textContent = 'Estado: ' + estadoText;
                            document.getElementById('ai-sicd-ya-ver').href = interno.url;
                            advertenciaEl.style.display = 'block';
                        } else {
                            advertenciaEl.style.display = 'none';
                        }
                    })
                    .catch(function() { advertenciaEl.style.display = 'none'; });

                info.style.display = 'block';
                var sicdEstadoLabels = {
                    1: 'Ingresada', 2: 'Enviada', 3: 'Aprobada',
                    4: 'Rechazada', 5: 'En despacho', 6: 'Recibida'
                };
                var fmtEstado = function(val) {
                    var n = parseInt(val, 10);
                    var label = sicdEstadoLabels[n] || 'Desconocido';
                    return '<span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:4px;padding:1px 6px;font-weight:600;white-space:nowrap;">' + n + ' — ' + label + '</span>';
                };
                var estadoGeneral = data.estado != null ? fmtEstado(data.estado) : '—';
                var html = '<strong>Centro de costo:</strong> ' + (data.centro_costo || '—')
                         + ' &nbsp;·&nbsp; <strong>Fecha:</strong> ' + (data.fecha || '—').trim()
                         + ' &nbsp;·&nbsp; <strong>Estado:</strong> ' + estadoGeneral;
                if (data.detalles && data.detalles.length > 0) {
                    html += '<div style="margin-top:0.45rem;overflow-x:auto;">';
                    html += '<table style="width:100%;border-collapse:collapse;font-size:0.7rem;">';
                    html += '<thead><tr style="background:#dcfce7;color:#14532d;">'
                          + '<th style="padding:3px 6px;text-align:left;border-bottom:1px solid #bbf7d0;">Ítem</th>'
                          + '<th style="padding:3px 6px;text-align:left;border-bottom:1px solid #bbf7d0;">Detalle</th>'
                          + '<th style="padding:3px 6px;text-align:right;border-bottom:1px solid #bbf7d0;">Cant.</th>'
                          + '<th style="padding:3px 6px;text-align:left;border-bottom:1px solid #bbf7d0;">Unidad</th>'
                          + '<th style="padding:3px 6px;text-align:right;border-bottom:1px solid #bbf7d0;">V. Unit.</th>'
                          + '<th style="padding:3px 6px;text-align:right;border-bottom:1px solid #bbf7d0;">Total Neto</th>'
                          + '</tr></thead><tbody>';
                    data.detalles.forEach(function(d, i) {
                        var bg = i % 2 === 0 ? '#f0fdf4' : '#ffffff';
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
                html += '<div id="ai-pdf-banner" style="margin-top:0.6rem;background:#fff7ed;border:1px solid #fed7aa;border-radius:0.6rem;padding:0.5rem 0.85rem;display:flex;align-items:center;gap:0.5rem;">'
                      + '<svg style="width:15px;height:15px;flex-shrink:0;animation:spin 1s linear infinite;color:#ea580c;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m6.364 1.636-2.121 2.121M21 12h-3m-1.636 6.364-2.121-2.121M12 21v-3m-6.364-1.636 2.121-2.121M3 12h3m1.636-6.364 2.121 2.121"/></svg>'
                      + '<span style="font-size:0.72rem;color:#c2410c;font-weight:500;">Verificando archivo asociado...</span>'
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
                                                    link.style.cssText = 'font-size:0.7rem;font-weight:600;background:#dcfce7;color:#166534;padding:3px 12px;border-radius:5px;text-decoration:none;';
                                                    btn.replaceWith(link);
                                                }
                                            }
                                        }
                                    })
                                    .catch(function() {});

                                banner.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;width:100%;">'
                                    + '<div style="display:flex;align-items:center;gap:0.45rem;">'
                                    + '<svg style="width:15px;height:15px;flex-shrink:0;color:#ea580c;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>'
                                    + '<span style="font-size:0.72rem;font-weight:700;color:#c2410c;">ARCHIVO ASOCIADO ENCONTRADO</span>'
                                    + '</div>'
                                    + '<div style="display:flex;gap:0.4rem;align-items:center;">'
                                    + '<a href="' + urlPdf + '" target="_blank" style="font-size:0.7rem;font-weight:600;background:#ea580c;color:#fff;padding:3px 12px;border-radius:5px;text-decoration:none;">Ver PDF</a>'
                                    + '<button id="ai-btn-enlazar" onclick="aiEnlazarSolicitud()" style="font-size:0.7rem;font-weight:600;background:#e0e7ff;color:#3730a3;padding:3px 12px;border-radius:5px;border:none;cursor:pointer;">Enlazar PDF SICD</button>'
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
                hint.innerHTML = '<svg style="width:13px;height:13px;flex-shrink:0" fill="none" stroke="#dc2626" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg><span style="color:#dc2626;font-weight:600;">' + data.mensaje + '</span>';
                document.getElementById('ai-codigo-sicd').style.borderColor = '#dc2626';
            }
        })
        .catch(function() {
            aiSicdValido = false;
            hint.innerHTML = '<span style="color:#d97706;">⚠️ Sin conexión al sistema externo.</span>';
            document.getElementById('ai-codigo-sicd').style.borderColor = '#d1d5db';
        });
}

function aiOcultarAdvertenciaSicd() {
    document.getElementById('ai-sicd-ya-ingresada').style.display = 'none';
}

document.getElementById('ai-codigo-sicd').addEventListener('input', function() {
    var codigo = this.value.trim();
    this.style.borderColor = '#d1d5db';
    document.getElementById('ai-codigo-hint').innerHTML = '';
    document.getElementById('ai-sicd-info').style.display = 'none';
    document.getElementById('ai-sicd-ya-ingresada').style.display = 'none';
    aiSicdValido = false;
    clearTimeout(aiSicdValidTimer);
    if (codigo.length >= 3) {
        aiSicdValidTimer = setTimeout(function() { aiValidarCodigo(codigo); }, 600);
    }
});

document.getElementById('ai-buscador').addEventListener('input', function() {
    var q = this.value.trim().toLowerCase();
    var res = document.getElementById('ai-resultados');
    if (q.length < 1) { res.style.display = 'none'; return; }
    var matches = aiProductos.filter(function(p) {
        return p.nombre.toLowerCase().indexOf(q) >= 0;
    }).slice(0, 10);
    if (!matches.length) { res.style.display = 'none'; return; }
    res.innerHTML = matches.map(function(p) {
        return '<div onclick="aiAgregar(' + p.id + ',\'' + p.nombre.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\')"'
            + ' style="padding:0.5rem 0.75rem;cursor:pointer;border-bottom:1px solid #f3f4f6;"'
            + ' onmouseover="this.style.background=\'#fef3c7\'" onmouseout="this.style.background=\'\'">'
            + '<p style="font-size:0.8rem;font-weight:600;color:#1f2937;">' + escHtmlAi(p.nombre) + '</p>'
            + '<p style="font-size:0.72rem;color:#6b7280;">Stock: ' + p.stock + '</p>'
            + '</div>';
    }).join('');
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
    var q = this.value.trim().toLowerCase();
    var res = document.getElementById('ai-resultados-manual');
    if (q.length < 1) { res.style.display = 'none'; return; }
    var matches = aiProductos.filter(function(p) {
        return p.nombre.toLowerCase().indexOf(q) >= 0;
    }).slice(0, 10);

    var html = matches.map(function(p) {
        return '<div data-pid="' + p.id + '" data-pnombre="' + escHtmlAi(p.nombre) + '" data-pcid="' + (p.contenedor_id || '') + '"'
            + ' onclick="aiAgregarManualDesdeDato(this)"'
            + ' style="padding:0.5rem 0.75rem;cursor:pointer;border-bottom:1px solid #f3f4f6;"'
            + ' onmouseover="this.style.background=\'#f3e8ff\'" onmouseout="this.style.background=\'\'">'
            + '<p style="font-size:0.8rem;font-weight:600;color:#1f2937;">' + escHtmlAi(p.nombre) + '</p>'
            + '<p style="font-size:0.72rem;color:#6b7280;">' + escHtmlAi(p.contenedor_nombre || '') + ' &middot; Stock: ' + p.stock + '</p>'
            + '</div>';
    }).join('');

    if (AI_IS_ADMIN) {
        var qEsc = q.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        html += '<div data-crear-nombre="' + qEsc + '" onclick="aiAbrirModalCrear(this.dataset.crearNombre)"'
            + ' style="padding:0.5rem 0.75rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem;border-top:1px solid #e5e7eb;background:#f0fdf4;"'
            + ' onmouseover="this.style.background=\'#dcfce7\'" onmouseout="this.style.background=\'#f0fdf4\'">'
            + '<span style="font-size:1rem;line-height:1;">➕</span>'
            + '<div>'
            + '<p style="font-size:0.8rem;font-weight:700;color:#16a34a;">Crear producto «' + qEsc + '»</p>'
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

    tr.innerHTML =
        '<td style="padding:0.4rem 0.6rem;">'
        + '<input type="hidden" name="items_manual[' + idx + '][producto_id]" value="' + id + '">'
        + '<span style="font-size:0.8rem;font-weight:500;color:#1f2937;">' + escHtmlAi(nombre) + '</span>'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items_manual[' + idx + '][cantidad]" value="1" min="1"'
        + ' style="width:62px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items_manual[' + idx + '][precio_total]" placeholder="0" min="0" step="1"'
        + ' style="width:100%;text-align:right;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;">'
        + '<select name="items_manual[' + idx + '][contenedor_id]"'
        + ' style="width:100%;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.78rem;background:#fff;">'
        + contOptions
        + '</select>'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;">'
        + '<input type="text" name="items_manual[' + idx + '][motivo]" placeholder="Motivo (opcional)"'
        + ' style="width:100%;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.78rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.3rem;text-align:center;white-space:nowrap;">'
        + '<button type="button" onclick="aiEditarManual(' + idx + ',\'' + escHtmlAi(nombre).replace(/'/g,"\\'") + '\')" title="Cambiar producto" style="color:#6b7280;background:none;border:none;cursor:pointer;font-size:0.95rem;line-height:1;margin-right:0.25rem;">&#9998;</button>'
        + '<button type="button" onclick="aiQuitarManual(' + idx + ')" title="Quitar" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;">&#x2715;</button>'
        + '</td>';
    tbody.appendChild(tr);
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
        + '<input type="number" name="items[' + idx + '][cantidad]" value="1" min="1"'
        + ' style="width:60px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items[' + idx + '][monto]" placeholder="0" min="0" step="1"'
        + ' style="width:95px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items[' + idx + '][precio_neto]" placeholder="0" min="0" step="1"'
        + ' style="width:105px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;">'
        + '<select name="items[' + idx + '][contenedor_id]"'
        + ' style="width:100%;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.78rem;background:#fff;">'
        + contOpts + '</select>'
        + '</td>'
        + '<td style="padding:0.4rem 0.3rem;text-align:center;">'
        + '<button type="button" onclick="aiQuitar(' + idx + ')" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;">&#x2715;</button>'
        + '</td>';
    tbody.appendChild(tr);
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
} // end if btn-agregar-inventario

// ── Modal crear producto rápido (dev) — fuera del if para ser global ──────────

var aiCrearFamiliaId = null;
var aiCrearCatId     = null;
var aiCrearNombre    = '';
var aiEditandoIdx    = null;

function aiAbrirModalCrear(nombre, editIdx) {
    if (!AI_IS_ADMIN) return;
    aiEditandoIdx    = (editIdx !== undefined) ? editIdx : null;
    aiCrearFamiliaId = null;
    aiCrearCatId     = null;
    aiCrearNombre    = nombre;
    document.getElementById('ai-crear-error').style.display = 'none';
    document.getElementById('ai-crear-cat-wrapper').style.display = 'none';
    var nombreEl = document.getElementById('ai-crear-nombre-display');
    if (nombreEl) nombreEl.textContent = nombre;
    var tituloEl = document.getElementById('ai-crear-titulo');
    if (tituloEl) tituloEl.textContent = aiEditandoIdx !== null ? 'Editar producto' : 'Nuevo producto';
    var res = document.getElementById('ai-resultados-manual');
    if (res) res.style.display = 'none';
    aiRenderCrearFamilias();
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

function aiRenderCrearFamilias() {
    var cont = document.getElementById('ai-crear-familias');
    if (!cont) return;
    cont.innerHTML = '';
    var lista = (typeof aiFamilias !== 'undefined') ? aiFamilias : [];
    lista.forEach(function(f) {
        var sel = f.id === aiCrearFamiliaId;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = f.nombre;
        btn.style.cssText = 'font-size:0.8rem;font-weight:600;padding:0.35rem 0.85rem;border-radius:0.5rem;border:1px solid;cursor:pointer;margin:0;'
            + (sel ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#374151;border-color:#d1d5db;');
        btn.onclick = function() {
            aiCrearFamiliaId = f.id;
            aiCrearCatId     = null;
            aiRenderCrearFamilias();
            aiRenderCrearCategorias();
        };
        cont.appendChild(btn);
    });
}

function aiRenderCrearCategorias() {
    var wrapper = document.getElementById('ai-crear-cat-wrapper');
    var cont    = document.getElementById('ai-crear-categorias-btns');
    if (!wrapper || !cont) return;
    if (!aiCrearFamiliaId) { wrapper.style.display = 'none'; return; }
    var lista   = aiFamilias || [];
    var familia = lista.find(function(f) { return f.id === aiCrearFamiliaId; });
    var cats    = (familia ? familia.categorias : []) || [];
    cont.innerHTML = '';
    if (cats.length === 0) {
        var empty = document.createElement('p');
        empty.textContent = 'Esta familia no tiene categorías aún. Crea una nueva usando el botón de abajo.';
        empty.style.cssText = 'font-size:0.75rem;color:#9ca3af;margin:0;';
        cont.appendChild(empty);
        aiCrearCatId = null;
    } else {
        cats.forEach(function(c) {
            var sel = c.id === aiCrearCatId;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = c.nombre;
            btn.style.cssText = 'font-size:0.8rem;font-weight:600;padding:0.35rem 0.85rem;border-radius:0.5rem;border:1px solid;cursor:pointer;'
                + (sel ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#374151;border-color:#d1d5db;');
            btn.onclick = function() { aiCrearCatId = c.id; aiRenderCrearCategorias(); };
            cont.appendChild(btn);
        });
    }
    wrapper.style.display = 'block';
}

function aiConfirmarCrearProducto() {
    var errDiv = document.getElementById('ai-crear-error');
    errDiv.style.display = 'none';
    if (!aiCrearFamiliaId) { errDiv.textContent = 'Selecciona una familia.'; errDiv.style.display = 'block'; return; }
    if (!aiCrearCatId) { errDiv.textContent = 'Selecciona una categoría.'; errDiv.style.display = 'block'; return; }

    var btn = document.getElementById('ai-crear-btn-guardar');
    btn.disabled = true; btn.textContent = 'Creando…';

    fetch(AI_URL_CREAR, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': AI_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ categoria_id: aiCrearCatId, nombre: aiCrearNombre }),
    })
    .then(function(res) { return res.json().then(function(p) { return { ok: res.ok, p: p }; }); })
    .then(function(data) {
        if (!data.ok) {
            errDiv.textContent = (data.p.errors ? Object.values(data.p.errors).flat().join(' ') : null) || data.p.message || 'Error al crear el producto.';
            errDiv.style.display = 'block';
        } else {
            var p = data.p;
            if (typeof aiProductos !== 'undefined') {
                aiProductos.push({ id: p.id, nombre: p.nombre, contenedor_id: null, contenedor_nombre: '', stock: 0 });
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
                if (typeof aiAgregarManual === 'function') aiAgregarManual(p.id, p.nombre, null);
            }
            aiCerrarModalCrear();
            var buscador = document.getElementById('ai-buscador-manual');
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

document.getElementById('ai-modal-crear-producto').addEventListener('click', function(e) {
    if (e.target === e.currentTarget) aiCerrarModalCrear();
});

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
        if (familia) familia.categorias.push({ id: res.d.id, nombre: res.d.nombre });
        aiCrearCatId = res.d.id;
        aiOcultarNuevaCategoria();
        aiRenderCrearCategorias();
    })
    .catch(function() { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}
</script>
@endpush