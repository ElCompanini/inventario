<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('maneja_presentacion')->default(false)->after('es_servicio');
            $table->string('tipo_presentacion', 50)->nullable()->after('maneja_presentacion');
            $table->unsignedSmallInteger('cantidad_presentacion')->nullable()->after('tipo_presentacion');
            $table->string('unidad_base', 50)->nullable()->after('cantidad_presentacion');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['maneja_presentacion', 'tipo_presentacion', 'cantidad_presentacion', 'unidad_base']);
        });
    }
};
