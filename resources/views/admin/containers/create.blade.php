@extends('layouts.app')

@section('title', 'Nuevo Container')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Nuevo Container</h1>
        <p class="text-sm text-gray-500 mt-1">Crea un nuevo contenedor de almacenamiento</p>
    </div>
    <a href="{{ route('admin.containers.index') }}"
       class="text-sm text-indigo-600 hover:underline font-medium">
        ← Volver a containers
    </a>
</div>

<div class="bg-white rounded-xl shadow p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.containers.store') }}">
        @csrf

        <div class="mb-4">
            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                Nombre <span class="text-red-500">*</span>
            </label>
            <input type="text" id="nombre" name="nombre"
                   value="{{ old('nombre') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400
                          @error('nombre') border-red-400 @enderror"
                   placeholder="Ej: Contenedor 3">
            @error('nombre')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">
                Descripción
            </label>
            <textarea id="descripcion" name="descripcion" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400
                             @error('descripcion') border-red-400 @enderror"
                      placeholder="Descripción opcional del contenedor">{{ old('descripcion') }}</textarea>
            @error('descripcion')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.containers.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                Crear container
            </button>
        </div>
    </form>
</div>

@endsection
