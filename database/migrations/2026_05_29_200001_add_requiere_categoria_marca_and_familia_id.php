<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. familias: add requiere_categoria / requiere_marca ─────────────
        Schema::table('familias', function (Blueprint $table) {
            $table->boolean('requiere_categoria')->default(true)->after('protegido');
            $table->boolean('requiere_marca')->default(false)->after('requiere_categoria');
        });

        // ── 2. productos: add nullable familia_id FK ─────────────────────────
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('familia_id')->nullable()->after('categoria_id');
            $table->foreign('familia_id')->references('id')->on('familias')->nullOnDelete();
        });

        // ── 3. Backfill productos.familia_id via categoria → familias JOIN ────
        DB::statement('
            UPDATE productos p
            JOIN categorias c ON c.id = p.categoria_id
            SET p.familia_id = c.familia_id
            WHERE p.categoria_id IS NOT NULL
        ');

        // ── 4. Backfill requiere_* per family ────────────────────────────────
        // SIN FAMILIA: no category, no brand
        DB::table('familias')->where('tipo', 'sin_familia')
            ->update(['requiere_categoria' => false, 'requiere_marca' => false]);

        // SERVICIOS: no category, no brand
        DB::table('familias')->where('tipo', 'servicios')
            ->update(['requiere_categoria' => false, 'requiere_marca' => false]);

        // PARTES Y PIEZAS: category required, brand required
        DB::table('familias')->where('tipo', 'partes_piezas')
            ->update(['requiere_categoria' => true, 'requiere_marca' => true]);

        // MATERIALES: category required, brand NOT required
        DB::table('familias')->where('nombre', 'MATERIALES')
            ->update(['requiere_categoria' => true, 'requiere_marca' => false]);

        // INSUMOS: no category, no brand
        DB::table('familias')->where('nombre', 'INSUMOS')
            ->update(['requiere_categoria' => false, 'requiere_marca' => false]);

        // SOFTWARE: category required, brand required (track publisher)
        DB::table('familias')->where('nombre', 'SOFTWARE')
            ->update(['requiere_categoria' => true, 'requiere_marca' => true]);
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['familia_id']);
            $table->dropColumn('familia_id');
        });

        Schema::table('familias', function (Blueprint $table) {
            $table->dropColumn(['requiere_categoria', 'requiere_marca']);
        });
    }
};
