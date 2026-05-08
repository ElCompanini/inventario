@extends('layouts.app')
@section('title', 'Editar ' . $computador->codigo)

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.computadores.show', $computador->id) }}" class="text-sm text-indigo-600 hover:underline">← {{ $computador->codigo }}</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-1">Editar {{ $computador->codigo }}</h1>
</div>

<div class="bg-white rounded-xl shadow p-6 max-w-xl">
    <form method="POST" action="{{ route('admin.computadores.update', $computador->id) }}">
        @csrf @method('PUT')

        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Código</label>
            <input type="text" value="{{ $computador->codigo }}" disabled
                   class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm font-mono text-gray-500">
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
            <input type="text" name="nombre" value="{{ old('nombre', $computador->nombre) }}" required maxlength="200"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            @error('nombre')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Estado</label>
                <select name="estado" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @foreach(\App\Models\ComputadorArmado::ESTADOS as $val => $label)
                        <option value="{{ $val }}" {{ old('estado', $computador->estado) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Ubicación</label>
                <input type="text" name="ubicacion" value="{{ old('ubicacion', $computador->ubicacion) }}" maxlength="200"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Usuario asignado</label>
            <input type="text" name="usuario_asignado" value="{{ old('usuario_asignado', $computador->usuario_asignado) }}" maxlength="150"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción</label>
            <textarea name="descripcion" rows="2" maxlength="1000"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400">{{ old('descripcion', $computador->descripcion) }}</textarea>
        </div>

        <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Notas</label>
            <textarea name="notas" rows="2" maxlength="1000"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400">{{ old('notas', $computador->notas) }}</textarea>
        </div>

        <div class="flex justify-between items-center">
            <form method="POST" action="{{ route('admin.computadores.destroy', $computador->id) }}"
                  onsubmit="return confirm('¿Eliminar el equipo? Solo es posible si no tiene componentes instalados.')">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-red-500 hover:text-red-700 transition">Eliminar equipo</button>
            </form>
            <div class="flex gap-3">
                <a href="{{ route('admin.computadores.show', $computador->id) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                    Guardar cambios
                </button>
            </div>
        </div>
    </form>
</div>

@endsection
