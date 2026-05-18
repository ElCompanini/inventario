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
            ->with(['usuario:id,name', 'usuarioEjecutor:id,name'])
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
        if (!empty($filtros['origen'])) {
            $query->where('origen', $filtros['origen']);
        }
        if (!empty($filtros['registrado_por'])) {
            $query->where('aprobado_por', 'like', '%' . $filtros['registrado_por'] . '%');
        }
        if (!empty($filtros['usuario'])) {
            $query->whereHas('usuario', fn($q) => $q->where('name', 'like', '%' . $filtros['usuario'] . '%'));
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

        // ── 4. Construir filas BINCARD con saldo acumulado ────────────────
        $saldoAcum    = 0;
        $costoPromedio = 0.0;
        $valorAcum    = 0.0;
        $rows         = [];

        foreach ($movimientos as $mov) {
            // Determinar entrada / salida
            // 'devolucion' suma stock → se muestra en columna Entrada del kardex
            $esEntrada = in_array($mov->tipo, ['entrada', 'devolucion']);
            $esSalida  = in_array($mov->tipo, ['salida', 'retiro', 'merma']);

            $entrada = $esEntrada ? $mov->cantidad : null;
            $salida  = $esSalida  ? $mov->cantidad : null;
            $ajuste  = (!$esEntrada && !$esSalida) ? $mov->cantidad : null;

            $saldoAntes = $saldoAcum; // capturar ANTES del movimiento para auditoría
            $saldoAcum += $esEntrada ? $mov->cantidad : ($esSalida ? -$mov->cantidad : 0);

            // ── Enriquecer con documento y proveedor ──────────────────────
            $tipoDoc    = '—';
            $nDoc       = '—';
            $nRef       = null;  // documento padre / referencia
            $rutProv    = '—';
            $proveedor  = '—';
            $costoUnit  = null;

            if ($mov->origen === 'sicd' && isset($sicds[$mov->origen_id])) {
                $sicd      = $sicds[$mov->origen_id];
                $sicdLabel = 'SICD ' . $sicd->codigo_sicd;

                // ── Granularidad: cada movimiento tiene su propia OC ──────
                // Prioridad 1: orden_compra_id directo en el movimiento (registros nuevos)
                // Prioridad 2: fallback por motivo o todas las OC (registros históricos)

                if ($mov->orden_compra_id && isset($ocsDirectas[$mov->orden_compra_id])) {
                    // Origen directo = OC · Referencia = SICD
                    $ocDirecta = $ocsDirectas[$mov->orden_compra_id];
                    $tipoDoc   = 'OC';
                    $nDoc      = 'OC ' . $ocDirecta->numero_oc;
                    $nRef      = $sicdLabel;
                    $rutProv   = $ocDirecta->api_proveedor_rut   ?? '—';
                    $proveedor = $ocDirecta->api_proveedor_nombre ?? '—';

                } else {
                    // ⚠️ Fallback para registros históricos sin orden_compra_id
                    $ocFromMotivo = null;
                    if (preg_match('/OC\s+([\w\-\.]+)/i', $mov->motivo ?? '', $m)) {
                        $ocFromMotivo = $ocsPorSicd[$sicd->id]?->ordenesCompra
                            ->firstWhere('numero_oc', $m[1]);
                    }

                    if ($ocFromMotivo) {
                        $tipoDoc   = 'OC';
                        $nDoc      = 'OC ' . $ocFromMotivo->numero_oc;
                        $nRef      = $sicdLabel;
                        $rutProv   = $ocFromMotivo->api_proveedor_rut   ?? '—';
                        $proveedor = $ocFromMotivo->api_proveedor_nombre ?? '—';
                    } else {
                        // Sin OC asociada: el documento directo es la SICD
                        $todasOcs = $ocsPorSicd[$sicd->id]?->ordenesCompra ?? collect();
                        $tipoDoc  = $todasOcs->isNotEmpty() ? 'SICD / OC' : 'SICD';
                        $nDoc     = $sicdLabel;
                        if ($todasOcs->count() === 1) {
                            $nRef = 'OC ' . $todasOcs->first()->numero_oc;
                        } elseif ($todasOcs->count() > 1) {
                            $nRef = 'OC: ' . $todasOcs->pluck('numero_oc')->filter()->join(' / ');
                        }
                        $rutProv   = $todasOcs->pluck('api_proveedor_rut')->filter()->unique()->join(' / ') ?: '—';
                        $proveedor = $todasOcs->pluck('api_proveedor_nombre')->filter()->unique()->join(' / ') ?: ($sicd->proveedor_nombre ?? '—');
                    }
                }

                // Si aún no hay proveedor (SICD sin OC), usar datos del SICD
                if ($proveedor === '—' && $sicd->proveedor_nombre) {
                    $proveedor = $sicd->proveedor_nombre;
                    $rutProv   = $sicd->rut_proveedor ?? $rutProv;
                }

                // Costo: lookup granular por SICD + OC específica (prioridad 1)
                $precioKey = 'Sicd|' . $sicd->id . '|' . ($mov->orden_compra_id ?? '');
                $precio = $preciosPorOrigenOc[$precioKey]
                    ?? $preciosPorOrigen->get('Sicd|' . $sicd->id)?->first();
                if ($precio) $costoUnit = round($precio->precio_neto * 1.19, 2);

            } elseif ($mov->origen === 'gasto_menor' && isset($gastos[$mov->origen_id])) {
                $gasto        = $gastos[$mov->origen_id];
                $tipoDoc      = 'Compra Directa';
                $nDoc         = 'GM-' . str_pad($gasto->id_gm, 4, '0', STR_PAD_LEFT);
                $nRef         = $gasto->folio ? 'Folio ' . $gasto->folio : null;
                $rutProv      = $gasto->rut_proveedor;
                $proveedor    = $gasto->proveedor_nombre ?? '—';
                $precioNetaGM = $gasto->precio_neto ?: ($gasto->cantidad > 0 ? round($gasto->monto / $gasto->cantidad, 2) : null);
                $costoUnit    = $precioNetaGM !== null ? round($precioNetaGM * 1.19, 2) : null;

            } elseif ($mov->origen === 'solicitud' && $mov->origen_id) {
                $solCode = 'SOL-' . str_pad($mov->origen_id, 6, '0', STR_PAD_LEFT);
                if ($mov->tipo === 'devolucion') {
                    $tipoDoc = 'Devolución';
                    $nDoc    = 'DEV-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT);
                    $nRef    = $solCode;
                } else {
                    $tipoDoc = 'Solicitud';
                    $nDoc    = $solCode;
                }
            } elseif ($mov->tipo === 'ajuste') {
                $tipoDoc = 'Ajuste Manual';
                $nDoc    = 'AJU-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT);
            } elseif ($mov->tipo === 'traslado') {
                $tipoDoc = 'Traslado';
                $nDoc    = 'MOV-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT);
            } elseif ($mov->tipo === 'entrada') {
                $tipoDoc = 'Entrada Manual';
                $nDoc    = 'AJU-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT);
            } elseif ($mov->tipo === 'salida') {
                $tipoDoc = 'Salida Manual';
                $nDoc    = 'RET-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT);
            } else {
                $tipoDoc = 'Movimiento Interno';
                $nDoc    = 'AJU-' . str_pad($mov->id, 6, '0', STR_PAD_LEFT);
            }

            // ── Prefer stored traceability fields over computed values ────
            if (!empty($mov->doc_origen))    $nDoc = $mov->doc_origen;
            if (!empty($mov->doc_referencia)) $nRef = $mov->doc_referencia;

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

            // Etiqueta de origen: legacy match como base, luego origen_tipo tiene prioridad
            $origenLabel = match(true) {
                $mov->origen === 'sicd'              => 'Compra',
                $mov->origen === 'gasto_menor'       => 'Compra',
                $mov->origen === 'solicitud' && $mov->tipo === 'devolucion' => 'Devolución',
                $mov->origen === 'solicitud'         => 'Solicitud',
                $mov->origen === 'computador_armado' => 'Armado',
                $mov->tipo   === 'traslado'          => 'Traslado',
                $mov->tipo   === 'ajuste'            => 'Ajuste',
                $mov->tipo   === 'merma'             => 'Ajuste',
                default                              => 'Manual',
            };

            // origen_tipo siempre tiene prioridad sobre el match legacy
            if (!empty($mov->origen_tipo)) {
                $origenLabel = match($mov->origen_tipo) {
                    'solicitud'         => 'Solicitud',
                    'devolucion'        => 'Devolución',
                    'retiro_directo'    => 'Manual',
                    'sicd'             => 'Compra',
                    'orden_compra'     => 'Compra',
                    'gasto_menor'      => 'Compra',
                    'computador_armado'=> 'Armado',
                    'ajuste'           => 'Ajuste',
                    'traslado'         => 'Traslado',
                    'entrada_manual'   => 'Manual',
                    'merma'            => 'Ajuste',
                    default            => $origenLabel,
                };
            }

            $rows[] = [
                'fecha'            => $mov->created_at->format('d/m/Y H:i'),
                'tipo_movimiento'  => ucfirst($mov->tipo),
                'origen_label'     => $origenLabel,
                'tipo_documento'   => $tipoDoc,
                'n_documento'      => $nDoc,
                'n_referencia'     => $nRef,
                'rut_proveedor'    => $rutProv,
                'proveedor'        => $proveedor,
                // Prefer DB-stored stock_anterior (ground truth) over computed running balance
                'stock_anterior'   => $mov->stock_anterior ?? $saldoAntes,
                'entrada'          => $entrada,
                'salida'           => $salida,
                'ajuste'           => $ajuste,
                'saldo'            => $saldoAcum,
                'costo_unitario'   => $costoUnit,
                'valor_movimiento' => $valorMov,
                'costo_promedio'   => round($costoPromedio, 2) ?: null,
                'valor_saldo'      => $valorSaldo,
                'usuario'          => $mov->usuario?->name ?? '—',
                // Prefer FK executor over stored name string
                'registrado_por'   => $mov->usuarioEjecutor?->name ?? $mov->aprobado_por ?? $mov->usuario?->name ?? '—',
                'codigo_movimiento'=> $mov->codigo_movimiento,
                'observaciones'    => $mov->motivo ?? '—',
                '_origen'          => $mov->origen,
                '_origen_id'       => $mov->origen_id,
                '_mov_id'          => $mov->id,
                'origen_tipo'      => $mov->origen_tipo,
            ];
        }

        // ── 5. Post-filtros sobre datos enriquecidos ─────────────────────
        if (!empty($filtros['proveedor_filtro'])) {
            $pf = mb_strtolower($filtros['proveedor_filtro']);
            $rows = array_values(array_filter($rows, fn($r) => str_contains(mb_strtolower($r['proveedor']), $pf)));
        }
        if (!empty($filtros['n_documento_filtro'])) {
            $nf = mb_strtolower($filtros['n_documento_filtro']);
            $rows = array_values(array_filter($rows, fn($r) => str_contains(mb_strtolower($r['n_documento']), $nf)));
        }

        // ── 6. Estadísticas resumen ───────────────────────────────────────
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
