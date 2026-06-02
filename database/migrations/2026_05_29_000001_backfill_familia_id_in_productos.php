<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Derivar familia_id desde la categoría cuando sea posible
        DB::statement('
            UPDATE productos p
            JOIN categorias c ON c.id = p.categoria_id
            SET p.familia_id = c.familia_id
            WHERE p.familia_id IS NULL AND p.categoria_id IS NOT NULL
        ');

        // 2. Productos sin categoría (y sin familia) → familia "Sin Familia"
        $sinFamiliaId = DB::table('familias')->where('tipo', 'sin_familia')->value('id');
        if ($sinFamiliaId) {
            DB::table('productos')
                ->whereNull('familia_id')
                ->update(['familia_id' => $sinFamiliaId]);
        }
    }

    public function down(): void
    {
        // No se puede revertir un backfill de datos de forma segura
    }
};
