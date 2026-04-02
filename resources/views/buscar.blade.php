@extends('layouts.app')

@section('title', 'Buscar' . ($q ? ' — ' . $q : ''))

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Búsqueda global</h1>

    <form method="GET" action="{{ route('buscar') }}" class="flex gap-2">
        <input type="text" name="q" value="{{ $q }}" autofocus
               placeholder="ID, nombre de producto, código SICD, N° OC, motivo..."
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <button type="submit"
                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition">
            Buscar
        </button>
    </form>
</div>

@if(strlen($q) < 2)
    <p class="text-sm text-gray-400">Ingresa al menos 2 caracteres para buscar.</p>
@else

    @php
        $total = $resultados['productos']->count()
               + $resultados['sicds']->count()
               + $resultados['ordenes']->count()
               + $resultados['historial']->count();
    @endphp

    <p class="text-sm text-gray-500 mb-5">
        {{ $total }} resultado(s) para <strong class="text-gray-800">"{{ $q }}"</strong>
    </p>

    @if($total === 0)
        <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400">
            Sin resultados. Intenta con otro término.
        </div>
    @endif

    {{-- Productos --}}
    @if($resultados['productos']->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">
                Inventario ({{ $resultados['productos']->count() }})
            </h2>
            <div class="bg-white rounded-xl shadow overflow-hidden divide-y divide-gray-100">
                @foreach($resultados['productos'] as $p)
                    <a href="{{ route('dashboard') }}#producto-{{ $p->id }}"
                       class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $p->nombre }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                ID #{{ $p->id }} ·
                                {{ $p->container->nombre ?? '—' }} ·
                                {{ $p->descripcion ?? 'Sin descripción' }}
                            </p>
                        </div>
                        <div class="text-right shrink-0 ml-4">
                            <span class="text-sm font-bold
                                @if($p->estadoStock() === 'critico') text-red-600
                                @elseif($p->estadoStock() === 'minimo') text-yellow-600
                                @else text-green-600 @endif">
                                Stock: {{ $p->stock_actual }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- SICDs --}}
    @if($resultados['sicds']->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">
                SICDs ({{ $resultados['sicds']->count() }})
            </h2>
            <div class="bg-white rounded-xl shadow overflow-hidden divide-y divide-gray-100">
                @foreach($resultados['sicds'] as $s)
                    <a href="{{ route('admin.sicd.show', $s->id) }}"
                       class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <div>
                            <p class="text-sm font-semibold font-mono text-indigo-700">{{ $s->codigo_sicd }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                ID #{{ $s->id }} · {{ $s->usuario->name }} · {{ $s->created_at->format('d/m/Y') }}
                                @if($s->descripcion) · {{ $s->descripcion }} @endif
                            </p>
                        </div>
                        <span class="text-xs font-semibold shrink-0 ml-4 px-2.5 py-1 rounded-full
                            @if($s->estado === 'recibido') bg-green-100 text-green-700
                            @elseif($s->estado === 'agrupado') bg-blue-100 text-blue-700
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ ucfirst($s->estado) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Órdenes de compra --}}
    @if($resultados['ordenes']->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">
                Órdenes de Compra ({{ $resultados['ordenes']->count() }})
            </h2>
            <div class="bg-white rounded-xl shadow overflow-hidden divide-y divide-gray-100">
                @foreach($resultados['ordenes'] as $oc)
                    <a href="{{ route('admin.ordenes.show', $oc->id) }}"
                       class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <div>
                            <p class="text-sm font-semibold font-mono text-indigo-700">{{ $oc->numero_oc }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                ID #{{ $oc->id }} · {{ $oc->usuario->name }} · {{ $oc->created_at->format('d/m/Y') }}
                            </p>
                        </div>
                        <span class="text-xs font-semibold shrink-0 ml-4 px-2.5 py-1 rounded-full
                            {{ $oc->estado === 'recibido' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ ucfirst($oc->estado) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Historial --}}
    @if($resultados['historial']->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">
                Historial ({{ $resultados['historial']->count() }})
            </h2>
            <div class="bg-white rounded-xl shadow overflow-hidden divide-y divide-gray-100">
                @foreach($resultados['historial'] as $h)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                {{ $h->producto->nombre }}
                                <span class="ml-2 text-xs font-semibold px-2 py-0.5 rounded-full
                                    @if($h->tipo === 'entrada') bg-green-100 text-green-700
                                    @elseif($h->tipo === 'salida') bg-orange-100 text-orange-700
                                    @else bg-blue-100 text-blue-700 @endif">
                                    {{ ucfirst($h->tipo) }}
                                </span>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                ID #{{ $h->id }} · {{ $h->motivo }} · {{ $h->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <span class="text-sm font-bold shrink-0 ml-4
                            @if($h->tipo === 'entrada') text-green-600
                            @elseif($h->tipo === 'salida') text-orange-600
                            @else text-blue-600 @endif">
                            {{ $h->tipo === 'entrada' ? '+' : ($h->tipo === 'salida' ? '−' : '') }}{{ $h->cantidad }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

@endif

@endsection
