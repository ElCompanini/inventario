<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BincardExport implements FromArray, WithTitle, WithEvents, WithColumnWidths
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'BINCARD';
    }

    public function array(): array
    {
        return []; // poblado manualmente en AfterSheet
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,  // Fecha
            'B' => 18,  // Tipo Movimiento
            'C' => 18,  // Tipo Documento
            'D' => 22,  // N° Documento
            'E' => 16,  // RUT Proveedor
            'F' => 26,  // Proveedor
            'G' => 10,  // Entrada
            'H' => 10,  // Salida
            'I' => 10,  // Saldo
            'J' => 14,  // Costo Unit.
            'K' => 16,  // Valor Movimiento
            'L' => 16,  // Costo Prom.
            'M' => 16,  // Valor Saldo
            'N' => 22,  // Usuario
            'O' => 34,  // Observaciones
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $data    = $this->data;
                $producto = $data['producto'];
                $filas   = $data['filas'];
                $mostrarCostos = $data['mostrar_costos'] ?? false;

                // ── PALETA DE COLORES ─────────────────────────────────────────
                $azulOscuro  = '1E3A5F';
                $azulMedio   = '2563EB';
                $azulClaro   = 'DBEAFE';
                $grisClaro   = 'F8FAFC';
                $grisMedio   = 'E2E8F0';
                $verdeClaro  = 'DCFCE7';
                $rojoClaro   = 'FEE2E2';
                $blanco      = 'FFFFFF';
                $textoBlanco = 'FFFFFF';
                $textoOscuro = '1E293B';
                $textoGris   = '64748B';

                $row = 1;

                // ══════════════════════════════════════════════════════════════
                // FILA 1: Encabezado institucional
                // ══════════════════════════════════════════════════════════════
                $sheet->mergeCells("A{$row}:O{$row}");
                $sheet->setCellValue("A{$row}", 'SISTEMA DE GESTIÓN DE INVENTARIO — REPORTE BINCARD');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $textoBlanco]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulOscuro]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(30);
                $row++;

                // FILA 2: Subtítulo
                $sheet->mergeCells("A{$row}:O{$row}");
                $sheet->setCellValue("A{$row}", 'Documento de Trazabilidad y Control de Inventario — Uso Exclusivo Interno / Auditoría');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => $textoBlanco]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulMedio]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(16);
                $row++;

                // ── Separador vacío
                $sheet->mergeCells("A{$row}:O{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisMedio]],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(6);
                $row++;

                // ══════════════════════════════════════════════════════════════
                // FILAS 4-7: Info producto en dos columnas
                // ══════════════════════════════════════════════════════════════
                $infoLeft = [
                    ['PRODUCTO',       $producto->nombre],
                    ['CÓDIGO INTERNO', '#' . $producto->id],
                    ['CATEGORÍA',      $producto->categoria?->nombre ?? '—'],
                    ['FAMILIA',        $producto->categoria?->familia?->nombre ?? '—'],
                ];
                $infoRight = [
                    ['UNIDAD DE MEDIDA', $producto->unidad ?? '—'],
                    ['UBICACIÓN',        $producto->container?->nombre ?? '—'],
                    ['CENTRO DE COSTO',  $producto->centroCosto?->acronimo ?? '—'],
                    ['ESTADO',           $producto->activo ? 'Activo' : 'Inactivo'],
                ];

                foreach ($infoLeft as $i => [$label, $value]) {
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->setCellValue("A{$row}", $label);
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => $textoGris]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisClaro]],
                    ]);
                    $sheet->mergeCells("C{$row}:H{$row}");
                    $sheet->setCellValue("C{$row}", $value);
                    $sheet->getStyle("C{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $textoOscuro]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $blanco]],
                    ]);

                    if (isset($infoRight[$i])) {
                        [$rl, $rv] = $infoRight[$i];
                        $sheet->mergeCells("I{$row}:J{$row}");
                        $sheet->setCellValue("I{$row}", $rl);
                        $sheet->getStyle("I{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => $textoGris]],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisClaro]],
                        ]);
                        $sheet->mergeCells("K{$row}:O{$row}");
                        $sheet->setCellValue("K{$row}", $rv);
                        $sheet->getStyle("K{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $textoOscuro]],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $blanco]],
                        ]);
                    }
                    $sheet->getRowDimension($row)->setRowHeight(18);
                    $row++;
                }

                // ── Stock + Métricas
                $metricas = [
                    ['STOCK ACTUAL', $producto->stock_actual],
                    ['STOCK MÍNIMO', $producto->stock_minimo ?? '—'],
                    ['STOCK CRÍTICO', $producto->stock_critico ?? '—'],
                ];
                if ($mostrarCostos) {
                    $metricas[] = ['COSTO PROMEDIO', $data['costo_promedio'] ? '$' . number_format($data['costo_promedio'], 0, ',', '.') : '—'];
                    $metricas[] = ['VALOR INVENTARIO', $data['valor_inventario'] ? '$' . number_format($data['valor_inventario'], 0, ',', '.') : '—'];
                }

                $sheet->mergeCells("A{$row}:O{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulClaro]],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(8);
                $row++;

                // Stock en una fila
                $colIdx = 0;
                $cols = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O'];
                foreach ($metricas as $idx => [$ml, $mv]) {
                    $c1 = $cols[$colIdx * 2];
                    $c2 = $cols[$colIdx * 2 + 1];
                    $sheet->setCellValue("{$c1}{$row}", $ml);
                    $sheet->getStyle("{$c1}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => $textoGris]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisClaro]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);
                    $sheet->setCellValue("{$c2}{$row}", $mv);
                    $sheet->getStyle("{$c2}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $azulMedio]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $blanco]],
                    ]);
                    $colIdx++;
                }
                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;

                // ── Fecha emisión y generado por
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->setCellValue("A{$row}", 'Generado: ' . $data['generado_at'] . ' por ' . $data['generado_por']);
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 8, 'color' => ['rgb' => $textoGris]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisClaro]],
                ]);
                $sheet->mergeCells("H{$row}:O{$row}");
                $filtrosStr = '';
                if (!empty($data['filtros']['fecha_desde'])) $filtrosStr .= 'Desde: ' . $data['filtros']['fecha_desde'] . '  ';
                if (!empty($data['filtros']['fecha_hasta'])) $filtrosStr .= 'Hasta: ' . $data['filtros']['fecha_hasta'];
                $sheet->setCellValue("H{$row}", $filtrosStr ?: 'Sin filtros de fecha');
                $sheet->getStyle("H{$row}")->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 8, 'color' => ['rgb' => $textoGris]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisClaro]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(16);
                $row++;

                // Separador
                $sheet->mergeCells("A{$row}:O{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisMedio]]]);
                $sheet->getRowDimension($row)->setRowHeight(6);
                $row++;

                // ══════════════════════════════════════════════════════════════
                // ENCABEZADO DE TABLA PRINCIPAL
                // ══════════════════════════════════════════════════════════════
                $headerRow = $row;
                $headers = [
                    'A' => 'FECHA',
                    'B' => 'TIPO MOVIMIENTO',
                    'C' => 'TIPO DOCUMENTO',
                    'D' => 'N° DOCUMENTO',
                    'E' => 'RUT PROVEEDOR',
                    'F' => 'PROVEEDOR',
                    'G' => 'ENTRADA',
                    'H' => 'SALIDA',
                    'I' => 'SALDO',
                    'J' => 'COSTO UNIT.',
                    'K' => 'VALOR MOVIMIENTO',
                    'L' => 'COSTO PROM.',
                    'M' => 'VALOR SALDO',
                    'N' => 'USUARIO RESPONSABLE',
                    'O' => 'OBSERVACIONES',
                ];
                if (!$mostrarCostos) {
                    unset($headers['J'], $headers['K'], $headers['L'], $headers['M']);
                }

                foreach ($headers as $col => $header) {
                    $sheet->setCellValue("{$col}{$row}", $header);
                }
                $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $textoBlanco]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulOscuro]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $blanco]]],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(28);
                $row++;

                // ══════════════════════════════════════════════════════════════
                // FILAS DE MOVIMIENTOS
                // ══════════════════════════════════════════════════════════════
                $dataStartRow = $row;
                foreach ($filas as $idx => $fila) {
                    $esEntrada = $fila['entrada'] !== null;
                    $esSalida  = $fila['salida']  !== null;
                    $bgColor   = $idx % 2 === 0 ? $blanco : $grisClaro;
                    if ($esEntrada) $bgColor = $idx % 2 === 0 ? 'F0FFF4' : $verdeClaro;
                    if ($esSalida)  $bgColor = $idx % 2 === 0 ? 'FFF8F8' : $rojoClaro;

                    $values = [
                        'A' => $fila['fecha'],
                        'B' => $fila['tipo_movimiento'],
                        'C' => $fila['tipo_documento'],
                        'D' => $fila['n_documento'],
                        'E' => $fila['rut_proveedor'],
                        'F' => $fila['proveedor'],
                        'G' => $fila['entrada'],
                        'H' => $fila['salida'],
                        'I' => $fila['saldo'],
                        'J' => $fila['costo_unitario'],
                        'K' => $fila['valor_movimiento'],
                        'L' => $fila['costo_promedio'],
                        'M' => $fila['valor_saldo'],
                        'N' => $fila['usuario'],
                        'O' => $fila['observaciones'],
                    ];
                    if (!$mostrarCostos) {
                        unset($values['J'], $values['K'], $values['L'], $values['M']);
                    }

                    foreach ($values as $col => $val) {
                        $sheet->setCellValue("{$col}{$row}", $val ?? '');
                    }

                    $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $grisMedio]]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    // Alineaciones específicas
                    $sheet->getStyle("G{$row}:M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getRowDimension($row)->setRowHeight(16);

                    // Formato números
                    foreach (['G', 'H', 'I'] as $nc) {
                        $sheet->getStyle("{$nc}{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    }
                    if ($mostrarCostos) {
                        foreach (['J', 'K', 'L', 'M'] as $nc) {
                            $sheet->getStyle("{$nc}{$row}")->getNumberFormat()->setFormatCode('$#,##0');
                        }
                    }

                    $row++;
                }

                // ── Fila de totales
                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->setCellValue("A{$row}", 'TOTALES');
                $sheet->setCellValue("G{$row}", $data['total_entradas']);
                $sheet->setCellValue("H{$row}", $data['total_salidas']);
                $sheet->setCellValue("I{$row}", $data['saldo_final']);
                if ($mostrarCostos) {
                    $sheet->setCellValue("M{$row}", $data['valor_inventario'] ?: '');
                }
                $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $textoBlanco]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulMedio]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $blanco]]],
                ]);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight(22);

                // ── Congelar encabezado de tabla
                $sheet->freezePane("A{$headerRow}");

                // ── Autofilter en la tabla
                $lastData = $row - 1;
                if ($lastData > $headerRow) {
                    $sheet->setAutoFilter("A{$headerRow}:O{$lastData}");
                }

                // ── Configuración de página para impresión
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
                $sheet->getHeaderFooter()
                    ->setOddHeader('&L&B' . ($producto->nombre) . '&C&B BINCARD&R&BPág. &P de &N');
                $sheet->getHeaderFooter()
                    ->setOddFooter('&LGenerado: ' . $data['generado_at'] . '&C' . $data['generado_por'] . '&RConfidencial');
            },
        ];
    }
}
