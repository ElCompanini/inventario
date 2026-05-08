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
        Schema::create('reporterias_indexadas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Clasificación
            $table->string('tipo', 50);          // BINCARD_EXCEL, BINCARD_PDF, INVENTARIO_EXCEL, etc.
            $table->string('nombre', 200);        // Descripción legible: "BINCARD – CLORURO..."
            $table->string('modulo', 50);         // reportes, inventario, gastos_menores, sicd, etc.
            $table->string('formato', 10);        // EXCEL | PDF | CSV | HTML

            // Autoría
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre', 100)->nullable(); // snapshot del nombre al momento

            // Archivo
            $table->string('nombre_archivo', 250)->nullable();
            $table->string('ruta_archivo', 500)->nullable();
            $table->unsignedBigInteger('tamaño_bytes')->nullable();
            $table->string('hash_archivo', 64)->nullable();  // SHA-256

            // Filtros y contexto
            $table->json('filtros')->nullable();   // parámetros exactos usados

            // Estado
            $table->string('estado', 20)->default('generado'); // generado | error | eliminado

            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para búsqueda
            $table->index('tipo');
            $table->index('modulo');
            $table->index('formato');
            $table->index('usuario_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporterias_indexadas');
    }
};
