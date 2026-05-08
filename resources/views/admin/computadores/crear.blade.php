@extends('layouts.app')
@section('title', 'Nuevo Armado')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.computadores.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a Armados</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-1">Nuevo Armado de Equipo</h1>
</div>

<div class="bg-white rounded-xl shadow p-6 max-w-xl">
    <form method="POST" action="{{ route('admin.computadores.store') }}">
        @csrf

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Código <span class="text-red-500">*</span></label>
                <input type="text" name="codigo" value="{{ old('codigo', $codigo) }}" required maxlength="50"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400 uppercase"
                       placeholder="PC-001">
                @error('codigo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required maxlength="200"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                       placeholder="Computador Escritorio Gerencia">
                @error('nombre')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción</label>
            <textarea name="descripcion" rows="2" maxlength="1000"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      placeholder="Descripción general del equipo...">{{ old('descripcion') }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Ubicación</label>
                <input type="text" name="ubicacion" value="{{ old('ubicacion') }}" maxlength="200"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                       placeholder="Oficina 3 / Sala Servidores">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Usuario asignado</label>
                <input type="text" name="usuario_asignado" value="{{ old('usuario_asignado') }}" maxlength="150"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                       placeholder="Nombre del usuario final">
            </div>
        </div>

        <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Notas</label>
            <textarea name="notas" rows="2" maxlength="1000"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400"
                      placeholder="Notas adicionales...">{{ old('notas') }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.computadores.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                Crear armado →
            </button>
        </div>
    </form>
</div>

@endsection
