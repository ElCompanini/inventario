@extends('layouts.app')

@section('title', 'Nuevo SICD')

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.sicd.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a SICD</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-1">Nuevo SICD</h1>
    <p class="text-sm text-gray-500 mt-1">
        Sube el documento SICD y el Excel con el detalle de productos.<br>
        El código se detectará del nombre del archivo (ej: <span class="font-mono">TIC(RAMO)-12345_descripcion.pdf</span>).
        Ingrésalo sin barra: <span class="font-mono">TIC(RAMO)12345</span> — el sistema la agrega automáticamente.
    </p>
</div>

<form method="POST" action="{{ route('admin.sicd.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="bg-white rounded-xl shadow p-6 space-y-5">

        {{-- Archivo SICD --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Documento SICD <span class="text-red-500">*</span>
            </label>
            <input type="file" id="archivo_sicd" name="archivo_sicd" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                   onchange="
                       document.getElementById('label-sicd').textContent = this.files[0]?.name ?? 'Ningún archivo seleccionado';
                       const filename = this.files[0]?.name || '';
                       const input = document.getElementById('codigo_sicd');
                       const feedback = document.getElementById('sicd_feedback');
                       const match = filename.match(/TIC\(([^)]+)\)[-_]?(\d+)/i);
                       if (match) {
                           input.value = 'TIC(' + match[1].toUpperCase() + ')' + match[2];
                           feedback.textContent = '✓ Código detectado: ' + input.value + '. Puedes editarlo si es incorrecto.';
                           feedback.className = 'text-xs mt-1 text-green-600';
                       } else if (filename) {
                           input.value = '';
                           feedback.textContent = '⚠ No se detectó código TIC. Ingrésalo manualmente (ej: TIC(RAMO)12345).';
                           feedback.className = 'text-xs mt-1 text-amber-600';
                       }
                   ">
            <label for="archivo_sicd"
                   class="flex items-center justify-center gap-2 w-full py-2.5 text-sm font-semibold border-2 border-dashed border-indigo-300 text-indigo-600 rounded-lg cursor-pointer hover:bg-indigo-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Seleccionar documento SICD
            </label>
            <p id="label-sicd" class="text-xs text-gray-400 text-center mt-1 truncate">Ningún archivo seleccionado</p>
            @error('archivo_sicd')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Archivo Excel --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Excel con detalle de productos <span class="text-red-500">*</span>
            </label>
            <p class="text-xs text-gray-400 mb-2">
                Col A: Descripción &nbsp;·&nbsp; Col B: Unidad &nbsp;·&nbsp; Col C: Cantidad solicitada &nbsp;·&nbsp; Col D: Precio neto &nbsp;·&nbsp; Col E: Total neto &nbsp;·&nbsp; Fila 1: cabeceras (se omite)
            </p>
            <input type="file" id="input-excel" name="archivo_excel" accept=".xlsx,.xls,.csv" class="hidden"
                   onchange="document.getElementById('label-excel').textContent = this.files[0]?.name ?? 'Ningún archivo seleccionado'">
            <label for="input-excel"
                   class="flex items-center justify-center gap-2 w-full py-2.5 text-sm font-semibold border-2 border-dashed border-green-300 text-green-700 rounded-lg cursor-pointer hover:bg-green-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Seleccionar archivo Excel
            </label>
            <p id="label-excel" class="text-xs text-gray-400 text-center mt-1 truncate">Ningún archivo seleccionado</p>
            @error('archivo_excel')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Código SICD --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Código SICD <span class="text-red-500">*</span>
            </label>
            <input type="text" id="codigo_sicd" name="codigo_sicd"
                   value="{{ old('codigo_sicd') }}"
                   placeholder="TIC(RAMO)NUMERO — se detecta del archivo"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('codigo_sicd') border-red-400 @enderror">
            <p id="sicd_feedback" class="text-xs mt-1 text-gray-400">
                Selecciona el archivo SICD para detectar el código automáticamente.
            </p>
            @error('codigo_sicd')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Descripción --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <input type="text" name="descripcion" value="{{ old('descripcion') }}"
                   placeholder="Opcional..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
    </div>

    <div class="mt-4 flex justify-end gap-3">
        <a href="{{ route('admin.sicd.index') }}"
           class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
            Crear SICD →
        </button>
    </div>
</form>


@endsection
