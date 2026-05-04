<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SicdExterno extends Model
{
    protected $connection = 'sicd_externa';
    protected $table      = 'solicitud_full';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    /**
     * Verifica si un código SICD existe en el sistema externo.
     */
    public static function existe(string $codigo): bool
    {
        return static::where('num_int_sol', $codigo)->exists();
    }

    /**
     * Devuelve los datos de una solicitud por código.
     */
    public static function buscar(string $codigo): ?object
    {
        return static::where('num_int_sol', $codigo)
            ->select('id', 'num_int_sol', 'centro_costo', 'estado', 'fecha_creacion')
            ->first();
    }

    /**
     * Devuelve el blob PDF de la solicitud por código, o null si no existe/está vacío.
     */
    public static function obtenerPdf(string $codigo): ?string
    {
        $row = static::where('num_int_sol', $codigo)
            ->selectRaw('pdf')
            ->first();

        if (!$row || empty($row->pdf) || strlen($row->pdf) < 100) {
            return null;
        }

        return $row->pdf;
    }

    /**
     * Indica si la solicitud tiene PDF disponible en el sistema externo.
     */
    public static function tienePdf(string $codigo): bool
    {
        return static::where('num_int_sol', $codigo)
            ->whereRaw('LENGTH(pdf) > 100')
            ->exists();
    }

    /**
     * Devuelve un mapa [codigo_sicd => estado_numerico] para un lote de códigos.
     */
    public static function estadosBulk(array $codigos): array
    {
        if (empty($codigos)) return [];
        try {
            return static::whereIn('num_int_sol', $codigos)
                ->select('num_int_sol', 'estado')
                ->get()
                ->keyBy('num_int_sol')
                ->map(fn($row) => $row->estado !== null ? (int) $row->estado : null)
                ->toArray();
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Devuelve etiqueta y colores para un estado numérico externo.
     */
    public static function etiquetaEstado(?int $estado): array
    {
        return match ($estado) {
            1 => ['texto' => '1.- Creada',                      'bg' => '#fef3c7', 'color' => '#92400e'],
            2 => ['texto' => '2.- Ejecución Abastecimiento',    'bg' => '#dbeafe', 'color' => '#1e40af'],
            3 => ['texto' => '3.- Análisis Presupuestario',     'bg' => '#ede9fe', 'color' => '#5b21b6'],
            4 => ['texto' => '4.- Refrendación Presupuestaria', 'bg' => '#fce7f3', 'color' => '#9d174d'],
            5 => ['texto' => '5.- Autorización RRFF y FF',      'bg' => '#ffedd5', 'color' => '#9a3412'],
            6 => ['texto' => '6.- Autorizada Para Compra',      'bg' => '#dcfce7', 'color' => '#166534'],
            default => ['texto' => $estado !== null ? $estado . '.- Desconocido' : '—', 'bg' => '#f3f4f6', 'color' => '#6b7280'],
        };
    }

}
