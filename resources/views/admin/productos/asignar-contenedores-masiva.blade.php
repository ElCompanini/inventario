@extends('layouts.app')
@section('title', 'Asignar contenedores — Carga masiva')

@section('content')

<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Asignar contenedores</h1>
        <p class="text-sm text-gray-500 mt-1">
            Elige el contenedor de destino para cada producto antes de confirmar la carga.
        </p>
    </div>
    <a href="{{ route('dashboard') }}"
       class="text-sm text-indigo-600 hover:underline font-medium mt-1">← Cancelar y volver</a>
</div>

<form method="POST" action="{{ route('admin.productos.carga.masiva.contenedores.confirmar') }}">
    @csrf

    <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-5 py-3 text-left">Producto</th>
                    <th class="px-5 py-3 text-center w-24">Cantidad</th>
                    <th class="px-5 py-3 text-left w-56">Contenedor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($pendiente['items'] as $i => $item)
                @php
                    $esNuevo = ($item['accion'] ?? '') === 'nuevo';
                    $nombre  = $esNuevo
                        ? ($item['nuevo_descripcion'] ?? $item['descripcion'])
                        : ($item['producto_nombre'] ?? $item['descripcion']);
                    $preselect = $item['contenedor_id'] ?? null;
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            @if($esNuevo)
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">NUEVO</span>
                            @endif
                            <div>
                                <p class="font-medium text-gray-800">{{ $nombre }}</p>
                                @if(!$esNuevo && ($item['descripcion'] ?? '') !== $nombre)
                                    <p class="text-xs text-gray-400">Excel: {{ $item['descripcion'] }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-center font-semibold text-gray-700">{{ $item['cantidad'] }}</td>
                    <td class="px-5 py-3">
                        <select name="contenedores[{{ $i }}]"
                                required
                                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs
                                       focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                            <option value="">— Selecciona —</option>
                            @foreach($containers as $c)
                                <option value="{{ $c->id }}"
                                    {{ $preselect == $c->id ? 'selected' : '' }}>
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

    {{-- Info SICD / OC --}}
    @if(!empty($pendiente['codigo_sicd']))
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-sm text-blue-700">
        <span class="font-semibold">SICD:</span> {{ $pendiente['codigo_sicd'] }}
        @if($pendiente['vincular_oc'])
            &nbsp;·&nbsp;<span class="font-semibold text-green-700">Continuará con Orden de Compra</span>
        @endif
    </div>
    @endif

    <div class="flex justify-end gap-3">
        <a href="{{ route('dashboard') }}"
           class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
            Confirmar carga →
        </button>
    </div>
</form>

@endsection
