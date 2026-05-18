<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Familia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PartesYPiezasCategoriasSeeder extends Seeder
{
    private const CATEGORIAS = [
        'Tarjeta gráfica',
        'Placa madre',
        'Memoria RAM',
        'SSD SATA',
        'SSD M.2',
        'HDD',
        'Fuente de poder',
        'Gabinete',
        'Ventilador',
        'Refrigeración líquida',
        'Disipador',
        'Pasta térmica',
        'Tarjeta de red',
        'Cable SATA',
        'Cable de Alimentación',
        'Monitor',
        'Teclado',
        'Mouse',
        'Audífonos',
        'Micrófono',
    ];

    public function run(): void
    {
        $familia = Familia::where('tipo', 'partes_piezas')->first();

        if (!$familia) {
            Log::warning('[PartesYPiezasCategoriasSeeder] Familia con tipo=partes_piezas no encontrada. Seeder omitido.');
            $this->command->warn('⚠  Familia "Partes y Piezas" no existe en BD. Seeder omitido.');
            return;
        }

        // Nombres actuales normalizados para detección de duplicados (case-insensitive)
        $existentes = Categoria::where('familia_id', $familia->id)
            ->withTrashed()
            ->pluck('nombre')
            ->map(fn($n) => mb_strtolower(trim($n)))
            ->toArray();

        $creadas  = 0;
        $omitidas = 0;

        foreach (self::CATEGORIAS as $nombre) {
            if (in_array(mb_strtolower(trim($nombre)), $existentes, true)) {
                $omitidas++;
                continue;
            }

            Categoria::create([
                'nombre'     => $nombre,
                'familia_id' => $familia->id,
                'activo'     => true,
            ]);

            $existentes[] = mb_strtolower(trim($nombre));
            $creadas++;
        }

        $this->command->info("✓ Partes y Piezas [{$familia->nombre}]: {$creadas} categorías creadas, {$omitidas} ya existían.");
    }
}
