<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sicds', function (Blueprint $table) {
            if (!Schema::hasColumn('sicds', 'documento_ruta')) {
                $table->string('documento_ruta')->nullable()->after('documento_mime');
            }
            if (!Schema::hasColumn('sicds', 'documento_nombre')) {
                $table->string('documento_nombre')->nullable()->after('documento_ruta');
            }
            if (!Schema::hasColumn('sicds', 'documento_estado')) {
                $table->string('documento_estado', 30)->default('pendiente')->after('documento_nombre');
            }
            if (!Schema::hasColumn('sicds', 'documento_error')) {
                $table->text('documento_error')->nullable()->after('documento_estado');
            }
            if (!Schema::hasColumn('sicds', 'documento_intentos')) {
                $table->unsignedTinyInteger('documento_intentos')->default(0)->after('documento_error');
            }
            if (!Schema::hasColumn('sicds', 'documento_adjuntado_at')) {
                $table->timestamp('documento_adjuntado_at')->nullable()->after('documento_intentos');
            }
            if (!Schema::hasColumn('sicds', 'documento_adjuntado_por')) {
                $table->foreignId('documento_adjuntado_por')->nullable()->after('documento_adjuntado_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'mp_estado_proceso')) {
                $table->string('mp_estado_proceso', 30)->default('pendiente')->after('api_intentos');
            }
            if (!Schema::hasColumn('ordenes_compra', 'mp_error_at')) {
                $table->timestamp('mp_error_at')->nullable()->after('mp_estado_proceso');
            }
        });

        Schema::table('facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas', 'documento_estado')) {
                $table->string('documento_estado', 30)->default('adjuntado')->after('ruta');
            }
            if (!Schema::hasColumn('facturas', 'documento_error')) {
                $table->text('documento_error')->nullable()->after('documento_estado');
            }
        });

        Schema::table('guias_despacho', function (Blueprint $table) {
            if (!Schema::hasColumn('guias_despacho', 'documento_estado')) {
                $table->string('documento_estado', 30)->default('adjuntado')->after('ruta');
            }
            if (!Schema::hasColumn('guias_despacho', 'documento_error')) {
                $table->text('documento_error')->nullable()->after('documento_estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guias_despacho', function (Blueprint $table) {
            foreach (['documento_error', 'documento_estado'] as $column) {
                if (Schema::hasColumn('guias_despacho', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('facturas', function (Blueprint $table) {
            foreach (['documento_error', 'documento_estado'] as $column) {
                if (Schema::hasColumn('facturas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            foreach (['mp_error_at', 'mp_estado_proceso'] as $column) {
                if (Schema::hasColumn('ordenes_compra', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('sicds', function (Blueprint $table) {
            if (Schema::hasColumn('sicds', 'documento_adjuntado_por')) {
                $table->dropConstrainedForeignId('documento_adjuntado_por');
            }
            foreach (['documento_adjuntado_at', 'documento_intentos', 'documento_error', 'documento_estado', 'documento_nombre', 'documento_ruta'] as $column) {
                if (Schema::hasColumn('sicds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
