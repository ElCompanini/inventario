<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gastos_menores ADD COLUMN fecha_ingreso DATETIME NULL AFTER fecha_emision');
        DB::statement('UPDATE gastos_menores SET fecha_ingreso = created_at WHERE fecha_ingreso IS NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gastos_menores DROP COLUMN fecha_ingreso');
    }
};
