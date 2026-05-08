@extends('layouts.app')
@section('title', 'Armado de Computadoras')

@section('content')

<div class="mb-5 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Armado de Computadoras</h1>
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
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                    Componentes — {{ $activos->count() }} instalado(s)
                </p>
                <div class="grid grid-cols-2 gap-1">
                    @foreach($tipos as $tipoKey => $tipoLabel)
                    @php $tiene = in_array($tipoKey, $tiposActivos); @endphp
                    <div class="flex items-center gap-1.5 text-xs {{ $tiene ? 'text-green-700' : 'text-gray-300' }}">
                        <span>{{ $tiene ? '✔' : '✖' }}</span>
                        <span class="{{ $tiene ? 'font-medium' : '' }}">{{ $tipoLabel }}</span>
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
