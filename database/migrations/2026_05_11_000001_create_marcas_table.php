<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('activo');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('marca_id')
                  ->nullable()
                  ->after('categoria_id')
                  ->constrained('marcas')
                  ->nullOnDelete();
        });

        Schema::table('precios', function (Blueprint $table) {
            $table->foreignId('marca_id')
                  ->nullable()
                  ->after('categoria_id')
                  ->constrained('marcas')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('precios', function (Blueprint $table) {
            $table->dropForeign(['marca_id']);
            $table->dropColumn('marca_id');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['marca_id']);
            $table->dropColumn('marca_id');
        });

        Schema::dropIfExists('marcas');
    }
};
