<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oc_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')
                  ->constrained('ordenes_compra')
                  ->cascadeOnDelete();
            $table->foreignId('sicd_detalle_id')
                  ->constrained('sicd_detalles')
                  ->cascadeOnDelete();
            $table->integer('cantidad_asignada');
            $table->decimal('precio_neto', 12, 2)->nullable();
            $table->decimal('total_neto',  12, 2)->nullable();
            $table->timestamps();

            $table->unique(['orden_compra_id', 'sicd_detalle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oc_detalles');
    }
};
