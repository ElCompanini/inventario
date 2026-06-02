<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Familia;
use App\Models\Marca;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PartesYPiezasProductosSeeder extends Seeder
{
    public function run(): void
    {
        $familia = Familia::where('tipo', 'partes_piezas')->first();
        if (!$familia) {
            $this->command->warn('Familia partes_piezas no encontrada.');
            return;
        }

        $familiaId = $familia->id;
        $cats      = Categoria::where('familia_id', $familiaId)->pluck('id', 'nombre');
        $marcas    = Marca::withTrashed()->pluck('id', 'nombre');

        $productos = [
            // Tarjeta gráfica
            ['nombre' => 'NVIDIA GeForce RTX 4070',              'categoria' => 'Tarjeta gráfica',      'marca' => 'NVIDIA'],
            ['nombre' => 'NVIDIA GeForce RTX 4060',              'categoria' => 'Tarjeta gráfica',      'marca' => 'NVIDIA'],
            ['nombre' => 'AMD Radeon RX 7700 XT',                'categoria' => 'Tarjeta gráfica',      'marca' => 'AMD'],
            ['nombre' => 'NVIDIA GeForce RTX 3060',              'categoria' => 'Tarjeta gráfica',      'marca' => 'NVIDIA'],
            ['nombre' => 'AMD Radeon RX 6600 XT',                'categoria' => 'Tarjeta gráfica',      'marca' => 'AMD'],
            // Placa madre
            ['nombre' => 'ASUS ROG Strix B650-A Gaming',         'categoria' => 'Placa madre',          'marca' => 'ASUS'],
            ['nombre' => 'MSI MAG B650 Tomahawk WiFi',           'categoria' => 'Placa madre',          'marca' => 'MSI'],
            ['nombre' => 'GIGABYTE B650 AORUS Elite AX',         'categoria' => 'Placa madre',          'marca' => 'GIGABYTE'],
            ['nombre' => 'ASRock B650M Pro RS',                  'categoria' => 'Placa madre',          'marca' => 'ASRock'],
            ['nombre' => 'ASUS PRIME Z790-P WiFi',               'categoria' => 'Placa madre',          'marca' => 'ASUS'],
            // Memoria RAM
            ['nombre' => 'Kingston Fury Beast DDR5 16GB 6000MHz','categoria' => 'Memoria RAM',          'marca' => 'Kingston'],
            ['nombre' => 'Corsair Vengeance DDR5 32GB 6000MHz',  'categoria' => 'Memoria RAM',          'marca' => 'Corsair'],
            ['nombre' => 'Kingston Fury Beast DDR4 16GB 3200MHz','categoria' => 'Memoria RAM',          'marca' => 'Kingston'],
            ['nombre' => 'Corsair Vengeance LPX DDR4 8GB 3200MHz','categoria' => 'Memoria RAM',         'marca' => 'Corsair'],
            ['nombre' => 'Kingston ValueRAM DDR4 8GB 2666MHz',   'categoria' => 'Memoria RAM',          'marca' => 'Kingston'],
            // SSD SATA
            ['nombre' => 'Samsung 870 EVO SATA 1TB',             'categoria' => 'SSD SATA',             'marca' => 'Samsung'],
            ['nombre' => 'Samsung 870 EVO SATA 500GB',           'categoria' => 'SSD SATA',             'marca' => 'Samsung'],
            ['nombre' => 'Kingston A400 SATA 240GB',             'categoria' => 'SSD SATA',             'marca' => 'Kingston'],
            ['nombre' => 'Kingston A400 SATA 480GB',             'categoria' => 'SSD SATA',             'marca' => 'Kingston'],
            // SSD M.2
            ['nombre' => 'Samsung 980 Pro NVMe M.2 1TB',         'categoria' => 'SSD M.2',              'marca' => 'Samsung'],
            ['nombre' => 'Samsung 970 EVO Plus NVMe M.2 500GB',  'categoria' => 'SSD M.2',              'marca' => 'Samsung'],
            ['nombre' => 'Western Digital SN850X NVMe 1TB',      'categoria' => 'SSD M.2',              'marca' => 'Western Digital'],
            ['nombre' => 'Kingston KC3000 NVMe M.2 512GB',       'categoria' => 'SSD M.2',              'marca' => 'Kingston'],
            // HDD
            ['nombre' => 'Seagate Barracuda 2TB 7200rpm',        'categoria' => 'HDD',                  'marca' => 'Seagate'],
            ['nombre' => 'Western Digital Blue 1TB 7200rpm',     'categoria' => 'HDD',                  'marca' => 'Western Digital'],
            ['nombre' => 'Seagate Barracuda 4TB 7200rpm',        'categoria' => 'HDD',                  'marca' => 'Seagate'],
            ['nombre' => 'Western Digital Red Plus 2TB NAS',     'categoria' => 'HDD',                  'marca' => 'Western Digital'],
            // Fuente de poder
            ['nombre' => 'Seasonic Focus GX-750 80+ Gold',       'categoria' => 'Fuente de poder',      'marca' => 'Seasonic'],
            ['nombre' => 'Corsair RM850x 80+ Gold Modular',      'categoria' => 'Fuente de poder',      'marca' => 'Corsair'],
            ['nombre' => 'be quiet! Pure Power 11 600W',         'categoria' => 'Fuente de poder',      'marca' => 'be quiet!'],
            ['nombre' => 'EVGA SuperNOVA 650 G6 80+ Gold',       'categoria' => 'Fuente de poder',      'marca' => 'EVGA'],
            ['nombre' => 'Seasonic Focus GX-850 80+ Gold',       'categoria' => 'Fuente de poder',      'marca' => 'Seasonic'],
            // Gabinete
            ['nombre' => 'NZXT H510 Mid-Tower',                  'categoria' => 'Gabinete',             'marca' => 'NZXT'],
            ['nombre' => 'Fractal Design Meshify C ATX',         'categoria' => 'Gabinete',             'marca' => 'Fractal Design'],
            ['nombre' => 'Cooler Master MasterBox Q300L',        'categoria' => 'Gabinete',             'marca' => 'Cooler Master'],
            ['nombre' => 'NZXT H7 Flow Mid-Tower',               'categoria' => 'Gabinete',             'marca' => 'NZXT'],
            // Ventilador
            ['nombre' => 'Cooler Master SickleFlow 120mm ARGB',  'categoria' => 'Ventilador',           'marca' => 'Cooler Master'],
            ['nombre' => 'Corsair LL120 RGB 120mm',              'categoria' => 'Ventilador',           'marca' => 'Corsair'],
            ['nombre' => 'Noctua NF-A12x25 120mm',              'categoria' => 'Ventilador',           'marca' => 'Noctua'],
            ['nombre' => 'be quiet! Silent Wings 3 120mm',       'categoria' => 'Ventilador',           'marca' => 'be quiet!'],
            // Refrigeración líquida
            ['nombre' => 'Corsair H100i Elite Capellix 240mm',   'categoria' => 'Refrigeración líquida','marca' => 'Corsair'],
            ['nombre' => 'NZXT Kraken X63 280mm',                'categoria' => 'Refrigeración líquida','marca' => 'NZXT'],
            ['nombre' => 'Cooler Master MasterLiquid 240L RGB',  'categoria' => 'Refrigeración líquida','marca' => 'Cooler Master'],
            // Disipador
            ['nombre' => 'Noctua NH-D15 Dual Tower',             'categoria' => 'Disipador',            'marca' => 'Noctua'],
            ['nombre' => 'Cooler Master Hyper 212 Black Edition', 'categoria' => 'Disipador',            'marca' => 'Cooler Master'],
            ['nombre' => 'be quiet! Dark Rock Pro 4',            'categoria' => 'Disipador',            'marca' => 'be quiet!'],
            // Pasta térmica
            ['nombre' => 'Arctic MX-4 Pasta Térmica 4g',         'categoria' => 'Pasta térmica',        'marca' => 'Arctic'],
            ['nombre' => 'Noctua NT-H1 Pasta Térmica 3.5g',      'categoria' => 'Pasta térmica',        'marca' => 'Noctua'],
            // Tarjeta de red
            ['nombre' => 'TP-Link Archer T6E WiFi PCIe AC1300',  'categoria' => 'Tarjeta de red',       'marca' => 'TP-Link'],
            ['nombre' => 'TP-Link TG-3468 Gigabit PCIe',         'categoria' => 'Tarjeta de red',       'marca' => 'TP-Link'],
            // Cable SATA
            ['nombre' => 'Cable SATA III 50cm',                  'categoria' => 'Cable SATA',           'marca' => null],
            ['nombre' => 'Cable SATA III 90cm',                  'categoria' => 'Cable SATA',           'marca' => null],
            // Cable de Alimentación
            ['nombre' => 'Cable ATX 24 pines 30cm',              'categoria' => 'Cable de Alimentación','marca' => null],
            ['nombre' => 'Cable PCIe 6+2 pines 45cm',            'categoria' => 'Cable de Alimentación','marca' => null],
            ['nombre' => 'Cable SATA Power 4 conectores',        'categoria' => 'Cable de Alimentación','marca' => null],
            // Monitor
            ['nombre' => 'LG 27" IPS 4K UltraFine 27UL650',     'categoria' => 'Monitor',              'marca' => 'LG'],
            ['nombre' => 'Samsung 24" FHD 144Hz S24F354',        'categoria' => 'Monitor',              'marca' => 'Samsung Monitor'],
            ['nombre' => 'BenQ GW2780 27" IPS FHD',             'categoria' => 'Monitor',              'marca' => 'BenQ'],
            ['nombre' => 'AOC 24G2 23.8" IPS 144Hz',            'categoria' => 'Monitor',              'marca' => 'AOC'],
            ['nombre' => 'LG 32" QHD 165Hz 32GP850',            'categoria' => 'Monitor',              'marca' => 'LG'],
            // Teclado
            ['nombre' => 'Logitech G413 SE Mecánico TKL',        'categoria' => 'Teclado',              'marca' => 'Logitech'],
            ['nombre' => 'Razer Ornata V3 Mecha-Membrana',       'categoria' => 'Teclado',              'marca' => 'Razer'],
            ['nombre' => 'HyperX Alloy Origins Core TKL',        'categoria' => 'Teclado',              'marca' => 'HyperX'],
            ['nombre' => 'SteelSeries Apex 3 TKL',               'categoria' => 'Teclado',              'marca' => 'SteelSeries'],
            // Mouse
            ['nombre' => 'Logitech G305 Wireless Lightspeed',    'categoria' => 'Mouse',                'marca' => 'Logitech'],
            ['nombre' => 'Razer DeathAdder V3 Ergonómico',       'categoria' => 'Mouse',                'marca' => 'Razer'],
            ['nombre' => 'SteelSeries Rival 3 Gaming',           'categoria' => 'Mouse',                'marca' => 'SteelSeries'],
            // Audífonos
            ['nombre' => 'HyperX Cloud II Wireless',             'categoria' => 'Audífonos',            'marca' => 'HyperX'],
            ['nombre' => 'Razer Kraken X Ultralight',            'categoria' => 'Audífonos',            'marca' => 'Razer'],
            ['nombre' => 'Corsair HS55 Surround',                'categoria' => 'Audífonos',            'marca' => 'Corsair'],
            // Micrófono
            ['nombre' => 'Blue Yeti USB Profesional',            'categoria' => 'Micrófono',            'marca' => 'Blue Microphones'],
        ];

        $creados = 0;
        $now     = now();

        foreach ($productos as $data) {
            $catId   = $cats[$data['categoria']] ?? null;
            $marcaId = $data['marca'] ? ($marcas[$data['marca']] ?? null) : null;

            if (!$catId) {
                $this->command->warn("Categoría no encontrada: {$data['categoria']}");
                continue;
            }

            DB::table('productos')->insert([
                'nombre'        => $data['nombre'],
                'unidad'        => 'Unidad',
                'stock_actual'  => 0,
                'stock_minimo'  => 0,
                'stock_critico' => 0,
                'familia_id'    => $familiaId,
                'categoria_id'  => $catId,
                'marca_id'      => $marcaId,
                'activo'        => true,
                'es_servicio'   => false,
                'tipo_item'     => 'producto',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $creados++;
        }

        $this->command->info("✓ {$creados} productos de Partes y Piezas creados.");
    }
}
