@extends('layouts.app')
@section('title', 'Retiro de Piezas')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Retiro de Piezas</h1>
    <p class="text-sm text-gray-500 mt-1">
        @if(auth()->user()->esAdmin())
            Como administrador, el retiro descuenta el stock directamente.
        @else
            Tu solicitud quedará pendiente de aprobación del administrador.
        @endif
    </p>
</div>

@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
        {{ $errors->first() }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ── BUSCADOR ── --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-bold text-gray-700 mb-3">1. Buscar producto</h2>

        <div class="relative">
            <input type="text" id="buscador-retiro"
                   placeholder="🔍  Buscar por descripción o categoría..."
                   autocomplete="off"
                   class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">

            {{-- Spinner --}}
            <span id="spinner" class="hidden absolute right-3 top-3 text-gray-400 text-xs">Buscando...</span>
        </div>

        {{-- Resultados --}}
        <div id="resultados" class="mt-2 space-y-1 max-h-80 overflow-y-auto"></div>
        <p id="sin-resultados" class="hidden text-xs text-gray-400 mt-2 text-center">Sin resultados.</p>
    </div>

    {{-- ── CARRITO ── --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-bold text-gray-700 mb-3">2. Piezas seleccionadas</h2>

        <div id="carrito-vacio" class="text-center py-8 text-gray-400 text-sm">
            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
            </svg>
            Agrega productos desde el buscador
        </div>

        <div id="carrito-lista" class="space-y-2"></div>
    </div>
</div>

{{-- ── FORMULARIO DE ENVÍO ── --}}
<form method="POST" action="{{ route('retiro.procesar') }}" id="form-retiro" class="mt-6 bg-white rounded-xl shadow p-5">
    @csrf

    {{-- Inputs ocultos del carrito (se generan por JS) --}}
    <div id="inputs-ocultos"></div>

    <div class="mb-4">
        <label for="motivo_retiro" class="block text-sm font-semibold text-gray-700 mb-1">
            Motivo de retiro <span class="text-red-500">*</span>
        </label>
        <textarea id="motivo_retiro" name="motivo_retiro" rows="3" required
                  placeholder="Describe el motivo del retiro de piezas..."
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                         focus:outline-none focus:ring-2 focus:ring-indigo-400">{{ old('motivo_retiro') }}</textarea>
    </div>

    <div class="flex items-center justify-between gap-4">
        <p id="resumen-carrito" class="text-sm text-gray-500">Carrito vacío</p>
        <button type="submit" id="btn-enviar" disabled
                class="px-6 py-2.5 text-sm font-semibold text-white rounded-lg transition
                       bg-gray-300 cursor-not-allowed"
                onclick="return confirmarRetiro()">
            @if(auth()->user()->esAdmin())
                Procesar retiro
            @else
                Enviar solicitud
            @endif
        </button>
    </div>
</form>

@push('scripts')
<script>
    // ── Estado del carrito ────────────────────────────────────────────────────
    let carrito = {};   // { id: { id, nombre, descripcion, stock_actual, cantidad } }
    let timerBusqueda;

    // ── Buscador con debounce ─────────────────────────────────────────────────
    document.getElementById('buscador-retiro').addEventListener('input', function () {
        clearTimeout(timerBusqueda);
        const q = this.value.trim();

        if (q.length < 2) {
            document.getElementById('resultados').innerHTML = '';
            document.getElementById('sin-resultados').classList.add('hidden');
            return;
        }

        document.getElementById('spinner').classList.remove('hidden');

        timerBusqueda = setTimeout(() => {
            fetch(`{{ route('retiro.buscar') }}?q=` + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(productos => {
                document.getElementById('spinner').classList.add('hidden');
                renderResultados(productos);
            })
            .catch(() => document.getElementById('spinner').classList.add('hidden'));
        }, 300);
    });

    function renderResultados(productos) {
        const cont = document.getElementById('resultados');
        const sinRes = document.getElementById('sin-resultados');

        if (!productos.length) {
            cont.innerHTML = '';
            sinRes.classList.remove('hidden');
            return;
        }

        sinRes.classList.add('hidden');
        cont.innerHTML = productos.map(p => `
            <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg border border-gray-200
                        hover:bg-indigo-50 hover:border-indigo-300 cursor-pointer transition"
                 onclick="agregarAlCarrito(${p.id}, '${escHtml(p.nombre)}', '${escHtml(p.descripcion)}', ${p.stock_actual})">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-indigo-700 truncate">${escHtml(p.nombre)}</p>
                    <p class="text-xs text-gray-600 truncate">${escHtml(p.descripcion)}</p>
                </div>
                <span class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full
                    ${p.stock_actual <= 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700'}">
                    Stock: ${p.stock_actual}
                </span>
            </div>
        `).join('');
    }

    // ── Carrito ───────────────────────────────────────────────────────────────
    function agregarAlCarrito(id, nombre, descripcion, stockActual) {
        if (carrito[id]) {
            carrito[id].cantidad = Math.min(carrito[id].cantidad + 1, stockActual);
        } else {
            if (stockActual <= 0) {
                alert('Este producto no tiene stock disponible.');
                return;
            }
            carrito[id] = { id, nombre, descripcion, stockActual, cantidad: 1 };
        }
        renderCarrito();
        document.getElementById('buscador-retiro').value = '';
        document.getElementById('resultados').innerHTML = '';
    }

    function cambiarCantidad(id, valor) {
        const cant = parseInt(valor);
        if (isNaN(cant) || cant < 1) {
            carrito[id].cantidad = 1;
        } else if (cant > carrito[id].stockActual) {
            carrito[id].cantidad = carrito[id].stockActual;
        } else {
            carrito[id].cantidad = cant;
        }
        renderCarrito();
    }

    function quitarDelCarrito(id) {
        delete carrito[id];
        renderCarrito();
    }

    function renderCarrito() {
        const lista     = document.getElementById('carrito-lista');
        const vacio     = document.getElementById('carrito-vacio');
        const inputs    = document.getElementById('inputs-ocultos');
        const resumen   = document.getElementById('resumen-carrito');
        const btnEnviar = document.getElementById('btn-enviar');
        const items     = Object.values(carrito);

        if (!items.length) {
            lista.innerHTML = '';
            inputs.innerHTML = '';
            vacio.classList.remove('hidden');
            resumen.textContent = 'Carrito vacío';
            btnEnviar.disabled = true;
            btnEnviar.className = 'px-6 py-2.5 text-sm font-semibold text-white rounded-lg transition bg-gray-300 cursor-not-allowed';
            return;
        }

        vacio.classList.add('hidden');

        lista.innerHTML = items.map((item, idx) => `
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-700 truncate">${escHtml(item.descripcion)}</p>
                    <p class="text-xs text-indigo-600">${escHtml(item.nombre)}</p>
                </div>
                <input type="number" min="1" max="${item.stockActual}" value="${item.cantidad}"
                       onchange="cambiarCantidad(${item.id}, this.value)"
                       class="w-16 text-center text-sm border border-gray-300 rounded-lg py-1 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <button type="button" onclick="quitarDelCarrito(${item.id})"
                        class="text-red-400 hover:text-red-600 transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `).join('');

        // Inputs ocultos para el form
        inputs.innerHTML = items.map((item, idx) => `
            <input type="hidden" name="items[${idx}][producto_id]" value="${item.id}">
            <input type="hidden" name="items[${idx}][cantidad]"    value="${item.cantidad}">
        `).join('');

        const totalPiezas = items.reduce((acc, i) => acc + i.cantidad, 0);
        resumen.textContent = `${items.length} producto(s) — ${totalPiezas} pieza(s) en total`;
        btnEnviar.disabled = false;
        btnEnviar.className = 'px-6 py-2.5 text-sm font-semibold text-white rounded-lg transition bg-indigo-600 hover:bg-indigo-700 cursor-pointer';
    }

    function confirmarRetiro() {
        const motivo = document.getElementById('motivo_retiro').value.trim();
        if (!motivo || motivo.length < 5) {
            alert('El motivo de retiro es obligatorio (mínimo 5 caracteres).');
            document.getElementById('motivo_retiro').focus();
            return false;
        }
        if (!Object.keys(carrito).length) {
            alert('El carrito está vacío.');
            return false;
        }
        return confirm('¿Confirmar el retiro de las piezas seleccionadas?');
    }

    // Escapa HTML para evitar XSS en el template literal
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
</script>
@endpush

@endsection
