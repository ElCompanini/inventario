<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignId('familia_id')->constrained('familias')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['nombre', 'familia_id']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->after('id')
                  ->constrained('categorias')->nullOnDelete();
        });

        // Seed families
        $ppId  = DB::table('familias')->insertGetId(['nombre' => 'Partes y Piezas', 'created_at' => now(), 'updated_at' => now()]);
        $ssoId = DB::table('familias')->insertGetId(['nombre' => 'SSO',             'created_at' => now(), 'updated_at' => now()]);
        $soaId = DB::table('familias')->insertGetId(['nombre' => 'SOA',             'created_at' => now(), 'updated_at' => now()]);

        // Migrate existing productos.nombre → categorias (default family: Partes y Piezas)
        $nombres = DB::table('productos')->distinct()->pluck('nombre');
        foreach ($nombres as $nombre) {
            $catId = DB::table('categorias')->insertGetId([
                'nombre'     => $nombre,
                'familia_id' => $ppId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('productos')->where('nombre', $nombre)->update(['categoria_id' => $catId]);
        }
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Categoria::class);
            $table->dropColumn('categoria_id');
        });
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('familias');
    }
};
