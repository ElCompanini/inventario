@extends('layouts.app')
@section('title', 'Resolver conflictos SICD')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Resolver conflictos — {{ $pendiente['codigo'] }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ count($pendiente['conflictos']) }} producto(s) del Excel no coinciden exactamente con la base de datos.
            Decide qué hacer con cada uno antes de confirmar.
        </p>
    </div>
    <a href="{{ route('admin.sicd.create') }}"
       class="text-sm text-indigo-600 hover:underline font-medium">← Cancelar y volver</a>
</div>

{{-- Exactos (referencia) --}}
@if(count($pendiente['exactos']) > 0)
<div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
    <p class="text-sm font-semibold text-green-700 mb-2">
        ✓ {{ count($pendiente['exactos']) }} producto(s) enlazados automáticamente (coincidencia exacta)
    </p>
    <ul class="text-xs text-green-600 space-y-0.5 list-disc list-inside">
        @foreach($pendiente['exactos'] as $e)
            <li>{{ $e['descripcion'] }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Formulario de resolución --}}
<form method="POST" action="{{ route('admin.sicd.confirmar') }}">
    @csrf

    <div class="space-y-4 mb-6">
        @foreach($pendiente['conflictos'] as $i => $c)
        <div class="bg-white rounded-xl shadow border-l-4 border-orange-400 p-5">

            {{-- Encabezado --}}
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <p class="text-sm font-bold text-gray-800">{{ $c['descripcion'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $c['unidad'] ?? '—' }} · Cant: {{ $c['cantidad'] }}
                        @if($c['precioNeto']) · ${{ number_format($c['precioNeto'], 0, ',', '.') }} @endif
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
            @if($c['sugerencia_id'])
            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-indigo-50
                          hover:border-indigo-300 cursor-pointer transition mb-2 opcion-radio"
                   data-idx="{{ $i }}">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                       class="mt-0.5 radio-accion" data-idx="{{ $i }}"
                       {{ $c['similitud'] >= 60 ? 'checked' : '' }}>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-indigo-700">
                        Enlazar al producto más parecido
                    </p>
                    <p class="text-xs text-gray-600 mt-0.5">{{ $c['sugerencia_nombre'] }}</p>
                    <input type="hidden" name="resoluciones[{{ $i }}][producto_id]"
                           value="{{ $c['sugerencia_id'] }}" class="input-sugerencia">
                </div>
                <span class="text-xs text-indigo-500 font-medium">{{ $c['similitud'] }}% similitud</span>
            </label>
            @endif

            {{-- Opción 2: Enlazar a otro producto (dropdown con filtro cascade) --}}
            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-blue-50
                          hover:border-blue-300 cursor-pointer transition mb-2 opcion-radio"
                   data-idx="{{ $i }}">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                       class="mt-0.5 radio-otro" data-idx="{{ $i }}">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-blue-700">Enlazar a otro producto</p>
                    {{-- Filtros cascade --}}
                    <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin:0.4rem 0 0.35rem;">
                        <select class="sicd-sel-familia" data-idx="{{ $i }}"
                                style="border:1px solid #d1d5db; border-radius:0.375rem; padding:0.25rem 0.5rem; font-size:0.72rem; outline:none; background:#fff; color:#374151;">
                            <option value="">— Familia —</option>
                        </select>
                        <select class="sicd-sel-cat" data-idx="{{ $i }}"
                                style="display:none; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.25rem 0.5rem; font-size:0.72rem; outline:none; background:#fff; color:#374151;">
                            <option value="">— Categoría —</option>
                        </select>
                        <select class="sicd-sel-marca" data-idx="{{ $i }}"
                                style="display:none; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.25rem 0.5rem; font-size:0.72rem; outline:none; background:#fff; color:#374151;">
                            <option value="">— Marca —</option>
                        </select>
                    </div>
                    <select name="resoluciones[{{ $i }}][producto_id]"
                            class="mt-0.5 w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs
                                   focus:outline-none focus:ring-2 focus:ring-blue-400 select-otro"
                            data-idx="{{ $i }}"
                            onclick="this.closest('label').querySelector('input[type=radio]').checked=true">
                        <option value="">— Selecciona un producto —</option>
                        @foreach($productos as $p)
                            <option value="{{ $p->id }}"
                                    data-cat="{{ $p->categoria_id }}"
                                    data-marca="{{ $p->marca_id }}"
                                {{ $c['sugerencia_id'] == $p->id ? 'selected' : '' }}>
                                {{ $p->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </label>

            {{-- Opción 3: Ingresar como nuevo --}}
            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50
                          cursor-pointer transition opcion-radio" data-idx="{{ $i }}">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="nuevo"
                       class="mt-0.5" {{ $c['similitud'] < 60 ? 'checked' : '' }}>
                <div>
                    <p class="text-sm font-semibold text-gray-700">Ingresar como nuevo producto</p>
                    <p class="text-xs text-gray-400 mt-0.5">Se creará el detalle sin enlace a un producto existente.</p>
                </div>
            </label>

        </div>
        @endforeach
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.sicd.create') }}"
           class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
            Confirmar y crear SICD
        </button>
    </div>
</form>

@push('scripts')
@php
$sicdFamiliasJson = json_encode(
    $familias->map(fn($f) => [
        'id'         => $f->id,
        'nombre'     => $f->nombre,
        'categorias' => $f->categorias->map(fn($c) => [
            'id'     => $c->id,
            'nombre' => $c->nombre,
            'marcas' => $c->marcas->map(fn($m) => ['id'=>$m->id,'nombre'=>$m->nombre])->values(),
        ])->values(),
    ])->values(),
    JSON_HEX_TAG | JSON_HEX_AMP
);
@endphp
<script type="application/json" id="sicd-familias-data">{!! $sicdFamiliasJson !!}</script>
<script>
var sicdFamilias = JSON.parse(document.getElementById('sicd-familias-data').textContent);

// Inicializar selects de familia para cada fila de conflicto
document.querySelectorAll('.sicd-sel-familia').forEach(function(sel) {
    sicdFamilias.forEach(function(f) {
        var opt = document.createElement('option');
        opt.value = f.id; opt.textContent = f.nombre;
        sel.appendChild(opt);
    });
    sel.addEventListener('change', function() {
        var idx    = this.dataset.idx;
        var famId  = parseInt(this.value) || null;
        var selCat = document.querySelector('.sicd-sel-cat[data-idx="' + idx + '"]');
        var selMarca = document.querySelector('.sicd-sel-marca[data-idx="' + idx + '"]');
        selCat.innerHTML = '<option value="">— Categoría —</option>';
        selMarca.innerHTML = '<option value="">— Marca —</option>';
        selMarca.style.display = 'none';
        sicdFiltrarOpciones(idx, null, null);
        if (!famId) { selCat.style.display = 'none'; return; }
        var familia = sicdFamilias.find(function(f) { return f.id === famId; });
        if (!familia) { selCat.style.display = 'none'; return; }
        familia.categorias.forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.id; opt.textContent = c.nombre;
            selCat.appendChild(opt);
        });
        selCat.style.display = '';
        selCat.value = '';
    });
});

document.querySelectorAll('.sicd-sel-cat').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var idx    = this.dataset.idx;
        var catId  = parseInt(this.value) || null;
        var famId  = parseInt(document.querySelector('.sicd-sel-familia[data-idx="' + idx + '"]').value) || null;
        var selMarca = document.querySelector('.sicd-sel-marca[data-idx="' + idx + '"]');
        selMarca.innerHTML = '<option value="">— Marca —</option>';
        selMarca.style.display = 'none';
        if (!catId) { sicdFiltrarOpciones(idx, null, null); return; }
        var familia = sicdFamilias.find(function(f) { return f.id === famId; });
        var cat = familia ? familia.categorias.find(function(c) { return c.id === catId; }) : null;
        if (cat && cat.marcas && cat.marcas.length > 0) {
            cat.marcas.forEach(function(m) {
                var opt = document.createElement('option');
                opt.value = m.id; opt.textContent = m.nombre;
                selMarca.appendChild(opt);
            });
            selMarca.style.display = '';
            selMarca.value = '';
            sicdFiltrarOpciones(idx, catId, null);
        } else {
            sicdFiltrarOpciones(idx, catId, null);
        }
    });
});

document.querySelectorAll('.sicd-sel-marca').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var idx     = this.dataset.idx;
        var catId   = parseInt(document.querySelector('.sicd-sel-cat[data-idx="' + idx + '"]').value) || null;
        var marcaId = parseInt(this.value) || null;
        sicdFiltrarOpciones(idx, catId, marcaId);
    });
});

function sicdFiltrarOpciones(idx, catId, marcaId) {
    var sel = document.querySelector('.select-otro[data-idx="' + idx + '"]');
    if (!sel) return;
    Array.from(sel.options).forEach(function(opt) {
        if (!opt.value) return; // mantener la opción vacía siempre visible
        var oCat   = parseInt(opt.dataset.cat)   || null;
        var oMarca = parseInt(opt.dataset.marca)  || null;
        var visible = (!catId || oCat === catId) && (!marcaId || oMarca === marcaId);
        opt.hidden   = !visible;
        opt.disabled = !visible;
    });
    // Si la opción actualmente seleccionada quedó oculta, limpiar
    var current = sel.options[sel.selectedIndex];
    if (current && current.hidden) sel.value = '';
}

// Cuando el usuario selecciona un producto del dropdown "Enlazar a otro",
// marca automáticamente ese radio y desmarca el input de sugerencia del grupo
document.querySelectorAll('.select-otro').forEach(function (sel) {
    sel.addEventListener('change', function () {
        var idx   = this.dataset.idx;
        var radio = this.closest('label').querySelector('input[type=radio]');
        radio.checked = true;

        var sugerencia = document.querySelector(
            'input[name="resoluciones[' + idx + '][producto_id]"].input-sugerencia'
        );
        if (sugerencia) sugerencia.disabled = true;

        document.querySelectorAll('.select-otro[data-idx="' + idx + '"]').forEach(function (s) {
            if (s !== sel) s.value = '';
        });
    });
});
</script>
@endpush

@endsection
