<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ampliar el enum de estado para incluir 'validado'
        DB::statement("
            ALTER TABLE ordenes_compra
            MODIFY COLUMN estado ENUM('pendiente','validado','recibido') NOT NULL DEFAULT 'pendiente'
        ");

        Schema::table('ordenes_compra', function (Blueprint $table) {
            // Datos retornados por la API de Mercado Público
            $table->string('api_codigo', 100)->nullable()->after('numero_oc');
            $table->string('api_nombre', 500)->nullable()->after('api_codigo');
            $table->text('api_descripcion')->nullable()->after('api_nombre');
            $table->string('api_tipo', 100)->nullable()->after('api_descripcion');
            $table->string('api_tipo_moneda', 50)->nullable()->after('api_tipo');
            $table->string('api_estado_mp', 100)->nullable()->after('api_tipo_moneda');
            $table->string('api_fecha_envio', 60)->nullable()->after('api_estado_mp');
            $table->bigInteger('api_total')->unsigned()->nullable()->after('api_fecha_envio');
            $table->string('api_proveedor_nombre', 300)->nullable()->after('api_total');
            $table->string('api_proveedor_rut', 20)->nullable()->after('api_proveedor_nombre');
            $table->string('api_contacto', 300)->nullable()->after('api_proveedor_rut');

            // Control del proceso de validación
            $table->timestamp('api_validado_at')->nullable()->after('api_contacto');
            $table->text('api_error')->nullable()->after('api_validado_at');
            $table->tinyInteger('api_intentos')->unsigned()->default(0)->after('api_error');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropColumn([
                'api_codigo', 'api_nombre', 'api_descripcion', 'api_tipo',
                'api_tipo_moneda', 'api_estado_mp', 'api_fecha_envio', 'api_total',
                'api_proveedor_nombre', 'api_proveedor_rut', 'api_contacto',
                'api_validado_at', 'api_error', 'api_intentos',
            ]);
        });

        DB::statement("
            ALTER TABLE ordenes_compra
            MODIFY COLUMN estado ENUM('pendiente','recibido') NOT NULL DEFAULT 'pendiente'
        ");
    }
};
