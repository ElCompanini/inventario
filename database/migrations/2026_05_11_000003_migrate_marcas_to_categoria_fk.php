<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marcas', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->unsignedBigInteger('categoria_id')->nullable()->after('id');
            $table->foreign('categoria_id')->references('id')->on('categorias')->nullOnDelete();
            // Brand name is unique within a category; same name allowed across different categories
            $table->unique(['categoria_id', 'nombre']);
        });

        Schema::dropIfExists('categoria_marca');
    }

    public function down(): void
    {
        Schema::table('marcas', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropUnique(['categoria_id', 'nombre']);
            $table->dropColumn('categoria_id');
            $table->unique('nombre');
        });

        Schema::create('categoria_marca', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('categoria_id');
            $table->unsignedBigInteger('marca_id');
            $table->foreign('categoria_id')->references('id')->on('categorias')->cascadeOnDelete();
            $table->foreign('marca_id')->references('id')->on('marcas')->cascadeOnDelete();
            $table->unique(['categoria_id', 'marca_id']);
            $table->timestamps();
        });
    }
};
