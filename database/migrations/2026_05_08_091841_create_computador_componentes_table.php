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
        Schema::create('computador_componentes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('computador_id')
                  ->constrained('computadores_armados')
                  ->cascadeOnDelete();

            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->cascadeOnDelete();

            $table->string('tipo_componente', 50);           // placa_madre, procesador, ram, gpu...
            $table->unsignedSmallInteger('cantidad')->default(1);
            $table->string('serial', 100)->nullable();       // número de serie opcional
            $table->boolean('activo')->default(true);        // true = instalado, false = retirado

            // Instalación
            $table->timestamp('fecha_instalacion')->nullable();
            $table->foreignId('usuario_instalacion_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Retiro
            $table->timestamp('fecha_retiro')->nullable();
            $table->foreignId('usuario_retiro_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('motivo_retiro', 500)->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['computador_id', 'activo']);
            $table->index('producto_id');
            $table->index('tipo_componente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('computador_componentes');
    }
};
