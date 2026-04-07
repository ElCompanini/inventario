@extends('layouts.app')

@section('title', 'Nueva Orden de Compra')

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.ordenes.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a Órdenes</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-1">Nueva Orden de Compra</h1>
    <p class="text-sm text-gray-500 mt-1">Ingresa el número de OC y selecciona los SICDs pendientes que agrupa.</p>
</div>

@if($sicdsPendientes->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
        <p class="text-amber-700 font-medium">No hay SICDs pendientes de agrupar.</p>
        <a href="{{ route('admin.sicd.create') }}" class="mt-3 inline-block text-indigo-600 hover:underline text-sm">Crear un SICD primero →</a>
    </div>
@else
    <form method="POST" action="{{ route('admin.ordenes.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="bg-white rounded-xl shadow p-6 space-y-5">

            {{-- Archivo OC: se sube primero y lee el N° automáticamente --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Archivo OC <span class="text-gray-400 text-xs">(PDF — detecta el N° automáticamente)</span>
                </label>
                {{-- Input oculto + zona de selección --}}
                <input type="file" id="input-archivo-oc" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                <input type="hidden" id="archivo-oc-temp" name="archivo_oc_temp">

                <div style="display:flex; align-items:center; gap:0.75rem;">
                    {{-- Seleccionar archivo --}}
                    <label for="input-archivo-oc"
                           style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.4rem 1rem; font-size:0.8rem; font-weight:600; border:1.5px solid #a5b4fc; border-radius:0.5rem; color:#4f46e5; background:#eef2ff; cursor:pointer; white-space:nowrap; transition:background .2s;">
                        Seleccionar archivo
                    </label>
                    <span id="nombre-archivo-oc" style="font-size:0.78rem; color:#9ca3af; font-style:italic; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        Ningún archivo seleccionado
                    </span>
                    {{-- Subir archivo --}}
                    <button type="button" id="btn-subir-oc" disabled
                            style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.4rem 1rem; font-size:0.8rem; font-weight:600; color:#fff; background:#9ca3af; border:none; border-radius:0.5rem; cursor:not-allowed; transition:background .2s, box-shadow .2s, transform .15s; white-space:nowrap;">
                        <svg style="width:0.85rem;height:0.85rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span id="btn-subir-oc-texto">Subir archivo</span>
                    </button>
                </div>

                <div id="oc-estado" style="margin-top:6px; font-size:0.75rem; display:none;"></div>
                <div id="oc-leyendo" style="display:none; font-size:0.75rem; color:#6366f1; margin-top:4px;">
                    ⏳ Leyendo PDF...
                </div>
                @error('archivo_oc')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Número OC --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Número de OC <span class="text-red-500">*</span>
                </label>
                <div style="position:relative;">
                    <input type="text" id="campo-numero-oc" name="numero_oc" value="{{ old('numero_oc') }}"
                           placeholder="Se detecta al subir el PDF, o escribe manualmente"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('numero_oc') border-red-400 @enderror">
                    <span id="oc-detectado-badge"
                          style="display:none; position:absolute; right:0.5rem; top:50%; transform:translateY(-50%); font-size:0.65rem; font-weight:700; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:9999px;">
                        ✓ detectado
                    </span>
                </div>
                @error('numero_oc')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- SICDs pendientes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    SICDs a agrupar <span class="text-red-500">*</span>
                </label>
                @error('sicd_ids')
                    <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                @enderror

                <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-96 overflow-y-auto">
                    @foreach($sicdsPendientes as $sicd)
                        <label class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="sicd_ids[]" value="{{ $sicd->id }}"
                                   {{ in_array($sicd->id, old('sicd_ids', [])) ? 'checked' : '' }}
                                   class="mt-0.5 w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-mono font-semibold text-indigo-700">{{ $sicd->codigo_sicd }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $sicd->detalles->count() }} producto(s) ·
                                    Creado {{ $sicd->created_at->format('d/m/Y') }} por {{ $sicd->usuario->name }}
                                </p>
                                @if($sicd->descripcion)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $sicd->descripcion }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-end gap-3">
            <a href="{{ route('admin.ordenes.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                Crear OC →
            </button>
        </div>
    </form>
@endif

@push('scripts')
<script>
    const inputArchivo = document.getElementById('input-archivo-oc');
    const btnSubir     = document.getElementById('btn-subir-oc');

    // Al seleccionar el archivo: mostrar nombre y habilitar botón
    inputArchivo.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        document.getElementById('nombre-archivo-oc').textContent     = file.name;
        document.getElementById('nombre-archivo-oc').style.color     = '#374151';
        document.getElementById('nombre-archivo-oc').style.fontStyle = 'normal';
        document.getElementById('oc-estado').style.display           = 'none';
        document.getElementById('archivo-oc-temp').value             = '';
        document.getElementById('oc-detectado-badge').style.display  = 'none';

        const campo = document.getElementById('campo-numero-oc');
        campo.style.borderColor = '';
        campo.style.background  = '';

        btnSubir.disabled         = false;
        btnSubir.style.background = '#4f46e5';
        btnSubir.style.cursor     = 'pointer';
    });

    // Efecto hover del botón (solo cuando habilitado)
    btnSubir.addEventListener('mouseenter', function () {
        if (!this.disabled) {
            this.style.background  = '#818cf8';
            this.style.boxShadow   = '0 0 10px 2px rgba(99,102,241,0.45)';
            this.style.transform   = 'scale(1.04)';
        }
    });
    btnSubir.addEventListener('mouseleave', function () {
        if (!this.disabled) {
            this.style.background = '#4f46e5';
            this.style.boxShadow  = 'none';
            this.style.transform  = 'scale(1)';
        }
    });

    // Al hacer clic en "Subir archivo": AJAX al servidor
    btnSubir.addEventListener('click', function () {
        const file = inputArchivo.files[0];
        if (!file) return;

        const estado = document.getElementById('oc-estado');
        const texto  = document.getElementById('btn-subir-oc-texto');

        btnSubir.disabled = true;
        btnSubir.style.background = '#6366f1';
        texto.textContent = 'Subiendo...';

        const formData = new FormData();
        formData.append('archivo_oc', file);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("admin.ordenes.subir.temp") }}', {
            method: 'POST',
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('archivo-oc-temp').value = data.temp_path;
            btnSubir.style.background = '#16a34a';
            texto.textContent = '✓ Subido';
            estado.style.display = 'block';
            estado.style.color   = '#15803d';
            estado.textContent   = '✓ Archivo subido: ' + data.nombre;

            // Rellenar número OC desde servidor (pdftotext)
            if (data.numero_oc) {
                const campo = document.getElementById('campo-numero-oc');
                campo.value             = data.numero_oc;
                campo.style.borderColor = '#22c55e';
                campo.style.background  = '#f0fdf4';
                document.getElementById('oc-detectado-badge').style.display = 'block';
            }
        })
        .catch(() => {
            btnSubir.disabled = false;
            btnSubir.style.background = '#4f46e5';
            texto.textContent = 'Subir archivo';
            estado.style.display = 'block';
            estado.style.color   = '#dc2626';
            estado.textContent   = '✗ Error al subir el archivo.';
        });
    });
</script>
@endpush

@endsection
