<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gastos_menores MODIFY monto BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE gastos_menores MODIFY precio_neto BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gastos_menores MODIFY monto DECIMAL(15,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE gastos_menores MODIFY precio_neto DECIMAL(15,2) NULL');
    }
};
