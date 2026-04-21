<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add boleta_id FK to sicds
        Schema::table('sicds', function (Blueprint $table) {
            $table->unsignedBigInteger('boleta_id')->nullable()->after('usuario_id');
        });

        // 2. Migrate existing blob data from sicds → boletas
        \DB::unprepared('SET GLOBAL max_allowed_packet=67108864');
        $sicds = \DB::table('sicds')
            ->whereNotNull('archivo_blob')
            ->where('archivo_blob', '!=', '')
            ->get(['id', 'archivo_nombre', 'archivo_blob', 'archivo_mime', 'archivo_ruta']);

        foreach ($sicds as $sicd) {
            $boletaId = \DB::table('boletas')->insertGetId([
                'archivo_nombre' => $sicd->archivo_nombre,
                'archivo_blob'   => $sicd->archivo_blob,
                'archivo_mime'   => $sicd->archivo_mime,
                'archivo_ruta'   => $sicd->archivo_ruta ?: null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            \DB::table('sicds')->where('id', $sicd->id)->update(['boleta_id' => $boletaId]);
        }

        // 3. Migrate legacy filesystem-only entries (no blob)
        $legacySicds = \DB::table('sicds')
            ->whereNull('archivo_blob')
            ->where('archivo_blob', '!=', '')
            ->whereNotNull('archivo_ruta')
            ->where('archivo_ruta', '!=', '')
            ->get(['id', 'archivo_nombre', 'archivo_ruta']);

        foreach ($legacySicds as $sicd) {
            $boletaId = \DB::table('boletas')->insertGetId([
                'archivo_nombre' => $sicd->archivo_nombre,
                'archivo_blob'   => null,
                'archivo_mime'   => null,
                'archivo_ruta'   => $sicd->archivo_ruta,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            \DB::table('sicds')->where('id', $sicd->id)->update(['boleta_id' => $boletaId]);
        }

        // 4. Drop old columns from sicds
        Schema::table('sicds', function (Blueprint $table) {
            $table->dropColumn(['archivo_nombre', 'archivo_blob', 'archivo_mime', 'archivo_ruta']);
        });
    }

    public function down(): void
    {
        Schema::table('sicds', function (Blueprint $table) {
            $table->string('archivo_nombre')->nullable();
            $table->longText('archivo_blob')->nullable();
            $table->string('archivo_mime', 100)->nullable();
            $table->string('archivo_ruta')->nullable();
        });

        Schema::table('sicds', function (Blueprint $table) {
            $table->dropColumn('boleta_id');
        });
    }
};
