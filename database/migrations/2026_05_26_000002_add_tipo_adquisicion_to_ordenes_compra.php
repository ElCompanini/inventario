<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'tipo_adquisicion')) {
                $table->enum('tipo_adquisicion', [
                    'compra_agil',
                    'licitacion',
                    'indeterminado',
                ])->default('indeterminado')->after('estado');
            }

            if (!Schema::hasColumn('ordenes_compra', 'tipo_adquisicion_origen')) {
                $table->enum('tipo_adquisicion_origen', [
                    'api_mp',
                    'codigo_mp',
                    'utm_estimado',
                    'manual',
                ])->default('manual')->after('tipo_adquisicion');
            }

            if (!Schema::hasColumn('ordenes_compra', 'tipo_adquisicion_confianza')) {
                $table->enum('tipo_adquisicion_confianza', [
                    'alta',
                    'media',
                    'baja',
                ])->default('baja')->after('tipo_adquisicion_origen');
            }

            if (!Schema::hasColumn('ordenes_compra', 'tipo_adquisicion_observacion')) {
                $table->text('tipo_adquisicion_observacion')->nullable()->after('tipo_adquisicion_confianza');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            foreach ([
                'tipo_adquisicion_observacion',
                'tipo_adquisicion_confianza',
                'tipo_adquisicion_origen',
                'tipo_adquisicion',
            ] as $column) {
                if (Schema::hasColumn('ordenes_compra', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
