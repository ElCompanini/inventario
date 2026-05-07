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
        Schema::table('precios', function (Blueprint $table) {
            $table->unsignedBigInteger('orden_compra_id')->nullable()->after('origen_id');
            $table->index('orden_compra_id', 'idx_precios_oc');
            $table->index(['origen_tipo', 'origen_id', 'orden_compra_id'], 'idx_precios_origen_oc');
        });
    }

    public function down(): void
    {
        Schema::table('precios', function (Blueprint $table) {
            $table->dropIndex('idx_precios_origen_oc');
            $table->dropIndex('idx_precios_oc');
            $table->dropColumn('orden_compra_id');
        });
    }
};
