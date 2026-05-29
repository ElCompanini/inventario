<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('servicio_estados', function (Blueprint $table) {
            if (!Schema::hasColumn('servicio_estados', 'proveedor_nombre')) {
                $table->string('proveedor_nombre')->nullable()->after('documento_referencia');
            }
        });

        $legacyMantencion = [
            'mantencion_arriendo',
            'mantencion/arriendo',
            'mantencion y arriendo',
            'mantencionyarriendo',
            'mantencionarriendo',
            'mantención/arriendo',
            'mantención y arriendo',
            'mantenimiento',
            'mantenimientos',
        ];

        DB::table('productos')
            ->whereIn(DB::raw('LOWER(tipo_item)'), $legacyMantencion)
            ->update(['tipo_item' => 'mantencion']);

        DB::table('productos')
            ->where(function ($query) {
                $query->whereNull('tipo_item')->orWhere('tipo_item', '');
            })
            ->update(['tipo_item' => 'producto']);
    }

    public function down(): void
    {
        Schema::table('servicio_estados', function (Blueprint $table) {
            if (Schema::hasColumn('servicio_estados', 'proveedor_nombre')) {
                $table->dropColumn('proveedor_nombre');
            }
        });
    }
};
