<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->boolean('clasificacion_pendiente')->default(false)->after('total_neto_original');
            $table->unsignedBigInteger('pendiente_user_id')->nullable()->after('clasificacion_pendiente');
            $table->timestamp('pendiente_at')->nullable()->after('pendiente_user_id');
            $table->unsignedBigInteger('resuelto_user_id')->nullable()->after('pendiente_at');
            $table->timestamp('resuelto_at')->nullable()->after('resuelto_user_id');

            $table->foreign('pendiente_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('resuelto_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->dropForeign(['pendiente_user_id']);
            $table->dropForeign(['resuelto_user_id']);
            $table->dropColumn([
                'clasificacion_pendiente',
                'pendiente_user_id',
                'pendiente_at',
                'resuelto_user_id',
                'resuelto_at',
            ]);
        });
    }
};
