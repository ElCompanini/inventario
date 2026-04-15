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
            <p class="text-xs text-green-600 mb-1">
                ✓ Tiene boleta adjunta:
                <a href="{{ route('admin.gastos-menores.boleta', $items->first()->id) }}"
                    target="_blank" class="underline">ver PDF actual</a>
            </p>
            @endif
            <input type="file" name="documento" accept=".pdf"
                class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg px-2.5 py-1.5
                          file:mr-3 file:py-1 file:px-3 file:rounded file:border-0
                          file:text-xs file:font-semibold file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200">
        </div>

        {{-- Tabla de ítems --}}
        <div style="border-top:1px solid #e5e7eb; padding-top:1rem;">
            <p class="text-xs font-semibold text-gray-600 mb-2">Productos</p>
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:#fef3c7;">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-amber-800">Producto</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-amber-800" style="width:80px;">Cant.</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-amber-800" style="width:120px;">Monto ($)</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-amber-800" style="width:140px;">P. Neto s/IVA ($)</th>
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