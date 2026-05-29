<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            if (!Schema::hasColumn('familias', 'tipo_item')) {
                $table->string('tipo_item', 20)->default('producto')->after('tipo_catalogo')->index();
            }
        });

        Schema::table('categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('categorias', 'tipo_item')) {
                $table->string('tipo_item', 20)->default('producto')->after('familia_id')->index();
            }
        });

        Schema::table('marcas', function (Blueprint $table) {
            if (!Schema::hasColumn('marcas', 'tipo_item')) {
                $table->string('tipo_item', 20)->nullable()->after('categoria_id')->index();
            }
        });

        DB::table('familias')
            ->where(fn($q) => $q->whereNull('tipo_item')->orWhere('tipo_item', ''))
            ->update(['tipo_item' => 'producto']);

        DB::table('familias')
            ->where(fn($q) => $q->where('tipo', 'servicios')->orWhere('tipo_catalogo', 'servicio'))
            ->update(['tipo_item' => 'servicio']);

        DB::table('categorias')
            ->leftJoin('familias', 'categorias.familia_id', '=', 'familias.id')
            ->whereNotNull('familias.tipo_item')
            ->update(['categorias.tipo_item' => DB::raw('familias.tipo_item')]);

        DB::table('categorias')
            ->where(fn($q) => $q->whereNull('tipo_item')->orWhere('tipo_item', ''))
            ->update(['tipo_item' => 'producto']);

        DB::table('marcas')
            ->leftJoin('categorias', 'marcas.categoria_id', '=', 'categorias.id')
            ->whereNotNull('categorias.tipo_item')
            ->update(['marcas.tipo_item' => DB::raw('categorias.tipo_item')]);

        DB::table('marcas')
            ->where(fn($q) => $q->whereNull('tipo_item')->orWhere('tipo_item', ''))
            ->update(['tipo_item' => 'producto']);
    }

    public function down(): void
    {
        Schema::table('marcas', function (Blueprint $table) {
            if (Schema::hasColumn('marcas', 'tipo_item')) {
                $table->dropIndex(['tipo_item']);
                $table->dropColumn('tipo_item');
            }
        });

        Schema::table('categorias', function (Blueprint $table) {
            if (Schema::hasColumn('categorias', 'tipo_item')) {
                $table->dropIndex(['tipo_item']);
                $table->dropColumn('tipo_item');
            }
        });

        Schema::table('familias', function (Blueprint $table) {
            if (Schema::hasColumn('familias', 'tipo_item')) {
                $table->dropIndex(['tipo_item']);
                $table->dropColumn('tipo_item');
            }
        });
    }
};
