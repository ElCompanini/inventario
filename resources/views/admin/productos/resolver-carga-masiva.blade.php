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
        <div class="bg-white rounded-xl shadow border-l-4 border-orange-400 p-5">

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
                       class="mt-0.5 radio-accion" data-idx="{{ $i }}"
                       {{ $c['similitud'] >= 60 ? 'checked' : '' }}
                       onchange="toggleNuevo({{ $i }}, false)">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-indigo-700">Enlazar al producto más parecido</p>
                    <p class="text-xs text-gray-600 mt-0.5">{{ $c['sugerencia_nombre'] }}</p>
                    <input type="hidden" name="resoluciones[{{ $i }}][producto_id]"
                           value="{{ $c['sugerencia_id'] }}" class="input-sugerencia-{{ $i }}">
                </div>
                <span class="text-xs text-indigo-500 font-medium shrink-0">{{ $c['similitud'] }}% similitud</span>
            </label>
            @endif

            {{-- Opción 2: Enlazar a otro producto --}}
            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-blue-50
                          hover:border-blue-300 cursor-pointer transition mb-2">
                <input type="radio" name="resoluciones[{{ $i }}][accion]" value="enlazar"
                       class="mt-0.5 radio-otro" data-idx="{{ $i }}"
                       onchange="toggleNuevo({{ $i }}, false)">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-blue-700">Enlazar a otro producto</p>
                    <select class="mt-1.5 w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs
                                   focus:outline-none focus:ring-2 focus:ring-blue-400 select-otro-{{ $i }}"
                            data-idx="{{ $i }}"
                            onchange="onSelectOtro({{ $i }}, this.value)">
                        <option value="">— Selecciona un producto —</option>
                        @foreach($productos as $p)
                            <option value="{{ $p->id }}"
                                {{ ($c['sugerencia_id'] ?? null) == $p->id ? 'selected' : '' }}>
                                {{ $p->descripcion }} ({{ $p->nombre }})
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="resoluciones[{{ $i }}][producto_id]"
                           value="" class="input-otro-{{ $i }}">
                </div>
            </label>

            {{-- Opción 3: Crear como nuevo producto --}}
            <div class="rounded-lg border border-gray-200 mb-1 overflow-hidden">
                <label class="flex items-center gap-3 p-3 hover:bg-emerald-50 hover:border-emerald-300 cursor-pointer transition">
                    <input type="radio" name="resoluciones[{{ $i }}][accion]" value="nuevo"
                           class="radio-nuevo-{{ $i }}"
                           {{ $c['similitud'] < 60 && empty($c['sugerencia_id']) ? 'checked' : '' }}
                           onchange="toggleNuevo({{ $i }}, true)">
                    <div>
                        <p class="text-sm font-semibold text-emerald-700">Crear como nuevo producto</p>
                        <p class="text-xs text-gray-400 mt-0.5">Se agrega a una categoría existente con la descripción del Excel.</p>
                    </div>
                </label>

                <div id="panel-nuevo-{{ $i }}"
                     class="{{ $c['similitud'] < 60 && empty($c['sugerencia_id']) ? '' : 'hidden' }} border-t border-gray-100 bg-emerald-50 p-3 space-y-2">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Categoría (familia) <span class="text-red-500">*</span>
                        </label>
                        <select name="resoluciones[{{ $i }}][nuevo_nombre]"
                                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            <option value="">— Selecciona —</option>
                            @foreach($familias as $f)
                                <option value="{{ $f }}">{{ $f }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-xs text-gray-400">El contenedor se elige en el paso siguiente.</p>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Descripción del producto
                        </label>
                        <input type="text" name="resoluciones[{{ $i }}][nuevo_descripcion]"
                               value="{{ $c['descripcion'] }}"
                               class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
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
    function toggleNuevo(idx, show) {
        const panel = document.getElementById('panel-nuevo-' + idx);
        if (show) {
            panel.classList.remove('hidden');
        } else {
            panel.classList.add('hidden');
        }
    }

    function onSelectOtro(idx, value) {
        // Marcar el radio "otro"
        document.querySelector('.radio-otro[data-idx="' + idx + '"]').checked = true;
        toggleNuevo(idx, false);

        // Actualizar el hidden input
        document.querySelector('.input-otro-' + idx).value = value;

        // Desactivar el hidden de sugerencia para que no pise
        const sug = document.querySelector('.input-sugerencia-' + idx);
        if (sug) sug.disabled = true;
    }

    // Al marcar "enlazar sugerencia", reactivar su hidden y desactivar el del otro
    document.querySelectorAll('.radio-accion').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const idx = this.dataset.idx;
            const sug = document.querySelector('.input-sugerencia-' + idx);
            const otro = document.querySelector('.input-otro-' + idx);
            if (sug) sug.disabled = false;
            if (otro) otro.value = '';
        });
    });
</script>
@endpush

@endsection
