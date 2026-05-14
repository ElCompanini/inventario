<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            $table->string('tipo_catalogo', 20)->default('bien')->after('tipo');
        });

        // Familias cuyo tipo estructural es 'servicios' → tipo_catalogo = 'servicio'
        DB::table('familias')->where('tipo', 'servicios')->update(['tipo_catalogo' => 'servicio']);
    }

    public function down(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            $table->dropColumn('tipo_catalogo');
        });
    }
};
