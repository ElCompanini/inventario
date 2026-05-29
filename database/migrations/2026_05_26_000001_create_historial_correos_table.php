<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_correos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_correo', 80)->index();
            $table->foreignId('historial_cambio_id')->nullable()->constrained('historial_cambios')->nullOnDelete();
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes')->nullOnDelete();
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costo')->nullOnDelete();
            $table->string('origen_type', 120)->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->json('destinatarios')->nullable();
            $table->json('metadata')->nullable();
            $table->string('estado', 40)->default('pendiente')->index();
            $table->text('error')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamps();

            $table->index(['origen_type', 'origen_id']);
            $table->index(['tipo_correo', 'centro_costo_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_correos');
    }
};
