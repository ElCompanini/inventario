<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Productos sin CC → heredar de la familia de su categoría ──────
        DB::statement("
            UPDATE productos p
            INNER JOIN categorias cat ON cat.id = p.categoria_id
            INNER JOIN familias f    ON f.id  = cat.familia_id
            SET p.centro_costo_id = f.centro_costo_id
            WHERE p.centro_costo_id IS NULL
              AND f.centro_costo_id IS NOT NULL
        ");

        // ── 2. Familias sin CC → inferir del CC más frecuente en sus productos ─
        $familiasSinCC = DB::table('familias')->whereNull('centro_costo_id')->pluck('id');

        foreach ($familiasSinCC as $familiaId) {
            $row = DB::table('productos')
                ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
                ->where('categorias.familia_id', $familiaId)
                ->whereNotNull('productos.centro_costo_id')
                ->select('productos.centro_costo_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('productos.centro_costo_id')
                ->orderByDesc('cnt')
                ->first();

            if ($row) {
                DB::table('familias')
                    ->where('id', $familiaId)
                    ->update(['centro_costo_id' => $row->centro_costo_id]);
            }
        }

        // ── 3. Segunda pasada: productos que siguen sin CC ahora que las familias
        //       ya tienen CC asignado en el paso anterior ────────────────────────
        DB::statement("
            UPDATE productos p
            INNER JOIN categorias cat ON cat.id = p.categoria_id
            INNER JOIN familias f    ON f.id  = cat.familia_id
            SET p.centro_costo_id = f.centro_costo_id
            WHERE p.centro_costo_id IS NULL
              AND f.centro_costo_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // No se puede revertir sin saber cuáles valores eran NULL originalmente.
    }
};
