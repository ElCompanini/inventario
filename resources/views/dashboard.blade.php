@extends('layouts.app')

@section('title', 'Productos')

@section('content')

<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Inventario de Productos</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $productos->count() }} producto(s) registrado(s)
        </p>
    </div>

    <div class="flex items-center gap-4 flex-wrap">
        @if(auth()->user()->esAdmin())
        <button type="button" id="btn-agregar-inventario"
            style="background:#2563eb; color:#fff; font-size:0.82rem; font-weight:600; padding:0.5rem 1.1rem; border-radius:0.5rem; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:0.4rem; transition:background .15s; white-space:nowrap;"
            onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Agregar Inventario
        </button>
        @endif

        {{-- Leyenda de colores --}}
        <div class="flex items-center gap-4 text-xs text-gray-600">
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-red-200 border border-red-300"></span>
                Stock crítico
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-yellow-100 border border-yellow-300"></span>
                Stock mínimo
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-white border border-gray-200"></span>
                Normal
            </span>
        </div>
    </div>
</div>

{{-- Errores de validación del formulario de solicitud --}}
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
    {{ $errors->first() }}
</div>
@endif

{{-- Buscador de productos --}}
<div class="mb-4">
    <input id="buscador-productos" type="text" placeholder="🔍  Buscar por categoría, descripción, contenedor, stock o estado..."
           class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

{{-- Tabla de productos --}}
<div class="bg-white rounded-xl shadow overflow-hidden p-4">

    <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
    <table id="tabla-inventario" class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">Producto</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 font-semibold text-gray-600" style="text-align:center;">Contenedor</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Stock Actual</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Mínimo</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Crítico</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $producto)
            @php
            $estado = $producto->estadoStock();
            $rowClass = match($estado) {
            'critico' => 'bg-red-50',
            'minimo' => 'bg-yellow-50',
            default => 'bg-white',
            };
            @endphp
            @php
                $pendienteSalida = $producto->solicitudes->sum('cantidad');
            @endphp
            <tr class="{{ $rowClass }} hover:brightness-95 transition">
                <td class="px-4 py-3 font-medium text-gray-900">
                    <div class="flex items-center gap-2">
                        @if($pendienteSalida > 0)
                            @if(auth()->user()->esAdmin())
                            <a href="{{ route('admin.solicitudes') }}"
                               style="position:relative; display:inline-flex; align-items:center; cursor:pointer; text-decoration:none;"
                               onmouseenter="this.querySelector('span').style.display='block'"
                               onmouseleave="this.querySelector('span').style.display='none'">
                            @else
                            <span style="position:relative; display:inline-flex; align-items:center; cursor:default;"
                                  onmouseenter="this.querySelector('span').style.display='block'"
                                  onmouseleave="this.querySelector('span').style.display='none'">
                            @endif
                                <svg width="20" height="20" viewBox="0 0 10 10" style="flex-shrink:0;">
                                    <circle cx="5" cy="5" r="5" fill="#f59e0b">
                                        <animate attributeName="opacity" values="1;0.4;1" dur="1.5s" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                                <span style="display:none; position:absolute; left:14px; top:50%; transform:translateY(-50%); z-index:9999;
                                             white-space:nowrap; background:#1f2937; color:#fff; font-size:11px; font-weight:500;
                                             padding:5px 10px; border-radius:6px; box-shadow:0 4px 8px rgba(0,0,0,.35);">
                                    ⏳ {{ $pendienteSalida }} unidad(es) de salida pendiente(s)<br>
                                    @foreach($producto->solicitudes as $sol)
                                        · {{ $sol->usuario->name ?? '—' }}: {{ $sol->cantidad }} u.<br>
                                    @endforeach
                                </span>
                            @if(auth()->user()->esAdmin())
                            </a>
                            @else
                            </span>
                            @endif
                        @endif

                        <span>{{ $producto->nombre }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $producto->descripcion ?? '—' }}</td>
                <td class="px-4 py-3" style="text-align:center; vertical-align:middle;">
                    @if($producto->container)
                        @if(auth()->user()->esAdmin())
                            <a href="{{ route('admin.containers.index') }}#container-{{ $producto->container->id }}"
                               style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.875rem; font-weight:700; padding:2px 12px; border-radius:9999px; text-decoration:none;">
                                {{ str_replace('Contenedor ', 'C', $producto->container->nombre) }}
                            </a>
                        @else
                            <span style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.875rem; font-weight:700; padding:2px 12px; border-radius:9999px;">
                                {{ str_replace('Contenedor ', 'C', $producto->container->nombre) }}
                            </span>
                        @endif
                    @else
                        <span style="display:inline-block; background:#e0e7ff; color:#4338ca; font-size:0.875rem; font-weight:700; padding:2px 12px; border-radius:9999px;">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center font-bold
                        {{ $estado === 'critico' ? 'text-red-700' : ($estado === 'minimo' ? 'text-yellow-700' : 'text-gray-800') }}">
                    {{ $producto->stock_actual }}
                </td>
                <td class="px-4 py-3 text-center text-gray-600">
                    @if($estado === 'minimo')
                        <span class="inline-block px-2 py-0.5 rounded-full estado-pulso-minimo">{{ $producto->stock_minimo }}</span>
                    @else
                        {{ $producto->stock_minimo }}
                    @endif
                </td>
                <td class="px-4 py-3 text-center text-gray-600">
                    @if($estado === 'critico')
                        <span class="inline-block px-2 py-0.5 rounded-full estado-pulso-critico">{{ $producto->stock_critico }}</span>
                    @else
                        {{ $producto->stock_critico }}
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($estado === 'critico')
                    <span style="position:relative; display:inline-flex; cursor:default;"
                          onmouseenter="this.querySelector('.tt').style.display='block'"
                          onmouseleave="this.querySelector('.tt').style.display='none'">
                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-sm font-semibold px-3 py-1.5 rounded-full estado-pulso-critico">
                            <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="#ef4444"><animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/></circle></svg>
                            Crítico
                        </span>
                        @if($producto->stock_critico_desde)
                        <span class="tt" style="display:none; position:absolute; left:50%; bottom:calc(100% + 6px); transform:translateX(-50%); z-index:9999;
                                     white-space:nowrap; background:#1f2937; color:#fff; font-size:11px; font-weight:500;
                                     padding:5px 10px; border-radius:6px; box-shadow:0 4px 8px rgba(0,0,0,.35);">
                            🔴 Crítico desde {{ $producto->stock_critico_desde->format('d/m/Y H:i') }}
                        </span>
                        @endif
                    </span>
                    @elseif($estado === 'minimo')
                    <span style="position:relative; display:inline-flex; cursor:default;"
                          onmouseenter="this.querySelector('.tt').style.display='block'"
                          onmouseleave="this.querySelector('.tt').style.display='none'">
                        <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-sm font-semibold px-3 py-1.5 rounded-full estado-pulso-minimo">
                            <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="#eab308"><animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/></circle></svg>
                            Mínimo
                        </span>
                        @if($producto->stock_minimo_desde)
                        <span class="tt" style="display:none; position:absolute; left:50%; bottom:calc(100% + 6px); transform:translateX(-50%); z-index:9999;
                                     white-space:nowrap; background:#1f2937; color:#fff; font-size:11px; font-weight:500;
                                     padding:5px 10px; border-radius:6px; box-shadow:0 4px 8px rgba(0,0,0,.35);">
                            🟡 Mínimo desde {{ $producto->stock_minimo_desde->format('d/m/Y H:i') }}
                        </span>
                        @endif
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-sm font-semibold px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Normal
                    </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex flex-col items-center gap-1.5">
                        @if(auth()->user()->esAdmin())
                        {{-- Admin: modificar stock directamente --}}
                        <a href="{{ route('admin.productos.editar', $producto->id) }}"
                            class="btn-accion-indigo inline-flex items-center gap-1 text-white text-xs font-medium px-2.5 py-1.5 rounded-lg whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Modificar
                        </a>
                        {{-- Admin: trasladar container --}}
                        <button type="button"
                            onclick="abrirModalTrasladar({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->contenedor }})"
                            class="btn-accion-blue inline-flex items-center gap-1 text-white text-xs font-medium px-2.5 py-1.5 rounded-lg whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Trasladar
                        </button>
                        @else
                        {{-- Usuario: solicitar entrada (solo si tiene permiso) --}}
                        @if(auth()->user()->tienePermiso('entrada'))
                        <button type="button"
                            onclick="abrirModal({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', 'entrada', {{ $producto->stock_actual }})"
                            class="btn-accion-green inline-flex items-center gap-1 text-white text-xs font-medium px-3 py-1.5 rounded-lg">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Entrada
                        </button>
                        @endif
                        {{-- Usuario: solicitar salida --}}
                        <button type="button"
                            onclick="abrirModal({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', 'salida', {{ $producto->stock_actual }})"
                            class="btn-accion-orange inline-flex items-center gap-1 text-white text-xs font-medium px-3 py-1.5 rounded-lg"
                            {{ $producto->stock_actual <= 0 ? 'disabled' : '' }}>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                            </svg>
                            Salida
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Modal solicitud (solo usuarios) --}}
@if(!auth()->user()->esAdmin())
<div id="modal-solicitud"
    class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div>
                <h3 class="text-lg font-semibold text-gray-800" id="modal-titulo">Solicitar movimiento</h3>
                <p class="text-sm text-gray-500" id="modal-subtitulo"></p>
            </div>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('solicitudes.store') }}" id="form-solicitud" novalidate>
            @csrf
            <input type="hidden" name="producto_id" id="modal-producto-id">
            <input type="hidden" name="tipo" id="modal-tipo">

            <div class="px-6 py-4 space-y-4">
                {{-- Stock actual info --}}
                <div class="bg-gray-50 rounded-lg px-4 py-2.5 text-sm text-gray-600 flex justify-between">
                    <span>Stock disponible:</span>
                    <span class="font-bold text-gray-800" id="modal-stock">—</span>
                </div>

                {{-- Cantidad --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cantidad <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="cantidad" id="modal-cantidad"
                        min="1" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Ej: 10">
                </div>

                {{-- Motivo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Motivo <span class="text-red-500">*</span>
                    </label>
                    <textarea name="motivo" id="modal-motivo"
                        rows="3" required maxlength="500"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Describe el motivo de esta solicitud..."></textarea>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex gap-3 justify-end">
                <button type="button" onclick="cerrarModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit" id="modal-btn-submit"
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg transition">
                    Enviar solicitud
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModal(productoId, nombre, tipo, stockActual) {
        document.getElementById('modal-producto-id').value = productoId;
        document.getElementById('modal-tipo').value = tipo;
        document.getElementById('modal-cantidad').value = '';
        document.getElementById('modal-motivo').value = '';
        document.getElementById('modal-stock').textContent = stockActual;

        const esEntrada = tipo === 'entrada';
        document.getElementById('modal-titulo').textContent =
            (esEntrada ? 'Solicitar Entrada' : 'Solicitar Salida') + ' — ' + nombre;
        document.getElementById('modal-subtitulo').textContent =
            esEntrada ? 'Añadir unidades al inventario' : 'Retirar unidades del inventario';

        const btn = document.getElementById('modal-btn-submit');
        if (esEntrada) {
            btn.className = 'px-4 py-2 text-sm font-medium text-white rounded-lg transition bg-green-600 hover:bg-green-700';
        } else {
            btn.className = 'px-4 py-2 text-sm font-medium text-white rounded-lg transition bg-orange-500 hover:bg-orange-600';
        }

        document.getElementById('modal-solicitud').classList.remove('hidden');
        document.getElementById('modal-cantidad').focus();
    }

    function cerrarModal() {
        document.getElementById('modal-solicitud').classList.add('hidden');
    }

    // Cerrar modal al hacer click fuera
    document.getElementById('modal-solicitud').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    // Validación del formulario antes de enviar
    document.getElementById('form-solicitud').addEventListener('submit', function(e) {
        const cantidad = parseInt(document.getElementById('modal-cantidad').value);
        const motivo = document.getElementById('modal-motivo').value.trim();
        const tipo = document.getElementById('modal-tipo').value;
        const stock = parseInt(document.getElementById('modal-stock').textContent);

        if (!cantidad || cantidad < 1) {
            e.preventDefault();
            alert('La cantidad debe ser mayor a 0.');
            return;
        }
        if (!motivo) {
            e.preventDefault();
            alert('El motivo es obligatorio.');
            return;
        }
        if (tipo === 'salida' && cantidad > stock) {
            e.preventDefault();
            alert('La cantidad no puede superar el stock disponible (' + stock + ').');
            return;
        }
    });
</script>
@endif

{{-- Modal traslado (solo admin) --}}
@if(auth()->user()->esAdmin())
<div id="modal-traslado"
    class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Trasladar container</h3>
                <p class="text-sm text-gray-500" id="traslado-subtitulo"></p>
            </div>
            <button onclick="cerrarModalTrasladar()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" id="form-traslado" action="" novalidate>
            @csrf

            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Container destino <span class="text-red-500">*</span>
                    </label>
                    <select name="contenedor_destino" id="traslado-destino" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Selecciona un container —</option>
                        @foreach($containers as $c)
                        <option value="{{ $c->id }}" class="opcion-traslado-destino">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Motivo <span class="text-red-500">*</span>
                    </label>
                    <textarea name="motivo" id="traslado-motivo"
                        rows="3" maxlength="500"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                        placeholder="Describe el motivo del traslado..."></textarea>
                    <p id="traslado-motivo-error" class="text-red-500 text-xs mt-1 hidden">El motivo es obligatorio.</p>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex gap-3 justify-end">
                <button type="button" onclick="cerrarModalTrasladar()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button type="button" onclick="confirmarTraslado()"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    Confirmar traslado
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalTrasladar(productoId, nombre, contenedorActual) {
        document.getElementById('form-traslado').action = '/admin/productos/' + productoId + '/trasladar';
        document.getElementById('traslado-subtitulo').textContent = nombre + ' — Container actual: C' + contenedorActual;
        document.getElementById('traslado-motivo').value = '';
        document.getElementById('traslado-destino').value = '';

        // Ocultar el container actual del select
        document.querySelectorAll('.opcion-traslado-destino').forEach(function(opt) {
            opt.hidden = parseInt(opt.value) === contenedorActual;
        });

        document.getElementById('modal-traslado').classList.remove('hidden');
    }

    function confirmarTraslado() {
        const motivo = document.getElementById('traslado-motivo').value.trim();
        const destino = document.getElementById('traslado-destino').value;
        const errorMotivo = document.getElementById('traslado-motivo-error');

        if (!motivo) {
            errorMotivo.classList.remove('hidden');
            document.getElementById('traslado-motivo').focus();
            return;
        }
        errorMotivo.classList.add('hidden');
        document.getElementById('form-traslado').submit();
    }

    document.getElementById('traslado-motivo').addEventListener('input', function () {
        if (this.value.trim()) {
            document.getElementById('traslado-motivo-error').classList.add('hidden');
        }
    });

    function cerrarModalTrasladar() {
        document.getElementById('modal-traslado').classList.add('hidden');
        document.getElementById('form-traslado').reset();
        document.getElementById('traslado-motivo-error').classList.add('hidden');
    }

    document.getElementById('modal-traslado').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalTrasladar();
    });
</script>
@endif

{{-- Modal movido a gastos-menores/index.blade.php --}}
@if(false)
<div id="modal-gasto-menor"
     style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); overflow-y:auto;">
    <div style="min-height:100%; display:flex; align-items:flex-start; justify-content:center; padding:2rem 1rem;">
        <div class="gm-modal-inner" style="background:#fff; border-radius:1rem; width:100%; max-width:780px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">

            {{-- Header --}}
            <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb;">
                <div>
                    <p style="font-size:1rem; font-weight:700; color:#92400e;">Compra de Gasto Menor</p>
                    <p style="font-size:0.75rem; color:#6b7280; margin-top:0.1rem;">Registra la boleta y actualiza el stock de los productos comprados</p>
                </div>
                <button type="button" onclick="cerrarModalGastoMenor()"
                        style="color:#9ca3af; font-size:1.25rem; line-height:1; background:none; border:none; cursor:pointer;">✕</button>
            </div>

            <form method="POST" action="{{ route('admin.gastos-menores.store') }}"
                  enctype="multipart/form-data" id="form-gasto-menor">
                @csrf
                <div style="padding:1.25rem; display:flex; flex-direction:column; gap:1rem;">

                    {{-- Datos de boleta --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                RUT Proveedor <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" name="rut_proveedor" placeholder="Ej: 12.345.678-9" required
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Folio <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" name="folio" placeholder="Ej: 001234" required
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Fecha y hora de emisión <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="datetime-local" name="fecha_emision" required
                                   max="{{ date('Y-m-d\TH:i') }}"
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                Boleta PDF <span style="font-weight:400; color:#9ca3af;">(opcional)</span>
                            </label>
                            <input type="file" name="documento" accept=".pdf"
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                        </div>
                    </div>

                    {{-- Buscador de productos --}}
                    <div style="border-top:1px solid #e5e7eb; padding-top:0.75rem;">
                        <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">
                            Agregar productos <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="position:relative;">
                            <input type="text" id="gm-buscador"
                                   placeholder="🔍 Buscar producto por nombre o descripción..."
                                   autocomplete="off"
                                   style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            <div id="gm-resultados"
                                 style="display:none; position:absolute; top:100%; left:0; right:0; z-index:10; background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 4px 16px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto; margin-top:2px;"></div>
                        </div>
                    </div>

                    {{-- Tabla de productos seleccionados --}}
                    <div id="gm-tabla-wrap" style="display:none;">
                        <table style="width:100%; font-size:0.78rem; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#fef3c7; color:#92400e;">
                                    <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600; border-radius:0.25rem 0 0 0;">Producto</th>
                                    <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:80px;">Cant.</th>
                                    <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:120px;">Monto ($)</th>
                                    <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:140px;">P. Neto s/IVA ($)</th>
                                    <th style="padding:0.4rem 0.6rem; width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="gm-items"></tbody>
                        </table>
                    </div>

                    <p id="gm-sin-items" style="font-size:0.75rem; color:#9ca3af; text-align:center; display:none;">
                        Agrega al menos un producto para continuar.
                    </p>

                </div>

                {{-- Footer --}}
                <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.5rem; padding:0.75rem 1.25rem; border-top:1px solid #e5e7eb; background:#fafafa; border-radius:0 0 1rem 1rem;">
                    <button type="button" onclick="cerrarModalGastoMenor()"
                            style="padding:0.4rem 1rem; font-size:0.8rem; font-weight:600; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="gm-btn-submit"
                            style="padding:0.4rem 1.1rem; font-size:0.8rem; font-weight:600; color:#fff; background:#d97706; border:none; border-radius:0.5rem; cursor:pointer; transition:opacity .15s;">
                        Registrar compra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Datos de productos (JSON) ─────────────────────────────────────────────────
const gmProductos = {!! json_encode($productos->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'descripcion' => $p->descripcion, 'stock' => $p->stock_actual])->values()) !!};

let gmItems   = [];   // { idx, id, nombre }
let gmCounter = 0;

// ── Modal ─────────────────────────────────────────────────────────────────────
document.getElementById('btn-abrir-gasto-menor').addEventListener('click', function() {
    const modal = document.getElementById('modal-gasto-menor');
    const inner = modal.querySelector('.gm-modal-inner');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    // Reiniciar animación
    inner.style.animation = 'none';
    inner.offsetHeight; // reflow
    inner.style.animation = '';
});
function cerrarModalGastoMenor() {
    document.getElementById('modal-gasto-menor').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('modal-gasto-menor').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalGastoMenor();
});

// ── Buscador ──────────────────────────────────────────────────────────────────
document.getElementById('gm-buscador').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    const res = document.getElementById('gm-resultados');
    if (q.length < 1) { res.style.display = 'none'; return; }

    const matches = gmProductos.filter(p =>
        p.nombre.toLowerCase().includes(q) || (p.descripcion || '').toLowerCase().includes(q)
    ).slice(0, 10);

    if (!matches.length) { res.style.display = 'none'; return; }

    res.innerHTML = matches.map(p => `
        <div onclick="gmAgregar(${p.id}, \`${p.nombre.replace(/`/g,'')}\`)"
             style="padding:0.5rem 0.75rem; cursor:pointer; border-bottom:1px solid #f3f4f6; transition:background .1s;"
             onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background=''">
            <p style="font-size:0.8rem; font-weight:600; color:#1f2937;">${escHtmlGm(p.nombre)}</p>
            <p style="font-size:0.72rem; color:#6b7280;">${escHtmlGm(p.descripcion || '')} · Stock: ${p.stock}</p>
        </div>
    `).join('');
    res.style.display = 'block';
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#gm-buscador') && !e.target.closest('#gm-resultados')) {
        document.getElementById('gm-resultados').style.display = 'none';
    }
});

// ── Agregar producto ──────────────────────────────────────────────────────────
function gmAgregar(id, nombre) {
    if (gmItems.find(i => i.id === id)) {
        document.getElementById('gm-buscador').value = '';
        document.getElementById('gm-resultados').style.display = 'none';
        return;
    }
    const idx = gmCounter++;
    gmItems.push({ idx, id, nombre });
    gmRenderFila(idx, id, nombre);
    document.getElementById('gm-buscador').value = '';
    document.getElementById('gm-resultados').style.display = 'none';
    gmActualizarTabla();
}

function gmRenderFila(idx, id, nombre) {
    const tbody = document.getElementById('gm-items');
    const tr = document.createElement('tr');
    tr.id = `gm-row-${idx}`;
    tr.style.borderBottom = '1px solid #f3f4f6';
    tr.innerHTML = `
        <td style="padding:0.4rem 0.6rem;">
            <input type="hidden" name="items[${idx}][producto_id]" value="${id}">
            <span style="font-size:0.8rem; font-weight:500; color:#1f2937;">${escHtmlGm(nombre)}</span>
        </td>
        <td style="padding:0.4rem 0.4rem; text-align:center;">
            <input type="number" name="items[${idx}][cantidad]" value="1" min="1" required
                   style="width:68px; text-align:center; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.3rem 0.4rem; font-size:0.8rem;">
        </td>
        <td style="padding:0.4rem 0.4rem; text-align:center;">
            <input type="number" name="items[${idx}][monto]" placeholder="0" min="0" step="1" required
                   style="width:108px; text-align:center; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.3rem 0.4rem; font-size:0.8rem;">
        </td>
        <td style="padding:0.4rem 0.4rem; text-align:center;">
            <input type="number" name="items[${idx}][precio_neto]" placeholder="0" min="0" step="1"
                   style="width:120px; text-align:center; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.3rem 0.4rem; font-size:0.8rem;">
        </td>
        <td style="padding:0.4rem 0.3rem; text-align:center;">
            <button type="button" onclick="gmQuitar(${idx})"
                    style="color:#ef4444; background:none; border:none; cursor:pointer; font-size:1rem; line-height:1;">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function gmQuitar(idx) {
    gmItems = gmItems.filter(i => i.idx !== idx);
    const row = document.getElementById(`gm-row-${idx}`);
    if (row) row.remove();
    gmActualizarTabla();
}

function gmActualizarTabla() {
    const wrap = document.getElementById('gm-tabla-wrap');
    const sin  = document.getElementById('gm-sin-items');
    wrap.style.display = gmItems.length ? '' : 'none';
    sin.style.display  = gmItems.length ? 'none' : '';
}

function escHtmlGm(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
@endif

@if(auth()->user()->esAdmin())
{{-- ══ MODAL AGREGAR INVENTARIO ══ --}}
<div id="modal-agregar-inv"
    style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); overflow-y:auto;">
    <div style="min-height:100%; display:flex; align-items:flex-start; justify-content:center; padding:2rem 1rem;">
        <div class="ai-modal-inner" style="background:#fff; border-radius:1rem; width:100%; max-width:820px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">

            {{-- Header --}}
            <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb;">
                <div>
                    <p style="font-size:1rem; font-weight:700; color:#1e40af;">Agregar Inventario</p>
                    <p style="font-size:0.75rem; color:#6b7280; margin-top:0.1rem;">Registra una entrada por boleta local o documento SICD externo</p>
                </div>
                <button type="button" onclick="cerrarModalAgregarInv()"
                    style="color:#9ca3af; font-size:1.25rem; line-height:1; background:none; border:none; cursor:pointer;">✕</button>
            </div>

            <form method="POST" action="" id="form-agregar-inv" enctype="multipart/form-data"
                data-url-local="{{ route('admin.gastos-menores.store') }}"
                data-url-externa="{{ route('admin.sicd.recibir.directo') }}">
                <input type="hidden" name="_modo" id="ai-modo" value="nuevo">
                @csrf
                <div style="padding:1.25rem; display:flex; flex-direction:column; gap:1rem;">

                    {{-- Selector de tipo --}}
                    <div>
                        <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                            Tipo de ingreso <span style="color:#ef4444;">*</span>
                        </label>
                        <select id="ai-tipo" name="_tipo"
                            style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem; font-size:0.8rem; box-sizing:border-box; background:#fff;"
                            onchange="aiCambiarTipo(this.value)">
                            <option value="">— Selecciona el tipo de ingreso —</option>
                            <option value="local">Local (Boleta de compra)</option>
                            <option value="externa">Externa (Documento SICD)</option>
                        </select>
                    </div>

                    {{-- ══ SECCIÓN LOCAL ══ --}}
                    <div id="ai-seccion-local" style="display:none; flex-direction:column; gap:1rem;">

                        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:0.5rem; padding:0.5rem 0.75rem;">
                            <p style="font-size:0.72rem; color:#92400e; font-weight:600; margin:0;">
                                📄 Los productos se sumarán al stock del inventario al registrar la boleta.
                            </p>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    RUT Proveedor <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="text" name="rut_proveedor" id="ai-rut" placeholder="Ej: 12.345.678-9"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Folio <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="text" name="folio" id="ai-folio" placeholder="Ej: 001234"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Fecha y hora de emisión <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="datetime-local" name="fecha_emision" id="ai-fecha"
                                    max="{{ date('Y-m-d\TH:i') }}"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Boleta PDF <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="file" name="documento" id="ai-doc" accept=".pdf"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                            </div>
                        </div>

                        <div style="border-top:1px solid #e5e7eb; padding-top:0.75rem;">
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">
                                Productos <span style="color:#ef4444;">*</span>
                            </label>
                            <div style="position:relative;">
                                <input type="text" id="ai-buscador"
                                    placeholder="🔍 Buscar producto por nombre o descripción..."
                                    autocomplete="off"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                                <div id="ai-resultados"
                                    style="display:none; position:absolute; top:100%; left:0; right:0; z-index:10; background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 4px 16px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto; margin-top:2px;"></div>
                            </div>
                        </div>

                        <div id="ai-tabla-wrap" style="display:none;">
                            <table style="width:100%; font-size:0.78rem; border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#fef3c7; color:#92400e;">
                                        <th style="padding:0.4rem 0.6rem; text-align:left; font-weight:600;">Producto</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:80px;">Cant.</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:120px;">Monto ($)</th>
                                        <th style="padding:0.4rem 0.6rem; text-align:center; font-weight:600; width:140px;">P. Neto s/IVA</th>
                                        <th style="padding:0.4rem 0.6rem; width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="ai-items"></tbody>
                            </table>
                        </div>
                        <p id="ai-sin-items" style="font-size:0.75rem; color:#9ca3af; text-align:center; display:none;">
                            Agrega al menos un producto para continuar.
                        </p>
                    </div>

                    {{-- ══ SECCIÓN EXTERNA ══ --}}
                    <div id="ai-seccion-externa" style="display:none; flex-direction:column; gap:0.75rem;">

                        {{-- Sub-selector Nuevo / Existente --}}
                        <div style="display:flex; gap:0.5rem;">
                            <button type="button" id="ai-tab-nuevo"
                                onclick="aiTabSicd('nuevo')"
                                style="flex:1; padding:0.45rem; font-size:0.78rem; font-weight:600; border-radius:0.5rem; border:2px solid #2563eb; background:#2563eb; color:#fff; cursor:pointer; transition:all .15s;">
                                Nuevo SICD
                            </button>
                            <button type="button" id="ai-tab-existente"
                                onclick="aiTabSicd('existente')"
                                style="flex:1; padding:0.45rem; font-size:0.78rem; font-weight:600; border-radius:0.5rem; border:2px solid #d1d5db; background:#fff; color:#6b7280; cursor:pointer; transition:all .15s;">
                                SICD Existente
                            </button>
                        </div>

                        {{-- ── Nuevo SICD ── --}}
                        <div id="ai-panel-nuevo" style="display:flex; flex-direction:column; gap:0.75rem;">

                            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.5rem; padding:0.5rem 0.75rem;">
                                <p style="font-size:0.72rem; color:#1e40af; font-weight:600; margin:0;">
                                    📋 El sistema procesará el Excel para actualizar el stock automáticamente.
                                </p>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                                <div>
                                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                        Documento SICD <span style="color:#ef4444;">*</span>
                                        <span style="font-weight:400; color:#9ca3af;">(PDF, JPG, PNG)</span>
                                    </label>
                                    <input type="file" name="archivo_sicd" id="ai-archivo-sicd" accept=".pdf,.jpg,.jpeg,.png"
                                        style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                                </div>
                                <div>
                                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                        Código SICD <span style="color:#ef4444;">*</span>
                                    </label>
                                    <input type="text" name="codigo_sicd" id="ai-codigo-sicd" placeholder="Se detecta del PDF automáticamente"
                                        style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box;">
                                    <span id="ai-codigo-hint" style="font-size:0.7rem; margin-top:0.2rem; display:block;"></span>
                                </div>
                            </div>

                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Excel de productos <span style="color:#ef4444;">*</span>
                                    <span style="font-weight:400; color:#9ca3af;">(XLSX, XLS, CSV)</span>
                                </label>
                                <input type="file" name="archivo_excel" id="ai-archivo-excel" accept=".xlsx,.xls,.csv"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                            </div>

                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Descripción <span style="font-weight:400; color:#9ca3af;">(opcional)</span>
                                </label>
                                <textarea name="descripcion" id="ai-descripcion" rows="2" maxlength="500"
                                    placeholder="Notas o descripción del documento SICD..."
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; box-sizing:border-box; resize:vertical;"></textarea>
                            </div>
                        </div>

                        {{-- ── SICD Existente ── --}}
                        <div id="ai-panel-existente" style="display:none; flex-direction:column; gap:0.75rem;">

                            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.5rem; padding:0.5rem 0.75rem;">
                                <p style="font-size:0.72rem; color:#166534; font-weight:600; margin:0;">
                                    🔗 Selecciona un SICD ya registrado para adjuntarle productos adicionales o continuar su proceso.
                                </p>
                            </div>

                            <div>
                                <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">
                                    Seleccionar SICD <span style="color:#ef4444;">*</span>
                                </label>
                                <select name="sicd_existente_id" id="ai-sicd-existente-id"
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.45rem 0.65rem; font-size:0.8rem; box-sizing:border-box; background:#fff;"
                                    onchange="aiSicdExistenteChange(this)">
                                    <option value="">— Selecciona un SICD —</option>
                                    @foreach($sicds as $sicd)
                                    <option value="{{ $sicd->id }}"
                                        data-codigo="{{ $sicd->codigo_sicd }}"
                                        data-estado="{{ $sicd->estado }}">
                                        {{ $sicd->codigo_sicd }}
                                        @if($sicd->descripcion) — {{ Str::limit($sicd->descripcion, 40) }}@endif
                                        ({{ $sicd->estado }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="ai-sicd-existente-info" style="display:none; background:#f9fafb; border:1px solid #e5e7eb; border-radius:0.5rem; padding:0.6rem 0.75rem;">
                                <p style="font-size:0.75rem; color:#374151; margin:0;">
                                    <span style="font-weight:600;">Código:</span> <span id="ai-info-codigo">—</span>
                                    &nbsp;·&nbsp;
                                    <span style="font-weight:600;">Estado:</span> <span id="ai-info-estado">—</span>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.5rem; padding:0.75rem 1.25rem; border-top:1px solid #e5e7eb; background:#fafafa; border-radius:0 0 1rem 1rem;">
                    <button type="button" onclick="cerrarModalAgregarInv()"
                        style="padding:0.4rem 1rem; font-size:0.8rem; font-weight:600; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="button" onclick="aiEnviar()" id="ai-btn-submit" disabled
                        style="padding:0.4rem 1.1rem; font-size:0.8rem; font-weight:600; color:#fff; background:#9ca3af; border:none; border-radius:0.5rem; cursor:not-allowed; transition:background .15s;">
                        Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('head')
<style>
    @keyframes btn-breathe-green { 0%,100%{box-shadow:0 0 0 0 rgba(22,163,74,.7)} 50%{box-shadow:0 0 0 6px rgba(22,163,74,0)} }
    @keyframes btn-breathe-blue  { 0%,100%{box-shadow:0 0 0 0 rgba(37,99,235,.7)} 50%{box-shadow:0 0 0 6px rgba(37,99,235,0)} }
    @keyframes btn-breathe-red   { 0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.7)} 50%{box-shadow:0 0 0 6px rgba(220,38,38,0)} }

    .dt-btn-excel { background:#16a34a; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s, transform .15s; }
    .dt-btn-excel:hover { background:#15803d; transform:translateY(-1px); animation:btn-breathe-green 1.6s ease-in-out infinite; }

    .dt-btn { background:#2563eb; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s, transform .15s; }
    .dt-btn:hover { background:#1d4ed8; transform:translateY(-1px); animation:btn-breathe-blue 1.6s ease-in-out infinite; }

    .dt-btn-pdf { background:#dc2626; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s, transform .15s; }
    .dt-btn-pdf:hover { background:#b91c1c; transform:translateY(-1px); animation:btn-breathe-red 1.6s ease-in-out infinite; }
    @keyframes gmFadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
    .gm-modal-inner { animation: gmFadeUp 0.35s cubic-bezier(.22,.68,0,1.2) both; }
    .ai-modal-inner { animation: gmFadeUp 0.35s cubic-bezier(.22,.68,0,1.2) both; }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const table = $('#tabla-inventario').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
            },
            order: [],
            paging: false,
            layout: {
                topStart: 'buttons',
                topEnd: null,
                bottomStart: null,
                bottomEnd: null,
            },
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel', exportOptions: { columns: ':not(:last-child)' } },
                { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn',       exportOptions: { columns: ':not(:last-child)' } },
                { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf',   exportOptions: { columns: ':not(:last-child)' }, orientation: 'landscape', pageSize: 'A4' },
            ],
            columnDefs: [{
                orderable: false,
                searchable: false,
                targets: -1
            }],
        });



        // Filtro personalizado: busca en texto visible de cada celda
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'tabla-inventario') return true;
            const q = ($('#buscador-productos').val() || '').toLowerCase().trim();
            if (!q) return true;
            // Leer texto real de las celdas (elimina HTML de badges/spans)
            const fila = table.row(dataIndex).node();
            const celdas = fila ? Array.from(fila.querySelectorAll('td')).map(td => td.innerText.toLowerCase()) : [];
            // Busca en: Producto(0), Descripción(1), Contenedor(2), Stock Actual(3), Estado(6)
            return [0, 1, 2, 3, 6].some(i => (celdas[i] || '').includes(q));
        });

        $('#buscador-productos').on('input', function () {
            table.draw();
        });
    });

    // Sincronizar todas las animaciones de pulso al mismo tiempo
    document.addEventListener('DOMContentLoaded', function () {
        const els = document.querySelectorAll('.estado-pulso-critico, .estado-pulso-minimo');
        els.forEach(el => { el.style.animationDelay = '0s'; el.style.animationPlayState = 'paused'; });
        requestAnimationFrame(() => els.forEach(el => { el.style.animationPlayState = 'running'; }));
    });
</script>
@endpush

@push('head')
<style>
    .btn-accion-indigo { background:#4f46e5; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-accion-indigo:hover { background:#a5b4fc; box-shadow:0 0 14px 4px rgba(165,180,252,0.75); transform:scale(1.05); }
    .btn-accion-blue { background:#2563eb; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-accion-blue:hover { background:#93c5fd; box-shadow:0 0 14px 4px rgba(147,197,253,0.75); transform:scale(1.05); }
    .btn-accion-green { background:#16a34a; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-accion-green:hover { background:#86efac; box-shadow:0 0 14px 4px rgba(134,239,172,0.75); transform:scale(1.05); }
    .btn-accion-orange { background:#f97316; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-accion-orange:hover { background:#fdba74; box-shadow:0 0 14px 4px rgba(253,186,116,0.75); transform:scale(1.05); }
    .btn-accion-orange:disabled { opacity:0.5; cursor:not-allowed; transform:none; box-shadow:none; }
    @keyframes pulso-critico { 0%,100% { box-shadow:0 0 0 0 rgba(239,68,68,.5); } 50% { box-shadow:0 0 0 6px rgba(239,68,68,0); } }
    @keyframes pulso-minimo  { 0%,100% { box-shadow:0 0 0 0 rgba(234,179,8,.5); } 50% { box-shadow:0 0 0 6px rgba(234,179,8,0); } }
    .estado-pulso-critico { animation: pulso-critico 1.5s ease-in-out infinite; }
    .estado-pulso-minimo  { animation: pulso-minimo  1.5s ease-in-out infinite; }
</style>
@endpush

@endsection

@push('scripts')
@php
$aiProductosJson = json_encode(
    $productos->map(fn($p) => ['id'=>$p->id,'nombre'=>$p->nombre,'descripcion'=>$p->descripcion,'stock'=>$p->stock_actual])->values(),
    JSON_HEX_TAG | JSON_HEX_AMP
);
@endphp
<script type="application/json" id="ai-data">{!! $aiProductosJson !!}</script>
<script>
if (document.getElementById('btn-agregar-inventario')) {
var aiProductos = JSON.parse(document.getElementById('ai-data').textContent);
var aiItems = [];
var aiCounter = 0;
var aiForm = document.getElementById('form-agregar-inv');
var aiUrlLocal = aiForm.dataset.urlLocal;
var aiUrlExterna = aiForm.dataset.urlExterna;

document.getElementById('btn-agregar-inventario').addEventListener('click', function() {
    var modal = document.getElementById('modal-agregar-inv');
    var inner = modal.querySelector('.ai-modal-inner');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    inner.style.animation = 'none';
    inner.offsetHeight;
    inner.style.animation = '';
});

function cerrarModalAgregarInv() {
    document.getElementById('modal-agregar-inv').style.display = 'none';
    document.body.style.overflow = '';
    aiForm.reset();
    document.getElementById('ai-seccion-local').style.display = 'none';
    document.getElementById('ai-seccion-externa').style.display = 'none';
    aiItems = [];
    aiCounter = 0;
    document.getElementById('ai-items').innerHTML = '';
    document.getElementById('ai-tabla-wrap').style.display = 'none';
    document.getElementById('ai-sin-items').style.display = 'none';
    var btn = document.getElementById('ai-btn-submit');
    btn.disabled = true;
    btn.style.background = '#9ca3af';
    btn.style.cursor = 'not-allowed';
    btn.textContent = 'Registrar';
}

document.getElementById('modal-agregar-inv').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAgregarInv();
});

function aiCambiarTipo(tipo) {
    var local = document.getElementById('ai-seccion-local');
    var externa = document.getElementById('ai-seccion-externa');
    local.style.display = (tipo === 'local') ? 'flex' : 'none';
    externa.style.display = (tipo === 'externa') ? 'flex' : 'none';
    if (tipo === 'externa') aiTabSicd('nuevo');
    var btn = document.getElementById('ai-btn-submit');
    if (tipo === 'local') {
        btn.disabled = false;
        btn.style.background = '#d97706';
        btn.style.cursor = 'pointer';
        btn.textContent = 'Registrar compra local';
    } else if (tipo === 'externa') {
        btn.disabled = false;
        btn.style.background = '#2563eb';
        btn.style.cursor = 'pointer';
        btn.textContent = 'Recibir SICD y actualizar stock';
    } else {
        btn.disabled = true;
        btn.style.background = '#9ca3af';
        btn.style.cursor = 'not-allowed';
        btn.textContent = 'Registrar';
    }
}

function aiTabSicd(tab) {
    var panelNuevo     = document.getElementById('ai-panel-nuevo');
    var panelExistente = document.getElementById('ai-panel-existente');
    var btnNuevo       = document.getElementById('ai-tab-nuevo');
    var btnExistente   = document.getElementById('ai-tab-existente');
    document.getElementById('ai-modo').value = tab;
    if (tab === 'nuevo') {
        panelNuevo.style.display     = 'flex';
        panelExistente.style.display = 'none';
        btnNuevo.style.background    = '#2563eb';
        btnNuevo.style.color         = '#fff';
        btnNuevo.style.borderColor   = '#2563eb';
        btnExistente.style.background  = '#fff';
        btnExistente.style.color       = '#6b7280';
        btnExistente.style.borderColor = '#d1d5db';
        document.getElementById('ai-btn-submit').textContent = 'Recibir SICD y actualizar stock';
    } else {
        panelNuevo.style.display     = 'none';
        panelExistente.style.display = 'flex';
        btnExistente.style.background  = '#2563eb';
        btnExistente.style.color       = '#fff';
        btnExistente.style.borderColor = '#2563eb';
        btnNuevo.style.background    = '#fff';
        btnNuevo.style.color         = '#6b7280';
        btnNuevo.style.borderColor   = '#d1d5db';
        document.getElementById('ai-btn-submit').textContent = 'Recibir SICD y actualizar stock';
    }
}

function aiSicdExistenteChange(sel) {
    var opt  = sel.options[sel.selectedIndex];
    var id   = sel.value;
    var info = document.getElementById('ai-sicd-existente-info');
    if (!id) { info.style.display = 'none'; return; }
    document.getElementById('ai-info-codigo').textContent = opt.dataset.codigo;
    document.getElementById('ai-info-estado').textContent = opt.dataset.estado;
    info.style.display = 'block';
}

function aiEnviar() {
    var tipo = document.getElementById('ai-tipo').value;
    if (!tipo) { alert('Selecciona el tipo de ingreso.'); return; }

    if (tipo === 'local') {
        var rut = document.getElementById('ai-rut').value.trim();
        var folio = document.getElementById('ai-folio').value.trim();
        var fecha = document.getElementById('ai-fecha').value;
        var doc = document.getElementById('ai-doc').files.length;
        if (!rut)   { alert('El RUT del proveedor es obligatorio.'); document.getElementById('ai-rut').focus(); return; }
        if (!folio) { alert('El folio es obligatorio.'); document.getElementById('ai-folio').focus(); return; }
        if (!fecha) { alert('La fecha de emisión es obligatoria.'); document.getElementById('ai-fecha').focus(); return; }
        if (!doc)   { alert('La boleta PDF es obligatoria.'); return; }
        if (aiItems.length === 0) { alert('Agrega al menos un producto.'); document.getElementById('ai-buscador').focus(); return; }
        aiForm.action = aiUrlLocal;
    } else if (tipo === 'externa') {
        var modo = document.getElementById('ai-modo').value;
        if (modo === 'existente') {
            var sicdId = document.getElementById('ai-sicd-existente-id').value;
            if (!sicdId) { alert('Selecciona un SICD existente.'); return; }
        } else {
            var codigo = document.getElementById('ai-codigo-sicd').value.trim();
            var sicdFile = document.getElementById('ai-archivo-sicd').files.length;
            var excelFile = document.getElementById('ai-archivo-excel').files.length;
            if (!codigo)    { alert('El código SICD es obligatorio.'); document.getElementById('ai-codigo-sicd').focus(); return; }
            if (!sicdFile)  { alert('El documento SICD es obligatorio.'); return; }
            if (!excelFile) { alert('El archivo Excel es obligatorio.'); return; }
        }
        aiForm.action = aiUrlExterna;
    }

    aiForm.submit();
}

document.getElementById('ai-archivo-sicd').addEventListener('change', function() {
    var file = this.files[0];
    var input = document.getElementById('ai-codigo-sicd');
    var hint  = document.getElementById('ai-codigo-hint');
    if (!file || file.type !== 'application/pdf') return;
    if (typeof pdfjsLib === 'undefined') return;

    hint.textContent = '⏳ Leyendo PDF...';
    hint.style.color = '#6b7280';

    var reader = new FileReader();
    reader.onload = function(e) {
        pdfjsLib.getDocument({ data: e.target.result }).promise.then(function(pdf) {
            var pages = [];
            for (var i = 1; i <= pdf.numPages; i++) { pages.push(i); }
            return Promise.all(pages.map(function(n) {
                return pdf.getPage(n).then(function(p) {
                    return p.getTextContent().then(function(tc) {
                        return tc.items.map(function(it) { return it.str; }).join(' ');
                    });
                });
            }));
        }).then(function(textos) {
            var fullText = textos.join(' ');
            // Busca TIC(RAMO)/12345 o TIC(RAMO)12345
            var match = fullText.match(/TIC\([^)]+\)\/?(\d+)/i);
            if (match) {
                // Normaliza con barra: TIC(RAMO)/12345
                var codigo = match[0].replace(/\/?(\d+)$/, '/$1').toUpperCase();
                input.value = codigo;
                hint.textContent = '✅ Código detectado automáticamente';
                hint.style.color = '#16a34a';
            } else {
                hint.textContent = '⚠️ No se detectó código TIC en el PDF. Ingrésalo manualmente.';
                hint.style.color = '#d97706';
            }
        }).catch(function() {
            hint.textContent = '⚠️ No se pudo leer el PDF. Ingresa el código manualmente.';
            hint.style.color = '#d97706';
        });
    };
    reader.readAsArrayBuffer(file);
});

document.getElementById('ai-buscador').addEventListener('input', function() {
    var q = this.value.trim().toLowerCase();
    var res = document.getElementById('ai-resultados');
    if (q.length < 1) { res.style.display = 'none'; return; }
    var matches = aiProductos.filter(function(p) {
        return p.nombre.toLowerCase().indexOf(q) >= 0 || (p.descripcion || '').toLowerCase().indexOf(q) >= 0;
    }).slice(0, 10);
    if (!matches.length) { res.style.display = 'none'; return; }
    res.innerHTML = matches.map(function(p) {
        return '<div onclick="aiAgregar(' + p.id + ',\'' + p.nombre.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\')"'
            + ' style="padding:0.5rem 0.75rem;cursor:pointer;border-bottom:1px solid #f3f4f6;"'
            + ' onmouseover="this.style.background=\'#fef3c7\'" onmouseout="this.style.background=\'\'">'
            + '<p style="font-size:0.8rem;font-weight:600;color:#1f2937;">' + escHtmlAi(p.nombre) + '</p>'
            + '<p style="font-size:0.72rem;color:#6b7280;">' + escHtmlAi(p.descripcion || '') + ' &middot; Stock: ' + p.stock + '</p>'
            + '</div>';
    }).join('');
    res.style.display = 'block';
});

document.addEventListener('click', function(e) {
    var res = document.getElementById('ai-resultados');
    if (res && !e.target.closest('#ai-buscador') && !e.target.closest('#ai-resultados')) {
        res.style.display = 'none';
    }
});

function aiAgregar(id, nombre) {
    if (aiItems.find(function(i) { return i.id === id; })) {
        document.getElementById('ai-buscador').value = '';
        document.getElementById('ai-resultados').style.display = 'none';
        return;
    }
    var idx = aiCounter++;
    aiItems.push({ idx: idx, id: id, nombre: nombre });
    aiRenderFila(idx, id, nombre);
    document.getElementById('ai-buscador').value = '';
    document.getElementById('ai-resultados').style.display = 'none';
    aiActualizarTabla();
}

function aiRenderFila(idx, id, nombre) {
    var tbody = document.getElementById('ai-items');
    var tr = document.createElement('tr');
    tr.id = 'ai-row-' + idx;
    tr.style.borderBottom = '1px solid #f3f4f6';
    tr.innerHTML =
        '<td style="padding:0.4rem 0.6rem;">'
        + '<input type="hidden" name="items[' + idx + '][producto_id]" value="' + id + '">'
        + '<span style="font-size:0.8rem;font-weight:500;color:#1f2937;">' + escHtmlAi(nombre) + '</span>'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items[' + idx + '][cantidad]" value="1" min="1"'
        + ' style="width:68px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items[' + idx + '][monto]" placeholder="0" min="0" step="1"'
        + ' style="width:108px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items[' + idx + '][precio_neto]" placeholder="0" min="0" step="1"'
        + ' style="width:120px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.3rem;text-align:center;">'
        + '<button type="button" onclick="aiQuitar(' + idx + ')" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;">&#x2715;</button>'
        + '</td>';
    tbody.appendChild(tr);
}

function aiQuitar(idx) {
    aiItems = aiItems.filter(function(i) { return i.idx !== idx; });
    var row = document.getElementById('ai-row-' + idx);
    if (row) row.remove();
    aiActualizarTabla();
}

function aiActualizarTabla() {
    var wrap = document.getElementById('ai-tabla-wrap');
    var sin  = document.getElementById('ai-sin-items');
    wrap.style.display = aiItems.length ? '' : 'none';
    sin.style.display  = aiItems.length ? 'none' : '';
}

function escHtmlAi(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
} // end if btn-agregar-inventario
</script>
@endpush