<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Insertar en centros_costo cualquier CC de users que no exista aún
        DB::statement("
            INSERT INTO centros_costo (acronimo, created_at, updated_at)
            SELECT DISTINCT centro_costo, NOW(), NOW()
            FROM users
            WHERE centro_costo IS NOT NULL
              AND centro_costo NOT IN (SELECT acronimo FROM centros_costo)
        ");

        // 2. Agregar la columna FK (nullable para no romper el ALTER si hay valores sin match)
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('centro_costo_id')->nullable()->after('centro_costo');
        });

        // 3. Poblar la FK con el id correspondiente
        DB::statement("
            UPDATE users u
            JOIN centros_costo cc ON cc.acronimo = u.centro_costo
            SET u.centro_costo_id = cc.id
        ");

        // 4. Eliminar la columna de texto
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('centro_costo');
        });

        // 5. Agregar FK constraint
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('centro_costo_id')
                  ->references('id')->on('centros_costo')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['centro_costo_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('centro_costo', 100)->nullable()->after('centro_costo_id');
        });

        DB::statement("
            UPDATE users u
            JOIN centros_costo cc ON cc.id = u.centro_costo_id
            SET u.centro_costo = cc.acronimo
        ");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('centro_costo_id');
        });
    }
};
