<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'factura_pendiente')) {
                $table->boolean('factura_pendiente')->default(false)->after('estado');
            }

            if (!Schema::hasColumn('ordenes_compra', 'factura_pendiente_at')) {
                $table->timestamp('factura_pendiente_at')->nullable()->after('factura_pendiente');
            }

            if (!Schema::hasColumn('ordenes_compra', 'factura_pendiente_por')) {
                $table->foreignId('factura_pendiente_por')
                    ->nullable()
                    ->after('factura_pendiente_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_compra', 'factura_pendiente_por')) {
                $table->dropConstrainedForeignId('factura_pendiente_por');
            }

            if (Schema::hasColumn('ordenes_compra', 'factura_pendiente_at')) {
                $table->dropColumn('factura_pendiente_at');
            }

            if (Schema::hasColumn('ordenes_compra', 'factura_pendiente')) {
                $table->dropColumn('factura_pendiente');
            }
        });
    }
};
