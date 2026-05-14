<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('unidades_medida', 'factor_conversion')) {
            Schema::table('unidades_medida', function (Blueprint $table) {
                $table->decimal('factor_conversion', 15, 6)->nullable()->after('descripcion')
                      ->comment('Factor de conversión opcional (ej: CAJA 20 → 20)');
            });
        }

        $existentes = DB::table('unidades_medida')->pluck('nombre')->map(fn($n) => strtoupper(trim($n)))->toArray();

        $nuevas = [
            ['nombre' => 'METRO LINEAL',    'abreviacion' => 'MTL',  'descripcion' => 'Metro lineal',          'factor_conversion' => 1.000000],
            ['nombre' => 'CAJA 10',         'abreviacion' => 'CJ10', 'descripcion' => 'Caja de 10 unidades',   'factor_conversion' => 10.000000],
            ['nombre' => 'CAJA 20',         'abreviacion' => 'CJ20', 'descripcion' => 'Caja de 20 unidades',   'factor_conversion' => 20.000000],
            ['nombre' => 'CAJA 50',         'abreviacion' => 'CJ50', 'descripcion' => 'Caja de 50 unidades',   'factor_conversion' => 50.000000],
            ['nombre' => 'BOLSA',           'abreviacion' => 'BLS',  'descripcion' => 'Bolsa',                 'factor_conversion' => null],
            ['nombre' => 'TUBO',            'abreviacion' => 'TUB',  'descripcion' => 'Tubo',                  'factor_conversion' => null],
            ['nombre' => 'BIDON',           'abreviacion' => 'BDN',  'descripcion' => 'Bidón',                 'factor_conversion' => null],
            ['nombre' => 'SACO',            'abreviacion' => 'SCO',  'descripcion' => 'Saco',                  'factor_conversion' => null],
            ['nombre' => 'PALLET',          'abreviacion' => 'PLT',  'descripcion' => 'Pallet',                'factor_conversion' => null],
            ['nombre' => 'JUEGO',           'abreviacion' => 'JGO',  'descripcion' => 'Juego o conjunto',      'factor_conversion' => null],
        ];

        $ahora = now();
        foreach ($nuevas as $u) {
            if (!in_array(strtoupper($u['nombre']), $existentes, true)) {
                DB::table('unidades_medida')->insert(array_merge($u, [
                    'activo'     => true,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('unidades_medida')->whereIn('nombre', [
            'METRO LINEAL','CAJA 10','CAJA 20','CAJA 50',
            'BOLSA','TUBO','BIDON','SACO','PALLET','JUEGO',
        ])->delete();

        Schema::table('unidades_medida', function (Blueprint $table) {
            $table->dropColumn('factor_conversion');
        });
    }
};
