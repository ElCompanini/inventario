<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->string('codigo_movimiento', 20)->nullable()->after('orden_compra_id');
            $table->string('doc_origen', 120)->nullable()->after('codigo_movimiento');
            $table->string('doc_referencia', 120)->nullable()->after('doc_origen');
            $table->integer('stock_anterior')->nullable()->after('doc_referencia');
            $table->integer('stock_posterior')->nullable()->after('stock_anterior');
            $table->foreignId('usuario_ejecutor_id')->nullable()->after('stock_posterior')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropForeign(['usuario_ejecutor_id']);
            $table->dropColumn([
                'codigo_movimiento',
                'doc_origen',
                'doc_referencia',
                'stock_anterior',
                'stock_posterior',
                'usuario_ejecutor_id',
            ]);
        });
    }
};
