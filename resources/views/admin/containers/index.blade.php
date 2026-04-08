@extends('layouts.app')

@section('title', 'Containers')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Containers</h1>
        <p class="text-sm text-gray-500 mt-1">Gestión de contenedores de almacenamiento</p>
    </div>
    <a href="{{ route('admin.containers.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo container
    </a>
</div>

@if($containers->isEmpty())
    <div class="bg-white rounded-xl shadow p-12 text-center">
        <p class="text-gray-500 font-medium">No hay containers registrados.</p>
    </div>
@else
    <div class="mb-4">
        <input id="buscador-containers" type="text" placeholder="🔍  Buscar container..."
               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
    </div>
    <div class="bg-white rounded-xl shadow overflow-hidden p-4">
        <p class="font-medium text-gray-900 text-sm mb-1">Exportar archivo:</p>
        <table id="tabla-containers" class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-3 py-3 w-8"></th>
                    <th class="px-6 py-3 font-semibold text-gray-600">ID</th>
                    <th class="px-6 py-3 font-semibold text-gray-600">Nombre</th>
                    <th class="px-6 py-3 font-semibold text-gray-600">Descripción</th>
                    <th class="px-6 py-3 font-semibold text-gray-600">Productos</th>
                    <th class="px-6 py-3 font-semibold text-gray-600 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($containers as $container)
                    <tr class="hover:bg-gray-50 cursor-pointer fila-container"
                        id="container-{{ $container->id }}"
                        data-container-id="{{ $container->id }}">
                        <td class="px-3 py-4 text-center">
                            <svg class="chevron w-4 h-4 text-gray-400 transition-transform duration-200 inline-block"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $container->id }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $container->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $container->descripcion ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-1 rounded-full">
                                {{ $container->productos_count }} producto(s)
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button"
                                        class="btn-trasladar inline-flex items-center gap-1.5 text-white text-xs font-semibold px-3 py-1.5 rounded-lg"
                                        data-id="{{ $container->id }}"
                                        data-nombre="{{ addslashes($container->nombre) }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    Trasladar
                                </button>
                                @if(auth()->user()->esAdmin())
                                <form method="POST" action="{{ route('admin.containers.destroy', $container->id) }}"
                                      id="form-delete-{{ $container->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            onclick="confirmarEliminar({{ $container->id }}, '{{ addslashes($container->nombre) }}')"
                                            class="btn-eliminar inline-flex items-center gap-1 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Eliminar
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@php
    $productosMapData = $containers->mapWithKeys(fn($c) => [
        $c->id => $c->productos->map(fn($p) => [
            'descripcion' => $p->descripcion ?? $p->nombre,
            'categoria'   => $p->nombre,
            'stock'       => $p->stock_actual,
        ])->values()
    ]);
@endphp

@push('scripts')
<script>
    $(document).ready(function () {
        const table = $('#tabla-containers').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
            order: [[1, 'asc']],
            paging: false,
            layout: { topStart: 'buttons', topEnd: null, bottomStart: null, bottomEnd: null },
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'dt-btn-excel', exportOptions: { columns: ':not(:first-child):not(:last-child)' } },
                { extend: 'csvHtml5',   text: 'CSV',   className: 'dt-btn',       exportOptions: { columns: ':not(:first-child):not(:last-child)' } },
                { extend: 'pdfHtml5',   text: 'PDF',   className: 'dt-btn-pdf',   exportOptions: { columns: ':not(:first-child):not(:last-child)' } },
            ],
            columnDefs: [
                { orderable: false, searchable: false, targets: [0, -1] },
            ],
        });

        $('#buscador-containers').on('input', function () { table.search(this.value).draw(); });

        // ── Mapa de productos por container ──
        const productosMap = @json($productosMapData);

        // ── Child rows: expandir/contraer productos ──
        $('#tabla-containers tbody').on('click', 'tr.fila-container', function (e) {
            if ($(e.target).closest('button, form, a').length) return;

            const tr          = $(this);
            const row         = table.row(tr);
            const chevron     = tr.find('.chevron');
            const containerId = tr.data('container-id');

            if (row.child.isShown()) {
                const inner = row.child().find('.child-row-inner');
                inner.addClass('closing');
                chevron.css('transform', 'rotate(0deg)');
                tr.removeClass('bg-indigo-50');
                tr.css('background', '');
                setTimeout(function() { row.child.hide(); }, 280);
            } else {
                const productos = productosMap[containerId] || [];
                let html = '<div class="child-row-inner" style="padding:0.5rem 1rem 1rem 3.5rem; background:#f5f7ff;">';

                if (!productos.length) {
                    html += '<p style="color:#6b7280;font-size:0.8rem;padding-top:0.5rem;">Sin productos en este container.</p>';
                } else {
                    html += '<table style="width:100%;font-size:0.8rem;border-collapse:collapse;margin-top:0.5rem;">';
                    html += '<thead><tr style="background:#e0e7ff;color:#3730a3;">'
                          + '<th style="padding:6px 12px;text-align:left;">Descripción</th>'
                          + '<th style="padding:6px 12px;text-align:left;">Categoría</th>'
                          + '<th style="padding:6px 12px;text-align:center;">Stock</th>'
                          + '</tr></thead><tbody>';
                    productos.forEach(function (p, i) {
                        const bg = i % 2 === 0 ? '#fff' : '#f1f5ff';
                        html += '<tr style="background:' + bg + ';">'
                              + '<td style="padding:6px 12px;color:#374151;">'       + (p.descripcion || '—') + '</td>'
                              + '<td style="padding:6px 12px;color:#4f46e5;font-weight:600;">' + p.categoria + '</td>'
                              + '<td style="padding:6px 12px;text-align:center;font-weight:700;color:#166534;">' + p.stock + '</td>'
                              + '</tr>';
                    });
                    html += '</tbody></table>';
                }
                html += '</div>';

                row.child(html).show();
                tr.addClass('bg-indigo-50');
                tr.css('background', '#dbeafe');
                chevron.css('transform', 'rotate(90deg)');
            }
        });

        // ── Expandir y resaltar fila si se llega via anchor #container-{id} ──
        if (window.location.hash) {
            const target = document.querySelector(window.location.hash);
            if (target) {
                setTimeout(function() {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    target.style.transition = 'background .3s';
                    target.style.background = '#e0e7ff';
                    setTimeout(function() { target.style.background = ''; }, 2000);

                    const tr          = $(target);
                    const row         = table.row(tr);
                    const chevron     = tr.find('.chevron');
                    const containerId = tr.data('container-id');
                    if (!row.child.isShown()) {
                        const productos = productosMap[containerId] || [];
                        let html = '<div class="child-row-inner" style="padding:0.5rem 1rem 1rem 3.5rem; background:#f5f7ff;">';
                        if (!productos.length) {
                            html += '<p style="color:#6b7280;font-size:0.8rem;padding-top:0.5rem;">Sin productos en este container.</p>';
                        } else {
                            html += '<table style="width:100%;font-size:0.8rem;border-collapse:collapse;margin-top:0.5rem;">';
                            html += '<thead><tr style="background:#e0e7ff;color:#3730a3;"><th style="padding:6px 12px;text-align:left;">Descripción</th><th style="padding:6px 12px;text-align:left;">Categoría</th><th style="padding:6px 12px;text-align:center;">Stock</th></tr></thead><tbody>';
                            productos.forEach(function(p, i) {
                                const bg = i % 2 === 0 ? '#fff' : '#f1f5ff';
                                html += '<tr style="background:' + bg + ';">'
                                      + '<td style="padding:6px 12px;color:#374151;">' + (p.descripcion || '—') + '</td>'
                                      + '<td style="padding:6px 12px;color:#4f46e5;font-weight:600;">' + p.categoria + '</td>'
                                      + '<td style="padding:6px 12px;text-align:center;font-weight:700;color:#166534;">' + p.stock + '</td>'
                                      + '</tr>';
                            });
                            html += '</tbody></table>';
                        }
                        html += '</div>';
                        row.child(html).show();
                        tr.addClass('bg-indigo-50');
                        chevron.css('transform', 'rotate(90deg)');
                    }
                }, 500);
            }
        }
    });
</script>
@endpush

{{-- Modal de traslado de container --}}
<div id="modalTrasladar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Trasladar productos de container</h2>
        <p class="text-sm text-gray-500 mb-4">
            Todos los productos de <span id="origenNombre" class="font-semibold text-blue-700"></span> serán movidos al container destino. El stock no se modifica.
        </p>

        <form method="POST" id="formTrasladar" action="">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Container destino</label>
                <select name="contenedor_destino_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Selecciona un container —</option>
                    @foreach($containers as $c)
                        <option value="{{ $c->id }}" class="opcion-container">{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo del traslado</label>
                <textarea name="motivo" rows="3" required maxlength="500" placeholder="Ingresa el motivo obligatorio..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalTrasladar()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    Confirmar traslado
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal de confirmación de eliminación --}}
<div id="modalEliminar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-2">¿Eliminar container?</h2>
        <p class="text-sm text-gray-600 mb-1">Estás a punto de eliminar:</p>
        <p id="nombreContainer" class="text-base font-semibold text-red-600 mb-4"></p>
        <p class="text-xs text-gray-500 mb-5">Esta acción no se puede deshacer. Solo puedes eliminar containers sin productos asignados.</p>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="cerrarModalEliminar()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                Cancelar
            </button>
            <button type="button" id="btnConfirmarEliminar"
                    class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                Sí, eliminar
            </button>
        </div>
    </div>
</div>

@push('head')
<style>
@keyframes btn-breathe-green { 0%,100%{box-shadow:0 0 0 0 rgba(22,163,74,.7)} 50%{box-shadow:0 0 0 6px rgba(22,163,74,0)} }
    @keyframes btn-breathe-blue  { 0%,100%{box-shadow:0 0 0 0 rgba(37,99,235,.7)} 50%{box-shadow:0 0 0 6px rgba(37,99,235,0)} }
    @keyframes btn-breathe-red   { 0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.7)} 50%{box-shadow:0 0 0 6px rgba(220,38,38,0)} }
    .dt-btn-excel { background:#16a34a; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s,transform .15s; }
    .dt-btn-excel:hover { background:#15803d; transform:translateY(-1px); animation:btn-breathe-green 1.6s ease-in-out infinite; }
    .dt-btn { background:#2563eb; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s,transform .15s; }
    .dt-btn:hover { background:#1d4ed8; transform:translateY(-1px); animation:btn-breathe-blue 1.6s ease-in-out infinite; }
    .dt-btn-pdf { background:#dc2626; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .2s,transform .15s; }
    .dt-btn-pdf:hover { background:#b91c1c; transform:translateY(-1px); animation:btn-breathe-red 1.6s ease-in-out infinite; }
    .btn-trasladar { background:#2563eb; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-trasladar:hover { background:#93c5fd; box-shadow:0 0 14px 4px rgba(147,197,253,0.75); transform:scale(1.05); }
    .btn-eliminar { background:#dc2626; transition: background .25s, box-shadow .25s, transform .25s; }
    .btn-eliminar:hover { background:#fca5a5; box-shadow:0 0 14px 4px rgba(252,165,165,0.75); transform:scale(1.05); }
    .fila-container { transition: background-color .6s ease; }
    .child-row-inner { animation: slideDown .5s cubic-bezier(.22,.61,.36,1); overflow:hidden; }
    .child-row-inner.closing { animation: slideUp .3s cubic-bezier(.22,.61,.36,1) forwards; }
    @keyframes slideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
    @keyframes slideUp   { from { opacity:1; transform:translateY(0); }      to { opacity:0; transform:translateY(-12px); } }
</style>
@endpush

@push('scripts')
<script>
    // --- Traslado (event delegation via data-* attributes) ---
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-trasladar');
        if (!btn) return;
        const id     = parseInt(btn.dataset.id);
        const nombre = btn.dataset.nombre;
        document.getElementById('origenNombre').textContent = nombre;
        document.getElementById('formTrasladar').action = '/admin/containers/' + id + '/trasladar';
        document.querySelectorAll('.opcion-container').forEach(function(opt) {
            opt.hidden = parseInt(opt.value) === id;
        });
        const modal = document.getElementById('modalTrasladar');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    function cerrarModalTrasladar() {
        const modal = document.getElementById('modalTrasladar');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.getElementById('formTrasladar').reset();
    }

    document.getElementById('modalTrasladar').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalTrasladar();
    });


    // --- Eliminar ---
    let formIdPendiente = null;

    function confirmarEliminar(id, nombre) {
        formIdPendiente = id;
        document.getElementById('nombreContainer').textContent = nombre;
        const modal = document.getElementById('modalEliminar');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalEliminar() {
        formIdPendiente = null;
        const modal = document.getElementById('modalEliminar');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('btnConfirmarEliminar').addEventListener('click', function () {
        if (formIdPendiente !== null) {
            document.getElementById('form-delete-' + formIdPendiente).submit();
        }
    });

    document.getElementById('modalEliminar').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalEliminar();
    });
</script>
@endpush

@endsection
