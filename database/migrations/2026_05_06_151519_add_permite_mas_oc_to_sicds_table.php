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
        Schema::table('sicds', function (Blueprint $table) {
            $table->boolean('permite_mas_oc')->default(false)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('sicds', function (Blueprint $table) {
            $table->dropColumn('permite_mas_oc');
        });
    }
};
