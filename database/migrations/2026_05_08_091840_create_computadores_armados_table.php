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
        Schema::create('computadores_armados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();          // PC-001, PC-002...
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->string('estado', 30)->default('en_armado'); // en_armado | listo | en_uso | desarmado
            $table->string('ubicacion', 200)->nullable();
            $table->string('usuario_asignado', 150)->nullable(); // quién usa el equipo

            $table->foreignId('usuario_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index('usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('computadores_armados');
    }
};
