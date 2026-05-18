@extends('layouts.app')

@section('title', 'Mis Solicitudes')

@push('head')
<style>
/* Tarjetas de solicitud */
.msol-card {
    background: #fff;
    border-radius: .75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: box-shadow .2s;
}
.msol-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.1); }
html.dark .msol-card { background: #1e293b; border-color: #334155; }

/* Badge de estado solicitud */
.sol-badge { display:inline-flex; align-items:center; gap:.3rem; font-size:.7rem; font-weight:700; padding:2px 9px; border-radius:9999px; }
.sol-pendiente  { background:#fef3c7; color:#92400e; }
.sol-aprobado   { background:#dcfce7; color:#15803d; }
.sol-rechazado  { background:#fee2e2; color:#991b1b; }
.sol-en-dev     { background:#dbeafe; color:#1d4ed8; }
.sol-cerrada    { background:#f3f4f6; color:#4b5563; }
html.dark .sol-pendiente { background:rgba(146,64,14,.25); color:#fcd34d; }
html.dark .sol-aprobado  { background:rgba(6,95,70,.22); color:#6ee7b7; }
html.dark .sol-rechazado { background:rgba(153,27,27,.25); color:#fca5a5; }
html.dark .sol-en-dev    { background:rgba(29,78,216,.22); color:#93c5fd; }
html.dark .sol-cerrada   { background:rgba(71,85,105,.3); color:#94a3b8; }

/* Badge estado devolución */
.dev-badge { display:inline-flex; align-items:center; gap:.25rem; font-size:.68rem; font-weight:700; padding:1px 7px; border-radius:9999px; }
.dev-pendiente { background:#fef3c7; color:#92400e; }
.dev-aprobada  { background:#dcfce7; color:#15803d; }
.dev-rechazada { background:#fee2e2; color:#991b1b; }
html.dark .dev-pendiente { background:rgba(146,64,14,.22); color:#fcd34d; }
html.dark .dev-aprobada  { background:rgba(6,95,70,.2); color:#6ee7b7; }
html.dark .dev-rechazada { background:rgba(153,27,27,.22); color:#fca5a5; }

/* Botón devolver */
.btn-devolver {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.4rem .9rem; font-size:.78rem; font-weight:700;
    color:#fff; background:#4f46e5; border:none; border-radius:.5rem;
    cursor:pointer; transition:background .15s, transform .1s;
    white-space:nowrap;
}
.btn-devolver:hover { background:#4338ca; transform:scale(1.03); }
.btn-devolver:disabled { opacity:.4; cursor:not-allowed; transform:none; }
html.dark .btn-devolver { background:#6366f1; }
html.dark .btn-devolver:hover { background:#4f46e5; }

/* Sub-fila devoluciones */
.dev-row { background:#f8fafc; border-top:1px solid #f1f5f9; }
html.dark .dev-row { background:#0f172a; border-top-color:#1e293b; }

/* Modal */
#modalSolDev {
    position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,.55);
    align-items:center; justify-content:center;
}
.modal-card {
    background:#fff; border-radius:1rem;
    box-shadow:0 24px 60px rgba(0,0,0,.25);
    width:500px; max-width:calc(100vw - 2rem);
    padding:1.75rem;
    animation:modal-in .25s cubic-bezier(.22,.68,0,1.2) both;
}
html.dark .modal-card { background:#1e293b; }
@keyframes modal-in {
    from { opacity:0; transform:scale(.94) translateY(8px); }
    to   { opacity:1; transform:scale(1)   translateY(0); }
}
.mf-label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
html.dark .mf-label { color:#e2e8f0; }
.mf-input {
    width:100%; border:1px solid #d1d5db; border-radius:.5rem;
    padding:.45rem .65rem; font-size:.88rem; box-sizing:border-box; outline:none;
    background:#fff; color:#111827;
    transition:border-color .15s, box-shadow .15s;
}
.mf-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
html.dark .mf-input { background:#0f172a; border-color:#334155; color:#f1f5f9; }
html.dark .mf-input:focus { border-color:#818cf8; box-shadow:0 0 0 3px rgba(129,140,248,.15); }

/* Texto dark */
html.dark h1, html.dark h2 { color:#f1f5f9; }
html.dark .text-gray-800 { color:#f1f5f9 !important; }
html.dark .text-gray-700 { color:#e2e8f0 !important; }
html.dark .text-gray-600 { color:#cbd5e1 !important; }
html.dark .text-gray-500 { color:#94a3b8 !important; }
html.dark .text-gray-400 { color:#64748b !important; }
</style>
@endpush

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Mis Solicitudes</h1>
    <p class="text-sm text-gray-500 mt-1">Historial de tus solicitudes de movimiento de stock y devoluciones</p>
</div>

@if($errors->any())
<div style="background:#fee2e2; border:1px solid #fecaca; color:#991b1b; border-radius:.6rem; padding:.75rem 1rem; margin-bottom:1.25rem; font-size:.85rem;">
    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

@forelse($solicitudes as $solicitud)
@if(!$solicitud->producto) @continue @endif
@php
    $devsSolicitud = $misDevolucionesPorSolicitud[$solicitud->id] ?? collect();
    $devAprobadas  = (int)$devsSolicitud->where('estado','aprobada')->sum('cantidad');
    $devPendientes = (int)$devsSolicitud->where('estado','pendiente')->sum('cantidad');
    $disponible    = max(0, $solicitud->cantidad - $devAprobadas - $devPendientes);

    $esSalidaFisica = $solicitud->tipo === 'salida'
        && in_array($solicitud->estado, ['aprobado', 'en_devolucion'])
        && !($solicitud->producto->es_servicio ?? true);

    $puedeDevolver = $esSalidaFisica && $disponible > 0;

    $solRef = 'SOL-' . str_pad($solicitud->id, 6, '0', STR_PAD_LEFT);

    $estadoClass = match($solicitud->estado) {
        'pendiente'     => 'sol-pendiente',
        'aprobado'      => 'sol-aprobado',
        'rechazado'     => 'sol-rechazado',
        'en_devolucion' => 'sol-en-dev',
        'cerrada'       => 'sol-cerrada',
        default         => 'sol-pendiente',
    };
    $estadoLabel = match($solicitud->estado) {
        'pendiente'     => '⏳ Pendiente',
        'aprobado'      => '✓ Aprobado',
        'rechazado'     => '✗ Rechazado',
        'en_devolucion' => '↩ En devolución',
        'cerrada'       => '⊘ Cerrada',
        default         => $solicitud->estado,
    };
@endphp

<div class="msol-card mb-3">

    {{-- Fila principal --}}
    <div class="px-5 py-4 flex items-center gap-4 flex-wrap">

        {{-- Ref + fecha --}}
        <div class="flex-shrink-0 text-right" style="min-width:90px;">
            <p class="text-xs font-bold text-gray-400">{{ $solRef }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $solicitud->created_at->format('d/m/Y H:i') }}</p>
        </div>

        {{-- Separador --}}
        <div style="width:1px; height:36px; background:#e5e7eb; flex-shrink:0;"></div>

        {{-- Producto + tipo --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-gray-800 truncate">{{ $solicitud->producto->nombre }}</span>
                @if($solicitud->tipo === 'salida')
                    <span class="sol-badge" style="background:#fff7ed; color:#c2410c;">↓ Salida</span>
                @else
                    <span class="sol-badge" style="background:#f0fdf4; color:#15803d;">↑ Entrada</span>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-0.5 truncate" title="{{ $solicitud->motivo }}">
                Motivo: {{ Str::limit($solicitud->motivo, 60) }}
            </p>
        </div>

        {{-- Cantidad --}}
        <div class="text-center flex-shrink-0">
            <p class="text-xs text-gray-400 mb-0.5">Cantidad</p>
            <p class="text-lg font-bold text-gray-800">{{ $solicitud->cantidad }}</p>
        </div>

        {{-- Estado --}}
        <div class="flex-shrink-0">
            <span class="sol-badge {{ $estadoClass }}">{{ $estadoLabel }}</span>
        </div>

        {{-- Botón devolver --}}
        @if($puedeDevolver)
        <button type="button" class="btn-devolver flex-shrink-0"
                onclick="abrirModalDev(
                    {{ $solicitud->id }},
                    '{{ addslashes($solicitud->producto->nombre) }}',
                    '{{ $solRef }}',
                    {{ $solicitud->cantidad }},
                    {{ $devAprobadas }},
                    {{ $devPendientes }},
                    {{ $disponible }}
                )">
            <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            Devolver materiales
        </button>
        @elseif($esSalidaFisica && $disponible === 0 && (int)$devPendientes > 0)
        <span class="flex-shrink-0 text-xs text-yellow-600 font-semibold" style="font-size:.72rem;">
            ⏳ Devolución en revisión
        </span>
        @endif
    </div>

    {{-- Sub-sección: devoluciones de esta solicitud --}}
    @if($devsSolicitud->isNotEmpty())
    <div class="dev-row px-5 py-3">
        <p class="text-xs font-bold text-gray-400 mb-2 uppercase tracking-wide">Solicitudes de devolución</p>
        <div class="space-y-1.5">
            @foreach($devsSolicitud as $dev)
            @php
                $devDocN   = 'DEV-' . str_pad($dev->id, 6, '0', STR_PAD_LEFT);
                $devBadge  = match($dev->estado) {
                    'pendiente' => 'dev-pendiente',
                    'aprobada'  => 'dev-aprobada',
                    'rechazada' => 'dev-rechazada',
                    default     => 'dev-pendiente',
                };
                $devLabel  = match($dev->estado) {
                    'pendiente' => '⏳ Pendiente',
                    'aprobada'  => '✓ Aprobada',
                    'rechazada' => '✗ Rechazada',
                    default     => $dev->estado,
                };
            @endphp
            <div class="flex items-center gap-3 flex-wrap text-xs text-gray-500">
                <span class="font-bold text-gray-700" style="min-width:90px;">{{ $devDocN }}</span>
                <span>{{ $dev->cantidad }} unidad(es)</span>
                <span class="dev-badge {{ $devBadge }}">{{ $devLabel }}</span>
                <span class="text-gray-400">{{ $dev->created_at->format('d/m/Y H:i') }}</span>
                @if($dev->estado === 'rechazada' && $dev->motivo_rechazo)
                    <span class="text-red-500">· {{ Str::limit($dev->motivo_rechazo, 60) }}</span>
                @endif
                @if($dev->estado === 'aprobada')
                    <span class="text-green-600 font-semibold">· Stock restituido</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@empty
<div class="msol-card px-6 py-12 text-center">
    <svg class="mx-auto mb-3 text-gray-300" style="width:2.5rem;height:2.5rem;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <p class="text-gray-400 font-medium">No has realizado solicitudes todavía.</p>
    <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline text-sm mt-1 inline-block">Ir a productos →</a>
</div>
@endforelse

{{-- ═══ MODAL: Solicitar devolución ═══════════════════════════════════════ --}}
<div id="modalSolDev" style="display:none;">
    <div class="modal-card">
        {{-- Header --}}
        <div style="display:flex; align-items:center; gap:.6rem; margin-bottom:.2rem;">
            <svg style="width:1.1rem;height:1.1rem;color:#6366f1;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            <h2 style="font-size:1rem;font-weight:700;margin:0;color:#1f2937;" class="text-gray-800">Devolver materiales</h2>
            <button type="button" onclick="cerrarModalDev()" style="margin-left:auto;color:#9ca3af;background:none;border:none;cursor:pointer;padding:.2rem;line-height:1;">
                <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Info solicitud --}}
        <div id="dev-sol-ref" style="font-size:.78rem;color:#6b7280;margin-bottom:1rem;padding-left:1.7rem;"></div>

        {{-- Resumen stock --}}
        <div style="background:#f8fafc;border-radius:.6rem;padding:.85rem 1rem;margin-bottom:1.25rem;display:flex;gap:1.5rem;flex-wrap:wrap;">
            <div class="text-center">
                <p style="font-size:.7rem;color:#6b7280;margin:0 0 .2rem;">Entregado</p>
                <p id="dev-entregado" style="font-size:1.1rem;font-weight:800;color:#1f2937;margin:0;">0</p>
            </div>
            <div class="text-center">
                <p style="font-size:.7rem;color:#6b7280;margin:0 0 .2rem;">Ya devuelto</p>
                <p id="dev-ya-devuelto" style="font-size:1.1rem;font-weight:800;color:#16a34a;margin:0;">0</p>
            </div>
            <div class="text-center">
                <p style="font-size:.7rem;color:#6b7280;margin:0 0 .2rem;">En revisión</p>
                <p id="dev-en-revision" style="font-size:1.1rem;font-weight:800;color:#d97706;margin:0;">0</p>
            </div>
            <div class="text-center">
                <p style="font-size:.7rem;color:#6b7280;margin:0 0 .2rem;">Disponible</p>
                <p id="dev-disponible" style="font-size:1.1rem;font-weight:800;color:#4f46e5;margin:0;">0</p>
            </div>
        </div>

        <form id="formSolDev" method="POST" action="">
            @csrf

            {{-- Cantidad --}}
            <div style="margin-bottom:1rem;">
                <label class="mf-label">
                    Cantidad a devolver <span style="color:#ef4444;">*</span>
                    <span style="font-weight:400;color:#6b7280;">(máx: <span id="dev-max-label">0</span>)</span>
                </label>
                <input type="number" name="cantidad_devolucion" id="dev-cantidad-inp"
                       min="1" required class="mf-input"
                       style="max-width:140px;">
            </div>

            {{-- Motivo --}}
            <div style="margin-bottom:.75rem;">
                <label class="mf-label">Motivo de la devolución <span style="color:#ef4444;">*</span></label>
                <select id="dev-motivo-select" class="mf-input" style="margin-bottom:.5rem;" onchange="aplicarMotivoSel(this.value)">
                    <option value="">— Selecciona un motivo —</option>
                    <option value="Material sobrante tras finalizar la tarea">Material sobrante</option>
                    <option value="Material no utilizado">No utilizado</option>
                    <option value="Error en la solicitud (cantidad excesiva)">Error en solicitud</option>
                    <option value="Excedente de trabajo finalizado">Excedente de trabajo</option>
                    <option value="Restitución de inventario">Restitución inventario</option>
                    <option value="otro">Otro (escribir manualmente)...</option>
                </select>
                <textarea name="motivo_devolucion" id="dev-motivo-inp" rows="2" required
                          minlength="5" maxlength="500"
                          placeholder="Detalle del motivo de devolución..."
                          class="mf-input" style="resize:vertical;"></textarea>
            </div>

            {{-- Aviso aprobación --}}
            <p style="font-size:.72rem;color:#6b7280;background:#f8fafc;border-radius:.4rem;padding:.5rem .7rem;margin-bottom:1.25rem;border-left:3px solid #6366f1;">
                Tu solicitud quedará <strong>pendiente de aprobación</strong> por un administrador. El stock se actualizará únicamente al ser aprobada.
            </p>

            {{-- Footer --}}
            <div style="display:flex;gap:.65rem;justify-content:flex-end;border-top:1px solid #f3f4f6;padding-top:1rem;">
                <button type="button" onclick="cerrarModalDev()"
                        style="padding:.45rem 1rem;font-size:.82rem;font-weight:600;color:#6b7280;background:#f3f4f6;border:none;border-radius:.5rem;cursor:pointer;"
                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:.45rem 1.25rem;font-size:.82rem;font-weight:700;color:#fff;background:#6366f1;border:none;border-radius:.5rem;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;"
                        onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                    <svg style="width:.85rem;height:.85rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Enviar solicitud
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function abrirModalDev(solId, nombre, solRef, cantidad, yaDevuelto, enRevision, disponible) {
    document.getElementById('dev-sol-ref').textContent       = solRef + ' — ' + nombre;
    document.getElementById('dev-entregado').textContent     = cantidad;
    document.getElementById('dev-ya-devuelto').textContent   = yaDevuelto;
    document.getElementById('dev-en-revision').textContent   = enRevision;
    document.getElementById('dev-disponible').textContent    = disponible;
    document.getElementById('dev-max-label').textContent     = disponible;

    const inp = document.getElementById('dev-cantidad-inp');
    inp.max   = disponible;
    inp.value = disponible === 1 ? 1 : '';

    document.getElementById('dev-motivo-select').value = '';
    document.getElementById('dev-motivo-inp').value    = '';

    document.getElementById('formSolDev').action =
        '/solicitudes/' + solId + '/solicitar-devolucion';

    const modal = document.getElementById('modalSolDev');
    modal.style.display = 'flex';
    setTimeout(() => inp.focus(), 80);
}

function cerrarModalDev() {
    document.getElementById('modalSolDev').style.display = 'none';
}

function aplicarMotivoSel(val) {
    const ta = document.getElementById('dev-motivo-inp');
    if (val && val !== 'otro') {
        ta.value = val;
    } else if (val === 'otro') {
        ta.value = '';
        ta.focus();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalDev();
});
</script>
@endpush
