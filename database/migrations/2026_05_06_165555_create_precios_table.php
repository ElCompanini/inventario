<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precios', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('familia_id')->nullable();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();

            // Precios
            $table->decimal('precio_neto', 14, 2);
            $table->decimal('precio_total', 14, 2)->nullable();
            $table->integer('cantidad')->default(1);

            // Trazabilidad de origen
            $table->string('fuente', 30)->default('manual');
            // 'boleta_local' | 'sicd_masiva' | 'sicd_manual' | 'manual'
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->string('origen_tipo', 50)->nullable();
            // e.g. 'GastoMenor', 'SicdDetalle'

            $table->string('notas', 500)->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('familia_id')->references('id')->on('familias')->onDelete('set null');
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');

            // Índices para búsquedas frecuentes
            $table->index('producto_id',  'idx_precios_producto');
            $table->index('familia_id',   'idx_precios_familia');
            $table->index('categoria_id', 'idx_precios_categoria');
            $table->index('fuente',       'idx_precios_fuente');
            $table->index('created_at',   'idx_precios_fecha');
            $table->index(['origen_tipo', 'origen_id'], 'idx_precios_origen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precios');
    }
};
