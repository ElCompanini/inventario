@extends('layouts.app')

@section('title', 'OC ' . $oc->numero_oc)

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.ordenes.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a Órdenes</a>
    <div class="flex items-start justify-between mt-1">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 font-mono">{{ $oc->numero_oc }}</h1>
            <p class="text-xs text-gray-400 mt-0.5">
                Creada por <strong>{{ $oc->usuario->name }}</strong> el {{ $oc->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        @if($oc->estado === 'recibido')
            <span class="inline-flex items-center bg-green-100 text-green-700 text-sm font-semibold px-3 py-1.5 rounded-full">✓ Recibido</span>
        @else
            <span class="inline-flex items-center bg-yellow-100 text-yellow-700 text-sm font-semibold px-3 py-1.5 rounded-full">⏳ Pendiente</span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLUMNA IZQUIERDA: SICDs agrupados --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Archivo OC (opcional) --}}
        @if($oc->archivo_ruta)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-700">Documento OC</h2>
                    <a href="{{ route('admin.ordenes.descargar', $oc->id) }}"
                       class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        {{ $oc->archivo_nombre }}
                    </a>
                </div>
            </div>
        @endif

        {{-- SICDs y sus productos --}}
        @foreach($oc->sicds as $sicd)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-700 font-mono">{{ $sicd->codigo_sicd }}</h2>
                        @if($sicd->descripcion)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $sicd->descripcion }}</p>
                        @endif
                    </div>
                    @if(auth()->user()->tienePermiso('sicd'))
                    <a href="{{ route('admin.sicd.show', $sicd->id) }}" class="text-xs text-indigo-600 hover:underline">Ver SICD →</a>
                    @endif
                </div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600 text-xs">Descripción</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600 text-xs">Unidad</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600 text-xs">Solicitado</th>
                            <th class="px-4 py-2 text-center font-semibold text-gray-600 text-xs">Recibido</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-600 text-xs">Precio Neto</th>
                            <th class="px-4 py-2 text-right font-semibold text-gray-600 text-xs">Total Neto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sicd->detalles as $det)
                            @php
                                $pendiente = $oc->estado === 'pendiente';
                                $diferente = !$pendiente && $det->cantidad_recibida != $det->cantidad_solicitada;
                            @endphp
                            <tr class="{{ $diferente ? 'bg-orange-50' : 'hover:bg-gray-50' }}">
                                <td class="px-4 py-2 text-gray-800">
                                    @if($det->producto)
                                        {{ $det->producto->nombre }}
                                        @if($det->producto->nombre !== $det->nombre_producto_excel)
                                            <span class="block text-xs text-gray-400 mt-0.5">Excel: {{ $det->nombre_producto_excel }}</span>
                                        @endif
                                    @else
                                        {{ $det->nombre_producto_excel }}
                                        <span class="ml-1 text-xs text-amber-500">(sin enlace)</span>
                                    @endif
                                    @if($diferente && $det->motivo_recepcion)
                                        <span class="block mt-1 text-xs font-semibold" style="color:#c2410c;">
                                            ⚠ Motivo: {{ $det->motivo_recepcion }}
                                        </span>
                                    @elseif($diferente)
                                        <span class="block mt-1 text-xs" style="color:#f97316;">Sin motivo registrado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center text-gray-600">{{ $det->unidad ?? '—' }}</td>
                                <td class="px-4 py-2 text-center font-semibold text-gray-700">{{ $det->cantidad_solicitada }}</td>
                                <td class="px-4 py-2 text-center font-semibold">
                                    @if($pendiente)
                                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> Pendiente
                                        </span>
                                    @elseif($diferente)
                                        <span class="text-orange-500">{{ $det->cantidad_recibida }}</span>
                                        <span class="text-xs font-normal text-orange-500">(solicitado: {{ $det->cantidad_solicitada }})</span>
                                    @else
                                        <span class="text-green-600">{{ $det->cantidad_recibida }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-gray-700">
                                    {{ $det->precio_neto ? '$' . number_format($det->precio_neto, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-800">
                                    {{ $det->total_neto ? '$' . number_format($det->total_neto, 0, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    {{-- COLUMNA DERECHA: documentos + recepción --}}
    <div class="space-y-4">

        {{-- FACTURA --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-700">Factura</h2>
                    <p class="text-xs text-red-500">Obligatoria para recepción</p>
                </div>
                <span class="w-2.5 h-2.5 rounded-full {{ $oc->factura ? 'bg-green-500' : 'bg-gray-300' }}"></span>
            </div>
            <div class="px-5 py-4">
                @if($oc->factura)
                    <p class="text-sm text-gray-700 font-medium truncate mb-1">{{ $oc->factura->nombre_original }}</p>
                    <p class="text-xs text-gray-400 mb-3">
                        {{ $oc->factura->subido_por }} · {{ $oc->factura->created_at->format('d/m/Y H:i') }}
                    </p>
                    <a href="{{ route('admin.ordenes.factura.descargar', $oc->id) }}"
                       class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar factura
                    </a>
                @elseif($oc->estado === 'pendiente')
                    <form method="POST" action="{{ route('admin.ordenes.factura.subir', $oc->id) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" id="input-factura" name="factura" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                               onchange="document.getElementById('label-factura').textContent = this.files[0]?.name ?? 'Ningún archivo seleccionado'">
                        <label for="input-factura"
                               class="flex items-center justify-center gap-2 w-full py-2 text-xs font-semibold border-2 border-dashed border-indigo-300 text-indigo-600 rounded-lg cursor-pointer hover:bg-indigo-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Seleccionar factura
                        </label>
                        <p id="label-factura" class="text-xs text-gray-400 text-center truncate">Ningún archivo seleccionado</p>
                        @error('factura')
                            <p class="text-red-500 text-xs">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="w-full py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                            Subir factura
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-400">No se subió factura.</p>
                @endif
            </div>
        </div>

        {{-- GUÍA DE DESPACHO --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-700">Guía de Despacho</h2>
                    <p class="text-xs text-gray-400">Opcional</p>
                </div>
                <span class="w-2.5 h-2.5 rounded-full {{ $oc->guia ? 'bg-green-500' : 'bg-gray-300' }}"></span>
            </div>
            <div class="px-5 py-4">
                @if($oc->guia)
                    <p class="text-sm text-gray-700 font-medium truncate mb-1">{{ $oc->guia->nombre_original }}</p>
                    <p class="text-xs text-gray-400 mb-3">
                        {{ $oc->guia->subido_por }} · {{ $oc->guia->created_at->format('d/m/Y H:i') }}
                    </p>
                    <a href="{{ route('admin.ordenes.guia.descargar', $oc->id) }}"
                       class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar guía
                    </a>
                @elseif(!$oc->guia)
                    <form method="POST" action="{{ route('admin.ordenes.guia.subir', $oc->id) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" id="input-guia" name="guia" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                               onchange="document.getElementById('label-guia').textContent = this.files[0]?.name ?? 'Ningún archivo seleccionado'">
                        <label for="input-guia"
                               class="flex items-center justify-center gap-2 w-full py-2 text-xs font-semibold border-2 border-dashed border-gray-300 text-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Seleccionar guía de despacho
                        </label>
                        <p id="label-guia" class="text-xs text-gray-400 text-center truncate">Ningún archivo seleccionado</p>
                        @error('guia')
                            <p class="text-red-500 text-xs">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                style="width:100%; padding:0.5rem; font-size:0.75rem; font-weight:600; color:#fff; background:#374151; border:none; border-radius:0.5rem; cursor:pointer;"
                                onmouseover="this.style.background='#1f2937'"
                                onmouseout="this.style.background='#374151'">
                            Subir guía de despacho
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-400">No se subió guía de despacho.</p>
                @endif
            </div>
        </div>

        {{-- BOTÓN RECEPCIÓN --}}
        @if($oc->estado === 'pendiente')
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Registrar Recepción</h3>

                @if(!$oc->factura)
                    <div class="mb-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Sube la <strong>factura</strong> antes de registrar la recepción.
                    </div>
                @endif

                <a href="{{ route('admin.ordenes.recepcion', $oc->id) }}"
                   class="block w-full py-2 text-sm font-semibold rounded-lg text-center transition
                          {{ $oc->factura ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-100 text-gray-400 pointer-events-none' }}">
                    Iniciar recepción →
                </a>
            </div>
        @endif

        @if($oc->estado === 'recibido')
            <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                <p class="text-sm font-semibold text-green-800 mb-1">OC procesada</p>
                <p class="text-xs text-green-700">
                    Recepción registrada el {{ $oc->procesado_at?->format('d/m/Y \a \l\a\s H:i') }}
                    por <strong>{{ $oc->procesado_por }}</strong>.
                </p>
            </div>
        @endif
    </div>

</div>

@endsection
