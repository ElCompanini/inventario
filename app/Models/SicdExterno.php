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
            1 => ['texto' => '1.- Creada',                      'bg' => '#fef3c7', 'color' => '#92400e', 'dark_bg' => '#422006', 'dark_color' => '#fde68a'],
            2 => ['texto' => '2.- Ejecución Abastecimiento',    'bg' => '#dbeafe', 'color' => '#1e40af', 'dark_bg' => '#1e3a5f', 'dark_color' => '#93c5fd'],
            3 => ['texto' => '3.- Análisis Presupuestario',     'bg' => '#ede9fe', 'color' => '#5b21b6', 'dark_bg' => '#3b0764', 'dark_color' => '#c4b5fd'],
            4 => ['texto' => '4.- Refrendación Presupuestaria', 'bg' => '#fce7f3', 'color' => '#9d174d', 'dark_bg' => '#4a0520', 'dark_color' => '#f9a8d4'],
            5 => ['texto' => '5.- Autorización RRFF y FF',      'bg' => '#ffedd5', 'color' => '#9a3412', 'dark_bg' => '#431407', 'dark_color' => '#fdba74'],
            6 => ['texto' => '6.- Autorizada Para Compra',      'bg' => '#dcfce7', 'color' => '#166534', 'dark_bg' => '#052e16', 'dark_color' => '#86efac'],
            default => ['texto' => $estado !== null ? $estado . '.- Desconocido' : '—', 'bg' => '#f3f4f6', 'color' => '#6b7280', 'dark_bg' => '#374151', 'dark_color' => '#9ca3af'],
        };
    }

}
