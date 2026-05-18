<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->string('origen_tipo', 50)->nullable()->after('origen_id');
            $table->string('referencia_tipo', 50)->nullable()->after('origen_tipo');
            $table->unsignedBigInteger('referencia_id')->nullable()->after('referencia_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropColumn(['origen_tipo', 'referencia_tipo', 'referencia_id']);
        });
    }
};
