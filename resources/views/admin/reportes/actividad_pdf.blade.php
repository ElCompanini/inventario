<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #1e293b; background: #fff; }

    .header { background: #1e3a5f; color: #fff; padding: 10px 14px 8px; margin-bottom: 4px; }
    .header h1 { font-size: 14px; font-weight: bold; letter-spacing: 0.5px; }
    .header p  { font-size: 8px; color: #93c5fd; margin-top: 2px; }

    .meta { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;
            margin-bottom: 10px; padding: 6px 10px; display: flex; gap: 0; flex-wrap: wrap; }
    .meta-item { flex: 1; min-width: 160px; padding: 3px 8px; }
    .meta-item .lbl { font-size: 7px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }
    .meta-item .val { font-size: 9px; font-weight: bold; color: #1e293b; margin-top: 1px; }

    table { width: 100%; border-collapse: collapse; font-size: 8px; }
    thead tr { background: #1e3a5f; color: #fff; }
    thead th { padding: 5px 4px; text-align: left; font-size: 7.5px; font-weight: bold;
               border: 1px solid #2563eb; letter-spacing: 0.3px; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr.entrada { background: #f0fdf4; }
    tbody tr.salida  { background: #fff8f8; }
    tbody td { padding: 4px 4px; border: 1px solid #e2e8f0; vertical-align: middle; }

    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
    .badge-entrada { background: #dcfce7; color: #166534; }
    .badge-salida  { background: #fee2e2; color: #991b1b; }

    .cantidad { text-align: right; font-weight: bold; font-size: 8.5px; }

    tfoot tr { background: #2563eb; color: #fff; }
    tfoot td { padding: 5px 4px; font-weight: bold; border: 1px solid #1e3a5f; }

    .footer { margin-top: 8px; font-size: 7px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 4px; }
</style>
</head>
<body>

<div class="header">
    <h1>SISTEMA DE GESTIÓN DE INVENTARIO — ACTIVIDAD RECIENTE</h1>
    <p>Historial de Movimientos · Uso Interno / Auditoría</p>
</div>

<div class="meta">
    <div class="meta-item">
        <div class="lbl">Período</div>
        <div class="val">{{ $data['desde'] }} → {{ $data['hasta'] }}</div>
    </div>
    <div class="meta-item">
        <div class="lbl">Total movimientos</div>
        <div class="val">{{ $data['total'] }} registros</div>
    </div>
    <div class="meta-item">
        <div class="lbl">Generado por</div>
        <div class="val">{{ $data['generado_por'] }}</div>
    </div>
    <div class="meta-item">
        <div class="lbl">Fecha generación</div>
        <div class="val">{{ $data['generado_at'] }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:56px">FECHA</th>
            <th style="width:34px">HORA</th>
            <th style="width:50px">TIPO</th>
            <th>PRODUCTO</th>
            <th style="width:70px">CATEGORÍA</th>
            <th style="width:54px">MARCA</th>
            <th style="width:36px; text-align:right">CANT.</th>
            <th style="width:58px">MÓDULO</th>
            <th style="width:62px">DOCUMENTO</th>
            <th style="width:60px">USUARIO</th>
            <th>OBSERVACIONES</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['filas'] as $f)
        <tr class="{{ $f['tipo'] }}">
            <td>{{ $f['fecha'] }}</td>
            <td>{{ $f['hora'] }}</td>
            <td><span class="badge badge-{{ $f['tipo'] }}">{{ strtoupper($f['tipo_label']) }}</span></td>
            <td>{{ $f['producto'] }}</td>
            <td>{{ $f['categoria'] }}</td>
            <td>{{ $f['marca'] }}</td>
            <td class="cantidad">{{ number_format($f['cantidad'], 0, ',', '.') }}</td>
            <td>{{ $f['modulo'] }}</td>
            <td>{{ $f['documento'] }}</td>
            <td>{{ $f['usuario'] }}</td>
            <td style="font-size:7px; color:#475569">{{ \Illuminate\Support\Str::limit($f['observaciones'], 60) }}</td>
        </tr>
        @empty
        <tr><td colspan="11" style="text-align:center; padding:16px; color:#94a3b8">Sin movimientos en el período seleccionado</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">TOTAL</td>
            <td style="text-align:right">{{ number_format(array_sum(array_column($data['filas'], 'cantidad')), 0, ',', '.') }}</td>
            <td colspan="4"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    Reporte generado el {{ $data['generado_at'] }} por {{ $data['generado_por'] }} · Sistema de Gestión de Inventario · Confidencial
</div>

</body>
</html>
