<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sicds', function (Blueprint $table) {
            $table->longText('archivo_blob')->nullable()->after('archivo_ruta');
            $table->string('archivo_mime', 100)->nullable()->after('archivo_blob');
        });
    }

    public function down(): void
    {
        Schema::table('sicds', function (Blueprint &$table) {
            $table->dropColumn(['archivo_blob', 'archivo_mime']);
        });
    }
};
