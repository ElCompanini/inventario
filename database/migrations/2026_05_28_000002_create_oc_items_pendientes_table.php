<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_items_pendientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_compra_id');
            $table->unsignedBigInteger('oc_detalle_id');
            $table->string('motivo');
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('pendiente_user_id');
            $table->timestamp('pendiente_at');

            // Resolución
            $table->unsignedBigInteger('producto_id_final')->nullable();
            $table->unsignedBigInteger('container_id_final')->nullable();
            $table->integer('cantidad_resolucion')->nullable();
            $table->unsignedBigInteger('resuelto_user_id')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->text('eliminado_motivo')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('orden_compra_id')->references('id')->on('ordenes_compra');
            $table->foreign('oc_detalle_id')->references('id')->on('oc_detalles');
            $table->foreign('pendiente_user_id')->references('id')->on('users');
            $table->foreign('producto_id_final')->references('id')->on('productos')->nullOnDelete();
            $table->foreign('container_id_final')->references('id')->on('containers')->nullOnDelete();
            $table->foreign('resuelto_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_items_pendientes');
    }
};
