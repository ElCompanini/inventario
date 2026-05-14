<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // true = registro es un servicio (instalación, mantención, soporte, etc.)
            // false (default) = producto físico con stock inventariable
            $table->boolean('es_servicio')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('es_servicio');
        });
    }
};
