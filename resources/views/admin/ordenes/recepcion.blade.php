@extends('layouts.app')

@section('title', 'Recepción OC ' . $oc->numero_oc)

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.ordenes.show', $oc->id) }}" class="text-sm text-indigo-600 hover:underline">← Volver a OC {{ $oc->numero_oc }}</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-1">Registrar Recepción</h1>
    <p class="text-sm text-gray-500 mt-1">
        Ingresa las cantidades <strong>realmente recibidas</strong> para cada producto.
        Las cantidades de stock se actualizarán al confirmar.
    </p>
</div>

<form method="POST" action="{{ route('admin.ordenes.recepcion.procesar', $oc->id) }}">
    @csrf

    <div class="space-y-4">
        @foreach($oc->sicds as $sicd)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-700 font-mono">{{ $sicd->codigo_sicd }}</h2>
                    @if($sicd->descripcion)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $sicd->descripcion }}</p>
                    @endif
                </div>

                @if($sicd->detalles->isEmpty())
                    <div class="px-5 py-4 text-sm text-gray-400">Sin productos en este SICD.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Producto</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Stock actual</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600">Solicitado</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600">Cantidad recibida</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Container destino</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($sicd->detalles as $det)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-800">{{ $det->nombre_producto_excel }}</p>
                                        @if(!$det->producto)
                                            <p class="text-xs text-amber-500 mt-0.5">Sin enlace a producto — no actualizará stock</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $det->producto?->stock_actual ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-700">
                                        {{ $det->cantidad_solicitada }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="number"
                                               name="recibido[{{ $det->id }}]"
                                               value="{{ old("recibido.{$det->id}", $det->cantidad_solicitada) }}"
                                               min="0"
                                               max="{{ $det->cantidad_solicitada }}"
                                               {{ !$det->producto ? 'disabled' : '' }}
                                               class="w-24 border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-400
                                                      {{ !$det->producto ? 'bg-gray-100 text-gray-400' : '' }}">
                                        @error("recibido.{$det->id}")
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($det->producto)
                                            <select name="container[{{ $det->id }}]"
                                                    class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                                @foreach($containers as $c)
                                                    <option value="{{ $c->id }}"
                                                        {{ $det->producto->contenedor == $c->id ? 'selected' : '' }}>
                                                        {{ $c->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('admin.ordenes.show', $oc->id) }}"
           class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Cancelar
        </a>
        <button type="submit"
                onclick="return confirm('¿Confirmar recepción de OC {{ $oc->numero_oc }}? Esta acción actualizará el stock y no se puede deshacer.')"
                class="px-6 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
            Confirmar recepción →
        </button>
    </div>
</form>

@endsection
