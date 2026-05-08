<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE historial_cambios MODIFY COLUMN origen ENUM('solicitud','orden','sicd','gasto_menor','computador_armado') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE historial_cambios MODIFY COLUMN origen ENUM('solicitud','orden') NULL");
    }
};
