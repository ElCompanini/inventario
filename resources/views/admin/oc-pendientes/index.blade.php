@extends('layouts.app')

@section('title', 'Productos a revisar')

@section('content')

<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mt-1" style="color:#111827;">Productos a revisar</h1>
        <p class="text-sm text-gray-500 mt-1">
            Ítems de Órdenes de Compra que quedaron sin asignación de producto durante la recepción.
        </p>
    </div>
    <a href="{{ route('admin.dashboard') }}"
       style="padding:0.5rem 1rem; font-size:0.8rem; font-weight:600; color:#6366f1; background:#eef2ff; border-radius:0.5rem; text-decoration:none; white-space:nowrap; flex-shrink:0;">
        ← Volver al dashboard
    </a>
</div>

@if(session('success'))
    <div style="background:#dcfce7; border:1px solid #86efac; border-radius:0.75rem; padding:0.875rem 1.25rem; margin-bottom:1.25rem; color:#15803d; font-size:0.875rem; font-weight:500;">
        ✓ {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background:#fee2e2; border:1px solid #fca5a5; border-radius:0.75rem; padding:0.875rem 1.25rem; margin-bottom:1.25rem; color:#b91c1c; font-size:0.875rem; font-weight:500;">
        ✗ {{ session('error') }}
    </div>
@endif

@if($pendientes->isEmpty())
    <div style="background:#f0fdf4; border:1.5px solid #86efac; border-radius:1rem; padding:3rem; text-align:center;">
        <div style="font-size:2.5rem; margin-bottom:0.75rem;">✅</div>
        <p style="font-size:1.1rem; font-weight:700; color:#15803d;">Todo al día</p>
        <p style="font-size:0.875rem; color:#166534; margin-top:0.25rem;">No hay ítems pendientes de revisión.</p>
    </div>
@else
    <div class="space-y-4">
        @foreach($pendientes as $pend)
        @php
            $ocDet       = $pend->ocDetalle;
            $sicdDet     = $pend->sicdDetalle ?? $ocDet?->sicdDetalle;
            $nombreExcel = $sicdDet?->nombre_producto_excel ?? $ocDet?->sicdDetalle?->nombre_producto_excel ?? '(sin nombre)';
            $oc          = $pend->ordenCompra;
            $motivoLabel = $pend->motivoLabel();
        @endphp

        <div class="bg-white rounded-xl shadow overflow-hidden" style="border:1.5px solid #fde68a;" id="card-pend-{{ $pend->id }}">

            {{-- ── Cabecera: información del pendiente ── --}}
            <div style="background:#fffbeb; padding:1rem 1.25rem; border-bottom:1px solid #fde68a;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div style="display:flex; align-items:flex-start; gap:0.75rem; flex:1; min-width:0;">
                        <span style="font-size:1.4rem; flex-shrink:0; line-height:1;">⏳</span>
                        <div style="min-width:0;">
                            <p style="font-size:1rem; font-weight:700; color:#92400e; margin:0; word-break:break-word;">{{ $nombreExcel }}</p>
                            <p style="font-size:0.75rem; color:#b45309; margin:0.25rem 0 0; line-height:1.5;">
                                OC&nbsp;<strong>{{ $oc?->numero_oc ?? '—' }}</strong>
                                &nbsp;·&nbsp; Motivo:&nbsp;<strong>{{ $motivoLabel }}</strong>
                                @if($pend->notas)
                                    &nbsp;·&nbsp; {{ $pend->notas }}
                                @endif
                            </p>
                            <p style="font-size:0.7rem; color:#78716c; margin:0.2rem 0 0;">
                                Registrado por&nbsp;<strong>{{ $pend->pendienteUser?->name ?? '—' }}</strong>
                                &nbsp;·&nbsp; {{ $pend->pendiente_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    <span style="flex-shrink:0; font-size:0.7rem; font-weight:700; background:#fef3c7; color:#92400e; border:1px solid #fde68a; border-radius:9999px; padding:3px 12px; white-space:nowrap; align-self:flex-start;">
                        ⏳ Pendiente
                    </span>
                </div>
            </div>

            {{-- ── Botones de acción ── --}}
            <div style="padding:0.875rem 1.25rem; display:flex; gap:0.75rem; border-bottom:1px solid #f1f5f9;">
                <button type="button"
                        id="btn-editar-{{ $pend->id }}"
                        onclick="toggleEditar({{ $pend->id }})"
                        style="flex:1; padding:0.625rem 1rem; font-size:0.875rem; font-weight:600; color:#fff; background:#6366f1; border:none; border-radius:0.5rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.5rem; transition:background .15s;"
                        onmouseover="if(!this.dataset.open) this.style.background='#4f46e5'"
                        onmouseout="if(!this.dataset.open) this.style.background='#6366f1'">
                    <svg style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Editar producto
                </button>
                <button type="button"
                        onclick='abrirEliminarModal({{ $pend->id }}, {!! json_encode($nombreExcel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!})'
                        style="flex:1; padding:0.625rem 1rem; font-size:0.875rem; font-weight:600; color:#fff; background:#ef4444; border:none; border-radius:0.5rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.5rem; transition:background .15s;"
                        onmouseover="this.style.background='#dc2626'"
                        onmouseout="this.style.background='#ef4444'">
                    <svg style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Borrar producto
                </button>
            </div>

            {{-- ── Panel edición (oculto hasta presionar "Editar producto") ── --}}
            <div id="resolver-{{ $pend->id }}" style="display:none;">
                <div style="padding:1rem 1.25rem;">
                    @php
                        $similares = \App\Models\Producto::where('activo', true)
                            ->where('es_servicio', false)
                            ->where('nombre', 'like', '%' . implode('%', array_slice(explode(' ', $nombreExcel), 0, 3)) . '%')
                            ->with(['categoria.familia', 'marca'])
                            ->take(5)
                            ->get();
                        $primerSimilar = $similares->first();
                        $pctPrimero    = null;
                        if ($primerSimilar) {
                            $sP = explode(' ', strtolower($primerSimilar->nombre));
                            $eP = explode(' ', strtolower($nombreExcel));
                            $co = array_intersect($sP, $eP);
                            $pctPrimero = count($eP) > 0 ? round((count($co) / count($eP)) * 100, 1) : 0;
                        }
                    @endphp

                    {{-- Info card con borde rojo --}}
                    <div class="pend-info-card" style="border-left:3px solid #ef4444; padding:0.625rem 0.875rem; background:#f9fafb; border-radius:0 0.5rem 0.5rem 0; margin-bottom:0.875rem;">
                        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.5rem; flex-wrap:wrap;">
                            <div style="min-width:0; flex:1;">
                                <p style="font-size:0.8rem; font-weight:700; color:#111827; margin:0; text-transform:uppercase; word-break:break-word;">{{ $nombreExcel }}</p>
                                <p style="font-size:0.68rem; color:#6b7280; margin:0.2rem 0 0; line-height:1.6;">
                                    Unidad Excel:&nbsp;<strong style="color:#374151;">{{ $sicdDet?->unidad ?? '—' }}</strong>
                                    &nbsp;·&nbsp; Cant:&nbsp;<strong style="color:#374151;">{{ $sicdDet?->cantidad_solicitada ?? '—' }}</strong>
                                    @if($sicdDet?->precio_neto)
                                    &nbsp;·&nbsp; P. Neto:&nbsp;<strong style="color:#374151;">${{ number_format($sicdDet->precio_neto, 0, ',', '.') }}</strong>
                                    @endif
                                    @if($sicdDet?->total_neto)
                                    &nbsp;·&nbsp; Total:&nbsp;<strong style="color:#374151;">${{ number_format($sicdDet->total_neto, 0, ',', '.') }}</strong>
                                    @endif
                                </p>
                            </div>
                            @if($pctPrimero !== null)
                            <span style="flex-shrink:0; align-self:flex-start; font-size:0.68rem; font-weight:700; background:#fef3c7; color:#92400e; border:1px solid #fde68a; border-radius:9999px; padding:2px 8px; white-space:nowrap;">{{ $pctPrimero }}% similitud</span>
                            @endif
                        </div>
                    </div>

                    {{-- Formulario --}}
                    <form id="form-resolver-{{ $pend->id }}" method="POST" action="{{ route('admin.oc-pendientes.resolver', $pend->id) }}">
                        @csrf
                        <input type="hidden" name="producto_id" id="producto-id-{{ $pend->id }}">

                        {{-- Opción 1: más similar --}}
                        @if($primerSimilar)
                        <div id="opt1-{{ $pend->id }}"
                             onclick="seleccionarOpcion({{ $pend->id }}, 1)"
                             data-prod-id="{{ $primerSimilar->id }}"
                             data-prod-nom="{{ $primerSimilar->nombre }}"
                             data-prod-fam="{{ $primerSimilar->categoria?->familia?->nombre ?? '' }}"
                             data-prod-cat="{{ $primerSimilar->categoria?->nombre ?? '' }}"
                             data-prod-mar="{{ $primerSimilar->marca?->nombre ?? '' }}"
                             class="pend-opt-card"
                             style="border:1.5px solid #e5e7eb; border-radius:0.625rem; padding:0.75rem 0.875rem; margin-bottom:0.5rem; cursor:pointer; transition:border-color .15s; background:#fff;">
                            <div style="display:flex; align-items:flex-start; gap:0.625rem;">
                                <div id="radio1-{{ $pend->id }}" class="pend-radio indigo" style="width:0.9rem; height:0.9rem; border-radius:50%; border:2px solid #6366f1; flex-shrink:0; margin-top:2px; transition:background .15s;"></div>
                                <div style="flex:1; min-width:0;">
                                    <p style="font-size:0.8rem; font-weight:700; color:#4f46e5; margin:0;">Enlazar al producto más similar</p>
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem; margin-top:0.25rem;">
                                        <p style="font-size:0.72rem; color:#6b7280; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;">{{ $primerSimilar->nombre }}</p>
                                        <span style="flex-shrink:0; font-size:0.68rem; font-weight:700; color:{{ $pctPrimero >= 60 ? '#16a34a' : ($pctPrimero >= 30 ? '#d97706' : '#6b7280') }};">{{ $pctPrimero }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Opción 2: buscar otro --}}
                        <div id="opt2-{{ $pend->id }}"
                             class="pend-opt-card"
                             style="border:1.5px solid #e5e7eb; border-radius:0.625rem; padding:0.75rem 0.875rem; margin-bottom:0.5rem; background:#fff;">
                            <div style="display:flex; align-items:flex-start; gap:0.625rem; cursor:pointer;" onclick="seleccionarOpcion({{ $pend->id }}, 2)">
                                <div id="radio2-{{ $pend->id }}" class="pend-radio indigo" style="width:0.9rem; height:0.9rem; border-radius:50%; border:2px solid #6366f1; flex-shrink:0; margin-top:2px; transition:background .15s;"></div>
                                <p style="font-size:0.8rem; font-weight:700; color:#4f46e5; margin:0;">Enlazar a otro producto</p>
                            </div>
                            <div id="search2-{{ $pend->id }}" style="display:none; margin-top:0.625rem; position:relative;">
                                <div class="pend-search-wrap" style="display:flex; align-items:center; gap:0.5rem; border:1.5px solid #d1d5db; border-radius:0.5rem; padding:0.375rem 0.625rem; background:#f9fafb;">
                                    <svg style="width:0.8rem;height:0.8rem;color:#9ca3af;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                                    <input type="text"
                                           id="buscar-pend-{{ $pend->id }}"
                                           placeholder="Buscar producto por nombre o código de barras..."
                                           oninput="buscarProductoPend({{ $pend->id }}, this.value)"
                                           style="flex:1; border:none; background:transparent; font-size:0.78rem; color:#111827; outline:none;">
                                </div>
                                <div id="resultados-pend-{{ $pend->id }}"
                                     style="display:none; position:absolute; left:0; right:0; top:100%; margin-top:2px; background:#fff; border:1.5px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 8px 20px rgba(0,0,0,.1); z-index:50; max-height:200px; overflow-y:auto;">
                                </div>
                            </div>
                        </div>

                        {{-- Opción 3: crear nuevo --}}
                        <div id="opt3-{{ $pend->id }}"
                             class="pend-opt-card"
                             style="border:1.5px solid #e5e7eb; border-radius:0.625rem; padding:0.75rem 0.875rem; margin-bottom:0.875rem; background:#fff;">
                            <div style="display:flex; align-items:flex-start; gap:0.625rem;">
                                <div id="radio3-{{ $pend->id }}" class="pend-radio green" style="width:0.9rem; height:0.9rem; border-radius:50%; border:2px solid #16a34a; flex-shrink:0; margin-top:2px; transition:background .15s; cursor:pointer;" onclick="seleccionarOpcion({{ $pend->id }}, 3)"></div>
                                <div>
                                    <p style="font-size:0.8rem; font-weight:700; color:#16a34a; margin:0; cursor:pointer;" onclick="seleccionarOpcion({{ $pend->id }}, 3)">Crear como nuevo producto</p>
                                    <p style="font-size:0.72rem; color:#9ca3af; margin:0.2rem 0 0.5rem;">Se agrega con la descripción del Excel.</p>
                                    <a href="{{ route('admin.productos.catalogo') }}" target="_blank"
                                       style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; color:#fff; background:#16a34a; border-radius:0.375rem; text-decoration:none;"
                                       onmouseover="this.style.background='#15803d'"
                                       onmouseout="this.style.background='#16a34a'">+ Ingresar datos</a>
                                </div>
                            </div>
                        </div>

                        {{-- Sección confirmar: visible tras seleccionar opción 1 o 2 --}}
                        <div id="confirm-{{ $pend->id }}" style="display:none; border-top:1px solid #e5e7eb; padding-top:0.875rem;">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.625rem; margin-bottom:0.625rem;">
                                <div>
                                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">Cantidad <span style="color:#dc2626;">*</span></label>
                                    <input type="number" name="cantidad" required min="1"
                                           value="{{ $sicdDet?->cantidad_solicitada ?? 1 }}"
                                           style="width:100%; border:1.5px solid #d1d5db; border-radius:0.5rem; padding:0.375rem 0.625rem; font-size:0.8rem; color:#111827; background:#fff; outline:none; box-sizing:border-box;"
                                           onfocus="this.style.borderColor='#6366f1'"
                                           onblur="this.style.borderColor='#d1d5db'">
                                </div>
                                <div>
                                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.25rem;">Container (opcional)</label>
                                    <select name="container_id"
                                            style="width:100%; border:1.5px solid #d1d5db; border-radius:0.5rem; padding:0.375rem 0.625rem; font-size:0.8rem; color:#374151; background:#fff; outline:none;"
                                            onfocus="this.style.borderColor='#6366f1'"
                                            onblur="this.style.borderColor='#d1d5db'">
                                        <option value="">— Sin cambio —</option>
                                        @foreach($containers as $c)
                                            <option value="{{ $c->id }}">{{ $c->nombre }}{{ $c->centroCosto ? ' — ' . $c->centroCosto->acronimo : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit"
                                    id="btn-resolver-{{ $pend->id }}"
                                    disabled
                                    style="width:100%; padding:0.625rem; font-size:0.875rem; font-weight:600; color:#fff; background:#9ca3af; border:none; border-radius:0.5rem; cursor:not-allowed; transition:background .15s;"
                                    onmouseover="if(!this.disabled) this.style.background='#15803d'"
                                    onmouseout="if(!this.disabled) this.style.background='#16a34a'">
                                Confirmar resolución →
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
        @endforeach
    </div>
@endif

{{-- Modal eliminar pendiente --}}
<div id="modal-eliminar-pend"
     style="display:none; position:fixed; inset:0; z-index:9500; background:rgba(0,0,0,.5); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:440px; max-width:calc(100vw - 2rem);">
        <div style="padding:1.25rem 1.5rem 1rem; border-bottom:1px solid #f1f5f9;">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <div style="width:2.5rem; height:2.5rem; border-radius:9999px; background:#fee2e2; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.1rem;">
                    🗑
                </div>
                <div>
                    <p style="font-size:1rem; font-weight:700; color:#111827; margin:0;">Eliminar ítem pendiente</p>
                    <p id="elim-nombre" style="font-size:0.8rem; color:#6b7280; margin:0.1rem 0 0; max-width:340px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></p>
                </div>
            </div>
        </div>
        <form id="form-eliminar-pend" method="POST" action="">
            @csrf
            @method('DELETE')
            <div style="padding:1.25rem 1.5rem;">
                <p style="font-size:0.85rem; color:#374151; margin-bottom:0.875rem; line-height:1.6;">
                    Esta acción eliminará el ítem de la lista de revisión sin actualizar el stock.<br>
                    <span style="color:#dc2626; font-size:0.78rem;">⚠ Asegúrate de que no tiene solución antes de eliminar.</span>
                </p>
                <label style="display:block; font-size:0.78rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">Motivo de la eliminación <span style="color:#dc2626;">*</span></label>
                <textarea name="motivo_eliminacion" rows="3" required
                          placeholder="Explica por qué se elimina sin resolución..."
                          style="width:100%; border:1.5px solid #fca5a5; border-radius:0.5rem; padding:0.5rem 0.625rem; font-size:0.8rem; color:#374151; background:#fff; resize:vertical; outline:none; box-sizing:border-box;"
                          onfocus="this.style.borderColor='#ef4444'; this.style.boxShadow='0 0 0 3px rgba(239,68,68,0.15)'"
                          onblur="this.style.borderColor='#fca5a5'; this.style.boxShadow='none'"></textarea>
            </div>
            <div style="padding:0.75rem 1.5rem 1.25rem; display:flex; gap:0.5rem; justify-content:flex-end;">
                <button type="button" onclick="cerrarEliminarModal()"
                        style="padding:0.5rem 1rem; font-size:0.875rem; font-weight:500; color:#374151; background:#f3f4f6; border:none; border-radius:0.5rem; cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:0.5rem 1.25rem; font-size:0.875rem; font-weight:600; color:#fff; background:#ef4444; border:none; border-radius:0.5rem; cursor:pointer; transition:background .15s;"
                        onmouseover="this.style.background='#dc2626'"
                        onmouseout="this.style.background='#ef4444'">
                    Eliminar definitivamente
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
var _buscarTimer = {};

function buscarProductoPend(pendId, q) {
    clearTimeout(_buscarTimer[pendId]);
    var el = document.getElementById('resultados-pend-' + pendId);
    if (q.length < 2) { el.style.display = 'none'; return; }

    _buscarTimer[pendId] = setTimeout(function() {
        fetch('{{ route('admin.oc-pendientes.buscar-producto') }}?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var dark    = _isDark();
                var bgItem  = dark ? '#1e293b' : '#fff';
                var bgHover = dark ? '#1e3a5f' : '#f0f9ff';
                var bdColor = dark ? '#334155' : '#f1f5f9';
                var txtMain = dark ? '#f1f5f9' : '#111827';
                var txtSub  = dark ? '#94a3b8' : '#6b7280';

                var opt1El    = document.getElementById('opt1-' + pendId);
                var opt1ProdId = opt1El ? parseInt(opt1El.dataset.prodId || '0') : 0;

                if (!data.length) {
                    el.innerHTML = '<p style="padding:0.75rem 1rem; font-size:0.8rem; color:' + txtSub + ';">Sin resultados.</p>';
                } else {
                    var html = '';
                    data.forEach(function(p) {
                        if (p.id === opt1ProdId) {
                            html += '<div style="padding:0.5rem 0.875rem; border-bottom:1px solid ' + bdColor + '; background:' + bgItem + '; cursor:default;">'
                                 + '<p style="font-size:0.8rem; font-weight:600; color:' + txtMain + '; margin:0; opacity:0.5;">' + p.nombre + '</p>'
                                 + '<p style="font-size:0.7rem; color:#f59e0b; margin:0.15rem 0 0; font-weight:500;">⚠ No puedes enlazar el producto a sí mismo.</p>'
                                 + '</div>';
                            return;
                        }
                        var familia  = esc(p.familia  || '');
                        var categoria= esc(p.categoria|| '');
                        var marca    = esc(p.marca    || '');
                        html += '<div onclick="seleccionarProducto(' + pendId + ', ' + p.id + ', \'' + esc(p.nombre) + '\', \'' + familia + '\', \'' + categoria + '\', \'' + marca + '\')" '
                             + 'style="padding:0.5rem 0.875rem; cursor:pointer; border-bottom:1px solid ' + bdColor + '; background:' + bgItem + ';" '
                             + 'onmouseover="this.style.background=\'' + bgHover + '\'" onmouseout="this.style.background=\'' + bgItem + '\'">'
                             + '<p style="font-size:0.8rem; font-weight:600; color:' + txtMain + '; margin:0;">' + p.nombre + '</p>'
                             + '<p style="font-size:0.7rem; color:' + txtSub + '; margin:0.1rem 0 0;">'
                             + (p.familia || '—') + ' › ' + (p.categoria || '—')
                             + (p.marca ? ' · ' + p.marca : '')
                             + ' · Stock: ' + p.stock
                             + '</p></div>';
                    });
                    el.innerHTML = html;
                }
                el.style.display = 'block';
            });
    }, 300);
}

function esc(str) {
    return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function seleccionarProducto(pendId, productoId, nombre, familia, categoria, marca) {
    document.getElementById('producto-id-' + pendId).value = productoId;

    var btn = document.getElementById('btn-resolver-' + pendId);
    if (btn) { btn.disabled = false; btn.style.background = '#16a34a'; btn.style.cursor = 'pointer'; }

    var confirm = document.getElementById('confirm-' + pendId);
    if (confirm) confirm.style.display = 'block';

    var res = document.getElementById('resultados-pend-' + pendId);
    if (res) res.style.display = 'none';
    var inp = document.getElementById('buscar-pend-' + pendId);
    if (inp) inp.value = '';
}

function limpiarProducto(pendId) {
    document.getElementById('producto-id-' + pendId).value = '';
    var btn = document.getElementById('btn-resolver-' + pendId);
    if (btn) { btn.disabled = true; btn.style.background = '#9ca3af'; btn.style.cursor = 'not-allowed'; }
    var confirm = document.getElementById('confirm-' + pendId);
    if (confirm) confirm.style.display = 'none';
}

function _isDark() { return document.documentElement.classList.contains('dark'); }

function seleccionarOpcion(pendId, opcion) {
    var defaultBorder = _isDark() ? '#334155' : '#e5e7eb';
    [1, 2, 3].forEach(function(n) {
        var opt   = document.getElementById('opt' + n + '-' + pendId);
        var radio = document.getElementById('radio' + n + '-' + pendId);
        if (opt)   { opt.style.borderColor = defaultBorder; delete opt.dataset.sel; }
        if (radio) { radio.style.background = 'transparent'; }
    });

    var search2 = document.getElementById('search2-'  + pendId);
    var opt     = document.getElementById('opt' + opcion + '-' + pendId);
    var radio   = document.getElementById('radio' + opcion + '-' + pendId);
    var acColor = opcion === 3 ? '#16a34a' : '#6366f1';

    if (opt)   { opt.style.borderColor = acColor; opt.dataset.sel = '1'; }
    if (radio) { radio.style.background = acColor; }

    if (opcion === 1) {
        if (search2) search2.style.display = 'none';
        if (opt) {
            seleccionarProducto(pendId,
                parseInt(opt.dataset.prodId),
                opt.dataset.prodNom,
                opt.dataset.prodFam,
                opt.dataset.prodCat,
                opt.dataset.prodMar
            );
        }
    } else if (opcion === 2) {
        limpiarProducto(pendId);
        if (search2) { search2.style.display = 'block'; }
        var inp = document.getElementById('buscar-pend-' + pendId);
        if (inp) setTimeout(function(){ inp.focus(); }, 50);
    } else {
        limpiarProducto(pendId);
        if (search2) search2.style.display = 'none';
    }
}

// Validar que haya producto seleccionado antes de submit
document.querySelectorAll('[id^="form-resolver-"]').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var pendId = this.id.replace('form-resolver-', '');
        var prodId = document.getElementById('producto-id-' + pendId).value;
        if (!prodId) {
            e.preventDefault();
            alert('Debes seleccionar un producto antes de confirmar.');
        }
    });
});

// ── Toggle panel edición ────────────────────────────────────────────────────────
function toggleEditar(pendId) {
    var sec = document.getElementById('resolver-' + pendId);
    var btn = document.getElementById('btn-editar-' + pendId);
    var abierto = sec.style.display !== 'none';

    sec.style.display = abierto ? 'none' : 'block';
    btn.dataset.open   = abierto ? '' : '1';
    btn.style.background = abierto ? '#6366f1' : '#475569';

    if (abierto) {
        btn.innerHTML = '<svg style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Editar producto';
    } else {
        btn.innerHTML = '<svg style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg> Cerrar edición';
    }
}

// ── Modal Eliminar ──────────────────────────────────────────────────────────────
function abrirEliminarModal(pendId, nombre) {
    document.getElementById('elim-nombre').textContent = nombre;
    document.getElementById('form-eliminar-pend').action =
        '/admin/oc-pendientes/' + pendId;
    document.getElementById('modal-eliminar-pend').style.display = 'flex';
}
function cerrarEliminarModal() {
    document.getElementById('modal-eliminar-pend').style.display = 'none';
}
document.getElementById('modal-eliminar-pend').addEventListener('click', function(e) {
    if (e.target === this) cerrarEliminarModal();
});
</script>
@endpush

@push('head')
<style>
/* ── Dark mode — tarjetas pendientes ───────────────────────────── */
html.dark .bg-white { background: #1e293b !important; }

/* botones section separator */
html.dark [id^="card-pend-"] > div:nth-child(2) {
    border-bottom-color: #334155 !important;
}

/* ── info card (red-left-border) ── */
html.dark .pend-info-card {
    background: #18181b !important;
}
html.dark .pend-info-card p { color: #94a3b8 !important; }
html.dark .pend-info-card strong { color: #cbd5e1 !important; }
html.dark .pend-info-card > div > div > p:first-child { color: #f1f5f9 !important; }
html.dark .pend-info-card span[style*="background:#fef3c7"] {
    background: #451a03 !important;
    border-color: #92400e !important;
    color: #fbbf24 !important;
}

/* ── option cards ── */
html.dark .pend-opt-card {
    background: #1e293b !important;
    border-color: #334155 !important;
}
html.dark .pend-opt-card p[style*="color:#4f46e5"] { color: #818cf8 !important; }
html.dark .pend-opt-card p[style*="color:#16a34a"] { color: #4ade80 !important; }
html.dark .pend-opt-card p[style*="color:#6b7280"] { color: #94a3b8 !important; }
html.dark .pend-opt-card p[style*="color:#9ca3af"] { color: #64748b !important; }

/* search wrapper inside option 2 */
html.dark .pend-search-wrap {
    background: #0f172a !important;
    border-color: #334155 !important;
}
html.dark .pend-search-wrap input { color: #f1f5f9 !important; }

/* confirm section separator */
html.dark [id^="confirm-"] {
    border-top-color: #334155 !important;
}
html.dark [id^="confirm-"] label { color: #94a3b8 !important; }
html.dark [id^="confirm-"] input[type="number"],
html.dark [id^="confirm-"] select {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}

/* card exterior */
html.dark [id^="card-pend-"] {
    background: #1e293b !important;
    border-color: rgba(251,191,36,0.25) !important;
}
/* cabecera amber */
html.dark [id^="card-pend-"] > div:first-child {
    background: #451a03 !important;
    border-bottom-color: rgba(251,191,36,0.25) !important;
}
html.dark [id^="card-pend-"] > div:first-child p { color: #fde68a !important; }
html.dark [id^="card-pend-"] > div:first-child strong { color: #fef3c7 !important; }

/* similar products cards */
html.dark [id^="card-pend-"] [style*="background:#f9fafb"] {
    background: #0f172a !important;
    border-color: #334155 !important;
}
html.dark [id^="card-pend-"] [style*="background:#f9fafb"] p { color: #cbd5e1 !important; }

/* search results dropdown */
html.dark [id^="resultados-pend-"] {
    background: #1e293b !important;
    border-color: #334155 !important;
}
html.dark [id^="resultados-pend-"] > div { border-bottom-color: #334155 !important; }
html.dark [id^="resultados-pend-"] p { color: #cbd5e1 !important; }

/* auto-detect box */
html.dark [id^="autodetect-"] {
    background: #0c1a2e !important;
    border-color: #1e40af !important;
}
html.dark [id^="autodetect-"] p { color: #93c5fd !important; }
html.dark [id^="autodetect-"] div { color: #cbd5e1 !important; }

/* form inputs */
html.dark [id^="card-pend-"] input[type="text"],
html.dark [id^="card-pend-"] input[type="number"],
html.dark [id^="card-pend-"] select,
html.dark [id^="card-pend-"] textarea {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
/* producto-nombre display */
html.dark [id^="producto-nombre-"] {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #94a3b8 !important;
}
/* texto de sección */
html.dark [id^="card-pend-"] p[style*="color:#374151"] { color: #cbd5e1 !important; }
html.dark [id^="card-pend-"] p[style*="color:#6b7280"] { color: #94a3b8 !important; }
html.dark [id^="card-pend-"] p[style*="color:#111827"] { color: #f1f5f9 !important; }

/* link catálogo */
html.dark [id^="card-pend-"] [style*="background:#f0f9ff"] {
    background: #0c1a2e !important;
    border-color: #1e40af !important;
}
html.dark [id^="card-pend-"] [style*="background:#f0f9ff"] a { color: #60a5fa !important; }
html.dark [id^="card-pend-"] [style*="background:#f0f9ff"] p { color: #94a3b8 !important; }

/* modal eliminar */
html.dark #modal-eliminar-pend > div { background: #1e293b !important; }
html.dark #modal-eliminar-pend p { color: #e2e8f0 !important; }

/* empty state */
html.dark [style*="background:#f0fdf4"] {
    background: #052e16 !important;
    border-color: #166534 !important;
}
html.dark [style*="background:#f0fdf4"] p { color: #86efac !important; }

/* flash messages */
html.dark [style*="background:#dcfce7"] {
    background: #052e16 !important;
    border-color: #166534 !important;
    color: #86efac !important;
}
html.dark [style*="background:#fee2e2"] {
    background: #450a0a !important;
    border-color: #991b1b !important;
    color: #fca5a5 !important;
}

@media (max-width: 640px) {
    [class*="pend-grid-"] { grid-template-columns: 1fr !important; }
}
</style>
@endpush

@endsection
