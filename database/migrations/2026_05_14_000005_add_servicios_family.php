<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert SERVICIOS structural family — idempotent
        DB::table('familias')->insertOrIgnore([
            'nombre'          => 'SERVICIOS',
            'activo'          => 1,
            'protegido'       => 1,
            'tipo'            => 'servicios',
            'centro_costo_id' => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public function down(): void
    {
        // Only remove if no categories depend on it
        $id = DB::table('familias')->where('tipo', 'servicios')->value('id');
        if ($id && DB::table('categorias')->where('familia_id', $id)->doesntExist()) {
            DB::table('familias')->where('id', $id)->delete();
        }
    }
};
