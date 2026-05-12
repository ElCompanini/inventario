<?php

namespace App\Services;

use App\Models\ReporteriaIndexada;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReporteriaService
{
    /**
     * Registra un reporte generado en el índice.
     *
     * @param  string  $tipo     Constante tipo: BINCARD_EXCEL, BINCARD_PDF, etc.
     * @param  string  $nombre   Nombre legible: "BINCARD – CLORURO DE SODIO 0.9%"
     * @param  string  $modulo   Módulo origen: reportes, inventario, gastos_menores...
     * @param  string  $formato  EXCEL | PDF | CSV | HTML
     * @param  array   $filtros  Parámetros exactos usados en la consulta
     * @param  string|null $rutaArchivo  Ruta relativa en disco local (null = solo metadata)
     * @param  string|null $nombreArchivo  Nombre de descarga para el usuario
     * @param  string|null $notas
     */
    public function registrar(
        string  $tipo,
        string  $nombre,
        string  $modulo,
        string  $formato,
        array   $filtros       = [],
        ?string $rutaArchivo   = null,
        ?string $nombreArchivo = null,
        ?string $notas         = null,
    ): ReporteriaIndexada {
        $tamaño = null;
        $hash   = null;

        if ($rutaArchivo && Storage::disk('local')->exists($rutaArchivo)) {
            $tamaño = Storage::disk('local')->size($rutaArchivo);
            $hash   = hash_file('sha256', Storage::disk('local')->path($rutaArchivo));
        }

        $user = Auth::user();

        return ReporteriaIndexada::create([
            'tipo'           => strtoupper($tipo),
            'nombre'         => $nombre,
            'modulo'         => $modulo,
            'formato'        => strtoupper($formato),
            'usuario_id'     => $user?->id,
            'usuario_nombre' => $user?->name,
            'nombre_archivo' => $nombreArchivo,
            'ruta_archivo'   => $rutaArchivo,
            'tamaño_bytes'   => $tamaño,
            'hash_archivo'   => $hash,
            'filtros'        => $filtros ?: null,
            'estado'         => 'generado',
            'notas'          => $notas,
        ]);
    }

    /**
     * Tipos de reporte disponibles (para filtros UI).
     */
    public static function tipos(): array
    {
        return [
            'BINCARD_VISTA'      => 'BINCARD – Vista en pantalla',
            'BINCARD_EXCEL'      => 'BINCARD – Excel',
            'BINCARD_PDF'        => 'BINCARD – PDF',
            'INVENTARIO_EXCEL'   => 'Inventario – Excel',
            'INVENTARIO_PDF'     => 'Inventario – PDF',
            'GASTOS_MENORES'     => 'Gastos Menores',
            'SICD'               => 'SICD',
            'ORDENES_COMPRA'     => 'Órdenes de Compra',
            'HISTORIAL_STOCK'    => 'Historial de Stock',
            'VALORIZACION'       => 'Valorización',
            'AUDITORIA'          => 'Auditoría',
            'ACTIVIDAD_EXCEL'    => 'Actividad Reciente – Excel',
            'ACTIVIDAD_PDF'      => 'Actividad Reciente – PDF',
        ];
    }

    public static function modulos(): array
    {
        return [
            'reportes'       => 'Reportes / BINCARD',
            'inventario'     => 'Inventario',
            'gastos_menores' => 'Gastos Menores',
            'sicd'           => 'SICD',
            'ordenes'        => 'Órdenes de Compra',
            'historial'      => 'Historial',
            'dashboard'      => 'Dashboard',
        ];
    }
}
