<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades_medida', function (Blueprint $table) {
            $table->boolean('es_presentacion')->default(false)->after('abreviacion');
        });

        // Mark units that are package/container types — already handled by "Tipo de paquete"
        DB::table('unidades_medida')
            ->whereIn('nombre', [
                'BIDON', 'BOLSA', 'CAJA', 'CAJA 10', 'CAJA 20', 'CAJA 50',
                'DOCENA', 'JUEGO', 'KIT', 'PALLET', 'PAQUETE',
                'ROLLO', 'SACO', 'SET', 'TUBO', 'PAR',
            ])
            ->update(['es_presentacion' => true]);
    }

    public function down(): void
    {
        Schema::table('unidades_medida', function (Blueprint $table) {
            $table->dropColumn('es_presentacion');
        });
    }
};
