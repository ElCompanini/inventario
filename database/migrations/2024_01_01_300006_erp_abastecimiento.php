<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar sicd_documentos (factura/guia pasan a nivel de OC)
        Schema::dropIfExists('sicd_documentos');

        // 2. Reestructurar sicds: quitar producto/cantidad (van a sicd_detalles)
        Schema::table('sicds', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn(['producto_id', 'cantidad', 'validado_por', 'validado_at']);
            $table->softDeletes();
        });
        DB::statement("UPDATE sicds SET estado = 'pendiente'");
        DB::statement("ALTER TABLE sicds MODIFY COLUMN estado ENUM('pendiente','agrupado','recibido') NOT NULL DEFAULT 'pendiente'");

        // 3. Detalle de productos por SICD (leídos del Excel)
        Schema::create('sicd_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sicd_id')->constrained('sicds')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('nombre_producto_excel')->comment('Nombre tal como vino en el Excel, para auditoría');
            $table->integer('cantidad_solicitada');
            $table->integer('cantidad_recibida')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        // 4. Órdenes de compra
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero_oc', 100)->unique();
            $table->string('archivo_nombre')->nullable()->comment('Archivo físico de la OC');
            $table->string('archivo_ruta')->nullable();
            $table->enum('estado', ['pendiente', 'recibido'])->default('pendiente');
            $table->string('procesado_por')->nullable();
            $table->timestamp('procesado_at')->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Pivot SICD ↔ OC (una OC puede tener varios SICD)
        Schema::create('orden_compra_sicd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('sicd_id')->constrained('sicds')->cascadeOnDelete();
            $table->unique(['orden_compra_id', 'sicd_id']);
            $table->timestamps();
        });

        // 6. Documentos de OC (factura obligatoria, guia opcional)
        Schema::create('oc_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->enum('tipo', ['factura', 'guia_despacho']);
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('subido_por');
            $table->foreignId('usuario_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        // 7. Soft deletes en historial (auditoría)
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::dropIfExists('oc_documentos');
        Schema::dropIfExists('orden_compra_sicd');
        Schema::dropIfExists('ordenes_compra');
        Schema::dropIfExists('sicd_detalles');
        Schema::table('sicds', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->foreignId('producto_id')->nullable()->constrained('productos');
            $table->integer('cantidad')->default(0);
            $table->string('validado_por')->nullable();
            $table->timestamp('validado_at')->nullable();
        });
        DB::statement("ALTER TABLE sicds MODIFY COLUMN estado ENUM('borrador','validado') NOT NULL DEFAULT 'borrador'");
    }
};
