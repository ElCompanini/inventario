<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE solicitudes MODIFY COLUMN estado ENUM('pendiente','aprobado','rechazado','en_devolucion','cerrada') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE solicitudes MODIFY COLUMN estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente'");
    }
};
