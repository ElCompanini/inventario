@extends('layouts.app')
@section('title', 'Centros de Costo')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Centros de Costo</h1>
        <p class="text-sm text-gray-500 mt-1">Administra los centros de costo disponibles para asignar a usuarios.</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Formulario nuevo --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-bold text-gray-700 mb-3">Agregar centro de costo</h2>
        <form method="POST" action="{{ route('dev.centros-costo.store') }}" class="flex gap-2">
            @csrf
            <input type="text" name="nombre" value="{{ old('nombre') }}" required
                   placeholder="Ej: TIC"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                Agregar
            </button>
        </form>
        @error('nombre')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Lista --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        @if($centros->isEmpty())
            <p class="text-sm text-gray-400 p-5">No hay centros de costo creados.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left">Nombre</th>
                        <th class="px-5 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($centros as $centro)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-mono font-medium text-gray-800">{{ $centro->nombre }}</td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('dev.centros-costo.destroy', $centro->id) }}"
                                  onsubmit="return confirm('¿Eliminar {{ $centro->nombre }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-xs text-red-500 hover:text-red-700 font-medium">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

@endsection
