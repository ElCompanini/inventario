<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_items_pendientes', function (Blueprint $table) {
            // Hacer nullable oc_detalle_id (puede no existir en OCs del sistema antiguo)
            $table->dropForeign(['oc_detalle_id']);
            $table->unsignedBigInteger('oc_detalle_id')->nullable()->change();
            $table->foreign('oc_detalle_id')->references('id')->on('oc_detalles')->nullOnDelete();

            // Referencia directa al ítem del SICD (siempre disponible)
            $table->unsignedBigInteger('sicd_detalle_id')->nullable()->after('oc_detalle_id');
            $table->foreign('sicd_detalle_id')->references('id')->on('sicd_detalles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('oc_items_pendientes', function (Blueprint $table) {
            $table->dropForeign(['sicd_detalle_id']);
            $table->dropColumn('sicd_detalle_id');
            $table->dropForeign(['oc_detalle_id']);
            $table->unsignedBigInteger('oc_detalle_id')->nullable(false)->change();
            $table->foreign('oc_detalle_id')->references('id')->on('oc_detalles');
        });
    }
};
