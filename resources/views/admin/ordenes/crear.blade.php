@extends('layouts.app')

@section('title', 'Nueva Orden de Compra')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.ordenes.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver a Órdenes</a>
    <div class="flex items-center justify-between mt-1">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Nueva Orden de Compra</h1>
            <p class="text-sm text-gray-500 mt-0.5">Selecciona los SICDs a agrupar, agrega los N° de OC y valida contra Mercado Público.</p>
        </div>
        <span id="mp-api-badge" class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-400">● API MP…</span>
    </div>
</div>

@if($sicdsPendientes->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-8 text-center">
        <p class="text-amber-700 font-semibold">No hay SICDs pendientes de agrupar.</p>
        <a href="{{ route('admin.sicd.create') }}" class="mt-3 inline-block text-indigo-600 hover:underline text-sm">Crear un SICD primero →</a>
    </div>
@else
<form id="form-nueva-oc" method="POST" action="{{ route('admin.ordenes.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="hidden-numero-oc" name="numero_oc">

    <div class="grid grid-cols-12 gap-6">

        {{-- ══ COLUMNA IZQUIERDA ══ --}}
        <div class="col-span-12 lg:col-span-5 space-y-5">

            {{-- Card A: SICDs --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">SICDs a agrupar <span class="text-red-400">*</span></h2>
                    <span id="badge-sicd-count" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700"></span>
                </div>

                @error('sicd_ids')
                    <p class="text-red-500 text-xs px-5 pt-3">{{ $message }}</p>
                @enderror

                <div class="divide-y divide-gray-100 max-h-72 overflow-y-auto">
                    @foreach($sicdsPendientes as $sicd)
                        <label class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer"
                               data-sicd
                               data-codigo="{{ $sicd->codigo_sicd }}"
                               data-productos="{{ $sicd->detalles->count() }}"
                               data-total="{{ $sicd->detalles->sum('total_neto') }}">
                            <input type="checkbox" name="sicd_ids[]" value="{{ $sicd->id }}"
                                   {{ in_array($sicd->id, old('sicd_ids', [])) ? 'checked' : '' }}
                                   onchange="onSicdChange()"
                                   class="mt-0.5 w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-mono font-semibold text-indigo-700">{{ $sicd->codigo_sicd }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $sicd->detalles->count() }} producto(s) ·
                                    {{ $sicd->created_at->format('d/m/Y') }} · {{ $sicd->usuario->name }}
                                </p>
                                @if($sicd->descripcion)
                                    <p class="text-xs text-gray-400 truncate">{{ $sicd->descripcion }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Card B: Multi-OC --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">Órdenes de Compra</h2>

                {{-- Input + Agregar --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">
                        Número de OC <span class="text-red-400">*</span>
                    </label>
                    <div style="display:flex; gap:0.5rem;">
                        <input type="text" id="oc-add-input"
                               placeholder="Ej: 1057900-8-SE25"
                               autocomplete="off"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();agregarCodigo();}"
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono
                                      focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <button type="button" onclick="agregarCodigo()"
                                style="padding:0.4rem 0.9rem; font-size:0.75rem; font-weight:600; color:#fff;
                                       background:#4f46e5; border:none; border-radius:0.5rem; cursor:pointer; white-space:nowrap;"
                                onmouseover="this.style.background='#4338ca'"
                                onmouseout="this.style.background='#4f46e5'">
                            Agregar →
                        </button>
                    </div>
                </div>

                {{-- Lista de OCs --}}
                <div id="oc-lista-wrap" style="border:1px solid #e5e7eb; border-radius:0.5rem; overflow:hidden; max-height:260px; overflow-y:auto;">
                    <div id="oc-lista-vacia" style="padding:0.75rem 1rem; font-size:0.75rem; color:#9ca3af; font-style:italic; text-align:center;">
                        Agrega al menos una OC para continuar
                    </div>
                    <div id="oc-lista"></div>
                </div>

                @error('numero_oc')
                    <p class="text-red-500 text-xs">{{ $message }}</p>
                @enderror
            </div>

        </div>

        {{-- ══ COLUMNA DERECHA: Panel MP ══ --}}
        <div class="col-span-12 lg:col-span-7">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5" style="min-height:420px;">

                <h2 class="text-sm font-semibold text-gray-700 pb-3 mb-4 border-b border-gray-100">
                    Validación Mercado Público
                </h2>

                {{-- Placeholder --}}
                <div id="mp-placeholder" class="flex flex-col items-center justify-center py-14 text-center">
                    <svg class="w-14 h-14 text-gray-200 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-400">Agrega N° de OC en el panel izquierdo</p>
                    <p class="text-xs text-gray-300 mt-1 max-w-xs">
                        Marca el checkbox de cada OC para ver sus detalles. Puedes tener varias desplegadas al mismo tiempo.
                    </p>
                </div>

                {{-- Resultados --}}
                <div id="mp-resultados" style="display:none;"></div>

            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="mt-5 flex items-center justify-between">
        <a href="{{ route('admin.ordenes.index') }}"
           class="px-5 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Cancelar
        </a>
        <div class="flex items-center gap-3">
            <p id="submit-hint" class="text-xs text-gray-400"></p>
            <button type="submit" id="btn-submit-oc" disabled
                    style="padding:0.6rem 1.75rem; font-size:0.875rem; font-weight:700;
                           color:#fff; background:#9ca3af; border:none; border-radius:0.75rem;
                           cursor:not-allowed; opacity:0.7; transition:background .2s;">
                Finalizar Orden de Compra →
            </button>
        </div>
    </div>
</form>
@endif

@push('head')
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush

@push('scripts')
<script>
const RUTA_BUSCAR_MP  = '{{ route("admin.ordenes.buscar-mp") }}';
const RUTA_API_STATUS = '{{ route("admin.ordenes.api-status") }}';
const CSRF = '{{ csrf_token() }}';

// ─── Estado ────────────────────────────────────────────────────────────────
// entrada: {codigo, estado, data, tipoInfo, codigoLic, sicdResumen, comparacion, mensaje, expandido}
// estado: 'cargando' | 'oc' | 'licitacion' | 'no_encontrado' | 'error'
let _ocEntradas = [];

// ─── Helpers ───────────────────────────────────────────────────────────────
const $el       = id => document.getElementById(id);
const esc       = s  => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const formatCLP = n  => {
    if (!n) return '—';
    return new Intl.NumberFormat('es-CL', { style:'currency', currency:'CLP', maximumFractionDigits:0 }).format(n);
};

function getSicdIds() {
    return [...document.querySelectorAll('[name="sicd_ids[]"]:checked')].map(el => el.value);
}

function onSicdChange() {
    const ids   = getSicdIds();
    const badge = $el('badge-sicd-count');
    if (badge) badge.textContent = ids.length > 0 ? ids.length + ' seleccionado' + (ids.length > 1 ? 's' : '') : '';
    actualizarSubmit();
}

// ─── Agregar código ─────────────────────────────────────────────────────────
function agregarCodigo(codigoOverride) {
    var raw  = codigoOverride !== undefined ? codigoOverride : ($el('oc-add-input') ? $el('oc-add-input').value : '');
    var code = raw.trim().toUpperCase();
    if (!code) return;

    // Duplicado: flash
    if (_ocEntradas.find(function(e) { return e.codigo === code; })) {
        var existing = document.querySelector('[data-oc-item="' + code + '"]');
        if (existing) {
            existing.style.transition = 'background .15s';
            existing.style.background = '#fef3c7';
            setTimeout(function() { existing.style.background = ''; }, 700);
        }
        if (!codigoOverride) $el('oc-add-input').value = '';
        return;
    }

    _ocEntradas.push({
        codigo: code, estado: 'cargando',
        data: null, tipoInfo: null, codigoLic: null,
        sicdResumen: [], comparacion: {}, mensaje: '', expandido: false
    });
    if (!codigoOverride && $el('oc-add-input')) $el('oc-add-input').value = '';

    renderLista();
    renderPanel();
    actualizarSubmit();
    consultarCodigo(code);
}

// ─── Quitar código ──────────────────────────────────────────────────────────
function quitarCodigo(code) {
    _ocEntradas = _ocEntradas.filter(function(e) { return e.codigo !== code; });
    renderLista();
    renderPanel();
    actualizarSubmit();
}

// ─── Reintentar ─────────────────────────────────────────────────────────────
function reintentarCodigo(code) {
    var entrada = _ocEntradas.find(function(e) { return e.codigo === code; });
    if (!entrada) return;
    entrada.estado   = 'cargando';
    entrada.data     = null;
    entrada.mensaje  = '';
    entrada.expandido = false;
    renderLista();
    renderPanel();
    consultarCodigo(code);
}

// ─── Cola de consultas (evita peticiones simultáneas que generan error 10500) ─
var _apiQueue   = [];
var _apiRunning = false;

function consultarCodigo(code) {
    _apiQueue.push(code);
    if (!_apiRunning) _procesarCola();
}

function _procesarCola() {
    if (_apiQueue.length === 0) { _apiRunning = false; return; }
    _apiRunning = true;
    var code = _apiQueue.shift();

    // Si el usuario quitó la entrada mientras esperaba, saltar
    if (!_ocEntradas.find(function(e) { return e.codigo === code; })) {
        setTimeout(_procesarCola, 0);
        return;
    }

    _fetchCodigo(code, function() {
        // Pausa de 800 ms entre llamadas para no saturar la API
        setTimeout(_procesarCola, 800);
    });
}

function _fetchCodigo(code, done) {
    fetch(RUTA_BUSCAR_MP, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ codigo: code, sicd_ids: getSicdIds() }),
    })
    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
    .then(function(res) {
        var ok   = res.ok;
        var data = res.data;
        var entrada = _ocEntradas.find(function(e) { return e.codigo === code; });
        if (!entrada) { done(); return; }

        if (!ok || data.error) {
            entrada.estado    = 'error';
            entrada.mensaje   = data.mensaje || 'Error desconocido.';
            entrada.expandido = true;
        } else if (data.encontrado) {
            entrada.estado      = data.tipo_busqueda;
            entrada.data        = data.api_data    || {};
            entrada.tipoInfo    = data.tipo_info   || {};
            entrada.codigoLic   = data.codigo_lic  || null;
            entrada.sicdResumen = data.sicd_resumen || [];
            entrada.comparacion = data.comparacion  || {};
            entrada.expandido   = true;
        } else {
            entrada.estado    = 'no_encontrado';
            entrada.mensaje   = data.mensaje || ('«' + code + '» no encontrado.');
            entrada.expandido = true;
        }

        renderLista();
        renderPanel();
        actualizarSubmit();
        done();
    })
    .catch(function() {
        var entrada = _ocEntradas.find(function(e) { return e.codigo === code; });
        if (entrada) {
            entrada.estado    = 'error';
            entrada.mensaje   = 'No se pudo conectar con Mercado Público.';
            entrada.expandido = true;
            renderLista();
            renderPanel();
            actualizarSubmit();
        }
        done();
    });
}

// ─── Toggle expand/collapse ─────────────────────────────────────────────────
function toggleExpandir(code) {
    var entrada = _ocEntradas.find(function(e) { return e.codigo === code; });
    if (!entrada) return;
    entrada.expandido = !entrada.expandido;
    _syncExpandido(code, entrada.expandido);
}

function _syncExpandido(code, val) {
    // Sync left-list checkbox
    var cb = document.querySelector('#oc-lista [data-oc-cb="' + code + '"]');
    if (cb) cb.checked = val;

    // Toggle body
    var body = document.querySelector('[data-oc-body="' + code + '"]');
    if (body) body.style.display = val ? 'block' : 'none';

    // Toggle card border
    var card = document.querySelector('[data-oc-card="' + code + '"]');
    if (card) {
        card.style.borderColor = val ? '#4f46e5' : '#e5e7eb';
        card.style.boxShadow   = val ? '0 0 0 2px #e0e7ff' : 'none';
    }
}

// ─── Render lista izquierda ─────────────────────────────────────────────────
function renderLista() {
    var lista = $el('oc-lista');
    var vacia = $el('oc-lista-vacia');
    if (!lista) return;

    if (_ocEntradas.length === 0) {
        lista.innerHTML = '';
        if (vacia) vacia.style.display = 'block';
        return;
    }
    if (vacia) vacia.style.display = 'none';

    lista.innerHTML = _ocEntradas.map(function(e) {
        var badge = _badgeHtml(e.estado);
        var codeEsc = esc(e.codigo);
        return '<div data-oc-item="' + codeEsc + '"'
             + ' style="display:flex;align-items:center;gap:0.5rem;padding:0.45rem 0.75rem;border-top:1px solid #f3f4f6;cursor:pointer;"'
             + ' onclick="toggleExpandir(\'' + codeEsc + '\')">'
             + '<input type="checkbox" data-oc-cb="' + codeEsc + '" '
             + (e.expandido ? 'checked ' : '')
             + 'onclick="event.stopPropagation()" onchange="toggleExpandir(\'' + codeEsc + '\')" '
             + 'style="width:14px;height:14px;accent-color:#4f46e5;cursor:pointer;flex-shrink:0;">'
             + '<span style="font-family:monospace;font-size:0.78rem;font-weight:600;color:#1f2937;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + codeEsc + '</span>'
             + badge
             + '<button type="button"'
             + ' onclick="event.stopPropagation();quitarCodigo(\'' + codeEsc + '\')"'
             + ' style="flex-shrink:0;padding:1px 7px;font-size:0.8rem;color:#9ca3af;background:transparent;border:1px solid #d1d5db;border-radius:0.35rem;cursor:pointer;line-height:1.6;"'
             + ' onmouseover="this.style.color=\'#ef4444\';this.style.borderColor=\'#fca5a5\'"'
             + ' onmouseout="this.style.color=\'#9ca3af\';this.style.borderColor=\'#d1d5db\'">×</button>'
             + '</div>';
    }).join('');
}

// ─── Render panel derecho ───────────────────────────────────────────────────
function renderPanel() {
    var placeholder = $el('mp-placeholder');
    var resultados  = $el('mp-resultados');
    if (!resultados) return;

    if (_ocEntradas.length === 0) {
        if (placeholder) placeholder.style.display = 'flex';
        resultados.style.display = 'none';
        resultados.innerHTML = '';
        return;
    }
    if (placeholder) placeholder.style.display = 'none';
    resultados.style.display = 'block';
    resultados.innerHTML = _ocEntradas.map(function(e) { return _buildCard(e); }).join('');
}

// ─── Build card ─────────────────────────────────────────────────────────────
function _buildCard(e) {
    var isExp = e.expandido;
    var codeEsc = esc(e.codigo);
    var borderStyle = isExp
        ? 'border-color:#4f46e5;box-shadow:0 0 0 2px #e0e7ff;'
        : 'border-color:#e5e7eb;';

    var headerBadge = (e.estado === 'oc' && e.tipoInfo)
        ? _ocTypeBadgeHtml(e.tipoInfo)
        : _badgeHtml(e.estado);

    // Extra in header for error/not-found state: reintentar button
    var headerExtra = '';
    if (e.estado === 'error' || e.estado === 'no_encontrado') {
        headerExtra = '<button type="button" onclick="event.stopPropagation();reintentarCodigo(\'' + codeEsc + '\')"'
            + ' style="font-size:0.7rem;font-weight:600;color:#4f46e5;background:#eef2ff;border:1px solid #a5b4fc;border-radius:0.375rem;padding:2px 9px;cursor:pointer;white-space:nowrap;flex-shrink:0;">'
            + 'Reintentar</button>';
    }

    var body = '';
    if (e.estado === 'cargando') {
        body = '<div style="display:flex;align-items:center;gap:8px;padding:12px 0;color:#6b7280;font-size:0.8rem;">'
             + '<svg style="width:16px;height:16px;flex-shrink:0;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24">'
             + '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/>'
             + '<path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" style="opacity:.75"/>'
             + '</svg>Consultando Mercado Público…</div>';
    } else if (e.estado === 'oc') {
        body = _buildOCBody(e);
    } else if (e.estado === 'licitacion') {
        body = _buildLicitacionBody(e);
    } else if (e.estado === 'no_encontrado') {
        body = '<p style="font-size:0.78rem;color:#b91c1c;padding:6px 0;">' + esc(e.mensaje || 'Código no encontrado. Verifica que sea correcto.') + '</p>';
    } else if (e.estado === 'error') {
        body = '<p style="font-size:0.78rem;color:#b91c1c;padding:6px 0;">' + esc(e.mensaje || 'Error al consultar.') + '</p>';
    }

    return '<div data-oc-card="' + codeEsc + '" style="border:2px solid;border-radius:0.75rem;overflow:hidden;margin-bottom:0.75rem;transition:border-color .15s,box-shadow .15s;' + borderStyle + '">'
        + '<div style="display:flex;align-items:center;gap:0.65rem;padding:0.55rem 0.9rem;background:#f9fafb;border-bottom:1px solid #e5e7eb;">'
        + '<span style="font-family:monospace;font-size:0.85rem;font-weight:700;color:#1f2937;flex:1;">' + codeEsc + '</span>'
        + headerBadge
        + headerExtra
        + '</div>'
        + '<div data-oc-body="' + codeEsc + '" style="padding:0.75rem 1rem;display:' + (isExp ? 'block' : 'none') + ';">'
        + body
        + '</div>'
        + '</div>';
}

// ─── OC body ────────────────────────────────────────────────────────────────
function _buildOCBody(e) {
    var d       = e.data        || {};
    var comp    = e.comparacion || {};
    var licLink = e.codigoLic   || d.codigo_licitacion || '';
    var ti      = e.tipoInfo    || {};
    var html    = '';

    // Tipo de proceso — grande y destacado
    if (ti.label) {
        var styleMap = {
            'bg-green-100 text-green-800 border-green-200':    'background:#dcfce7;color:#166534;border:2px solid #86efac;',
            'bg-indigo-100 text-indigo-800 border-indigo-200': 'background:#e0e7ff;color:#3730a3;border:2px solid #a5b4fc;',
            'bg-amber-100 text-amber-800 border-amber-200':    'background:#fef3c7;color:#92400e;border:2px solid #fcd34d;',
            'bg-blue-100 text-blue-800 border-blue-200':       'background:#dbeafe;color:#1e40af;border:2px solid #93c5fd;',
            'bg-purple-100 text-purple-800 border-purple-200': 'background:#f3e8ff;color:#6b21a8;border:2px solid #d8b4fe;',
        };
        var s = styleMap[ti.clase] || 'background:#f3f4f6;color:#374151;border:2px solid #d1d5db;';
        html += '<div style="text-align:center;margin-bottom:0.75rem;">'
              + '<span style="display:inline-flex;align-items:center;gap:0.4rem;font-size:1.35rem;font-weight:800;padding:0.45rem 1.4rem;border-radius:1rem;letter-spacing:-0.01em;' + s + '">'
              + esc((ti.icono || '') + ' ' + ti.label)
              + '</span>'
              + '</div>';
    }

    html += '<div style="background:#f9fafb;border-radius:0.5rem;padding:0.45rem 0.7rem;display:flex;flex-wrap:wrap;gap:0.3rem 1.25rem;font-size:0.7rem;margin-bottom:0.5rem;border:1px solid #f3f4f6;">'
          + '<div><span style="color:#9ca3af;">Proveedor: </span><b style="color:#1f2937;">' + esc(d.proveedor_nombre || '—') + '</b></div>'
          + '<div><span style="color:#9ca3af;">RUT: </span><span style="color:#374151;">' + esc(d.proveedor_rut || '—') + '</span></div>'
          + '<div><span style="color:#9ca3af;">Estado: </span><span style="color:#374151;">' + esc(d.estado || '—') + '</span></div>'
          + '<div><span style="color:#9ca3af;">Envío: </span><span style="color:#374151;">' + esc(d.fecha_envio || '—') + '</span></div>'
          + (licLink ? '<div><span style="color:#9ca3af;">Licitación: </span><span style="font-family:monospace;font-weight:600;color:#4338ca;">' + esc(licLink) + '</span></div>' : '')
          + '</div>';

    if (d.nombre) {
        html += '<p style="font-size:0.7rem;color:#6b7280;font-style:italic;margin-bottom:0.5rem;">"' + esc(d.nombre) + '"</p>';
    }

    html += _buildItemsTable(d.items || [], d);

    // Comparación — solo si hay datos de SICD con monto
    var totalSC = comp.total_sicd || 0;
    if (comp.total_mp && totalSC > 0) {
        var diff = Math.abs(totalSC - comp.total_mp);
        var pct  = ((diff / comp.total_mp) * 100).toFixed(1);
        var ok   = comp.coincide;
        html += '<div style="margin-top:0.5rem;background:#f9fafb;border-radius:0.5rem;padding:0.45rem 0.7rem;font-size:0.7rem;border:1px solid #e5e7eb;">'
              + '<div style="display:flex;justify-content:space-between;padding:2px 0;"><span style="color:#6b7280;">Total SICDs</span><span style="font-weight:600;">' + formatCLP(totalSC) + '</span></div>'
              + '<div style="display:flex;justify-content:space-between;padding:2px 0;border-top:1px solid #f3f4f6;"><span style="color:#6b7280;">Total MP</span><span style="font-weight:600;">' + formatCLP(comp.total_mp) + '</span></div>'
              + '<div style="display:flex;justify-content:space-between;padding:2px 0;border-top:1px solid #f3f4f6;"><span style="color:#6b7280;">Diferencia</span><span style="font-weight:700;color:' + (ok ? '#16a34a' : '#d97706') + ';">' + pct + '%&nbsp;' + (ok ? '✓ Aceptable' : '⚠ Revisar') + '</span></div>'
              + '</div>';
    }

    html += '<div style="margin-top:0.6rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:0.6rem;padding:0.45rem 0.7rem;display:flex;align-items:center;gap:0.5rem;">'
          + '<span>✅</span>'
          + '<p style="font-size:0.7rem;font-weight:700;color:#15803d;">OC validada correctamente en Mercado Público</p>'
          + '</div>';

    return html;
}

// ─── Licitación body ────────────────────────────────────────────────────────
function _buildLicitacionBody(e) {
    var d = e.data || {};
    return '<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:0.5rem;padding:0.45rem 0.7rem;display:flex;gap:0.5rem;align-items:start;margin-bottom:0.5rem;">'
         + '<span style="flex-shrink:0;">⚠️</span>'
         + '<div><p style="font-size:0.7rem;font-weight:700;color:#92400e;">Se encontró la Licitación — no el código de OC</p>'
         + '<p style="font-size:0.68rem;color:#b45309;margin-top:2px;">Busca el código de OC asociado en Mercado Público e ingrésalo en el campo de la izquierda.</p></div>'
         + '</div>'
         + '<div style="font-size:0.7rem;display:grid;grid-template-columns:1fr 1fr;gap:0.4rem 1rem;">'
         + '<div><span style="color:#9ca3af;font-size:0.65rem;text-transform:uppercase;letter-spacing:.05em;">Organismo</span><br><b style="color:#1f2937;">' + esc(d.organismo || '—') + '</b></div>'
         + '<div><span style="color:#9ca3af;font-size:0.65rem;text-transform:uppercase;letter-spacing:.05em;">Estado</span><br><span style="color:#374151;">' + esc(d.estado || '—') + '</span></div>'
         + '<div><span style="color:#9ca3af;font-size:0.65rem;text-transform:uppercase;letter-spacing:.05em;">Monto Estimado</span><br><span style="color:#374151;">' + formatCLP(d.monto_estimado) + '</span></div>'
         + '<div><span style="color:#9ca3af;font-size:0.65rem;text-transform:uppercase;letter-spacing:.05em;">Total Adjudicado</span><br><b style="color:#15803d;">' + (d.total_adjudicado > 0 ? formatCLP(d.total_adjudicado) : '—') + '</b></div>'
         + '<div><span style="color:#9ca3af;font-size:0.65rem;text-transform:uppercase;letter-spacing:.05em;">Proveedor</span><br><span style="color:#374151;">' + esc(d.proveedor_nombre || '—') + '</span></div>'
         + '<div><span style="color:#9ca3af;font-size:0.65rem;text-transform:uppercase;letter-spacing:.05em;">Cierre</span><br><span style="color:#374151;">' + esc(d.fecha_cierre || '—') + '</span></div>'
         + '</div>';
}

// ─── Badges ─────────────────────────────────────────────────────────────────
function _badgeHtml(estado) {
    var cfgs = {
        cargando:      { bg:'#f3f4f6', color:'#6b7280',  text:'⟳ Consultando…' },
        oc:            { bg:'#dcfce7', color:'#15803d',  text:'✓ OC' },
        licitacion:    { bg:'#fef3c7', color:'#92400e',  text:'⚠ Licitación' },
        no_encontrado: { bg:'#fee2e2', color:'#b91c1c',  text:'✗ No encontrado' },
        error:         { bg:'#fee2e2', color:'#b91c1c',  text:'⚠ Error' },
    };
    var c = cfgs[estado] || cfgs.error;
    return '<span style="font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:9999px;background:' + c.bg + ';color:' + c.color + ';white-space:nowrap;flex-shrink:0;">' + c.text + '</span>';
}

function _ocTypeBadgeHtml(ti) {
    if (!ti || !ti.label) return _badgeHtml('oc');
    var styleMap = {
        'bg-green-100 text-green-800 border-green-200':    'background:#dcfce7;color:#166534;border:1px solid #bbf7d0;',
        'bg-indigo-100 text-indigo-800 border-indigo-200': 'background:#e0e7ff;color:#3730a3;border:1px solid #c7d2fe;',
        'bg-amber-100 text-amber-800 border-amber-200':    'background:#fef3c7;color:#92400e;border:1px solid #fde68a;',
        'bg-blue-100 text-blue-800 border-blue-200':       'background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;',
        'bg-purple-100 text-purple-800 border-purple-200': 'background:#f3e8ff;color:#6b21a8;border:1px solid #e9d5ff;',
    };
    var s = styleMap[ti.clase] || 'background:#f3f4f6;color:#374151;border:1px solid #d1d5db;';
    return '<span style="font-size:0.7rem;font-weight:700;padding:3px 10px;border-radius:9999px;white-space:nowrap;flex-shrink:0;' + s + '">' + esc((ti.icono || '') + ' ' + (ti.label || 'OC')) + '</span>';
}

// ─── Tabla de ítems + resumen financiero ────────────────────────────────────
function _buildItemsTable(items, d) {
    var neto      = d.total_neto  || 0;
    var total     = d.total       || 0;
    var impuestos = d.impuestos   || (total > neto ? total - neto : 0);
    var dcto = 0, cargos = 0;
    items.forEach(function(it) { dcto += (it.descuento || 0); cargos += (it.cargo || 0); });

    var filas = items.length > 0
        ? items.map(function(it) {
            return '<tr style="border-bottom:1px solid #f3f4f6;">'
                + '<td style="padding:5px 7px;font-family:monospace;color:#6b7280;white-space:nowrap;">' + esc(it.codigo) + '</td>'
                + '<td style="padding:5px 7px;color:#1f2937;max-width:110px;overflow:hidden;text-overflow:ellipsis;" title="' + esc(it.nombre) + '">' + esc(it.nombre) + '</td>'
                + '<td style="padding:5px 7px;text-align:center;white-space:nowrap;color:#374151;font-weight:600;">' + esc(it.cantidad) + '</td>'
                + '<td style="padding:5px 7px;color:#4b5563;max-width:130px;">' + esc(it.especificacion_comprador) + '</td>'
                + '<td style="padding:5px 7px;color:#4b5563;max-width:130px;">' + esc(it.especificacion_proveedor) + '</td>'
                + '<td style="padding:5px 7px;text-align:right;white-space:nowrap;color:#374151;">' + _fmtNum(it.precio_unitario) + '</td>'
                + '<td style="padding:5px 7px;text-align:right;white-space:nowrap;color:#374151;">' + _fmtDec(it.descuento) + '</td>'
                + '<td style="padding:5px 7px;text-align:right;white-space:nowrap;color:#374151;">' + _fmtDec(it.cargo) + '</td>'
                + '<td style="padding:5px 7px;text-align:right;white-space:nowrap;font-weight:600;color:#1f2937;">' + _fmtNum(it.total) + '</td>'
                + '</tr>';
          }).join('')
        : '<tr><td colspan="9" style="padding:10px;text-align:center;color:#9ca3af;font-style:italic;">Sin detalle de ítems</td></tr>';

    return '<div style="overflow-x:auto;border:1px solid #e5e7eb;border-radius:0.5rem;">'
         + '<table style="min-width:100%;font-size:0.7rem;border-collapse:collapse;">'
         + '<thead style="background:#f9fafb;"><tr style="border-bottom:1px solid #e5e7eb;">'
         + '<th style="padding:5px 7px;text-align:left;font-weight:600;color:#6b7280;white-space:nowrap;">Código</th>'
         + '<th style="padding:5px 7px;text-align:left;font-weight:600;color:#6b7280;">Producto</th>'
         + '<th style="padding:5px 7px;text-align:center;font-weight:600;color:#6b7280;">Cantidad</th>'
         + '<th style="padding:5px 7px;text-align:left;font-weight:600;color:#6b7280;">Esp. Comprador</th>'
         + '<th style="padding:5px 7px;text-align:left;font-weight:600;color:#6b7280;">Esp. Proveedor</th>'
         + '<th style="padding:5px 7px;text-align:right;font-weight:600;color:#6b7280;white-space:nowrap;">Precio Unit.</th>'
         + '<th style="padding:5px 7px;text-align:right;font-weight:600;color:#6b7280;">Descuento</th>'
         + '<th style="padding:5px 7px;text-align:right;font-weight:600;color:#6b7280;">Cargos</th>'
         + '<th style="padding:5px 7px;text-align:right;font-weight:600;color:#6b7280;white-space:nowrap;">Valor Total</th>'
         + '</tr></thead>'
         + '<tbody>' + filas + '</tbody></table></div>'
         + '<div style="display:flex;justify-content:flex-end;margin-top:8px;">'
         + '<table style="font-size:0.72rem;border:1px solid #e5e7eb;border-radius:0.5rem;overflow:hidden;min-width:200px;">'
         + '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:4px 12px;color:#6b7280;">Neto</td><td style="padding:4px 12px;text-align:right;font-weight:600;color:#374151;">$ ' + _fmtNum(neto) + '</td></tr>'
         + '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:4px 12px;color:#6b7280;">Dcto.</td><td style="padding:4px 12px;text-align:right;color:#374151;">$ ' + _fmtNum(dcto) + '</td></tr>'
         + '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:4px 12px;color:#6b7280;">Cargos</td><td style="padding:4px 12px;text-align:right;color:#374151;">$ ' + _fmtNum(cargos) + '</td></tr>'
         + '<tr style="border-bottom:1px solid #d1d5db;background:#f9fafb;"><td style="padding:4px 12px;font-weight:600;color:#374151;">Subtotal</td><td style="padding:4px 12px;text-align:right;font-weight:600;color:#374151;">$ ' + _fmtNum(neto) + '</td></tr>'
         + '<tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:4px 12px;color:#6b7280;">19% IVA</td><td style="padding:4px 12px;text-align:right;color:#374151;">$ ' + _fmtNum(impuestos) + '</td></tr>'
         + '<tr style="background:#f9fafb;"><td style="padding:6px 12px;font-weight:700;color:#1f2937;">Total</td><td style="padding:6px 12px;text-align:right;font-weight:700;color:#15803d;">$ ' + _fmtNum(total) + '</td></tr>'
         + '</table></div>';
}

function _fmtNum(n) {
    if (n == null || n === '') return '0';
    return new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(n);
}
function _fmtDec(n) {
    if (n == null || n === '') return '0,00';
    return new Intl.NumberFormat('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}

// ─── Submit ──────────────────────────────────────────────────────────────────
function actualizarSubmit() {
    var btn  = $el('btn-submit-oc');
    var hint = $el('submit-hint');
    if (!btn) return;

    var primeraOC = _ocEntradas.find(function(e) { return e.estado === 'oc'; });
    var sicdCount = getSicdIds().length;
    var valid     = !!(primeraOC && sicdCount > 0);

    var hiddenOC = $el('hidden-numero-oc');
    if (hiddenOC) hiddenOC.value = primeraOC ? primeraOC.codigo : '';

    btn.disabled = !valid;
    if (valid) {
        btn.style.background = '#16a34a';
        btn.style.cursor     = 'pointer';
        btn.style.opacity    = '1';
        if (hint) hint.textContent = '';
    } else {
        btn.style.background = '#9ca3af';
        btn.style.cursor     = 'not-allowed';
        btn.style.opacity    = '0.65';
        if (hint) {
            if (sicdCount === 0 && _ocEntradas.length === 0) {
                hint.textContent = 'Selecciona SICDs y agrega una OC';
            } else if (sicdCount === 0) {
                hint.textContent = 'Selecciona al menos un SICD';
            } else if (!primeraOC) {
                hint.textContent = 'Agrega y valida al menos una OC';
            }
        }
    }
}

// ─── Estado API ───────────────────────────────────────────────────────────
fetch(RUTA_API_STATUS, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var badge = $el('mp-api-badge');
        if (badge) {
            badge.textContent = data.activa ? '● API MP activa' : '● API MP inactiva';
            badge.className   = 'text-xs font-medium px-2.5 py-1 rounded-full ' + (data.activa ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600');
        }
    })
    .catch(function() {});

// ─── Init ────────────────────────────────────────────────────────────────
actualizarSubmit();

@if(old('sicd_ids'))
    setTimeout(onSicdChange, 100);
@endif
</script>
@endpush

@endsection
