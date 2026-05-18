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
    <h1 class="text-2xl font-bold text-gray-800">
        @if($producto->es_servicio) Detalle del Servicio @else Modificar Stock Directamente @endif
    </h1>
    <p class="text-sm text-gray-500 mt-1">
        @if($producto->es_servicio) Servicio administrativo — los registros se generan mediante SICD, OC o Gastos Menores @else Esta acción se registrará en el historial de cambios @endif
    </p>
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

            <div class="pt-3 border-t space-y-2">
            @if($producto->es_servicio)
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Tipo</span>
                    <span class="text-sm font-semibold" style="color:#1d4ed8;">Servicio Administrativo</span>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>Sin stock físico</span>
                    <span>Sin stock mínimo/crítico</span>
                </div>
            @else
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Stock actual</span>
                    @php $estado = $producto->estadoStock(); @endphp
                    <div class="text-right">
                        <span class="text-2xl font-bold
                            {{ $estado === 'critico' ? 'text-red-600' : ($estado === 'minimo' ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ $producto->stock_actual }}
                        </span>
                        @if($producto->tienePresentacion())
                            <p class="text-xs font-medium mt-0.5" style="color:#2563eb;">
                                {{ $producto->cantidadVisual((int)$producto->stock_actual) }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>Stock mínimo: <strong>{{ $producto->stock_minimo }}</strong></span>
                    <span>Stock crítico: <strong>{{ $producto->stock_critico }}</strong></span>
                </div>
            @endif
            </div>
        </div>
    </div>

    {{-- Formulario de modificación --}}
    <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
        @if($producto->es_servicio)
        {{-- Servicios: sin stock físico — mostrar aviso informativo --}}
        <div style="text-align:center; padding:2rem 1rem;">
            <div style="width:2.75rem;height:2.75rem;border-radius:9999px;background:#dbeafe;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <svg style="width:1.4rem;height:1.4rem;" fill="none" stroke="#1d4ed8" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
            </div>
            <p style="font-size:.9rem;font-weight:700;color:#1e3a5f;margin:0 0 .5rem;">Servicio Administrativo</p>
            <p style="font-size:.78rem;color:#6b7280;line-height:1.65;margin:0;">
                Este producto es un <strong>servicio administrativo</strong> y no maneja<br>
                stock físico, entradas ni salidas de bodega.<br>
                Los registros se generan a través de SICD, OC o Gastos Menores.
            </p>
        </div>
        @else
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
                    <label class="tipo-mov-label tipo-entrada flex items-center gap-3 border-2 rounded-lg px-4 py-3 cursor-pointer transition
                                  has-[:checked]:border-green-500 has-[:checked]:bg-green-50 border-gray-200 hover:border-gray-300">
                        <input type="radio" name="tipo" value="entrada"
                               class="text-green-600"
                               {{ old('tipo', 'entrada') === 'entrada' ? 'checked' : '' }}>
                        <div>
                            <p class="tipo-mov-title font-semibold text-gray-800 text-sm">↑ Entrada</p>
                            <p class="tipo-mov-sub text-xs text-gray-500">Suma al stock</p>
                        </div>
                    </label>
                    <label class="tipo-mov-label tipo-salida flex items-center gap-3 border-2 rounded-lg px-4 py-3 cursor-pointer transition
                                  has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 border-gray-200 hover:border-gray-300">
                        <input type="radio" name="tipo" value="salida"
                               class="text-orange-500"
                               {{ old('tipo') === 'salida' ? 'checked' : '' }}>
                        <div>
                            <p class="tipo-mov-title font-semibold text-gray-800 text-sm">↓ Salida</p>
                            <p class="tipo-mov-sub text-xs text-gray-500">Resta del stock</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Cantidad --}}
            <div class="mb-5">
                @if($producto->tienePresentacion())
                {{-- Salida: info visual de stock --}}
                <div id="salidaPkgInfo" style="display:none;"
                     class="salida-pkg-info mb-3 px-3 py-2 rounded-lg text-sm">
                    <span>
                        📦 Stock actual: <strong>{{ $producto->stock_actual }}</strong> unidades
                        &nbsp;= <strong>{{ $producto->cantidadVisual((int)$producto->stock_actual) }}</strong>
                    </span>
                </div>

                {{-- Entrada: toggle "ingresar por paquetes" --}}
                <div id="togglePkgWrap" style="display:flex;" class="mb-3 items-center gap-2">
                    <input type="checkbox" id="togglePkg"
                           class="w-4 h-4 border-gray-300 rounded focus:ring-blue-500"
                           style="accent-color:#2563eb;"
                           onchange="onTogglePkg()">
                    <label for="togglePkg" class="text-sm font-medium cursor-pointer" style="color:#1d4ed8;">
                        Ingresar por paquetes
                        <span class="font-normal text-xs ml-1" style="color:#9ca3af;">
                            (1 {{ $producto->tipo_presentacion }} = {{ $producto->cantidad_presentacion }} {{ $producto->unidad_base ?: 'unidad' }})
                        </span>
                    </label>
                </div>

                {{-- Paquetes input (visible cuando toggle ON) --}}
                <div id="pkgInputWrap" style="display:none;" class="mb-3">
                    <label for="pkg_cantidad" class="block text-sm font-medium text-gray-700 mb-1">
                        Cantidad en {{ $producto->tipo_presentacion }}s <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="pkg_cantidad"
                           min="1" step="1"
                           oninput="onPkgCantidadChange()"
                           class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border:1px solid #60a5fa; focus-ring-color:#2563eb;"
                           placeholder="Ej: 3">
                    <div id="pkgPreview" style="display:none;"
                         class="salida-pkg-info mt-2 px-3 py-2 rounded-lg text-sm font-medium"></div>
                </div>
                @endif

                {{-- Campo cantidad (unidades reales) --}}
                <div id="realCantidadWrap">
                    <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">
                        Cantidad <span class="text-red-500">*</span>
                        @if($producto->tienePresentacion())
                        <span id="labelUnidadesReales" class="font-normal text-xs text-gray-400">(unidades reales)</span>
                        @endif
                    </label>
                    <input type="number" name="cantidad" id="cantidad"
                           value="{{ old('cantidad') }}"
                           min="1" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500
                                  {{ $errors->has('cantidad') ? 'border-red-400 bg-red-50' : '' }}"
                           placeholder="Ej: 50">
                    <p class="text-xs text-gray-400 mt-1">
                        Stock disponible: {{ $producto->stock_actual }} unidades
                        @if($producto->tienePresentacion())
                        <span style="color:#2563eb;" class="font-medium">({{ $producto->cantidadVisual((int)$producto->stock_actual) }})</span>
                        @endif
                    </p>
                </div>
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
        @endif
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
        <p class="text-sm text-gray-500 mb-2">Esta acción se registrará en el historial de cambios y no puede deshacerse.</p>
        <div id="modalStockResumen" class="mb-5 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 font-medium"></div>
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

    /* Stock info box (salida paquetes) */
    .salida-pkg-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
    }
    html.dark .salida-pkg-info {
        background: rgba(37, 99, 235, 0.15);
        border: 1px solid rgba(96, 165, 250, 0.3);
        color: #93c5fd;
    }

    /* Dark mode — radio cards tipo movimiento */
    html.dark .tipo-mov-label {
        border-color: #374151;
    }
    html.dark .tipo-mov-label:hover {
        border-color: #4b5563;
    }
    html.dark .tipo-mov-title {
        color: #e5e7eb;
    }
    html.dark .tipo-mov-sub {
        color: #9ca3af;
    }
    html.dark .tipo-entrada:has(input:checked) {
        border-color: #16a34a !important;
        background: rgba(22, 163, 74, 0.15) !important;
    }
    html.dark .tipo-entrada:has(input:checked) .tipo-mov-title {
        color: #86efac;
    }
    html.dark .tipo-entrada:has(input:checked) .tipo-mov-sub {
        color: #4ade80;
    }
    html.dark .tipo-salida:has(input:checked) {
        border-color: #ea580c !important;
        background: rgba(234, 88, 12, 0.15) !important;
    }
    html.dark .tipo-salida:has(input:checked) .tipo-mov-title {
        color: #fdba74;
    }
    html.dark .tipo-salida:has(input:checked) .tipo-mov-sub {
        color: #fb923c;
    }
</style>
@endpush

@push('scripts')
<script>
    // ── Paquetes ──────────────────────────────────────────────────────────
    var HAS_PKG  = {{ $producto->tienePresentacion() ? 'true' : 'false' }};
    var PKG_QTY  = {{ $producto->tienePresentacion() ? (int)$producto->cantidad_presentacion : 0 }};
    var PKG_TIPO = '{{ addslashes($producto->tipo_presentacion ?? '') }}';
    var PKG_BASE = '{{ addslashes($producto->unidad_base ?? 'unidad') }}';
    var pkgModeActive = false;

    function onTipoChange() {
        if (!HAS_PKG) return;
        var tipo = (document.querySelector('input[name="tipo"]:checked') || {}).value;
        var toggleWrap = document.getElementById('togglePkgWrap');
        var salidaInfo  = document.getElementById('salidaPkgInfo');

        if (tipo === 'entrada') {
            toggleWrap.style.display = 'flex';
            salidaInfo.style.display  = 'none';
        } else {
            // salida — show visual info, hide package toggle
            toggleWrap.style.display = 'none';
            salidaInfo.style.display  = '';
            // reset pkg mode
            if (pkgModeActive) {
                document.getElementById('togglePkg').checked = false;
                onTogglePkg();
            }
        }
    }

    function onTogglePkg() {
        pkgModeActive = document.getElementById('togglePkg').checked;
        var pkgWrap  = document.getElementById('pkgInputWrap');
        var realWrap = document.getElementById('realCantidadWrap');
        if (pkgModeActive) {
            pkgWrap.style.display  = '';
            realWrap.style.display = 'none';
            document.getElementById('cantidad').removeAttribute('required');
            document.getElementById('cantidad').value = '';
            document.getElementById('pkg_cantidad').focus();
        } else {
            pkgWrap.style.display  = 'none';
            realWrap.style.display = '';
            document.getElementById('cantidad').setAttribute('required', 'required');
            document.getElementById('cantidad').value = '';
            document.getElementById('pkgPreview').style.display = 'none';
        }
    }

    function onPkgCantidadChange() {
        var pkgs    = parseInt(document.getElementById('pkg_cantidad').value, 10);
        var preview = document.getElementById('pkgPreview');
        if (!pkgs || pkgs < 1) {
            preview.style.display = 'none';
            document.getElementById('cantidad').value = '';
            return;
        }
        var real = pkgs * PKG_QTY;
        document.getElementById('cantidad').value = real;
        preview.style.display = '';
        preview.innerHTML = '<strong>' + pkgs + ' ' + PKG_TIPO + (pkgs > 1 ? 's' : '') + '</strong>'
            + ' &nbsp;=&nbsp; '
            + '<strong>' + real + ' ' + PKG_BASE + (real !== 1 ? 's' : '') + '</strong> reales';
    }

    // Wire tipo radio change
    document.querySelectorAll('input[name="tipo"]').forEach(function(r) {
        r.addEventListener('change', onTipoChange);
    });
    // Run on load to set initial state
    onTipoChange();

    // ── Cancelar ─────────────────────────────────────────────────────────
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

    // ── Confirmar stock ───────────────────────────────────────────────────
    function abrirModalStock() {
        // Package mode: validate and convert
        if (HAS_PKG && pkgModeActive) {
            var pkgs = parseInt(document.getElementById('pkg_cantidad').value, 10);
            if (!pkgs || pkgs < 1) {
                document.getElementById('pkg_cantidad').focus();
                return;
            }
            var real = pkgs * PKG_QTY;
            document.getElementById('cantidad').value = real;
            document.getElementById('cantidad').setAttribute('required', 'required');
        }

        // Build resumen for modal
        var tipo  = (document.querySelector('input[name="tipo"]:checked') || {}).value || '';
        var cant  = document.getElementById('cantidad').value;
        var label = tipo === 'entrada' ? '↑ Entrada' : '↓ Salida';
        var resumen = label + ': ' + cant + ' unidades';
        if (HAS_PKG && pkgModeActive) {
            var pkgs = parseInt(document.getElementById('pkg_cantidad').value, 10);
            resumen += ' (' + pkgs + ' ' + PKG_TIPO + (pkgs > 1 ? 's' : '') + ')';
        }
        document.getElementById('modalStockResumen').textContent = resumen;

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

    // ── Traslado ──────────────────────────────────────────────────────────
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
