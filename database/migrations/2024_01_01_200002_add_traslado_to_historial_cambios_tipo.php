<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE historial_cambios MODIFY tipo ENUM('entrada', 'salida', 'traslado') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE historial_cambios MODIFY tipo ENUM('entrada', 'salida') NOT NULL");
    }
};
