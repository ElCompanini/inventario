@extends('layouts.app')
@section('title', 'Catálogo de Productos y Servicios')

@section('content')

{{-- Header --}}
<div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Catálogo de Productos y Servicios</h1>
        <p class="text-sm text-gray-500 mt-1">Gestión de familias, categorías, marcas y productos</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="toggleScanner()"
                id="btn-scanner"
                class="btn-ghost inline-flex items-center gap-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-600 text-sm font-semibold px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7V5a1 1 0 011-1h2M4 17v2a1 1 0 001 1h2M17 4h2a1 1 0 011 1v2M17 20h2a1 1 0 001-1v-2M7 12h10"/>
            </svg>
            Scanner
        </button>
        <button onclick="abrirModalCargaMasivaProductos()"
                class="btn-ghost inline-flex items-center gap-2 border border-indigo-300 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-semibold px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Carga masiva
        </button>
        <button onclick="abrirModalFamilia()"
                class="btn-primary inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva familia
        </button>
    </div>
</div>

{{-- Panel Scanner --}}
<div id="scanner-panel" style="display:none;" class="mb-6">
    <div class="bg-white rounded-xl shadow border border-indigo-100 p-5">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7V5a1 1 0 011-1h2M4 17v2a1 1 0 001 1h2M17 4h2a1 1 0 011 1v2M17 20h2a1 1 0 001-1v-2M7 12h10"/>
            </svg>
            <h2 class="text-sm font-bold text-gray-700">Escanear código de barras</h2>
            <span class="ml-auto text-xs text-gray-400">Escanea o escribe el código y presiona Enter</span>
        </div>

        <div class="flex gap-2">
            <input type="text" id="barcode-input"
                   placeholder="Apunta la pistola aquí..."
                   autocomplete="off"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400"
                   onkeydown="if(event.key==='Enter') buscarBarcode()">
            <button onclick="buscarBarcode()"
                    class="btn-primary inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                Buscar
            </button>
            <button onclick="limpiarScanner()"
                    class="btn-ghost border border-gray-300 text-gray-500 hover:text-gray-700 text-sm px-3 py-2 rounded-lg">
                ✕
            </button>
        </div>

        <div id="scanner-resultado" class="mt-4" style="display:none;"></div>
    </div>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm">
    {{ session('success') }}
</div>
@endif

{{-- Family tabs — BIENES --}}
<div id="catalogo-tipo-selector" class="mb-5">
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" data-catalogo-tipo="producto" onclick="cambiarTipoCatalogo('producto')" class="catalogo-tipo-btn">
            Productos <span id="catalogo-count-producto" class="catalogo-tipo-count">0</span>
        </button>
        <button type="button" data-catalogo-tipo="servicio" onclick="cambiarTipoCatalogo('servicio')" class="catalogo-tipo-btn">
            Servicios <span id="catalogo-count-servicio" class="catalogo-tipo-count">0</span>
        </button>
        <button type="button" data-catalogo-tipo="mantencion" onclick="cambiarTipoCatalogo('mantencion')" class="catalogo-tipo-btn">
            Mantenciones <span id="catalogo-count-mantencion" class="catalogo-tipo-count">0</span>
        </button>
        <button type="button" data-catalogo-tipo="arriendo" onclick="cambiarTipoCatalogo('arriendo')" class="catalogo-tipo-btn">
            Arriendos <span id="catalogo-count-arriendo" class="catalogo-tipo-count">0</span>
        </button>
    </div>
</div>

<p id="catalogo-nivel-familia" class="text-sm font-bold uppercase tracking-widest mb-0.5" style="color:#374151;">Familias</p>
<p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Bienes</p>
<div class="flex flex-wrap items-center gap-2 mb-4">
    @foreach($familiasBienes as $familia)
    <a href="{{ route('admin.productos.catalogo', ['familia' => $familia->id]) }}"
       data-familia-id="{{ $familia->id }}"
       data-tipo-item="{{ $familia->tipo_item ?? 'producto' }}"
       class="{{ $familiaActiva === $familia->id ? 'btn-primary' : 'btn-ghost' }}
              px-5 py-2 rounded-full text-sm font-semibold transition
              {{ $familiaActiva === $familia->id
                 ? 'bg-indigo-600 text-white shadow'
                 : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
        {{ $familia->nombre }}
    </a>
    @endforeach
</div>

@if($familiasServicios->isNotEmpty())
{{-- Separator --}}
<div style="border-top:1px solid #e5e7eb; margin:0.5rem 0 1rem;"></div>

{{-- Family tabs — SERVICIOS --}}
<p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Servicios</p>
<div class="flex flex-wrap items-center gap-2 mb-6">
    @foreach($familiasServicios as $familia)
    <a href="{{ route('admin.productos.catalogo', ['familia' => $familia->id]) }}"
       data-familia-id="{{ $familia->id }}"
       data-tipo-item="{{ $familia->tipo_item ?? 'producto' }}"
       class="{{ $familiaActiva === $familia->id ? 'btn-primary' : 'btn-ghost' }}
              px-5 py-2 rounded-full text-sm font-semibold transition
              {{ $familiaActiva === $familia->id
                 ? 'bg-indigo-600 text-white shadow'
                 : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
        {{ $familia->nombre }}
    </a>
    @endforeach
</div>
@else
<div class="mb-6"></div>
@endif

@php
    $familiaActual      = $familias->firstWhere('id', $familiaActiva);
    $esSinFamilia       = $familiaActual && $familiaActual->esSinFamilia();
    $familiaProtegida   = $familiaActual && $familiaActual->esPartesYPiezas();
    $esFamiliaServicios = $familiaActual && $familiaActual->esServicios();
    $catsToShow         = $esSinFamilia
        ? ($categoriasActivas ?? collect())
        : ($familiaActual?->categorias ?? collect());
@endphp

@if($familiaActual)


<div id="catalogo-grid" class="grid grid-cols-1 gap-5" style="grid-template-columns: repeat({{ $esFamiliaServicios ? 3 : 4 }}, minmax(0, 1fr));">

    {{-- LEFT: Categorías --}}
    <div>
        <div class="bg-white rounded-xl shadow p-5 overflow-hidden">
            <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom:1px solid #f3f4f6;">
                <h2 id="catalogo-categorias-titulo" class="text-sm font-bold text-gray-700">Categorías</h2>
                @if($esSinFamilia)
                <span class="text-xs text-gray-400 italic">Vista global</span>
                @elseif($familiaProtegida)
                <span class="text-xs text-gray-400 italic">Familia protegida</span>
                @else
                <button onclick="abrirModalCategoria({{ $familiaActual->id }})"
                        class="btn-primary inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva
                </button>
                @endif
            </div>

            @if($catsToShow->isEmpty())
            <p class="text-xs text-gray-400 italic text-center py-4">Sin categorías aún</p>
            @else
            <ul class="space-y-1" id="lista-categorias">
                @foreach($catsToShow as $cat)
                <li class="flex items-center gap-1 group">
                    <button onclick="seleccionarCategoria({{ $cat->id }}, '{{ addslashes($cat->nombre) }}')"
                            id="cat-btn-{{ $cat->id }}"
                            class="btn-ghost cat-item flex-1 text-left px-3 py-2.5 rounded-lg text-sm flex items-center justify-between
                                   {{ request('categoria', $catsToShow->first()?->id) == $cat->id
                                      ? 'bg-indigo-50 text-indigo-700 font-semibold'
                                      : 'text-gray-700 hover:bg-gray-50' }}"
                            data-cat-id="{{ $cat->id }}">
                        <span class="cat-nombre min-w-0 flex-1 truncate text-xs">{{ $cat->nombre }}</span>
                        <span class="text-xs text-gray-400 ml-2 shrink-0">{{ $cat->productos->count() }}</span>
                    </button>
                    @if(auth()->user()->esDev() && !$familiaProtegida && !$esSinFamilia)
                    <button onclick="editarCategoria({{ $cat->id }}, '{{ addslashes($cat->nombre) }}')"
                            title="Editar categoría"
                            class="opacity-0 group-hover:opacity-100 p-2 text-gray-400 hover:text-indigo-600 rounded-md hover:bg-indigo-50 transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button onclick="abrirConfirmarEliminarCat({{ $cat->id }}, '{{ addslashes($cat->nombre) }}')"
                            title="Eliminar categoría"
                            class="opacity-0 group-hover:opacity-100 p-2 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    @endif
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    {{-- MIDDLE: Marcas — hidden for SERVICIOS family --}}
    <div id="panel-marcas-col" @if($esFamiliaServicios) style="display:none;" @endif>
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom:1px solid #f3f4f6;">
                <h2 id="catalogo-marcas-titulo" class="text-sm font-bold text-gray-700">Marcas</h2>
                <button onclick="abrirModalMarcaCatalogo()"
                        class="btn-primary inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva
                </button>
            </div>
            <p class="text-xs text-gray-400 italic text-center py-4" id="marcas-panel-hint">Selecciona una categoría</p>
            <ul class="space-y-1" id="lista-marcas" style="display:none;"></ul>
        </div>
    </div>

    {{-- RIGHT: Productos (spans 2 cols when SERVICIOS to fill gap left by hidden Marcas panel) --}}
    <div id="panel-productos-col" @if($esFamiliaServicios) style="grid-column: span 2;" @endif>
        <div class="bg-white rounded-xl shadow p-5" id="panel-productos">

            @if($esFamiliaServicios)
            <div id="servicios-familia-badge" class="mb-3 flex items-center gap-2 px-3 py-2 rounded-lg">
                <svg id="servicios-familia-icon" class="w-4 h-4 shrink-0" fill="none" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p id="servicios-familia-texto" class="text-xs font-semibold">Familia de Servicios — sin stock físico · sin marcas · los productos son descripciones de servicios</p>
            </div>
            @endif

            <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom:1px solid #f3f4f6;">
                <div>
                    <h2 class="text-sm font-bold text-gray-700" id="titulo-categoria">
                        @if($familiaActual->categorias->isNotEmpty())
                            Selecciona una categoría
                        @else
                            Sin categorías
                        @endif
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5" id="subtitulo-categoria"></p>
                </div>
                <button id="btn-nuevo-producto" onclick="abrirModalProducto()"
                        class="btn-primary hidden items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span id="btn-nuevo-texto">{{ $esFamiliaServicios ? 'Nuevo servicio' : 'Nuevo producto' }}</span>
                </button>
            </div>

            <div id="area-productos">
                @if($familiaActual->categorias->isNotEmpty())
                <p class="text-sm text-gray-400 text-center py-8 italic">Haz clic en una categoría para ver sus productos</p>
                @else
                <p class="text-sm text-gray-400 text-center py-8 italic">Crea una categoría para comenzar</p>
                @endif
            </div>
        </div>
    </div>

</div>
@endif

{{-- Data JSON for JS --}}
<script id="catalogo-data" type="application/json">
{!! json_encode($familias->map(fn($f) => [
    'id'         => $f->id,
    'nombre'     => $f->nombre,
    'tipo'       => $f->tipo,
    'tipo_item'  => $f->tipo_item ?? 'producto',
    'categorias' => $f->categorias->map(fn($c) => [
        'id'        => $c->id,
        'nombre'    => $c->nombre,
        'familia_id'=> $c->familia_id,
        'tipo_item' => $c->tipo_item ?? ($f->tipo_item ?? 'producto'),
        'marcas'    => $c->marcas->map(fn($m) => ['id' => $m->id, 'nombre' => $m->nombre, 'activo' => (bool)$m->activo, 'tipo_item' => $m->tipo_item])->values(),
        'productos' => $c->productos->map(fn($p) => [
            'id'                    => $p->id,
            'nombre'                => $p->nombre,
            'stock_actual'          => $p->stock_actual,
            'stock_minimo'          => $p->stock_minimo,
            'stock_critico'         => $p->stock_critico,
            'contenedor_id'         => $p->contenedor,
            'marca_id'              => $p->marca_id,
            'marca_nombre'          => $p->marca?->nombre,
            'es_servicio'           => (bool) $p->es_servicio,
            'tipo_item'             => $p->tipo_item ?? ((bool) $p->es_servicio ? 'servicio' : 'producto'),
            'maneja_presentacion'   => (bool) $p->maneja_presentacion,
            'tipo_presentacion'     => $p->tipo_presentacion,
            'cantidad_presentacion' => $p->cantidad_presentacion,
            'unidad_base'           => $p->unidad_base,
            'unidad_medida_id'      => $p->unidad_medida_id,
        ])->values(),
    ])->values(),
])->values()) !!}
</script>

<script id="containers-data" type="application/json">
{!! json_encode($containers->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])->values()) !!}
</script>

<script id="unidades-data" type="application/json">
{!! json_encode($unidades->map(fn($u) => ['id' => $u->id, 'nombre' => $u->nombre, 'abreviacion' => $u->abreviacion])->values()) !!}
</script>


{{-- Modal: nueva familia --}}
<div id="modal-familia" style="display:none; position:fixed; inset:0; z-index:9000; align-items:center; justify-content:center; background:rgba(0,0,0,.5);">
    <div id="modal-familia-inner" class="bg-white rounded-xl shadow-xl w-full mx-4" style="max-width:420px; padding:1.5rem; animation:cat-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Nueva familia</h2>
        <p class="text-sm text-gray-500 mb-4">Las familias agrupan categorías de productos.</p>

        <div id="modal-fam-errors" class="hidden mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-3 py-2 text-sm"></div>

        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" id="fam-nombre-input" maxlength="100" placeholder="Ej: Redes"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-5">

        <div class="flex justify-end gap-3" style="border-top:1px solid #f3f4f6; padding-top:1rem;">
            <button onclick="cerrarModalFamilia()"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Cancelar
            </button>
            <button id="btn-guardar-fam" onclick="guardarFamilia()"
                    class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                Guardar
            </button>
        </div>
    </div>
</div>

{{-- Modal: nueva/editar categoría --}}
<div id="modal-categoria" style="display:none; position:fixed; inset:0; z-index:9000; align-items:center; justify-content:center; background:rgba(0,0,0,.5);">
    <div id="modal-categoria-inner" class="bg-white rounded-xl shadow-xl w-full mx-4" style="max-width:420px; padding:1.5rem; animation:cat-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <h2 class="text-lg font-bold text-gray-800 mb-1" id="modal-cat-titulo">Nueva categoría</h2>
        <p class="text-sm text-gray-500 mb-4" id="modal-cat-subtitulo"></p>

        <div id="modal-cat-errors" class="hidden mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-3 py-2 text-sm"></div>

        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" id="cat-nombre-input" maxlength="150" placeholder="Ej: Memorias RAM"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-5">

        <div class="flex justify-end gap-3" style="border-top:1px solid #f3f4f6; padding-top:1rem;">
            <button onclick="cerrarModalCategoria()"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Cancelar
            </button>
            <button id="btn-guardar-cat" onclick="guardarCategoria()"
                    class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                Guardar
            </button>
        </div>
    </div>
</div>

{{-- Modal: nuevo/editar producto --}}
<div id="modal-producto" style="display:none; position:fixed; inset:0; z-index:9000; align-items:center; justify-content:center; background:rgba(0,0,0,.5);">
    <div id="modal-producto-inner" class="bg-white rounded-xl shadow-xl w-full mx-4" style="max-width:520px; padding:1.5rem; animation:cat-in .25s cubic-bezier(.22,.68,0,1.2) both; max-height:90vh; overflow-y:auto;">
        <h2 class="text-lg font-bold text-gray-800 mb-1" id="modal-prod-titulo">Nuevo producto</h2>
        <p class="text-sm text-gray-500 mb-4" id="modal-prod-subtitulo"></p>

        <div id="modal-prod-errors" class="hidden mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-3 py-2 text-sm"></div>
        <div id="modal-prod-success" class="hidden mb-3 bg-green-50 border border-green-300 text-green-700 rounded-lg px-3 py-2 text-sm"></div>

        {{-- Breadcrumb familia › categoría › marca (solo al crear) --}}
        <div id="prod-selector-wrapper" class="mb-4">
            <div id="prod-breadcrumb" class="inline-flex items-center gap-1.5 text-sm bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 flex-wrap">
                <span id="prod-breadcrumb-fam" class="font-semibold text-indigo-600"></span>
                <span class="text-gray-400">›</span>
                <span id="prod-breadcrumb-cat" class="font-semibold text-gray-700"></span>
                <span id="prod-breadcrumb-sep-marca" class="text-gray-400">›</span>
                <span id="prod-breadcrumb-marca" class="font-semibold text-indigo-500"></span>
            </div>
            <div style="border-top:1px solid #f3f4f6; margin-top:0.75rem;"></div>
        </div>

        <div class="space-y-4">

            {{-- Nombre: solo al crear --}}
            <div id="prod-nombre-wrapper">
                <label id="prod-nombre-label" class="block text-sm font-medium text-gray-700 mb-1">Nombre del producto <span class="text-red-500">*</span></label>
                <input type="text" id="prod-nombre" maxlength="200" placeholder="Ej: Cable HDMI 1.8m"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase">
            </div>

            <div id="prod-tipo-wrapper">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de item <span class="text-red-500">*</span></label>
                <select id="prod-tipo-item" onchange="toggleTipoItemProducto()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="producto">Producto</option>
                    <option value="servicio">Servicio</option>
                    <option value="mantencion">Mantención</option>
                    <option value="arriendo">Arriendo</option>
                </select>
            </div>

            <div id="prod-stock-wrap" class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock mínimo <span class="text-red-500">*</span></label>
                    <input type="number" id="prod-stock-minimo" min="0" value="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock crítico <span class="text-red-500">*</span></label>
                    <input type="number" id="prod-stock-critico" min="0" value="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            {{-- Contenedor: requerido al crear, oculto al editar --}}
            <div id="prod-contenedor-wrapper">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contenedor <span class="text-red-500">*</span></label>
                <select id="prod-contenedor"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Selecciona un contenedor —</option>
                </select>
            </div>

            {{-- Unidad de medida: requerida al crear, oculta al editar --}}
            <div id="prod-unidad-wrapper">
                <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de medida <span class="text-red-500">*</span></label>
                <select id="prod-unidad"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Selecciona una unidad —</option>
                </select>
            </div>

            {{-- Paquetes (oculto para servicios) --}}
            <div id="prod-pres-wrapper" style="border-top:1px solid #f3f4f6; padding-top:0.875rem;">
                <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; user-select:none;">
                    <input type="checkbox" id="prod-maneja-pres" onchange="togglePresentacionFields()"
                           style="width:1rem; height:1rem; accent-color:#4f46e5; cursor:pointer;">
                    <span style="font-size:0.875rem; font-weight:600; color:#374151;">¿Maneja paquetes?</span>
                    <span style="font-size:0.75rem; color:#9ca3af;">(caja, bolsa, pack…)</span>
                </label>
                <div id="prod-pres-fields" style="display:none; margin-top:0.75rem; display:grid; grid-template-columns:1fr 1fr; gap:0.625rem;">
                    <div style="grid-column:span 1;">
                        <label style="display:block; font-size:0.8125rem; font-weight:500; color:#374151; margin-bottom:0.25rem;">Tipo de paquete <span style="color:#ef4444;">*</span></label>
                        <select id="prod-tipo-pres"
                                style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4375rem 0.625rem; font-size:0.8125rem; outline:none;">
                            <option value="">— Selecciona —</option>
                            @foreach(['Caja','Paquete','Bolsa','Pack','Kit','Rollo','Resma','Tubo','Bidón','Saco','Pallet','Otro'] as $tp)
                            <option value="{{ $tp }}">{{ $tp }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="grid-column:span 1;">
                        <label style="display:block; font-size:0.8125rem; font-weight:500; color:#374151; margin-bottom:0.25rem;">Unidades por paquete <span style="color:#ef4444;">*</span></label>
                        <input type="number" id="prod-cant-pres" min="2" max="9999" placeholder="Ej: 40"
                               style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4375rem 0.625rem; font-size:0.8125rem; outline:none;">
                    </div>
                    <div style="grid-column:span 2; background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.5rem; padding:0.5rem 0.75rem;">
                        <p id="prod-pres-preview" style="font-size:0.75rem; color:#1d4ed8; margin:0;"></p>
                    </div>
                </div>
            </div>

            <div id="prod-operacional-wrapper" class="catalogo-operacional-box" style="display:none;">
                <p id="prod-operacional-titulo" class="catalogo-operacional-title">Control operacional</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="catalogo-field-label">Proveedor <span class="text-red-500">*</span></label>
                        <input type="text" id="prod-proveedor" maxlength="255" class="catalogo-field-input" placeholder="Proveedor responsable">
                    </div>
                    <div id="prod-fecha-ejecucion-wrap">
                        <label class="catalogo-field-label">Fecha ejecución</label>
                        <input type="date" id="prod-fecha-ejecucion" class="catalogo-field-input">
                    </div>
                    <div>
                        <label class="catalogo-field-label">Estado</label>
                        <select id="prod-estado-operacional" class="catalogo-field-input">
                            <option value="pendiente">Pendiente</option>
                            <option value="aprobado">Aprobada</option>
                            <option value="en_proceso">En proceso</option>
                            <option value="ejecutado">Ejecutada</option>
                            <option value="validado">Validada</option>
                            <option value="cerrado">Cerrada</option>
                            <option value="cancelado">Cancelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="catalogo-field-label">Documento referencia</label>
                        <input type="text" id="prod-documento-referencia" maxlength="100" class="catalogo-field-input" placeholder="OC, SICD, contrato">
                    </div>
                    <div style="grid-column:span 2;">
                        <label class="catalogo-field-label">Observación</label>
                        <textarea id="prod-observacion" rows="2" class="catalogo-field-input" placeholder="Detalle técnico u operacional"></textarea>
                    </div>
                </div>
            </div>

            <div id="prod-arriendo-wrapper" class="catalogo-operacional-box catalogo-arriendo-box" style="display:none;">
                <p class="catalogo-operacional-title">Control temporal / contractual</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="catalogo-field-label">Proveedor <span class="text-red-500">*</span></label>
                        <input type="text" id="prod-arr-proveedor" maxlength="255" class="catalogo-field-input" placeholder="Proveedor del arriendo">
                    </div>
                    <div>
                        <label class="catalogo-field-label">Fecha inicio <span class="text-red-500">*</span></label>
                        <input type="date" id="prod-arr-fecha-inicio" class="catalogo-field-input">
                    </div>
                    <div>
                        <label class="catalogo-field-label">Condición de término <span class="text-red-500">*</span></label>
                        <select id="prod-arr-condicion" onchange="toggleCondicionArriendo()" class="catalogo-field-input">
                            <option value="con_fecha">Con fecha de término definida</option>
                            <option value="sin_fecha">Sin fecha de término definida</option>
                        </select>
                    </div>
                    <div id="prod-arr-fecha-termino-wrap">
                        <label class="catalogo-field-label">Fecha término <span class="text-red-500">*</span></label>
                        <input type="date" id="prod-arr-fecha-termino" class="catalogo-field-input">
                    </div>
                    <div>
                        <label class="catalogo-field-label">Monto período <span class="text-red-500">*</span></label>
                        <input type="number" id="prod-arr-monto-periodo" min="0" step="0.01" class="catalogo-field-input" placeholder="0">
                    </div>
                    <div>
                        <label class="catalogo-field-label">Monto total estimado <span class="text-red-500">*</span></label>
                        <input type="number" id="prod-arr-monto-total" min="0" step="0.01" class="catalogo-field-input" placeholder="0">
                    </div>
                    <div>
                        <label class="catalogo-field-label">Unidad de tiempo</label>
                        <select id="prod-arr-unidad-tiempo" class="catalogo-field-input">
                            <option value="dia">Día</option>
                            <option value="mes">Mes</option>
                            <option value="anio">Año</option>
                            <option value="periodo">Período</option>
                        </select>
                    </div>
                    <div>
                        <label class="catalogo-field-label">Documento referencia <span class="text-red-500">*</span></label>
                        <input type="text" id="prod-arr-documento" maxlength="100" class="catalogo-field-input" placeholder="OC, SICD, contrato">
                    </div>
                    <div style="grid-column:span 2;">
                        <label class="catalogo-field-label">Observación</label>
                        <textarea id="prod-arr-observacion" rows="2" class="catalogo-field-input" placeholder="Condiciones del arriendo"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-5" style="border-top:1px solid #f3f4f6; padding-top:1rem;">
            <button onclick="cerrarModalProducto()"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Cerrar
            </button>
            <button id="btn-guardar-prod" onclick="guardarProducto()"
                    class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                Guardar
            </button>
        </div>
    </div>
</div>

{{-- Modal confirmación asociar barcode --}}
<div id="modal-confirmar-asociar" style="display:none; position:fixed; inset:0; z-index:70; background:rgba(0,0,0,0.55); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.25); width:440px; max-width:calc(100vw - 2rem); animation: cat-in .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.5rem 1.5rem 1rem; display:flex; align-items:flex-start; gap:0.75rem;">
            <div style="flex-shrink:0; width:2.25rem; height:2.25rem; border-radius:9999px; background:#e0e7ff; display:flex; align-items:center; justify-content:center;">
                <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="#4338ca" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7V5a1 1 0 011-1h2M4 17v2a1 1 0 001 1h2M17 4h2a1 1 0 011 1v2M17 20h2a1 1 0 001-1v-2M7 12h10"/>
                </svg>
            </div>
            <div style="flex:1;">
                <p style="font-size:0.9375rem; font-weight:700; color:#1f2937; margin:0 0 0.4rem;">¿Asociar código de barras?</p>
                <p id="confirmar-asociar-texto" style="font-size:0.8125rem; color:#6b7280; margin:0; line-height:1.5;"></p>
                <div style="margin-top:0.75rem; padding:0.5rem 0.75rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.5rem;">
                    <p style="font-size:0.7rem; color:#94a3b8; margin:0 0 0.2rem; text-transform:uppercase; letter-spacing:0.05em;">Se creará un nuevo producto</p>
                    <p id="confirmar-asociar-detalle" style="font-size:0.8125rem; font-weight:600; color:#1e293b; margin:0;"></p>
                </div>
            </div>
        </div>
        <div style="padding:0.75rem 1.5rem 1.25rem; display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button" onclick="cancelarAsociar()"
                    class="btn-secondary"
                    style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                Cancelar
            </button>
            <button type="button" id="btn-confirmar-asociar" onclick="confirmarAsociar()"
                    class="btn-primary"
                    style="padding:0.5rem 1.1rem; font-size:0.875rem; font-weight:600; color:#fff; background:#4f46e5; border:none; border-radius:0.5rem; cursor:pointer;">
                Sí, asociar
            </button>
        </div>
    </div>
</div>

{{-- Modal buscar producto existente para asociar barcode --}}
<div id="modal-buscar-existente" style="display:none; position:fixed; inset:0; z-index:70; background:rgba(0,0,0,0.55); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.25); width:500px; max-width:calc(100vw - 2rem); max-height:80vh; display:flex; flex-direction:column; animation: cat-in .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.25rem 1.5rem 0.75rem; border-bottom:1px solid #f1f5f9; flex-shrink:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem;">
                <p style="font-size:0.9375rem; font-weight:700; color:#1f2937; margin:0;">Asociar a producto existente</p>
                <button onclick="cerrarBuscarExistente()" style="background:none; border:none; font-size:1.2rem; color:#9ca3af; cursor:pointer; line-height:1; padding:0;">✕</button>
            </div>
            <p id="buscar-existente-codigo" style="font-size:0.78rem; color:#6b7280; font-family:monospace; margin:0 0 0.6rem;"></p>
            <input type="text" id="buscar-existente-input" placeholder="Buscar por nombre..."
                   oninput="filtrarBuscarExistente(this.value)"
                   style="width:100%; box-sizing:border-box; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.5rem 0.75rem; font-size:0.875rem; outline:none; focus:ring-2;">
        </div>
        <div id="buscar-existente-lista" style="flex:1; overflow-y:auto; padding:0.5rem 0.75rem;"></div>
    </div>
</div>

{{-- Modal Wizard Barcode --}}
<div id="modal-barcode" style="display:none; position:fixed; inset:0; z-index:50; background:rgba(0,0,0,0.5); overflow-y:auto;">
    <div id="modal-barcode-inner" style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.25); width:520px; max-width:calc(100vw - 2rem); margin:5vh auto; position:relative; animation: cat-in .25s cubic-bezier(.22,.68,0,1.2) both;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h3 class="text-base font-bold text-gray-800" id="bc-wizard-titulo">Nuevo producto</h3>
                <p class="text-xs text-gray-500 mt-0.5" id="bc-wizard-subtitulo">Código: <span id="bc-codigo-display" class="font-mono font-semibold text-indigo-600"></span></p>
            </div>
            <button onclick="cerrarModalBarcode()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Steps indicator --}}
        <div class="px-6 pt-4 flex items-center gap-2">
            @foreach(['Familia','Categoría','Marca','Producto'] as $i => $step)
            <div class="flex items-center gap-2 {{ $loop->last ? '' : 'flex-1' }}">
                <div id="bc-step-circle-{{ $i+1 }}" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0" style="background:#e0e7ff; color:#4338ca;">{{ $i+1 }}</div>
                <span id="bc-step-label-{{ $i+1 }}" class="text-xs font-medium text-gray-500 whitespace-nowrap">{{ $step }}</span>
                @unless($loop->last)
                <div class="flex-1 h-px bg-gray-200 mx-1"></div>
                @endunless
            </div>
            @endforeach
        </div>

        {{-- Step 1: Familia --}}
        <div id="bc-step-1" class="px-6 py-5">
            <p class="text-sm font-medium text-gray-700 mb-3">Selecciona la familia:</p>
            <div id="bc-familias-lista" class="grid grid-cols-2 gap-2"></div>
            <button type="button" onclick="bcMostrarNuevaFamilia()"
                    id="bc-btn-nueva-fam"
                    style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                + Nueva familia
            </button>
            <div id="bc-nueva-fam-wrap" style="display:none; margin-top:0.25rem;">
                <div class="flex gap-2 items-center">
                    <input type="text" id="bc-nueva-fam" placeholder="Nombre de la familia"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           onkeydown="if(event.key==='Enter')bcCrearFamilia(); if(event.key==='Escape')bcOcultarNuevaFamilia();">
                    <button onclick="bcCrearFamilia()"
                            class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">
                        Guardar
                    </button>
                    <button onclick="bcOcultarNuevaFamilia()"
                            class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">
                        ✕
                    </button>
                </div>
            </div>
            <div id="bc-step1-errors" class="hidden mt-2 text-xs text-red-600"></div>
        </div>

        {{-- Step 2: Categoría --}}
        <div id="bc-step-2" class="px-6 py-5" style="display:none;">
            <p class="text-sm font-medium text-gray-700 mb-3">Selecciona la categoría:</p>
            <div id="bc-categorias-lista" class="grid grid-cols-2 gap-2"></div>
            <button type="button" onclick="bcMostrarNuevaCat()"
                    id="bc-btn-nueva-cat"
                    style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                + Nueva categoría
            </button>
            <div id="bc-nueva-cat-wrap" style="display:none; margin-top:0.25rem;">
                <div class="flex gap-2 items-center">
                    <input type="text" id="bc-nueva-cat" placeholder="Nombre de la categoría"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           onkeydown="if(event.key==='Enter')bcCrearCategoria(); if(event.key==='Escape')bcOcultarNuevaCat();">
                    <button onclick="bcCrearCategoria()"
                            class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">
                        Guardar
                    </button>
                    <button onclick="bcOcultarNuevaCat()"
                            class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">
                        ✕
                    </button>
                </div>
            </div>
            <div id="bc-step2-errors" class="hidden mt-2 text-xs text-red-600"></div>
        </div>

        {{-- Step 3: Marca --}}
        <div id="bc-step-3" class="px-6 py-5" style="display:none;">
            <p class="text-sm font-medium text-gray-700 mb-3">Selecciona la marca:</p>
            <div id="bc-marcas-lista" class="grid grid-cols-2 gap-2"></div>
            <button type="button" onclick="bcMostrarNuevaMarca()"
                    id="bc-btn-nueva-marca"
                    style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                + Nueva marca
            </button>
            <div id="bc-nueva-marca-wrap" style="display:none; margin-top:0.25rem;">
                <div class="flex gap-2 items-center">
                    <input type="text" id="bc-nueva-marca-input" placeholder="Nombre de la marca"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           onkeydown="if(event.key==='Enter')bcGuardarNuevaMarca(); if(event.key==='Escape')bcOcultarNuevaMarca();">
                    <button onclick="bcGuardarNuevaMarca()"
                            class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">
                        Guardar
                    </button>
                    <button onclick="bcOcultarNuevaMarca()"
                            class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">
                        ✕
                    </button>
                </div>
                <div id="bc-nueva-marca-errors" class="hidden mt-1 text-xs text-red-600"></div>
            </div>
            <div id="bc-step3-errors" class="hidden mt-2 text-xs text-red-600"></div>
        </div>

        {{-- Step 4: Producto --}}
        <div id="bc-step-4" class="px-6 py-5" style="display:none;">
            <div class="space-y-3">
                {{-- Nombre --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre del producto <span class="text-red-500">*</span></label>
                    <input type="text" id="bc-nombre" placeholder="Ej: CABLE HDMI 2.1"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           style="text-transform:uppercase;"
                           oninput="this.value=this.value.toUpperCase()">
                </div>
                {{-- Unidad de medida + Contenedor --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Unidad de medida</label>
                        <select id="bc-unidad-medida" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                            @foreach(\App\Models\UnidadMedida::where('es_presentacion', false)->orWhereNull('es_presentacion')->orderBy('nombre')->get() as $um)
                                <option value="{{ $um->id }}" {{ $um->nombre === 'UNIDAD' ? 'selected' : '' }}>{{ $um->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Contenedor</label>
                        <select id="bc-contenedor" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                            <option value="">— Sin asignar —</option>
                        </select>
                    </div>
                </div>
                {{-- Paquete / Presentación --}}
                <div>
                    <label style="display:inline-flex; align-items:center; gap:0.5rem; cursor:pointer; user-select:none; font-size:0.8rem; font-weight:600; color:#374151;">
                        <input type="checkbox" id="bc-paquete-check" onchange="bcTogglePaquete()"
                               style="width:1rem; height:1rem; accent-color:#4338ca; cursor:pointer; flex-shrink:0;">
                        <span>¿Su producto viene en paquete?</span>
                    </label>
                    <div id="bc-paquete-wrap" style="display:none; margin-top:0.5rem; padding:0.6rem 0.75rem; background:#f5f3ff; border-radius:0.5rem;">
                        {{-- Fila 1: tipo + paquetes recibidos --}}
                        <div style="display:grid; grid-template-columns:1fr auto; gap:0.5rem; align-items:end; margin-bottom:0.5rem;">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de paquete</label>
                                <select id="bc-present-select" onchange="bcOnPresentSelect()"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                                    <option value="">— Selecciona —</option>
                                    @foreach(\App\Models\UnidadMedida::activas()->where('es_presentacion', true)->whereRaw("nombre NOT REGEXP '[0-9]'")->orderBy('nombre')->get() as $um)
                                        <option value="{{ $um->nombre }}">{{ $um->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Paquetes recibidos</label>
                                <input type="number" id="bc-paquetes-recibidos" min="0" value="0" oninput="bcOnPaquetesRecibidos()"
                                       style="width:5.5rem;" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            </div>
                        </div>
                        {{-- Unidades c/u --}}
                        <div style="margin-bottom:0.4rem;">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Unidades c/u</label>
                            <input type="number" id="bc-present-cantidad" min="1" value="1" oninput="bcOnPresentCantidad()"
                                   class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        </div>
                        <p id="bc-present-preview" style="font-size:0.75rem; color:#4338ca; font-weight:700; margin:0;"></p>
                    </div>
                </div>
                {{-- Stock inicial (sin paquete) --}}
                <div id="bc-cantidad-wrap">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Unidades que llegaron <span class="font-normal text-gray-400">(stock inicial)</span></label>
                    <input type="number" id="bc-cantidad-inicial" min="0" value="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                {{-- Stock mín / crítico --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Stock mínimo</label>
                        <input type="number" id="bc-stock-minimo" min="0" value="0"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Stock crítico</label>
                        <input type="number" id="bc-stock-critico" min="0" value="0"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
                <div id="bc-step4-errors" class="hidden text-xs text-red-600"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 pb-5 flex justify-between gap-3" style="border-top:1px solid #f3f4f6; padding-top:1rem;">
            <button id="bc-btn-atras" onclick="bcAtras()" style="display:none;"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">
                ← Atrás
            </button>
            <div class="ml-auto flex gap-2">
                <button onclick="cerrarModalBarcode()"
                        class="btn-secondary px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">
                    Cancelar
                </button>
                <button id="bc-btn-siguiente" onclick="bcSiguiente()"
                        class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                    Siguiente →
                </button>
                <button id="bc-btn-guardar" onclick="bcGuardar()" style="display:none;"
                        class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: nueva/editar marca (dentro del catálogo) --}}
<div id="modal-marca-cat" style="display:none; position:fixed; inset:0; z-index:9000; align-items:center; justify-content:center; background:rgba(0,0,0,.5);">
    <div id="modal-marca-cat-inner" class="bg-white rounded-xl shadow-xl w-full mx-4" style="max-width:420px; padding:1.5rem; animation:cat-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <h2 class="text-lg font-bold text-gray-800 mb-1" id="modal-marca-cat-titulo">Nueva marca</h2>
        <p class="text-sm text-gray-500 mb-4">Las marcas son globales y se reutilizan en todos los productos.</p>

        <div id="modal-marca-cat-errors" class="hidden mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-3 py-2 text-sm"></div>

        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" id="marca-cat-nombre-input" maxlength="100" placeholder="Ej: Samsung"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-5"
               style="text-transform:uppercase;"
               onkeydown="if(event.key==='Enter') guardarMarcaCatalogo()">

        <div class="flex justify-end gap-3" style="border-top:1px solid #f3f4f6; padding-top:1rem;">
            <button onclick="cerrarModal('modal-marca-cat')"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Cancelar
            </button>
            <button id="btn-guardar-marca-cat" onclick="guardarMarcaCatalogo()"
                    class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                Guardar
            </button>
        </div>
    </div>
</div>

{{-- Modal: confirmar eliminación de categoría --}}
<div id="modal-eliminar-cat" style="display:none; position:fixed; inset:0; z-index:9100; align-items:center; justify-content:center; background:rgba(0,0,0,.5);">
    <div class="bg-white rounded-xl shadow-xl w-full mx-4" style="max-width:400px; padding:1.5rem; animation:cat-in .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div class="flex items-start gap-3 mb-5">
            <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-content:center">
                <svg class="w-5 h-5 text-red-600 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <p class="text-base font-bold text-gray-800">Eliminar categoría</p>
                <p class="text-sm text-gray-500 mt-1">¿Eliminar <span id="eliminar-cat-nombre" class="font-semibold text-gray-700"></span>? Solo es posible si no tiene productos asignados.</p>
            </div>
        </div>
        <div id="eliminar-cat-error" class="hidden mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-3 py-2 text-sm"></div>
        <div class="flex justify-end gap-3" style="border-top:1px solid #f3f4f6; padding-top:1rem;">
            <button onclick="cerrarConfirmarEliminarCat()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Cancelar
            </button>
            <button id="btn-confirmar-eliminar-cat" onclick="confirmarEliminarCat()"
                    class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">
                Eliminar
            </button>
        </div>
    </div>
</div>

@push('head')
<style>
    .catalogo-tipo-btn {
        display:inline-flex; align-items:center; gap:.45rem;
        border:1px solid #d1d5db; background:#fff; color:#4b5563;
        border-radius:.65rem; padding:.55rem .85rem; font-size:.82rem; font-weight:700;
        transition:border-color .15s, color .15s, background .15s, box-shadow .15s;
    }
    .catalogo-tipo-btn:hover { border-color:#818cf8; color:#4f46e5; background:#f8fafc; }
    .catalogo-tipo-btn.is-active { border-color:#4f46e5; background:#4f46e5; color:#fff; box-shadow:0 8px 18px rgba(79,70,229,.18); }
    .catalogo-tipo-count { min-width:1.35rem; text-align:center; border-radius:9999px; padding:.05rem .35rem; font-size:.72rem; background:#eef2ff; color:#4338ca; }
    .catalogo-tipo-btn.is-active .catalogo-tipo-count { background:rgba(255,255,255,.22); color:#fff; }
    html.dark .catalogo-tipo-btn { background:#0f172a; border-color:#334155; color:#cbd5e1; }
    html.dark .catalogo-tipo-btn:hover { background:#111827; border-color:#6366f1; color:#a5b4fc; }
    html.dark .catalogo-tipo-btn.is-active { background:#4f46e5; border-color:#818cf8; color:#fff; }
    html.dark .catalogo-tipo-count { background:#1e1b4b; color:#c4b5fd; }
    html.dark .catalogo-tipo-btn.is-active .catalogo-tipo-count { background:rgba(255,255,255,.22); color:#fff; }
    html.dark #catalogo-nivel-familia { color:#e2e8f0 !important; }
    .catalogo-operacional-box {
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        border-radius: .75rem;
        padding: .95rem;
    }
    .catalogo-operacional-title {
        margin: 0 0 .75rem;
        color: #4f46e5;
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .035em;
        text-transform: uppercase;
    }
    .catalogo-field-label {
        display: block;
        color: #374151;
        font-size: .8125rem;
        font-weight: 600;
        margin-bottom: .25rem;
    }
    .catalogo-field-input {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: .5rem;
        padding: .5rem .65rem;
        font-size: .8125rem;
        outline: none;
        background: #fff;
        color: #111827;
    }
    .catalogo-field-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99,102,241,.18);
    }
    html.dark .catalogo-operacional-box {
        background: #111827;
        border-color: #334155;
    }
    html.dark .catalogo-operacional-title { color: #a5b4fc; }
    html.dark .catalogo-field-label { color: #cbd5e1; }
    html.dark .catalogo-field-input {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }
    html.dark .catalogo-field-input::placeholder { color: #64748b; }
    @keyframes cat-in {
        from { opacity:0; transform:scale(.94); }
        to   { opacity:1; transform:scale(1); }
    }

    /* SERVICIOS family badge — light mode */
    #servicios-familia-badge {
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
    }
    #servicios-familia-icon { stroke: #7c3aed; }
    #servicios-familia-texto { color: #7c3aed; }

    /* SERVICIOS family badge — dark mode */
    html.dark #servicios-familia-badge {
        background: rgba(109, 40, 217, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.3);
    }
    html.dark #servicios-familia-icon { stroke: #c4b5fd; }
    html.dark #servicios-familia-texto { color: #c4b5fd; }

    /* [SERVICIO] inline badge en lista de productos */
    .cat-servicio-badge {
        display: inline-flex; align-items: center; gap: 4px;
        background: #f5f3ff; border: 1px solid #ddd6fe; color: #7c3aed;
        font-size: 0.7rem; font-weight: 600; padding: 2px 7px; border-radius: 5px;
    }
    html.dark .cat-servicio-badge {
        background: rgba(109, 40, 217, 0.2);
        border: 1px solid rgba(139, 92, 246, 0.4);
        color: #c4b5fd;
    }

    /* ── Modal barcode: dark mode ── */
    html.dark #modal-barcode-inner {
        background: #1e293b !important;
        color: #e2e8f0 !important;
    }
    html.dark #modal-barcode-inner h3,
    html.dark #modal-barcode-inner p,
    html.dark #modal-barcode-inner label,
    html.dark #bc-step-1 p,
    html.dark #bc-step-2 p,
    html.dark #bc-step-3 p,
    html.dark #bc-step-4 label {
        color: #cbd5e1 !important;
    }
    html.dark #bc-btn-nueva-fam,
    html.dark #bc-btn-nueva-cat,
    html.dark #bc-btn-nueva-marca { color: #818cf8 !important; }
    html.dark #bc-nueva-fam,
    html.dark #bc-nueva-cat,
    html.dark #bc-nueva-marca-input,
    html.dark #bc-nombre,
    html.dark #bc-stock-minimo,
    html.dark #bc-stock-critico,
    html.dark #bc-cantidad-inicial,
    html.dark #bc-paquetes-recibidos,
    html.dark #bc-present-cantidad {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    html.dark #bc-present-select {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    html.dark #bc-paquete-wrap {
        background: rgba(67, 56, 202, 0.15) !important;
        border: 1px solid rgba(99, 102, 241, 0.25);
    }
    html.dark #bc-present-preview { color: #818cf8 !important; }
    html.dark .bc-btn-cancel-small {
        background: #1e293b !important;
        color: #94a3b8 !important;
    }
    /* Botones de familia/categoría/marca en modo noche */
    html.dark #bc-familias-lista button:not(.bg-indigo-600),
    html.dark #bc-categorias-lista button:not(.bg-indigo-600),
    html.dark #bc-marcas-lista button:not(.bg-indigo-600):not(.bg-gray-600) {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }
    html.dark #bc-familias-lista button:not(.bg-indigo-600):hover,
    html.dark #bc-categorias-lista button:not(.bg-indigo-600):hover,
    html.dark #bc-marcas-lista button:not(.bg-indigo-600):not(.bg-gray-600):hover {
        border-color: #6366f1 !important;
        color: #818cf8 !important;
    }
    html.dark #modal-barcode-inner .border-t { border-color: #334155 !important; }
    html.dark #modal-barcode-inner .bg-gray-100 { background: #334155 !important; color: #94a3b8 !important; }
</style>
@endpush

@push('scripts')
<script>
const CSRF                = '{{ csrf_token() }}';
const IS_DEV              = {{ auth()->user()->esDev() ? 'true' : 'false' }};
const IS_FAMILIA_SERVICIOS = {{ $esFamiliaServicios ? 'true' : 'false' }};
const ROUTE_FAM_STORE  = '{{ route('admin.catalogo.familias.store') }}';
const ROUTE_CAT_STORE  = '{{ route('admin.catalogo.categorias.store') }}';
const ROUTE_CAT_UPDATE  = (id) => `{{ url('admin/catalogo/categorias') }}/${id}`;
const ROUTE_CAT_DESTROY = (id) => `{{ url('admin/catalogo/categorias') }}/${id}`;
const ROUTE_PROD_STORE   = '{{ route('admin.catalogo.productos.store') }}';
const ROUTE_PROD_UPDATE  = (id) => `{{ url('admin/catalogo/productos') }}/${id}`;
const ROUTE_PROD_DESTROY = (id) => `{{ url('admin/catalogo/productos') }}/${id}`;
const ROUTE_BARCODE          = '{{ route('admin.catalogo.barcode') }}';
const ROUTE_ASOCIAR_BARCODE  = (id) => `{{ url('admin/catalogo/productos') }}/${id}/barcode`;
const ROUTE_MARCA_UPDATE  = (id) => `{{ url('admin/catalogo/marcas') }}/${id}`;
const ROUTE_MARCA_DESTROY = (id) => `{{ url('admin/catalogo/marcas') }}/${id}`;
const ROUTE_MARCA_TOGGLE  = (id) => `{{ url('admin/catalogo/marcas') }}/${id}/toggle`;
const ROUTE_CAT_MARCA_STORE = (catId) => `{{ url('admin/catalogo/categorias') }}/${catId}/marcas`;

const catalogoData   = JSON.parse(document.getElementById('catalogo-data').textContent);
const containersData = JSON.parse(document.getElementById('containers-data').textContent);
const unidadesData   = JSON.parse(document.getElementById('unidades-data').textContent);
// Brands live inside catalogoData per category; build a flat index for quick lookup by ID
let marcasData = catalogoData.flatMap(f => f.categorias.flatMap(c => (c.marcas || []).map(m => ({...m}))));

let catActualId      = null;
let catActualNombre  = '';
let catFamiliaId     = {{ $familiaActiva }};
let editandoCatId    = null;
let editandoProdId   = null;
let marcaActualId    = null;
let marcaActualNombre = '';
let editandoMarcaCatId = null;
let catalogoTipoActual = ['producto', 'servicio', 'mantencion', 'arriendo'].includes(localStorage.getItem('catalogo_tipo_item') || '')
    ? localStorage.getItem('catalogo_tipo_item')
    : 'producto';
let familiaVistaId = catFamiliaId;

function normalizarTipoCatalogoProducto(p) {
    return p && p.tipo_item ? p.tipo_item : ((p && p.es_servicio) ? 'servicio' : 'producto');
}

function productosPorTipo(productos) {
    return (productos || []).filter(function(p) {
        return normalizarTipoCatalogoProducto(p) === catalogoTipoActual;
    });
}

function labelTipoCatalogo(plural) {
    var labels = {
        producto: plural ? 'productos' : 'producto',
        servicio: plural ? 'servicios' : 'servicio',
        mantencion: plural ? 'mantenciones' : 'mantención',
        arriendo: plural ? 'arriendos' : 'arriendo',
    };
    return labels[catalogoTipoActual] || labels.producto;
}

function tituloCategoriaCatalogo() {
    return {
        producto: 'Categorías',
        servicio: 'Categorías servicio',
        mantencion: 'Área o equipo',
        arriendo: 'Categorías arriendo',
    }[catalogoTipoActual] || 'Categorías';
}

function tituloFamiliaCatalogo() {
    return {
        producto: 'Familias',
        servicio: 'Familias servicio',
        mantencion: 'Tipos mantención',
        arriendo: 'Tipos arriendo',
    }[catalogoTipoActual] || 'Familias';
}

function tituloMarcaCatalogo() {
    return {
        producto: 'Marcas',
        servicio: 'Servicios',
        mantencion: 'Mantenciones',
        arriendo: 'Arriendos',
    }[catalogoTipoActual] || 'Marcas';
}

function marcasAplicanCatalogo() {
    return catalogoTipoActual === 'producto';
}

function familiasPorTipoCatalogo() {
    return catalogoData.filter(function(f) {
        return (f.tipo_item || 'producto') === catalogoTipoActual;
    });
}

function contarTipoCatalogo() {
    var counts = { producto: 0, servicio: 0, mantencion: 0, arriendo: 0 };
    catalogoData.forEach(function(f) {
        (f.categorias || []).forEach(function(c) {
            (c.productos || []).forEach(function(p) {
                var tipo = normalizarTipoCatalogoProducto(p);
                if (counts[tipo] !== undefined) counts[tipo]++;
            });
        });
    });
    Object.keys(counts).forEach(function(tipo) {
        var el = document.getElementById('catalogo-count-' + tipo);
        if (el) el.textContent = counts[tipo];
    });
}

function aplicarEstadoTipoCatalogo() {
    document.querySelectorAll('[data-catalogo-tipo]').forEach(function(btn) {
        btn.classList.toggle('is-active', btn.dataset.catalogoTipo === catalogoTipoActual);
    });
    var catTitle = document.getElementById('catalogo-categorias-titulo');
    var famTitle = document.getElementById('catalogo-nivel-familia');
    var marcaTitle = document.getElementById('catalogo-marcas-titulo');
    var marcasCol = document.getElementById('panel-marcas-col');
    var productosCol = document.getElementById('panel-productos-col');
    var grid = document.getElementById('catalogo-grid');
    var nuevoTexto = document.getElementById('btn-nuevo-texto');
    if (famTitle) famTitle.textContent = tituloFamiliaCatalogo();
    if (catTitle) catTitle.textContent = tituloCategoriaCatalogo();
    if (marcaTitle) marcaTitle.textContent = tituloMarcaCatalogo();
    if (marcasCol) marcasCol.style.display = marcasAplicanCatalogo() ? '' : 'none';
    if (productosCol) productosCol.style.gridColumn = marcasAplicanCatalogo() ? '' : 'span 2';
    if (grid) grid.style.gridTemplateColumns = marcasAplicanCatalogo() ? 'repeat(4, minmax(0, 1fr))' : 'repeat(3, minmax(0, 1fr))';
    if (nuevoTexto) nuevoTexto.textContent = 'Nuevo ' + labelTipoCatalogo(false);
}

function getFamiliaVista() {
    var familia = catalogoData.find(function(f) {
        return Number(f.id) === Number(familiaVistaId) && (f.tipo_item || 'producto') === catalogoTipoActual;
    });
    return familia || familiasPorTipoCatalogo()[0] || null;
}

function getCategoriaFiltrada(cat) {
    if (!cat) return null;
    if ((cat.tipo_item || 'producto') !== catalogoTipoActual) return null;
    return Object.assign({}, cat, { productos: productosPorTipo(cat.productos || []) });
}

function actualizarFamiliaTabs() {
    document.querySelectorAll('[data-familia-id]').forEach(function(a) {
        var sameTipo = (a.dataset.tipoItem || 'producto') === catalogoTipoActual;
        a.style.display = sameTipo ? '' : 'none';
        var active = Number(a.dataset.familiaId) === Number(familiaVistaId);
        a.classList.toggle('bg-indigo-600', active);
        a.classList.toggle('text-white', active);
        a.classList.toggle('shadow', active);
        a.classList.toggle('bg-white', !active);
        a.classList.toggle('text-gray-600', !active);
        a.classList.toggle('border', !active);
        a.classList.toggle('border-gray-200', !active);
        a.onclick = function(ev) {
            ev.preventDefault();
            familiaVistaId = Number(a.dataset.familiaId);
            catFamiliaId = familiaVistaId;
            catActualId = null;
            marcaActualId = null;
            marcaActualNombre = '';
            renderCategoriasCatalogo();
            actualizarFamiliaTabs();
            limpiarPanelCatalogo();
        };
    });
}

function limpiarPanelCatalogo() {
    var titulo = document.getElementById('titulo-categoria');
    var subtitulo = document.getElementById('subtitulo-categoria');
    var area = document.getElementById('area-productos');
    var btnNuevo = document.getElementById('btn-nuevo-producto');
    var hint = document.getElementById('marcas-panel-hint');
    var marcas = document.getElementById('lista-marcas');
    if (titulo) titulo.textContent = 'Selecciona una categoría';
    if (subtitulo) subtitulo.textContent = '';
    if (area) area.innerHTML = '<p class="text-sm text-gray-400 text-center py-8 italic">Haz clic en una categoría para ver sus ' + labelTipoCatalogo(true) + '</p>';
    if (btnNuevo) btnNuevo.classList.add('hidden');
    if (hint) hint.style.display = '';
    if (marcas) { marcas.style.display = 'none'; marcas.innerHTML = ''; }
}

function renderCategoriasCatalogo() {
    var familia = getFamiliaVista();
    var lista = document.getElementById('lista-categorias');
    if (!lista) return;
    if (!familia) {
        lista.innerHTML = '<li class="text-xs text-gray-400 italic text-center py-4">Crea una familia para ' + labelTipoCatalogo(true) + '</li>';
        return;
    }
    familiaVistaId = familia.id;
    catFamiliaId = familia.id;
    lista.innerHTML = '';
    (familia.categorias || []).filter(function(cat) {
        return (cat.tipo_item || 'producto') === catalogoTipoActual;
    }).forEach(function(cat) {
        var count = productosPorTipo(cat.productos || []).length;
        var active = Number(cat.id) === Number(catActualId);
        var li = document.createElement('li');
        var catNombre = escHtml(cat.nombre).replace(/'/g, "\\'");
        var actionHtml = IS_DEV
            ? '<button onclick="editarCategoria(' + cat.id + ', \'' + catNombre + '\')" title="Editar categoría" class="opacity-0 group-hover:opacity-100 p-2 text-gray-400 hover:text-indigo-600 rounded-md hover:bg-indigo-50 transition shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>'
                + '<button onclick="abrirConfirmarEliminarCat(' + cat.id + ', \'' + catNombre + '\')" title="Eliminar categoría" class="opacity-0 group-hover:opacity-100 p-2 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>'
            : '';
        li.className = 'flex items-center gap-1 group';
        li.innerHTML = '<button onclick="seleccionarCategoria(' + cat.id + ', \'' + catNombre + '\')" id="cat-btn-' + cat.id + '" class="btn-ghost cat-item flex-1 text-left px-3 py-2.5 rounded-lg text-sm flex items-center justify-between ' + (active ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50') + '" data-cat-id="' + cat.id + '"><span class="cat-nombre min-w-0 flex-1 truncate text-xs">' + escHtml(cat.nombre) + '</span><span class="text-xs text-gray-400 ml-2 shrink-0">' + count + '</span></button>' + actionHtml;
        lista.appendChild(li);
    });
}

function cambiarTipoCatalogo(tipo) {
    catalogoTipoActual = ['producto', 'servicio', 'mantencion', 'arriendo'].includes(tipo) ? tipo : 'producto';
    localStorage.setItem('catalogo_tipo_item', catalogoTipoActual);
    var primeraFamilia = familiasPorTipoCatalogo()[0] || null;
    familiaVistaId = primeraFamilia ? primeraFamilia.id : null;
    catFamiliaId = familiaVistaId;
    catActualId = null;
    marcaActualId = null;
    marcaActualNombre = '';
    aplicarEstadoTipoCatalogo();
    actualizarFamiliaTabs();
    renderCategoriasCatalogo();
    limpiarPanelCatalogo();
}

// ── Scanner de código de barras ───────────────────────────────────────────────

function toggleScanner() {
    const panel = document.getElementById('scanner-panel');
    const open  = panel.style.display !== 'none';
    panel.style.display = open ? 'none' : 'block';
    if (!open) setTimeout(() => document.getElementById('barcode-input').focus(), 80);
}

function limpiarScanner() {
    document.getElementById('barcode-input').value = '';
    document.getElementById('scanner-resultado').style.display = 'none';
    document.getElementById('barcode-input').focus();
}

let _asociarProductoId  = null;
let _asociarCodigo      = '';
let _asociarDescripcion = '';

function asociarBarcode(productoId, codigo, descripcion) {
    _asociarProductoId  = productoId;
    _asociarCodigo      = codigo;
    _asociarDescripcion = descripcion;

    document.getElementById('confirmar-asociar-texto').innerHTML =
        'El código <span style="font-family:monospace;font-weight:700;color:#4338ca;">' + escHtml(codigo) + '</span> '
        + 'se vinculará como una entrada independiente. El producto original no será modificado.';
    document.getElementById('confirmar-asociar-detalle').textContent = descripcion;
    document.getElementById('modal-confirmar-asociar').style.display = 'flex';
}

function cancelarAsociar() {
    document.getElementById('modal-confirmar-asociar').style.display = 'none';
}

// ── Buscar producto existente para asociar barcode ────────────────────────────

let _buscarExistenteCodigo = '';
let _todosProductosFlat    = null;

function _getTodosProductos() {
    if (!_todosProductosFlat) {
        _todosProductosFlat = [];
        catalogoData.forEach(function(f) {
            f.categorias.forEach(function(c) {
                (c.productos || []).forEach(function(p) {
                    _todosProductosFlat.push({ id: p.id, nombre: p.nombre, familia: f.nombre, categoria: c.nombre });
                });
            });
        });
        _todosProductosFlat.sort((a, b) => a.nombre.localeCompare(b.nombre));
    }
    return _todosProductosFlat;
}

function abrirBuscarExistente(codigo) {
    _buscarExistenteCodigo = codigo;
    document.getElementById('buscar-existente-codigo').textContent = 'Código: ' + codigo;
    document.getElementById('buscar-existente-input').value = '';
    filtrarBuscarExistente('');
    document.getElementById('modal-buscar-existente').style.display = 'flex';
    setTimeout(() => document.getElementById('buscar-existente-input').focus(), 80);
}

function cerrarBuscarExistente() {
    document.getElementById('modal-buscar-existente').style.display = 'none';
}

function filtrarBuscarExistente(q) {
    const lista     = document.getElementById('buscar-existente-lista');
    const todos     = _getTodosProductos();
    const term      = q.trim().toLowerCase();
    const filtrados = term ? todos.filter(p => p.nombre.toLowerCase().includes(term)) : todos;
    if (filtrados.length === 0) {
        lista.innerHTML = '<p style="font-size:0.8rem; color:#9ca3af; padding:0.75rem 0.25rem; margin:0;">Sin resultados.</p>';
        return;
    }
    lista.innerHTML = filtrados.slice(0, 100).map(p => `
        <div onclick="seleccionarExistente(${p.id}, '${p.nombre.replace(/\\/g,'\\\\').replace(/'/g,"\\\'")}')"
             style="display:flex; align-items:center; gap:0.75rem; padding:0.55rem 0.5rem; border-radius:0.5rem; cursor:pointer; transition:background .1s;"
             onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">
            <div style="flex:1; min-width:0;">
                <p style="font-size:0.85rem; font-weight:600; color:#1e293b; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escHtml(p.nombre)}</p>
                <p style="font-size:0.73rem; color:#94a3b8; margin:0;">${escHtml(p.familia)} › ${escHtml(p.categoria)}</p>
            </div>
            <svg style="width:0.9rem;height:0.9rem;color:#6366f1;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
        </div>`).join('');
}

function seleccionarExistente(id, nombre) {
    cerrarBuscarExistente();
    asociarBarcode(id, _buscarExistenteCodigo, nombre);
}

async function confirmarAsociar() {
    const btn = document.getElementById('btn-confirmar-asociar');
    btn.disabled = true;
    btn.textContent = 'Asociando...';
    try {
        const body = new URLSearchParams({ _token: CSRF, codigo_barras: _asociarCodigo, _method: 'PATCH' });
        const res  = await fetch(ROUTE_ASOCIAR_BARCODE(_asociarProductoId), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        const json = await res.json();
        cancelarAsociar();
        if (json.ok) {
            const btnAsociar = document.getElementById('btn-asociar-' + _asociarProductoId);
            if (btnAsociar) {
                btnAsociar.textContent = '✓ Asociado';
                btnAsociar.style.background = '#16a34a';
                btnAsociar.disabled = true;
            }
            buscarBarcode();
        }
    } catch(e) {
        cancelarAsociar();
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sí, asociar';
    }
}

async function buscarBarcode() {
    const codigo = document.getElementById('barcode-input').value.trim();
    if (!codigo) return;

    const resDiv = document.getElementById('scanner-resultado');
    resDiv.style.display = 'block';
    resDiv.innerHTML = '<p class="text-xs text-gray-400 flex items-center gap-1.5"><svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m6.364 1.636-2.121 2.121M21 12h-3"/></svg>Buscando...</p>';

    try {
        const r    = await fetch(ROUTE_BARCODE + '?codigo=' + encodeURIComponent(codigo));
        const data = await r.json();

        if (data.encontrado) {
            const p = data.producto;
            resDiv.innerHTML = `
            <div class="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-xl">
                <div class="mt-0.5 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-green-700 uppercase tracking-wide mb-1">Producto encontrado</p>
                    <p class="text-sm font-semibold text-gray-800">${escHtml(p.nombre)}</p>
                    <p class="text-xs text-gray-500 mt-0.5">${escHtml(p.familia)} › ${escHtml(p.categoria)}</p>
                    <p class="text-xs text-gray-400 mt-1 font-mono">Código: ${escHtml(p.codigo_barras)}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs text-gray-500">Stock actual</p>
                    <p class="text-2xl font-bold text-gray-800">${p.stock_actual}</p>
                </div>
            </div>`;
        } else {
            let html = `
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl mb-3">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <p class="text-sm font-bold text-amber-700">Producto nuevo</p>
                </div>
                <p class="text-xs text-amber-600">El código <span class="font-mono font-semibold">${escHtml(codigo)}</span> no existe en el catálogo.</p>
                <div style="display:flex; gap:0.5rem; margin-top:0.75rem; flex-wrap:wrap;">
                    <button onclick="abrirWizardBarcode('${escHtml(codigo).replace(/'/g,"\\\'")}')"
                            class="btn-primary inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar al catálogo
                    </button>
                    <button onclick="abrirBuscarExistente('${escHtml(codigo).replace(/'/g,"\\\'")}')"
                            style="display:inline-flex; align-items:center; gap:0.375rem; background:#475569; color:#fff; font-size:0.75rem; font-weight:600; padding:0.375rem 0.75rem; border-radius:0.5rem; border:none; cursor:pointer;"
                            onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#475569'">
                        <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Asociar a existente
                    </button>
                </div>
            </div>`;

            if (data.similares && data.similares.length > 0) {
                html += `<p class="text-xs font-semibold text-gray-500 mb-2">Productos con código similar:</p>
                <div class="space-y-2">`;
                data.similares.forEach(s => {
                    const pct   = s.similitud;
                    const color = pct >= 80 ? '#16a34a' : pct >= 60 ? '#d97706' : '#6b7280';
                    html += `
                    <div class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg">
                        <div class="shrink-0 text-center" style="min-width:42px;">
                            <p class="text-base font-bold" style="color:${color};">${pct}%</p>
                            <p class="text-xs text-gray-400">similar</p>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">${escHtml(s.nombre)}</p>
                            <p class="text-xs text-gray-400">${escHtml(s.familia)} › ${escHtml(s.categoria)}</p>
                            <p class="text-xs font-mono text-gray-400">${escHtml(s.codigo_barras)}</p>
                        </div>
                        <button id="btn-asociar-${s.id}"
                                onclick="asociarBarcode(${s.id}, '${escHtml(codigo).replace(/'/g,"\\'")}', '${escHtml(s.nombre).replace(/'/g,"\\'")}')"
                                class="btn-primary shrink-0 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg" style="cursor:pointer;">
                            Asociar
                        </button>
                    </div>`;
                });
                html += '</div>';
            }
            resDiv.innerHTML = html;
        }
    } catch(e) {
        resDiv.innerHTML = '<p class="text-xs text-red-500">Error de conexión.</p>';
    }
}

// ── Wizard barcode ─────────────────────────────────────────────────────────────

let bcCodigo            = '';
let bcStep              = 1;
let bcFamiliaId         = null;
let bcFamiliaNombre     = '';
let bcCatId             = null;
let bcCatNombre         = '';
let bcMarcaId           = null;
let bcSinMarcaExplicito = false;
let bcManejaPresent     = false;
let bcPresentNombre     = '';
let bcPresentCantidad   = 1;

function abrirWizardBarcode(codigo) {
    bcCodigo            = codigo;
    bcStep              = 1;
    bcFamiliaId         = null;
    bcCatId             = null;
    bcMarcaId           = null;
    bcSinMarcaExplicito = false;
    bcManejaPresent     = false;
    bcPresentNombre     = '';
    bcPresentCantidad   = 1;
    document.getElementById('bc-codigo-display').textContent = codigo;
    bcIrAStep(1);
    document.getElementById('modal-barcode').style.display = 'block';
    void document.getElementById('modal-barcode-inner').offsetHeight;
    document.getElementById('modal-barcode-inner').style.animation = 'cat-in .25s cubic-bezier(.22,.68,0,1.2) both';
}

function cerrarModalBarcode() {
    document.getElementById('modal-barcode').style.display = 'none';
}

function bcIrAStep(step) {
    bcStep = step;
    [1,2,3,4].forEach(n => {
        document.getElementById('bc-step-' + n).style.display = n === step ? 'block' : 'none';
        const circle = document.getElementById('bc-step-circle-' + n);
        const label  = document.getElementById('bc-step-label-' + n);
        if (n < step) {
            circle.style.background = '#a5b4fc'; circle.style.color = '#3730a3';
            circle.innerHTML = n;
            label.style.color = '#6366f1'; label.style.fontWeight = '500';
        } else if (n === step) {
            circle.style.background = '#4338ca'; circle.style.color = '#fff';
            circle.innerHTML = n;
            label.style.color = '#4338ca'; label.style.fontWeight = '700';
        } else {
            circle.style.background = '#e0e7ff'; circle.style.color = '#a5b4fc';
            circle.innerHTML = n;
            label.style.color = '#9ca3af'; label.style.fontWeight = '500';
        }
    });
    document.getElementById('bc-btn-atras').style.display     = step > 1 ? 'inline-flex' : 'none';
    document.getElementById('bc-btn-siguiente').style.display = step < 4 ? 'inline-flex' : 'none';
    document.getElementById('bc-btn-guardar').style.display   = step === 4 ? 'inline-flex' : 'none';

    if (step === 1) { bcOcultarNuevaFamilia(); bcRenderFamilias(); }
    if (step === 2) { bcOcultarNuevaCat(); bcRenderCategorias(); }
    if (step === 3) { bcOcultarNuevaMarca(); bcRenderMarcas(); }
    if (step === 4) {
        // reset step 4 fields
        document.getElementById('bc-nombre').value = '';
        document.getElementById('bc-paquete-check').checked = false;
        document.getElementById('bc-paquete-wrap').style.display = 'none';
        document.getElementById('bc-cantidad-wrap').style.display = 'block';
        document.getElementById('bc-cantidad-inicial').value = '0';
        document.getElementById('bc-present-select').value = '';
        document.getElementById('bc-paquetes-recibidos').value = '0';
        document.getElementById('bc-present-cantidad').value = '1';
        document.getElementById('bc-present-preview').textContent = '';
        bcManejaPresent = false; bcPresentNombre = ''; bcPresentCantidad = 1;
        // Poblar select de contenedores
        const selCont = document.getElementById('bc-contenedor');
        selCont.innerHTML = '<option value="">— Sin asignar —</option>';
        (containersData || []).forEach(function(c) {
            const opt = document.createElement('option');
            opt.value = c.id; opt.textContent = c.nombre;
            selCont.appendChild(opt);
        });
        setTimeout(() => document.getElementById('bc-nombre').focus(), 50);
    }
}

function bcTogglePaquete() {
    const chk  = document.getElementById('bc-paquete-check');
    const wrap = document.getElementById('bc-paquete-wrap');
    const cWrap = document.getElementById('bc-cantidad-wrap');
    bcManejaPresent = chk.checked;
    wrap.style.display  = chk.checked ? 'block' : 'none';
    cWrap.style.display = chk.checked ? 'none'  : 'block';
    if (!chk.checked) { bcPresentNombre = ''; document.getElementById('bc-present-preview').textContent = ''; }
}

function bcOnPresentSelect() {
    bcPresentNombre = document.getElementById('bc-present-select').value;
    bcUpdatePresentPreview();
}

function bcOnPresentCantidad() {
    bcPresentCantidad = parseInt(document.getElementById('bc-present-cantidad').value) || 1;
    bcUpdatePresentPreview();
}

function bcOnPaquetesRecibidos() {
    bcUpdatePresentPreview();
}

function bcUpdatePresentPreview() {
    const prev = document.getElementById('bc-present-preview');
    const rec  = parseInt(document.getElementById('bc-paquetes-recibidos').value) || 0;
    const uPaq = bcPresentNombre || '?';
    const total = rec * (bcPresentCantidad || 1);
    prev.textContent = rec > 0 ? (rec + ' ' + uPaq + ' × ' + bcPresentCantidad + ' u. = ' + total + ' unidades') : '';
}

function bcRenderFamilias() {
    const lista = document.getElementById('bc-familias-lista');
    lista.innerHTML = '';
    catalogoData.forEach(f => {
        const sel = f.id === bcFamiliaId;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = f.nombre;
        btn.className = 'text-sm font-medium px-4 py-3 rounded-xl border text-left transition ' +
            (sel ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400 hover:text-indigo-600');
        btn.onclick = () => { bcFamiliaId = f.id; bcFamiliaNombre = f.nombre; bcRenderFamilias(); };
        lista.appendChild(btn);
    });
}

function bcRenderCategorias() {
    const familia = catalogoData.find(f => f.id === bcFamiliaId);
    const lista   = document.getElementById('bc-categorias-lista');
    lista.innerHTML = '';
    if (!familia) return;
    document.getElementById('bc-wizard-titulo').textContent = 'Nueva categoría — ' + bcFamiliaNombre;
    familia.categorias.forEach(c => {
        const sel = c.id === bcCatId;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = c.nombre;
        btn.className = 'text-sm font-medium px-4 py-3 rounded-xl border text-left transition ' +
            (sel ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400 hover:text-indigo-600');
        btn.onclick = () => { bcCatId = c.id; bcCatNombre = c.nombre; bcRenderCategorias(); };
        lista.appendChild(btn);
    });
    document.getElementById('bc-nueva-cat').value = '';
}

function bcMostrarNuevaFamilia() {
    document.getElementById('bc-nueva-fam-wrap').style.display = 'block';
    document.getElementById('bc-btn-nueva-fam').style.display  = 'none';
    document.getElementById('bc-nueva-fam').focus();
}

function bcOcultarNuevaFamilia() {
    document.getElementById('bc-nueva-fam-wrap').style.display = 'none';
    document.getElementById('bc-btn-nueva-fam').style.display  = 'inline-flex';
    document.getElementById('bc-nueva-fam').value = '';
    document.getElementById('bc-step1-errors').classList.add('hidden');
}

async function bcCrearFamilia() {
    const nombre = document.getElementById('bc-nueva-fam').value.trim();
    const errDiv = document.getElementById('bc-step1-errors');
    if (!nombre || nombre.length < 2) { errDiv.textContent = 'El nombre debe tener al menos 2 caracteres.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    try {
        const res  = await fetch(ROUTE_FAM_STORE, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ _token: CSRF, nombre, tipo_item: catalogoTipoActual }) });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors?.nombre?.[0] ?? 'Error al crear.'; errDiv.classList.remove('hidden'); return; }
        catalogoData.push({ id: json.id, nombre: json.nombre, categorias: [] });
        bcFamiliaId = json.id; bcFamiliaNombre = json.nombre;
        bcOcultarNuevaFamilia();
        bcRenderFamilias();
    } catch(e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
}

function bcMostrarNuevaCat() {
    document.getElementById('bc-nueva-cat-wrap').style.display = 'block';
    document.getElementById('bc-btn-nueva-cat').style.display  = 'none';
    document.getElementById('bc-nueva-cat').focus();
}

function bcOcultarNuevaCat() {
    document.getElementById('bc-nueva-cat-wrap').style.display = 'none';
    document.getElementById('bc-btn-nueva-cat').style.display  = 'inline-flex';
    document.getElementById('bc-nueva-cat').value = '';
    document.getElementById('bc-step2-errors').classList.add('hidden');
}

async function bcCrearCategoria() {
    const nombre = document.getElementById('bc-nueva-cat').value.trim();
    const errDiv = document.getElementById('bc-step2-errors');
    if (!nombre || nombre.length < 2) { errDiv.textContent = 'El nombre debe tener al menos 2 caracteres.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    try {
        const body = new URLSearchParams({ _token: CSRF, nombre, familia_id: bcFamiliaId });
        const res  = await fetch(ROUTE_CAT_STORE, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors?.nombre?.[0] ?? 'Error.'; errDiv.classList.remove('hidden'); return; }
        const familia = catalogoData.find(f => f.id === bcFamiliaId);
        if (familia) familia.categorias.push({ id: json.id, nombre: json.nombre, marcas: [], productos: [] });
        bcCatId = json.id; bcCatNombre = json.nombre;
        bcOcultarNuevaCat();
        bcRenderCategorias();
    } catch(e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
}

function bcSiguiente() {
    if (bcStep === 1) {
        const errDiv = document.getElementById('bc-step1-errors');
        if (!bcFamiliaId) { errDiv.textContent = 'Selecciona una familia.'; errDiv.classList.remove('hidden'); return; }
        errDiv.classList.add('hidden');
        document.getElementById('bc-wizard-titulo').textContent = 'Selecciona la categoría';
        bcIrAStep(2);
    } else if (bcStep === 2) {
        const errDiv = document.getElementById('bc-step2-errors');
        if (!bcCatId) { errDiv.textContent = 'Selecciona o crea una categoría.'; errDiv.classList.remove('hidden'); return; }
        errDiv.classList.add('hidden');
        document.getElementById('bc-wizard-titulo').textContent = 'Selecciona la marca';
        bcIrAStep(3);
    } else if (bcStep === 3) {
        const errDiv = document.getElementById('bc-step3-errors');
        if (!bcMarcaId && !bcSinMarcaExplicito) { errDiv.textContent = 'Selecciona una marca o elige Sin marca.'; errDiv.classList.remove('hidden'); return; }
        errDiv.classList.add('hidden');
        document.getElementById('bc-wizard-titulo').textContent = 'Datos del producto';
        document.getElementById('bc-stock-minimo').value = '0';
        document.getElementById('bc-stock-critico').value = '0';
        document.getElementById('bc-step4-errors').classList.add('hidden');
        bcIrAStep(4);
    }
}

function bcAtras() {
    if (bcStep === 2) { document.getElementById('bc-wizard-titulo').textContent = 'Nuevo producto'; bcIrAStep(1); }
    else if (bcStep === 3) { document.getElementById('bc-wizard-titulo').textContent = 'Selecciona la categoría'; bcIrAStep(2); }
    else if (bcStep === 4) { document.getElementById('bc-wizard-titulo').textContent = 'Selecciona la marca'; bcIrAStep(3); }
}

async function bcGuardar() {
    const nombre        = document.getElementById('bc-nombre').value.trim();
    const stock_minimo  = document.getElementById('bc-stock-minimo').value;
    const stock_critico = document.getElementById('bc-stock-critico').value;
    const errDiv        = document.getElementById('bc-step4-errors');
    errDiv.classList.add('hidden');
    if (!nombre) { errDiv.textContent = 'El nombre del producto es obligatorio.'; errDiv.classList.remove('hidden'); document.getElementById('bc-nombre').focus(); return; }
    const btn = document.getElementById('bc-btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const usaPaquete  = document.getElementById('bc-paquete-check').checked;
        const paqRecibidos = parseInt(document.getElementById('bc-paquetes-recibidos').value) || 0;
        const cantIni      = usaPaquete
            ? paqRecibidos * (bcPresentCantidad || 1)
            : (parseInt(document.getElementById('bc-cantidad-inicial').value) || 0);

        const unidadMedidaId = document.getElementById('bc-unidad-medida')?.value || '';
        const contenedorId   = document.getElementById('bc-contenedor')?.value || '';
        const params = {
            _token: CSRF,
            nombre,
            stock_minimo,
            stock_critico,
            categoria_id:         bcCatId,
            codigo_barras:        bcCodigo,
            stock_inicial:        cantIni,
            maneja_presentacion:  usaPaquete && !!bcPresentNombre ? '1' : '0',
            tipo_presentacion:    (usaPaquete && bcPresentNombre) ? bcPresentNombre : '',
            cantidad_presentacion:(usaPaquete && bcPresentNombre) ? bcPresentCantidad : '',
        };
        if (bcMarcaId) params.marca_id = bcMarcaId;
        if (unidadMedidaId) params.unidad_medida_id = unidadMedidaId;
        if (contenedorId) params.contenedor = contenedorId;
        const body = new URLSearchParams(params);
        const res  = await fetch(ROUTE_PROD_STORE, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Error.'); errDiv.classList.remove('hidden'); }
        else {
            cerrarModalBarcode();
            limpiarScanner();
            location.reload();
        }
    } catch(e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

function bcRenderMarcas() {
    const familia = catalogoData.find(f => f.id === bcFamiliaId);
    const cat     = familia?.categorias.find(c => c.id === bcCatId);
    const marcas  = cat?.marcas ?? [];
    const lista   = document.getElementById('bc-marcas-lista');
    lista.innerHTML = '';

    marcas.forEach(m => {
        const sel = m.id === bcMarcaId;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = m.nombre;
        btn.className = 'text-sm font-medium px-4 py-3 rounded-xl border text-left transition ' +
            (sel ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400 hover:text-indigo-600');
        btn.onclick = () => { bcMarcaId = m.id; bcSinMarcaExplicito = false; bcRenderMarcas(); };
        lista.appendChild(btn);
    });

    // Sin marca
    const sinBtn = document.createElement('button');
    sinBtn.type = 'button';
    sinBtn.textContent = 'Sin marca';
    sinBtn.className = 'text-sm font-medium px-4 py-3 rounded-xl text-left transition';
    sinBtn.style.cssText = bcSinMarcaExplicito
        ? 'border:2px solid #6366f1; border-style:solid; background:#e0e7ff; color:#3730a3;'
        : 'border:1px dashed #d1d5db; background:transparent; color:#6b7280;';
    sinBtn.onmouseover = () => { if (!bcSinMarcaExplicito) sinBtn.style.borderColor = '#a5b4fc'; };
    sinBtn.onmouseout  = () => { if (!bcSinMarcaExplicito) sinBtn.style.borderColor = '#d1d5db'; };
    sinBtn.onclick = () => { bcMarcaId = null; bcSinMarcaExplicito = true; bcRenderMarcas(); };
    lista.appendChild(sinBtn);
}

function bcMostrarNuevaMarca() {
    document.getElementById('bc-nueva-marca-wrap').style.display = 'block';
    document.getElementById('bc-btn-nueva-marca').style.display  = 'none';
    document.getElementById('bc-nueva-marca-input').focus();
}

function bcOcultarNuevaMarca() {
    document.getElementById('bc-nueva-marca-wrap').style.display = 'none';
    document.getElementById('bc-btn-nueva-marca').style.display  = 'inline-flex';
    document.getElementById('bc-nueva-marca-input').value = '';
    document.getElementById('bc-nueva-marca-errors').classList.add('hidden');
}

async function bcGuardarNuevaMarca() {
    const nombre = document.getElementById('bc-nueva-marca-input').value.trim();
    const errDiv = document.getElementById('bc-nueva-marca-errors');
    if (!nombre || nombre.length < 2) { errDiv.textContent = 'El nombre debe tener al menos 2 caracteres.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    try {
        const body = new URLSearchParams({ _token: CSRF, nombre });
        const res  = await fetch(ROUTE_CAT_MARCA_STORE(bcCatId), { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors?.nombre?.[0] ?? 'Error.'; errDiv.classList.remove('hidden'); return; }
        // Agregar al catalogoData local
        const familia = catalogoData.find(f => f.id === bcFamiliaId);
        const cat     = familia?.categorias.find(c => c.id === bcCatId);
        if (cat) cat.marcas = cat.marcas ?? [];
        if (cat) cat.marcas.push({ id: json.id, nombre: json.nombre });
        bcMarcaId = json.id; bcSinMarcaExplicito = false;
        bcOcultarNuevaMarca();
        bcRenderMarcas();
    } catch(e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
}

// ── Modal helpers ────────────────────────────────────────────────────────────

function abrirModal(id) {
    const inner = document.getElementById(id + '-inner');
    const m     = document.getElementById(id);
    inner.style.animation = 'none';
    m.style.display = 'flex';
    void inner.offsetHeight;
    inner.style.animation = 'cat-in .25s cubic-bezier(.22,.68,0,1.2) both';
}
function cerrarModal(id) {
    document.getElementById(id).style.display = 'none';
}

// ── Modal Familia ────────────────────────────────────────────────────────────

function abrirModalFamilia() {
    document.getElementById('fam-nombre-input').value = '';
    document.getElementById('modal-fam-errors').classList.add('hidden');
    abrirModal('modal-familia');
    setTimeout(() => document.getElementById('fam-nombre-input').focus(), 50);
}
function cerrarModalFamilia() { cerrarModal('modal-familia'); }

async function guardarFamilia() {
    const nombre = document.getElementById('fam-nombre-input').value.trim();
    const errDiv = document.getElementById('modal-fam-errors');
    if (!nombre || nombre.length < 2) { errDiv.textContent = 'El nombre debe tener al menos 2 caracteres.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    const btn = document.getElementById('btn-guardar-fam');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const res  = await fetch(ROUTE_FAM_STORE, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ _token: CSRF, nombre, tipo_item: catalogoTipoActual }) });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors?.nombre?.[0] ?? json.message ?? 'Error al guardar.'; errDiv.classList.remove('hidden'); }
        else { cerrarModalFamilia(); localStorage.setItem('catalogo_tipo_item', catalogoTipoActual); window.location = '{{ route('admin.productos.catalogo') }}?familia=' + json.id; }
    } catch (e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

document.getElementById('modal-familia').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModalFamilia(); });
document.getElementById('fam-nombre-input').addEventListener('keydown', e => { if (e.key === 'Enter') guardarFamilia(); });

// ── Selección de categoría ───────────────────────────────────────────────────

function seleccionarCategoria(catId, catNombre) {
    catActualId      = catId;
    catActualNombre  = catNombre;
    marcaActualId    = null;
    marcaActualNombre = '';
    document.querySelectorAll('.cat-item').forEach(el => {
        el.classList.remove('bg-indigo-50', 'text-indigo-700', 'font-semibold');
        el.classList.add('text-gray-700');
    });
    const btn = document.getElementById('cat-btn-' + catId);
    if (btn) { btn.classList.add('bg-indigo-50', 'text-indigo-700', 'font-semibold'); btn.classList.remove('text-gray-700'); }

    document.getElementById('titulo-categoria').textContent = catNombre;
    const catRaw = catalogoData.flatMap(f => f.categorias).find(c => c.id === catId);
    const cat    = getCategoriaFiltrada(catRaw);
    const count  = cat?.productos?.length ?? 0;
    const label  = labelTipoCatalogo(false);
    document.getElementById('subtitulo-categoria').textContent = count === 0
        ? ('Sin ' + labelTipoCatalogo(true))
        : (count === 1 ? ('1 ' + label) : (count + ' ' + labelTipoCatalogo(true)));

    const btnNuevo = document.getElementById('btn-nuevo-producto');
    btnNuevo.classList.remove('hidden');
    btnNuevo.style.display = 'inline-flex';

    if (marcasAplicanCatalogo()) {
        renderMarcas(cat);
    } else {
        limpiarPanelCatalogo();
        document.getElementById('titulo-categoria').textContent = catNombre;
        document.getElementById('subtitulo-categoria').textContent = count === 0
            ? ('Sin ' + labelTipoCatalogo(true))
            : (count === 1 ? ('1 ' + label) : (count + ' ' + labelTipoCatalogo(true)));
        btnNuevo.classList.remove('hidden');
        btnNuevo.style.display = 'inline-flex';
    }
    renderProductos(cat?.productos ?? []);
}

function renderProductos(productos) {
    const area = document.getElementById('area-productos');
    const emptyMsg = 'Sin ' + labelTipoCatalogo(true) + ' en esta categoría. Agrega el primero.';
    if (!productos.length) {
        area.innerHTML = '<p class="text-sm text-gray-400 text-center py-8 italic">' + emptyMsg + '</p>';
        return;
    }
    let html = '<div class="space-y-2">';
    productos.forEach(p => {
        const tipoItem = normalizarTipoCatalogoProducto(p);
        const esNoFisico = tipoItem !== 'producto';
        const estado     = esNoFisico ? tipoItem : (p.stock_actual <= p.stock_critico ? 'critico' : p.stock_actual <= p.stock_minimo ? 'minimo' : 'normal');
        const colorStock = estado === 'critico' ? 'text-red-600' : estado === 'minimo' ? 'text-yellow-600' : esNoFisico ? 'text-gray-400' : 'text-green-600';
        const stockDisplay = esNoFisico
            ? '<span class="text-xs text-gray-400 italic">—</span>'
            : `<span class="text-lg font-bold ${colorStock}">${p.stock_actual}</span>`;
        const tipoBadge = tipoItem === 'servicio' ? '[SERVICIO]' : (tipoItem === 'mantencion' ? '[MANTENCIÓN]' : '[ARRIENDO]');
        const stockBadges = esNoFisico
            ? '<span class="cat-servicio-badge">' + tipoBadge + '</span>'
            : `<span class="inline-flex items-center gap-1 bg-yellow-50 border border-yellow-200 text-yellow-700 font-medium px-2 py-0.5 rounded-md">Mín: <strong>${p.stock_minimo}</strong></span>
               <span class="inline-flex items-center gap-1 bg-red-50 border border-red-200 text-red-600 font-medium px-2 py-0.5 rounded-md">Crít: <strong>${p.stock_critico}</strong></span>`;
        html += `
        <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 leading-snug">${escHtml(p.nombre)}</p>
                ${(!esNoFisico && p.marca_nombre) ? `<p class="text-xs text-indigo-600 font-semibold">${escHtml(p.marca_nombre)}</p>` : ''}
                <p class="text-xs mt-1 flex items-center gap-1.5">${stockBadges}</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                ${stockDisplay}
                ${`<button onclick="editarProducto(${p.id})"
                        class="btn-ghost text-xs font-semibold text-gray-600 hover:text-gray-800 border border-gray-300 hover:border-gray-400 bg-white hover:bg-gray-50 px-3 py-1.5 rounded-lg">
                    Editar
                </button>`}
                ${IS_DEV ? `<button onclick="eliminarProducto(${p.id}, '${escHtml(p.nombre).replace(/'/g,"\\'")}')"
                        class="btn-ghost text-xs font-semibold text-red-500 hover:text-red-700 border border-red-200 hover:border-red-400 bg-white hover:bg-red-50 px-3 py-1.5 rounded-lg">
                    Eliminar
                </button>` : ''}
            </div>
        </div>`;
    });
    html += '</div>';
    area.innerHTML = html;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Modal Categoría ──────────────────────────────────────────────────────────

function abrirModalCategoria(familiaId) {
    catFamiliaId  = familiaId;
    editandoCatId = null;
    document.getElementById('modal-cat-titulo').textContent    = 'Nueva categoría';
    document.getElementById('modal-cat-subtitulo').textContent = catalogoData.find(f => f.id === familiaId)?.nombre ?? '';
    document.getElementById('cat-nombre-input').value          = '';
    document.getElementById('modal-cat-errors').classList.add('hidden');
    abrirModal('modal-categoria');
    setTimeout(() => document.getElementById('cat-nombre-input').focus(), 50);
}

function editarCategoria(catId, catNombre) {
    editandoCatId = catId;
    const familia = catalogoData.find(f => f.categorias.some(c => c.id === catId));
    catFamiliaId  = familia ? familia.id : catFamiliaId;
    document.getElementById('modal-cat-titulo').textContent    = 'Editar categoría';
    document.getElementById('modal-cat-subtitulo').textContent = familia ? familia.nombre : '';
    document.getElementById('cat-nombre-input').value          = catNombre;
    document.getElementById('modal-cat-errors').classList.add('hidden');
    abrirModal('modal-categoria');
    setTimeout(() => document.getElementById('cat-nombre-input').focus(), 50);
}

let _eliminarCatId = null;

function abrirConfirmarEliminarCat(catId, catNombre) {
    _eliminarCatId = catId;
    document.getElementById('eliminar-cat-nombre').textContent = catNombre;
    document.getElementById('eliminar-cat-error').classList.add('hidden');
    document.getElementById('btn-confirmar-eliminar-cat').disabled = false;
    document.getElementById('btn-confirmar-eliminar-cat').textContent = 'Eliminar';
    document.getElementById('modal-eliminar-cat').style.display = 'flex';
}

function cerrarConfirmarEliminarCat() {
    document.getElementById('modal-eliminar-cat').style.display = 'none';
    _eliminarCatId = null;
}

async function confirmarEliminarCat() {
    if (!_eliminarCatId) return;
    const btn    = document.getElementById('btn-confirmar-eliminar-cat');
    const errDiv = document.getElementById('eliminar-cat-error');
    btn.disabled = true;
    btn.textContent = 'Eliminando...';
    errDiv.classList.add('hidden');
    try {
        const res  = await fetch(ROUTE_CAT_DESTROY(_eliminarCatId), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (!res.ok || !json.ok) {
            errDiv.textContent = json.message ?? 'No se pudo eliminar la categoría.';
            errDiv.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            return;
        }
        location.reload();
    } catch(e) {
        errDiv.textContent = 'Error de conexión.';
        errDiv.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Eliminar';
    }
}

document.getElementById('modal-eliminar-cat').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarConfirmarEliminarCat(); });

function cerrarModalCategoria() { cerrarModal('modal-categoria'); }

async function guardarCategoria() {
    const nombre = document.getElementById('cat-nombre-input').value.trim();
    const errDiv = document.getElementById('modal-cat-errors');
    if (!nombre || nombre.length < 2) { errDiv.textContent = 'El nombre debe tener al menos 2 caracteres.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    const btn = document.getElementById('btn-guardar-cat');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const url    = editandoCatId ? ROUTE_CAT_UPDATE(editandoCatId) : ROUTE_CAT_STORE;
        const method = editandoCatId ? 'PUT' : 'POST';
        const body   = new URLSearchParams({ _token: CSRF, nombre, tipo_item: catalogoTipoActual });
        if (!editandoCatId) body.append('familia_id', catFamiliaId);
        const res  = await fetch(url, { method, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors?.nombre?.[0] ?? json.message ?? 'Error al guardar.'; errDiv.classList.remove('hidden'); }
        else { cerrarModalCategoria(); location.reload(); }
    } catch (e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

document.getElementById('modal-categoria').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModalCategoria(); });
document.getElementById('cat-nombre-input').addEventListener('keydown', e => { if (e.key === 'Enter') guardarCategoria(); });

// ── Panel de Marcas ──────────────────────────────────────────────────────────

function renderMarcas(cat) {
    const hint  = document.getElementById('marcas-panel-hint');
    const lista = document.getElementById('lista-marcas');
    hint.style.display = 'none';
    lista.style.display = 'block';

    const catMarcas  = cat ? (cat.marcas || []) : [];
    const cuentas    = {};
    (cat?.productos ?? []).forEach(function(p) {
        if (p.marca_id) cuentas[p.marca_id] = (cuentas[p.marca_id] || 0) + 1;
    });
    const totalProds = (cat?.productos ?? []).length;

    lista.innerHTML = '';

    // "Todas" row
    const liTodas = document.createElement('li');
    const isTodas = marcaActualId === null;
    liTodas.innerHTML = `<button onclick="seleccionarMarca(null, '')" id="marca-btn-todas"
        class="btn-ghost w-full text-left px-3 py-2.5 rounded-lg text-sm flex items-center justify-between
               ${isTodas ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50'}">
        <span>Todas</span>
        <span class="text-xs text-gray-400 ml-2 shrink-0">${totalProds}</span>
    </button>`;
    lista.appendChild(liTodas);

    // "Sin Marca" row — products with no brand assigned
    const sinMarcaCount = (cat?.productos ?? []).filter(function(p) { return !p.marca_id; }).length;
    const isSinMarca = marcaActualId === 0;
    const liSinMarca = document.createElement('li');
    liSinMarca.innerHTML = `<button onclick="seleccionarMarca(0, 'Sin Marca')" id="marca-btn-sin-marca"
        class="btn-ghost w-full text-left px-3 py-2.5 rounded-lg text-sm flex items-center justify-between
               ${isSinMarca ? 'bg-amber-50 text-amber-700 font-semibold' : 'text-gray-500 hover:bg-gray-50'}">
        <span>SIN MARCA</span>
        <span class="text-xs ${sinMarcaCount > 0 ? 'text-amber-500' : 'text-gray-300'} ml-2 shrink-0">${sinMarcaCount}</span>
    </button>`;
    lista.appendChild(liSinMarca);

    const marcasActivas = catMarcas.filter(function(m) { return m.activo !== false; });

    if (marcasActivas.length === 0) {
        const liVacio = document.createElement('li');
        liVacio.innerHTML = '<p class="text-xs text-gray-400 italic px-3 py-2">Sin marcas. Usa "Nueva" para crear una.</p>';
        lista.appendChild(liVacio);
    } else {
        marcasActivas.forEach(function(m) {
            const count = cuentas[m.id] || 0;
            const isSel = marcaActualId === m.id;
            const li = document.createElement('li');
            li.className = 'flex items-center gap-1 group';
            li.innerHTML = `
                <button onclick="seleccionarMarca(${m.id}, '${escHtml(m.nombre).replace(/'/g, "\\'")}')" id="marca-btn-${m.id}"
                        class="btn-ghost flex-1 text-left px-3 py-2.5 rounded-lg text-sm flex items-center justify-between
                               ${isSel ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50'}">
                    <span class="truncate">${escHtml(m.nombre)}</span>
                    <span class="text-xs ${count > 0 ? 'text-gray-400' : 'text-gray-300'} ml-2 shrink-0">${count}</span>
                </button>
                <button onclick="abrirModalMarcaCatalogo(${m.id})" title="Editar marca"
                        class="opacity-0 group-hover:opacity-100 p-2 text-gray-400 hover:text-indigo-600 rounded-md hover:bg-indigo-50 transition shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button onclick="desasociarMarcaDeCategoria(${m.id})" title="Eliminar marca"
                        class="opacity-0 group-hover:opacity-100 p-2 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;
            lista.appendChild(li);
        });
    }

}

function seleccionarMarca(marcaId, marcaNombre) {
    marcaActualId     = marcaId;
    marcaActualNombre = marcaNombre;

    const catRaw = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catActualId; });
    const cat = getCategoriaFiltrada(catRaw);
    renderMarcas(cat);

    let prods = cat ? cat.productos : [];
    if (marcaId === 0) {
        // Sin Marca: products with no brand assigned
        prods = prods.filter(function(p) { return !p.marca_id; });
    } else if (marcaId) {
        prods = prods.filter(function(p) { return p.marca_id === marcaId; });
    }

    const total = cat ? cat.productos.length : 0;
    const shown = prods.length;
    document.getElementById('subtitulo-categoria').textContent = (marcaId !== null)
        ? (shown === 0 ? 'Sin ' + labelTipoCatalogo(true) + ' ' + (marcaId === 0 ? 'sin marca' : 'de ' + marcaNombre) : shown + ' de ' + total + ' ' + labelTipoCatalogo(true))
        : (total === 0 ? 'Sin ' + labelTipoCatalogo(true) : (total === 1 ? '1 ' + labelTipoCatalogo(false) : total + ' ' + labelTipoCatalogo(true)));

    renderProductos(prods);
}

// ── Modal Marca (catálogo) ────────────────────────────────────────────────────

function abrirModalMarcaCatalogo(marcaId) {
    marcaId = marcaId || null;
    editandoMarcaCatId = marcaId;
    const marca = marcaId ? marcasData.find(function(m) { return m.id === marcaId; }) : null;
    document.getElementById('modal-marca-cat-titulo').textContent = marcaId ? 'Editar marca' : 'Nueva marca';
    document.getElementById('marca-cat-nombre-input').value = marca ? marca.nombre : '';
    document.getElementById('modal-marca-cat-errors').classList.add('hidden');
    abrirModal('modal-marca-cat');
    setTimeout(function() { document.getElementById('marca-cat-nombre-input').focus(); }, 50);
}

async function guardarMarcaCatalogo() {
    const nombre = document.getElementById('marca-cat-nombre-input').value.trim().toUpperCase();
    const errDiv = document.getElementById('modal-marca-cat-errors');
    if (!nombre || nombre.length < 2) { errDiv.textContent = 'El nombre debe tener al menos 2 caracteres.'; errDiv.classList.remove('hidden'); return; }
    if (!editandoMarcaCatId && !catActualId) { errDiv.textContent = 'Selecciona una categoría primero.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    const btn = document.getElementById('btn-guardar-marca-cat');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const url    = editandoMarcaCatId ? ROUTE_MARCA_UPDATE(editandoMarcaCatId) : ROUTE_CAT_MARCA_STORE(catActualId);
        const method = editandoMarcaCatId ? 'PUT' : 'POST';
        const res = await fetch(url, {
            method,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ _token: CSRF, nombre, tipo_item: catalogoTipoActual })
        });
        const json = await res.json();
        if (!res.ok || !json.ok) {
            errDiv.textContent = json.errors?.nombre?.[0] ?? json.message ?? 'Error al guardar.';
            errDiv.classList.remove('hidden');
        } else {
            const cat = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catActualId; });
            if (editandoMarcaCatId) {
                // Update in catalogoData
                if (cat) {
                    const mIdx = cat.marcas.findIndex(function(m) { return m.id === editandoMarcaCatId; });
                    if (mIdx !== -1) cat.marcas[mIdx].nombre = json.nombre;
                    cat.productos.forEach(function(p) {
                        if (p.marca_id === editandoMarcaCatId) p.marca_nombre = json.nombre;
                    });
                }
                const gIdx = marcasData.findIndex(function(m) { return m.id === editandoMarcaCatId; });
                if (gIdx !== -1) marcasData[gIdx].nombre = json.nombre;
            } else {
                // Brand created directly in category — add to catalogoData
                const newMarca = { id: json.id, nombre: json.nombre, activo: true, tipo_item: 'producto' };
                if (cat) {
                    cat.marcas.push(newMarca);
                    cat.marcas.sort(function(a, b) { return a.nombre.localeCompare(b.nombre); });
                }
                marcasData.push(newMarca);
                marcasData.sort(function(a, b) { return a.nombre.localeCompare(b.nombre); });
            }
            cerrarModal('modal-marca-cat');
            if (marcasAplicanCatalogo()) renderMarcas(getCategoriaFiltrada(cat));
            if (catActualId) {
                let prods = productosPorTipo(cat ? cat.productos : []);
                if (marcaActualId > 0) prods = prods.filter(function(p) { return p.marca_id === marcaActualId; });
                else if (marcaActualId === 0) prods = prods.filter(function(p) { return !p.marca_id; });
                renderProductos(prods);
            }
        }
    } catch(e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

async function toggleMarcaCatalogo(marcaId) {
    try {
        const res = await fetch(ROUTE_MARCA_TOGGLE(marcaId), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ _token: CSRF, _method: 'PATCH' })
        });
        const json = await res.json();
        if (json.ok) {
            const cat = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catActualId; });
            if (cat) {
                const mIdx = cat.marcas.findIndex(function(m) { return m.id === marcaId; });
                if (mIdx !== -1) cat.marcas[mIdx].activo = json.activo;
            }
            const gIdx = marcasData.findIndex(function(m) { return m.id === marcaId; });
            if (gIdx !== -1) marcasData[gIdx].activo = json.activo;
            if (!json.activo && marcaActualId === marcaId) { marcaActualId = null; marcaActualNombre = ''; }
            renderMarcas(getCategoriaFiltrada(cat));
        }
    } catch(e) { showAviso('Error de conexión.', 'error'); }
}

document.getElementById('modal-marca-cat').addEventListener('click', function(e) { if (e.target === e.currentTarget) cerrarModal('modal-marca-cat'); });

async function desasociarMarcaDeCategoria(marcaId) {
    if (!catActualId) return;
    try {
        const res = await fetch(ROUTE_MARCA_DESTROY(marcaId), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json',
                       'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ _token: CSRF, _method: 'DELETE' })
        });
        const json = await res.json();
        if (json.ok) {
            const cat = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catActualId; });
            if (cat) {
                cat.marcas = cat.marcas.filter(function(m) { return m.id !== marcaId; });
                if (marcaActualId === marcaId) { marcaActualId = null; marcaActualNombre = ''; }
            }
            marcasData = marcasData.filter(function(m) { return m.id !== marcaId; });
            renderMarcas(getCategoriaFiltrada(cat));
            if (marcaActualId === null) {
                const prods = productosPorTipo(cat ? cat.productos : []);
                renderProductos(prods);
                document.getElementById('subtitulo-categoria').textContent = prods.length === 0 ? 'Sin ' + labelTipoCatalogo(true) : (prods.length === 1 ? '1 ' + labelTipoCatalogo(false) : prods.length + ' ' + labelTipoCatalogo(true));
            }
        } else {
            showAviso(json.message ?? 'No se puede eliminar esta marca.', 'error');
        }
    } catch(e) { showAviso('Error de conexión.', 'error'); }
}

// ── Marca helpers ────────────────────────────────────────────────────────────

function poblarSelectMarca(selectedId) {
    var sel = document.getElementById('prod-marca');
    sel.innerHTML = '<option value="">— Selecciona una marca —</option>';

    var catId = editandoProdId ? catActualId : prodCatId;
    var cat   = catId ? catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catId; }) : null;
    var pool  = cat ? (cat.marcas || []).filter(function(m) { return m.activo !== false; }) : [];

    // In edit mode always include the currently assigned brand even if it changed categories
    var lista = pool.slice();
    if (selectedId && !lista.find(function(m) { return String(m.id) === String(selectedId); })) {
        var current = marcasData.find(function(m) { return String(m.id) === String(selectedId); });
        if (current) lista.unshift(current);
    }

    lista.forEach(function(m) {
        var opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = m.nombre;
        if (selectedId && String(m.id) === String(selectedId)) opt.selected = true;
        sel.appendChild(opt);
    });
}

async function crearMarcaRapida() {
    var nombreInput = document.getElementById('prod-nueva-marca');
    var nombre = nombreInput.value.trim().toUpperCase();
    if (!nombre) return;
    var catId = editandoProdId ? catActualId : prodCatId;
    if (!catId) { showAviso('Selecciona una categoría primero.', 'warn'); return; }
    try {
        var res = await fetch(ROUTE_CAT_MARCA_STORE(catId), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ _token: CSRF, nombre, tipo_item: 'producto' })
        });
        var json = await res.json();
        if (json.ok) {
            var cat = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catId; });
            var newMarca = { id: json.id, nombre: json.nombre, activo: true, tipo_item: 'producto' };
            if (cat) {
                cat.marcas.push(newMarca);
                cat.marcas.sort(function(a, b) { return a.nombre.localeCompare(b.nombre); });
            }
            marcasData.push(newMarca);
            marcasData.sort(function(a, b) { return a.nombre.localeCompare(b.nombre); });
            poblarSelectMarca(json.id);
            nombreInput.value = '';
        } else {
            showAviso(json.errors?.nombre?.[0] ?? json.message ?? 'Error al crear la marca.', 'error');
        }
    } catch(e) { showAviso('Error de conexión.', 'error'); }
}

// ── Modal Producto ───────────────────────────────────────────────────────────

let prodFamiliaId = null;
let prodCatId     = null;

function prodRenderFamilias() {
    const cont = document.getElementById('prod-familias-btns');
    cont.innerHTML = '';
    catalogoData.forEach(function(f) {
        var sel = f.id === prodFamiliaId;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = f.nombre;
        btn.className = 'text-xs font-semibold px-3 py-1.5 rounded-lg border transition ' +
            (sel ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400 hover:text-indigo-600');
        btn.onclick = function() { prodFamiliaId = f.id; prodCatId = null; prodRenderFamilias(); prodRenderCategorias(); };
        cont.appendChild(btn);
    });
}

function prodRenderCategorias() {
    var wrapper = document.getElementById('prod-cat-wrapper');
    var cont    = document.getElementById('prod-categorias-btns');
    if (!prodFamiliaId) { wrapper.style.display = 'none'; return; }
    var familia = catalogoData.find(function(f) { return f.id === prodFamiliaId; });
    cont.innerHTML = '';
    (familia ? familia.categorias : []).forEach(function(c) {
        var sel = c.id === prodCatId;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = c.nombre;
        btn.className = 'text-xs font-semibold px-3 py-1.5 rounded-lg border transition ' +
            (sel ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400 hover:text-indigo-600');
        btn.onclick = function() { prodCatId = c.id; prodRenderCategorias(); poblarSelectMarca(null); };
        cont.appendChild(btn);
    });
    wrapper.style.display = 'block';
}

function abrirModalProducto() {
    editandoProdId = null;
    var familia = catActualId ? catalogoData.find(function(f) { return f.categorias.some(function(c) { return c.id === catActualId; }); }) : null;
    prodFamiliaId = familia ? familia.id : null;
    prodCatId     = catActualId;

    // Populate breadcrumb
    var catObj = familia ? familia.categorias.find(function(c) { return c.id === catActualId; }) : null;
    document.getElementById('prod-breadcrumb-fam').textContent = familia ? familia.nombre : '—';
    document.getElementById('prod-breadcrumb-cat').textContent = catObj  ? catObj.nombre  : '—';
    // For SERVICIOS: no marca in breadcrumb
    if (IS_FAMILIA_SERVICIOS) {
        document.getElementById('prod-breadcrumb-sep-marca').style.display = 'none';
        document.getElementById('prod-breadcrumb-marca').textContent = '';
    } else {
        var hasMarca = !!marcaActualNombre;
        document.getElementById('prod-breadcrumb-sep-marca').style.display = hasMarca ? '' : 'none';
        document.getElementById('prod-breadcrumb-marca').textContent = hasMarca ? marcaActualNombre : '';
    }

    document.getElementById('prod-selector-wrapper').style.display  = 'block';
    document.getElementById('modal-prod-errors').classList.add('hidden');
    document.getElementById('modal-prod-success').classList.add('hidden');
    document.getElementById('prod-nombre').value = '';
    document.getElementById('prod-nombre-wrapper').style.display = 'block';
    document.getElementById('prod-tipo-item').value = catalogoTipoActual;
    document.getElementById('prod-tipo-wrapper').style.display = 'block';
    resetCamposOperacionales();

    if (IS_FAMILIA_SERVICIOS) {
        // Service mode: simplified form
        document.getElementById('modal-prod-titulo').textContent   = 'Nuevo servicio';
        document.getElementById('modal-prod-subtitulo').textContent = '';
        document.getElementById('prod-nombre-label').innerHTML = 'Descripción del servicio <span style="color:#ef4444">*</span>';
        document.getElementById('prod-nombre').placeholder = 'Ej: Mantención preventiva impresora HP';
        document.getElementById('prod-stock-wrap').style.display     = 'none';
        document.getElementById('prod-contenedor-wrapper').style.display = 'none';
        document.getElementById('prod-unidad-wrapper').style.display    = 'none';
        document.getElementById('prod-pres-wrapper').style.display     = 'none';
        resetPresentacionFields();
    } else {
        // Physical product mode: full form
        document.getElementById('modal-prod-titulo').textContent   = 'Nuevo producto';
        document.getElementById('prod-pres-wrapper').style.display = 'block';
        resetPresentacionFields();
        document.getElementById('modal-prod-subtitulo').textContent = '';
        document.getElementById('prod-nombre-label').innerHTML = 'Nombre del producto <span style="color:#ef4444">*</span>';
        document.getElementById('prod-nombre').placeholder = 'Ej: Cable HDMI 1.8m';
        document.getElementById('prod-stock-minimo').value  = '0';
        document.getElementById('prod-stock-critico').value = '0';
        document.getElementById('prod-stock-wrap').style.display = '';

        // Poblar y mostrar select de contenedor
        var sel = document.getElementById('prod-contenedor');
        sel.innerHTML = '<option value="">— Selecciona un contenedor —</option>';
        containersData.forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            sel.appendChild(opt);
        });
        document.getElementById('prod-contenedor-wrapper').style.display = 'block';

        // Poblar y mostrar select de unidad de medida
        var selU = document.getElementById('prod-unidad');
        selU.innerHTML = '<option value="">— Selecciona una unidad —</option>';
        unidadesData.forEach(function(u) {
            var opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.nombre + (u.abreviacion ? ' (' + u.abreviacion + ')' : '');
            selU.appendChild(opt);
        });
        document.getElementById('prod-unidad-wrapper').style.display = 'block';
    }
    toggleTipoItemProducto();

    abrirModal('modal-producto');
    setTimeout(function() { document.getElementById('prod-nombre').focus(); }, 50);
}

// Deriva "Unidad", "Metro lineal", etc. desde el nombre de la unidad de medida seleccionada
function getUnidadBaseFromSelected() {
    var sel = document.getElementById('prod-unidad');
    if (!sel || !sel.value) return 'unidad';
    var txt = (sel.options[sel.selectedIndex]?.text || '').replace(/\s*\(.*?\)\s*$/, '').trim();
    return txt.toLowerCase().replace(/\b\w/g, function(c) { return c.toUpperCase(); }) || 'unidad';
}

// Wire up preview update on presentacion inputs (once, after DOM ready)
document.addEventListener('DOMContentLoaded', function() {
    ['prod-tipo-pres','prod-cant-pres','prod-unidad'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', actualizarPreviewPres);
        if (el) el.addEventListener('change', actualizarPreviewPres);
    });
});

function editarProducto(prodId) {
    var cat  = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catActualId; });
    var prod = cat ? cat.productos.find(function(p) { return p.id === prodId; }) : null;
    if (!prod) return;
    editandoProdId = prodId;
    document.getElementById('prod-selector-wrapper').style.display = 'none';
    document.getElementById('modal-prod-titulo').textContent    = 'Editar producto';
    document.getElementById('modal-prod-subtitulo').textContent = catActualNombre;
    document.getElementById('prod-stock-minimo').value          = prod.stock_minimo;
    document.getElementById('prod-stock-critico').value         = prod.stock_critico;
    document.getElementById('modal-prod-errors').classList.add('hidden');
    document.getElementById('modal-prod-success').classList.add('hidden');
    document.getElementById('prod-contenedor-wrapper').style.display = 'none';
    document.getElementById('prod-nombre-wrapper').style.display = 'none';
    document.getElementById('prod-tipo-wrapper').style.display = 'block';
    document.getElementById('prod-tipo-item').value = prod.tipo_item || (prod.es_servicio ? 'servicio' : 'producto');
    // Mostrar unidad en edición para que pueda cambiarse
    var selU = document.getElementById('prod-unidad');
    selU.innerHTML = '<option value="">— Selecciona una unidad —</option>';
    unidadesData.forEach(function(u) {
        var opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = u.nombre + (u.abreviacion ? ' (' + u.abreviacion + ')' : '');
        if (u.id === prod.unidad_medida_id) opt.selected = true;
        selU.appendChild(opt);
    });
    document.getElementById('prod-unidad-wrapper').style.display = (prod.tipo_item || (prod.es_servicio ? 'servicio' : 'producto')) === 'producto' ? 'block' : 'none';
    // Presentación
    if ((prod.tipo_item || (prod.es_servicio ? 'servicio' : 'producto')) === 'producto') {
        document.getElementById('prod-pres-wrapper').style.display = 'block';
        document.getElementById('prod-maneja-pres').checked = !!prod.maneja_presentacion;
        document.getElementById('prod-tipo-pres').value   = prod.tipo_presentacion || '';
        document.getElementById('prod-cant-pres').value   = prod.cantidad_presentacion || '';
        document.getElementById('prod-pres-fields').style.display = prod.maneja_presentacion ? 'grid' : 'none';
        actualizarPreviewPres();
    } else {
        document.getElementById('prod-pres-wrapper').style.display = 'none';
    }
    toggleTipoItemProducto();
    abrirModal('modal-producto');
    setTimeout(function() { document.getElementById('prod-stock-minimo').focus(); }, 50);
}

function cerrarModalProducto() { cerrarModal('modal-producto'); }

function tipoItemActualProducto() {
    return document.getElementById('prod-tipo-item')?.value || 'producto';
}

function resetCamposOperacionales() {
    ['prod-proveedor', 'prod-fecha-ejecucion', 'prod-documento-referencia', 'prod-observacion',
     'prod-arr-proveedor', 'prod-arr-fecha-inicio', 'prod-arr-fecha-termino', 'prod-arr-monto-periodo',
     'prod-arr-monto-total', 'prod-arr-documento', 'prod-arr-observacion'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    var estado = document.getElementById('prod-estado-operacional');
    if (estado) estado.value = 'pendiente';
    var condicion = document.getElementById('prod-arr-condicion');
    if (condicion) condicion.value = 'con_fecha';
    var unidad = document.getElementById('prod-arr-unidad-tiempo');
    if (unidad) unidad.value = 'dia';
    toggleCondicionArriendo();
}

function toggleCondicionArriendo() {
    var condicion = document.getElementById('prod-arr-condicion')?.value || 'con_fecha';
    var wrap = document.getElementById('prod-arr-fecha-termino-wrap');
    var input = document.getElementById('prod-arr-fecha-termino');
    if (wrap) wrap.style.display = condicion === 'con_fecha' ? '' : 'none';
    if (input && condicion !== 'con_fecha') input.value = '';
}

function toggleTipoItemProducto() {
    var tipo = tipoItemActualProducto();
    var fisico = tipo === 'producto';
    var label = tipo === 'arriendo' ? 'Nombre del arriendo' : (tipo === 'mantencion' ? 'Nombre de la mantención' : (tipo === 'servicio' ? 'Descripción del servicio' : 'Nombre del producto'));
    var title = tipo === 'arriendo' ? 'Nuevo arriendo' : (tipo === 'mantencion' ? 'Nueva mantención' : (tipo === 'servicio' ? 'Nuevo servicio' : 'Nuevo producto'));
    var nombreLabel = document.getElementById('prod-nombre-label');
    var titulo = document.getElementById('modal-prod-titulo');
    if (titulo && !editandoProdId) titulo.textContent = title;
    if (nombreLabel && document.getElementById('prod-nombre-wrapper').style.display !== 'none') {
        nombreLabel.innerHTML = label + ' <span style="color:#ef4444">*</span>';
    }
    document.getElementById('prod-nombre').placeholder = tipo === 'arriendo'
        ? 'Ej: Arriendo impresora mensual'
        : (tipo === 'mantencion'
            ? 'Ej: Mantención preventiva climatización'
            : (tipo === 'servicio' ? 'Ej: Soporte software' : 'Ej: Cable HDMI 1.8m'));
    document.getElementById('prod-stock-wrap').style.display = fisico ? '' : 'none';
    document.getElementById('prod-contenedor-wrapper').style.display = fisico && !editandoProdId ? '' : 'none';
    document.getElementById('prod-unidad-wrapper').style.display = fisico ? 'block' : 'none';
    document.getElementById('prod-pres-wrapper').style.display = fisico ? 'block' : 'none';
    document.getElementById('prod-operacional-wrapper').style.display = (tipo === 'servicio' || tipo === 'mantencion') ? 'block' : 'none';
    document.getElementById('prod-arriendo-wrapper').style.display = tipo === 'arriendo' ? 'block' : 'none';
    document.getElementById('prod-fecha-ejecucion-wrap').style.display = tipo === 'mantencion' ? '' : 'none';
    document.getElementById('prod-operacional-titulo').textContent = tipo === 'mantencion' ? 'Control mantención' : 'Control servicio';
    if (!fisico) resetPresentacionFields();
}

/* ── Presentaciones ──────────────────────────────────────────────── */
function togglePresentacionFields() {
    var checked = document.getElementById('prod-maneja-pres').checked;
    var fields  = document.getElementById('prod-pres-fields');
    fields.style.display = checked ? 'grid' : 'none';
    if (!checked) actualizarPreviewPres();
}
function actualizarPreviewPres() {
    var preview = document.getElementById('prod-pres-preview');
    var tipo    = document.getElementById('prod-tipo-pres')?.value;
    var cant    = parseInt(document.getElementById('prod-cant-pres')?.value) || 0;
    var base    = getUnidadBaseFromSelected();
    if (tipo && cant >= 2) {
        preview.textContent = '→ 1 ' + tipo + ' = ' + cant + ' ' + base + '(s)  ·  Ejemplo: 3 ' + tipo + '(s) = ' + (3 * cant) + ' ' + base + '(s)';
    } else {
        preview.textContent = 'Completa los campos para ver el equivalente.';
    }
}
function resetPresentacionFields() {
    document.getElementById('prod-maneja-pres').checked = false;
    document.getElementById('prod-pres-fields').style.display = 'none';
    document.getElementById('prod-tipo-pres').value    = '';
    document.getElementById('prod-cant-pres').value    = '';
    document.getElementById('prod-pres-preview').textContent = '';
}

async function eliminarProducto(prodId, nombre) {
    if (!confirm('¿Inactivar el producto "' + nombre + '"?\n\nEl producto quedará inactivo y no aparecerá en el inventario, pero sus datos históricos se conservan.')) return;
    try {
        const res  = await fetch(ROUTE_PROD_DESTROY(prodId), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (!res.ok || !json.ok) { showAviso('Error al inactivar el producto.', 'error'); return; }
        // Quitar del catalogoData local
        catalogoData.forEach(function(f) {
            f.categorias.forEach(function(c) {
                var idx = c.productos.findIndex(function(p) { return p.id === prodId; });
                if (idx !== -1) c.productos.splice(idx, 1);
            });
        });
        // Actualizar contador de la categoría activa
        var cat = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catActualId; });
        if (cat) {
            var prods = productosPorTipo(cat.productos);
            var span = document.querySelector('#cat-btn-' + catActualId + ' span.text-xs');
            if (span) span.textContent = prods.length;
            document.getElementById('subtitulo-categoria').textContent = prods.length === 0 ? 'Sin ' + labelTipoCatalogo(true) : (prods.length === 1 ? '1 ' + labelTipoCatalogo(false) : prods.length + ' ' + labelTipoCatalogo(true));
            renderProductos(prods);
            if (marcasAplicanCatalogo()) renderMarcas(getCategoriaFiltrada(cat));
            renderCategoriasCatalogo();
            contarTipoCatalogo();
        }
    } catch(e) { showAviso('Error de conexión.', 'error'); }
}

async function guardarProducto() {
    var errDiv = document.getElementById('modal-prod-errors');
    var sucDiv = document.getElementById('modal-prod-success');
    errDiv.classList.add('hidden');
    sucDiv.classList.add('hidden');

    if (!editandoProdId && !prodCatId) { errDiv.textContent = 'No hay categoría seleccionada.'; errDiv.classList.remove('hidden'); return; }

    var nombreProd = document.getElementById('prod-nombre')?.value.trim() ?? '';
    if (!editandoProdId && !nombreProd) {
        errDiv.textContent = 'El nombre de ' + labelTipoCatalogo(false) + ' es obligatorio.';
        errDiv.classList.remove('hidden');
        return;
    }

    var stock_minimo  = '0';
    var stock_critico = '0';
    var contenedorId  = '';
    var unidadId      = '';
    var tipoItem      = tipoItemActualProducto();
    var esFisico      = tipoItem === 'producto';

    if (esFisico) {
        stock_minimo  = document.getElementById('prod-stock-minimo').value;
        stock_critico = document.getElementById('prod-stock-critico').value;
        contenedorId  = document.getElementById('prod-contenedor')?.value ?? '';
        unidadId      = document.getElementById('prod-unidad')?.value ?? '';
        if (!editandoProdId && !contenedorId) {
            errDiv.textContent = 'Debes seleccionar un contenedor.';
            errDiv.classList.remove('hidden');
            return;
        }
        if (!editandoProdId && !unidadId) {
            errDiv.textContent = 'Debes seleccionar una unidad de medida.';
            errDiv.classList.remove('hidden');
            return;
        }
    } else if (!editandoProdId && tipoItem === 'mantencion') {
        if (!document.getElementById('prod-proveedor').value.trim()) {
            errDiv.textContent = 'Debes indicar el proveedor de la mantención.';
            errDiv.classList.remove('hidden');
            return;
        }
    } else if (!editandoProdId && tipoItem === 'arriendo') {
        var condicionArriendo = document.getElementById('prod-arr-condicion').value;
        var requeridos = [
            ['prod-arr-proveedor', 'Debes indicar el proveedor del arriendo.'],
            ['prod-arr-fecha-inicio', 'Debes indicar la fecha de inicio.'],
            ['prod-arr-monto-periodo', 'Debes indicar el monto del período.'],
            ['prod-arr-monto-total', 'Debes indicar el monto total estimado.'],
            ['prod-arr-documento', 'Debes indicar el documento de referencia.'],
        ];
        if (condicionArriendo === 'con_fecha') {
            requeridos.push(['prod-arr-fecha-termino', 'Debes indicar la fecha de término.']);
        }
        for (var i = 0; i < requeridos.length; i++) {
            if (!document.getElementById(requeridos[i][0]).value.trim()) {
                errDiv.textContent = requeridos[i][1];
                errDiv.classList.remove('hidden');
                return;
            }
        }
    }

    var btn = document.getElementById('btn-guardar-prod');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        var url    = editandoProdId ? ROUTE_PROD_UPDATE(editandoProdId) : ROUTE_PROD_STORE;
        var method = editandoProdId ? 'PUT' : 'POST';
        var body   = new URLSearchParams({ _token: CSRF, stock_minimo, stock_critico });
        if (!editandoProdId) {
            body.append('categoria_id', prodCatId);
            body.append('nombre', nombreProd.toUpperCase());
            body.append('tipo_item', tipoItem);
            if (tipoItem === 'servicio') {
                body.append('es_servicio', '1');
            } else if (esFisico) {
                if (marcaActualId) body.append('marca_id', marcaActualId);
                body.append('contenedor', contenedorId);
                body.append('unidad_medida_id', unidadId);
            }
        }
        // Unidad de medida también al editar
        if (editandoProdId) {
            body.append('tipo_item', tipoItem);
        }
        if (editandoProdId && esFisico && unidadId) {
            body.append('unidad_medida_id', unidadId);
        }
        // Presentación (solo para productos físicos)
        if (esFisico) {
            var manejaPres = document.getElementById('prod-maneja-pres')?.checked;
            body.append('maneja_presentacion', manejaPres ? '1' : '0');
            if (manejaPres) {
                body.append('tipo_presentacion',    document.getElementById('prod-tipo-pres')?.value || '');
                body.append('cantidad_presentacion', document.getElementById('prod-cant-pres')?.value || '');
                body.append('unidad_base',           getUnidadBaseFromSelected());
            }
        }
        if (!editandoProdId && (tipoItem === 'servicio' || tipoItem === 'mantencion')) {
            body.append('proveedor_nombre', document.getElementById('prod-proveedor').value.trim());
            body.append('estado_operacional', document.getElementById('prod-estado-operacional').value);
            body.append('fecha_ejecucion', document.getElementById('prod-fecha-ejecucion').value);
            body.append('documento_referencia', document.getElementById('prod-documento-referencia').value.trim());
            body.append('observacion', document.getElementById('prod-observacion').value.trim());
        }
        if (!editandoProdId && tipoItem === 'arriendo') {
            body.append('proveedor_nombre', document.getElementById('prod-arr-proveedor').value.trim());
            body.append('fecha_inicio', document.getElementById('prod-arr-fecha-inicio').value);
            body.append('condicion_termino', document.getElementById('prod-arr-condicion').value);
            body.append('fecha_termino', document.getElementById('prod-arr-fecha-termino').value);
            body.append('monto_periodo', document.getElementById('prod-arr-monto-periodo').value);
            body.append('monto_total', document.getElementById('prod-arr-monto-total').value);
            body.append('unidad_tiempo', document.getElementById('prod-arr-unidad-tiempo').value);
            body.append('documento_referencia', document.getElementById('prod-arr-documento').value.trim());
            body.append('observacion', document.getElementById('prod-arr-observacion').value.trim());
        }
        var res  = await fetch(url, { method, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        var json = await res.json();
        if (!res.ok || !json.ok) {
            errDiv.textContent = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Error al guardar.');
            errDiv.classList.remove('hidden');
        } else if (editandoProdId) {
            var cat  = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catActualId; });
            var prod = cat ? cat.productos.find(function(p) { return p.id === editandoProdId; }) : null;
            if (prod) {
                prod.stock_minimo  = parseInt(stock_minimo);
                prod.stock_critico = parseInt(stock_critico);
                prod.tipo_item = tipoItem;
                prod.es_servicio = tipoItem === 'servicio';
                if (esFisico) {
                    var manejaPres = document.getElementById('prod-maneja-pres')?.checked;
                    prod.maneja_presentacion   = manejaPres;
                    prod.tipo_presentacion     = manejaPres ? (document.getElementById('prod-tipo-pres')?.value || null) : null;
                    prod.cantidad_presentacion = manejaPres ? (parseInt(document.getElementById('prod-cant-pres')?.value) || null) : null;
                    prod.unidad_base           = manejaPres ? getUnidadBaseFromSelected() : null;
                }
            }
            var prods = productosPorTipo(cat ? cat.productos : []);
            if (catalogoTipoActual === 'producto' && marcaActualId > 0) prods = prods.filter(function(p) { return p.marca_id === marcaActualId; });
            else if (catalogoTipoActual === 'producto' && marcaActualId === 0) prods = prods.filter(function(p) { return !p.marca_id; });
            renderProductos(prods);
            if (cat && marcasAplicanCatalogo()) renderMarcas(getCategoriaFiltrada(cat));
            renderCategoriasCatalogo();
            contarTipoCatalogo();
            cerrarModalProducto();
        } else {
            var familia = catalogoData.find(function(f) { return f.categorias.some(function(c) { return c.id === prodCatId; }); });
            var cat     = familia ? familia.categorias.find(function(c) { return c.id === prodCatId; }) : null;
            if (cat) {
                var marcaSel = esFisico ? marcasData.find(function(m) { return m.id === marcaActualId; }) : null;
                cat.productos.push({
                    id: json.id, nombre: json.nombre,
                    stock_actual: 0, stock_minimo: parseInt(stock_minimo), stock_critico: parseInt(stock_critico),
                    contenedor_id: null,
                    marca_id: esFisico ? (marcaActualId || null) : null,
                    marca_nombre: marcaSel ? marcaSel.nombre : null,
                    es_servicio: tipoItem === 'servicio',
                    tipo_item: tipoItem,
                });
                if (catActualId === prodCatId) {
                    var prodsVis = productosPorTipo(cat.productos);
                    document.getElementById('subtitulo-categoria').textContent = prodsVis.length + ' ' + labelTipoCatalogo(true);
                    if (catalogoTipoActual === 'producto' && marcaActualId > 0) prodsVis = prodsVis.filter(function(p) { return p.marca_id === marcaActualId; });
                    else if (catalogoTipoActual === 'producto' && marcaActualId === 0) prodsVis = prodsVis.filter(function(p) { return !p.marca_id; });
                    renderProductos(prodsVis);
                    if (marcasAplicanCatalogo()) renderMarcas(getCategoriaFiltrada(cat));
                }
                var spanCont = document.querySelector('#cat-btn-' + prodCatId + ' span.text-xs');
                if (spanCont) spanCont.textContent = productosPorTipo(cat.productos).length;
                contarTipoCatalogo();
                renderCategoriasCatalogo();
            }
            cerrarModalProducto();
        }
    } catch (e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
    finally {
        btn.disabled = false;
        btn.textContent = IS_FAMILIA_SERVICIOS ? 'Guardar' : 'Guardar';
    }
}

// Auto-select first category on load
window.addEventListener('DOMContentLoaded', function() {
    contarTipoCatalogo();
    aplicarEstadoTipoCatalogo();
    actualizarFamiliaTabs();
    renderCategoriasCatalogo();
    const primerBtn = document.querySelector('.cat-item');
    if (primerBtn) seleccionarCategoria(parseInt(primerBtn.dataset.catId), primerBtn.querySelector('.cat-nombre').textContent.trim());
});

// ── Modal de aviso (reemplaza alert() nativo) ─────────────────────────────
function showAviso(mensaje, tipo) {
    const modal   = document.getElementById('aviso-modal');
    const icon    = document.getElementById('aviso-icon');
    const texto   = document.getElementById('aviso-texto');
    const btnOk   = document.getElementById('aviso-ok');

    const cfg = {
        warn:  { bg: '#fef3c7', border: '#f59e0b', iconBg: '#fde68a', iconColor: '#b45309', stroke: 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z', btnBg: '#f59e0b', btnHover: '#d97706' },
        error: { bg: '#fee2e2', border: '#ef4444', iconBg: '#fecaca', iconColor: '#b91c1c', stroke: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', btnBg: '#ef4444', btnHover: '#dc2626' },
        info:  { bg: '#eff6ff', border: '#3b82f6', iconBg: '#dbeafe', iconColor: '#1d4ed8', stroke: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', btnBg: '#3b82f6', btnHover: '#2563eb' },
    };
    const c = cfg[tipo] || cfg.warn;

    const isDark = document.documentElement.classList.contains('dark');
    const innerBg     = isDark ? '#1e293b' : '#fff';
    const textColor   = isDark ? '#e2e8f0' : '#1f2937';

    modal.querySelector('.aviso-inner').style.background = innerBg;
    modal.querySelector('.aviso-icon-wrap').style.background = c.iconBg;
    modal.querySelector('.aviso-icon-wrap').style.border = '2px solid ' + c.border;
    icon.setAttribute('stroke', c.iconColor);
    icon.querySelector('path').setAttribute('d', c.stroke);
    texto.textContent = mensaje;
    texto.style.color = textColor;
    btnOk.style.background = c.btnBg;
    btnOk.onmouseover = () => btnOk.style.background = c.btnHover;
    btnOk.onmouseout  = () => btnOk.style.background = c.btnBg;

    modal.style.display = 'flex';
    modal.querySelector('.aviso-inner').style.animation = 'aviso-in .2s cubic-bezier(.22,.68,0,1.2) both';
}
function cerrarAviso() {
    document.getElementById('aviso-modal').style.display = 'none';
}

// ══════════════════════════════════════════════════════════════════════════
// Carga Masiva de Productos — Modal Logic
// ══════════════════════════════════════════════════════════════════════════

function _cmpIsDark() { return document.documentElement.classList.contains('dark'); }

function _cmpApplyTheme() {
    var dm = _cmpIsDark();
    var inner   = document.getElementById('cmp-inner');
    var header  = document.getElementById('cmp-header');
    var footer  = document.getElementById('cmp-footer');
    if (!inner) return;

    inner.style.background  = dm ? '#1e293b' : '#ffffff';
    inner.style.color       = dm ? '#e2e8f0' : '#1f2937';
    header.style.borderColor = dm ? '#334155' : '#f3f4f6';
    footer.style.borderColor = dm ? '#334155' : '#f3f4f6';

    document.querySelectorAll('.cmp-label').forEach(function(el) {
        el.style.color = dm ? '#cbd5e1' : '#374151';
    });
    document.querySelectorAll('.cmp-input').forEach(function(el) {
        el.style.background   = dm ? '#0f172a' : '#ffffff';
        el.style.color        = dm ? '#e2e8f0' : '#111827';
        el.style.borderColor  = dm ? '#475569' : '#d1d5db';
    });
    document.querySelectorAll('.cmp-btn-cancel').forEach(function(el) {
        el.style.color       = dm ? '#cbd5e1' : '#374151';
        el.style.borderColor = dm ? '#475569' : '#d1d5db';
    });
    var lbl = document.getElementById('cmp-excel-lbl');
    if (lbl) {
        lbl.style.borderColor = dm ? '#475569' : '#d1d5db';
        lbl.style.color       = dm ? '#94a3b8' : '#6b7280';
    }
    var catBadge = document.getElementById('cmp-cat-nombre')?.parentElement;
    if (catBadge) {
        catBadge.style.background   = dm ? '#1e3a5f' : '#eff6ff';
        catBadge.style.borderColor  = dm ? '#2563eb' : '#bfdbfe';
        catBadge.style.color        = dm ? '#93c5fd' : '#1d4ed8';
    }
}

function abrirModalCargaMasivaProductos() {
    if (!catActualId) {
        var primerBtn = document.querySelector('.cat-item');
        if (primerBtn) seleccionarCategoria(parseInt(primerBtn.dataset.catId), primerBtn.querySelector('.cat-nombre').textContent.trim());
    }
    var modal = document.getElementById('modal-carga-masiva-productos');
    document.getElementById('cmp-cat-nombre').textContent = catActualNombre || '—';
    document.getElementById('cmp-contenedor-sel').value = '';
    document.getElementById('cmp-excel-input').value = '';
    document.getElementById('cmp-excel-txt').textContent = 'Seleccionar Excel (.xlsx, .xls, .csv)';
    document.getElementById('cmp-excel-ok').style.display = 'none';
    document.getElementById('cmp-error').style.display = 'none';
    document.getElementById('cmp-btn-importar').disabled = false;
    document.getElementById('cmp-btn-importar').textContent = 'Revisar productos';
    document.getElementById('cmp-btn-importar').onclick = cmpImportar;
    modal.style.display = 'flex';
    _cmpApplyTheme();
}

function cerrarModalCargaMasivaProductos() {
    document.getElementById('modal-carga-masiva-productos').style.display = 'none';
}

function cmpOnExcelChange(input) {
    var txt = document.getElementById('cmp-excel-txt');
    var ok  = document.getElementById('cmp-excel-ok');
    var lbl = document.getElementById('cmp-excel-lbl');
    if (input.files.length > 0) {
        txt.textContent = input.files[0].name;
        ok.style.display = 'block';
        lbl.style.borderColor = '#22c55e';
    } else {
        txt.textContent = 'Seleccionar Excel (.xlsx, .xls, .csv)';
        ok.style.display = 'none';
        lbl.style.borderColor = _cmpIsDark() ? '#475569' : '#d1d5db';
    }
}

async function cmpImportar() {
    var errDiv = document.getElementById('cmp-error');
    errDiv.style.display = 'none';

    var excelFile = document.getElementById('cmp-excel-input').files[0];
    if (!excelFile) { errDiv.textContent = 'Adjunta un archivo Excel antes de continuar.'; errDiv.style.display = 'block'; return; }

    var btn = document.getElementById('cmp-btn-importar');
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;">'
        + '<svg style="width:13px;height:13px;animation:ai-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>'
        + ' Leyendo Excel...</span>';

    var fd = new FormData();
    fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    fd.append('excel_catalogo', excelFile);

    try {
        var resp = await fetch('{{ route("admin.catalogo.carga-masiva.preview") }}', { method: 'POST', body: fd });
        var json = await resp.json();

        if (!json.ok) {
            errDiv.textContent = json.error || json.message || 'Error al leer el Excel.';
            errDiv.style.display = 'block';
        } else {
            var cont = document.getElementById('cmp-contenedor-sel').value || null;
            cerrarModalCargaMasivaProductos();
            rcmAbrir(json.rows, cont);
            return;
        }
    } catch (e) {
        errDiv.textContent = 'Error de conexión al servidor.';
        errDiv.style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = 'Revisar productos';
}

// ══════════════════════════════════════════════════════════════════════════
// Modal de Revisión — Carga Masiva de Productos
// ══════════════════════════════════════════════════════════════════════════

var rcmRows         = [];
var rcmFamilias     = [];
var rcmContenedorId = null;
var rcmItemData     = []; // per-row wizard results; null = not yet configured

// Wizard state
var _rcmWizardIdx  = null;
var _rcmFamiliaId  = null;
var _rcmCatId      = null;
var _rcmMarcaId    = null;
var _rcmTipoItem   = 'producto';
var _rcmStep       = 1;

var RCM_CSRF        = '';
var RCM_FAM_STORE   = '{{ route("admin.catalogo.familias.store") }}';
var RCM_CAT_STORE   = '{{ route("admin.catalogo.categorias.store") }}';
var RCM_MARCA_STORE = '{{ route("admin.catalogo.marcas.store") }}';
var RCM_CONFIRM_URL = '{{ route("admin.catalogo.carga-masiva.confirmar") }}';

function rcmIsDark() { return document.documentElement.classList.contains('dark'); }

function rcmAbrir(rows, contenedorId) {
    RCM_CSRF = document.querySelector('meta[name="csrf-token"]').content;
    rcmRows = rows;
    rcmContenedorId = contenedorId || null;
    rcmItemData = rows.map(function() { return null; });

    rcmFamilias = [];
    (catalogoData || []).forEach(function(f) {
        if ((f.tipo_item || 'producto') !== 'producto') return;
        rcmFamilias.push({
            id: f.id,
            nombre: f.nombre,
            categorias: (f.categorias || [])
                .filter(function(c) { return (c.tipo_item || 'producto') === 'producto'; })
                .map(function(c) {
                    return {
                        id: c.id,
                        nombre: c.nombre,
                        marcas: (c.marcas || []).filter(function(m) { return m.activo; })
                            .map(function(m) { return { id: m.id, nombre: m.nombre }; })
                    };
                })
        });
    });

    document.getElementById('rcm-subtitulo').textContent =
        rows.length + ' producto(s) — ingresa los datos de cada uno antes de guardar';
    document.getElementById('rcm-err').style.display = 'none';
    document.getElementById('rcm-btn-confirmar').disabled = false;
    document.getElementById('rcm-btn-confirmar').textContent = 'Confirmar y guardar';
    document.getElementById('rcm-btn-confirmar').onclick = rcmConfirmar;
    var volverBtn = document.getElementById('rcm-btn-volver');
    if (volverBtn) volverBtn.style.display = '';

    rcmRenderCards();
    document.getElementById('modal-review-carga').style.display = 'flex';
    rcmApplyTheme();
}

function rcmCerrar() {
    document.getElementById('modal-review-carga').style.display = 'none';
}

function rcmVolver() {
    rcmCerrar();
    document.getElementById('modal-carga-masiva-productos').style.display = 'flex';
    _cmpApplyTheme();
}

function rcmApplyTheme() {
    var dm = rcmIsDark();
    var inner = document.getElementById('rcm-inner');
    if (!inner) return;
    inner.style.background = dm ? '#1e293b' : '#ffffff';
    var hdr = document.getElementById('rcm-header');
    var ftr = document.getElementById('rcm-footer');
    if (hdr) { hdr.style.borderColor = dm ? '#334155' : '#e5e7eb'; hdr.style.background = dm ? '#1e293b' : '#fff'; }
    if (ftr) { ftr.style.borderColor = dm ? '#334155' : '#e5e7eb'; ftr.style.background = dm ? '#1e293b' : '#fff'; }
    var titulo = document.getElementById('rcm-titulo');
    if (titulo) titulo.style.color = dm ? '#e2e8f0' : '#1f2937';
    var subtitulo = document.getElementById('rcm-subtitulo');
    if (subtitulo) subtitulo.style.color = dm ? '#94a3b8' : '#6b7280';
    var volverBtn = document.getElementById('rcm-btn-volver');
    if (volverBtn) { volverBtn.style.borderColor = dm ? '#475569' : '#e5e7eb'; volverBtn.style.color = dm ? '#cbd5e1' : '#374151'; volverBtn.style.background = 'transparent'; }
    var body = document.getElementById('rcm-body');
    if (body) body.style.background = dm ? '#0f172a' : '#f9fafb';
}

// ── Card rendering ───────────────────────────────────────────────────────────

function rcmBuscarCat(catId) {
    var found = null;
    rcmFamilias.forEach(function(f) {
        if (!found) { found = f.categorias.find(function(c) { return c.id === catId; }) || null; }
    });
    return found;
}

function rcmRenderCards() {
    var dm = rcmIsDark();
    var container = document.getElementById('rcm-rows-container');
    if (!container) return;
    var html = '';
    rcmRows.forEach(function(row, idx) { html += rcmCardHtml(row, idx, dm); });
    container.innerHTML = html;
}

function rcmCardHtml(row, idx, dm) {
    var cardBg      = dm ? '#1e293b' : '#ffffff';
    var cardBorder  = dm ? '#334155' : '#e5e7eb';
    var labelColor  = dm ? '#94a3b8' : '#6b7280';
    var textColor   = dm ? '#e2e8f0' : '#111827';
    var tagBg       = dm ? '#312e81' : '#e0e7ff';
    var tagColor    = dm ? '#a5b4fc' : '#4338ca';
    var optBg       = dm ? '#0f172a' : '#f9fafb';
    var optBorder   = dm ? '#334155' : '#e5e7eb';
    var emTitleColor = dm ? '#6ee7b7' : '#065f46';

    return '<div id="rcm-card-' + idx + '" style="background:' + cardBg + '; border:1px solid ' + cardBorder + '; border-radius:0.75rem; padding:1rem 1.25rem; margin-bottom:0.875rem;">'
        + '<div style="display:flex; align-items:flex-start; gap:0.75rem; margin-bottom:0.65rem;">'
        +   '<span style="font-size:0.7rem; font-weight:700; background:' + tagBg + '; color:' + tagColor + '; padding:0.15rem 0.55rem; border-radius:9999px; white-space:nowrap; flex-shrink:0; margin-top:0.2rem;">Fila ' + row.fila + '</span>'
        +   '<div style="flex:1; min-width:0;">'
        +     '<p style="font-size:0.9rem; font-weight:700; color:' + textColor + '; margin:0 0 0.15rem; word-break:break-word;">' + escHtml(row.descripcion) + '</p>'
        +     '<p style="font-size:0.75rem; color:' + labelColor + '; margin:0;">'
        +       escHtml(row.unidad) + ' &middot; cant: <b style="color:' + textColor + ';">' + row.cantidad + '</b> &middot; mín: ' + row.minimo + ' &middot; crít: ' + row.critico
        +     '</p>'
        +   '</div>'
        + '</div>'
        + '<div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem; background:' + optBg + '; border:1px solid ' + optBorder + '; border-radius:0.5rem;">'
        +   '<div style="flex:1; min-width:0;">'
        +     '<p style="font-size:0.85rem; font-weight:600; color:' + emTitleColor + '; margin:0 0 0.4rem;">Crear como nuevo producto</p>'
        +     '<div id="rcm-card-action-' + idx + '" style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">'
        +       '<button type="button" id="rcm-btn-ingresar-' + idx + '" onclick="rcmAbrirWizard(' + idx + ')"'
        +       ' style="display:inline-flex; align-items:center; gap:0.35rem; font-size:0.78rem; font-weight:600; color:#fff; background:#2563eb; border:none; border-radius:0.375rem; padding:0.35rem 0.85rem; cursor:pointer;">'
        +         '<svg style="width:0.85rem;height:0.85rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
        +         '+ Ingresar datos'
        +       '</button>'
        +       '<span id="rcm-resumen-' + idx + '" style="display:none; font-size:0.72rem; font-weight:600;'
        +         ' background:' + (dm ? '#052e16' : '#ecfdf5') + '; color:' + (dm ? '#86efac' : '#065f46') + ';'
        +         ' border:1px solid ' + (dm ? '#166534' : '#a7f3d0') + '; border-radius:0.375rem; padding:0.2rem 0.55rem;"></span>'
        +     '</div>'
        +   '</div>'
        + '</div>'
        + '</div>';
}

function rcmUpdateCardDone(idx) {
    var data = rcmItemData[idx];
    if (!data) return;
    var dm = rcmIsDark();
    var cat   = rcmBuscarCat(data.categoria_id);
    var marca = (cat && cat.marcas && data.marca_id) ? cat.marcas.find(function(m) { return m.id === data.marca_id; }) : null;
    var resumenEl = document.getElementById('rcm-resumen-' + idx);
    var btnEl     = document.getElementById('rcm-btn-ingresar-' + idx);
    var card      = document.getElementById('rcm-card-' + idx);
    if (resumenEl) {
        resumenEl.textContent = '✓ ' + (cat ? cat.nombre : 'Sin cat.') + (marca ? ' · ' + marca.nombre : ' · sin marca')
            + ' · ' + (data.nombre || '');
        resumenEl.style.display = '';
    }
    if (btnEl) {
        btnEl.innerHTML = '<svg style="width:0.8rem;height:0.8rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Editar';
        btnEl.style.background = '#6b7280';
    }
    if (card) card.style.borderColor = dm ? '#166534' : '#86efac';
}

// ── Wizard ───────────────────────────────────────────────────────────────────

function rcmAbrirWizard(idx) {
    _rcmWizardIdx = idx;
    var row  = rcmRows[idx];
    var data = rcmItemData[idx] || {};
    _rcmFamiliaId = data.familia_id  || null;
    _rcmCatId     = data.categoria_id || null;
    _rcmMarcaId   = data.marca_id    || null;
    _rcmTipoItem  = data.tipo_item   || 'producto';

    document.getElementById('rcm-wizard-desc').textContent = row.descripcion || '';
    document.getElementById('rcm-wizard-error').style.display = 'none';
    document.getElementById('rcm-modal-nombre-edit').value   = data.nombre || row.descripcion || '';
    document.getElementById('rcm-modal-minimo').value        = data.minimo  !== undefined ? data.minimo  : (row.minimo  || 0);
    document.getElementById('rcm-modal-critico').value       = data.critico !== undefined ? data.critico : (row.critico || 0);
    document.getElementById('rcm-modal-contenedor').value    = data.contenedor_id || '';

    var pkgActivo = data.maneja_presentacion || false;
    document.getElementById('rcm-modal-pkg-toggle').checked  = pkgActivo;
    document.getElementById('rcm-modal-pkg-tipo').value      = data.tipo_presentacion || '';
    document.getElementById('rcm-modal-pkg-cant').value      = data.cantidad_presentacion || '';
    document.getElementById('rcm-modal-pkg-base').value      = data.unidad_base || '';
    document.getElementById('rcm-modal-pkg-fields').style.display = pkgActivo ? 'block' : 'none';
    rcmActualizarPkgPreview();

    document.getElementById('rcm-wizard-modal').style.display = 'flex';
    rcmWizardGoStep(1);
}

function rcmCerrarWizard() {
    document.getElementById('rcm-wizard-modal').style.display = 'none';
}

function rcmWizardGoStep(n) {
    _rcmStep = n;
    var dark = rcmIsDark();
    [1,2,3,4].forEach(function(i) {
        document.getElementById('rcm-wstep-' + i).style.display = i === n ? '' : 'none';
        var circle = document.getElementById('rcm-wcircle-' + i);
        var label  = document.getElementById('rcm-wlabel-'  + i);
        var line   = document.getElementById('rcm-wline-'   + i);
        if (i < n) {
            circle.style.background = '#a5b4fc'; circle.style.color = '#3730a3';
            if (label) { label.style.color = '#9ca3af'; label.style.fontWeight = '500'; }
            if (line)  line.style.background = '#a5b4fc';
        } else if (i === n) {
            circle.style.background = '#7c3aed'; circle.style.color = '#fff';
            if (label) { label.style.color = '#7c3aed'; label.style.fontWeight = '700'; }
            if (line)  line.style.background = dark ? '#334155' : '#e5e7eb';
        } else {
            circle.style.background = dark ? '#1e293b' : '#e0e7ff';
            circle.style.color      = dark ? '#64748b' : '#4338ca';
            if (label) { label.style.color = '#9ca3af'; label.style.fontWeight = '500'; }
            if (line)  line.style.background = dark ? '#334155' : '#e5e7eb';
        }
    });
    document.getElementById('rcm-wbtn-atras').style.display     = n > 1 ? '' : 'none';
    document.getElementById('rcm-wbtn-siguiente').style.display = n < 4 ? '' : 'none';
    document.getElementById('rcm-wbtn-confirmar').style.display = n === 4 ? '' : 'none';
    [1,2,3].forEach(function(i) {
        var e = document.getElementById('rcm-wstep' + i + '-error');
        if (e) e.style.display = 'none';
    });
    document.getElementById('rcm-wizard-error').style.display = 'none';
    if (n === 1) { rcmWizardSeleccionarTipo(_rcmTipoItem); rcmWizardRenderFamilias(); }
    if (n === 2) rcmWizardRenderCategorias();
    if (n === 3) rcmWizardRenderMarcas(rcmBuscarCat(_rcmCatId));
    if (n === 4) rcmToggleCamposPorTipo();
}

function rcmWizardSiguiente() {
    if (_rcmStep === 2 && !_rcmCatId) {
        var e2 = document.getElementById('rcm-wstep2-error');
        e2.textContent = 'Selecciona una categoría para continuar.';
        e2.style.display = 'block'; return;
    }
    rcmWizardGoStep(_rcmStep + 1);
}

function rcmWizardAtras() { rcmWizardGoStep(_rcmStep - 1); }

function rcmWizardSeleccionarTipo(tipo) {
    _rcmTipoItem = ['producto','servicio','mantencion','arriendo'].indexOf(tipo) >= 0 ? tipo : 'producto';
    document.querySelectorAll('[data-rcm-tipo]').forEach(function(btn) {
        btn.classList.toggle('rw-tile-sel', btn.dataset.rcmTipo === _rcmTipoItem);
    });
    rcmToggleCamposPorTipo();
}

function rcmToggleCamposPorTipo() {
    var fisico = _rcmTipoItem === 'producto';
    var wrapCont  = document.getElementById('rcm-step4-cont-wrap');
    var wrapPkg   = document.getElementById('rcm-step4-pkg-wrap');
    var wrapStock = document.getElementById('rcm-step4-stock-wrap');
    if (wrapCont)  wrapCont.style.display  = fisico ? '' : 'none';
    if (wrapPkg)   wrapPkg.style.display   = fisico ? '' : 'none';
    if (wrapStock) wrapStock.style.display = fisico ? '' : 'none';
    if (!fisico) {
        document.getElementById('rcm-modal-contenedor').value = '';
        document.getElementById('rcm-modal-minimo').value = '0';
        document.getElementById('rcm-modal-critico').value = '0';
        document.getElementById('rcm-modal-pkg-toggle').checked = false;
        rcmTogglePkg();
    }
}

function rcmWizardRenderFamilias() {
    var cont = document.getElementById('rcm-wizard-familias');
    cont.innerHTML = '';
    rcmFamilias.forEach(function(f) {
        var btn = document.createElement('button');
        btn.type = 'button'; btn.textContent = f.nombre;
        btn.className = 'rw-tile' + (f.id === _rcmFamiliaId ? ' rw-tile-sel' : '');
        btn.onclick = function() {
            _rcmFamiliaId = (_rcmFamiliaId === f.id) ? null : f.id;
            _rcmCatId = null; _rcmMarcaId = null;
            rcmWizardRenderFamilias();
        };
        cont.appendChild(btn);
    });
}

function rcmWizardRenderCategorias() {
    var cont = document.getElementById('rcm-wizard-categorias');
    cont.innerHTML = '';
    var cats = [];
    if (_rcmFamiliaId) {
        var fam = rcmFamilias.find(function(f) { return f.id === _rcmFamiliaId; });
        if (fam) cats = fam.categorias;
    } else {
        rcmFamilias.forEach(function(f) { f.categorias.forEach(function(c) { cats.push(c); }); });
    }
    cats.forEach(function(c) {
        var btn = document.createElement('button');
        btn.type = 'button'; btn.textContent = c.nombre;
        btn.className = 'rw-tile' + (c.id === _rcmCatId ? ' rw-tile-sel' : '');
        btn.onclick = function() { _rcmCatId = c.id; _rcmMarcaId = null; rcmWizardRenderCategorias(); };
        cont.appendChild(btn);
    });
}

function rcmWizardRenderMarcas(cat) {
    var cont = document.getElementById('rcm-wizard-marcas');
    cont.innerHTML = '';
    var btnSin = document.createElement('button');
    btnSin.type = 'button'; btnSin.textContent = 'Sin marca';
    btnSin.className = 'rw-tile-sin' + (_rcmMarcaId === null ? ' rw-tile-sel' : '');
    btnSin.onclick = function() { _rcmMarcaId = null; rcmWizardRenderMarcas(cat); };
    cont.appendChild(btnSin);
    if (cat && cat.marcas) {
        cat.marcas.forEach(function(m) {
            var btn = document.createElement('button');
            btn.type = 'button'; btn.textContent = m.nombre;
            btn.className = 'rw-tile' + (m.id === _rcmMarcaId ? ' rw-tile-sel' : '');
            btn.onclick = function() { _rcmMarcaId = m.id; rcmWizardRenderMarcas(cat); };
            cont.appendChild(btn);
        });
    }
}

function rcmActualizarPkgPreview() {
    var tipo = (document.getElementById('rcm-modal-pkg-tipo').value || '').trim();
    var cant = parseInt(document.getElementById('rcm-modal-pkg-cant').value) || 0;
    var base = (document.getElementById('rcm-modal-pkg-base').value || '').trim();
    var prev = document.getElementById('rcm-modal-pkg-preview');
    if (prev) prev.textContent = (tipo && cant >= 1 && base)
        ? '1 ' + tipo + ' = ' + cant + ' ' + base + (cant !== 1 && base.slice(-1).toLowerCase() !== 's' ? 's' : '')
        : '';
}

function rcmTogglePkg() {
    var activo = document.getElementById('rcm-modal-pkg-toggle').checked;
    document.getElementById('rcm-modal-pkg-fields').style.display = activo ? 'block' : 'none';
    if (!activo) {
        var prev = document.getElementById('rcm-modal-pkg-preview');
        if (prev) prev.textContent = '';
    } else {
        rcmActualizarPkgPreview();
        document.getElementById('rcm-modal-pkg-tipo').focus();
    }
}

function rcmConfirmarWizard() {
    var errDiv = document.getElementById('rcm-wizard-error');
    errDiv.style.display = 'none';
    if (!_rcmCatId) { errDiv.textContent = 'Selecciona una categoría.'; errDiv.style.display = 'block'; return; }

    var fisico    = _rcmTipoItem === 'producto';
    var pkgActivo = fisico && document.getElementById('rcm-modal-pkg-toggle').checked;
    var pkgTipo   = (document.getElementById('rcm-modal-pkg-tipo').value || '').trim();
    var pkgCant   = parseInt(document.getElementById('rcm-modal-pkg-cant').value) || 0;
    var pkgBase   = (document.getElementById('rcm-modal-pkg-base').value || '').trim();
    if (pkgActivo && (!pkgTipo || pkgCant < 1 || !pkgBase)) {
        errDiv.textContent = 'Completa tipo, cantidad (≥ 1) y unidad base del paquete.';
        errDiv.style.display = 'block'; return;
    }

    var nombre = (document.getElementById('rcm-modal-nombre-edit').value || '').trim().toUpperCase();
    if (!nombre) { errDiv.textContent = 'El nombre no puede estar vacío.'; errDiv.style.display = 'block'; return; }
    var contId = document.getElementById('rcm-modal-contenedor').value || null;
    if (fisico && !contId) { errDiv.textContent = 'Selecciona un contenedor.'; errDiv.style.display = 'block'; return; }
    var min  = parseInt(document.getElementById('rcm-modal-minimo').value)  || 0;
    var crit = parseInt(document.getElementById('rcm-modal-critico').value) || 0;

    rcmItemData[_rcmWizardIdx] = {
        nombre:               nombre,
        tipo_item:            _rcmTipoItem,
        familia_id:           _rcmFamiliaId,
        categoria_id:         _rcmCatId,
        marca_id:             _rcmMarcaId,
        contenedor_id:        contId ? parseInt(contId) : null,
        minimo:               min,
        critico:              crit,
        maneja_presentacion:  pkgActivo,
        tipo_presentacion:    pkgActivo ? pkgTipo : '',
        cantidad_presentacion: pkgActivo ? pkgCant : null,
        unidad_base:          pkgActivo ? pkgBase : '',
    };
    rcmCerrarWizard();
    rcmUpdateCardDone(_rcmWizardIdx);
}

// ── Create familia/categoría/marca from wizard ───────────────────────────────

function rcmToggleNuevaFam() {
    var form = document.getElementById('rcm-nueva-fam-form');
    var show = form.style.display === 'none';
    form.style.display = show ? 'block' : 'none';
    if (show) setTimeout(function() { document.getElementById('rcm-nueva-fam-input').focus(); }, 50);
}

function rcmToggleNuevaCat() {
    var form = document.getElementById('rcm-nueva-cat-form');
    var show = form.style.display === 'none';
    form.style.display = show ? 'block' : 'none';
    if (show) setTimeout(function() { document.getElementById('rcm-nueva-cat-input').focus(); }, 50);
}

function rcmToggleNuevaMarca() {
    var form = document.getElementById('rcm-nueva-marca-form');
    var show = form.style.display === 'none';
    form.style.display = show ? 'block' : 'none';
    if (show) setTimeout(function() { document.getElementById('rcm-nueva-marca-input').focus(); }, 50);
}

function rcmCrearFamilia() {
    var nombre = document.getElementById('rcm-nueva-fam-input').value.trim();
    if (!nombre) return;
    fetch(RCM_FAM_STORE, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': RCM_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ nombre: nombre, tipo_item: 'producto' }),
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok || data.id) {
            rcmFamilias.push({ id: data.id, nombre: data.nombre || nombre, categorias: [] });
            _rcmFamiliaId = data.id; _rcmCatId = null;
            rcmWizardRenderFamilias();
            document.getElementById('rcm-nueva-fam-input').value = '';
            document.getElementById('rcm-nueva-fam-form').style.display = 'none';
        }
    }).catch(function() {});
}

function rcmCrearCategoria() {
    var nombre = document.getElementById('rcm-nueva-cat-input').value.trim();
    if (!nombre) return;
    if (!_rcmFamiliaId) {
        var errDiv = document.getElementById('rcm-wizard-error');
        errDiv.textContent = 'Para crear una categoría, selecciona una familia primero.';
        errDiv.style.display = 'block'; return;
    }
    fetch(RCM_CAT_STORE, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': RCM_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ nombre: nombre, familia_id: _rcmFamiliaId }),
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok || data.id) {
            var fam = rcmFamilias.find(function(f) { return f.id === _rcmFamiliaId; });
            if (fam) fam.categorias.push({ id: data.id, nombre: data.nombre || nombre, marcas: [] });
            _rcmCatId = data.id; _rcmMarcaId = null;
            document.getElementById('rcm-nueva-cat-input').value = '';
            document.getElementById('rcm-nueva-cat-form').style.display = 'none';
            rcmWizardRenderCategorias();
        }
    }).catch(function() {});
}

function rcmCrearMarca() {
    var nombre = document.getElementById('rcm-nueva-marca-input').value.trim();
    if (!nombre || !_rcmCatId) return;
    fetch(RCM_MARCA_STORE, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': RCM_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ nombre: nombre, categoria_id: _rcmCatId }),
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.ok || data.id) {
            var cat = rcmBuscarCat(_rcmCatId);
            if (cat) { if (!cat.marcas) cat.marcas = []; cat.marcas.push({ id: data.id, nombre: data.nombre || nombre.toUpperCase() }); }
            _rcmMarcaId = data.id;
            rcmWizardRenderMarcas(cat);
            document.getElementById('rcm-nueva-marca-input').value = '';
            document.getElementById('rcm-nueva-marca-form').style.display = 'none';
        }
    }).catch(function() {});
}

// ── Main confirm ─────────────────────────────────────────────────────────────

async function rcmConfirmar() {
    var errDiv = document.getElementById('rcm-err');
    errDiv.style.display = 'none';
    var items = [];

    for (var i = 0; i < rcmRows.length; i++) {
        var row  = rcmRows[i];
        var data = rcmItemData[i];
        if (!data || !data.categoria_id) {
            errDiv.textContent = 'Configura el producto de la fila ' + row.fila + ' antes de confirmar.';
            errDiv.style.display = 'block';
            var card = document.getElementById('rcm-card-' + i);
            if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        items.push({
            fila:                  row.fila,
            nombre:                data.nombre,
            unidad:                row.unidad,
            cantidad:              row.cantidad,
            minimo:                data.minimo,
            critico:               data.critico,
            categoria_id:          data.categoria_id,
            marca_id:              data.marca_id || null,
            contenedor_id:         data.contenedor_id || null,
            tipo_item:             data.tipo_item || 'producto',
            maneja_presentacion:   data.maneja_presentacion || false,
            tipo_presentacion:     data.tipo_presentacion || '',
            cantidad_presentacion: data.cantidad_presentacion || null,
            unidad_base:           data.unidad_base || '',
        });
    }

    var btn = document.getElementById('rcm-btn-confirmar');
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;">'
        + '<svg style="width:13px;height:13px;animation:ai-spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>'
        + ' Guardando...</span>';

    try {
        var resp = await fetch(RCM_CONFIRM_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ contenedor_id: rcmContenedorId || null, items: items }),
        });
        var json = await resp.json();
        var dm   = rcmIsDark();

        var resHtml = '<div style="padding:1.25rem; border-radius:0.5rem; background:'
            + (json.creados > 0 ? (dm ? '#052e16' : '#f0fdf4') : (dm ? '#1c1917' : '#f9fafb'))
            + '; border:1px solid ' + (json.creados > 0 ? '#86efac' : '#e5e7eb') + ';">'
            + '<p style="font-size:0.9rem; font-weight:700; color:' + (json.creados > 0 ? '#15803d' : '#6b7280') + '; margin:0 0 0.3rem;">'
            + (json.creados || 0) + ' producto(s) creado(s) correctamente.</p>';
        if ((json.errores || []).length > 0) {
            resHtml += '<ul style="margin:0.3rem 0 0; padding-left:1.2rem; font-size:0.78rem; color:#b91c1c;">';
            json.errores.forEach(function(e) { resHtml += '<li>' + escHtml(e) + '</li>'; });
            resHtml += '</ul>';
        }
        resHtml += '</div>';

        document.getElementById('rcm-rows-container').innerHTML = resHtml;
        document.getElementById('rcm-btn-volver').style.display = 'none';
        btn.disabled = false;
        btn.textContent = 'Cerrar y recargar';
        btn.style.background = json.creados > 0 ? '#16a34a' : '#6b7280';
        btn.onclick = function() { rcmCerrar(); location.reload(); };
    } catch(e) {
        errDiv.textContent = 'Error de conexión al servidor.';
        errDiv.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Confirmar y guardar';
    }
}
</script>
@endpush

{{-- ═══════════════════════════════════════════════════════════════════════
     Modal — Carga Masiva de Productos desde Excel
════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-carga-masiva-productos"
     style="display:none; position:fixed; inset:0; z-index:9990; background:rgba(0,0,0,.55);
            align-items:flex-start; justify-content:center; padding:2rem 1rem; overflow-y:auto;"
     onclick="if(event.target===this) cerrarModalCargaMasivaProductos()">

    <div id="cmp-inner"
         style="border-radius:0.875rem; box-shadow:0 20px 60px rgba(0,0,0,.3); width:600px;
                max-width:calc(100vw - 2rem); overflow:hidden; animation:cmp-in .2s cubic-bezier(.22,.68,0,1.2) both;">

        {{-- Header --}}
        <div id="cmp-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--cmp-border);">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <p style="font-size:1rem; font-weight:700; margin:0;" id="cmp-titulo">Carga masiva de productos</p>
                    <p style="font-size:0.78rem; margin:0.2rem 0 0; color:#6b7280;">Importa productos desde Excel al catálogo</p>
                </div>
                <button onclick="cerrarModalCargaMasivaProductos()"
                        style="background:none; border:none; cursor:pointer; padding:0.25rem; border-radius:0.375rem; line-height:1; color:#9ca3af;">
                    <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div id="cmp-body" style="padding:1.5rem; display:flex; flex-direction:column; gap:1rem;">

            {{-- Categoría activa (contexto) --}}
            <span id="cmp-cat-nombre" style="display:none;"></span>

            {{-- Contenedor --}}
            <div>
                <label style="display:block; font-size:0.78rem; font-weight:600; margin-bottom:0.35rem;" class="cmp-label">
                    Contenedor <span style="font-weight:400; color:#9ca3af;">(opcional)</span>
                </label>
                <select id="cmp-contenedor-sel"
                        style="width:100%; border:1px solid var(--cmp-border); border-radius:0.4rem; padding:0.4rem 0.6rem; font-size:0.83rem; outline:none; box-sizing:border-box;"
                        class="cmp-input">
                    <option value="">Sin contenedor</option>
                    @foreach($containers as $container)
                    <option value="{{ $container->id }}">{{ $container->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Excel upload --}}
            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.4rem;">
                    <label style="font-size:0.78rem; font-weight:600;" class="cmp-label">
                        Archivo Excel <span style="color:#ef4444;">*</span>
                        <span style="font-weight:400; color:#9ca3af; font-size:0.71rem;">(col A = descripcion, B = unidad, C = cantidad, D = minimo, E = critico — fila 1 encabezado)</span>
                    </label>
                    <a href="{{ asset('templates/plantilla_catalogo_productos.xlsx') }}" download
                       style="font-size:0.72rem; font-weight:600; color:#fff; background:#2563eb; text-decoration:none;
                              display:inline-flex; align-items:center; gap:0.3rem; padding:0.25rem 0.65rem; border-radius:0.4rem; white-space:nowrap;">
                        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        Plantilla
                    </a>
                </div>
                <input type="file" id="cmp-excel-input" accept=".xlsx,.xls,.csv" style="display:none;"
                       onchange="cmpOnExcelChange(this)">
                <label for="cmp-excel-input" id="cmp-excel-lbl"
                       style="display:flex; align-items:center; gap:0.6rem; border:2px dashed var(--cmp-border); border-radius:0.5rem;
                              padding:0.6rem 0.9rem; cursor:pointer; font-size:0.8rem; transition:border-color .15s;"
                       class="cmp-label">
                    <svg style="width:1rem;height:1rem;flex-shrink:0; color:#6b7280;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span id="cmp-excel-txt">Seleccionar Excel (.xlsx, .xls, .csv)</span>
                </label>
                <div id="cmp-excel-ok" style="display:none; margin-top:0.35rem; font-size:0.78rem; color:#16a34a; font-weight:600;">
                    ✓ Archivo adjuntado
                </div>
            </div>

            {{-- Error global --}}
            <div id="cmp-error" style="display:none; background:#fee2e2; border:1px solid #fca5a5; border-radius:0.5rem; padding:0.6rem 0.9rem; font-size:0.8rem; color:#991b1b;"></div>

        </div>

        {{-- Footer --}}
        <div id="cmp-footer" style="padding:1rem 1.5rem; border-top:1px solid var(--cmp-border); display:flex; justify-content:flex-end; gap:0.6rem;">
            <button type="button" onclick="cerrarModalCargaMasivaProductos()"
                    style="padding:0.45rem 1rem; font-size:0.85rem; font-weight:600; border-radius:0.5rem; border:1px solid var(--cmp-border); cursor:pointer; background:transparent;"
                    class="cmp-btn-cancel">
                Cancelar
            </button>
            <button type="button" id="cmp-btn-importar" onclick="cmpImportar()"
                    style="padding:0.45rem 1.25rem; font-size:0.85rem; font-weight:600; color:#fff; background:#4f46e5; border:none; border-radius:0.5rem; cursor:pointer;">
                Revisar productos
            </button>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     Modal — Revisión Carga Masiva de Productos (card list)
════════════════════════════════════════════════════════════════════════ --}}
<div id="modal-review-carga"
     style="display:none; position:fixed; inset:0; z-index:9992; background:rgba(0,0,0,.6);
            align-items:flex-start; justify-content:center; padding:2rem 1rem; overflow-y:auto;">

    <div id="rcm-inner"
         style="border-radius:0.875rem; box-shadow:0 24px 64px rgba(0,0,0,.35); width:820px;
                max-width:calc(100vw - 2rem); overflow:hidden; animation:cmp-in .2s cubic-bezier(.22,.68,0,1.2) both;">

        {{-- Header --}}
        <div id="rcm-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; background:#fff;">
            <div>
                <p id="rcm-titulo" style="font-size:1rem; font-weight:700; margin:0; color:#1f2937;">Revisar productos antes de guardar</p>
                <p id="rcm-subtitulo" style="font-size:0.78rem; margin:0.2rem 0 0; color:#6b7280;"></p>
            </div>
            <button onclick="rcmCerrar()"
                    style="background:none; border:none; cursor:pointer; padding:0.25rem; border-radius:0.375rem; line-height:1; color:#9ca3af;">
                <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div id="rcm-body" style="padding:1.25rem 1.5rem; overflow-y:auto; max-height:62vh; background:#f9fafb;">
            <div id="rcm-rows-container"></div>
        </div>

        {{-- Footer --}}
        <div id="rcm-footer" style="padding:1rem 1.5rem; border-top:1px solid #e5e7eb; background:#fff; display:flex; align-items:center; justify-content:space-between; gap:0.6rem; flex-wrap:wrap;">
            <div id="rcm-err" style="display:none; font-size:0.8rem; color:#b91c1c; flex:1;"></div>
            <div style="display:flex; gap:0.6rem; margin-left:auto;">
                <button id="rcm-btn-volver" onclick="rcmVolver()"
                        style="padding:0.45rem 1rem; font-size:0.85rem; font-weight:600; border-radius:0.5rem; border:1px solid #e5e7eb; cursor:pointer; background:transparent; color:#374151;">
                    ← Volver
                </button>
                <button id="rcm-btn-confirmar" onclick="rcmConfirmar()"
                        style="padding:0.45rem 1.4rem; font-size:0.85rem; font-weight:600; color:#fff; background:#4f46e5; border:none; border-radius:0.5rem; cursor:pointer;">
                    Confirmar y guardar
                </button>
            </div>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     Modal — Wizard 4 pasos (nuevo producto desde carga masiva)
════════════════════════════════════════════════════════════════════════ --}}
<div id="rcm-wizard-modal"
     style="display:none; position:fixed; inset:0; z-index:9993; background:rgba(0,0,0,.5);
            align-items:center; justify-content:center; padding:1rem;">
    <div class="cm-modal-box"
         style="border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:500px;
                max-width:calc(100vw - 2rem); max-height:90vh; display:flex; flex-direction:column;
                overflow:hidden; animation:resolverIn .2s cubic-bezier(.22,.68,0,1.2) both;">

        {{-- Header --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.25rem 1.5rem 0; flex-shrink:0;">
            <div>
                <p class="cm-modal-title" style="font-size:1rem; font-weight:700; margin:0;">Nuevo producto</p>
                <p id="rcm-wizard-desc" class="cm-modal-nombre-desc" style="font-size:0.8rem; margin:0.2rem 0 0; font-weight:600; word-break:break-word;"></p>
            </div>
            <button onclick="rcmCerrarWizard()" style="background:none;border:none;cursor:pointer;font-size:1.25rem;line-height:1;padding:0;color:#9ca3af;" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        {{-- Step indicator --}}
        <div style="display:flex; align-items:center; gap:0.5rem; padding:1rem 1.5rem 0; flex-shrink:0;">
            @foreach([1=>'Familia',2=>'Categoría',3=>'Marca',4=>'Producto'] as $sn => $slabel)
            <div style="display:flex; align-items:center; gap:0.5rem; {{ $sn < 4 ? 'flex:1;' : '' }}">
                <div id="rcm-wcircle-{{ $sn }}"
                     style="width:1.5rem; height:1.5rem; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:700; flex-shrink:0; background:#e0e7ff; color:#4338ca;">{{ $sn }}</div>
                <span id="rcm-wlabel-{{ $sn }}" style="font-size:0.72rem; font-weight:500; white-space:nowrap; color:#9ca3af;">{{ $slabel }}</span>
                @if($sn < 4)
                <div id="rcm-wline-{{ $sn }}" style="flex:1; height:1px; background:#e5e7eb; margin:0 0.25rem;"></div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Scrollable content --}}
        <div style="flex:1; overflow-y:auto;">

            {{-- Step 1: Tipo item + Familia --}}
            <div id="rcm-wstep-1" style="padding:1.25rem 1.5rem;">
                <p class="cm-modal-label" style="font-size:0.85rem; font-weight:600; margin:0 0 0.6rem;">Tipo de ítem:</p>
                <div style="display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:0.5rem; margin-bottom:1rem;">
                    <button type="button" class="rw-tile" data-rcm-tipo="producto"    onclick="rcmWizardSeleccionarTipo('producto')">Producto</button>
                    <button type="button" class="rw-tile" data-rcm-tipo="servicio"    onclick="rcmWizardSeleccionarTipo('servicio')">Servicio</button>
                    <button type="button" class="rw-tile" data-rcm-tipo="mantencion"  onclick="rcmWizardSeleccionarTipo('mantencion')">Mantención</button>
                    <button type="button" class="rw-tile" data-rcm-tipo="arriendo"    onclick="rcmWizardSeleccionarTipo('arriendo')">Arriendo</button>
                </div>
                <p class="cm-modal-label" style="font-size:0.85rem; font-weight:600; margin:0 0 0.6rem;">Selecciona la familia:</p>
                <div id="rcm-wizard-familias" style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0.5rem;"></div>
                <button type="button" onclick="rcmToggleNuevaFam()"
                        style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                    + Nueva familia
                </button>
                <div id="rcm-nueva-fam-form" style="display:none; margin-top:0.25rem;">
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" id="rcm-nueva-fam-input" placeholder="Nombre de la familia"
                               class="flex-1 cm-input border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();rcmCrearFamilia();} if(event.key==='Escape')rcmToggleNuevaFam();">
                        <button type="button" onclick="rcmCrearFamilia()" class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">Guardar</button>
                        <button type="button" onclick="rcmToggleNuevaFam()" class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">✕</button>
                    </div>
                </div>
                <div id="rcm-wstep1-error" class="mt-2 text-xs text-red-600" style="display:none;"></div>
            </div>

            {{-- Step 2: Categoría --}}
            <div id="rcm-wstep-2" style="display:none; padding:1.25rem 1.5rem;">
                <p class="cm-modal-label" style="font-size:0.85rem; font-weight:600; margin:0 0 0.6rem;">Selecciona la categoría: <span style="color:#ef4444;">*</span></p>
                <div id="rcm-wizard-categorias" style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0.5rem;"></div>
                <button type="button" onclick="rcmToggleNuevaCat()"
                        style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                    + Nueva categoría
                </button>
                <div id="rcm-nueva-cat-form" style="display:none; margin-top:0.25rem;">
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" id="rcm-nueva-cat-input" placeholder="Nombre de la categoría"
                               class="flex-1 cm-input border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();rcmCrearCategoria();} if(event.key==='Escape')rcmToggleNuevaCat();">
                        <button type="button" onclick="rcmCrearCategoria()" class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">Guardar</button>
                        <button type="button" onclick="rcmToggleNuevaCat()" class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">✕</button>
                    </div>
                </div>
                <div id="rcm-wstep2-error" class="mt-2 text-xs text-red-600" style="display:none;"></div>
            </div>

            {{-- Step 3: Marca --}}
            <div id="rcm-wstep-3" style="display:none; padding:1.25rem 1.5rem;">
                <p class="cm-modal-label" style="font-size:0.85rem; font-weight:600; margin:0 0 0.6rem;">Selecciona la marca: <span style="color:#9ca3af; font-weight:400;">(opcional)</span></p>
                <div id="rcm-wizard-marcas" style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0.5rem;"></div>
                <button type="button" onclick="rcmToggleNuevaMarca()"
                        style="font-size:0.78rem; color:#4338ca; background:none; border:none; cursor:pointer; padding:0.4rem 0; margin-top:0.5rem; display:inline-flex; align-items:center; gap:0.2rem; font-weight:600;">
                    + Nueva marca
                </button>
                <div id="rcm-nueva-marca-form" style="display:none; margin-top:0.25rem;">
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" id="rcm-nueva-marca-input" placeholder="Nombre de la marca"
                               class="flex-1 cm-input border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();rcmCrearMarca();} if(event.key==='Escape')rcmToggleNuevaMarca();">
                        <button type="button" onclick="rcmCrearMarca()" class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">Guardar</button>
                        <button type="button" onclick="rcmToggleNuevaMarca()" class="text-xs text-gray-500 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg">✕</button>
                    </div>
                </div>
                <div id="rcm-wstep3-error" class="mt-2 text-xs text-red-600" style="display:none;"></div>
            </div>

            {{-- Step 4: Datos del producto --}}
            <div id="rcm-wstep-4" style="display:none; padding:1.25rem 1.5rem;">
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <div>
                        <label class="cm-modal-label" style="display:block; font-size:0.78rem; font-weight:600; margin-bottom:0.3rem;">
                            Nombre del producto <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" id="rcm-modal-nombre-edit" placeholder="EJ: CABLE HDMI 2.1"
                               class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                               style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()">
                    </div>
                    <div id="rcm-step4-cont-wrap">
                        <label class="cm-modal-label" style="display:block; font-size:0.78rem; font-weight:600; margin-bottom:0.3rem;">
                            Contenedor <span style="color:#ef4444;">*</span>
                        </label>
                        <select id="rcm-modal-contenedor"
                                class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">— Sin asignar —</option>
                            @foreach($containers as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="rcm-step4-pkg-wrap">
                        <label style="display:inline-flex; align-items:center; gap:0.5rem; cursor:pointer; user-select:none; font-size:0.8rem; font-weight:600;" class="cm-modal-label">
                            <input type="checkbox" id="rcm-modal-pkg-toggle" onchange="rcmTogglePkg()"
                                   style="width:1rem; height:1rem; accent-color:#4338ca; cursor:pointer; flex-shrink:0;">
                            <span>¿Su producto viene en paquete?</span>
                        </label>
                        <div id="rcm-modal-pkg-fields" style="display:none; margin-top:0.5rem; padding:0.6rem 0.75rem; border-radius:0.5rem; background:#f5f3ff;">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.5rem;">
                                <div>
                                    <label class="cm-modal-label" style="display:block; font-size:0.72rem; font-weight:600; margin-bottom:0.25rem;">Tipo de paquete <span style="color:#ef4444;">*</span></label>
                                    <select id="rcm-modal-pkg-tipo" oninput="rcmActualizarPkgPreview()" onchange="rcmActualizarPkgPreview()"
                                            class="w-full cm-input border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                        <option value="">— Selecciona —</option>
                                        @foreach(['Caja','Paquete','Bolsa','Pack','Kit','Rollo','Resma','Tubo','Bidón','Saco','Pallet','Otro'] as $tp)
                                        <option value="{{ $tp }}">{{ $tp }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="cm-modal-label" style="display:block; font-size:0.72rem; font-weight:600; margin-bottom:0.25rem;">Unidades por paquete <span style="color:#ef4444;">*</span></label>
                                    <input type="number" id="rcm-modal-pkg-cant" min="1" max="9999" placeholder="Ej: 100"
                                           oninput="rcmActualizarPkgPreview()"
                                           class="w-full cm-input border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                </div>
                            </div>
                            <div style="margin-bottom:0.35rem;">
                                <label class="cm-modal-label" style="display:block; font-size:0.72rem; font-weight:600; margin-bottom:0.25rem;">Unidad base <span style="color:#ef4444;">*</span></label>
                                <select id="rcm-modal-pkg-base" onchange="rcmActualizarPkgPreview()"
                                        class="w-full cm-input border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                    <option value="">— Selecciona —</option>
                                    @foreach($unidades as $u)
                                    @php $unNombre = ucwords(strtolower($u->nombre)); @endphp
                                    <option value="{{ $unNombre }}">{{ $unNombre }}{{ $u->abreviacion ? ' (' . $u->abreviacion . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p id="rcm-modal-pkg-preview" style="font-size:0.75rem; color:#4338ca; font-weight:700; margin:0;"></p>
                        </div>
                    </div>
                    <div id="rcm-step4-stock-wrap" style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div>
                            <label class="cm-modal-label" style="display:block; font-size:0.78rem; font-weight:600; margin-bottom:0.3rem;">Stock mínimo</label>
                            <input type="number" id="rcm-modal-minimo" min="0" value="0"
                                   class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        </div>
                        <div>
                            <label class="cm-modal-label" style="display:block; font-size:0.78rem; font-weight:600; margin-bottom:0.3rem;">Stock crítico</label>
                            <input type="number" id="rcm-modal-critico" min="0" value="0"
                                   class="w-full cm-input border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        </div>
                    </div>
                    <div id="rcm-wizard-error" class="text-xs text-red-600" style="display:none;"></div>
                </div>
            </div>

        </div>{{-- /scrollable --}}

        {{-- Footer --}}
        <div class="cm-modal-footer" style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.5rem; border-top:1px solid #f3f4f6; flex-shrink:0;">
            <button type="button" id="rcm-wbtn-atras" onclick="rcmWizardAtras()" style="display:none;"
                    class="cm-btn-cancel px-4 py-2 text-sm font-medium rounded-lg border border-gray-200">← Atrás</button>
            <div style="margin-left:auto; display:flex; gap:0.5rem;">
                <button type="button" onclick="rcmCerrarWizard()"
                        class="cm-btn-cancel px-4 py-2 text-sm font-medium rounded-lg border border-gray-200">Cancelar</button>
                <button type="button" id="rcm-wbtn-siguiente" onclick="rcmWizardSiguiente()"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Siguiente →</button>
                <button type="button" id="rcm-wbtn-confirmar" onclick="rcmConfirmarWizard()" style="display:none;"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Confirmar</button>
            </div>
        </div>

    </div>
</div>

{{-- Modal aviso (reemplaza alert nativo) --}}
<div id="aviso-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55); align-items:center; justify-content:center; padding:1rem;"
     onclick="if(event.target===this) cerrarAviso()">
    <div class="aviso-inner" style="border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.3); width:380px; max-width:calc(100vw - 2rem); padding:1.5rem;">
        <div style="display:flex; align-items:flex-start; gap:1rem;">
            <div class="aviso-icon-wrap" style="width:2.5rem; height:2.5rem; border-radius:9999px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg id="aviso-icon" style="width:1.25rem;height:1.25rem;" fill="none" stroke="#b45309" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <p id="aviso-texto" style="flex:1; font-size:0.875rem; line-height:1.6; margin:0; padding-top:0.3rem;"></p>
        </div>
        <div style="margin-top:1.25rem; display:flex; justify-content:flex-end;">
            <button id="aviso-ok" onclick="cerrarAviso()"
                    style="padding:0.45rem 1.25rem; font-size:0.875rem; font-weight:600; color:#fff; border:none; border-radius:0.5rem; cursor:pointer; transition:background .15s;">
                Entendido
            </button>
        </div>
    </div>
</div>

@push('head')
<style>
@keyframes aviso-in  { from { opacity:0; transform:scale(.93) translateY(-10px); } to { opacity:1; transform:none; } }
@keyframes cmp-in    { from { opacity:0; transform:scale(.95) translateY(-10px); } to { opacity:1; transform:none; } }
@keyframes resolverIn{ from { opacity:0; transform:scale(.95) translateY(-8px);  } to { opacity:1; transform:none; } }

:root { --cmp-border: #e5e7eb; }
html.dark { --cmp-border: #334155; }

#modal-carga-masiva-productos #cmp-btn-importar:hover:not(:disabled) { background:#4338ca !important; }
#modal-carga-masiva-productos #cmp-btn-importar:disabled { opacity:.65; cursor:not-allowed; }

#rcm-btn-confirmar:hover:not(:disabled) { filter:brightness(1.1); }
#rcm-btn-confirmar:disabled { opacity:.65; cursor:not-allowed; }

/* ── Wizard tiles ─────────────────────────────────────────────────────── */
.rw-tile {
    font-size:.875rem; font-weight:500; padding:.75rem 1rem;
    border-radius:.75rem; border:1px solid #d1d5db;
    background:#fff; color:#374151;
    cursor:pointer; transition:all .15s; text-align:left; width:100%;
}
.rw-tile:hover { border-color:#a5b4fc; color:#4338ca; }
.rw-tile.rw-tile-sel { background:#4f46e5; color:#fff; border-color:#4f46e5; }
.rw-tile.rw-tile-sel:hover { background:#4338ca; border-color:#4338ca; }
html.dark .rw-tile { background:#0f172a; color:#e2e8f0; border-color:#334155; }
html.dark .rw-tile:hover { border-color:#7c3aed; color:#c4b5fd; }
html.dark .rw-tile.rw-tile-sel { background:#4f46e5; color:#fff; border-color:#4f46e5; }

.rw-tile-sin {
    font-size:.875rem; font-weight:500; padding:.75rem 1rem;
    border-radius:.75rem; border:1px dashed #d1d5db;
    background:transparent; color:#6b7280;
    cursor:pointer; transition:all .15s; text-align:left; width:100%;
}
.rw-tile-sin:hover { border-color:#a5b4fc; color:#4338ca; }
.rw-tile-sin.rw-tile-sel { border:2px solid #6366f1; background:#e0e7ff; color:#3730a3; }
html.dark .rw-tile-sin { border-color:#475569; color:#94a3b8; }
html.dark .rw-tile-sin:hover { border-color:#7c3aed; color:#c4b5fd; }
html.dark .rw-tile-sin.rw-tile-sel { border-color:#6366f1; background:#1e1b4b; color:#a5b4fc; }

/* ── Wizard modal classes ─────────────────────────────────────────────── */
.cm-modal-box         { background:#fff; }
.cm-modal-title       { color:#1f2937; }
.cm-modal-nombre-desc { color:#6d28d9; }
.cm-modal-label       { color:#374151; }
.cm-modal-footer      { background:#fff; }
.cm-btn-cancel        { background:#f3f4f6; color:#374151; }
.cm-input             { background:#fff; color:#1f2937; }

html.dark .cm-modal-box         { background:#1e293b; }
html.dark .cm-modal-title       { color:#f1f5f9; }
html.dark .cm-modal-nombre-desc { color:#a5b4fc; }
html.dark .cm-modal-label       { color:#94a3b8; }
html.dark .cm-modal-footer      { background:#1e293b; border-color:#334155 !important; }
html.dark .cm-btn-cancel        { background:#334155; color:#cbd5e1; border-color:#475569; }
html.dark .cm-input             { background:#0f172a; color:#e2e8f0; border-color:#334155; }
html.dark #rcm-modal-pkg-fields { background:#1e1b4b; }
html.dark #rcm-modal-pkg-preview{ color:#93c5fd; }
</style>
@endpush

@endsection
