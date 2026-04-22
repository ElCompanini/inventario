@extends('layouts.app')

@section('title', 'Modificar Stock — ' . $producto->nombre)

@section('content')

<div class="mb-6">
    <a href="{{ route('dashboard') }}"
       class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 transition mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver a productos
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Modificar Stock Directamente</h1>
    <p class="text-sm text-gray-500 mt-1">Esta acción se registrará en el historial de cambios</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Card de info del producto --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">Información del Producto</h2>

        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Nombre</span>
                <span class="font-semibold text-gray-800">{{ $producto->nombre }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Contenedor</span>
                <span class="font-semibold text-gray-800">{{ $producto->container->nombre ?? 'Sin container' }}</span>
            </div>
            @if($producto->descripcion)
            <div>
                <span class="text-gray-500">Descripción</span>
                <p class="text-gray-800 mt-1">{{ $producto->descripcion }}</p>
            </div>
            @endif

            <div class="pt-3 border-t space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Stock actual</span>
                    @php $estado = $producto->estadoStock(); @endphp
                    <span class="text-2xl font-bold
                        {{ $estado === 'critico' ? 'text-red-600' : ($estado === 'minimo' ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ $producto->stock_actual }}
                    </span>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>Stock mínimo: <strong>{{ $producto->stock_minimo }}</strong></span>
                    <span>Stock crítico: <strong>{{ $producto->stock_critico }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario de modificación --}}
    <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">Registrar Movimiento</h2>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.productos.stock', $producto->id) }}" id="formStock" novalidate>
            @csrf

            {{-- Tipo --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de movimiento <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 border-2 rounded-lg px-4 py-3 cursor-pointer transition
                                  has-[:checked]:border-green-500 has-[:checked]:bg-green-50 border-gray-200 hover:border-gray-300">
                        <input type="radio" name="tipo" value="entrada"
                               class="text-green-600"
                               {{ old('tipo', 'entrada') === 'entrada' ? 'checked' : '' }}>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">↑ Entrada</p>
                            <p class="text-xs text-gray-500">Suma al stock</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 border-2 rounded-lg px-4 py-3 cursor-pointer transition
                                  has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 border-gray-200 hover:border-gray-300">
                        <input type="radio" name="tipo" value="salida"
                               class="text-orange-500"
                               {{ old('tipo') === 'salida' ? 'checked' : '' }}>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">↓ Salida</p>
                            <p class="text-xs text-gray-500">Resta del stock</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Cantidad --}}
            <div class="mb-5">
                <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">
                    Cantidad <span class="text-red-500">*</span>
                </label>
                <input type="number" name="cantidad" id="cantidad"
                       value="{{ old('cantidad') }}"
                       min="1" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-indigo-500
                              {{ $errors->has('cantidad') ? 'border-red-400 bg-red-50' : '' }}"
                       placeholder="Ej: 50">
                <p class="text-xs text-gray-400 mt-1">Stock disponible: {{ $producto->stock_actual }}</p>
            </div>

            {{-- Motivo --}}
            <div class="mb-5">
                <label for="motivo" class="block text-sm font-medium text-gray-700 mb-1">
                    Motivo <span class="text-red-500">*</span>
                </label>
                <textarea name="motivo" id="motivo"
                          rows="3" required maxlength="500"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-indigo-500
                                 {{ $errors->has('motivo') ? 'border-red-400 bg-red-50' : '' }}"
                          placeholder="Describe el motivo del movimiento directo de stock...">{{ old('motivo') }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="button"
                        onclick="abrirModalStock()"
                        class="btn-primary flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
                               py-2.5 rounded-lg text-sm">
                    Confirmar Modificación
                </button>
                <button type="button"
                        onclick="abrirModalCancelarStock()"
                        class="btn-secondary flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg text-sm">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Sección Trasladar Container (oculta por defecto) --}}
<div class="mt-6 bg-white rounded-xl shadow p-6 hidden" id="seccion-traslado">
    <h2 class="text-base font-semibold text-gray-700 mb-1 pb-2 border-b">Trasladar a otro Container</h2>
    <p class="text-xs text-gray-400 mb-4">Mueve este producto a un container diferente. La acción queda registrada en el historial.</p>

    @if($errors->has('contenedor_destino'))
        <div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
            {{ $errors->first('contenedor_destino') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.productos.trasladar', $producto->id) }}" id="formTraslado" novalidate>
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
            <div>
                <label for="contenedor_destino" class="block text-sm font-medium text-gray-700 mb-1">
                    Container destino <span class="text-red-500">*</span>
                </label>
                <select name="contenedor_destino" id="contenedor_destino" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500
                               {{ $errors->has('contenedor_destino') ? 'border-red-400 bg-red-50' : '' }}">
                    <option value="">— Seleccionar container —</option>
                    @foreach($containers as $container)
                        @if($container->id != $producto->contenedor)
                            <option value="{{ $container->id }}" {{ old('contenedor_destino') == $container->id ? 'selected' : '' }}>
                                {{ $container->nombre }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label for="motivo_traslado" class="block text-sm font-medium text-gray-700 mb-1">
                    Motivo <span class="text-red-500">*</span>
                </label>
                <input type="text" name="motivo" id="motivo_traslado" required maxlength="500"
                       value="{{ old('motivo') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="Describe el motivo del traslado...">
            </div>
        </div>
        <div class="mt-4">
            <button type="button"
                    onclick="abrirModalTraslado()"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg transition text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Confirmar Traslado
            </button>
        </div>
    </form>
</div>


{{-- Modal: confirmar cancelación --}}
<div id="modalCancelarStock" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" style="animation: modal-prod-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <h2 class="text-lg font-bold text-gray-800 mb-1">¿Cancelar modificación?</h2>
        <p class="text-sm text-gray-500 mb-6">Los datos ingresados se perderán. ¿Deseas volver al listado de productos?</p>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="cerrarModalCancelarStock()"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Seguir editando
            </button>
            <a href="{{ route('dashboard') }}"
               class="btn-danger px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">
                Sí, cancelar
            </a>
        </div>
    </div>
</div>

{{-- Modal: confirmar modificación de stock --}}
<div id="modalStock" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" style="animation: modal-prod-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Confirmar modificación de stock</h2>
        <p class="text-sm text-gray-500 mb-6">Esta acción se registrará en el historial de cambios y no puede deshacerse.</p>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="cerrarModalStock()"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Cancelar
            </button>
            <button type="button" onclick="document.getElementById('formStock').submit()"
                    class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                Confirmar
            </button>
        </div>
    </div>
</div>

{{-- Modal: confirmar traslado de container --}}
<div id="modalTraslado" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" style="animation: modal-prod-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Confirmar traslado de container</h2>
        <p class="text-sm text-gray-500 mb-6">El producto será movido al container seleccionado. Esta acción quedará registrada en el historial.</p>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="cerrarModalTraslado()"
                    class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                Cancelar
            </button>
            <button type="button" onclick="document.getElementById('formTraslado').submit()"
                    class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                Confirmar
            </button>
        </div>
    </div>
</div>

@push('head')
<style>
    @keyframes modal-prod-in {
        from { opacity:0; transform:scale(.94); }
        to   { opacity:1; transform:scale(1); }
    }
</style>
@endpush

@push('scripts')
<script>
    function abrirModalCancelarStock() {
        var cantidad = document.getElementById('cantidad').value.trim();
        var motivo   = document.getElementById('motivo').value.trim();
        if (cantidad || motivo) {
            var m = document.getElementById('modalCancelarStock');
            m.classList.remove('hidden'); m.classList.add('flex');
        } else {
            window.location = '{{ route('dashboard') }}';
        }
    }
    function cerrarModalCancelarStock() {
        var m = document.getElementById('modalCancelarStock');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    document.getElementById('modalCancelarStock').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalCancelarStock();
    });

    function abrirModalStock() {
        var m = document.getElementById('modalStock');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function cerrarModalStock() {
        var m = document.getElementById('modalStock');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    document.getElementById('modalStock').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalStock();
    });

    function abrirModalTraslado() {
        var m = document.getElementById('modalTraslado');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function cerrarModalTraslado() {
        var m = document.getElementById('modalTraslado');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    document.getElementById('modalTraslado').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalTraslado();
    });
</script>
@endpush

@endsection
