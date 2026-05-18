<?php

namespace App\Services;

use App\Models\GastoMenor;
use App\Models\HistorialCambio;
use App\Models\OrdenCompra;
use App\Models\Precio;
use App\Models\Producto;
use App\Models\Sicd;

class BincardService
{
    /**
     * Genera el historial BINCARD completo para un producto.
     * Enriquece cada movimiento con valorización, proveedor y documento.
     */
    public function generarBincard(Producto $producto, array $filtros = []): array
    {
        // ── 1. Cargar movimientos ──────────────────────────────────────────
        $query = HistorialCambio::withTrashed()
            ->where('producto_id', $producto->id)
            ->with(['usuario:id,name'])
            ->orderBy('created_at')
            ->orderBy('id');

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }
        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        $movimientos = $query->get();

        // ── 2. Pre-cargar documentos relacionados ─────────────────────────
        $sicdIds  = $movimientos->where('origen', 'sicd')->pluck('origen_id')->unique()->filter();
        $gmIds    = $movimientos->where('origen', 'gasto_menor')->pluck('origen_id')->unique()->filter();
        $ocIds    = $movimientos->whereNotNull('orden_compra_id')->pluck('orden_compra_id')->unique()->filter();

        $sicds  = Sicd::withTrashed()->whereIn('id', $sicdIds)->get()->keyBy('id');
        $gastos = GastoMenor::whereIn('id', $gmIds)->get()->keyBy('id');

        // OCs directamente vinculadas a cada movimiento (link granular)
        $ocsDirectas = OrdenCompra::whereIn('id', $ocIds)
            ->select('id', 'numero_oc', 'api_proveedor_nombre', 'api_proveedor_rut')
            ->get()
            ->keyBy('id');

        // Fallback: OCs por SICD (para registros históricos sin orden_compra_id)
        $ocsPorSicd = Sicd::withTrashed()->whereIn('id', $sicdIds)
            ->with(['ordenesCompra' => fn($q) => $q->select('ordenes_compra.id', 'numero_oc', 'api_proveedor_nombre', 'api_proveedor_rut')])
            ->get()
            ->keyBy('id');

        // ── 3. Pre-cargar precios indexados para lookup granular ─────────
        // Indexado por: "origen_tipo|origen_id|orden_compra_id" para resolución exacta
        $preciosRaw = Precio::where('producto_id', $producto->id)->orderBy('created_at')->get();

        // Índice granular: sicd_id + oc_id → precio exacto
        $preciosPorOrigenOc = $preciosRaw->mapWithKeys(function ($p) {
            $key = ($p->origen_tipo ?? '') . '|' . ($p->origen_id ?? '') . '|' . ($p->orden_compra_id ?? '');
            return [$key => $p];
        });

        // Índice fallback: sicd_id → primer precio (registros históricos sin orden_compra_id)
        $preciosPorOrigen = $preciosRaw->groupBy(function ($p) {
            return ($p->origen_tipo ?? '') . '|' . ($p->origen_id ?? '');
        });

        // Alias para compatibilidad con código de GastoMenor más abajo
        $precios = $preciosRaw;

        // ── 4. Construir filas BINCARD con saldo acumulado ────────────────
        $saldoAcum    = 0;
        $costoPromedio = 0.0;
        $valorAcum    = 0.0;
        $rows         = [];

        foreach ($movimientos as $mov) {
            // Determinar entrada / salida
            $esEntrada = in_array($mov->tipo, ['entrada']);
            $esSalida  = in_array($mov->tipo, ['salida', 'retiro', 'merma']);

            $entrada = $esEntrada ? $mov->cantidad : null;
            $salida  = $esSalida  ? $mov->cantidad : null;
            $ajuste  = (!$esEntrada && !$esSalida) ? $mov->cantidad : null;

            $saldoAcum += $esEntrada ? $mov->cantidad : ($esSalida ? -$mov->cantidad : 0);

            // ── Enriquecer con documento y proveedor ──────────────────────
            $tipoDoc    = '—';
            $nDoc       = '—';
            $rutProv    = '—';
            $proveedor  = '—';
            $costoUnit  = null;

            if ($mov->origen === 'sicd' && isset($sicds[$mov->origen_id])) {
                $sicd      = $sicds[$mov->origen_id];
                $tipoDoc   = 'SICD';
                $sicdLabel = 'SICD ' . $sicd->codigo_sicd;

                // ── Granularidad: cada movimiento tiene su propia OC ──────
                // Prioridad 1: orden_compra_id directo en el movimiento (registros nuevos)
                // Prioridad 2: fallback por motivo o todas las OC (registros históricos)

                if ($mov->orden_compra_id && isset($ocsDirectas[$mov->orden_compra_id])) {
                    // ✅ Link directo — muestra SOLO la OC que causó este ingreso
                    $ocDirecta = $ocsDirectas[$mov->orden_compra_id];
                    $tipoDoc   = 'SICD / OC';
                    $nDoc      = $sicdLabel . ' | OC ' . $ocDirecta->numero_oc;
                    $rutProv   = $ocDirecta->api_proveedor_rut   ?? '—';
                    $proveedor = $ocDirecta->api_proveedor_nombre ?? '—';

                } else {
                    // ⚠️ Fallback para registros históricos sin orden_compra_id:
                    // Intentar parsear el motivo ("OC 2770-339-SE25 – SICD ...")
                    $ocFromMotivo = null;
                    if (preg_match('/OC\s+([\w\-\.]+)/i', $mov->motivo ?? '', $m)) {
                        $ocFromMotivo = $ocsPorSicd[$sicd->id]?->ordenesCompra
                            ->firstWhere('numero_oc', $m[1]);
                    }

                    if ($ocFromMotivo) {
                        $tipoDoc   = 'SICD / OC';
                        $nDoc      = $sicdLabel . ' | OC ' . $ocFromMotivo->numero_oc;
                        $rutProv   = $ocFromMotivo->api_proveedor_rut   ?? '—';
                        $proveedor = $ocFromMotivo->api_proveedor_nombre ?? '—';
                    } else {
                        // Último recurso: mostrar todas las OC (comportamiento antiguo)
                        $todasOcs = $ocsPorSicd[$sicd->id]?->ordenesCompra ?? collect();
                        $ocNums   = $todasOcs->pluck('numero_oc')->filter()->values();
                        $nDoc     = $ocNums->isNotEmpty()
                            ? $sicdLabel . ' | OC: ' . $ocNums->join(' / ')
                            : $sicdLabel;
                        $rutProv   = $todasOcs->pluck('api_proveedor_rut')->filter()->unique()->join(' / ') ?: '—';
                        $proveedor = $todasOcs->pluck('api_proveedor_nombre')->filter()->unique()->join(' / ') ?: ($sicd->proveedor_nombre ?? '—');
                        if ($todasOcs->isNotEmpty()) $tipoDoc = 'SICD / OC';
                    }
                }

                // Si aún no hay proveedor (SICD sin OC, carga manual directa), usar datos del SICD
                if ($proveedor === '—' && $sicd->proveedor_nombre) {
                    $proveedor = $sicd->proveedor_nombre;
                    $rutProv   = $sicd->rut_proveedor ?? $rutProv;
                    if ($sicd->folio) $nDoc .= ' | Folio ' . $sicd->folio;
                }

                // Costo: lookup granular por SICD + OC específica (prioridad 1)
                // → evita ambigüedad cuando múltiples OCs comparten una SICD
                $precioKey = 'Sicd|' . $sicd->id . '|' . ($mov->orden_compra_id ?? '');
                $precio = $preciosPorOrigenOc[$precioKey]
                    // Fallback: primer precio con ese sicd_id (registros históricos)
                    ?? $preciosPorOrigen->get('Sicd|' . $sicd->id)?->first();
                if ($precio) $costoUnit = round($precio->precio_neto * 1.19, 2);

            } elseif ($mov->origen === 'gasto_menor' && isset($gastos[$mov->origen_id])) {
                $gasto   = $gastos[$mov->origen_id];
                $tipoDoc = 'Boleta/Factura';
                $nDoc      = $gasto->folio;
                $rutProv   = $gasto->rut_proveedor;
                $proveedor = $gasto->proveedor_nombre ?? '—';
                $precioNetaGM = $gasto->precio_neto ?: ($gasto->cantidad > 0 ? round($gasto->monto / $gasto->cantidad, 2) : null);
                $costoUnit = $precioNetaGM !== null ? round($precioNetaGM * 1.19, 2) : null;

            } elseif ($mov->tipo === 'entrada') {
                $tipoDoc = 'Documento Interno';
            } else {
                $tipoDoc = 'Movimiento Interno';
            }

            // ── Calcular costo promedio ponderado (AVCO) ──────────────────
            if ($esEntrada && $costoUnit !== null && $costoUnit > 0) {
                $valorEntrada = $costoUnit * $mov->cantidad;
                if ($saldoAcum > 0) {
                    $costoPromedio = ($valorAcum + $valorEntrada) / $saldoAcum;
                }
                $valorAcum += $valorEntrada;
            } elseif ($esSalida && $costoPromedio > 0) {
                $valorAcum = max(0, $valorAcum - ($costoPromedio * $mov->cantidad));
            }

            $valorMov   = $costoUnit !== null ? round($costoUnit * $mov->cantidad, 2) : null;
            $valorSaldo = $costoPromedio > 0 ? round($costoPromedio * max(0, $saldoAcum), 2) : null;

            $rows[] = [
                'fecha'           => $mov->created_at->format('d/m/Y H:i'),
                'tipo_movimiento' => ucfirst($mov->tipo),
                'tipo_documento'  => $tipoDoc,
                'n_documento'     => $nDoc,
                'rut_proveedor'   => $rutProv,
                'proveedor'       => $proveedor,
                'entrada'         => $entrada,
                'salida'          => $salida,
                'ajuste'          => $ajuste,
                'saldo'           => $saldoAcum,
                'costo_unitario'  => $costoUnit,
                'valor_movimiento'=> $valorMov,
                'costo_promedio'  => round($costoPromedio, 2) ?: null,
                'valor_saldo'     => $valorSaldo,
                'usuario'         => $mov->usuario?->name ?? $mov->aprobado_por ?? '—',
                'observaciones'   => $mov->motivo ?? '—',
                '_origen'         => $mov->origen,
                '_origen_id'      => $mov->origen_id,
            ];
        }

        // ── 5. Estadísticas resumen ───────────────────────────────────────
        $totalEntradas = collect($rows)->sum('entrada');
        $totalSalidas  = collect($rows)->sum('salida');
        $ultimoCosto   = collect($rows)->whereNotNull('costo_unitario')->last()['costo_unitario'] ?? null;

        return [
            'producto'        => $producto,
            'es_servicio'     => (bool) $producto->es_servicio,
            'filas'           => $rows,
            'total_entradas'  => $totalEntradas,
            'total_salidas'   => $totalSalidas,
            'saldo_final'     => $saldoAcum,
            'costo_promedio'  => round($costoPromedio, 2),
            'ultimo_costo'    => $ultimoCosto,
            'valor_inventario'=> $valorSaldo ?? 0,
            'filtros'         => $filtros,
            'generado_por'    => auth()->user()?->name,
            'generado_at'     => now()->format('d/m/Y H:i'),
        ];
    }
}
