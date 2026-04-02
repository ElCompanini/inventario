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

            {{-- Número OC --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Número de OC <span class="text-red-500">*</span>
                </label>
                <input type="text" name="numero_oc" value="{{ old('numero_oc') }}"
                       placeholder="Ej: OC-2024-001"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('numero_oc') border-red-400 @enderror">
                @error('numero_oc')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Archivo OC (opcional) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Archivo OC <span class="text-gray-400 text-xs">(opcional)</span>
                </label>
                <input type="file" name="archivo_oc" accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                @error('archivo_oc')
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

@endsection
