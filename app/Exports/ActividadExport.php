<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ActividadExport implements FromArray, WithTitle, WithEvents, WithColumnWidths
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string { return 'Actividad Reciente'; }
    public function array(): array  { return []; }

    public function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 8,  'C' => 11, 'D' => 32,
            'E' => 20, 'F' => 16, 'G' => 10, 'H' => 18,
            'I' => 20, 'J' => 22, 'K' => 42,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $d      = $this->data;
                $filas  = $d['filas'];

                $azulOscuro = '1E3A5F';
                $azulMedio  = '2563EB';
                $grisMedio  = 'E2E8F0';
                $grisClaro  = 'F8FAFC';
                $verdeClaro = 'DCFCE7';
                $rojoClaro  = 'FEE2E2';
                $blanco     = 'FFFFFF';
                $txtBlanco  = 'FFFFFF';
                $txtOscuro  = '1E293B';
                $txtGris    = '64748B';
                $lastCol    = 'K';

                $row = 1;

                // ── Título ────────────────────────────────────────────────────
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->setCellValue("A{$row}", 'SISTEMA DE GESTIÓN DE INVENTARIO — REPORTE ACTIVIDAD RECIENTE');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => $txtBlanco]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulOscuro]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(28);
                $row++;

                // ── Subtítulo ─────────────────────────────────────────────────
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->setCellValue("A{$row}", 'Historial de Movimientos de Inventario — Uso Interno / Auditoría');
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => $txtBlanco]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulMedio]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(14);
                $row++;

                // ── Separador ─────────────────────────────────────────────────
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisMedio]]]);
                $sheet->getRowDimension($row)->setRowHeight(5);
                $row++;

                // ── Metadata ──────────────────────────────────────────────────
                foreach ([
                    ['Período',           $d['desde'] . ' → ' . $d['hasta']],
                    ['Total movimientos',  $d['total'] . ' registros'],
                    ['Generado por',       $d['generado_por']],
                    ['Fecha generación',   $d['generado_at']],
                ] as [$label, $val]) {
                    $sheet->setCellValue("A{$row}", $label);
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => $txtGris]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisClaro]],
                    ]);
                    $sheet->mergeCells("B{$row}:{$lastCol}{$row}");
                    $sheet->setCellValue("B{$row}", $val);
                    $sheet->getStyle("B{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $txtOscuro]],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(16);
                    $row++;
                }

                // ── Separador ─────────────────────────────────────────────────
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->getStyle("A{$row}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $grisMedio]]]);
                $sheet->getRowDimension($row)->setRowHeight(5);
                $row++;

                // ── Encabezado tabla ──────────────────────────────────────────
                $headerRow = $row;
                $headers = [
                    'A' => 'FECHA', 'B' => 'HORA', 'C' => 'TIPO',
                    'D' => 'PRODUCTO', 'E' => 'CATEGORÍA', 'F' => 'MARCA',
                    'G' => 'CANTIDAD', 'H' => 'MÓDULO', 'I' => 'DOCUMENTO',
                    'J' => 'USUARIO', 'K' => 'OBSERVACIONES',
                ];
                foreach ($headers as $col => $h) {
                    $sheet->setCellValue("{$col}{$row}", $h);
                }
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $txtBlanco]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulOscuro]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $blanco]]],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(24);
                $row++;

                // ── Filas de datos ────────────────────────────────────────────
                foreach ($filas as $idx => $f) {
                    $isEntrada = $f['tipo'] === 'entrada';
                    if ($isEntrada) {
                        $bg = $idx % 2 === 0 ? 'F0FFF4' : $verdeClaro;
                    } else {
                        $bg = $idx % 2 === 0 ? 'FFF8F8' : $rojoClaro;
                    }

                    $vals = [
                        'A' => $f['fecha'],        'B' => $f['hora'],
                        'C' => strtoupper($f['tipo_label']),
                        'D' => $f['producto'],     'E' => $f['categoria'],
                        'F' => $f['marca'],        'G' => $f['cantidad'],
                        'H' => $f['modulo'],       'I' => $f['documento'],
                        'J' => $f['usuario'],      'K' => $f['observaciones'],
                    ];
                    foreach ($vals as $col => $val) {
                        $sheet->setCellValue("{$col}{$row}", $val ?? '—');
                    }
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $grisMedio]]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getRowDimension($row)->setRowHeight(15);
                    $row++;
                }

                // ── Fila de totales ───────────────────────────────────────────
                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->setCellValue("A{$row}", 'TOTAL: ' . count($filas) . ' movimientos');
                $sheet->setCellValue("G{$row}", array_sum(array_column($filas, 'cantidad')));
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $txtBlanco]],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulMedio]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $blanco]]],
                ]);
                $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getRowDimension($row)->setRowHeight(20);

                // ── Freeze + autofilter ───────────────────────────────────────
                $sheet->freezePane("A{$headerRow}");
                $lastData = $row - 1;
                if ($lastData > $headerRow) {
                    $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$lastData}");
                }

                // ── Configuración impresión ───────────────────────────────────
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
                $sheet->getHeaderFooter()
                    ->setOddHeader('&L&B Actividad Reciente &C&B INVENTARIO &R&B Pág. &P de &N');
                $sheet->getHeaderFooter()
                    ->setOddFooter('&L Generado: ' . $d['generado_at'] . ' &C ' . $d['generado_por'] . ' &R Confidencial');
            },
        ];
    }
}
