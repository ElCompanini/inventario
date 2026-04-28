@extends('layouts.app')
@section('title', 'Catálogo de Productos')

@section('content')

{{-- Header --}}
<div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Catálogo de Productos</h1>
        <p class="text-sm text-gray-500 mt-1">Gestión de familias, categorías y productos</p>
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

{{-- Family tabs --}}
<div class="flex flex-wrap items-center gap-2 mb-6">
    @foreach($familias as $familia)
    <a href="{{ route('admin.productos.catalogo', ['familia' => $familia->id]) }}"
       class="{{ $familiaActiva === $familia->id ? 'btn-primary' : 'btn-ghost' }}
              px-5 py-2 rounded-full text-sm font-semibold transition
              {{ $familiaActiva === $familia->id
                 ? 'bg-indigo-600 text-white shadow'
                 : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
        {{ $familia->nombre }}
    </a>
    @endforeach
</div>

@php $familiaActual = $familias->firstWhere('id', $familiaActiva); @endphp

@if($familiaActual)

{{-- CC de la familia activa --}}
<div class="mb-4 flex items-center gap-3 bg-white rounded-xl shadow px-4 py-3">
    <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
    </svg>
    <span class="text-sm font-semibold text-gray-700">Centro de costo de la familia
        <span class="text-indigo-600">{{ $familiaActual->nombre }}</span>:
    </span>
    <select id="select-cc-familia"
            data-url="{{ route('admin.catalogo.familias.asignar-cc', $familiaActual->id) }}"
            class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <option value="">— Sin centro de costo —</option>
        @foreach(\App\Models\CentroCosto::orderBy('acronimo')->get(['id','acronimo']) as $cc)
            <option value="{{ $cc->id }}" {{ $familiaActual->centro_costo_id == $cc->id ? 'selected' : '' }}>
                {{ $cc->acronimo }}
            </option>
        @endforeach
    </select>
    <span id="cc-familia-ok" class="text-green-600 text-sm font-semibold hidden">✓ Guardado</span>
    <span id="cc-familia-err" class="text-red-500 text-sm hidden">Error al guardar</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT: Categorías --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom:1px solid #f3f4f6;">
                <h2 class="text-sm font-bold text-gray-700">Categorías</h2>
                <button onclick="abrirModalCategoria({{ $familiaActual->id }})"
                        class="btn-primary inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva
                </button>
            </div>

            @if($familiaActual->categorias->isEmpty())
            <p class="text-xs text-gray-400 italic text-center py-4">Sin categorías aún</p>
            @else
            <ul class="space-y-1" id="lista-categorias">
                @foreach($familiaActual->categorias as $cat)
                <li>
                    <button onclick="seleccionarCategoria({{ $cat->id }}, '{{ addslashes($cat->nombre) }}')"
                            id="cat-btn-{{ $cat->id }}"
                            class="btn-ghost cat-item w-full text-left px-3 py-2.5 rounded-lg text-sm flex items-center justify-between
                                   {{ request('categoria', $familiaActual->categorias->first()?->id) == $cat->id
                                      ? 'bg-indigo-50 text-indigo-700 font-semibold'
                                      : 'text-gray-700 hover:bg-gray-50' }}"
                            data-cat-id="{{ $cat->id }}">
                        <span class="cat-nombre min-w-0 flex-1 truncate">{{ $cat->nombre }}</span>
                        <span class="text-xs text-gray-400 ml-2 shrink-0">{{ $cat->productos->count() }}</span>
                    </button>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    {{-- RIGHT: Productos --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow p-5" id="panel-productos">

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
                    Nuevo producto
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
    'categorias' => $f->categorias->map(fn($c) => [
        'id'        => $c->id,
        'nombre'    => $c->nombre,
        'familia_id'=> $c->familia_id,
        'productos' => $c->productos->map(fn($p) => [
            'id'           => $p->id,
            'nombre'       => $p->nombre,
            'stock_actual' => $p->stock_actual,
            'stock_minimo' => $p->stock_minimo,
            'stock_critico'=> $p->stock_critico,
            'contenedor_id'=> $p->contenedor,
        ])->values(),
    ])->values(),
])->values()) !!}
</script>

<script id="containers-data" type="application/json">
{!! json_encode($containers->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])->values()) !!}
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

        {{-- Selectores familia/categoría (solo al crear) --}}
        <div id="prod-selector-wrapper" class="space-y-3 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Familia <span class="text-red-500">*</span></label>
                <div id="prod-familias-btns" class="flex flex-wrap gap-2"></div>
            </div>
            <div id="prod-cat-wrapper" style="display:none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoría <span class="text-red-500">*</span></label>
                <div id="prod-categorias-btns" class="flex flex-wrap gap-2"></div>
            </div>
            <div style="border-top:1px solid #f3f4f6; margin-top:0.25rem;"></div>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
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
            @foreach(['Familia','Categoría','Producto'] as $i => $step)
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
            <div id="bc-step1-errors" class="hidden mt-3 text-xs text-red-600"></div>
        </div>

        {{-- Step 2: Categoría --}}
        <div id="bc-step-2" class="px-6 py-5" style="display:none;">
            <p class="text-sm font-medium text-gray-700 mb-3">Selecciona o crea la categoría:</p>
            <div id="bc-categorias-lista" class="grid grid-cols-2 gap-2 mb-3"></div>
            <div class="flex gap-2 mt-2">
                <input type="text" id="bc-nueva-cat" placeholder="Nueva categoría..."
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <button onclick="bcCrearCategoria()"
                        class="btn-primary text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg">
                    Crear
                </button>
            </div>
            <div id="bc-step2-errors" class="hidden mt-3 text-xs text-red-600"></div>
        </div>

        {{-- Step 3: Producto --}}
        <div id="bc-step-3" class="px-6 py-5" style="display:none;">
            <div class="space-y-3">
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
                <div id="bc-step3-errors" class="hidden text-xs text-red-600"></div>
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

@push('head')
<style>
    @keyframes cat-in {
        from { opacity:0; transform:scale(.94); }
        to   { opacity:1; transform:scale(1); }
    }
</style>
@endpush

@push('scripts')
<script>
const CSRF             = '{{ csrf_token() }}';
const ROUTE_FAM_STORE  = '{{ route('admin.catalogo.familias.store') }}';
const ROUTE_CAT_STORE  = '{{ route('admin.catalogo.categorias.store') }}';
const ROUTE_CAT_UPDATE = (id) => `{{ url('admin/catalogo/categorias') }}/${id}`;
const ROUTE_PROD_STORE  = '{{ route('admin.catalogo.productos.store') }}';
const ROUTE_PROD_UPDATE = (id) => `{{ url('admin/catalogo/productos') }}/${id}`;
const ROUTE_BARCODE          = '{{ route('admin.catalogo.barcode') }}';
const ROUTE_ASOCIAR_BARCODE  = (id) => `{{ url('admin/catalogo/productos') }}/${id}/barcode`;

const catalogoData   = JSON.parse(document.getElementById('catalogo-data').textContent);
const containersData = JSON.parse(document.getElementById('containers-data').textContent);

let catActualId    = null;
let catActualNombre = '';
let catFamiliaId   = {{ $familiaActiva }};
let editandoCatId  = null;
let editandoProdId = null;

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
                <button onclick="abrirWizardBarcode('${escHtml(codigo).replace(/'/g,"\\\'")}')"
                        class="btn-primary mt-3 inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar al catálogo
                </button>
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

let bcCodigo        = '';
let bcStep          = 1;
let bcFamiliaId     = null;
let bcFamiliaNombre = '';
let bcCatId         = null;
let bcCatNombre     = '';

function abrirWizardBarcode(codigo) {
    bcCodigo    = codigo;
    bcStep      = 1;
    bcFamiliaId = null;
    bcCatId     = null;
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
    [1,2,3].forEach(n => {
        document.getElementById('bc-step-' + n).style.display = n === step ? 'block' : 'none';
        const circle = document.getElementById('bc-step-circle-' + n);
        const label  = document.getElementById('bc-step-label-' + n);
        if (n < step) {
            circle.style.background = '#bbf7d0'; circle.style.color = '#166534';
            circle.innerHTML = '✓';
        } else if (n === step) {
            circle.style.background = '#4338ca'; circle.style.color = '#fff';
            circle.innerHTML = n;
            label.style.color = '#4338ca'; label.style.fontWeight = '600';
        } else {
            circle.style.background = '#e0e7ff'; circle.style.color = '#4338ca';
            circle.innerHTML = n;
            label.style.color = '#9ca3af'; label.style.fontWeight = '500';
        }
    });
    document.getElementById('bc-btn-atras').style.display    = step > 1 ? 'inline-flex' : 'none';
    document.getElementById('bc-btn-siguiente').style.display = step < 3 ? 'inline-flex' : 'none';
    document.getElementById('bc-btn-guardar').style.display   = step === 3 ? 'inline-flex' : 'none';

    if (step === 1) bcRenderFamilias();
    if (step === 2) bcRenderCategorias();
    if (step === 3) setTimeout(() => document.getElementById('bc-stock-minimo').focus(), 50);
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

async function bcCrearCategoria() {
    const nombre = document.getElementById('bc-nueva-cat').value.trim();
    const errDiv = document.getElementById('bc-step2-errors');
    if (!nombre) { errDiv.textContent = 'Escribe el nombre.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    try {
        const body = new URLSearchParams({ _token: CSRF, nombre, familia_id: bcFamiliaId });
        const res  = await fetch(ROUTE_CAT_STORE, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors?.nombre?.[0] ?? 'Error.'; errDiv.classList.remove('hidden'); return; }
        // Agregar al catalogoData local
        const familia = catalogoData.find(f => f.id === bcFamiliaId);
        if (familia) familia.categorias.push({ id: json.id, nombre: json.nombre, productos: [] });
        bcCatId = json.id; bcCatNombre = json.nombre;
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
        document.getElementById('bc-wizard-titulo').textContent = 'Datos del producto';
        document.getElementById('bc-stock-minimo').value = '0';
        document.getElementById('bc-stock-critico').value = '0';
        document.getElementById('bc-step3-errors').classList.add('hidden');
        bcIrAStep(3);
    }
}

function bcAtras() {
    if (bcStep === 2) { document.getElementById('bc-wizard-titulo').textContent = 'Nuevo producto'; bcIrAStep(1); }
    else if (bcStep === 3) { document.getElementById('bc-wizard-titulo').textContent = 'Selecciona la categoría'; bcIrAStep(2); }
}

async function bcGuardar() {
    const stock_minimo  = document.getElementById('bc-stock-minimo').value;
    const stock_critico = document.getElementById('bc-stock-critico').value;
    const errDiv        = document.getElementById('bc-step3-errors');
    errDiv.classList.add('hidden');
    const btn = document.getElementById('bc-btn-guardar');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const body = new URLSearchParams({ _token: CSRF, stock_minimo, stock_critico, categoria_id: bcCatId, codigo_barras: bcCodigo });
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
    if (!nombre) { errDiv.textContent = 'El nombre es obligatorio.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    const btn = document.getElementById('btn-guardar-fam');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const res  = await fetch(ROUTE_FAM_STORE, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ _token: CSRF, nombre }) });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors?.nombre?.[0] ?? json.message ?? 'Error al guardar.'; errDiv.classList.remove('hidden'); }
        else { cerrarModalFamilia(); window.location = '{{ route('admin.productos.catalogo') }}?familia=' + json.id; }
    } catch (e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

document.getElementById('modal-familia').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModalFamilia(); });
document.getElementById('fam-nombre-input').addEventListener('keydown', e => { if (e.key === 'Enter') guardarFamilia(); });

// ── Selección de categoría ───────────────────────────────────────────────────

function seleccionarCategoria(catId, catNombre) {
    catActualId     = catId;
    catActualNombre = catNombre;
    document.querySelectorAll('.cat-item').forEach(el => {
        el.classList.remove('bg-indigo-50', 'text-indigo-700', 'font-semibold');
        el.classList.add('text-gray-700');
    });
    const btn = document.getElementById('cat-btn-' + catId);
    if (btn) { btn.classList.add('bg-indigo-50', 'text-indigo-700', 'font-semibold'); btn.classList.remove('text-gray-700'); }

    document.getElementById('titulo-categoria').textContent = catNombre;
    const cat   = catalogoData.flatMap(f => f.categorias).find(c => c.id === catId);
    const count = cat?.productos?.length ?? 0;
    document.getElementById('subtitulo-categoria').textContent = count === 0 ? 'Sin productos' : (count === 1 ? '1 producto' : count + ' productos');

    const btnNuevo = document.getElementById('btn-nuevo-producto');
    btnNuevo.classList.remove('hidden');
    btnNuevo.style.display = 'inline-flex';

    renderProductos(cat?.productos ?? []);
}

function renderProductos(productos) {
    const area = document.getElementById('area-productos');
    if (!productos.length) {
        area.innerHTML = '<p class="text-sm text-gray-400 text-center py-8 italic">Sin productos en esta categoría. Agrega el primero.</p>';
        return;
    }
    let html = '<div class="space-y-2">';
    productos.forEach(p => {
        const estado     = p.stock_actual <= p.stock_critico ? 'critico' : p.stock_actual <= p.stock_minimo ? 'minimo' : 'normal';
        const colorStock = estado === 'critico' ? 'text-red-600' : estado === 'minimo' ? 'text-yellow-600' : 'text-green-600';
        html += `
        <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 leading-snug">${escHtml(p.nombre)}</p>
                <p class="text-xs mt-1 flex items-center gap-1.5">
                    <span class="inline-flex items-center gap-1 bg-yellow-50 border border-yellow-200 text-yellow-700 font-medium px-2 py-0.5 rounded-md">Mín: <strong>${p.stock_minimo}</strong></span>
                    <span class="inline-flex items-center gap-1 bg-red-50 border border-red-200 text-red-600 font-medium px-2 py-0.5 rounded-md">Crít: <strong>${p.stock_critico}</strong></span>
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <span class="text-lg font-bold ${colorStock}">${p.stock_actual}</span>
                <button onclick="editarProducto(${p.id})"
                        class="btn-ghost text-xs font-semibold text-gray-600 hover:text-gray-800 border border-gray-300 hover:border-gray-400 bg-white hover:bg-gray-50 px-3 py-1.5 rounded-lg">
                    Editar
                </button>
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
function cerrarModalCategoria() { cerrarModal('modal-categoria'); }

async function guardarCategoria() {
    const nombre = document.getElementById('cat-nombre-input').value.trim();
    const errDiv = document.getElementById('modal-cat-errors');
    if (!nombre) { errDiv.textContent = 'El nombre es obligatorio.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    const btn = document.getElementById('btn-guardar-cat');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const url    = editandoCatId ? ROUTE_CAT_UPDATE(editandoCatId) : ROUTE_CAT_STORE;
        const method = editandoCatId ? 'PUT' : 'POST';
        const body   = new URLSearchParams({ _token: CSRF, nombre });
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
        btn.onclick = function() { prodCatId = c.id; prodRenderCategorias(); };
        cont.appendChild(btn);
    });
    wrapper.style.display = 'block';
}

function abrirModalProducto() {
    editandoProdId = null;
    // Pre-seleccionar familia y categoría si hay una activa
    if (catActualId) {
        var familia = catalogoData.find(function(f) { return f.categorias.some(function(c) { return c.id === catActualId; }); });
        prodFamiliaId = familia ? familia.id : null;
        prodCatId     = catActualId;
    } else {
        prodFamiliaId = null;
        prodCatId     = null;
    }
    document.getElementById('prod-selector-wrapper').style.display = 'block';
    document.getElementById('modal-prod-titulo').textContent    = 'Nuevo producto';
    document.getElementById('modal-prod-subtitulo').textContent = 'Selecciona familia y categoría';
    document.getElementById('prod-stock-minimo').value          = '0';
    document.getElementById('prod-stock-critico').value         = '0';
    document.getElementById('modal-prod-errors').classList.add('hidden');
    document.getElementById('modal-prod-success').classList.add('hidden');
    prodRenderFamilias();
    prodRenderCategorias();
    abrirModal('modal-producto');
    setTimeout(function() { document.getElementById('prod-stock-minimo').focus(); }, 50);
}

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
    abrirModal('modal-producto');
    setTimeout(function() { document.getElementById('prod-stock-minimo').focus(); }, 50);
}

function cerrarModalProducto() { cerrarModal('modal-producto'); }

async function guardarProducto() {
    var stock_minimo  = document.getElementById('prod-stock-minimo').value;
    var stock_critico = document.getElementById('prod-stock-critico').value;
    var errDiv        = document.getElementById('modal-prod-errors');
    var sucDiv        = document.getElementById('modal-prod-success');
    errDiv.classList.add('hidden');
    sucDiv.classList.add('hidden');

    if (!editandoProdId && !prodFamiliaId) { errDiv.textContent = 'Selecciona una familia.'; errDiv.classList.remove('hidden'); return; }
    if (!editandoProdId && !prodCatId) { errDiv.textContent = 'Selecciona una categoría.'; errDiv.classList.remove('hidden'); return; }

    var btn = document.getElementById('btn-guardar-prod');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        var url    = editandoProdId ? ROUTE_PROD_UPDATE(editandoProdId) : ROUTE_PROD_STORE;
        var method = editandoProdId ? 'PUT' : 'POST';
        var body   = new URLSearchParams({ _token: CSRF, stock_minimo, stock_critico });
        if (!editandoProdId) body.append('categoria_id', prodCatId);
        var res  = await fetch(url, { method, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        var json = await res.json();
        if (!res.ok || !json.ok) {
            errDiv.textContent = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Error al guardar.');
            errDiv.classList.remove('hidden');
        } else if (editandoProdId) {
            var cat = catalogoData.flatMap(function(f) { return f.categorias; }).find(function(c) { return c.id === catActualId; });
            var prod = cat ? cat.productos.find(function(p) { return p.id === editandoProdId; }) : null;
            if (prod) { prod.stock_minimo = parseInt(stock_minimo); prod.stock_critico = parseInt(stock_critico); }
            renderProductos(cat ? cat.productos : []);
            cerrarModalProducto();
        } else {
            var familia = catalogoData.find(function(f) { return f.categorias.some(function(c) { return c.id === prodCatId; }); });
            var cat     = familia ? familia.categorias.find(function(c) { return c.id === prodCatId; }) : null;
            if (cat) {
                cat.productos.push({ id: json.id, nombre: json.nombre, stock_actual: 0, stock_minimo: parseInt(stock_minimo), stock_critico: parseInt(stock_critico), contenedor_id: null });
                if (catActualId === prodCatId) {
                    document.getElementById('subtitulo-categoria').textContent = cat.productos.length + ' producto' + (cat.productos.length !== 1 ? 's' : '');
                    renderProductos(cat.productos);
                }
                var spanCont = document.querySelector('[data-cat-id="' + prodCatId + '"] + span, #cat-btn-' + prodCatId + ' span.text-xs');
                if (spanCont) spanCont.textContent = cat.productos.length;
            }
            sucDiv.textContent = '✓ Producto "' + (json.nombre || '') + '" guardado. Puedes agregar otro.';
            sucDiv.classList.remove('hidden');
            document.getElementById('prod-stock-minimo').value = '0';
            document.getElementById('prod-stock-critico').value = '0';
            document.getElementById('prod-stock-minimo').focus();
        }
    } catch (e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

document.getElementById('modal-producto').addEventListener('click', function(e) { if (e.target === e.currentTarget) cerrarModalProducto(); });

// Auto-select first category on load
window.addEventListener('DOMContentLoaded', function() {
    const primerBtn = document.querySelector('.cat-item');
    if (primerBtn) seleccionarCategoria(parseInt(primerBtn.dataset.catId), primerBtn.querySelector('.cat-nombre').textContent.trim());

    // Asignación de CC de familia
    const selCC = document.getElementById('select-cc-familia');
    if (selCC) {
        selCC.addEventListener('change', function() {
            const url  = this.dataset.url;
            const ccId = this.value || null;
            const ok   = document.getElementById('cc-familia-ok');
            const err  = document.getElementById('cc-familia-err');
            ok.classList.add('hidden');
            err.classList.add('hidden');

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ centro_costo_id: ccId }),
            })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    ok.classList.remove('hidden');
                    setTimeout(() => ok.classList.add('hidden'), 2500);
                } else {
                    err.classList.remove('hidden');
                }
            })
            .catch(() => err.classList.remove('hidden'));
        });
    }
});
</script>
@endpush

@endsection
