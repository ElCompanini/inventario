<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->decimal('precio_neto_original', 12, 2)->nullable()->after('total_neto');
            $table->decimal('total_neto_original',  12, 2)->nullable()->after('precio_neto_original');
        });

        // Backfill: los registros existentes conservan su valor actual como referencial.
        // Para SICDs ya procesados donde sicd_detalle fue sobreescrito, esto es lo mejor
        // que podemos hacer retroactivamente.
        DB::statement('UPDATE sicd_detalles SET precio_neto_original = precio_neto, total_neto_original = total_neto WHERE total_neto_original IS NULL');
    }

    public function down(): void
    {
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->dropColumn(['precio_neto_original', 'total_neto_original']);
        });
    }
};
