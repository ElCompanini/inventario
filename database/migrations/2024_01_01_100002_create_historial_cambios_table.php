<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->enum('tipo', ['entrada', 'salida']);
            $table->text('motivo');
            $table->string('aprobado_por')->nullable()->comment('Nombre del admin que aprobó o realizó el cambio directo');
            $table->foreignId('usuario_id')->constrained('users')->comment('Quien solicitó o realizó el cambio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
    }
};
