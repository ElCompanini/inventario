<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computador_componentes', function (Blueprint $table) {
            $table->foreignId('categoria_id')
                  ->nullable()
                  ->after('producto_id')
                  ->constrained('categorias')
                  ->nullOnDelete();

            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::table('computador_componentes', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });
    }
};
