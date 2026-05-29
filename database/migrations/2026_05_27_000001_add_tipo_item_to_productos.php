<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'tipo_item')) {
                $table->string('tipo_item', 20)->default('producto')->after('es_servicio')->index();
            }
        });

        DB::table('productos')
            ->where(function ($query) {
                $query->whereNull('tipo_item')->orWhere('tipo_item', '');
            })
            ->update(['tipo_item' => 'producto']);

        DB::table('productos')
            ->where('es_servicio', true)
            ->update(['tipo_item' => 'servicio']);

        DB::table('productos')
            ->where('es_servicio', false)
            ->where(function ($query) {
                $query->whereNull('tipo_item')->orWhere('tipo_item', '');
            })
            ->update(['tipo_item' => 'producto']);
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'tipo_item')) {
                $table->dropIndex(['tipo_item']);
                $table->dropColumn('tipo_item');
            }
        });
    }
};
