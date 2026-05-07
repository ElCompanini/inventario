<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->unsignedBigInteger('orden_compra_id')->nullable()->after('origen_id');
            $table->index('orden_compra_id', 'idx_historial_oc');
        });
    }

    public function down(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropIndex('idx_historial_oc');
            $table->dropColumn('orden_compra_id');
        });
    }
};
