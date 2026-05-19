<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('servicio_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('estado', 30);
            $table->string('estado_anterior', 30)->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->text('observacion')->nullable();
            $table->foreignId('sicd_id')->nullable()->constrained('sicds')->nullOnDelete();
            $table->foreignId('orden_compra_id')->nullable()->constrained('ordenes_compra')->nullOnDelete();
            $table->string('documento_referencia', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_estados');
    }
};
