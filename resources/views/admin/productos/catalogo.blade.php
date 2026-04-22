@extends('layouts.app')
@section('title', 'Catálogo de Productos')

@section('content')

{{-- Header --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Catálogo de Productos</h1>
        <p class="text-sm text-gray-500 mt-1">Gestión de familias, categorías y productos</p>
    </div>
    <button onclick="abrirModalFamilia()"
            class="btn-primary inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva familia
    </button>
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
            'descripcion'  => $p->descripcion,
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
    <div id="modal-producto-inner" class="bg-white rounded-xl shadow-xl w-full mx-4" style="max-width:500px; padding:1.5rem; animation:cat-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <h2 class="text-lg font-bold text-gray-800 mb-1" id="modal-prod-titulo">Nuevo producto</h2>
        <p class="text-sm text-gray-500 mb-4" id="modal-prod-subtitulo"></p>

        <div id="modal-prod-errors" class="hidden mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-3 py-2 text-sm"></div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción del producto <span class="text-red-500">*</span></label>
                <textarea id="prod-descripcion" rows="3" maxlength="500"
                          placeholder="Ej: Memoria RAM 16GB DDR5 4800MHz SO-DIMM"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
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
                Cancelar
            </button>
            <button id="btn-guardar-prod" onclick="guardarProducto()"
                    class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                Guardar
            </button>
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
const CSRF           = '{{ csrf_token() }}';
const ROUTE_FAM_STORE  = '{{ route('admin.catalogo.familias.store') }}';
const ROUTE_CAT_STORE  = '{{ route('admin.catalogo.categorias.store') }}';
const ROUTE_CAT_UPDATE = (id) => `{{ url('admin/catalogo/categorias') }}/${id}`;
const ROUTE_PROD_STORE  = '{{ route('admin.catalogo.productos.store') }}';
const ROUTE_PROD_UPDATE = (id) => `{{ url('admin/catalogo/productos') }}/${id}`;

const catalogoData   = JSON.parse(document.getElementById('catalogo-data').textContent);
const containersData = JSON.parse(document.getElementById('containers-data').textContent);

let catActualId    = null;
let catActualNombre = '';
let catFamiliaId   = {{ $familiaActiva }};
let editandoCatId  = null;
let editandoProdId = null;

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
                <p class="text-sm font-medium text-gray-800 leading-snug">${escHtml(p.descripcion)}</p>
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

function abrirModalProducto() {
    if (!catActualId) return;
    editandoProdId = null;
    document.getElementById('modal-prod-titulo').textContent    = 'Nuevo producto';
    document.getElementById('modal-prod-subtitulo').textContent = catActualNombre;
    document.getElementById('prod-descripcion').value           = '';
    document.getElementById('prod-stock-minimo').value          = '0';
    document.getElementById('prod-stock-critico').value         = '0';
    document.getElementById('modal-prod-errors').classList.add('hidden');
    abrirModal('modal-producto');
    setTimeout(() => document.getElementById('prod-descripcion').focus(), 50);
}

function editarProducto(prodId) {
    const cat  = catalogoData.flatMap(f => f.categorias).find(c => c.id === catActualId);
    const prod = cat?.productos?.find(p => p.id === prodId);
    if (!prod) return;
    editandoProdId = prodId;
    document.getElementById('modal-prod-titulo').textContent    = 'Editar producto';
    document.getElementById('modal-prod-subtitulo').textContent = catActualNombre;
    document.getElementById('prod-descripcion').value           = prod.descripcion;
    document.getElementById('prod-stock-minimo').value          = prod.stock_minimo;
    document.getElementById('prod-stock-critico').value         = prod.stock_critico;
    document.getElementById('modal-prod-errors').classList.add('hidden');
    abrirModal('modal-producto');
    setTimeout(() => document.getElementById('prod-descripcion').focus(), 50);
}

function cerrarModalProducto() { cerrarModal('modal-producto'); }

async function guardarProducto() {
    const descripcion   = document.getElementById('prod-descripcion').value.trim();
    const stock_minimo  = document.getElementById('prod-stock-minimo').value;
    const stock_critico = document.getElementById('prod-stock-critico').value;
    const errDiv        = document.getElementById('modal-prod-errors');
    if (!descripcion) { errDiv.textContent = 'La descripción es obligatoria.'; errDiv.classList.remove('hidden'); return; }
    errDiv.classList.add('hidden');
    const btn = document.getElementById('btn-guardar-prod');
    btn.disabled = true; btn.textContent = 'Guardando...';
    try {
        const url    = editandoProdId ? ROUTE_PROD_UPDATE(editandoProdId) : ROUTE_PROD_STORE;
        const method = editandoProdId ? 'PUT' : 'POST';
        const body   = new URLSearchParams({ _token: CSRF, descripcion, stock_minimo, stock_critico });
        if (!editandoProdId) body.append('categoria_id', catActualId);
        const res  = await fetch(url, { method, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const json = await res.json();
        if (!res.ok || !json.ok) { errDiv.textContent = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Error al guardar.'); errDiv.classList.remove('hidden'); }
        else { cerrarModalProducto(); location.reload(); }
    } catch (e) { errDiv.textContent = 'Error de conexión.'; errDiv.classList.remove('hidden'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

document.getElementById('modal-producto').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModalProducto(); });

// Auto-select first category on load
window.addEventListener('DOMContentLoaded', function() {
    const primerBtn = document.querySelector('.cat-item');
    if (primerBtn) seleccionarCategoria(parseInt(primerBtn.dataset.catId), primerBtn.querySelector('.cat-nombre').textContent.trim());
});
</script>
@endpush

@endsection
