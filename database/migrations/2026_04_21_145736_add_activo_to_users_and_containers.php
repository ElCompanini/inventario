<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('activo')->default(1)->after('permisos');
        });

        Schema::table('containers', function (Blueprint $table) {
            $table->tinyInteger('activo')->default(1)->after('descripcion');
        });

        // Reemplazar soft deletes en sicds por estado='cancelado'
        Schema::table('sicds', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn('activo');
        });

        Schema::table('sicds', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
