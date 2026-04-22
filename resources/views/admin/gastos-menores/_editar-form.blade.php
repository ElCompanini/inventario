<div id="gm-edit-errors" class="hidden mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-2 text-sm"></div>

<form method="POST" action="{{ route('admin.gastos-menores.update', urlencode($folio)) }}"
    enctype="multipart/form-data" id="form-editar-gm">
    @csrf @method('PUT')

    <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                RUT Proveedor <span class="text-red-500">*</span>
            </label>
            <input type="text" name="rut_proveedor"
                value="{{ $items->first()->rut_proveedor }}" required
                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
                Fecha y hora de emisión <span class="text-red-500">*</span>
            </label>
            <input type="datetime-local" name="fecha_emision"
                value="{{ \Carbon\Carbon::parse($items->first()->fecha_emision)->format('Y-m-d\TH:i') }}"
                required max="{{ date('Y-m-d\TH:i') }}"
                class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
        </div>
    </div>

    <div class="mb-4">
        <p class="text-sm font-bold text-gray-700 mb-2">Boleta PDF</p>
        @if($items->first()->documento_path)
        <p class="text-xs text-green-600 mb-2">
            ✓ Tiene boleta adjunta:
            <a href="{{ route('admin.gastos-menores.boleta', $items->first()->id) }}"
                target="_blank" class="underline font-semibold">ver PDF actual</a>
        </p>
        @endif
        <div class="flex items-center gap-3">
            <label for="doc-input-modal"
                class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-lg transition"
                style="background:#d97706; white-space:nowrap;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                {{ $items->first()->documento_path ? 'Reemplazar boleta' : 'Seleccionar PDF' }}
            </label>
            <span id="doc-nombre-modal" class="text-xs text-gray-500 italic">Ningún archivo seleccionado</span>
            <input type="file" id="doc-input-modal" name="documento" accept=".pdf" class="hidden"
                onchange="document.getElementById('doc-nombre-modal').textContent = this.files[0]?.name || 'Ningún archivo seleccionado'">
        </div>
    </div>

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
                    <td class="px-2 py-2">
                        <input type="number" name="items[{{ $i }}][cantidad]"
                            value="{{ $item->cantidad }}" min="1" required
                            class="w-full text-center border border-gray-300 rounded-lg px-1 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="items[{{ $i }}][monto]"
                            value="{{ $item->monto }}" min="0" required
                            class="w-full text-center border border-gray-300 rounded-lg px-1 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="items[{{ $i }}][precio_neto]"
                            value="{{ $item->precio_neto }}" min="0"
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
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex justify-end gap-2 mt-5">
        <button type="button" id="btn-cancelar-editar-gm"
            class="btn-secondary px-4 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">
            Cancelar
        </button>
        <button type="submit" id="btn-submit-editar-gm"
            class="btn-primary px-4 py-1.5 text-xs font-semibold text-white rounded-lg"
            style="background:#d97706;">
            Guardar cambios
        </button>
    </div>
</form>
