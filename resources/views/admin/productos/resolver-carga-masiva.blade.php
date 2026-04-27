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
            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-indigo-50
                          hover:border-indigo-300 cursor-pointer transition mb-2">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                       class="mt-0.5 accent-indigo-600" data-idx="{{ $i }}" data-tipo="sugerencia"
                       data-pid="{{ $c['sugerencia_id'] }}"
                       {{ $autoEnlazar ? 'checked' : '' }}
                       onchange="onRadioChange({{ $i }}, 'sugerencia', {{ $c['sugerencia_id'] }})">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-indigo-700">Enlazar al producto más parecido</p>
                    <p class="text-xs text-gray-600 mt-0.5">{{ $c['sugerencia_nombre'] }}</p>
                </div>
                <span class="text-xs text-indigo-500 font-medium shrink-0">{{ $c['similitud'] }}% similitud</span>
            </label>
            @endif

            {{-- Opción 2: Enlazar a otro producto --}}
            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-blue-50
                          hover:border-blue-300 cursor-pointer transition mb-2">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                       class="mt-0.5 accent-indigo-600" data-idx="{{ $i }}" data-tipo="otro"
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
            <div class="rounded-lg border border-gray-200 mb-1 overflow-hidden">
                <label class="flex items-center gap-3 p-3 hover:bg-emerald-50 hover:border-emerald-300 cursor-pointer transition">
                    <input type="radio" name="resoluciones[{{ $i }}][accion]" value="nuevo"
                           class="accent-indigo-600" data-idx="{{ $i }}" data-tipo="nuevo"
                           {{ $autoNuevo ? 'checked' : '' }}
                           onchange="onRadioChange({{ $i }}, 'nuevo', 0)">
                    <div>
                        <p class="text-sm font-semibold text-emerald-700">Crear como nuevo producto</p>
                        <p class="text-xs text-gray-400 mt-0.5">Se agrega a una categoría existente con la descripción del Excel.</p>
                    </div>
                </label>

                <div id="panel-nuevo-{{ $i }}"
                     class="{{ $autoNuevo ? '' : 'hidden' }} border-t border-gray-100 bg-indigo-50 p-3 space-y-2">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Familia <span class="text-red-500">*</span></label>
                        <select id="resolver-fam-select-{{ $i }}"
                                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                onchange="resolverOnFamilia({{ $i }}, this.value)">
                            <option value="">— Selecciona una familia —</option>
                            @foreach($familias as $fam)
                                <option value="{{ $fam->id }}">{{ $fam->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="resolver-cat-wrap-{{ $i }}" style="display:none;">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Categoría <span class="text-red-500">*</span></label>
                        <select id="resolver-cat-select-{{ $i }}"
                                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                onchange="document.getElementById('resolver-cat-hidden-{{ $i }}').value = this.value">
                            <option value="">— Selecciona una categoría —</option>
                        </select>
                        <input type="hidden" name="resoluciones[{{ $i }}][nuevo_categoria_id]" id="resolver-cat-hidden-{{ $i }}" value="">
                    </div>
                    <p class="text-xs text-gray-400">El contenedor se elige en el paso siguiente.</p>

                </div>
            </div>

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

@push('scripts')
<script>
var resolverFamilias = {!! json_encode($familias->map(fn($f) => [
    'id'         => $f->id,
    'nombre'     => $f->nombre,
    'categorias' => $f->categorias->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])->values(),
])->values(), JSON_HEX_TAG | JSON_HEX_AMP) !!};

function resolverOnFamilia(idx, familiaId) {
    var wrap   = document.getElementById('resolver-cat-wrap-' + idx);
    var select = document.getElementById('resolver-cat-select-' + idx);
    var hidden = document.getElementById('resolver-cat-hidden-' + idx);
    hidden.value = '';
    select.innerHTML = '<option value="">— Selecciona una categoría —</option>';

    var fam = resolverFamilias.find(function(f) { return f.id == familiaId; });
    if (!fam || !fam.categorias.length) { wrap.style.display = 'none'; return; }

    fam.categorias.forEach(function(c) {
        var opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.nombre;
        select.appendChild(opt);
    });
    wrap.style.display = 'block';
}

    function onRadioChange(idx, tipo, pid) {
        // Actualizar el único hidden de producto_id
        document.getElementById('input-pid-' + idx).value = (tipo === 'sugerencia') ? pid : '';
        // Mostrar/ocultar panel nuevo
        const panel = document.getElementById('panel-nuevo-' + idx);
        panel.classList.toggle('hidden', tipo !== 'nuevo');
    }

    function onSelectOtro(idx, value) {
        // Marcar el radio "otro" y actualizar el hidden
        const radios = document.querySelectorAll('input[name="resoluciones[' + idx + '][accion]"]');
        radios.forEach(function(r) {
            if (r.dataset.tipo === 'otro') r.checked = true;
        });
        document.getElementById('input-pid-' + idx).value = value;
        document.getElementById('panel-nuevo-' + idx).classList.add('hidden');
    }
</script>
@endpush

@endsection
