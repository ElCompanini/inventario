<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY rol ENUM('admin','usuario','dev') NOT NULL DEFAULT 'usuario'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario'");
    }
};
