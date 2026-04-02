<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar tablas de órdenes de compra
        Schema::dropIfExists('documentos_orden');
        Schema::dropIfExists('sicd_items');
        Schema::dropIfExists('ordenes_compra');

        // SICD como entidad principal
        Schema::create('sicds', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sicd', 100);
            $table->string('archivo_nombre');
            $table->string('archivo_ruta');
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['borrador', 'validado'])->default('borrador');
            $table->string('validado_por')->nullable();
            $table->timestamp('validado_at')->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });

        // Documentos asociados al SICD (factura y guía de despacho)
        Schema::create('sicd_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sicd_id')->constrained('sicds')->cascadeOnDelete();
            $table->enum('tipo', ['factura', 'guia_despacho']);
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('subido_por');
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });

        // Actualizar enum origen en historial_cambios: reemplazar 'orden' por 'sicd'
        DB::statement("ALTER TABLE historial_cambios MODIFY COLUMN origen ENUM('solicitud','sicd') NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('sicd_documentos');
        Schema::dropIfExists('sicds');
        DB::statement("ALTER TABLE historial_cambios MODIFY COLUMN origen ENUM('solicitud','orden') NULL");
    }
};
