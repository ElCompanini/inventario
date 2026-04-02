<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla facturas
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('subido_por');
            $table->foreignId('usuario_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Crear tabla guias_despacho
        Schema::create('guias_despacho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('subido_por');
            $table->foreignId('usuario_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Migrar datos existentes de oc_documentos a las tablas correspondientes
        $docs = DB::table('oc_documentos')->whereNull('deleted_at')->get();

        foreach ($docs as $doc) {
            if ($doc->tipo === 'factura') {
                DB::table('facturas')->insert([
                    'orden_compra_id' => $doc->orden_compra_id,
                    'nombre_original' => $doc->nombre_original,
                    'ruta'            => $doc->ruta,
                    'subido_por'      => $doc->subido_por,
                    'usuario_id'      => $doc->usuario_id,
                    'created_at'      => $doc->created_at,
                    'updated_at'      => $doc->updated_at,
                ]);
            } else {
                DB::table('guias_despacho')->insert([
                    'orden_compra_id' => $doc->orden_compra_id,
                    'nombre_original' => $doc->nombre_original,
                    'ruta'            => $doc->ruta,
                    'subido_por'      => $doc->subido_por,
                    'usuario_id'      => $doc->usuario_id,
                    'created_at'      => $doc->created_at,
                    'updated_at'      => $doc->updated_at,
                ]);
            }
        }

        // 4. Eliminar tabla oc_documentos
        Schema::dropIfExists('oc_documentos');
    }

    public function down(): void
    {
        Schema::create('oc_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->enum('tipo', ['factura', 'guia_despacho']);
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('subido_por');
            $table->foreignId('usuario_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::dropIfExists('guias_despacho');
        Schema::dropIfExists('facturas');
    }
};
