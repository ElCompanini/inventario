<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->integer('cantidad')->default(1)->after('monto');
            $table->decimal('precio_neto', 12, 2)->nullable()->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->dropColumn(['cantidad', 'precio_neto']);
        });
    }
};
