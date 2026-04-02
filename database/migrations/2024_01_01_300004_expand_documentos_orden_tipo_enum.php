<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documentos_orden MODIFY COLUMN tipo ENUM('factura','guia_despacho','orden_compra','sicd') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documentos_orden MODIFY COLUMN tipo ENUM('factura','guia_despacho') NOT NULL");
    }
};
