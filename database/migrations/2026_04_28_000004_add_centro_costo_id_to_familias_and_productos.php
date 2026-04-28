<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── familias ──────────────────────────────────────────────────────
        // Quitar unique global de nombre (ahora será único por CC)
        Schema::table('familias', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->unsignedBigInteger('centro_costo_id')->nullable()->after('nombre');
            $table->foreign('centro_costo_id')
                  ->references('id')->on('centros_costo')
                  ->nullOnDelete();
        });

        // ── productos ─────────────────────────────────────────────────────
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('centro_costo_id')->nullable()->after('categoria_id');
            $table->foreign('centro_costo_id')
                  ->references('id')->on('centros_costo')
                  ->nullOnDelete();
        });

        // Poblar centro_costo_id en productos desde el container que tienen asignado
        DB::statement("
            UPDATE productos p
            JOIN containers c ON c.id = p.contenedor
            SET p.centro_costo_id = c.centro_costo_id
            WHERE c.centro_costo_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['centro_costo_id']);
            $table->dropColumn('centro_costo_id');
        });

        Schema::table('familias', function (Blueprint $table) {
            $table->dropForeign(['centro_costo_id']);
            $table->dropColumn('centro_costo_id');
            $table->unique('nombre');
        });
    }
};
