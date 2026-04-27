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
                @php $rolLabels = [0 => 'Usuario', 1 => 'Admin', 2 => 'Dev']; @endphp
                @if(auth()->id() === $usuario->id)
                    <input type="hidden" name="rol" value="{{ $usuario->rol }}">
                    <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-2.5 py-1.5 text-sm text-gray-400 select-none">
                        {{ $rolLabels[$usuario->rol] ?? 'Usuario' }} <span class="text-xs">(no modificable)</span>
                    </div>
                @else
                    <select name="rol" required
                            class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="0" {{ old('rol', $usuario->rol) == 0 ? 'selected' : '' }}>Usuario</option>
                        <option value="1" {{ old('rol', $usuario->rol) == 1 ? 'selected' : '' }}>Admin</option>
                        @if(auth()->user()->esDev())
                        <option value="2" {{ old('rol', $usuario->rol) == 2 ? 'selected' : '' }}>Dev</option>
                        @endif
                    </select>
                @endif
                @error('rol') <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p> @enderror
            </div>
            @if(auth()->user()->esAdmin())
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

                @if(auth()->user()->esDev())
                @include('admin.usuarios._nuevo-cc-panel', ['selectId' => 'cc-select-editar'])
                @endif
            </div>
            @endif
        </div>

        {{-- Permisos: solo visibles para Super Administrador --}}
        @if(auth()->user()->esDev())
        <div id="bloque-permisos" style="border-top:1px solid #e5e7eb; padding-top:0.75rem; {{ old('rol', $usuario->rol) != 0 ? 'display:none;' : '' }}">
            <p class="text-xs font-semibold text-gray-600 mb-2">Permisos</p>
            <div class="grid grid-cols-2 gap-2">
                @foreach(\App\Models\User::PERMISOS_DISPONIBLES as $key => $label)
                    @php $activo = old($key, in_array($key, $usuario->permisos ?? []) ? '1' : '') === '1'; @endphp
                    <div class="flex items-center justify-between gap-3 select-none py-1 px-2 rounded-lg hover:bg-gray-50 transition">
                        <span class="text-xs text-gray-700">{{ $label }}</span>
                        <input type="checkbox" name="{{ $key }}" value="1" {{ $activo ? 'checked' : '' }}
                               id="perm-{{ $key }}" class="perm-toggle" style="display:none;">
                        <div class="perm-track" data-for="perm-{{ $key }}" style="
                            width:34px; height:19px; border-radius:9999px; position:relative; cursor:pointer; flex-shrink:0;
                            background: {{ $activo ? '#2563eb' : '#6b7280' }};
                            transition: background .12s;">
                            <div class="perm-thumb" style="
                                width:13px; height:13px; border-radius:50%; background:#fff;
                                position:absolute; top:3px;
                                left: {{ $activo ? '18px' : '3px' }};
                                transition: left .12s;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.25);"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex justify-end gap-2 pt-1">
            <a href="{{ route('admin.usuarios.index') }}"
               class="btn-secondary px-3 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Cancelar
            </a>
            <button type="submit"
                    class="btn-primary px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.querySelector('select[name="rol"]').addEventListener('change', function () {
        var bloque = document.getElementById('bloque-permisos');
        if (bloque) bloque.style.display = this.value == '0' ? '' : 'none';
    });

    // Toggle visual para permisos
    document.querySelectorAll('.perm-track').forEach(function(track) {
        var chk   = document.getElementById(track.dataset.for);
        var thumb = track.querySelector('.perm-thumb');

        track.addEventListener('click', function() {
            if (chk.disabled) return;
            chk.checked = !chk.checked;
            track.style.background = chk.checked ? '#2563eb' : '#6b7280';
            thumb.style.left       = chk.checked ? '18px' : '3px';
            chk.dispatchEvent(new Event('change'));
        });
    });

    // Dependencia: aprobar_solicitudes obliga a tener solicitudes activo
    var chkAprobar     = document.querySelector('input[name="aprobar_solicitudes"]');
    var chkSolicitudes = document.querySelector('input[name="solicitudes"]');

    function setDisabledSolicitudes(disabled) {
        if (!chkSolicitudes) return;
        var track = document.querySelector('.perm-track[data-for="' + chkSolicitudes.id + '"]');
        chkSolicitudes.disabled = disabled;
        track.style.opacity = disabled ? '0.5' : '1';
        track.style.cursor  = disabled ? 'not-allowed' : 'pointer';
    }

    if (chkAprobar && chkSolicitudes) {
        chkAprobar.addEventListener('change', function () {
            if (this.checked) {
                var solTrack = document.querySelector('.perm-track[data-for="' + chkSolicitudes.id + '"]');
                chkSolicitudes.checked = true;
                solTrack.style.background = '#2563eb';
                solTrack.querySelector('.perm-thumb').style.left = '18px';
                setDisabledSolicitudes(true);
            } else {
                setDisabledSolicitudes(false);
            }
        });

        if (chkAprobar.checked) {
            setDisabledSolicitudes(true);
        }

        document.querySelector('form').addEventListener('submit', function () {
            chkSolicitudes.disabled = false;
        });
    }
</script>
@endpush

@endsection
