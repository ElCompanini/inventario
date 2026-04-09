<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->dropColumn('fecha_ingreso');
        });
    }

    public function down(): void
    {
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->dateTime('fecha_ingreso')->nullable()->after('fecha_emision');
        });
    }
};
