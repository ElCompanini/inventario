@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')

<div class="mb-4" style="max-width:640px; margin-left:auto; margin-right:auto;">
    <a href="{{ route('admin.usuarios.index') }}" class="text-xs text-indigo-600 hover:underline">← Volver a Usuarios</a>
    <h1 class="text-lg font-bold text-gray-800 mt-0.5">Editar Usuario</h1>
</div>

<div class="bg-white rounded-xl shadow p-5 mx-auto" style="max-width:640px;">
    <form method="POST" action="{{ route('admin.usuarios.update', $usuario->id) }}" class="space-y-3">
        @csrf @method('PUT')

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre</label>
                <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @error('name') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                <input type="text" name="email" value="{{ old('email', $usuario->email) }}" required
                       class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @error('email') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Rol</label>
                <select name="rol" required
                        class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="usuario" {{ old('rol', $usuario->rol) === 'usuario' ? 'selected' : '' }}>Usuario</option>
                    <option value="admin"   {{ old('rol', $usuario->rol) === 'admin'   ? 'selected' : '' }}>Admin</option>
                </select>
                @error('rol') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
            </div>
            @php $rolAuth = auth()->user()->rol; @endphp
            @if($rolAuth === 'dev' || $rolAuth === 'admin')
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Centro de Costo</label>
                <select name="centro_costo" id="cc-select-editar"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm font-mono text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer">
                    <option value="">— Sin centro de costo —</option>
                    @foreach($centrosCosto as $cc)
                        <option value="{{ $cc }}" {{ old('centro_costo', $usuario->centro_costo) === $cc ? 'selected' : '' }}>{{ $cc }}</option>
                    @endforeach
                </select>
                @error('centro_costo') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror

                @if($rolAuth === 'dev')
                @include('admin.usuarios._nuevo-cc-panel', ['selectId' => 'cc-select-editar'])
                @endif
            </div>
            @endif
        </div>

        {{-- Permisos: solo visibles si rol = usuario --}}
        <div id="bloque-permisos" style="border-top:1px solid #e5e7eb; padding-top:0.75rem; {{ old('rol', $usuario->rol) === 'admin' ? 'display:none;' : '' }}">
            <p class="text-xs font-semibold text-gray-600 mb-1.5">Permisos</p>
            <div class="grid grid-cols-2 gap-1">
                @foreach(\App\Models\User::PERMISOS_DISPONIBLES as $key => $label)
                    @php $activo = in_array($key, $usuario->permisos ?? []); @endphp
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox"
                               name="{{ $key }}"
                               value="1"
                               {{ old($key, $activo ? '1' : '') ? 'checked' : '' }}
                               style="width:0.875rem; height:0.875rem; accent-color:#4f46e5;">
                        <span class="text-xs text-gray-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-1">
            <a href="{{ route('admin.usuarios.index') }}"
               class="px-3 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
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

    var chkAprobar     = document.querySelector('input[name="aprobar_solicitudes"]');
    var chkSolicitudes = document.querySelector('input[name="solicitudes"]');

    if (chkAprobar && chkSolicitudes) {
        // Al marcar aprobar_solicitudes → forzar solicitudes
        chkAprobar.addEventListener('change', function () {
            if (this.checked) {
                chkSolicitudes.checked  = true;
                chkSolicitudes.disabled = true;
            } else {
                chkSolicitudes.disabled = false;
            }
        });

        // Al cargar la página, si aprobar_solicitudes ya está marcado → deshabilitar solicitudes
        if (chkAprobar.checked) {
            chkSolicitudes.checked  = true;
            chkSolicitudes.disabled = true;
        }

        // Asegurar que el valor de solicitudes se envíe aunque esté disabled
        document.querySelector('form').addEventListener('submit', function () {
            if (chkSolicitudes.disabled) {
                chkSolicitudes.disabled = false;
            }
        });
    }
</script>
@endpush

@endsection
