@extends('layouts.app')

@section('title', 'Solicitudes Pendientes')

@section('content')

@php
    $fProductos    = $solicitudes->pluck('producto.nombre')->unique()->sort()->values();
    $fSolicitantes = $solicitudes->pluck('usuario.name')->unique()->sort()->values();
@endphp

<div class="mb-4 flex items-center gap-3 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Solicitudes Pendientes</h1>
        <p class="text-sm text-gray-500 mt-1" id="contador-sol">
            {{ $solicitudes->count() }} solicitud(es) esperando revisión
        </p>
    </div>

    @if($solicitudes->isNotEmpty())
    <div class="ml-auto flex items-center gap-2">
        {{-- Botón filtros --}}
        <button type="button" id="btn-filtros-sol"
            class="relative flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium border rounded-lg shadow-sm transition bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M11 16h2"/>
            </svg>
            Filtros
            <span id="badge-sol" class="hidden absolute -top-1.5 -right-1.5 w-2.5 h-2.5 bg-indigo-600 rounded-full border-2 border-white"></span>
        </button>

        {{-- Buscador --}}
        <input type="text" id="buscador-solicitudes" placeholder="🔍  Buscar..."
               class="px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm bg-white
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-56">
    </div>
    @endif
</div>

{{-- Panel de filtros (flujo normal, debajo del header) --}}
@if($solicitudes->isNotEmpty())
<div id="panel-filtros-sol" class="hidden mb-4">
    <div class="bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">

        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50">
            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Filtros</span>
            <button type="button" id="btn-limpiar-filtros-sol" class="text-xs text-indigo-600 hover:underline font-medium">
                Limpiar todo
            </button>
        </div>

        <div class="grid grid-cols-2 gap-0 divide-x divide-gray-100 md:grid-cols-4">

            {{-- Productos --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-header w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2" data-target="acc-productos">
                    <span>Producto</span>
                    <svg class="acc-chevron w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="acc-productos" class="acc-body space-y-1 max-h-44 overflow-y-auto pr-1">
                    @foreach($fProductos as $p)
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-producto w-3.5 h-3.5 accent-indigo-600 shrink-0" value="{{ strtolower($p) }}">
                        <span class="text-xs text-gray-700 leading-tight">{{ $p }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Solicitante --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-header w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2" data-target="acc-solicitante">
                    <span>Solicitante</span>
                    <svg class="acc-chevron w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="acc-solicitante" class="acc-body space-y-1 max-h-44 overflow-y-auto pr-1">
                    @foreach($fSolicitantes as $s)
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-solicitante w-3.5 h-3.5 accent-indigo-600 shrink-0" value="{{ strtolower($s) }}">
                        <span class="text-xs text-gray-700 leading-tight">{{ $s }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Contenedor --}}
            <div class="px-4 py-3">
                <button type="button" class="acc-header w-full flex items-center justify-between text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2" data-target="acc-contenedor">
                    <span>Contenedor</span>
                    <svg class="acc-chevron w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="acc-contenedor" class="acc-body space-y-1 max-h-44 overflow-y-auto pr-1">
                    @foreach($containers as $c)
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-indigo-50 px-1.5 py-1 rounded-md transition">
                        <input type="checkbox" class="fil-contenedor w-3.5 h-3.5 accent-indigo-600 shrink-0" value="{{ strtolower($c->nombre) }}">
                        <span class="text-xs text-gray-700 leading-tight">{{ $c->nombre }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Fecha --}}
            <div class="px-4 py-3">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Fecha</p>
                <div class="space-y-2">
                    <div>
                        <label class="text-xs text-gray-400 block mb-0.5">Desde</label>
                        <input type="date" id="fil-fecha-desde"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 block mb-0.5">Hasta</label>
                        <input type="date" id="fil-fecha-hasta"
                            class="w-full text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endif

@if($solicitudes->isEmpty())
    <div class="bg-white rounded-xl shadow p-12 text-center">
        <div class="text-5xl mb-4">✅</div>
        <p class="text-gray-500 font-medium">No hay solicitudes pendientes.</p>
    </div>
@else
    <div class="space-y-4" id="lista-solicitudes">
        @foreach($solicitudes as $solicitud)
            @if(!$solicitud->producto) @continue @endif
            @php
                $esEntrada = $solicitud->tipo === 'entrada';
                $stockActual = $solicitud->producto->stock_actual;
                $stockTras = $esEntrada
                    ? $stockActual + $solicitud->cantidad
                    : $stockActual - $solicitud->cantidad;
                $stockInsuficiente = !$esEntrada && $stockActual < $solicitud->cantidad;
            @endphp

            <div class="bg-white rounded-xl shadow overflow-hidden border-l-4 sol-card
                {{ $esEntrada ? 'border-green-500' : 'border-orange-500' }}"
                 data-buscar="{{ strtolower($solicitud->producto->nombre . ' ' . $solicitud->motivo . ' ' . $solicitud->usuario->name . ' ' . ($solicitud->producto->container->nombre ?? '')) }}"
                 data-producto="{{ strtolower($solicitud->producto->nombre) }}"
                 data-solicitante="{{ strtolower($solicitud->usuario->name) }}"
                 data-contenedor="{{ strtolower($solicitud->producto->container->nombre ?? '') }}"
                 data-fecha="{{ $solicitud->created_at->format('Y-m-d') }}">
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        {{-- Info solicitud --}}
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-lg font-bold" style="color: {{ $esEntrada ? '#15803d' : '#ea580c' }}">
                                    {{ $solicitud->producto->nombre }}
                                </span>
                                @if($esEntrada)
                                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                        ↑ ENTRADA +{{ $solicitud->cantidad }}
                                    </span>
                                @else
                                    <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                        ↓ SALIDA −{{ $solicitud->cantidad }}
                                    </span>
                                @endif
                                @if($stockInsuficiente)
                                    <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                        ⚠ Stock insuficiente
                                    </span>
                                @endif
                            </div>

                            <p class="text-base text-gray-700 mb-3">
                                <span class="font-semibold text-gray-800">Motivo:</span>
                                <span style="background:{{ $esEntrada ? '#dcfce7' : '#ffedd5' }}; color:{{ $esEntrada ? '#15803d' : '#c2410c' }}; border-radius:0.5rem; padding:2px 10px;">{{ $solicitud->motivo }}</span>
                            </p>

                            <div class="flex items-center gap-6 text-base text-gray-600">
                                <span>
                                    <span class="font-semibold text-gray-800">Solicitante:</span>
                                    {{ $solicitud->usuario->name }}
                                </span>
                                <span>
                                    <span class="font-semibold text-gray-800">Fecha:</span>
                                    {{ $solicitud->created_at->format('d/m/Y H:i') }}
                                </span>
                                <span>
                                    <span class="font-semibold text-gray-800">Contenedor:</span>
                                    {{ $solicitud->producto->container->nombre ?? '—' }}
                                </span>
                            </div>
                        </div>

                        {{-- Previsualización de stock --}}
                        <div class="flex-shrink-0 text-center bg-gray-50 rounded-xl px-5 py-3 min-w-[140px]">
                            <p class="text-xs text-gray-500 mb-1">Stock actual</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $stockActual }}</p>
                            <div class="my-1 text-gray-400 text-lg">↓</div>
                            <p class="text-xs text-gray-500 mb-1">Tras aprobar</p>
                            <p class="text-2xl font-bold {{ $stockTras < 0 ? 'text-red-600' : ($stockTras <= $solicitud->producto->stock_critico ? 'text-red-500' : ($stockTras <= $solicitud->producto->stock_minimo ? 'text-yellow-600' : 'text-green-600')) }}">
                                {{ $stockTras }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Botones aprobar / rechazar (solo admin) --}}
                <div class="px-6 py-3 bg-gray-50 border-t flex items-center gap-3">
                    @if(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'))
                        @if(!$stockInsuficiente)
                            <button type="button"
                                    onclick="abrirModalAprobacion({{ $solicitud->id }}, '{{ route('admin.solicitudes.aprobar', $solicitud->id) }}')"
                                    class="btn-aprobar btn-primary inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
                                           text-white text-sm font-semibold px-4 py-2 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Aprobar
                            </button>
                        @endif

                        <button type="button"
                                onclick="abrirModalRechazo({{ $solicitud->id }}, '{{ route('admin.solicitudes.rechazar', $solicitud->id) }}')"
                                class="btn-rechazar btn-danger inline-flex items-center gap-2 bg-red-600 hover:bg-red-700
                                       text-white text-sm font-semibold px-4 py-2 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Rechazar
                        </button>
                    @else
                        <span class="text-xs text-gray-400 italic">Solo lectura — sin permisos para aprobar o rechazar</span>
                    @endif

                    <span class="text-xs text-gray-400 ml-auto">#{{ $solicitud->id }}</span>
                </div>
            </div>
        @endforeach
    </div>
    <p id="sin-resultados" class="hidden text-center text-gray-400 py-10">Sin resultados para tu búsqueda.</p>
@endif

{{-- ══ SECCIÓN: Solicitudes de Devolución de Usuarios — Pendientes ═══════ --}}
@if(isset($solicitudesDevolucionPendientes) && $solicitudesDevolucionPendientes->isNotEmpty())
<div class="mt-10">
    <div class="flex items-center gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-700">Solicitudes de Devolución</h2>
        <span style="font-size:.72rem; font-weight:700; background:#fef3c7; color:#92400e; padding:2px 10px; border-radius:9999px;">
            {{ $solicitudesDevolucionPendientes->count() }} pendiente(s)
        </span>
    </div>
    <p class="text-sm text-gray-400 mb-4 -mt-2">Solicitudes de devolución enviadas por usuarios. Aprueba para que el stock se reintegre, o rechaza con un motivo.</p>

    <div class="space-y-3">
        @foreach($solicitudesDevolucionPendientes as $dev)
        @if(!$dev->producto) @continue @endif
        @php
            $devDoc = 'DEV-' . str_pad($dev->id, 6, '0', STR_PAD_LEFT);
            $solDoc = 'SOL-' . str_pad($dev->solicitud_id, 6, '0', STR_PAD_LEFT);
        @endphp

        <div class="bg-white rounded-xl shadow overflow-hidden border-l-4 border-violet-400">
            <div class="px-6 py-4 flex items-center justify-between gap-4 flex-wrap">

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="font-bold text-gray-800 truncate">{{ $dev->producto->nombre }}</span>
                        <span style="font-size:.68rem; font-weight:700; background:#ede9fe; color:#6d28d9; padding:2px 8px; border-radius:9999px;">
                            Devolución pendiente
                        </span>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-500 flex-wrap">
                        <span>Solicitante: <strong class="text-gray-700">{{ $dev->usuario->name ?? '—' }}</strong></span>
                        <span>Solicitud original: <strong class="text-indigo-600">{{ $solDoc }}</strong></span>
                        <span>{{ $dev->created_at->format('d/m/Y H:i') }}</span>
                        <span class="font-mono text-xs text-gray-400">{{ $devDoc }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1.5">
                        <span class="font-semibold text-gray-700">Motivo:</span> {{ $dev->motivo }}
                    </p>
                </div>

                {{-- Cantidad --}}
                <div class="text-center flex-shrink-0">
                    <p class="text-xs text-gray-400 mb-0.5">A devolver</p>
                    <p class="text-2xl font-bold text-violet-600">{{ $dev->cantidad }}</p>
                    <p class="text-xs text-gray-400">unidad(es)</p>
                </div>

                {{-- Botones --}}
                @if(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'))
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:.5rem; flex-shrink:0;">
                    {{-- Aprobar --}}
                    <form method="POST" action="{{ route('admin.devoluciones.aprobar', $dev->id) }}">
                        @csrf
                        <button type="submit"
                                style="display:inline-flex; align-items:center; gap:.4rem; padding:.5rem 1.1rem; font-size:.82rem; font-weight:700; color:#fff; background:#16a34a; border:none; border-radius:.5rem; cursor:pointer; transition:background .15s;"
                                onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Aprobar
                        </button>
                    </form>
                    {{-- Rechazar --}}
                    <button type="button"
                            onclick="abrirModalRechazoDevolucion({{ $dev->id }}, '{{ addslashes($devDoc) }}')"
                            style="display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .9rem; font-size:.78rem; font-weight:700; color:#dc2626; background:transparent; border:1.5px solid #fca5a5; border-radius:.5rem; cursor:pointer; transition:background .15s, border-color .15s;"
                            onmouseover="this.style.background='#fef2f2'; this.style.borderColor='#ef4444';"
                            onmouseout="this.style.background='transparent'; this.style.borderColor='#fca5a5';">
                        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Rechazar
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Modal rechazo de devolución de usuario --}}
<div id="modalRechazoDevolucion" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 24px 60px rgba(0,0,0,.25); width:460px; max-width:calc(100vw - 2rem); padding:1.75rem; animation:modal-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="display:flex; align-items:center; gap:.6rem; margin-bottom:.25rem;">
            <svg style="width:1.1rem;height:1.1rem;color:#dc2626;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            <h2 style="font-size:1rem; font-weight:700; color:#1f2937; margin:0;">Rechazar Solicitud de Devolución</h2>
        </div>
        <p id="rdv-doc-label" style="font-size:.82rem; color:#4b5563; margin:0 0 1.25rem; padding-left:1.7rem;"></p>
        <form id="formRechazoDevolucion" method="POST" action="">
            @csrf
            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem;">
                    Motivo del rechazo <span style="color:#ef4444;">*</span>
                </label>
                <textarea name="motivo_rechazo" rows="3" required minlength="5" maxlength="500"
                          placeholder="Ej: Ya se realizó la devolución completa, cantidad incorrecta..."
                          style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.82rem; resize:vertical; box-sizing:border-box; outline:none;"
                          onfocus="this.style.borderColor='#ef4444'; this.style.boxShadow='0 0 0 3px rgba(239,68,68,.12)'"
                          onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"></textarea>
            </div>
            <div style="display:flex; gap:.65rem; justify-content:flex-end; border-top:1px solid #f3f4f6; padding-top:1rem;">
                <button type="button" onclick="document.getElementById('modalRechazoDevolucion').style.display='none'"
                        style="padding:.45rem 1rem; font-size:.82rem; font-weight:600; color:#6b7280; background:#f3f4f6; border:none; border-radius:.5rem; cursor:pointer;"
                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:.45rem 1.25rem; font-size:.82rem; font-weight:700; color:#fff; background:#dc2626; border:none; border-radius:.5rem; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem;"
                        onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                    <svg style="width:.85rem;height:.85rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Confirmar rechazo
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══ SECCIÓN: Solicitudes Aprobadas — Devoluciones ════════════════════ --}}
@if(isset($solicitudesAprobadas) && $solicitudesAprobadas->isNotEmpty())
<div class="mt-10">
    <div class="flex items-center gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-700">Devoluciones</h2>
        <span style="font-size:.72rem; font-weight:700; background:#dbeafe; color:#1d4ed8; padding:2px 10px; border-radius:9999px;">
            {{ $solicitudesAprobadas->count() }} solicitud(es)
        </span>
    </div>
    <p class="text-sm text-gray-400 mb-4 -mt-2">Solicitudes de salida aprobadas. Abre el proceso de devolución para luego registrar unidades devueltas.</p>

    <div class="space-y-3">
        @foreach($solicitudesAprobadas as $sol)
        @if(!$sol->producto) @continue @endif
        @php
            $yaDevuelto  = (int) ($devolucionesPorSolicitud[$sol->id] ?? 0);
            $maxDevolver = $sol->cantidad - $yaDevuelto;
            $devCompleta = $maxDevolver <= 0;
            $pct         = $sol->cantidad > 0 ? min(100, round(($yaDevuelto / $sol->cantidad) * 100)) : 0;
            $enDevolucion = $sol->estado === 'en_devolucion';
            $borderColor  = $devCompleta ? 'border-gray-300' : ($enDevolucion ? 'border-blue-500' : 'border-amber-400');
        @endphp

        <div class="bg-white rounded-xl shadow overflow-hidden border-l-4 {{ $borderColor }}">
            <div class="px-6 py-4 flex items-center justify-between gap-4">

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="font-bold text-gray-800 truncate">{{ $sol->producto->nombre }}</span>
                        @if($devCompleta)
                            <span style="font-size:.68rem; font-weight:700; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:9999px;">
                                Devolución completa ✓
                            </span>
                        @elseif($enDevolucion)
                            <span style="font-size:.68rem; font-weight:700; background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:9999px;">
                                En devolución
                            </span>
                        @else
                            <span style="font-size:.68rem; font-weight:700; background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:9999px;">
                                Aprobada
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-5 text-sm text-gray-500 flex-wrap">
                        <span>Solicitante: <strong class="text-gray-700">{{ $sol->usuario->name }}</strong></span>
                        <span>Aprobada: <strong class="text-gray-700">{{ $sol->updated_at->format('d/m/Y') }}</strong></span>
                        <span>#{{ $sol->id }}</span>
                    </div>
                    {{-- Barra de progreso devolución --}}
                    <div class="mt-2 flex items-center gap-3">
                        <div style="flex:1; height:6px; background:#e5e7eb; border-radius:9999px; overflow:hidden; max-width:180px;">
                            <div style="height:100%; width:{{ $pct }}%; background:{{ $devCompleta ? '#16a34a' : '#3b82f6' }}; border-radius:9999px; transition:width .4s;"></div>
                        </div>
                        <span class="text-xs text-gray-500">
                            {{ $yaDevuelto }} / {{ $sol->cantidad }} devueltos
                        </span>
                    </div>
                </div>

                {{-- Estadísticas --}}
                <div class="hidden sm:flex items-center gap-6 text-center shrink-0">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Entregado</p>
                        <p class="text-xl font-bold text-gray-800">{{ $sol->cantidad }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Ya devuelto</p>
                        <p class="text-xl font-bold" style="color:{{ $yaDevuelto > 0 ? '#16a34a' : '#9ca3af' }};">{{ $yaDevuelto }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Devolvible</p>
                        <p class="text-xl font-bold" style="color:{{ $devCompleta ? '#9ca3af' : '#1d4ed8' }};">{{ $maxDevolver }}</p>
                    </div>
                </div>

                {{-- Botones según estado --}}
                @if(auth()->user()->esAdmin() || auth()->user()->tienePermiso('aprobar_solicitudes'))
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:.5rem; flex-shrink:0;">

                    @if($sol->estado === 'aprobado' && !$devCompleta)
                    {{-- Estado: aprobado → mostrar "Abrir solicitud" --}}
                    <form method="POST" action="{{ route('admin.solicitudes.abrir', $sol->id) }}">
                        @csrf
                        <button type="submit"
                                style="display:inline-flex; align-items:center; gap:.4rem; padding:.5rem 1.1rem; font-size:.82rem; font-weight:700; color:#fff; background:#d97706; border:none; border-radius:.5rem; cursor:pointer; transition:background .15s;"
                                onmouseover="this.style.background='#b45309'" onmouseout="this.style.background='#d97706'">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                            Abrir solicitud
                        </button>
                    </form>

                    @elseif($enDevolucion && !$devCompleta)
                    {{-- Estado: en_devolucion → "Registrar devolución" + "Cerrar solicitud" --}}
                    <button type="button"
                            onclick="abrirModalDevolucion({{ $sol->id }}, '{{ addslashes($sol->producto->nombre) }}', {{ $sol->cantidad }}, {{ $yaDevuelto }}, {{ $maxDevolver }})"
                            style="display:inline-flex; align-items:center; gap:.4rem; padding:.5rem 1.1rem; font-size:.82rem; font-weight:700; color:#fff; background:#2563eb; border:none; border-radius:.5rem; cursor:pointer; transition:background .15s;"
                            onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Registrar devolución
                    </button>
                    <form method="POST" action="{{ route('admin.solicitudes.cerrar', $sol->id) }}"
                          onsubmit="return confirm('¿Cerrar la solicitud #{{ $sol->id }}? No se podrán registrar más devoluciones.')">
                        @csrf
                        <button type="submit"
                                style="display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .9rem; font-size:.78rem; font-weight:700; color:#dc2626; background:transparent; border:1.5px solid #fca5a5; border-radius:.5rem; cursor:pointer; transition:background .15s, border-color .15s;"
                                onmouseover="this.style.background='#fef2f2'; this.style.borderColor='#ef4444';"
                                onmouseout="this.style.background='transparent'; this.style.borderColor='#fca5a5';">
                            <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM16 7V5a4 4 0 00-8 0v2"/>
                            </svg>
                            Cerrar solicitud
                        </button>
                    </form>

                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Modal: registrar devolución --}}
<div id="modalDevolucion" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.55); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:1rem; box-shadow:0 24px 60px rgba(0,0,0,.25); width:480px; max-width:calc(100vw - 2rem); padding:1.75rem; animation:modal-in .25s cubic-bezier(.22,.68,0,1.2) both;">
        <div style="display:flex; align-items:center; gap:.6rem; margin-bottom:.25rem;">
            <svg style="width:1.1rem; height:1.1rem; color:#2563eb;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            <h2 style="font-size:1rem; font-weight:700; color:#1f2937; margin:0;">Registrar Devolución</h2>
        </div>
        <p id="dev-producto-nombre" style="font-size:.82rem; color:#4b5563; margin:0 0 1.25rem; padding-left:1.7rem;"></p>

        <form id="formDevolucion" method="POST" action="">
            @csrf
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem;">
                    Cantidad a devolver <span style="color:#ef4444;">*</span>
                    <span style="font-weight:400; color:#6b7280;">(máx: <span id="dev-max-label">0</span>)</span>
                </label>
                <input type="number" name="cantidad_devolucion" id="dev-cantidad"
                       min="1" required
                       style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.9rem; box-sizing:border-box; outline:none;"
                       onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,.15)'"
                       onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                <p style="font-size:.72rem; color:#6b7280; margin:.3rem 0 0;">
                    Entregado: <strong id="dev-entregado">0</strong> ud. &nbsp;·&nbsp; Ya devuelto: <strong id="dev-ya-devuelto">0</strong> ud.
                </p>
            </div>
            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem;">
                    Motivo de la devolución <span style="color:#ef4444;">*</span>
                </label>
                <textarea name="motivo_devolucion" id="dev-motivo" rows="3" required minlength="5" maxlength="500"
                          placeholder="Ej: Materiales sobrantes tras finalizar la tarea..."
                          style="width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.82rem; resize:vertical; box-sizing:border-box; outline:none;"
                          onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,.15)'"
                          onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"></textarea>
            </div>
            <div style="display:flex; gap:.65rem; justify-content:flex-end; border-top:1px solid #f3f4f6; padding-top:1rem;">
                <button type="button" onclick="cerrarModalDevolucion()"
                        style="padding:.45rem 1rem; font-size:.82rem; font-weight:600; color:#6b7280; background:#f3f4f6; border:none; border-radius:.5rem; cursor:pointer;"
                        onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    Cancelar
                </button>
                <button type="submit"
                        style="padding:.45rem 1.25rem; font-size:.82rem; font-weight:700; color:#fff; background:#2563eb; border:none; border-radius:.5rem; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem;"
                        onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                    <svg style="width:.85rem; height:.85rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Confirmar devolución
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal de rechazo con motivo --}}
<div id="modalRechazo" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Rechazar solicitud</h2>
        <p class="text-sm text-gray-500 mb-4">Ingresa el motivo del rechazo para informar al solicitante.</p>

        <form id="formRechazo" method="POST" action="" onsubmit="return validarRechazo()">
            @csrf
            <div class="mb-4">
                <label for="motivo_rechazo" class="block text-sm font-medium text-gray-700 mb-1">
                    Motivo de rechazo <span class="text-red-500">*</span>
                </label>
                <textarea id="motivo_rechazo" name="motivo_rechazo" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                          placeholder="Ej: Stock insuficiente, solicitud duplicada, error en la cantidad..."
                          oninput="limpiarErrorRechazo()"></textarea>
                <p id="error-motivo-rechazo" class="hidden mt-1.5 text-sm font-medium text-red-600 flex items-center gap-1">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    Debes ingresar el motivo del rechazo antes de continuar.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalRechazo()"
                        class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                    Cancelar
                </button>
                <button type="submit"
                        class="btn-danger px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">
                    Confirmar rechazo
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal de aprobación --}}
<div id="modalAprobacion" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Aprobar solicitud</h2>
        <p class="text-sm text-gray-500 mb-6">¿Confirmas que deseas aprobar esta solicitud? El stock se actualizará de inmediato.</p>

        <form id="formAprobacion" method="POST" action="">
            @csrf
            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalAprobacion()"
                        class="btn-secondary px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">
                    Cancelar
                </button>
                <button type="submit"
                        class="btn-primary px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    Confirmar aprobación
                </button>
            </div>
        </form>
    </div>
</div>

@push('head')
<style>
    /* ── Entrada de cards ── */
    @keyframes sol-fade-up {
        from { opacity:0; transform:translateY(18px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .sol-card {
        animation: sol-fade-up .35s cubic-bezier(.22,.68,0,1.15) both;
    }
    .sol-card:nth-child(1)  { animation-delay:.04s }
    .sol-card:nth-child(2)  { animation-delay:.08s }
    .sol-card:nth-child(3)  { animation-delay:.12s }
    .sol-card:nth-child(4)  { animation-delay:.16s }
    .sol-card:nth-child(5)  { animation-delay:.20s }
    .sol-card:nth-child(6)  { animation-delay:.24s }
    .sol-card:nth-child(7)  { animation-delay:.28s }
    .sol-card:nth-child(8)  { animation-delay:.32s }
    .sol-card:nth-child(9)  { animation-delay:.36s }
    .sol-card:nth-child(10) { animation-delay:.40s }

    /* ── Hover card ── */
    .sol-card { transition: box-shadow .2s, transform .2s; }
    .sol-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.10); transform: translateY(-2px); }

    /* ── Panel de filtros dropdown ── */
    @keyframes panel-drop {
        from { opacity:0; transform:translateY(-8px) scale(.97); }
        to   { opacity:1; transform:translateY(0)   scale(1); }
    }
    #panel-filtros-sol:not(.hidden) {
        animation: panel-drop .2s cubic-bezier(.22,.68,0,1.2) both;
    }

    /* ── Acordeón body ── */
    @keyframes acc-open {
        from { opacity:0; transform:translateY(-4px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .acc-body:not(.hidden) {
        animation: acc-open .18s ease both;
    }

    /* ── Badge pulso ── */
    @keyframes badge-pulse {
        0%,100% { box-shadow:0 0 0 0 rgba(79,70,229,.6); }
        50%      { box-shadow:0 0 0 5px rgba(79,70,229,0); }
    }
    #badge-sol:not(.hidden) {
        animation: badge-pulse 1.8s ease-in-out infinite;
    }

    /* ── Modales ── */
    @keyframes modal-in {
        from { opacity:0; transform:scale(.94); }
        to   { opacity:1; transform:scale(1); }
    }
    #modalRechazo > div,
    #modalAprobacion > div {
        animation: modal-in .25s cubic-bezier(.22,.68,0,1.2) both;
    }

    /* ── Botones aprobar/rechazar ── */
    .btn-aprobar  { transition: background .15s, transform .15s, box-shadow .15s; }
    .btn-aprobar:hover  { transform:translateY(-1px); box-shadow:0 4px 12px rgba(22,163,74,.35); }
    .btn-rechazar { transition: background .15s, transform .15s, box-shadow .15s; }
    .btn-rechazar:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(220,38,38,.35); }

    /* ── Chevron ── */
    .acc-chevron { transition: transform .2s ease; }

    /* ── Label activo cuando su checkbox está marcado ── */
    label:has(.fil-producto:checked),
    label:has(.fil-solicitante:checked),
    label:has(.fil-contenedor:checked) {
        background: #eef2ff !important;
        outline: 1px solid #c7d2fe;
        border-radius: 0.375rem;
    }
    label:has(.fil-producto:checked) span,
    label:has(.fil-solicitante:checked) span,
    label:has(.fil-contenedor:checked) span {
        color: #4338ca !important;
        font-weight: 600;
    }

    /* ── Cabecera de acordeón resaltada si tiene filtros activos ── */
    .acc-header.has-active > span:first-child {
        color: #4338ca;
    }
    .acc-header.has-active .acc-chevron {
        color: #4338ca;
    }
</style>
@endpush

@push('scripts')
<script>
    // ── Filtro de solicitudes pendientes ─────────────────────────────────
    var filProductos    = new Set();
    var filSolicitantes = new Set();
    var filContenedores = new Set();
    var filDesde = null, filHasta = null;

    function aplicarFiltrosSol() {
        var q = (document.getElementById('buscador-solicitudes')?.value || '').toLowerCase().trim();
        var cards = document.querySelectorAll('.sol-card');
        var visibles = 0;

        cards.forEach(function(card) {
            var ok = true;
            if (q && !card.dataset.buscar.includes(q)) ok = false;
            if (filProductos.size    && !filProductos.has(card.dataset.producto))    ok = false;
            if (filSolicitantes.size && !filSolicitantes.has(card.dataset.solicitante)) ok = false;
            if (filContenedores.size && !filContenedores.has(card.dataset.contenedor))  ok = false;
            if (filDesde || filHasta) {
                var fMs = new Date(card.dataset.fecha + 'T00:00:00').getTime();
                if (filDesde && fMs < filDesde) ok = false;
                if (filHasta && fMs > filHasta) ok = false;
            }
            card.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });

        document.getElementById('sin-resultados').classList.toggle('hidden', visibles > 0);
        document.getElementById('contador-sol').textContent = visibles + ' solicitud(es) esperando revisión';

        // Badge activo
        var hayFiltro = filProductos.size || filSolicitantes.size || filContenedores.size || filDesde || filHasta;
        var badge = document.getElementById('badge-sol');
        if (badge) badge.classList.toggle('hidden', !hayFiltro);

        // Resaltar encabezados de acordeón con filtros activos
        var btnProductos   = document.querySelector('[data-target="acc-productos"]');
        var btnSolicitante = document.querySelector('[data-target="acc-solicitante"]');
        var btnContenedor  = document.querySelector('[data-target="acc-contenedor"]');
        if (btnProductos)   btnProductos.classList.toggle('has-active',   filProductos.size > 0);
        if (btnSolicitante) btnSolicitante.classList.toggle('has-active', filSolicitantes.size > 0);
        if (btnContenedor)  btnContenedor.classList.toggle('has-active',  filContenedores.size > 0);
        // Fecha no tiene acc-header propio, no aplica
    }

    // Buscador
    var buscadorSol = document.getElementById('buscador-solicitudes');
    if (buscadorSol) buscadorSol.addEventListener('input', aplicarFiltrosSol);

    // Checkboxes
    document.querySelectorAll('.fil-producto').forEach(function(cb) {
        cb.addEventListener('change', function() {
            this.checked ? filProductos.add(this.value) : filProductos.delete(this.value);
            aplicarFiltrosSol();
        });
    });
    document.querySelectorAll('.fil-solicitante').forEach(function(cb) {
        cb.addEventListener('change', function() {
            this.checked ? filSolicitantes.add(this.value) : filSolicitantes.delete(this.value);
            aplicarFiltrosSol();
        });
    });
    document.querySelectorAll('.fil-contenedor').forEach(function(cb) {
        cb.addEventListener('change', function() {
            this.checked ? filContenedores.add(this.value) : filContenedores.delete(this.value);
            aplicarFiltrosSol();
        });
    });

    // Fecha
    document.getElementById('fil-fecha-desde')?.addEventListener('change', function() {
        filDesde = this.value ? new Date(this.value + 'T00:00:00').getTime() : null;
        aplicarFiltrosSol();
    });
    document.getElementById('fil-fecha-hasta')?.addEventListener('change', function() {
        filHasta = this.value ? new Date(this.value + 'T23:59:59').getTime() : null;
        aplicarFiltrosSol();
    });

    // Toggle panel
    var btnFiltros = document.getElementById('btn-filtros-sol');
    var panelFiltros = document.getElementById('panel-filtros-sol');
    if (btnFiltros) {
        btnFiltros.addEventListener('click', function(e) {
            e.stopPropagation();
            panelFiltros.classList.toggle('hidden');
        });
        document.addEventListener('click', function(e) {
            if (!panelFiltros.contains(e.target) && e.target !== btnFiltros) {
                panelFiltros.classList.add('hidden');
            }
        });
    }

    // Acordeón
    document.querySelectorAll('.acc-header').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var body = document.getElementById(this.dataset.target);
            var chevron = this.querySelector('.acc-chevron');
            body.classList.toggle('hidden');
            chevron.style.transform = body.classList.contains('hidden') ? '' : 'rotate(180deg)';
        });
    });

    // Limpiar filtros
    document.getElementById('btn-limpiar-filtros-sol')?.addEventListener('click', function() {
        filProductos.clear(); filSolicitantes.clear(); filContenedores.clear();
        filDesde = null; filHasta = null;
        document.querySelectorAll('.fil-producto,.fil-solicitante,.fil-contenedor').forEach(function(cb) { cb.checked = false; });
        var d = document.getElementById('fil-fecha-desde'), h = document.getElementById('fil-fecha-hasta');
        if (d) d.value = ''; if (h) h.value = '';
        if (buscadorSol) buscadorSol.value = '';
        aplicarFiltrosSol();
    });

    function abrirModalAprobacion(id, url) {
        const modal = document.getElementById('modalAprobacion');
        document.getElementById('formAprobacion').action = url;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalAprobacion() {
        const modal = document.getElementById('modalAprobacion');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('modalAprobacion').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalAprobacion();
    });

    function abrirModalRechazo(id, url) {
        const modal = document.getElementById('modalRechazo');
        const form  = document.getElementById('formRechazo');
        form.action = url || `/admin/solicitudes/${id}/rechazar`;
        document.getElementById('motivo_rechazo').value = '';
        limpiarErrorRechazo();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(function() { document.getElementById('motivo_rechazo').focus(); }, 50);
    }

    function cerrarModalRechazo() {
        const modal = document.getElementById('modalRechazo');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        limpiarErrorRechazo();
    }

    function validarRechazo() {
        const motivo = document.getElementById('motivo_rechazo').value.trim();
        if (!motivo) {
            const textarea = document.getElementById('motivo_rechazo');
            const error    = document.getElementById('error-motivo-rechazo');
            textarea.classList.add('border-red-500', 'ring-1', 'ring-red-400');
            error.classList.remove('hidden');
            error.classList.add('flex');
            textarea.focus();
            return false;
        }
        return true;
    }

    function limpiarErrorRechazo() {
        const textarea = document.getElementById('motivo_rechazo');
        const error    = document.getElementById('error-motivo-rechazo');
        textarea.classList.remove('border-red-500', 'ring-1', 'ring-red-400');
        error.classList.add('hidden');
        error.classList.remove('flex');
    }

    // Cerrar al hacer clic fuera del modal
    document.getElementById('modalRechazo').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalRechazo();
    });

    // ── Modal de devolución ───────────────────────────────────────────────
    function abrirModalDevolucion(id, nombre, cantidad, yaDevuelto, maxDevolver) {
        document.getElementById('dev-producto-nombre').textContent = nombre;
        document.getElementById('dev-entregado').textContent = cantidad;
        document.getElementById('dev-ya-devuelto').textContent = yaDevuelto;
        document.getElementById('dev-max-label').textContent = maxDevolver;
        var inp = document.getElementById('dev-cantidad');
        inp.value = '';
        inp.max = maxDevolver;
        document.getElementById('dev-motivo').value = '';
        document.getElementById('formDevolucion').action = '/admin/solicitudes/' + id + '/devolucion';
        var m = document.getElementById('modalDevolucion');
        m.style.display = 'flex';
        setTimeout(function() { inp.focus(); }, 50);
    }

    function cerrarModalDevolucion() {
        document.getElementById('modalDevolucion').style.display = 'none';
    }

    document.getElementById('modalDevolucion').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalDevolucion();
    });

    // ── Modal rechazo de devolución de usuario ────────────────────────────
    function abrirModalRechazoDevolucion(devId, devDoc) {
        document.getElementById('rdv-doc-label').textContent = devDoc;
        document.getElementById('formRechazoDevolucion').action = '/admin/devoluciones/' + devId + '/rechazar';
        var m = document.getElementById('modalRechazoDevolucion');
        m.style.display = 'flex';
    }

    document.getElementById('modalRechazoDevolucion').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('modalRechazoDevolucion').style.display = 'none';
        }
    });
</script>
@endpush

@endsection
