<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; color: #1e293b; }

/* Encabezado */
.header-inst { background:#1e3a5f; color:#fff; padding:8px 14px; }
.header-inst h1 { font-size:11pt; font-weight:bold; margin:0; }
.header-inst p  { font-size:7pt; opacity:.8; margin:2px 0 0; }
.header-sub { background:#2563eb; color:#fff; padding:4px 14px; font-size:7pt; font-style:italic; }

/* Info producto */
.info-block { background:#f8fafc; border-bottom:2px solid #e2e8f0; padding:8px 14px; }
.info-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; }
.info-item label { font-size:6.5pt; font-weight:bold; text-transform:uppercase; color:#64748b; letter-spacing:.03em; display:block; }
.info-item span  { font-size:8.5pt; font-weight:bold; color:#1e293b; }

/* KPIs */
.kpi-row { display:grid; grid-template-columns:repeat(6,1fr); gap:4px; padding:6px 14px; background:#fff; border-bottom:1px solid #e2e8f0; }
.kpi-box { text-align:center; padding:4px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:4px; }
.kpi-box label { font-size:6pt; font-weight:700; text-transform:uppercase; color:#64748b; display:block; }
.kpi-box span  { font-size:10pt; font-weight:800; display:block; }

.cost-row { display:grid; grid-template-columns:repeat(3,1fr); gap:4px; padding:4px 14px 6px; background:#fff; border-bottom:1px solid #e2e8f0; }
.cost-box { text-align:center; padding:4px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:4px; }
.cost-box label { font-size:6pt; font-weight:700; text-transform:uppercase; color:#1e40af; display:block; }
.cost-box span  { font-size:9pt; font-weight:800; color:#1d4ed8; display:block; }

/* Meta */
.meta { padding:4px 14px; font-size:6.5pt; color:#64748b; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; }

/* Tabla */
.table-wrap { padding:0 0; margin-top:4px; }
table { width:100%; border-collapse:collapse; }
thead tr th {
    background:#1e3a5f; color:#fff;
    font-size:6.5pt; font-weight:700;
    padding:5px 4px;
    text-align:center;
    border:1px solid #2563eb;
    white-space:nowrap;
}
tbody tr td {
    font-size:7pt;
    padding:3px 4px;
    border:1px solid #e2e8f0;
    vertical-align:middle;
}
tbody tr.entrada { background:#f0fdf4; }
tbody tr.salida  { background:#fff8f8; }
tbody tr.alt     { background:#f8fafc; }
tbody tr.totales { background:#1e3a5f; color:#fff; }
tbody tr.totales td { border-color:#2563eb; font-weight:bold; color:#fff; }

.badge { display:inline-block; font-size:6pt; font-weight:700; padding:1px 5px; border-radius:9999px; }
.badge-e { background:#dcfce7; color:#15803d; }
.badge-s { background:#fee2e2; color:#dc2626; }
.badge-n { background:#f3f4f6; color:#6b7280; }

.text-right  { text-align:right; }
.text-center { text-align:center; }
.text-indigo { color:#4f46e5; }
.text-green  { color:#16a34a; }
.text-red    { color:#dc2626; }
.mono        { font-family:'DejaVu Sans Mono', monospace; font-size:6.5pt; }

/* Footer */
.footer { margin-top:8px; padding:4px 14px; font-size:6pt; color:#94a3b8; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; }
</style>
</head>
<body>

@php $producto = $data['producto']; $mostrarCostos = $data['mostrar_costos']; @endphp

{{-- Encabezado institucional --}}
<div class="header-inst">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1>TARJETA BINCARD — CONTROL DE EXISTENCIAS</h1>
            <p>Sistema de Gestión de Inventario · Documento de Trazabilidad y Auditoría</p>
        </div>
        <div style="text-align:right; font-size:7pt;">
            <div>Emitido: {{ $data['generado_at'] }}</div>
            <div>Por: {{ $data['generado_por'] }}</div>
        </div>
    </div>
</div>
<div class="header-sub">Uso Exclusivo Interno — Auditoría Administrativa y Financiera</div>

{{-- Info producto --}}
<div class="info-block">
    <div class="info-grid">
        <div class="info-item"><label>Producto</label><span>{{ $producto->nombre }}</span></div>
        <div class="info-item"><label>Categoría / Familia</label><span>{{ $producto->categoria?->nombre ?? '—' }} / {{ $producto->categoria?->familia?->nombre ?? '—' }}</span></div>
        <div class="info-item"><label>Unidad / Ubicación</label><span>{{ $producto->unidadMedida?->abreviacion ?? $producto->unidad ?? '—' }} · {{ $producto->container?->nombre ?? '—' }}</span></div>
        <div class="info-item"><label>Centro de Costo</label><span>{{ $producto->centroCosto?->nombre_completo ?? '—' }}</span></div>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-row">
    @php $estado = $producto->estadoStock(); @endphp
    @php $kpis = [
        ['Stock Actual', $producto->stock_actual, $estado === 'critico' ? '#ef4444' : ($estado === 'minimo' ? '#f59e0b' : '#16a34a')],
        ['Mínimo', $producto->stock_minimo ?? '—', '#6b7280'],
        ['Crítico', $producto->stock_critico ?? '—', '#6b7280'],
        ['Total Entradas', $data['total_entradas'], '#16a34a'],
        ['Total Salidas', $data['total_salidas'], '#ef4444'],
        ['Saldo Final', $data['saldo_final'], '#4f46e5'],
    ]; @endphp
    @foreach($kpis as [$l,$v,$c])
    <div class="kpi-box"><label>{{ $l }}</label><span style="color:{{ $c }}">{{ $v }}</span></div>
    @endforeach
</div>

@if($mostrarCostos && ($data['costo_promedio'] || $data['ultimo_costo']))
<div class="cost-row">
    @foreach([
        ['Costo Promedio', $data['costo_promedio'] ? '$'.number_format($data['costo_promedio'],0,',','.') : '—'],
        ['Último Costo', $data['ultimo_costo'] ? '$'.number_format($data['ultimo_costo'],0,',','.') : '—'],
        ['Valor Inventario', $data['valor_inventario'] ? '$'.number_format($data['valor_inventario'],0,',','.') : '—'],
    ] as [$l,$v])
    <div class="cost-box"><label>{{ $l }}</label><span>{{ $v }}</span></div>
    @endforeach
</div>
@endif

<div class="meta">
    <span>
        @if(!empty($data['filtros']['fecha_desde'])) Desde: {{ $data['filtros']['fecha_desde'] }} @endif
        @if(!empty($data['filtros']['fecha_hasta'])) Hasta: {{ $data['filtros']['fecha_hasta'] }} @endif
        @if(empty($data['filtros']['fecha_desde']) && empty($data['filtros']['fecha_hasta'])) Sin filtro de fechas — historial completo @endif
    </span>
    <span>Código: #{{ $producto->id }} · Estado: {{ $producto->activo ? 'Activo' : 'Inactivo' }}</span>
</div>

{{-- Tabla BINCARD --}}
<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th style="text-align:left;">Fecha</th>
            <th>Tipo Mov.</th>
            <th style="text-align:left;">N° Documento</th>
            <th>RUT Proveedor</th>
            <th style="text-align:left;">Proveedor</th>
            <th style="color:#86efac;">Entrada</th>
            <th style="color:#fca5a5;">Salida</th>
            <th style="color:#a5b4fc;">Saldo</th>
            @if($mostrarCostos)
            <th>Costo Unit.</th>
            <th>Valor Mov.</th>
            <th>C. Prom.</th>
            <th>Val. Saldo</th>
            @endif
            <th style="text-align:left;">Usuario</th>
            <th style="text-align:left;">Observaciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['filas'] as $idx => $fila)
        @php
            $esE = $fila['entrada'] !== null;
            $esS = $fila['salida']  !== null;
            $cls = $esE ? 'entrada' : ($esS ? 'salida' : ($idx % 2 ? 'alt' : ''));
        @endphp
        <tr class="{{ $cls }}">
            <td class="mono">{{ $fila['fecha'] }}</td>
            <td class="text-center">
                <span class="badge {{ $esE ? 'badge-e' : ($esS ? 'badge-s' : 'badge-n') }}">{{ $fila['tipo_movimiento'] }}</span>
            </td>
            <td class="mono text-indigo">{{ $fila['n_documento'] }}</td>
            <td class="text-center mono">{{ $fila['rut_proveedor'] }}</td>
            <td>{{ \Illuminate\Support\Str::limit($fila['proveedor'], 25) }}</td>
            <td class="text-center text-green" style="font-weight:bold;">{{ $fila['entrada'] ?? '' }}</td>
            <td class="text-center text-red" style="font-weight:bold;">{{ $fila['salida'] ?? '' }}</td>
            <td class="text-center text-indigo" style="font-weight:bold;">{{ $fila['saldo'] }}</td>
            @if($mostrarCostos)
            <td class="text-right">{{ $fila['costo_unitario'] ? '$'.number_format($fila['costo_unitario'],0,',','.') : '—' }}</td>
            <td class="text-right">{{ $fila['valor_movimiento'] ? '$'.number_format($fila['valor_movimiento'],0,',','.') : '—' }}</td>
            <td class="text-right" style="color:#64748b;">{{ $fila['costo_promedio'] ? '$'.number_format($fila['costo_promedio'],0,',','.') : '—' }}</td>
            <td class="text-right text-indigo" style="font-weight:bold;">{{ $fila['valor_saldo'] ? '$'.number_format($fila['valor_saldo'],0,',','.') : '—' }}</td>
            @endif
            <td>{{ $fila['usuario'] }}</td>
            <td>{{ \Illuminate\Support\Str::limit($fila['observaciones'], 40) }}</td>
        </tr>
        @empty
        <tr><td colspan="15" style="text-align:center; padding:12px; color:#9ca3af;">Sin movimientos para los filtros seleccionados.</td></tr>
        @endforelse

        @if(count($data['filas']) > 0)
        <tr class="totales">
            <td colspan="{{ $mostrarCostos ? 6 : 6 }}" style="text-align:right; padding:4px 8px;">TOTALES Y SALDO FINAL</td>
            <td class="text-center" style="color:#86efac;">{{ $data['total_entradas'] ?: '—' }}</td>
            <td class="text-center" style="color:#fca5a5;">{{ $data['total_salidas'] ?: '—' }}</td>
            <td class="text-center" style="color:#a5b4fc;">{{ $data['saldo_final'] }}</td>
            @if($mostrarCostos)
            <td></td><td></td>
            <td class="text-right" style="color:#bfdbfe;">{{ $data['costo_promedio'] ? '$'.number_format($data['costo_promedio'],0,',','.') : '—' }}</td>
            <td class="text-right" style="color:#a5b4fc;">{{ $data['valor_inventario'] ? '$'.number_format($data['valor_inventario'],0,',','.') : '—' }}</td>
            @endif
            <td colspan="2"></td>
        </tr>
        @endif
    </tbody>
</table>
</div>

<div class="footer">
    <span>Documento generado automáticamente — Confidencial — No válido sin firma</span>
    <span>{{ $data['generado_at'] }} · {{ $data['generado_por'] }}</span>
</div>

</body>
</html>
