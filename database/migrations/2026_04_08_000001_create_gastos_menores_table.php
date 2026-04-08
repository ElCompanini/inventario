<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_menores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('historial_cambio_id')->nullable()->constrained('historial_cambios')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('rut_proveedor', 20);
            $table->string('folio', 50);
            $table->decimal('monto', 12, 2);
            $table->datetime('fecha_emision');
            $table->string('documento_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_menores');
    }
};
