<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->string('proveedor_nombre', 300)->nullable()->after('rut_proveedor');
        });
    }
    public function down(): void {
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->dropColumn('proveedor_nombre');
        });
    }
};
