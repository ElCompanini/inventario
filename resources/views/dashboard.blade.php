@extends('layouts.app')

@section('title', 'Productos')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Inventario de Productos</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $productos->count() }} producto(s) registrado(s)
        </p>
    </div>

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

{{-- Errores de validación del formulario de solicitud --}}
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
    {{ $errors->first() }}
</div>
@endif

{{-- Buscador de productos --}}
<div class="mb-4">
    <input id="buscador-productos" type="text" placeholder="🔍  Buscar por producto, descripción, contenedor, stock o estado..."
           class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                  focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
</div>

{{-- Tabla de productos --}}
<div class="bg-white rounded-xl shadow overflow-hidden p-4">
    <h1 class=" font-medium text-gray-900 sorting_1">Exportar archivo:</h1>
    <table id="tabla-inventario" class="w-full text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-semibold text-gray-600">Producto</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Descripción</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Contenedor</th>
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
                            <span style="position:relative; display:inline-flex; align-items:center; cursor:default;"
                                  onmouseenter="this.querySelector('span').style.display='block'"
                                  onmouseleave="this.querySelector('span').style.display='none'">
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
                            </span>
                        @endif
                        <span>{{ $producto->nombre }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $producto->descripcion ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                        {{ str_replace('Contenedor ', 'C', $producto->container->nombre ?? '—') }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center font-bold
                        {{ $estado === 'critico' ? 'text-red-700' : ($estado === 'minimo' ? 'text-yellow-700' : 'text-gray-800') }}">
                    {{ $producto->stock_actual }}
                </td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $producto->stock_minimo }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $producto->stock_critico }}</td>
                <td class="px-4 py-3 text-center">
                    @if($estado === 'critico')
                    <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Crítico
                    </span>
                    @elseif($estado === 'minimo')
                    <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 text-xs font-semibold px-2 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> Mínimo
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Normal
                    </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex flex-col items-center gap-1.5">
                        @if(auth()->user()->esAdmin())
                        {{-- Admin: modificar stock directamente --}}
                        <a href="{{ route('admin.productos.editar', $producto->id) }}"
                            class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium px-2.5 py-1.5 rounded-lg transition whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Modificar
                        </a>
                        {{-- Admin: trasladar container --}}
                        <button type="button"
                            onclick="abrirModalTrasladar({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', {{ $producto->contenedor }})"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-2.5 py-1.5 rounded-lg transition whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Trasladar
                        </button>
                        @else
                        {{-- Usuario: solicitar entrada --}}
                        <button type="button"
                            onclick="abrirModal({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', 'entrada', {{ $producto->stock_actual }})"
                            class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Entrada
                        </button>
                        {{-- Usuario: solicitar salida --}}
                        <button type="button"
                            onclick="abrirModal({{ $producto->id }}, '{{ addslashes($producto->nombre) }}', 'salida', {{ $producto->stock_actual }})"
                            class="inline-flex items-center gap-1 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition"
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
                        rows="3" required maxlength="500"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                        placeholder="Describe el motivo del traslado..."></textarea>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex gap-3 justify-end">
                <button type="button" onclick="cerrarModalTrasladar()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit"
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

    function cerrarModalTrasladar() {
        document.getElementById('modal-traslado').classList.add('hidden');
        document.getElementById('form-traslado').reset();
    }

    document.getElementById('modal-traslado').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalTrasladar();
    });
</script>
@endif

@push('head')
<style>
    .dt-btn-excel { background:#16a34a; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn-excel:hover { background:#15803d; }
    .dt-btn { background:#2563eb; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn:hover { background:#1d4ed8; }
    .dt-btn-pdf { background:#dc2626; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn-pdf:hover { background:#b91c1c; }
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
            buttons: [{
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'dt-btn-excel',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'dt-btn',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    className: 'dt-btn-pdf',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    },
                    orientation: 'landscape',
                    pageSize: 'A4'
                },
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
</script>
@endpush

@endsection