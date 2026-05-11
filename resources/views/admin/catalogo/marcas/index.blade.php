@extends('layouts.app')
@section('title', 'Marcas')

@section('content')

<div class="mb-5 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Marcas</h1>
        <p class="text-sm text-gray-500 mt-1">Entidades de marca independientes — reutilizables en todos los productos.</p>
    </div>
    <button onclick="document.getElementById('modal-nueva-marca').style.display='flex'"
            style="background:#4f46e5;color:#fff;font-size:.82rem;font-weight:600;padding:.5rem 1.1rem;border-radius:.5rem;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;"
            onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva Marca
    </button>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="min-w-full text-sm divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600">Productos</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600">Estado</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($marcas as $m)
            <tr class="hover:bg-gray-50 {{ $m->trashed() ? 'opacity-50' : '' }}" id="row-m-{{ $m->id }}">
                <td class="px-5 py-3 font-bold text-gray-800">{{ $m->nombre }}</td>
                <td class="px-5 py-3 text-center text-gray-600">{{ $m->productos_count }}</td>
                <td class="px-5 py-3 text-center">
                    @if($m->trashed())
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                            Eliminada
                        </span>
                    @elseif($m->activo)
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                            Activa
                        </span>
                    @else
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600">
                            Inactiva
                        </span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if(!$m->trashed() && $m->activo)
                        <button onclick="abrirEditarMarca({{ $m->id }}, '{{ addslashes($m->nombre) }}')"
                                style="font-size:.75rem;font-weight:600;color:#4f46e5;background:none;border:none;cursor:pointer;">
                            Editar
                        </button>
                        @endif

                        {{-- Toggle activo/inactivo o restaurar --}}
                        <form method="POST" action="{{ route('admin.catalogo.marcas.toggle', $m->id) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    style="font-size:.75rem;font-weight:600;color:{{ $m->trashed() || !$m->activo ? '#16a34a' : '#f59e0b' }};background:none;border:none;cursor:pointer;">
                                {{ $m->trashed() || !$m->activo ? 'Activar' : 'Desactivar' }}
                            </button>
                        </form>

                        @if($m->productos_count === 0 && !$m->trashed())
                        <button onclick="abrirEliminarMarca({{ $m->id }}, '{{ addslashes($m->nombre) }}')"
                                style="font-size:.75rem;font-weight:600;color:#ef4444;background:none;border:none;cursor:pointer;">
                            Eliminar
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">No hay marcas registradas aún.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal: Nueva marca --}}
<div id="modal-nueva-marca" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 24px 60px rgba(0,0,0,.25);width:420px;max-width:calc(100vw - 2rem);padding:1.5rem;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-800">Nueva Marca</h3>
            <button onclick="document.getElementById('modal-nueva-marca').style.display='none'"
                    style="color:#9ca3af;background:none;border:none;cursor:pointer;font-size:1.25rem;">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.catalogo.marcas.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" placeholder="Ej: MSI" required maxlength="100"
                       style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.85rem;box-sizing:border-box;text-transform:uppercase;">
                <p class="text-xs text-gray-400 mt-0.5">Se guardará en mayúsculas</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-nueva-marca').style.display='none'"
                        style="padding:.4rem 1rem;font-size:.82rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:none;border-radius:.5rem;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:.4rem 1.1rem;font-size:.82rem;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:.5rem;cursor:pointer;">
                    Crear Marca
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Editar marca --}}
<div id="modal-editar-marca" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 24px 60px rgba(0,0,0,.25);width:420px;max-width:calc(100vw - 2rem);padding:1.5rem;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-800">Editar Marca</h3>
            <button onclick="document.getElementById('modal-editar-marca').style.display='none'"
                    style="color:#9ca3af;background:none;border:none;cursor:pointer;font-size:1.25rem;">✕</button>
        </div>
        <form id="form-editar-marca" method="POST" class="space-y-3">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" id="edit-marca-nombre" name="nombre" required maxlength="100"
                       style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.85rem;box-sizing:border-box;text-transform:uppercase;">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-editar-marca').style.display='none'"
                        style="padding:.4rem 1rem;font-size:.82rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:none;border-radius:.5rem;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:.4rem 1.1rem;font-size:.82rem;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:.5rem;cursor:pointer;">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Formulario DELETE oculto --}}
<form id="form-eliminar-marca" method="POST" style="display:none;">
    @csrf @method('DELETE')
</form>

{{-- Modal: confirmar eliminación --}}
<div id="modal-eliminar-marca" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 24px 60px rgba(0,0,0,.25);width:400px;max-width:calc(100vw - 2rem);padding:1.5rem;">
        <div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1.25rem;">
            <div style="flex-shrink:0;width:2.5rem;height:2.5rem;border-radius:9999px;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                <svg style="width:1.2rem;height:1.2rem;" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <p style="font-size:.9375rem;font-weight:700;color:#1f2937;margin:0 0 .3rem;">Eliminar marca</p>
                <p style="font-size:.8125rem;color:#6b7280;margin:0;">¿Desactivar la marca <span id="eliminar-marca-nombre" style="font-weight:700;color:#374151;"></span>? Se realizará un soft delete; el historial se conserva.</p>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid #f3f4f6;padding-top:1rem;">
            <button type="button" onclick="cerrarEliminarMarca()"
                    style="padding:.4rem 1rem;font-size:.82rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:none;border-radius:.5rem;cursor:pointer;">
                Cancelar
            </button>
            <button type="button" id="btn-confirmar-eliminar-marca" onclick="confirmarEliminarMarca()"
                    style="padding:.4rem 1.1rem;font-size:.82rem;font-weight:600;color:#fff;background:#dc2626;border:none;border-radius:.5rem;cursor:pointer;">
                Eliminar
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function abrirEditarMarca(id, nombre) {
    var form = document.getElementById('form-editar-marca');
    form.action = '/admin/catalogo/marcas/' + id;
    document.getElementById('edit-marca-nombre').value = nombre;
    document.getElementById('modal-editar-marca').style.display = 'flex';
}

var _eliminarMarcaId = null;

function abrirEliminarMarca(id, nombre) {
    _eliminarMarcaId = id;
    document.getElementById('eliminar-marca-nombre').textContent = nombre;
    var btn = document.getElementById('btn-confirmar-eliminar-marca');
    btn.disabled = false;
    btn.textContent = 'Eliminar';
    document.getElementById('modal-eliminar-marca').style.display = 'flex';
}

function cerrarEliminarMarca() {
    document.getElementById('modal-eliminar-marca').style.display = 'none';
    _eliminarMarcaId = null;
}

function confirmarEliminarMarca() {
    if (!_eliminarMarcaId) return;
    var form = document.getElementById('form-eliminar-marca');
    form.action = '/admin/catalogo/marcas/' + _eliminarMarcaId;
    var btn = document.getElementById('btn-confirmar-eliminar-marca');
    btn.disabled = true;
    btn.textContent = 'Eliminando...';
    form.submit();
}

document.getElementById('modal-eliminar-marca').addEventListener('click', function(e) {
    if (e.target === e.currentTarget) cerrarEliminarMarca();
});
</script>
@endpush

@endsection
