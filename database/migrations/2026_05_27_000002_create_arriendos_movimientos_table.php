<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arriendos_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('oc_id')->nullable()->constrained('ordenes_compra')->nullOnDelete();
            $table->foreignId('sicd_id')->nullable()->constrained('sicds')->nullOnDelete();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->string('proveedor_nombre')->nullable();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->unsignedInteger('duracion')->nullable();
            $table->string('unidad_tiempo', 20)->nullable();
            $table->decimal('monto_periodo', 14, 2)->nullable();
            $table->decimal('monto_total', 14, 2)->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ejecutado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacion')->nullable();
            $table->string('documento_referencia')->nullable();
            $table->timestamps();

            $table->index(['producto_id', 'estado_nuevo']);
            $table->index(['fecha_inicio', 'fecha_termino']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arriendos_movimientos');
    }
};
