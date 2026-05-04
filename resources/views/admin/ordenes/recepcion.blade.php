@extends('layouts.app')

@section('title', 'Recepción OC ' . $oc->numero_oc)

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.ordenes.show', $oc->id) }}" class="text-sm text-indigo-600 hover:underline">← Volver a OC {{ $oc->numero_oc }}</a>
    <h1 class="text-2xl font-bold text-gray-800 mt-1">Registrar Recepción</h1>
    <p class="text-sm text-gray-500 mt-1">
        Ingresa las cantidades <strong>realmente recibidas</strong> para cada producto.
        Las cantidades de stock se actualizarán al confirmar.
    </p>
</div>


<form id="form-recepcion" method="POST" action="{{ route('admin.ordenes.recepcion.procesar', $oc->id) }}">
    @csrf

    <div class="space-y-4">
        @foreach($oc->sicds as $sicd)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-700 font-mono">{{ $sicd->codigo_sicd }}</h2>
                    @if($sicd->descripcion)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $sicd->descripcion }}</p>
                    @endif
                </div>

                @if($sicd->detalles->isEmpty())
                    <div class="px-5 py-4 text-sm text-gray-400">Sin productos en este SICD.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th colspan="6" class="px-4 py-1"></th>
                                <th class="px-4 py-1 text-left">
                                    <div style="border:2px solid #3b82f6; border-radius:0.5rem; overflow:hidden;">
                                        <p style="font-size:0.75rem; font-weight:700; color:#1d4ed8; background:#dbeafe; padding:4px 8px;">Cambiar todos los contenedores a:</p>
                                        <select id="container-global"
                                                style="width:100%; padding:0.375rem 0.5rem; font-size:0.75rem; font-weight:600; color:#1d4ed8; background:#fff; outline:none; border:none;">
                                            <option value="">— seleccionar —</option>
                                            @foreach($containers as $c)
                                                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </th>
                            </tr>
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Producto</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Stock actual</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600">Solicitado</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600">Cantidad recibida</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Precio neto</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Total neto</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Container destino</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($sicd->detalles as $det)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3" style="vertical-align:middle;">
                                        @if($det->producto)
                                            <p class="font-medium text-gray-800">{{ $det->producto->nombre }}</p>
                                            @if($det->producto->nombre !== $det->nombre_producto_excel)
                                                <p class="text-xs text-gray-400 mt-0.5">Excel: {{ $det->nombre_producto_excel }}</p>
                                            @endif
                                        @else
                                            <p class="font-medium text-gray-800">{{ $det->nombre_producto_excel }}</p>
                                            <p class="text-xs text-amber-500 mt-0.5">Sin enlace a producto — no actualizará stock</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600" style="vertical-align:middle;">
                                        {{ $det->producto?->stock_actual ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-700" style="vertical-align:middle;">
                                        {{ $det->cantidad_solicitada }}
                                    </td>
                                    <td class="px-4 py-3 text-center" style="vertical-align:middle;">
                                        <input type="number"
                                               name="recibido[{{ $det->id }}]"
                                               data-solicitado="{{ $det->cantidad_solicitada }}"
                                               data-detid="{{ $det->id }}"
                                               value="{{ old("recibido.{$det->id}", $det->cantidad_solicitada) }}"
                                               min="0"
                                               {{ !$det->producto ? 'disabled' : '' }}
                                               class="input-recibido w-24 border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-400
                                                      {{ !$det->producto ? 'bg-gray-100 text-gray-400' : '' }}">
                                        @error("recibido.{$det->id}")
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3 text-right" style="vertical-align:middle;">
                                        <div style="display:inline-block; width:7.5rem;">
                                            <input type="text"
                                                   data-precio
                                                   data-sicd="{{ $det->precio_neto ? (int) $det->precio_neto : '' }}"
                                                   name="precio_neto[{{ $det->id }}]"
                                                   value="{{ old("precio_neto.{$det->id}", $det->precio_neto ? (int) $det->precio_neto : '') }}"
                                                   placeholder="—"
                                                   style="width:100%;"
                                                   class="input-precio border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none">
                                            @if($det->precio_neto)
                                                <span style="font-size:0.78rem; color:#111827; white-space:nowrap;"><strong>SICD:</strong> ${{ number_format($det->precio_neto, 0, ',', '.') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right" style="vertical-align:middle;">
                                        <div style="display:inline-block; width:7.5rem;">
                                            <input type="text"
                                                   data-precio
                                                   data-sicd="{{ $det->total_neto ? (int) $det->total_neto : '' }}"
                                                   name="total_neto[{{ $det->id }}]"
                                                   value="{{ old("total_neto.{$det->id}", $det->total_neto ? (int) $det->total_neto : '') }}"
                                                   placeholder="—"
                                                   style="width:100%;"
                                                   class="input-precio border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none">
                                            @if($det->total_neto)
                                                <span style="font-size:0.78rem; color:#111827; white-space:nowrap;"><strong>SICD:</strong> ${{ number_format($det->total_neto, 0, ',', '.') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3" style="vertical-align:middle;">
                                        @if($det->producto)
                                            <select name="container[{{ $det->id }}]"
                                                    style="width:100%; border:2px solid #3b82f6; border-radius:0.5rem; padding:0.375rem 0.5rem; font-size:0.75rem; font-weight:600; color:#1d4ed8; background:#eff6ff; outline:none;"
                                                    class="w-full">
                                                @foreach($containers as $c)
                                                    <option value="{{ $c->id }}"
                                                        {{ $det->producto->contenedor == $c->id ? 'selected' : '' }}>
                                                        {{ $c->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr id="motivo-row-{{ $det->id }}"
                                    style="display:none; background:#fff7ed;">
                                    <td colspan="7" class="px-4 py-3" style="border-top:1px dashed #fed7aa;">
                                        <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                                            <span style="font-size:0.8rem; font-weight:700; color:#c2410c; white-space:nowrap; padding-top:0.4rem;">
                                                ⚠ Cantidad diferente — Motivo:
                                            </span>
                                            <textarea name="motivo_recepcion[{{ $det->id }}]"
                                                      rows="2"
                                                      placeholder="Indica el motivo por el que la cantidad recibida difiere de la solicitada..."
                                                      style="flex:1; border:1.5px solid #f97316; border-radius:0.5rem; padding:0.375rem 0.625rem; font-size:0.8rem; color:#7c2d12; background:#fff; resize:vertical; outline:none;"
                                                      onfocus="this.style.borderColor='#ea580c'; this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.2)'"
                                                      onblur="this.style.borderColor='#f97316'; this.style.boxShadow='none'">{{ old("motivo_recepcion.{$det->id}") }}</textarea>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('admin.ordenes.show', $oc->id) }}"
           style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:600; color:#fff; background:#f87171; border-radius:0.5rem; text-decoration:none; transition:background .2s;"
           onmouseover="this.style.background='#ef4444'"
           onmouseout="this.style.background='#f87171'">
            Cancelar
        </a>
        <button type="button" onclick="abrirConfirmRecepcion()"
                class="px-6 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
            Confirmar recepción →
        </button>
        <button type="submit" id="btn-submit-real" style="display:none">Confirmar</button>
    </div>
</form>

{{-- Modal confirmación recepción --}}
<div id="modal-confirm-recepcion"
     style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,.5); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:420px; max-width:calc(100vw - 2rem); animation:recep-in .2s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="padding:1.5rem 1.5rem 1rem;">
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                <div style="width:2.5rem; height:2.5rem; border-radius:9999px; background:#dcfce7; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size:1rem; font-weight:700; color:#111827; margin:0;">Confirmar recepción</p>
                    <p style="font-size:0.8rem; color:#6b7280; margin:0.1rem 0 0;">OC <strong style="color:#4f46e5;">{{ $oc->numero_oc }}</strong></p>
                </div>
            </div>
            <p style="font-size:0.85rem; color:#374151; line-height:1.6; margin:0;">
                Esta acción <strong>actualizará el stock</strong> de todos los productos recibidos y marcará la OC como recibida.<br>
                <span style="color:#dc2626; font-size:0.78rem;">⚠ Esta acción no se puede deshacer.</span>
            </p>
        </div>
        <div style="padding:0.75rem 1.5rem 1.25rem; display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button" onclick="cerrarConfirmRecepcion()"
                    style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                Cancelar
            </button>
            <button type="button" onclick="confirmarRecepcion()"
                    style="padding:0.5rem 1.25rem; font-size:0.875rem; font-weight:600; color:#fff; background:#16a34a; border:none; border-radius:0.5rem; cursor:pointer; transition:background .15s;"
                    onmouseover="this.style.background='#15803d'"
                    onmouseout="this.style.background='#16a34a'">
                Sí, confirmar →
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('container-global').addEventListener('change', function () {
        const val = this.value;
        if (!val) return;
        document.querySelectorAll('select[name^="container["]').forEach(function (sel) {
            sel.value = val;
        });
    });

    // ── Precio: formato visual $100.000 ─────────────────────────────────────
    function colorearSegunSicd(input) {
        const sicd = parseInt(input.dataset.sicd) || null;
        const raw  = parseInt(input.value.replace(/\$/g,'').replace(/\./g,'').replace(/[^0-9]/g,'')) || null;
        if (!sicd || !raw) { input.style.borderColor = ''; input.style.background = ''; return; }
        if (raw === sicd) {
            input.style.borderColor = '#22c55e';
            input.style.background  = '#f0fdf4';
        } else {
            input.style.borderColor = '#f97316';
            input.style.background  = '#fff7ed';
        }
    }

    function formatearPrecio(input) {
        const raw = input.value.replace(/\./g, '').replace(/[^0-9]/g, '');
        if (!raw) { input.value = ''; return; }
        input.value = '$' + parseInt(raw).toLocaleString('es-CL');
    }

    function rawNum(input) {
        return parseInt(input.value.replace(/\$/g, '').replace(/\./g, '').replace(/[^0-9]/g, '')) || 0;
    }

    function recalcularTotal(precioInput) {
        const name = precioInput.getAttribute('name'); // precio_neto[123]
        const id = name.match(/\[(\d+)\]/)?.[1];
        if (!id) return;
        const cantInput  = document.querySelector(`input[name="recibido[${id}]"]`);
        const totalInput = document.querySelector(`input[name="total_neto[${id}]"]`);
        if (!cantInput || !totalInput) return;
        const precio = rawNum(precioInput);
        const cant   = parseInt(cantInput.value) || 0;
        const total  = precio * cant;
        totalInput.value = total ? '$' + total.toLocaleString('es-CL') : '';
        colorearSegunSicd(totalInput);
    }

    document.querySelectorAll('.input-precio').forEach(function(input) {
        if (input.value) { formatearPrecio(input); colorearSegunSicd(input); }

        input.addEventListener('input', function () {
            const raw = this.value.replace(/\$/g, '').replace(/\./g, '').replace(/[^0-9]/g, '');
            if (!raw) { this.value = ''; } else { this.value = '$' + parseInt(raw).toLocaleString('es-CL'); }
            colorearSegunSicd(this);
            if (this.name && this.name.startsWith('precio_neto')) recalcularTotal(this);
        });
    });

    // También recalcular si cambia la cantidad recibida + mostrar motivo si difiere
    function verificarMotivo(cantInput) {
        const id         = cantInput.dataset.detid;
        const solicitado = parseInt(cantInput.dataset.solicitado) || 0;
        const recibido   = parseInt(cantInput.value) || 0;
        const motivoRow  = document.getElementById('motivo-row-' + id);
        if (!motivoRow) return;
        const textarea = motivoRow.querySelector('textarea');
        if (recibido !== solicitado) {
            motivoRow.style.display = '';
            if (textarea) textarea.required = true;
        } else {
            motivoRow.style.display = 'none';
            if (textarea) { textarea.required = false; textarea.value = ''; }
        }
    }

    document.querySelectorAll('.input-recibido').forEach(function(cantInput) {
        // Verificar al cargar (por si hay old() con valor distinto)
        verificarMotivo(cantInput);

        cantInput.addEventListener('input', function () {
            const id = this.name.match(/\[(\d+)\]/)?.[1];
            if (!id) return;
            const precioInput = document.querySelector(`input[name="precio_neto[${id}]"]`);
            if (precioInput) recalcularTotal(precioInput);
            verificarMotivo(this);
        });
    });

    // Antes de enviar: convertir a número puro
    document.getElementById('form-recepcion').addEventListener('submit', function () {
        document.querySelectorAll('.input-precio').forEach(function(input) {
            input.value = input.value.replace(/\$/g, '').replace(/\./g, '').replace(/[^0-9]/g, '');
        });
    });

    function abrirConfirmRecepcion() {
        document.getElementById('modal-confirm-recepcion').style.display = 'flex';
    }
    function cerrarConfirmRecepcion() {
        document.getElementById('modal-confirm-recepcion').style.display = 'none';
    }
    function confirmarRecepcion() {
        cerrarConfirmRecepcion();
        var form = document.getElementById('form-recepcion');
        if (form.requestSubmit) {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }
    document.getElementById('modal-confirm-recepcion').addEventListener('click', function(e) {
        if (e.target === this) cerrarConfirmRecepcion();
    });
</script>
@endpush

@push('head')
<style>
@keyframes recep-in { from { opacity:0; transform:scale(.95) translateY(-8px); } to { opacity:1; transform:none; } }
</style>
@endpush

@endsection
