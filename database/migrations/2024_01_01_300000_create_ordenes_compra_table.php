<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero_orden', 100)->unique();
            $table->string('proveedor', 200);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['borrador', 'validada', 'cancelada'])->default('borrador');
            $table->string('validado_por')->nullable();
            $table->timestamp('validado_at')->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
