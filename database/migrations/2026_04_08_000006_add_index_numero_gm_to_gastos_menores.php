<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gastos_menores ADD INDEX idx_numero_gm (numero_gm)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gastos_menores DROP INDEX idx_numero_gm');
    }
};
