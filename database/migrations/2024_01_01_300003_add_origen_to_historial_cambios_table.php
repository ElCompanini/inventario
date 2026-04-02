<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->enum('origen', ['solicitud', 'orden'])->nullable()->after('usuario_id');
            $table->unsignedBigInteger('origen_id')->nullable()->after('origen');
        });
    }

    public function down(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropColumn(['origen', 'origen_id']);
        });
    }
};
