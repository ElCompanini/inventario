@extends('layouts.app')

@section('title', 'Solicitudes Pendientes')

@section('content')

<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Solicitudes Pendientes</h1>
        <p class="text-sm text-gray-500 mt-1" id="contador-sol">
            {{ $solicitudes->count() }} solicitud(es) esperando revisión
        </p>
    </div>
    @if($solicitudes->isNotEmpty())
        <div class="relative w-72 shrink-0">
            <input type="text" id="buscador-solicitudes" placeholder="Buscar por producto, motivo, solicitante..."
                   class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
        </div>
    @endif
</div>

@if($solicitudes->isEmpty())
    <div class="bg-white rounded-xl shadow p-12 text-center">
        <div class="text-5xl mb-4">✅</div>
        <p class="text-gray-500 font-medium">No hay solicitudes pendientes.</p>
    </div>
@else
    <div class="space-y-4" id="lista-solicitudes">
        @foreach($solicitudes as $solicitud)
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
                 data-buscar="{{ strtolower($solicitud->producto->nombre . ' ' . $solicitud->motivo . ' ' . $solicitud->usuario->name . ' ' . $solicitud->producto->container->nombre) }}">
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

                {{-- Botones aprobar / rechazar --}}
                <div class="px-6 py-3 bg-gray-50 border-t flex items-center gap-3">
                    @if(!$stockInsuficiente)
                        <form method="POST" action="{{ route('admin.solicitudes.aprobar', $solicitud->id) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700
                                           text-white text-sm font-semibold px-4 py-2 rounded-lg transition"
                                    onclick="return confirm('¿Aprobar esta solicitud?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Aprobar
                            </button>
                        </form>
                    @endif

                    {{-- Botón que abre el modal de rechazo --}}
                    <button type="button"
                            onclick="abrirModalRechazo({{ $solicitud->id }}, '{{ route('admin.solicitudes.rechazar', $solicitud->id) }}')"
                            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700
                                   text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Rechazar
                    </button>

                    <span class="text-xs text-gray-400 ml-auto">#{{ $solicitud->id }}</span>
                </div>
            </div>
        @endforeach
    </div>
    <p id="sin-resultados" class="hidden text-center text-gray-400 py-10">Sin resultados para tu búsqueda.</p>
@endif

{{-- Modal de rechazo con motivo --}}
<div id="modalRechazo" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Rechazar solicitud</h2>
        <p class="text-sm text-gray-500 mb-4">Ingresa el motivo del rechazo para informar al solicitante.</p>

        <form id="formRechazo" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label for="motivo_rechazo" class="block text-sm font-medium text-gray-700 mb-1">
                    Motivo de rechazo <span class="text-red-500">*</span>
                </label>
                <textarea id="motivo_rechazo" name="motivo_rechazo" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                          placeholder="Ej: Stock insuficiente, solicitud duplicada, error en la cantidad..."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="cerrarModalRechazo()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                    Confirmar rechazo
                </button>
            </div>
        </form>
    </div>
</div>

@push('head')
<style>
    .dt-btn-excel { background:#16a34a; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn-excel:hover { background:#15803d; }
    .dt-btn { background:#2563eb; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn:hover { background:#1d4ed8; }
    .dt-btn-pdf { background:#dc2626; color:#fff; padding:0.375rem 0.75rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; transition:background .15s; }
    .dt-btn-pdf:hover { background:#b91c1c; }
    #tabla-solicitudes tbody tr { transition: background-color .2s ease; }
    #tabla-solicitudes tbody tr:hover { background-color: #f3f4f6 !important; }
</style>
@endpush

@push('scripts')
<script>
    // Buscador en tiempo real para solicitudes pendientes
    const buscadorSol = document.getElementById('buscador-solicitudes');
    if (buscadorSol) {
        buscadorSol.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.sol-card');
            let visibles = 0;
            cards.forEach(card => {
                const coincide = !q || card.dataset.buscar.includes(q);
                card.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });
            document.getElementById('sin-resultados').classList.toggle('hidden', visibles > 0);
            document.getElementById('contador-sol').textContent = visibles + ' solicitud(es) esperando revisión';
        });
    }

    function abrirModalRechazo(id) {
        const modal = document.getElementById('modalRechazo');
        const form  = document.getElementById('formRechazo');
        form.action = `/admin/solicitudes/${id}/rechazar`;
        document.getElementById('motivo_rechazo').value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalRechazo() {
        const modal = document.getElementById('modalRechazo');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Cerrar al hacer clic fuera del modal
    document.getElementById('modalRechazo').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalRechazo();
    });
</script>
@endpush

@endsection
