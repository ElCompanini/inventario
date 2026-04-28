<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centros_costo', function (Blueprint $table) {
            $table->renameColumn('nombre', 'acronimo');
        });

        Schema::table('centros_costo', function (Blueprint $table) {
            $table->string('nombre_completo', 200)->nullable()->after('acronimo');
        });
    }

    public function down(): void
    {
        Schema::table('centros_costo', function (Blueprint $table) {
            $table->dropColumn('nombre_completo');
        });

        Schema::table('centros_costo', function (Blueprint $table) {
            $table->renameColumn('acronimo', 'nombre');
        });
    }
};
