<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add protegido column to familias
        if (!Schema::hasColumn('familias', 'protegido')) {
            Schema::table('familias', function (Blueprint $table) {
                $table->boolean('protegido')->default(false)->after('activo');
            });
        }

        // Add protegido column to marcas
        if (!Schema::hasColumn('marcas', 'protegido')) {
            Schema::table('marcas', function (Blueprint $table) {
                $table->boolean('protegido')->default(false)->after('activo');
            });
        }

        // Insert SIN FAMILIA if not exists
        $existeFam = DB::table('familias')->where('nombre', 'SIN FAMILIA')->exists();
        if (!$existeFam) {
            DB::table('familias')->insert([
                'nombre'          => 'SIN FAMILIA',
                'centro_costo_id' => null,
                'activo'          => true,
                'protegido'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } else {
            DB::table('familias')->where('nombre', 'SIN FAMILIA')->update(['protegido' => true]);
        }

        // Insert SIN MARCA if not exists (categoria_id nullable)
        $existeMarca = DB::table('marcas')->where('nombre', 'SIN MARCA')->exists();
        if (!$existeMarca) {
            DB::table('marcas')->insert([
                'nombre'      => 'SIN MARCA',
                'categoria_id' => null,
                'activo'      => true,
                'protegido'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
                'deleted_at'  => null,
            ]);
        } else {
            DB::table('marcas')->where('nombre', 'SIN MARCA')->update(['protegido' => true]);
        }
    }

    public function down(): void
    {
        DB::table('familias')->where('nombre', 'SIN FAMILIA')->delete();
        DB::table('marcas')->where('nombre', 'SIN MARCA')->delete();

        if (Schema::hasColumn('familias', 'protegido')) {
            Schema::table('familias', function (Blueprint $table) {
                $table->dropColumn('protegido');
            });
        }

        if (Schema::hasColumn('marcas', 'protegido')) {
            Schema::table('marcas', function (Blueprint $table) {
                $table->dropColumn('protegido');
            });
        }
    }
};
