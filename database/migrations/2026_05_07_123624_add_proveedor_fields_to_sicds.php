<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sicds', function (Blueprint $table) {
            $table->string('rut_proveedor', 20)->nullable()->after('descripcion');
            $table->string('proveedor_nombre', 300)->nullable()->after('rut_proveedor');
            $table->string('folio', 50)->nullable()->after('proveedor_nombre');
        });
    }
    public function down(): void {
        Schema::table('sicds', function (Blueprint $table) {
            $table->dropColumn(['rut_proveedor', 'proveedor_nombre', 'folio']);
        });
    }
};
