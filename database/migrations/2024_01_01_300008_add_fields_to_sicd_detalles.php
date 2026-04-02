<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->string('unidad')->nullable()->after('nombre_producto_excel');
            $table->decimal('precio_neto', 12, 2)->nullable()->after('cantidad_solicitada');
            $table->decimal('total_neto', 12, 2)->nullable()->after('precio_neto');
        });
    }

    public function down(): void
    {
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->dropColumn(['unidad', 'precio_neto', 'total_neto']);
        });
    }
};
