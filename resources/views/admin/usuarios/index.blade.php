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
</style>
@endpush

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Usuarios</h1>
        <p class="text-sm text-gray-500 mt-0.5">Gestiona los accesos y centros de costo del sistema.</p>
    </div>
    <a href="{{ route('admin.usuarios.create') }}" class="btn-usr-editar">
        + Nuevo usuario
    </a>
</div>

@if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl">
        {{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table id="tabla-usuarios" class="min-w-full divide-y divide-gray-100 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Email</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600">Rol</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600">Centro de Costo</th>
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Permisos</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600" onclick="event.stopPropagation()">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($usuarios as $u)
                <tr data-href="{{ route('admin.usuarios.edit', $u->id) }}">
                    <td class="px-5 py-3 font-medium text-gray-800">
                        {{ $u->name }}
                        @if($u->id === auth()->id())
                            <span class="ml-1 text-xs text-indigo-500">(tú)</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $u->email }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($u->esDev())
                            <span style="display:inline-block; background:#fae8ff; color:#7e22ce; font-size:0.75rem; font-weight:700; padding:2px 10px; border-radius:9999px;">Super Administrador</span>
                        @elseif($u->esAdmin())
                            <span style="display:inline-block; background:#e0e7ff; color:#3730a3; font-size:0.75rem; font-weight:700; padding:2px 10px; border-radius:9999px;">Admin</span>
                        @else
                            <span style="display:inline-block; background:#f3f4f6; color:#374151; font-size:0.75rem; font-weight:600; padding:2px 10px; border-radius:9999px;">Usuario</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($u->centroCosto)
                            <span style="display:inline-block; background:#fef9c3; color:#854d0e; font-size:0.8rem; font-weight:700; padding:2px 12px; border-radius:9999px; font-family:monospace;">
                                {{ $u->centroCosto->acronimo }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">Sin asignar</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @if($u->esAdmin())
                            <span class="text-xs text-indigo-400 italic">Acceso completo</span>
                        @elseif($u->permisos && count($u->permisos))
                            <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                @foreach($u->permisos as $p)
                                    <span style="display:inline-block; background:#e0e7ff; color:#3730a3; font-size:0.7rem; font-weight:600; padding:1px 8px; border-radius:9999px;">
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
<div id="modalEliminarUsuario" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
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
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalEliminar() {
        var modal = document.getElementById('modalEliminarUsuario');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('modalEliminarUsuario').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalEliminar();
    });
</script>
@endpush

@endsection
