<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vp_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users');
            $table->date('fecha_desde');
            $table->date('fecha_hasta');
            $table->json('filtros')->nullable();        // filtros opcionales aplicados
            $table->decimal('total_sicd', 15, 2)->default(0);
            $table->decimal('total_oc', 15, 2)->default(0);
            $table->decimal('variacion', 15, 2)->default(0);
            $table->integer('n_sicds')->default(0);
            $table->integer('n_ocs')->default(0);
            $table->json('detalle')->nullable();        // array por SICD con breakdown
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vp_historial');
    }
};
