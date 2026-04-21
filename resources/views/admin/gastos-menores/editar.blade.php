@extends('layouts.app')
@section('title', 'Editar Gasto Menor — Folio ' . $folio)

@section('content')

<div class="mb-4" style="max-width:700px; margin:auto;">
    <a href="{{ route('admin.gastos-menores.index') }}" class="text-xs text-indigo-600 hover:underline">← Volver a Gastos Menores</a>
    <h1 class="text-lg font-bold text-gray-800 mt-0.5">Editar Folio <span class="font-mono text-amber-700">{{ $folio }}</span></h1>
</div>

@if($errors->any())
<div class="mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-2 text-sm" style="max-width:700px; margin:auto;">
    {{ $errors->first() }}
</div>
@endif

<div class="bg-white rounded-xl shadow p-5" style="max-width:700px; margin:auto;">
    <form method="POST" action="{{ route('admin.gastos-menores.update', urlencode($folio)) }}"
        enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- Datos de cabecera --}}
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    RUT Proveedor <span class="text-red-500">*</span>
                </label>
                <input type="text" name="rut_proveedor"
                    value="{{ old('rut_proveedor', $items->first()->rut_proveedor) }}" required
                    class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Fecha y hora de emisión <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" name="fecha_emision"
                    value="{{ old('fecha_emision', \Carbon\Carbon::parse($items->first()->fecha_emision)->format('Y-m-d\TH:i')) }}"
                    required max="{{ date('Y-m-d\TH:i') }}"
                    class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Reemplazar Boleta PDF
                <span class="font-normal text-gray-400">(opcional — deja vacío para mantener la actual)</span>
            </label>
            @if($items->first()->documento_path)
            <p class="text-xs text-green-600 mb-2">
                ✓ Tiene boleta adjunta:
                <a href="{{ route('admin.gastos-menores.boleta', $items->first()->id) }}"
                    target="_blank" class="underline font-semibold">ver PDF actual</a>
            </p>
            @endif
            <div class="flex items-center gap-3">
                <label for="doc-input"
                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-lg transition"
                    style="background:#d97706; white-space:nowrap;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    {{ $items->first()->documento_path ? 'Reemplazar boleta' : 'Seleccionar PDF' }}
                </label>
                <span id="doc-nombre" class="text-xs text-gray-500 italic">Ningún archivo seleccionado</span>
                <input type="file" id="doc-input" name="documento" accept=".pdf" class="hidden"
                    onchange="document.getElementById('doc-nombre').textContent = this.files[0]?.name || 'Ningún archivo seleccionado'">
            </div>
        </div>

        {{-- Tabla de ítems --}}
        <div style="border-top:1px solid #e5e7eb; padding-top:1rem;">
            <p class="text-xs font-semibold text-gray-600 mb-2">Productos</p>
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:#fef3c7;">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-amber-800">Producto</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-amber-800" style="width:70px;">Cant.</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-amber-800" style="width:110px;">Monto ($)</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-amber-800" style="width:120px;">P. Neto s/IVA ($)</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-amber-800" style="width:160px;">Contenedor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $i => $item)
                    <tr>
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                        <td class="px-3 py-2 font-medium text-gray-800">
                            {{ $item->producto->nombre ?? '—' }}
                            @if($item->producto?->descripcion)
                            <p class="text-xs text-gray-400 font-normal">{{ $item->producto->descripcion }}</p>
                            @endif
                        </td>
                        <td class="px-2 py-2 text-center">
                            <input type="number" name="items[{{ $i }}][cantidad]"
                                value="{{ old("items.{$i}.cantidad", $item->cantidad) }}"
                                min="1" required
                                class="w-full text-center border border-gray-300 rounded-lg px-1 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                        </td>
                        <td class="px-2 py-2 text-center">
                            <input type="number" name="items[{{ $i }}][monto]"
                                value="{{ old("items.{$i}.monto", $item->monto) }}"
                                min="0" required
                                class="w-full text-center border border-gray-300 rounded-lg px-1 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                        </td>
                        <td class="px-2 py-2 text-center">
                            <input type="number" name="items[{{ $i }}][precio_neto]"
                                value="{{ old("items.{$i}.precio_neto", $item->precio_neto) }}"
                                min="0"
                                class="w-full text-center border border-gray-300 rounded-lg px-1 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                        </td>
                        <td class="px-2 py-2">
                            <select class="gm-cont-select w-full border border-gray-300 rounded-lg px-1 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400"
                                    data-id="{{ $item->id }}"
                                    data-url="{{ route('admin.gastos-menores.contenedor', $item->id) }}">
                                @foreach($containers as $c)
                                    <option value="{{ $c->id }}"
                                        {{ ($item->historialCambio?->contenedor_id == $c->id) ? 'selected' : '' }}>
                                        {{ $c->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="gm-cont-msg text-xs hidden"></span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-2 mt-5">
            <a href="{{ route('admin.gastos-menores.index') }}"
                class="px-4 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Cancelar
            </a>
            <button type="submit"
                class="px-4 py-1.5 text-xs font-semibold text-white rounded-lg transition"
                style="background:#d97706;">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.querySelectorAll('.gm-cont-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var msg = this.closest('td').querySelector('.gm-cont-msg');
        msg.className = 'gm-cont-msg text-xs text-gray-400';
        msg.textContent = 'Guardando...';
        msg.classList.remove('hidden');
        fetch(this.dataset.url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ contenedor_id: this.value })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.ok) {
                msg.className = 'gm-cont-msg text-xs text-green-600 font-semibold';
                msg.textContent = '✓ Guardado';
                setTimeout(function() { msg.classList.add('hidden'); }, 2000);
            } else {
                msg.className = 'gm-cont-msg text-xs text-red-500';
                msg.textContent = 'Error';
            }
        })
        .catch(function() {
            msg.className = 'gm-cont-msg text-xs text-red-500';
            msg.textContent = 'Error de conexión';
        });
    });
});
</script>
@endpush