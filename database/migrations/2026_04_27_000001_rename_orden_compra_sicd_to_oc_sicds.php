<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('orden_compra_sicd', 'oc_sicds');
    }

    public function down(): void
    {
        Schema::rename('oc_sicds', 'orden_compra_sicd');
    }
};
