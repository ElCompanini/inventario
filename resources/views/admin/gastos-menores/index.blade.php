@extends('layouts.app')
@section('title', 'Gastos Menores')

@section('content')

<div class="mb-5 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Gastos Menores</h1>
        <p class="text-sm text-gray-500 mt-1">Registro de compras de gasto menor con sus boletas y productos asociados</p>
    </div>
    <button type="button" id="btn-abrir-gasto-menor"
        style="background:#d97706; color:#fff; font-size:0.82rem; font-weight:600; padding:0.5rem 1.1rem; border-radius:0.5rem; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:0.4rem; transition:background .15s; white-space:nowrap;"
        onmouseover="this.style.background='#b45309'" onmouseout="this.style.background='#d97706'">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Nueva Compra
    </button>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm">
    {{ session('success') }}
</div>
@endif

@php
function formatearRut(string $rut): string {
$limpio = strtoupper(preg_replace('/[^0-9kK]/', '', $rut));
if (strlen($limpio) < 2) return $rut;
    $dv=substr($limpio, -1);
    $num=substr($limpio, 0, -1);
    return number_format((int) $num, 0, ',' , '.' ) . '-' . $dv;
    }
    @endphp

    {{-- Buscador --}}
    <div class="mb-2">
        <input type="text" id="buscador-gm"
            placeholder="🔍  Buscar por ID, folio, RUT proveedor o producto..."
            class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg shadow-sm
                      focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
    </div>

    <div id="gm-filtro-chip" class="hidden mb-4 flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 rounded-full">
            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span id="gm-filtro-label">Filtrando por boleta</span>
        </span>
        <button onclick="gmLimpiarFiltro()" class="text-xs text-gray-400 hover:text-red-500 transition underline">Limpiar filtro</button>
    </div>

    <p id="gm-sin-resultados" class="hidden text-sm text-gray-400 text-center py-6">Sin resultados para la búsqueda.</p>

    @if($registros->isEmpty())
    <div class="bg-white rounded-xl shadow py-32 text-center text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6M4.5 19.5l15-15M3 10.5A7.5 7.5 0 1110.5 3" />
        </svg>
        <p class="text-sm">No hay gastos menores registrados aún.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($registros as $folio => $items)
        @php
        $primero = $items->first();
        $totalMonto = $items->sum('monto');
        $fecha = \Carbon\Carbon::parse($primero->fecha_emision);
        @endphp

        @php
        $searchText = strtolower(
        'gm-' . str_pad($primero->id_gm ?? 0, 4, '0', STR_PAD_LEFT) . ' ' .
        $folio . ' ' .
        $primero->rut_proveedor . ' ' .
        $items->pluck('producto.nombre')->implode(' ')
        );
        @endphp
        <div class="bg-white rounded-xl shadow overflow-hidden gm-card" data-search="{{ $searchText }}">
            {{-- Header de boleta --}}
            <div style="background:#fef3c7; border-left:4px solid #d97706;"
                class="px-5 py-3 flex items-center justify-between gap-3 gm-card-header">
                <div style="display:grid; grid-template-columns:90px 110px 1fr 110px 140px 110px 100px; align-items:stretch; gap:0; flex:1; overflow:hidden;">
                    <div style="background:#d97706; border-radius:0.4rem; display:flex; align-items:center; justify-content:center; padding:0.2rem 0; margin:0.25rem 0;">
                        <span style="color:#fff; font-size:0.85rem; font-weight:800; font-family:monospace; letter-spacing:0.03em; white-space:nowrap; display:inline-block;">
                            GM-{{ str_pad($primero->id_gm ?? 0, 4, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>
                    <div style="padding:0 0.5rem;">
                        <p class="text-xs text-amber-600 font-semibold uppercase tracking-wide">Folio</p>
                        <p class="text-sm font-extrabold text-amber-900 font-mono truncate">{{ $folio }}</p>
                    </div>
                    <div style="padding:0 0.5rem;">
                        <p class="text-xs text-amber-600 font-semibold uppercase tracking-wide">Proveedor</p>
                        <p class="text-sm font-bold text-amber-900">{{ $primero->proveedor_nombre ?? '—' }}</p>
                        <p class="text-xs text-amber-700 truncate font-mono">{{ formatearRut($primero->rut_proveedor) }}</p>
                    </div>
                    <div style="padding:0 0.5rem;">
                        <p class="text-xs text-amber-600 font-semibold uppercase tracking-wide">Fecha Emisión</p>
                        <p class="text-sm font-semibold text-amber-900">{{ $fecha->format('d/m/Y') }}</p>
                    </div>
                    <div style="padding:0 0.5rem;">
                        <p class="text-xs text-amber-600 font-semibold uppercase tracking-wide">Registrado por</p>
                        <p class="text-sm font-semibold text-amber-900 truncate">{{ $primero->user->name ?? '—' }}</p>
                    </div>
                    <div style="padding:0 0.5rem;">
                        <p class="text-xs text-amber-600 font-semibold uppercase tracking-wide">Fecha Ingreso</p>
                        <p class="text-sm font-semibold text-amber-900">
                            {{ $primero->created_at ? $primero->created_at->format('d/m/Y') : '—' }}
                        </p>
                    </div>
                    <div style="padding:0 0.5rem;">
                        <p class="text-xs text-amber-600 font-semibold uppercase tracking-wide">Total Neto</p>
                        <p class="text-sm font-bold text-amber-900">${{ number_format($totalMonto, 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if($primero->documento_path)
                    <a href="{{ route('admin.gastos-menores.boleta', $primero->id) }}"
                        target="_blank"
                        class="gm-btn-boleta inline-flex items-center gap-1.5 text-xs font-semibold text-white px-3 py-1.5 rounded-lg"
                        style="background:#dc2626;">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                        Ver Boleta PDF
                    </a>
                    @else
                    <span class="text-xs text-gray-400 italic">Sin documento</span>
                    @endif

                    <button type="button"
                        onclick="abrirEditarGm('{{ route('admin.gastos-menores.edit', urlencode($folio)) }}', '{{ addslashes($folio) }}')"
                        class="gm-btn-editar inline-flex items-center gap-1.5 text-xs font-semibold text-white px-3 py-1.5 rounded-lg"
                        style="background:#ea580c;">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </button>
                </div>
            </div>

            {{-- Tabla de productos --}}
            <div style="overflow-x:auto;">
            <table class="w-full text-sm" style="min-width:700px; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                        <th style="padding:0.5rem 1rem; text-align:left; font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; width:52px;">#</th>
                        <th style="padding:0.5rem 1rem; text-align:left; font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em;">Producto · Categoría · Marca</th>
                        <th style="padding:0.5rem 1rem; text-align:center; font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; width:100px;">Cant.</th>
                        <th style="padding:0.5rem 1rem; text-align:right; font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; width:120px;">P. Neto</th>
                        <th style="padding:0.5rem 1rem; text-align:right; font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; width:120px;">Total</th>
                        <th style="padding:0.5rem 1rem; text-align:center; font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; width:130px;">Contenedor</th>
                        <th style="padding:0.5rem 1rem; text-align:center; font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; width:90px;">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    @php
                        $prod = $item->producto;
                        $unidad = $prod?->unidadMedida?->abreviacion ?? $prod?->unidad ?? null;
                        $tipoMov = $item->historialCambio?->tipo ?? 'entrada';
                    @endphp
                    <tr style="border-bottom:1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                        <td style="padding:0.6rem 1rem; font-size:0.72rem; color:#9ca3af; font-family:monospace;">#{{ $item->id }}</td>
                        <td style="padding:0.6rem 1rem;">
                            @if($prod)
                                <p class="gm-prod-nombre" style="font-size:0.8rem; font-weight:600; margin:0;">{{ $prod->nombre }}</p>
                                <p class="gm-prod-meta" style="font-size:0.72rem; margin:0.1rem 0 0;">
                                    {{ $prod->categoria?->nombre ?? '—' }}
                                    @if($prod->marca)
                                        · <span class="gm-marca-text">{{ $prod->marca->nombre }}</span>
                                    @endif
                                    @if(!$prod->activo)
                                        <span class="gm-badge-inactivo" style="font-size:0.68rem; font-weight:600; margin-left:0.25rem;">[inactivo]</span>
                                    @endif
                                </p>
                            @else
                                <p style="font-size:0.8rem; color:#9ca3af; font-style:italic; margin:0;">Producto no encontrado</p>
                            @endif
                        </td>
                        <td style="padding:0.6rem 1rem; text-align:center;">
                            <span style="font-size:0.85rem; font-weight:600; color:#1f2937;">{{ $item->cantidad }}</span>
                            @if($unidad)
                                <span style="font-size:0.68rem; color:#9ca3af; margin-left:0.2rem;">{{ $unidad }}</span>
                            @endif
                        </td>
                        <td style="padding:0.6rem 1rem; text-align:right; font-size:0.82rem; color:#374151;">
                            {{ $item->precio_neto ? '$' . number_format($item->precio_neto, 0, ',', '.') : '—' }}
                        </td>
                        <td style="padding:0.6rem 1rem; text-align:right; font-size:0.82rem; font-weight:600; color:#111827;">
                            ${{ number_format($item->monto, 0, ',', '.') }}
                        </td>
                        <td style="padding:0.6rem 1rem; text-align:center;">
                            @if($item->historialCambio?->container)
                                <span class="gm-badge-container" style="display:inline-block; background:#eef2ff; color:#4338ca; font-size:0.72rem; font-weight:600; padding:0.15rem 0.5rem; border-radius:9999px; white-space:nowrap;">
                                    {{ $item->historialCambio->container->nombre }}
                                </span>
                            @else
                                <span style="color:#9ca3af; font-size:0.75rem;">—</span>
                            @endif
                        </td>
                        <td style="padding:0.6rem 1rem; text-align:center;">
                            @if($tipoMov === 'entrada')
                                <span class="gm-badge-tipo gm-badge-entrada" style="display:inline-block; background:#dcfce7; color:#16a34a; font-size:0.68rem; font-weight:700; padding:0.15rem 0.5rem; border-radius:9999px; text-transform:uppercase; letter-spacing:0.03em;">Entrada</span>
                            @elseif($tipoMov === 'salida')
                                <span class="gm-badge-tipo gm-badge-salida" style="display:inline-block; background:#fef2f2; color:#dc2626; font-size:0.68rem; font-weight:700; padding:0.15rem 0.5rem; border-radius:9999px; text-transform:uppercase; letter-spacing:0.03em;">Salida</span>
                            @elseif($tipoMov === 'ajuste')
                                <span class="gm-badge-tipo gm-badge-ajuste" style="display:inline-block; background:#fef3c7; color:#d97706; font-size:0.68rem; font-weight:700; padding:0.15rem 0.5rem; border-radius:9999px; text-transform:uppercase; letter-spacing:0.03em;">Ajuste</span>
                            @else
                                <span style="color:#9ca3af; font-size:0.75rem;">{{ $tipoMov }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ══ MODAL EDITAR GASTO MENOR ══ --}}
    <div id="modal-editar-gm"
        style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); overflow-y:auto;">
        <div style="min-height:100%; display:flex; align-items:flex-start; justify-content:center; padding:2rem 1rem;">
            <div class="gm-edit-modal-inner" style="background:#fff; border-radius:1rem; width:100%; max-width:780px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb;">
                    <div>
                        <p style="font-size:0.7rem; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.1rem;">Gasto Menor</p>
                        <p style="font-size:1rem; font-weight:700; color:#92400e;">Editar Folio <span id="gm-edit-titulo" class="font-mono"></span></p>
                    </div>
                    <button type="button" onclick="cerrarModalEditarGm()"
                        style="color:#9ca3af; font-size:1.25rem; line-height:1; background:none; border:none; cursor:pointer;">✕</button>
                </div>
                <div id="gm-edit-body" style="padding:1.25rem;"></div>
            </div>
        </div>
    </div>

    {{-- ══ MODAL NUEVA COMPRA ══ --}}
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
                                    Folio — Número de Boleta <span style="color:#ef4444;">*</span>
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
                                    Boleta PDF <span style="color:#ef4444;">*</span>
                                </label>
                                <input type="file" name="documento" accept=".pdf" required
                                    style="width:100%; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.65rem; font-size:0.75rem; box-sizing:border-box; color:#374151;">
                            </div>
                        </div>

                        {{-- Selector cascade Familia → Categoría → Marca → Producto --}}
                        <div style="border-top:1px solid #e5e7eb; padding-top:0.75rem;">
                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">
                                Agregar productos <span style="color:#ef4444;">*</span>
                            </label>
                            <div style="display:flex; gap:0.45rem; flex-wrap:wrap; margin-bottom:0.45rem;">
                                <select id="gm-sel-familia"
                                        style="border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.6rem; font-size:0.78rem; min-width:140px; outline:none; background:#fff; color:#374151;">
                                    <option value="">— Familia —</option>
                                </select>
                                <select id="gm-sel-cat"
                                        style="display:none; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.6rem; font-size:0.78rem; min-width:140px; outline:none; background:#fff; color:#374151;">
                                    <option value="">— Categoría —</option>
                                </select>
                                <select id="gm-sel-marca"
                                        style="display:none; border:1px solid #d1d5db; border-radius:0.5rem; padding:0.35rem 0.6rem; font-size:0.78rem; min-width:140px; outline:none; background:#fff; color:#374151;">
                                    <option value="">— Marca —</option>
                                </select>
                            </div>
                            <div id="gm-productos-lista"
                                 style="display:none; border:1px solid #e5e7eb; border-radius:0.5rem; max-height:200px; overflow-y:auto; background:#fff; margin-bottom:0.35rem;"></div>
                            <p id="gm-limite-msg" style="display:none; font-size:0.75rem; color:#dc2626; font-weight:600; margin-top:0.4rem;">
                                ⚠ Límite de 25 productos por compra alcanzado.
                            </p>
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

    @endsection

    @push('head')
    <style>
        @keyframes gmFadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .gm-modal-inner,
        .gm-edit-modal-inner {
            animation: gmFadeUp 0.35s cubic-bezier(.22, .68, 0, 1.2) both;
        }

        /* Botón Ver Boleta PDF */
        a.gm-btn-boleta, button.gm-btn-boleta {
            cursor: pointer;
            transition: transform .1s, box-shadow .15s, filter .15s;
        }
        a.gm-btn-boleta:hover, button.gm-btn-boleta:hover {
            transform: scale(1.04);
            box-shadow: 0 0 10px 2px rgba(220,38,38,0.45);
        }
        a.gm-btn-boleta:active, button.gm-btn-boleta:active {
            transform: scale(.95);
            box-shadow: none;
            filter: brightness(.88);
        }

        /* Botón Editar */
        button.gm-btn-editar {
            cursor: pointer;
            transition: transform .1s, box-shadow .15s, filter .15s;
        }
        button.gm-btn-editar:hover {
            transform: scale(1.04);
            box-shadow: 0 0 10px 2px rgba(234,88,12,0.45);
        }
        button.gm-btn-editar:active {
            transform: scale(.95);
            box-shadow: none;
            filter: brightness(.88);
        }

        /* Dark mode — tarjetas gasto menor */
        html.dark .gm-card { background:#1e293b; }

        /* Header de boleta */
        html.dark .gm-card-header { background:#1c1917 !important; border-left-color:#d97706 !important; }
        html.dark .gm-card-header p { color:#fde68a !important; }

        /* Tabla encabezado */
        html.dark .gm-card table thead tr { background:#0f172a; border-color:#334155; }
        html.dark .gm-card table thead th { color:#94a3b8 !important; }

        /* Tabla cuerpo */
        html.dark .gm-card table tbody tr { border-color:#334155; }
        html.dark .gm-card table tbody tr:hover { background:#0f172a !important; }
        html.dark .gm-card table tbody td { color:#cbd5e1 !important; }
        /* Nombre y meta del producto — día */
        .gm-prod-nombre { color:#111827; }
        .gm-prod-meta   { color:#6b7280; }
        .gm-marca-text  { color:#7c3aed; }
        .gm-badge-inactivo { color:#dc2626; }
        /* Nombre y meta del producto — noche */
        html.dark .gm-prod-nombre { color:#f1f5f9; }
        html.dark .gm-prod-meta   { color:#94a3b8; }
        html.dark .gm-marca-text  { color:#c4b5fd; }
        html.dark .gm-badge-inactivo { color:#f87171; }

        /* Badges tipo movimiento */
        html.dark .gm-badge-entrada { background:#14532d !important; color:#86efac !important; }
        html.dark .gm-badge-salida  { background:#450a0a !important; color:#fca5a5 !important; }
        html.dark .gm-badge-ajuste  { background:#451a03 !important; color:#fcd34d !important; }

        /* Badge contenedor */
        html.dark .gm-badge-container { background:#1e1b4b !important; color:#a5b4fc !important; }
    </style>
    @endpush

@push('scripts')
@php
$gmProductosJson = json_encode(
    $productos->map(fn($p) => ['id'=>$p->id,'nombre'=>$p->nombre,'stock'=>$p->stock_actual,'categoria_id'=>$p->categoria_id,'marca_id'=>$p->marca_id])->values(),
    JSON_HEX_TAG | JSON_HEX_AMP
);
$gmFamiliasJson = json_encode(
    $familias->map(fn($f) => [
        'id'         => $f->id,
        'nombre'     => $f->nombre,
        'categorias' => $f->categorias->map(fn($c) => [
            'id'     => $c->id,
            'nombre' => $c->nombre,
            'marcas' => $c->marcas->map(fn($m) => ['id'=>$m->id,'nombre'=>$m->nombre])->values(),
        ])->values(),
    ])->values(),
    JSON_HEX_TAG | JSON_HEX_AMP
);
@endphp
<script type="application/json" id="gm-data">{!! $gmProductosJson !!}</script>
<script type="application/json" id="gm-familias-data">{!! $gmFamiliasJson !!}</script>
<script>
var gmProductos = JSON.parse(document.getElementById('gm-data').textContent);
var gmFamilias  = JSON.parse(document.getElementById('gm-familias-data').textContent);

// Inicializar select de familias
(function() {
    var sel = document.getElementById('gm-sel-familia');
    gmFamilias.forEach(function(f) {
        var opt = document.createElement('option');
        opt.value = f.id; opt.textContent = f.nombre;
        sel.appendChild(opt);
    });
    sel.addEventListener('change', gmCambiarFamilia);
    document.getElementById('gm-sel-cat').addEventListener('change', gmCambiarCat);
    document.getElementById('gm-sel-marca').addEventListener('change', gmCambiarMarca);
})();

function gmCambiarFamilia() {
    var famId  = parseInt(this.value) || null;
    var selCat = document.getElementById('gm-sel-cat');
    var selMarca = document.getElementById('gm-sel-marca');
    selCat.innerHTML = '<option value="">— Categoría —</option>';
    selMarca.innerHTML = '<option value="">— Marca —</option>';
    selMarca.style.display = 'none';
    document.getElementById('gm-productos-lista').style.display = 'none';
    if (!famId) { selCat.style.display = 'none'; return; }
    var familia = gmFamilias.find(function(f) { return f.id === famId; });
    if (!familia) { selCat.style.display = 'none'; return; }
    familia.categorias.forEach(function(c) {
        var opt = document.createElement('option');
        opt.value = c.id; opt.textContent = c.nombre;
        selCat.appendChild(opt);
    });
    selCat.style.display = '';
    selCat.value = '';
}

function gmCambiarCat() {
    var catId  = parseInt(this.value) || null;
    var famId  = parseInt(document.getElementById('gm-sel-familia').value) || null;
    var selMarca = document.getElementById('gm-sel-marca');
    selMarca.innerHTML = '<option value="">— Marca —</option>';
    selMarca.style.display = 'none';
    document.getElementById('gm-productos-lista').style.display = 'none';
    if (!catId) return;
    var familia = gmFamilias.find(function(f) { return f.id === famId; });
    var cat = familia ? familia.categorias.find(function(c) { return c.id === catId; }) : null;
    if (!cat) return;
    if (cat.marcas && cat.marcas.length > 0) {
        cat.marcas.forEach(function(m) {
            var opt = document.createElement('option');
            opt.value = m.id; opt.textContent = m.nombre;
            selMarca.appendChild(opt);
        });
        selMarca.style.display = '';
        selMarca.value = '';
    } else {
        gmMostrarProductosFiltrados(catId, null);
    }
}

function gmCambiarMarca() {
    var catId   = parseInt(document.getElementById('gm-sel-cat').value)   || null;
    var marcaId = parseInt(this.value) || null;
    if (!catId) return;
    gmMostrarProductosFiltrados(catId, marcaId);
}

function gmMostrarProductosFiltrados(catId, marcaId) {
    var lista = document.getElementById('gm-productos-lista');
    var filtrados = gmProductos.filter(function(p) {
        if (p.categoria_id !== catId) return false;
        if (marcaId && p.marca_id !== marcaId) return false;
        return true;
    });
    if (!filtrados.length) {
        lista.innerHTML = '<p style="font-size:0.78rem;color:#9ca3af;padding:0.5rem 0.75rem;">Sin productos en esta selección.</p>';
        lista.style.display = 'block';
        return;
    }
    lista.innerHTML = filtrados.map(function(p) {
        return '<div onclick="gmAgregar(' + p.id + ',\'' + p.nombre.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\')" '
            + 'style="padding:0.45rem 0.75rem;cursor:pointer;border-bottom:1px solid #f3f4f6;" '
            + 'onmouseover="this.style.background=\'#fef3c7\'" onmouseout="this.style.background=\'\'">'
            + '<span style="font-size:0.8rem;font-weight:600;color:#1f2937;">' + escHtmlGm(p.nombre) + '</span>'
            + '<span style="font-size:0.72rem;color:#6b7280;margin-left:0.5rem;">Stock: ' + p.stock + '</span>'
            + '</div>';
    }).join('');
    lista.style.display = 'block';
}
var gmItems = [];
var gmCounter = 0;

document.getElementById('btn-abrir-gasto-menor').addEventListener('click', function() {
    var modal = document.getElementById('modal-gasto-menor');
    var inner = modal.querySelector('.gm-modal-inner');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    inner.style.animation = 'none';
    inner.offsetHeight;
    inner.style.animation = '';
});

function cerrarModalGastoMenor() {
    document.getElementById('modal-gasto-menor').style.display = 'none';
    document.body.style.overflow = '';
    // Resetear cascade y lista de productos
    document.getElementById('gm-sel-familia').value = '';
    var selCat = document.getElementById('gm-sel-cat');
    var selMarca = document.getElementById('gm-sel-marca');
    selCat.innerHTML = '<option value="">— Categoría —</option>';
    selCat.style.display = 'none';
    selMarca.innerHTML = '<option value="">— Marca —</option>';
    selMarca.style.display = 'none';
    document.getElementById('gm-productos-lista').style.display = 'none';
}

document.getElementById('modal-gasto-menor').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalGastoMenor();
});

var GM_MAX_ITEMS = 25;

function gmAgregar(id, nombre) {
    if (gmItems.find(function(i) { return i.id === id; })) return;
    if (gmItems.length >= GM_MAX_ITEMS) {
        document.getElementById('gm-limite-msg').style.display = '';
        return;
    }
    document.getElementById('gm-limite-msg').style.display = 'none';
    var idx = gmCounter++;
    gmItems.push({ idx: idx, id: id, nombre: nombre });
    gmRenderFila(idx, id, nombre);
    gmActualizarTabla();
}

function gmRenderFila(idx, id, nombre) {
    var tbody = document.getElementById('gm-items');
    var tr = document.createElement('tr');
    tr.id = 'gm-row-' + idx;
    tr.style.borderBottom = '1px solid #f3f4f6';
    tr.innerHTML =
        '<td style="padding:0.4rem 0.6rem;">'
        + '<input type="hidden" name="items[' + idx + '][producto_id]" value="' + id + '">'
        + '<span style="font-size:0.8rem;font-weight:500;color:#1f2937;">' + escHtmlGm(nombre) + '</span>'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items[' + idx + '][cantidad]" value="1" min="1" required style="width:68px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items[' + idx + '][monto]" placeholder="0" min="0" step="1" required style="width:108px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.4rem;text-align:center;">'
        + '<input type="number" name="items[' + idx + '][precio_neto]" placeholder="0" min="0" step="1" style="width:120px;text-align:center;border:1px solid #d1d5db;border-radius:0.375rem;padding:0.3rem 0.4rem;font-size:0.8rem;">'
        + '</td>'
        + '<td style="padding:0.4rem 0.3rem;text-align:center;">'
        + '<button type="button" onclick="gmQuitar(' + idx + ')" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem;line-height:1;">✕</button>'
        + '</td>';ddddddddddd
    tbody.appendChild(tr);
}

function gmQuitar(idx) {
    gmItems = gmItems.filter(function(i) { return i.idx !== idx; });
    var row = document.getElementById('gm-row-' + idx);
    if (row) row.remove();
    gmActualizarTabla();
}

function gmActualizarTabla() {
    var wrap = document.getElementById('gm-tabla-wrap');
    var sin  = document.getElementById('gm-sin-items');
    wrap.style.display = gmItems.length ? '' : 'none';
    sin.style.display  = gmItems.length ? 'none' : '';
}

function escHtmlGm(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function gmFiltrar(q, etiqueta) {
    q = (q || '').trim().toLowerCase();
    var cards = document.querySelectorAll('.gm-card');
    var visible = 0;
    cards.forEach(function(card) {
        var match = !q || card.dataset.search.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('gm-sin-resultados').classList.toggle('hidden', visible > 0 || !q);
    var chip = document.getElementById('gm-filtro-chip');
    if (etiqueta) {
        document.getElementById('gm-filtro-label').textContent = 'Mostrando boleta ' + etiqueta;
        chip.classList.remove('hidden');
    } else {
        chip.classList.add('hidden');
    }
}

function gmLimpiarFiltro() {
    document.getElementById('buscador-gm').value = '';
    gmFiltrar('');
    history.replaceState(null, '', window.location.pathname);
}

document.getElementById('buscador-gm').addEventListener('input', function() {
    gmFiltrar(this.value);
});

(function() {
    var params = new URLSearchParams(window.location.search);
    var gm = params.get('gm');
    if (gm) {
        var label = 'GM-' + String(gm).padStart(4, '0');
        var val = label.toLowerCase();
        document.getElementById('buscador-gm').value = val;
        gmFiltrar(val, label);
    }
})();

// ══ Modal Editar Gasto Menor ══
function abrirEditarGm(url, folio) {
    var modal = document.getElementById('modal-editar-gm');
    var inner = modal.querySelector('.gm-edit-modal-inner');
    document.getElementById('gm-edit-titulo').textContent = folio;
    document.getElementById('gm-edit-body').innerHTML = '<p style="text-align:center;color:#9ca3af;padding:2rem 0;font-size:0.875rem;">Cargando...</p>';
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    inner.style.animation = 'none';
    inner.offsetHeight;
    inner.style.animation = '';

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
    .then(function(r) { return r.text(); })
    .then(function(html) {
        document.getElementById('gm-edit-body').innerHTML = html;
        gmEditInit();
    })
    .catch(function() {
        document.getElementById('gm-edit-body').innerHTML = '<p style="text-align:center;color:#ef4444;padding:2rem 0;font-size:0.875rem;">Error al cargar el formulario.</p>';
    });
}

function gmEditInit() {
    // Contenedor selects AJAX
    document.querySelectorAll('#modal-editar-gm .gm-cont-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            fetch(this.dataset.url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ contenedor_id: this.value })
            });
        });
    });

    // Cancelar
    var btnCancelar = document.getElementById('btn-cancelar-editar-gm');
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModalEditarGm);

    // Submit via fetch
    var form = document.getElementById('form-editar-gm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('btn-submit-editar-gm');
            var errDiv = document.getElementById('gm-edit-errors');
            if (errDiv) errDiv.classList.add('hidden');
            if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form)
            })
            .then(function(r) {
                return r.json().then(function(d) { return { ok: r.ok, data: d }; });
            })
            .then(function(res) {
                if (res.ok && res.data.ok) {
                    cerrarModalEditarGm();
                    window.location.reload();
                } else {
                    if (btn) { btn.disabled = false; btn.textContent = 'Guardar cambios'; }
                    if (errDiv) {
                        var msgs = res.data.errors
                            ? Object.values(res.data.errors).flat().join(' · ')
                            : (res.data.message || 'Error al guardar.');
                        errDiv.textContent = msgs;
                        errDiv.classList.remove('hidden');
                    }
                }
            })
            .catch(function() {
                if (btn) { btn.disabled = false; btn.textContent = 'Guardar cambios'; }
                if (errDiv) {
                    errDiv.textContent = 'Error de conexión. Intenta de nuevo.';
                    errDiv.classList.remove('hidden');
                }
            });
        });
    }
}

function cerrarModalEditarGm() {
    var modal = document.getElementById('modal-editar-gm');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('gm-edit-body').innerHTML = '';
}

document.getElementById('modal-editar-gm').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEditarGm();
});
</script>
@endpush