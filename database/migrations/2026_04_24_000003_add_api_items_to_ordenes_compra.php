<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->json('api_items')->nullable()->after('api_licitacion_codigo');
            $table->bigInteger('api_impuestos')->unsigned()->nullable()->after('api_total');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropColumn(['api_items', 'api_impuestos']);
        });
    }
};
