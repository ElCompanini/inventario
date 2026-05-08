<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_detalles', function (Blueprint $table) {
            $table->integer('cantidad_recibida')->nullable()->after('cantidad_asignada');
        });
    }

    public function down(): void
    {
        Schema::table('oc_detalles', function (Blueprint $table) {
            $table->dropColumn('cantidad_recibida');
        });
    }
};
