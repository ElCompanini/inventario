@extends('layouts.app')

@section('title', 'Mis Solicitudes')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Mis Solicitudes</h1>
    <p class="text-sm text-gray-500 mt-1">Historial de tus solicitudes de movimiento de stock</p>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Producto</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Tipo</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Cantidad</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Motivo</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($solicitudes as $solicitud)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                        {{ $solicitud->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900">
                        {{ $solicitud->producto->nombre }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($solicitud->tipo === 'entrada')
                            <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                ↑ Entrada
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                ↓ Salida
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center font-medium text-gray-800">
                        {{ $solicitud->cantidad }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $solicitud->motivo }}">
                        {{ $solicitud->motivo }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($solicitud->estado === 'pendiente')
                            <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                ⏳ Pendiente
                            </span>
                        @elseif($solicitud->estado === 'aprobado')
                            <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                ✓ Aprobado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                ✗ Rechazado
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                        No has realizado solicitudes todavía.
                        <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline ml-1">
                            Ir a productos
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
