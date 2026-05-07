<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_medida_id')->nullable()->after('unidad');
            $table->index('unidad_medida_id', 'idx_productos_unidad_medida');
        });

        // Migrar datos existentes: texto libre → FK normalizado
        $mapeo = [
            'UNID' => ['UNID','UNIDAD','UND','UNI','UN','U','UNIDADES','UNIT'],
            'KG'   => ['KG','KILO','KILOGRAMO','KILOS','KILOGRAMOS'],
            'GR'   => ['GR','GRAMO','GRAMOS','G'],
            'LT'   => ['LT','LITRO','LITROS','L','LTR'],
            'ML'   => ['ML','MILILITRO','MILILITROS'],
            'MT'   => ['MT','METRO','METROS','M'],
            'CM'   => ['CM','CENTIMETRO','CENTIMETROS'],
            'M2'   => ['M2','METRO CUADRADO','MT2'],
            'M3'   => ['M3','METRO CUBICO','MT3'],
            'CJA'  => ['CAJA','CAJAS','CJA'],
            'PKT'  => ['PAQUETE','PAQUETES','PKT','PAQ'],
            'KIT'  => ['KIT','KITS'],
            'PAR'  => ['PAR','PARES'],
            'DOC'  => ['DOCENA','DOCENAS','DOC'],
            'ROL'  => ['ROLLO','ROLLOS','ROL'],
            'TON'  => ['TON','TONELADA','TONELADAS'],
            'SET'  => ['SET','SETS'],
            'SRV'  => ['SERVICIO','SERVICIOS','SRV'],
            'HR'   => ['HR','HORA','HORAS','HRS'],
        ];

        $unidadesDb = DB::table('unidades_medida')->pluck('id', 'abreviacion');

        foreach ($mapeo as $abrev => $variantes) {
            if (!isset($unidadesDb[$abrev])) continue;
            DB::table('productos')
                ->whereRaw('UPPER(TRIM(COALESCE(unidad,""))) IN (' . implode(',', array_fill(0, count($variantes), '?')) . ')', $variantes)
                ->whereNull('unidad_medida_id')
                ->update(['unidad_medida_id' => $unidadesDb[$abrev]]);
        }

        // Fallback: productos con unidad no mapeada → UNIDAD genérica
        if (isset($unidadesDb['UNID'])) {
            DB::table('productos')
                ->whereNotNull('unidad')
                ->whereNull('unidad_medida_id')
                ->update(['unidad_medida_id' => $unidadesDb['UNID']]);
        }
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('idx_productos_unidad_medida');
            $table->dropColumn('unidad_medida_id');
        });
    }
};
