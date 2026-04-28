@extends('layouts.app')
@section('title', 'Retiro de Piezas')

@section('content')

<div class="mb-3" style="max-width:900px; margin-left:auto; margin-right:auto;">
    <h1 class="text-xl font-bold text-gray-800">Retiro de Piezas</h1>
    <p class="text-sm text-gray-500 mt-0.5">
        @if(auth()->user()->esAdmin())
        Como administrador, el retiro descuenta el stock directamente.
        @else
        Tu solicitud quedará pendiente de aprobación del administrador.
        @endif
    </p>
</div>

@if(session('error'))
<div class="mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-3 py-2 text-sm" style="max-width:900px; margin-left:auto; margin-right:auto;">
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="mb-3 bg-red-50 border border-red-300 text-red-700 rounded-lg px-3 py-2 text-sm" style="max-width:900px; margin-left:auto; margin-right:auto;">
    {{ $errors->first() }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4" style="max-width:900px; margin-left:auto; margin-right:auto;">

    {{-- ── BUSCADOR ── --}}
    <div class="bg-white rounded-xl shadow p-4">
        <h2 class="text-sm font-bold text-gray-700 mb-2">1. Buscar producto</h2>

        <div>
            <input type="text" id="buscador-retiro"
                placeholder="🔍  Buscar por descripción o categoría..."
                autocomplete="off"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
            <p id="spinner" class="hidden text-sm text-gray-400 mt-2 text-center">Buscando...</p>
        </div>

        <div id="resultados" class="mt-2 space-y-1 max-h-56 overflow-y-auto"></div>
        <p id="sin-resultados" class="hidden text-sm text-gray-400 mt-2 text-center">Sin resultados.</p>
    </div>

    {{-- ── CARRITO ── --}}
    <div class="bg-white rounded-xl shadow p-4">
        <h2 class="text-md font-bold text-gray-700 mb-2">2. Piezas seleccionadas</h2>

        <div id="carrito-vacio" class="text-center py-6 text-gray-400 text-lg">
            <svg class="w-8 h-8 mx-auto mb-1.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
            </svg>
            Agrega productos desde el buscador
        </div>

        <div id="carrito-lista" class="space-y-1.5"></div>
    </div>
</div>
<br>
{{-- ── FORMULARIO DE ENVÍO ── --}}
<form method="POST" action="{{ route('retiro.procesar') }}" id="form-retiro"
    class="mt-4 bg-white rounded-xl shadow p-4" style="max-width:900px; margin-left:auto; margin-right:auto;">
    @csrf

    <div id="inputs-ocultos"></div>

    <div class="mb-3">
        <label for="motivo_retiro" class="block text-sm font-semibold text-gray-700 mb-1">
            Motivo de retiro <span class="text-red-500">*</span>
        </label>
        <textarea id="motivo_retiro" name="motivo_retiro" rows="2" required
            placeholder="Describe el motivo del retiro de piezas..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                         focus:outline-none focus:ring-2 focus:ring-indigo-400">{{ old('motivo_retiro') }}</textarea>
    </div>

    <div class="flex items-center justify-between gap-4">
        <p id="resumen-carrito" class="text-sm text-gray-500">Carrito vacío</p>
        <button type="submit" id="btn-enviar" disabled
            class="px-4 py-2 text-sm font-semibold text-white rounded-lg transition
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

{{-- ── Modal alerta ── --}}
<div id="retiro-alert-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem 2rem 1.5rem; max-width:380px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.18); animation:fadeInUp .18s ease;">
        <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.6rem;">
            <svg style="width:20px;height:20px;color:#ef4444;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <h3 style="font-size:0.95rem;font-weight:700;color:#1e293b;margin:0;">Atención</h3>
        </div>
        <p id="retiro-alert-msg" style="font-size:0.875rem;color:#64748b;margin:0 0 1.25rem;line-height:1.5;"></p>
        <div style="display:flex;justify-content:flex-end;">
            <button onclick="cerrarAlertaRetiro()" style="padding:0.45rem 1.25rem;background:#4f46e5;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;">Aceptar</button>
        </div>
    </div>
</div>

{{-- ── Modal confirmación retiro ── --}}
<div id="retiro-confirm-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem 2rem 1.5rem; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.18); animation:fadeInUp .18s ease;">
        <h3 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.5rem;">Confirmar retiro</h3>
        <hr style="border:none;border-top:1px solid #e2e8f0;margin:0 0 1rem;">
        <p style="font-size:0.875rem;color:#475569;margin:0 0 0.35rem;font-weight:600;">¿Confirmar el retiro de las piezas seleccionadas?</p>
        <p id="retiro-confirm-resumen" style="font-size:0.8125rem;color:#94a3b8;margin:0 0 1.4rem;"></p>
        <div style="display:flex;justify-content:flex-end;gap:0.6rem;">
            <button onclick="cerrarConfirmRetiro()" style="padding:0.45rem 1.1rem;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;">Cancelar</button>
            <button onclick="submitRetiro()" style="padding:0.45rem 1.25rem;background:#4f46e5;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;">Confirmar</button>
        </div>
    </div>
</div>

<style>
@keyframes fadeInUp {
    from { opacity:0; transform:translateY(12px); }
    to   { opacity:1; transform:translateY(0); }
}
</style>

@push('scripts')
<script>
    // ── Estado del carrito ────────────────────────────────────────────────────
    let carrito = {}; // { id: { id, nombre, stock_actual, cantidad } }
    let timerBusqueda;

    // ── Modales personalizados ────────────────────────────────────────────────
    function mostrarAlertaRetiro(msg) {
        document.getElementById('retiro-alert-msg').textContent = msg;
        document.getElementById('retiro-alert-modal').style.display = 'flex';
    }
    function cerrarAlertaRetiro() {
        document.getElementById('retiro-alert-modal').style.display = 'none';
    }
    function mostrarConfirmRetiro() {
        const items = Object.values(carrito);
        const totalPiezas = items.reduce((acc, i) => acc + i.cantidad, 0);
        const lista = items.map(i => {
            const meta = [i.familia, i.categoria].filter(Boolean).join(' › ');
            return `<div style="padding:0.4rem 0; border-bottom:1px solid #f1f5f9;">
                <span style="font-size:0.8125rem;font-weight:600;color:#1e293b;">${escHtml(i.nombre)}</span>
                ${meta ? `<span style="font-size:0.75rem;color:#94a3b8;margin-left:0.4rem;">${escHtml(meta)}</span>` : ''}
                <span style="float:right;font-size:0.8125rem;font-weight:600;color:#4f46e5;">${i.cantidad} u.</span>
            </div>`;
        }).join('');
        document.getElementById('retiro-confirm-resumen').innerHTML =
            lista + `<p style="font-size:0.8125rem;color:#94a3b8;margin:0.6rem 0 0;text-align:right;">${items.length} producto(s) — ${totalPiezas} pieza(s) en total</p>`;
        document.getElementById('retiro-confirm-modal').style.display = 'flex';
    }
    function cerrarConfirmRetiro() {
        document.getElementById('retiro-confirm-modal').style.display = 'none';
    }
    function submitRetiro() {
        cerrarConfirmRetiro();
        document.getElementById('form-retiro').submit();
    }

    // Cerrar modales al hacer clic en el backdrop
    ['retiro-alert-modal', 'retiro-confirm-modal'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });

    // ── Buscador con debounce ─────────────────────────────────────────────────
    document.getElementById('buscador-retiro').addEventListener('input', function() {
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
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
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
                 onclick="agregarAlCarrito(${p.id}, '${escHtml(p.nombre)}', ${p.stock_actual}, '${escHtml(p.categoria)}', '${escHtml(p.familia)}')">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-indigo-700 truncate">${escHtml(p.nombre)}</p>
                    ${p.familia || p.categoria ? `<p class="text-[10px] text-gray-400 truncate">${escHtml(p.familia)}${p.familia && p.categoria ? ' › ' : ''}${escHtml(p.categoria)}</p>` : ''}
                </div>
                <span class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full
                    ${p.stock_actual <= 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700'}">
                    Stock: ${p.stock_actual}
                </span>
            </div>
        `).join('');
    }

    // ── Carrito ───────────────────────────────────────────────────────────────
    function agregarAlCarrito(id, nombre, stockActual, categoria, familia) {
        if (carrito[id]) {
            carrito[id].cantidad = Math.min(carrito[id].cantidad + 1, stockActual);
        } else {
            if (stockActual <= 0) {
                mostrarAlertaRetiro('Este producto no tiene stock disponible.');
                return;
            }
            carrito[id] = {
                id,
                nombre,
                stockActual,
                cantidad: 1,
                categoria: categoria || '',
                familia: familia || '',
            };
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
        const lista = document.getElementById('carrito-lista');
        const vacio = document.getElementById('carrito-vacio');
        const inputs = document.getElementById('inputs-ocultos');
        const resumen = document.getElementById('resumen-carrito');
        const btnEnviar = document.getElementById('btn-enviar');
        const items = Object.values(carrito);

        if (!items.length) {
            lista.innerHTML = '';
            inputs.innerHTML = '';
            vacio.classList.remove('hidden');
            resumen.textContent = 'Carrito vacío';
            btnEnviar.disabled = true;
            btnEnviar.className = 'px-4 py-2 text-sm font-semibold text-white rounded-lg transition bg-gray-300 cursor-not-allowed';
            return;
        }

        vacio.classList.add('hidden');

        lista.innerHTML = items.map((item, idx) => `
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-700 truncate">${escHtml(item.nombre)}</p>
                    ${(item.familia || item.categoria) ? `<p class="text-[10px] text-gray-400 truncate">${escHtml([item.familia, item.categoria].filter(Boolean).join(' › '))}</p>` : ''}
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
        btnEnviar.className = 'px-4 py-2 text-sm font-semibold text-white rounded-lg transition bg-indigo-600 hover:bg-indigo-700 cursor-pointer';
    }

    function confirmarRetiro() {
        const motivo = document.getElementById('motivo_retiro').value.trim();
        if (!motivo || motivo.length < 5) {
            mostrarAlertaRetiro('El motivo de retiro es obligatorio (mínimo 5 caracteres).');
            document.getElementById('motivo_retiro').focus();
            return false;
        }
        if (!Object.keys(carrito).length) {
            mostrarAlertaRetiro('El carrito está vacío.');
            return false;
        }
        mostrarConfirmRetiro();
        return false;
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