<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Eliminar familias vacías que ya no se usan (SSO, SOA) ─────────────
        DB::table('familias')->whereIn('id', [2, 3])->delete();

        // ── MATERIALES (id 6): reemplazar categoría ALCOHOL con Redes y Otros ─
        DB::table('categorias')->where('familia_id', 6)->delete();
        DB::table('categorias')->insert([
            ['nombre' => 'Redes', 'familia_id' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Otros', 'familia_id' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── INSUMOS (sin categorías ni marcas) ────────────────────────────────
        if (!DB::table('familias')->where('nombre', 'INSUMOS')->exists()) {
            DB::table('familias')->insert([
                'nombre' => 'INSUMOS', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ── SOFTWARE ──────────────────────────────────────────────────────────
        $softwareId = DB::table('familias')->where('nombre', 'SOFTWARE')->value('id');
        if (!$softwareId) {
            $softwareId = DB::table('familias')->insertGetId([
                'nombre' => 'SOFTWARE', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('categorias')->where('familia_id', $softwareId)->delete();
        DB::table('categorias')->insert([
            ['nombre' => 'S.O',       'familia_id' => $softwareId, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Antivirus', 'familia_id' => $softwareId, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Otros',     'familia_id' => $softwareId, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('familias')->insert([
            ['id' => 2, 'nombre' => 'SSO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nombre' => 'SOA', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('categorias')->where('familia_id', 6)->delete();
        DB::table('categorias')->insert([
            ['nombre' => 'ALCOHOL', 'familia_id' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('familias')->whereIn('nombre', ['INSUMOS', 'SOFTWARE'])->delete();
    }
};
