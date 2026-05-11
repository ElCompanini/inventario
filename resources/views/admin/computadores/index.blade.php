@extends('layouts.app')
@section('title', 'Armado de Equipos')

@section('content')

<div class="mb-5 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Armado de Equipos</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $computadores->count() }} equipo(s) registrado(s)</p>
    </div>
    <a href="{{ route('admin.computadores.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo Armado
    </a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">{{ $errors->first() }}</div>
@endif

@if($computadores->isEmpty())
    <div class="bg-white rounded-xl shadow p-10 text-center">
        <svg class="w-8 h-8 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <p class="font-semibold text-gray-500">No hay armados registrados aún.</p>
        <a href="{{ route('admin.computadores.create') }}"
           class="mt-3 inline-block text-indigo-600 hover:underline text-sm">Crear el primer armado →</a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($computadores as $pc)
        @php
            $activos   = $pc->componentesActivos;
            $tipos     = \App\Models\ComputadorArmado::TIPOS_COMPONENTE;
            $tiposActivos = $activos->pluck('tipo_componente')->unique()->toArray();
            $statusClass = match($pc->estado) {
                'listo'     => 'bg-green-100 text-green-700',
                'en_uso'    => 'bg-blue-100 text-blue-700',
                'desarmado' => 'bg-gray-100 text-gray-500',
                default     => 'bg-yellow-100 text-yellow-700', // en_armado
            };
        @endphp
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden hover:shadow-md transition">
            <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-base font-bold text-gray-800 font-mono">{{ $pc->codigo }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $statusClass }}">
                            {{ \App\Models\ComputadorArmado::ESTADOS[$pc->estado] ?? $pc->estado }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-0.5 truncate">{{ $pc->nombre }}</p>
                    @if($pc->ubicacion)
                        <p class="text-xs text-gray-400 mt-0.5">📍 {{ $pc->ubicacion }}</p>
                    @endif
                </div>
                <a href="{{ route('admin.computadores.show', $pc->id) }}"
                   class="flex-shrink-0 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                    Ver →
                </a>
            </div>

            {{-- Componentes en grid visual --}}
            <div class="px-5 py-3">
                @php
                    $total      = count($tipos);
                    $instalados = $activos->count();
                    $pct        = $total > 0 ? round($instalados / $total * 100) : 0;
                @endphp
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Componentes
                    </p>
                    <span class="text-xs font-bold {{ $instalados === $total ? 'text-green-600 dark:text-green-400' : ($instalados > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-500 dark:text-red-400') }}">
                        {{ $instalados }}/{{ $total }}
                    </span>
                </div>
                {{-- Barra de progreso --}}
                <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 mb-3">
                    <div class="h-1.5 rounded-full transition-all {{ $instalados === $total ? 'bg-green-500' : ($instalados > 0 ? 'bg-yellow-400' : 'bg-red-400') }}"
                         style="width: {{ $pct }}%"></div>
                </div>
                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
                    @foreach($tipos as $tipoKey => $tipoLabel)
                    @php $tiene = in_array($tipoKey, $tiposActivos); @endphp
                    <div class="flex items-center gap-1.5 text-xs min-w-0">
                        @if($tiene)
                            <svg class="w-3.5 h-3.5 shrink-0 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="font-medium text-gray-700 dark:text-gray-200 truncate">{{ $tipoLabel }}</span>
                        @else
                            <svg class="w-3.5 h-3.5 shrink-0 text-red-400 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="text-gray-400 dark:text-gray-500 truncate">{{ $tipoLabel }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            @if($activos->isNotEmpty())
            <div class="px-5 py-2 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-500">Valorización:</span>
                <span class="text-xs font-bold text-gray-800">
                    ${{ number_format($pc->valorizacionTotal(), 0, ',', '.') }}
                </span>
            </div>
            @endif
        </div>
        @endforeach
    </div>
@endif

@endsection
