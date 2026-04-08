<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gastos_menores CHANGE numero_gm id_gm INT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gastos_menores CHANGE id_gm numero_gm INT UNSIGNED NULL');
    }
};
