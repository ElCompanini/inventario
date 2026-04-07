@extends('layouts.app')

@section('title', 'SICD ' . $sicd->codigo_sicd)

@section('content')

{{-- Header --}}
<div class="mb-6">
    <a href="{{ route('admin.sicd.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a SICD</a>
    <div class="flex items-start justify-between mt-1">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 font-mono">{{ $sicd->codigo_sicd }}</h1>
            @if($sicd->descripcion)
                <p class="text-sm text-gray-500 mt-0.5">{{ $sicd->descripcion }}</p>
            @endif
        </div>
        @if($sicd->estado === 'recibido')
            <span class="inline-flex items-center bg-green-100 text-green-700 text-sm font-semibold px-3 py-1.5 rounded-full">✓ Recibido</span>
        @elseif($sicd->estado === 'agrupado')
            <span class="inline-flex items-center bg-blue-100 text-blue-700 text-sm font-semibold px-3 py-1.5 rounded-full">📎 Agrupado en OC</span>
        @else
            <span class="inline-flex items-center bg-yellow-100 text-yellow-700 text-sm font-semibold px-3 py-1.5 rounded-full">⏳ Pendiente</span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLUMNA IZQUIERDA: detalles --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Documento SICD --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-700">Documento SICD</h2>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span>Subido por <strong>{{ $sicd->usuario->name }}</strong></span>
                    <span>{{ $sicd->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            <div class="px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-800">{{ $sicd->archivo_nombre }}</p>
                </div>
                <a href="{{ route('admin.sicd.descargar', $sicd->id) }}"
                   class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Descargar
                </a>
            </div>
        </div>

        {{-- Tabla de productos (del Excel) --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-700">Detalle de productos</h2>
                <p class="text-xs text-gray-400 mt-0.5">Leídos desde el Excel adjunto al crear el SICD</p>
            </div>
            @if($sicd->detalles->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-gray-400">Sin productos registrados.</div>
            @else
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Descripción</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Unidad</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Cant. Solicitada</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Cant. Recibida</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Precio Neto</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Total Neto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sicd->detalles as $det)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-800">
                                    @if($det->producto)
                                        {{ $det->producto->nombre }}
                                        @if($det->producto->nombre !== $det->nombre_producto_excel)
                                            <span class="block text-xs text-gray-400 mt-0.5">Excel: {{ $det->nombre_producto_excel }}</span>
                                        @endif
                                    @else
                                        {{ $det->nombre_producto_excel }}
                                        <span class="ml-1 text-xs text-amber-500">(sin enlace)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $det->unidad ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $det->cantidad_solicitada }}</td>
                                <td class="px-4 py-3 text-center font-semibold {{ $det->cantidad_recibida > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $det->cantidad_recibida }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ $det->precio_neto !== null ? '$' . number_format($det->precio_neto, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                    {{ $det->total_neto !== null ? '$' . number_format($det->total_neto, 0, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- COLUMNA DERECHA: OC asociada --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-700">Orden de Compra</h2>
            </div>
            <div class="px-5 py-4">
                @php $oc = $sicd->ordenesCompra->first(); @endphp
                @if($oc)
                    <p class="text-sm font-mono font-semibold text-indigo-700 mb-1">{{ $oc->numero_oc }}</p>
                    <p class="text-xs text-gray-500 mb-3">
                        Estado:
                        @if($oc->estado === 'recibido')
                            <span class="text-green-600 font-semibold">Recibido</span>
                        @else
                            <span class="text-yellow-600 font-semibold">Pendiente</span>
                        @endif
                    </p>
                    <a href="{{ route('admin.ordenes.show', $oc->id) }}"
                       class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        Ver OC →
                    </a>
                @else
                    <p class="text-sm text-gray-400">Aún no asignado a ninguna OC.</p>
                    @if($sicd->estado === 'pendiente')
                        <a href="{{ route('admin.ordenes.create') }}"
                           class="mt-3 inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline font-medium">
                            Crear OC y agrupar →
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>

</div>

@endsection
