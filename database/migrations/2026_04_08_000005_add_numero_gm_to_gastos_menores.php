<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gastos_menores ADD COLUMN numero_gm INT UNSIGNED NULL AFTER id');

        // Numerar los registros existentes por orden de creación
        DB::statement('SET @n := 0');
        DB::statement('UPDATE gastos_menores SET numero_gm = (@n := @n + 1) ORDER BY id ASC');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gastos_menores DROP COLUMN numero_gm');
    }
};
