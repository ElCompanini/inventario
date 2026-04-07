<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->text('motivo_recepcion')->nullable()->after('cantidad_recibida');
        });
    }

    public function down(): void
    {
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->dropColumn('motivo_recepcion');
        });
    }
};
