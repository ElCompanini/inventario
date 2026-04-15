<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->unsignedBigInteger('contenedor_id')->nullable()->after('producto_id');
        });
    }

    public function down(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropColumn('contenedor_id');
        });
    }
};
