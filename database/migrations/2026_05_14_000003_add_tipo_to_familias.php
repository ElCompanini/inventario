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
            $table->string('tipo', 20)->default('normal')->after('protegido');
        });

        // Mark SIN FAMILIA
        DB::table('familias')->where('nombre', 'SIN FAMILIA')->update(['tipo' => 'sin_familia']);

        // Mark PARTES Y PIEZAS (detect by name, same logic as esFamiliaPartesYPiezas)
        DB::table('familias')->get()->each(function ($f) {
            $n = strtolower(str_replace([' ', '_', '-'], '', $f->nombre));
            if (str_contains($n, 'partes') && str_contains($n, 'piezas')) {
                DB::table('familias')->where('id', $f->id)->update(['tipo' => 'partes_piezas']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
