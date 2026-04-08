<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->decimal('monto',      15, 2)->change();
            $table->decimal('precio_neto', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->decimal('monto',       12, 2)->change();
            $table->decimal('precio_neto', 12, 2)->nullable()->change();
        });
    }
};
