@extends('layouts.app')

@section('title', 'Nuevo Usuario')

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a Usuarios</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-1">Nuevo Usuario</h1>
</div>

<div class="bg-white rounded-xl shadow p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.usuarios.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña</label>
            <input type="password" name="password" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Rol</label>
            @if(auth()->user()->esDev())
            <select name="rol" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="0" {{ old('rol', '0') == '0' ? 'selected' : '' }}>Usuario</option>
                <option value="1" {{ old('rol') == '1' ? 'selected' : '' }}>Admin</option>
                <option value="2" {{ old('rol') == '2' ? 'selected' : '' }}>Dev</option>
            </select>
            @error('rol') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            @else
            <input type="hidden" name="rol" value="0">
            <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-400 select-none">
                Usuario <span class="text-xs">(solo dev puede asignar roles)</span>
            </div>
            @endif
        </div>

        @if(auth()->user()->esAdmin())
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Centro de Costo</label>
            <select name="centro_costo_id" id="cc-select-crear"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer">
                <option value="">— Sin centro de costo —</option>
                @foreach($centrosCosto as $cc)
                    <option value="{{ $cc->id }}" {{ old('centro_costo_id') == $cc->id ? 'selected' : '' }}>{{ $cc->acronimo }}</option>
                @endforeach
            </select>
            @error('centro_costo_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

            @if(auth()->user()->esDev())
            @include('admin.usuarios._nuevo-cc-panel', ['selectId' => 'cc-select-crear'])
            @endif
        </div>
        @endif

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.usuarios.index') }}"
               class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                Crear usuario
            </button>
        </div>
    </form>
</div>

@endsection
