@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@push('head')
<style>
    .btn-usr-editar {
        padding: 4px 14px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #fff;
        background: #4f46e5;
        border-radius: 0.4rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: background .15s, box-shadow .15s, transform .1s;
    }
    .btn-usr-editar:hover {
        background: #818cf8;
        box-shadow: 0 0 10px 2px rgba(99,102,241,0.5);
        transform: scale(1.04);
    }
    .btn-usr-editar:active { transform: scale(.96); box-shadow: none; }

    .btn-usr-eliminar {
        padding: 4px 14px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #fff;
        background: #ef4444;
        border-radius: 0.4rem;
        border: none;
        cursor: pointer;
        transition: background .15s, box-shadow .15s, transform .1s;
    }
    .btn-usr-eliminar:hover {
        background: #f87171;
        box-shadow: 0 0 10px 2px rgba(239,68,68,0.5);
        transform: scale(1.04);
    }
    .btn-usr-eliminar:active { transform: scale(.95); box-shadow: none; filter: brightness(.9); }
    #tabla-usuarios tbody tr {
        cursor: pointer;
    }
    #tabla-usuarios tbody tr:hover {
        background-color: #f5f3ff !important;
    }
    /* Dark mode — hover con alta especificidad para ganarle al !important de arriba */
    html.dark #tabla-usuarios tbody tr:hover {
        background-color: rgba(99,102,241,.12) !important;
    }
    html.dark .btn-usr-editar:hover {
        background: #4f46e5 !important;
        box-shadow: 0 0 10px 2px rgba(99,102,241,0.4) !important;
    }
    html.dark .btn-usr-eliminar:hover {
        background: #dc2626 !important;
        box-shadow: 0 0 10px 2px rgba(239,68,68,0.4) !important;
    }
    .btn-usr-reset {
        padding: 4px 12px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #fff;
        background: #e11d48;
        border-radius: 0.4rem;
        border: none;
        cursor: pointer;
        transition: background .15s, box-shadow .15s, transform .1s;
    }
    .btn-usr-reset:hover {
        background: #fb7185;
        box-shadow: 0 0 10px 2px rgba(225,29,72,0.35);
        transform: scale(1.04);
    }
    html.dark .usr-reset-pending-row {
        background: rgba(225,29,72,.10);
    }
</style>
@endpush

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Usuarios</h1>
        <p class="text-sm text-gray-500 mt-0.5">Gestiona los accesos y centros de costo del sistema.</p>
    </div>
    <div class="flex items-center gap-2">
        @if((int) auth()->user()->rol === 2 && $resetPendientesCount > 0)
            <a href="{{ route('admin.usuarios.index', ['filtro' => 'reset_pendiente']) }}" class="btn-usr-reset">
                {{ $resetPendientesCount }} reset pendiente(s)
            </a>
        @endif
        @if($mostrarPendientes)
            <a href="{{ route('admin.usuarios.index') }}" class="btn-secondary px-3 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Ver todos
            </a>
        @endif
        <a href="{{ route('admin.usuarios.create') }}" class="btn-usr-editar">
            + Nuevo usuario
        </a>
    </div>
</div>

@if((int) auth()->user()->rol === 2 && $mostrarPendientes)
    <div class="mb-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-4 py-3 rounded-lg text-sm">
        Mostrando usuarios con solicitud de reseteo pendiente.
    </div>
@endif

<div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden">
    <table id="tabla-usuarios" class="min-w-full divide-y divide-gray-100 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Email</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600">Rol</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600">Centro de Costo</th>
                @if((int) auth()->user()->rol === 2)
                    <th class="px-5 py-3 text-center font-semibold text-gray-600">Recuperacion</th>
                @endif
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Permisos</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600" onclick="event.stopPropagation()">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($usuarios as $u)
                <tr data-href="{{ route('admin.usuarios.edit', $u->id) }}" class="{{ $u->pendingPasswordResetRequest ? 'usr-reset-pending-row bg-rose-50' : '' }}">
                    <td class="px-5 py-3 font-medium text-gray-800">
                        {{ $u->name }}
                        @if($u->id === auth()->id())
                            <span class="ml-1 text-xs text-indigo-500">(tú)</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $u->email }}</td>
                    <td class="px-5 py-3 text-center">
                        @if((int) $u->rol === 2)
                            <span class="inline-block text-xs font-bold px-2.5 py-0.5 rounded-full
                                         bg-purple-100 text-purple-800
                                         dark:bg-purple-900/50 dark:text-purple-300">
                                Super Administrador
                            </span>
                        @elseif($u->esAdmin())
                            <span class="inline-block text-xs font-bold px-2.5 py-0.5 rounded-full
                                         bg-indigo-100 text-indigo-800
                                         dark:bg-indigo-900/50 dark:text-indigo-300">
                                Admin
                            </span>
                        @else
                            <span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full
                                         bg-gray-100 text-gray-700
                                         dark:bg-gray-700 dark:text-gray-300">
                                Usuario
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($u->centroCosto)
                            <span class="inline-block text-xs font-bold font-mono px-3 py-0.5 rounded-full
                                         bg-amber-100 text-amber-800
                                         dark:bg-amber-900/40 dark:text-amber-300">
                                {{ $u->centroCosto->acronimo }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">Sin asignar</span>
                        @endif
                    </td>
                    @if((int) auth()->user()->rol === 2)
                        <td class="px-5 py-3 text-center">
                            @if($u->pendingPasswordResetRequest)
                                <span class="inline-block text-xs font-bold px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300">
                                    Solicitud de reseteo pendiente
                                </span>
                            @elseif((int) ($u->password_reset_status ?? 0) === 1)
                                <span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                    Contrasena reiniciada
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">Sin solicitud</span>
                            @endif
                        </td>
                    @endif
                    <td class="px-5 py-3">
                        @if((int) $u->rol === 2)
                            <span class="text-xs text-purple-400 dark:text-purple-300 italic">Acceso total (Super Administrador)</span>
                        @elseif($u->esAdmin() && empty($u->permisos))
                            <span class="text-xs text-indigo-400 dark:text-indigo-300 italic">Acceso completo</span>
                        @elseif($u->permisos && count($u->permisos))
                            <div class="flex flex-wrap gap-1">
                                @foreach($u->permisos as $p)
                                    <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full
                                                 bg-indigo-100 text-indigo-700
                                                 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ \App\Models\User::PERMISOS_DISPONIBLES[$p] ?? $p }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">Sin permisos</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center" onclick="event.stopPropagation()">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('admin.usuarios.edit', $u->id) }}" class="btn-usr-editar">
                                Editar
                            </a>
                            @if((int) auth()->user()->rol === 2)
                                <button type="button" class="btn-usr-reset"
                                        onclick="abrirModalReset({{ json_encode($u->name) }}, '{{ route('admin.usuarios.reset-password', $u->id) }}', {{ $u->id === auth()->id() ? 'true' : 'false' }})">
                                    {{ $u->id === auth()->id() ? 'Resetear mi contrasena' : 'Resetear contrasena' }}
                                </button>
                            @endif
                            @if($u->id !== auth()->id())
                                <button type="button" class="btn-usr-eliminar"
                                        onclick="abrirModalEliminar({{ $u->id }}, {{ json_encode($u->name) }}, '{{ route('admin.usuarios.destroy', $u->id) }}')">
                                    Eliminar
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Modal eliminación usuario --}}
<div id="modalResetPassword" class="fixed inset-0 z-50 items-center justify-center bg-black/50" style="display:none">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full mx-4 p-6 border border-gray-100 dark:border-slate-700" style="max-width:460px; animation: modal-usr-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-6 h-6 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <h2 class="text-lg font-bold text-orange-500 dark:text-orange-400">Resetear contrasena</h2>
        </div>

        <p id="modal-reset-texto" class="text-sm text-gray-600 dark:text-gray-300 mb-2"></p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-6">La contrasena quedara como contrasena por defecto y se guardara hasheada.</p>

        <form id="formResetPassword" method="POST" action="">
            @csrf
            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalReset()"
                        class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg">
                    Cancelar
                </button>
                <button type="submit"
                        class="btn-danger px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">
                    Confirmar reset
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modalEliminarUsuario" class="fixed inset-0 z-50 items-center justify-center bg-black/50" style="display:none">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" style="animation: modal-usr-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Desactivar usuario</h2>
        <p class="text-sm text-gray-500 mb-1">¿Confirmas que deseas desactivar a <strong id="modal-usr-nombre" class="text-gray-800"></strong>?</p>
        <p class="text-xs text-gray-400 mb-6">El usuario no podrá iniciar sesión, pero sus datos se conservan.</p>

        <form id="formEliminarUsuario" method="POST" action="">
            @csrf @method('DELETE')
            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEliminar()"
                        class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                    Cancelar
                </button>
                <button type="submit"
                        class="btn-danger px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

@push('head')
<style>
    @keyframes modal-usr-in {
        from { opacity:0; transform:scale(.94); }
        to   { opacity:1; transform:scale(1); }
    }
</style>
@endpush

@push('scripts')
<script>
    document.querySelectorAll('#tabla-usuarios tbody tr[data-href]').forEach(function(tr) {
        tr.addEventListener('click', function() {
            window.location = this.dataset.href;
        });
    });

    function abrirModalEliminar(id, nombre, url) {
        document.getElementById('modal-usr-nombre').textContent = nombre;
        document.getElementById('formEliminarUsuario').action = url;
        var modal = document.getElementById('modalEliminarUsuario');
        modal.style.display = 'flex';
    }

    function cerrarModalEliminar() {
        var modal = document.getElementById('modalEliminarUsuario');
        modal.style.display = 'none';
    }

    function abrirModalReset(nombre, url, esPropio) {
        var texto = esPropio
            ? 'Confirmas que deseas resetear tu propia contrasena a la contrasena por defecto?'
            : 'Confirmas que deseas resetear la contrasena de ' + nombre + ' a la contrasena por defecto?';
        document.getElementById('modal-reset-texto').textContent = texto;
        document.getElementById('formResetPassword').action = url;
        document.getElementById('modalResetPassword').style.display = 'flex';
    }

    function cerrarModalReset() {
        document.getElementById('modalResetPassword').style.display = 'none';
    }

    document.getElementById('modalEliminarUsuario').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalEliminar();
    });

    document.getElementById('modalResetPassword').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalReset();
    });
</script>
@endpush

@endsection
