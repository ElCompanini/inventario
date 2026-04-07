@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a Usuarios</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-1">Editar Usuario</h1>
</div>

<div class="bg-white rounded-xl shadow p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.usuarios.update', $usuario->id) }}" class="space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
            <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Rol</label>
            <select name="rol" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="usuario" {{ old('rol', $usuario->rol) === 'usuario' ? 'selected' : '' }}>Usuario</option>
                <option value="admin"   {{ old('rol', $usuario->rol) === 'admin'   ? 'selected' : '' }}>Admin</option>
            </select>
            @error('rol') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Centro de Costo</label>
            <input type="text" name="centro_costo" value="{{ old('centro_costo', $usuario->centro_costo) }}"
                   placeholder="Ej: TIC(RAMO)"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <p class="text-xs text-gray-400 mt-0.5">Opcional. Asocia al usuario con un centro de costo.</p>
            @error('centro_costo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Permisos: solo visibles si rol = usuario --}}
        <div id="bloque-permisos" style="border-top:1px solid #e5e7eb; padding-top:1rem; {{ old('rol', $usuario->rol) === 'admin' ? 'display:none;' : '' }}">
            <p class="text-sm font-semibold text-gray-700 mb-2">Permisos del usuario</p>
            <div class="space-y-2">
                @foreach(\App\Models\User::PERMISOS_DISPONIBLES as $key => $label)
                    @php $activo = in_array($key, $usuario->permisos ?? []); @endphp
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox"
                               name="{{ $key }}"
                               value="1"
                               {{ old($key, $activo ? '1' : '') ? 'checked' : '' }}
                               style="width:1rem; height:1rem; accent-color:#4f46e5;">
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div style="border-top:1px solid #e5e7eb; padding-top:1rem;">
            <label class="block text-sm font-semibold text-gray-700 mb-1">
                Nueva contraseña <span class="font-normal text-gray-400">(dejar en blanco para no cambiar)</span>
            </label>
            <input type="password" name="password"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar nueva contraseña</label>
            <input type="password" name="password_confirmation"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.usuarios.index') }}"
               class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.querySelector('select[name="rol"]').addEventListener('change', function () {
        document.getElementById('bloque-permisos').style.display =
            this.value === 'usuario' ? '' : 'none';
    });
</script>
@endpush

@endsection
