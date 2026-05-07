<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();        // UNIDAD, KILOGRAMO, LITRO …
            $table->string('abreviacion', 20)->unique();   // UNID, KG, LT …
            $table->string('descripcion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Seed de unidades comunes en mayúsculas
        $unidades = [
            ['nombre' => 'UNIDAD',          'abreviacion' => 'UNID',  'descripcion' => 'Unidad genérica'],
            ['nombre' => 'KILOGRAMO',        'abreviacion' => 'KG',    'descripcion' => 'Kilogramo'],
            ['nombre' => 'GRAMO',            'abreviacion' => 'GR',    'descripcion' => 'Gramo'],
            ['nombre' => 'LITRO',            'abreviacion' => 'LT',    'descripcion' => 'Litro'],
            ['nombre' => 'MILILITRO',        'abreviacion' => 'ML',    'descripcion' => 'Mililitro'],
            ['nombre' => 'METRO',            'abreviacion' => 'MT',    'descripcion' => 'Metro lineal'],
            ['nombre' => 'CENTIMETRO',       'abreviacion' => 'CM',    'descripcion' => 'Centímetro'],
            ['nombre' => 'METRO CUADRADO',   'abreviacion' => 'M2',    'descripcion' => 'Metro cuadrado'],
            ['nombre' => 'METRO CUBICO',     'abreviacion' => 'M3',    'descripcion' => 'Metro cúbico'],
            ['nombre' => 'CAJA',             'abreviacion' => 'CJA',   'descripcion' => 'Caja'],
            ['nombre' => 'PAQUETE',          'abreviacion' => 'PKT',   'descripcion' => 'Paquete'],
            ['nombre' => 'KIT',              'abreviacion' => 'KIT',   'descripcion' => 'Kit o conjunto'],
            ['nombre' => 'PAR',              'abreviacion' => 'PAR',   'descripcion' => 'Par'],
            ['nombre' => 'DOCENA',           'abreviacion' => 'DOC',   'descripcion' => 'Docena (12 unidades)'],
            ['nombre' => 'ROLLO',            'abreviacion' => 'ROL',   'descripcion' => 'Rollo'],
            ['nombre' => 'TONELADA',         'abreviacion' => 'TON',   'descripcion' => 'Tonelada métrica'],
            ['nombre' => 'SET',              'abreviacion' => 'SET',   'descripcion' => 'Set o juego'],
            ['nombre' => 'SERVICIO',         'abreviacion' => 'SRV',   'descripcion' => 'Servicio'],
            ['nombre' => 'HORA',             'abreviacion' => 'HR',    'descripcion' => 'Hora'],
            ['nombre' => 'DIA',              'abreviacion' => 'DIA',   'descripcion' => 'Día'],
        ];

        $now = now();
        foreach ($unidades as &$u) {
            $u['created_at'] = $now;
            $u['updated_at'] = $now;
        }
        DB::table('unidades_medida')->insert($unidades);
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_medida');
    }
};
