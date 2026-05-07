@extends('layouts.app')
@section('title', 'Historial de Precios')

@section('content')

<div class="mb-5 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Historial de Precios</h1>
        <p class="text-sm text-gray-500 mt-1">Trazabilidad de precios por producto, familia y categoría</p>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Producto</label>
        <select name="producto" onchange="this.form.submit()"
                style="border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; background:#fff; min-width:200px;">
            <option value="">— Todos —</option>
            @foreach($productos as $p)
                <option value="{{ $p->id }}" {{ request('producto') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Familia</label>
        <select name="familia" onchange="this.form.submit()"
                style="border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; background:#fff; min-width:160px;">
            <option value="">— Todas —</option>
            @foreach($familias as $f)
                <option value="{{ $f->id }}" {{ request('familia') == $f->id ? 'selected' : '' }}>{{ $f->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Fuente</label>
        <select name="fuente" onchange="this.form.submit()"
                style="border:1px solid #d1d5db; border-radius:0.5rem; padding:0.4rem 0.65rem; font-size:0.8rem; background:#fff; min-width:160px;">
            <option value="">— Todas —</option>
            <option value="boleta_local" {{ request('fuente') === 'boleta_local' ? 'selected' : '' }}>Boleta Local</option>
            <option value="sicd_masiva"  {{ request('fuente') === 'sicd_masiva'  ? 'selected' : '' }}>SICD (Recepción OC)</option>
            <option value="sicd_manual"  {{ request('fuente') === 'sicd_manual'  ? 'selected' : '' }}>SICD (Carga manual)</option>
            <option value="manual"       {{ request('fuente') === 'manual'       ? 'selected' : '' }}>Manual</option>
        </select>
    </div>
    @if(request()->hasAny(['producto','familia','fuente']))
    <a href="{{ route('admin.precios.index') }}"
       style="padding:0.4rem 0.75rem; font-size:0.78rem; font-weight:600; color:#6b7280; background:#f3f4f6; border-radius:0.5rem; text-decoration:none;">
        ✕ Limpiar
    </a>
    @endif
</form>

<div class="bg-white rounded-xl shadow overflow-hidden">
    @if($precios->isEmpty())
        <div class="py-20 text-center text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-4-6h8"/>
            </svg>
            <p class="text-sm">No hay precios registrados aún.</p>
        </div>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 whitespace-nowrap">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Producto</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Familia</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Categoría</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 whitespace-nowrap">Precio Neto</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600">Cantidad</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 whitespace-nowrap">Total Neto</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600">Fuente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Notas</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($precios as $p)
                @php
                    $fuenteLabel = match($p->fuente) {
                        'boleta_local' => ['texto' => 'Boleta Local',     'bg' => '#fef3c7', 'color' => '#92400e'],
                        'sicd_masiva'  => ['texto' => 'SICD OC',          'bg' => '#eff6ff', 'color' => '#1e40af'],
                        'sicd_manual'  => ['texto' => 'SICD Manual',      'bg' => '#f0fdf4', 'color' => '#166534'],
                        default        => ['texto' => 'Manual',           'bg' => '#f3f4f6', 'color' => '#6b7280'],
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap text-xs">
                        {{ $p->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-4 py-2.5 font-medium text-gray-800">
                        {{ $p->producto?->nombre ?? '—' }}
                        @if($p->producto?->unidad)
                            <span class="text-xs text-gray-400 ml-1">/ {{ $p->producto->unidad }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-gray-600 text-xs">{{ $p->familia?->nombre ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-600 text-xs">{{ $p->categoria?->nombre ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-gray-800 whitespace-nowrap">
                        ${{ number_format($p->precio_neto, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-700">{{ $p->cantidad }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-green-700 whitespace-nowrap">
                        @if($p->precio_total)
                            ${{ number_format($p->precio_total, 0, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <span style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:9999px;
                                     background:{{ $fuenteLabel['bg'] }}; color:{{ $fuenteLabel['color'] }}; white-space:nowrap;">
                            {{ $fuenteLabel['texto'] }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-500">{{ $p->notas ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-600">{{ $p->usuario?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($precios->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">
            Mostrando {{ $precios->firstItem() }}–{{ $precios->lastItem() }} de {{ $precios->total() }} registros
        </p>
        <div class="flex gap-1">
            @foreach($precios->links()->offsetGet('elements') ?? [] as $element)
                @if(is_string($element))
                    <span style="padding:0.3rem 0.5rem; font-size:0.75rem; color:#9ca3af;">{{ $element }}</span>
                @elseif(is_array($element))
                    @foreach($element as $page => $url)
                        @if($page == $precios->currentPage())
                            <span style="padding:0.3rem 0.65rem; font-size:0.75rem; font-weight:700; background:#2563eb; color:#fff; border-radius:0.4rem;">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" style="padding:0.3rem 0.65rem; font-size:0.75rem; font-weight:600; background:#eff6ff; color:#2563eb; border-radius:0.4rem; text-decoration:none;"
                               onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>
    </div>
    @endif
    @endif
</div>

@endsection
