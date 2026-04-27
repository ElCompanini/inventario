<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate string values to integers in a temp column, then swap
        DB::statement("ALTER TABLE users ADD COLUMN rol_int TINYINT UNSIGNED NOT NULL DEFAULT 0");

        DB::statement("UPDATE users SET rol_int = CASE
            WHEN rol = 'dev'    THEN 2
            WHEN rol = 'admin'  THEN 1
            ELSE 0
        END");

        DB::statement("ALTER TABLE users DROP COLUMN rol");
        DB::statement("ALTER TABLE users CHANGE rol_int rol TINYINT UNSIGNED NOT NULL DEFAULT 0");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users ADD COLUMN rol_str ENUM('admin','usuario','dev') NOT NULL DEFAULT 'usuario'");

        DB::statement("UPDATE users SET rol_str = CASE
            WHEN rol = 2 THEN 'dev'
            WHEN rol = 1 THEN 'admin'
            ELSE 'usuario'
        END");

        DB::statement("ALTER TABLE users DROP COLUMN rol");
        DB::statement("ALTER TABLE users CHANGE rol_str rol ENUM('admin','usuario','dev') NOT NULL DEFAULT 'usuario'");
    }
};
