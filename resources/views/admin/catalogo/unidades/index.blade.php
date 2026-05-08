@extends('layouts.app')
@section('title', 'Unidades de Medida')

@section('content')

<div class="mb-5 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Unidades de Medida</h1>
        <p class="text-sm text-gray-500 mt-1">Administra las unidades normalizadas del inventario. Siempre en mayúsculas.</p>
    </div>
    <button onclick="document.getElementById('modal-nueva-unidad').style.display='flex'"
            style="background:#4f46e5;color:#fff;font-size:.82rem;font-weight:600;padding:.5rem 1.1rem;border-radius:.5rem;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;"
            onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva Unidad
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
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Abreviación</th>
                <th class="px-5 py-3 text-left font-semibold text-gray-600">Descripción</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600">Productos</th>
                <th class="px-5 py-3 text-center font-semibold text-gray-600">Estado</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($unidades as $u)
            <tr class="hover:bg-gray-50" id="row-u-{{ $u->id }}">
                <td class="px-5 py-3 font-bold text-gray-800">{{ $u->nombre }}</td>
                <td class="px-5 py-3">
                    <span style="font-size:.75rem;font-weight:700;padding:2px 10px;border-radius:9999px;background:#eff6ff;color:#2563eb;font-family:monospace;">
                        {{ $u->abreviacion }}
                    </span>
                </td>
                <td class="px-5 py-3 text-gray-500 text-xs">{{ $u->descripcion ?? '—' }}</td>
                <td class="px-5 py-3 text-center text-gray-600">{{ $u->productos_count ?? $u->productos()->count() }}</td>
                <td class="px-5 py-3 text-center">
                    <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full
                        {{ $u->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                        {{ $u->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button onclick="abrirEditarUnidad({{ $u->id }}, '{{ addslashes($u->nombre) }}', '{{ addslashes($u->abreviacion) }}', '{{ addslashes($u->descripcion ?? '') }}', {{ $u->activo ? 'true' : 'false' }})"
                                style="font-size:.75rem;font-weight:600;color:#4f46e5;background:none;border:none;cursor:pointer;">
                            Editar
                        </button>
                        @if($u->productos()->count() === 0)
                        <button onclick="abrirEliminarUnidad({{ $u->id }}, '{{ addslashes($u->nombre) }}')"
                                style="font-size:.75rem;font-weight:600;color:#ef4444;background:none;border:none;cursor:pointer;">
                            Eliminar
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No hay unidades registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal: Nueva unidad --}}
<div id="modal-nueva-unidad" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 24px 60px rgba(0,0,0,.25);width:460px;max-width:calc(100vw - 2rem);padding:1.5rem;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-800">Nueva Unidad de Medida</h3>
            <button onclick="document.getElementById('modal-nueva-unidad').style.display='none'"
                    style="color:#9ca3af;background:none;border:none;cursor:pointer;font-size:1.25rem;">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.catalogo.unidades.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" placeholder="Ej: KILOGRAMO" required
                           style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.85rem;box-sizing:border-box;text-transform:uppercase;">
                    <p class="text-xs text-gray-400 mt-0.5">Se guardará en mayúsculas</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Abreviación <span class="text-red-500">*</span></label>
                    <input type="text" name="abreviacion" placeholder="Ej: KG" required maxlength="20"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.85rem;box-sizing:border-box;text-transform:uppercase;font-family:monospace;">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción <span class="text-gray-400">(opcional)</span></label>
                <input type="text" name="descripcion" placeholder="Descripción breve"
                       style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.85rem;box-sizing:border-box;">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-nueva-unidad').style.display='none'"
                        style="padding:.4rem 1rem;font-size:.82rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:none;border-radius:.5rem;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:.4rem 1.1rem;font-size:.82rem;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:.5rem;cursor:pointer;">
                    Crear Unidad
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Editar unidad --}}
<div id="modal-editar-unidad" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 24px 60px rgba(0,0,0,.25);width:460px;max-width:calc(100vw - 2rem);padding:1.5rem;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-800">Editar Unidad</h3>
            <button onclick="document.getElementById('modal-editar-unidad').style.display='none'"
                    style="color:#9ca3af;background:none;border:none;cursor:pointer;font-size:1.25rem;">✕</button>
        </div>
        <form id="form-editar-unidad" method="POST" class="space-y-3">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="edit-nombre" name="nombre" required
                           style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.85rem;box-sizing:border-box;text-transform:uppercase;">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Abreviación <span class="text-red-500">*</span></label>
                    <input type="text" id="edit-abreviacion" name="abreviacion" required maxlength="20"
                           style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.85rem;box-sizing:border-box;text-transform:uppercase;font-family:monospace;">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción</label>
                <input type="text" id="edit-descripcion" name="descripcion"
                       style="width:100%;border:1px solid #d1d5db;border-radius:.5rem;padding:.4rem .65rem;font-size:.85rem;box-sizing:border-box;">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="edit-activo" name="activo" value="1" style="width:1rem;height:1rem;accent-color:#4f46e5;">
                <label for="edit-activo" class="text-sm font-medium text-gray-700 cursor-pointer">Activo</label>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-editar-unidad').style.display='none'"
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
<form id="form-eliminar-unidad" method="POST" style="display:none;">
    @csrf @method('DELETE')
</form>

{{-- Modal: confirmar eliminación --}}
<div id="modal-eliminar-unidad" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 24px 60px rgba(0,0,0,.25);width:400px;max-width:calc(100vw - 2rem);padding:1.5rem;">
        <div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1.25rem;">
            <div style="flex-shrink:0;width:2.5rem;height:2.5rem;border-radius:9999px;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                <svg style="width:1.2rem;height:1.2rem;color:#dc2626;" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <p style="font-size:.9375rem;font-weight:700;color:#1f2937;margin:0 0 .3rem;">Eliminar unidad</p>
                <p style="font-size:.8125rem;color:#6b7280;margin:0;">¿Desactivar la unidad <span id="eliminar-unidad-nombre" style="font-weight:700;color:#374151;"></span>? Se realizará un soft delete; el historial se conserva.</p>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid #f3f4f6;padding-top:1rem;">
            <button type="button" onclick="cerrarEliminarUnidad()"
                    style="padding:.4rem 1rem;font-size:.82rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:none;border-radius:.5rem;cursor:pointer;">
                Cancelar
            </button>
            <button type="button" id="btn-confirmar-eliminar-unidad" onclick="confirmarEliminarUnidad()"
                    style="padding:.4rem 1.1rem;font-size:.82rem;font-weight:600;color:#fff;background:#dc2626;border:none;border-radius:.5rem;cursor:pointer;">
                Eliminar
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function abrirEditarUnidad(id, nombre, abrev, desc, activo) {
    var form = document.getElementById('form-editar-unidad');
    form.action = '/admin/catalogo/unidades/' + id;
    document.getElementById('edit-nombre').value      = nombre;
    document.getElementById('edit-abreviacion').value  = abrev;
    document.getElementById('edit-descripcion').value  = desc;
    document.getElementById('edit-activo').checked     = activo;
    document.getElementById('modal-editar-unidad').style.display = 'flex';
}

var _eliminarUnidadId = null;

function abrirEliminarUnidad(id, nombre) {
    _eliminarUnidadId = id;
    document.getElementById('eliminar-unidad-nombre').textContent = nombre;
    var btn = document.getElementById('btn-confirmar-eliminar-unidad');
    btn.disabled = false;
    btn.textContent = 'Eliminar';
    document.getElementById('modal-eliminar-unidad').style.display = 'flex';
}

function cerrarEliminarUnidad() {
    document.getElementById('modal-eliminar-unidad').style.display = 'none';
    _eliminarUnidadId = null;
}

function confirmarEliminarUnidad() {
    if (!_eliminarUnidadId) return;
    var form = document.getElementById('form-eliminar-unidad');
    form.action = '/admin/catalogo/unidades/' + _eliminarUnidadId;
    var btn = document.getElementById('btn-confirmar-eliminar-unidad');
    btn.disabled = true;
    btn.textContent = 'Eliminando...';
    form.submit();
}

document.getElementById('modal-eliminar-unidad').addEventListener('click', function(e) {
    if (e.target === e.currentTarget) cerrarEliminarUnidad();
});
</script>
@endpush

@endsection
