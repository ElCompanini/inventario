@extends('layouts.app')
@section('title', 'Resolver conflictos — Carga masiva')

@section('content')

<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Resolver conflictos — Carga masiva</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ count($pendiente['conflictos']) }} producto(s) del Excel no coinciden exactamente con la base de datos.
            Decide qué hacer con cada uno antes de confirmar.
        </p>
    </div>
    <a href="{{ route('dashboard') }}"
       class="text-sm text-indigo-600 hover:underline font-medium mt-1">← Cancelar y volver</a>
</div>

{{-- Exactos --}}
@if(count($pendiente['exactos']) > 0)
<div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
    <p class="text-sm font-semibold text-green-700 mb-2">
        ✓ {{ count($pendiente['exactos']) }} producto(s) enlazados automáticamente (coincidencia exacta)
    </p>
    <ul class="text-xs text-green-600 space-y-0.5 list-disc list-inside">
        @foreach($pendiente['exactos'] as $e)
            <li>{{ $e['descripcion'] }} <span class="text-green-400">(× {{ $e['cantidad'] }})</span></li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.productos.carga.masiva.confirmar') }}">
    @csrf

    <div class="space-y-4 mb-6">
        @foreach($pendiente['conflictos'] as $i => $c)
        @php
            $autoEnlazar = $c['similitud'] >= 60 && !empty($c['sugerencia_id']);
            $autoNuevo   = $c['similitud'] < 60 && empty($c['sugerencia_id']);
            // Valor inicial del producto_id único
            $initPid     = $autoEnlazar ? $c['sugerencia_id'] : '';
        @endphp
        <div class="bg-white rounded-xl shadow border-l-4 border-orange-400 p-5">

            {{-- ── Hidden único para producto_id ── --}}
            <input type="hidden" name="resoluciones[{{ $i }}][producto_id]"
                   value="{{ $initPid }}" id="input-pid-{{ $i }}">

            {{-- Cabecera --}}
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <p class="text-sm font-bold text-gray-800">{{ $c['descripcion'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $c['unidad'] ?? '—' }} · Cant: {{ $c['cantidad'] }}
                        @if(!empty($c['precioNeto'])) · ${{ number_format($c['precioNeto'], 0, ',', '.') }} @endif
                    </p>
                </div>
                @if($c['similitud'] > 0)
                    <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full
                        {{ $c['similitud'] >= 70 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-600' }}">
                        {{ $c['similitud'] }}% similitud
                    </span>
                @endif
            </div>

            {{-- Opción 1: Enlazar a sugerencia --}}
            @if(!empty($c['sugerencia_id']))
            <label class="flex items-center gap-3 py-3 pr-4 rounded-lg border border-gray-200 hover:bg-indigo-50
                          hover:border-indigo-300 cursor-pointer transition mb-2" style="padding-left:0.75rem;">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                       class="shrink-0 accent-indigo-600" data-idx="{{ $i }}" data-tipo="sugerencia"
                       data-pid="{{ $c['sugerencia_id'] }}"
                       {{ $autoEnlazar ? 'checked' : '' }}
                       onchange="onRadioChange({{ $i }}, 'sugerencia', {{ $c['sugerencia_id'] }})">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-indigo-700">Enlazar al producto más parecido</p>
                    <p class="text-xs text-gray-600 mt-0.5">{{ $c['sugerencia_nombre'] }}</p>
                </div>
                <span class="text-xs text-indigo-500 font-medium shrink-0 mr-3">{{ $c['similitud'] }}% similitud</span>
            </label>
            @endif

            {{-- Opción 2: Enlazar a otro producto --}}
            <label class="flex items-center gap-3 py-3 pr-4 rounded-lg border border-gray-200 hover:bg-blue-50
                          hover:border-blue-300 cursor-pointer transition mb-2" style="padding-left:0.75rem;">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                       class="shrink-0 accent-indigo-600" data-idx="{{ $i }}" data-tipo="otro"
                       onchange="onRadioChange({{ $i }}, 'otro', 0)">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-blue-700">Enlazar a otro producto</p>
                    <select id="select-otro-{{ $i }}"
                            class="mt-1.5 w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs
                                   focus:outline-none focus:ring-2 focus:ring-blue-400"
                            onchange="onSelectOtro({{ $i }}, this.value)">
                        <option value="">— Selecciona un producto —</option>
                        @foreach($productos as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </label>

            {{-- Opción 3: Crear como nuevo producto --}}
            <label class="flex items-center gap-3 py-3 pr-4 rounded-lg border border-gray-200 hover:bg-emerald-50 hover:border-emerald-300 cursor-pointer transition" style="padding-left:0.75rem;">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="nuevo"
                       class="shrink-0 accent-indigo-600" data-idx="{{ $i }}" data-tipo="nuevo"
                       {{ $autoNuevo ? 'checked' : '' }}
                       onchange="onRadioChange({{ $i }}, 'nuevo', 0)">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-emerald-700">Crear como nuevo producto</p>
                    <p class="text-xs text-gray-400 mt-0.5">Se agrega a una categoría existente con la descripción del Excel.</p>
                    <div id="panel-nuevo-{{ $i }}" class="{{ $autoNuevo ? '' : 'hidden' }} mt-6 flex items-center gap-3 flex-wrap">
                        <button type="button" onclick="resolverAbrirModal({{ $i }}, '{{ addslashes($c['descripcion']) }}')"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-white px-3 py-1.5 rounded-lg shadow-sm"
                                style="background:#2563eb; transition:background .15s, transform .1s;"
                                onmouseover="this.style.background='#1d4ed8'; this.style.transform='translateY(-1px)';"
                                onmouseout="this.style.background='#2563eb'; this.style.transform='';"
                                onmousedown="this.style.transform='scale(.97)';"
                                onmouseup="this.style.transform='translateY(-1px)';">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Ingresar nuevo producto
                        </button>
                        <span id="resolver-resumen-{{ $i }}" class="text-xs text-emerald-700 font-medium bg-emerald-50 px-2 py-1 rounded-md border border-emerald-200 hidden"></span>
                    </div>
                    <input type="hidden" name="resoluciones[{{ $i }}][nuevo_categoria_id]" id="resolver-cat-hidden-{{ $i }}" value="">
                    <input type="hidden" name="resoluciones[{{ $i }}][nuevo_stock_minimo]"  id="resolver-min-hidden-{{ $i }}"  value="0">
                    <input type="hidden" name="resoluciones[{{ $i }}][nuevo_stock_critico]" id="resolver-crit-hidden-{{ $i }}" value="0">
                </div>
            </label>

        </div>
        @endforeach
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('dashboard') }}"
           class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
            Confirmar carga →
        </button>
    </div>
</form>

{{-- Modal nuevo producto --}}
<div id="resolver-modal-nuevo" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,.5); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:480px; max-width:calc(100vw - 2rem); max-height:85vh; overflow-y:auto; animation:resolverIn .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.25rem 1.25rem 0; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;">
            <div>
                <p style="font-size:1rem; font-weight:700; color:#1f2937; margin:0;">Nuevo producto</p>
                <p id="resolver-modal-nombre" style="font-size:0.8rem; color:#374151; margin:0.2rem 0 0; font-weight:500;"></p>
            </div>
            <button onclick="resolverCerrarModal()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1.25rem;line-height:1;flex-shrink:0;">✕</button>
        </div>
        <div style="padding:1rem 1.25rem;">
            <p style="font-size:0.75rem; font-weight:600; color:#374151; margin:0 0 0.5rem;">Familia <span style="color:#ef4444;">*</span></p>
            <div id="resolver-modal-familias" style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-bottom:0.5rem;"></div>
            <div id="resolver-nueva-fam-wrap" style="display:flex; gap:0.4rem; align-items:center; margin-bottom:1rem;">
                <button type="button" onclick="resolverToggleNuevaFam()"
                        style="font-size:0.75rem; font-weight:600; color:#7c3aed; background:none; border:none; cursor:pointer; padding:0;">+ Nueva familia</button>
                <span id="resolver-nueva-fam-form" style="display:none; gap:0.4rem; align-items:center;">
                    <input type="text" id="resolver-nueva-fam-input" placeholder="Nombre familia"
                           style="border:1px solid #d1d5db; border-radius:0.375rem; padding:3px 8px; font-size:0.78rem; outline:none; width:150px;">
                    <button type="button" onclick="resolverCrearFamilia()"
                            style="font-size:0.75rem; font-weight:600; background:#7c3aed; color:#fff; border:none; border-radius:0.375rem; padding:3px 10px; cursor:pointer;">Crear</button>
                    <button type="button" onclick="resolverToggleNuevaFam()"
                            style="font-size:0.75rem; color:#9ca3af; background:none; border:none; cursor:pointer;">✕</button>
                </span>
            </div>
            <div id="resolver-modal-cat-wrap" style="display:none; margin-bottom:1rem;">
                <p style="font-size:0.75rem; font-weight:600; color:#374151; margin:0 0 0.5rem;">Categoría <span style="color:#ef4444;">*</span></p>
                <div id="resolver-modal-categorias" style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-bottom:0.5rem;"></div>
                <div style="display:flex; gap:0.4rem; align-items:center;">
                    <button type="button" onclick="resolverToggleNuevaCat()"
                            style="font-size:0.75rem; font-weight:600; color:#7c3aed; background:none; border:none; cursor:pointer; padding:0;">+ Nueva categoría</button>
                    <span id="resolver-nueva-cat-form" style="display:none; gap:0.4rem; align-items:center;">
                        <input type="text" id="resolver-nueva-cat-input" placeholder="Nombre categoría"
                               style="border:1px solid #d1d5db; border-radius:0.375rem; padding:3px 8px; font-size:0.78rem; outline:none; width:150px;">
                        <button type="button" onclick="resolverCrearCategoria()"
                                style="font-size:0.75rem; font-weight:600; background:#7c3aed; color:#fff; border:none; border-radius:0.375rem; padding:3px 10px; cursor:pointer;">Crear</button>
                        <button type="button" onclick="resolverToggleNuevaCat()"
                                style="font-size:0.75rem; color:#9ca3af; background:none; border:none; cursor:pointer;">✕</button>
                    </span>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">Stock mínimo <span style="color:#ef4444;">*</span></label>
                    <input type="number" id="resolver-modal-minimo" min="0" value="0"
                           style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.6rem; font-size:0.875rem; outline:none; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.3rem;">Stock crítico <span style="color:#ef4444;">*</span></label>
                    <input type="number" id="resolver-modal-critico" min="0" value="0"
                           style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.6rem; font-size:0.875rem; outline:none; box-sizing:border-box;">
                </div>
            </div>
            <div id="resolver-modal-error" style="display:none; font-size:0.8rem; color:#dc2626; background:#fef2f2; border-radius:0.375rem; padding:0.4rem 0.6rem; margin-bottom:0.75rem;"></div>
        </div>
        <div style="padding:0.75rem 1.25rem 1.25rem; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:0.5rem;">
            <button type="button" onclick="resolverCerrarModal()"
                    style="padding:0.45rem 1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:0.5rem; cursor:pointer;">
                Cancelar
            </button>
            <button type="button" onclick="resolverConfirmarModal()"
                    style="padding:0.45rem 1.1rem; font-size:0.875rem; font-weight:600; color:#fff; background:#7c3aed; border:none; border-radius:0.5rem; cursor:pointer;">
                Confirmar
            </button>
        </div>
    </div>
</div>

@push('head')
<style>
@keyframes resolverIn { from{opacity:0;transform:scale(.95) translateY(-8px)} to{opacity:1;transform:none} }
</style>
@endpush

@push('scripts')
<script>
var resolverFamilias = {!! json_encode($familias->map(fn($f) => [
    'id'         => $f->id,
    'nombre'     => $f->nombre,
    'categorias' => $f->categorias->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])->values(),
])->values(), JSON_HEX_TAG | JSON_HEX_AMP) !!};

var _resolverModalIdx   = null;
var _resolverFamiliaId  = null;
var _resolverCatId      = null;

function resolverAbrirModal(idx, nombre) {
    _resolverModalIdx  = idx;
    _resolverFamiliaId = null;
    _resolverCatId     = null;
    document.getElementById('resolver-modal-nombre').textContent = nombre;
    document.getElementById('resolver-modal-error').style.display = 'none';
    document.getElementById('resolver-modal-cat-wrap').style.display = 'none';
    document.getElementById('resolver-modal-minimo').value  = document.getElementById('resolver-min-hidden-'  + idx).value || '0';
    document.getElementById('resolver-modal-critico').value = document.getElementById('resolver-crit-hidden-' + idx).value || '0';
    resolverRenderFamilias();
    document.getElementById('resolver-modal-nuevo').style.display = 'flex';
}

function resolverCerrarModal() {
    document.getElementById('resolver-modal-nuevo').style.display = 'none';
}

function resolverRenderFamilias() {
    var cont = document.getElementById('resolver-modal-familias');
    cont.innerHTML = '';
    resolverFamilias.forEach(function(f) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = f.nombre;
        var sel = f.id === _resolverFamiliaId;
        btn.style.cssText = 'font-size:0.8rem;font-weight:600;padding:0.35rem 0.85rem;border-radius:0.5rem;border:1px solid ' + (sel ? '#7c3aed;background:#7c3aed;color:#fff' : '#d1d5db;background:#fff;color:#374151') + ';cursor:pointer;transition:background .15s;';
        btn.onclick = function() { _resolverFamiliaId = f.id; _resolverCatId = null; resolverRenderFamilias(); resolverRenderCategorias(); };
        cont.appendChild(btn);
    });
}

function resolverRenderCategorias() {
    var wrap = document.getElementById('resolver-modal-cat-wrap');
    var cont = document.getElementById('resolver-modal-categorias');
    var fam  = resolverFamilias.find(function(f) { return f.id === _resolverFamiliaId; });
    if (!fam || !fam.categorias.length) { wrap.style.display = 'none'; return; }
    cont.innerHTML = '';
    fam.categorias.forEach(function(c) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = c.nombre;
        var sel = c.id === _resolverCatId;
        btn.style.cssText = 'font-size:0.8rem;font-weight:600;padding:0.35rem 0.85rem;border-radius:0.5rem;border:1px solid ' + (sel ? '#7c3aed;background:#7c3aed;color:#fff' : '#d1d5db;background:#fff;color:#374151') + ';cursor:pointer;transition:background .15s;';
        btn.onclick = function() { _resolverCatId = c.id; resolverRenderCategorias(); };
        cont.appendChild(btn);
    });
    wrap.style.display = 'block';
}

function resolverConfirmarModal() {
    var errDiv = document.getElementById('resolver-modal-error');
    errDiv.style.display = 'none';
    if (!_resolverFamiliaId) { errDiv.textContent = 'Selecciona una familia.'; errDiv.style.display = 'block'; return; }
    if (!_resolverCatId)     { errDiv.textContent = 'Selecciona una categoría.'; errDiv.style.display = 'block'; return; }
    var min  = parseInt(document.getElementById('resolver-modal-minimo').value)  || 0;
    var crit = parseInt(document.getElementById('resolver-modal-critico').value) || 0;
    var idx  = _resolverModalIdx;
    document.getElementById('resolver-cat-hidden-'  + idx).value = _resolverCatId;
    document.getElementById('resolver-min-hidden-'  + idx).value = min;
    document.getElementById('resolver-crit-hidden-' + idx).value = crit;
    var fam  = resolverFamilias.find(function(f) { return f.id === _resolverFamiliaId; });
    var cat  = fam ? fam.categorias.find(function(c) { return c.id === _resolverCatId; }) : null;
    var resumen = document.getElementById('resolver-resumen-' + idx);
    if (resumen) {
        resumen.textContent = '✓ ' + (fam ? fam.nombre : '') + ' › ' + (cat ? cat.nombre : '') + ' · Mín: ' + min + ' / Crít: ' + crit;
        resumen.classList.remove('hidden');
    }
    resolverCerrarModal();
}

function onRadioChange(idx, tipo, pid) {
    document.getElementById('input-pid-' + idx).value = (tipo === 'sugerencia') ? pid : '';
    const panel = document.getElementById('panel-nuevo-' + idx);
    panel.classList.toggle('hidden', tipo !== 'nuevo');
}

function onSelectOtro(idx, value) {
    const radios = document.querySelectorAll('input[name="resoluciones[' + idx + '][accion]"]');
    radios.forEach(function(r) { if (r.dataset.tipo === 'otro') r.checked = true; });
    document.getElementById('input-pid-' + idx).value = value;
    document.getElementById('panel-nuevo-' + idx).classList.add('hidden');
}

document.getElementById('resolver-modal-nuevo').addEventListener('click', function(e) {
    if (e.target === this) resolverCerrarModal();
});

var RESOLVER_CSRF      = '{{ csrf_token() }}';
var RESOLVER_URL_FAM   = '{{ route("admin.catalogo.familias.store") }}';
var RESOLVER_URL_CAT   = '{{ route("admin.catalogo.categorias.store") }}';

function resolverToggleNuevaFam() {
    var form = document.getElementById('resolver-nueva-fam-form');
    var show = form.style.display === 'none' || form.style.display === '';
    form.style.display = show ? 'flex' : 'none';
    if (show) setTimeout(function() { document.getElementById('resolver-nueva-fam-input').focus(); }, 50);
}

function resolverToggleNuevaCat() {
    var form = document.getElementById('resolver-nueva-cat-form');
    var show = form.style.display === 'none' || form.style.display === '';
    form.style.display = show ? 'flex' : 'none';
    if (show) setTimeout(function() { document.getElementById('resolver-nueva-cat-input').focus(); }, 50);
}

function resolverCrearFamilia() {
    var nombre = document.getElementById('resolver-nueva-fam-input').value.trim();
    if (!nombre) return;
    fetch(RESOLVER_URL_FAM, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': RESOLVER_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ nombre: nombre }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok || data.id) {
            resolverFamilias.push({ id: data.id, nombre: data.nombre || nombre, categorias: [] });
            _resolverFamiliaId = data.id;
            _resolverCatId = null;
            resolverRenderFamilias();
            resolverRenderCategorias();
            document.getElementById('resolver-nueva-fam-input').value = '';
            document.getElementById('resolver-nueva-fam-form').style.display = 'none';
        }
    })
    .catch(function() {});
}

function resolverCrearCategoria() {
    var nombre = document.getElementById('resolver-nueva-cat-input').value.trim();
    if (!nombre || !_resolverFamiliaId) return;
    fetch(RESOLVER_URL_CAT, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': RESOLVER_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ nombre: nombre, familia_id: _resolverFamiliaId }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok || data.id) {
            var fam = resolverFamilias.find(function(f) { return f.id === _resolverFamiliaId; });
            if (fam) fam.categorias.push({ id: data.id, nombre: data.nombre || nombre });
            _resolverCatId = data.id;
            resolverRenderCategorias();
            document.getElementById('resolver-nueva-cat-input').value = '';
            document.getElementById('resolver-nueva-cat-form').style.display = 'none';
        }
    })
    .catch(function() {});
}

document.getElementById('resolver-nueva-fam-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') resolverCrearFamilia();
});
document.getElementById('resolver-nueva-cat-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') resolverCrearCategoria();
});
</script>
@endpush

@endsection
