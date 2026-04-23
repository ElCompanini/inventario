<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Limpiar referencias huérfanas antes de agregar FK constraints ────

        // productos.contenedor → containers.id
        DB::statement('
            UPDATE productos p
            LEFT JOIN containers c ON p.contenedor = c.id
            SET p.contenedor = NULL
            WHERE p.contenedor IS NOT NULL AND c.id IS NULL
        ');

        // historial_cambios.contenedor_id → containers.id
        DB::statement('
            UPDATE historial_cambios hc
            LEFT JOIN containers c ON hc.contenedor_id = c.id
            SET hc.contenedor_id = NULL
            WHERE hc.contenedor_id IS NOT NULL AND c.id IS NULL
        ');

        // sicds.boleta_id → boletas.id
        DB::statement('
            UPDATE sicds s
            LEFT JOIN boletas b ON s.boleta_id = b.id
            SET s.boleta_id = NULL
            WHERE s.boleta_id IS NOT NULL AND b.id IS NULL
        ');

        // ── 2. Agregar FK constraints faltantes ─────────────────────────────────

        Schema::table('productos', function (Blueprint $table) {
            $table->foreign('contenedor')
                  ->references('id')->on('containers')
                  ->nullOnDelete();
        });

        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->foreign('contenedor_id')
                  ->references('id')->on('containers')
                  ->nullOnDelete();
        });

        Schema::table('sicds', function (Blueprint $table) {
            $table->foreign('boleta_id')
                  ->references('id')->on('boletas')
                  ->nullOnDelete();
        });

        // ── 3. Corregir solicitudes.updated_at (no debería ser nullable) ────────

        DB::statement('
            UPDATE solicitudes
            SET updated_at = created_at
            WHERE updated_at IS NULL
        ');

        DB::statement('
            ALTER TABLE solicitudes
            MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ');

        // ── 4. Índices de performance ────────────────────────────────────────────

        Schema::table('solicitudes', function (Blueprint $table) {
            // Filtraje más común: WHERE estado='pendiente' AND tipo='salida'
            $table->index(['estado', 'tipo'], 'idx_solicitudes_estado_tipo');
            // Panel del usuario: WHERE usuario_id=? AND estado=?
            $table->index(['usuario_id', 'estado'], 'idx_solicitudes_usuario_estado');
            // Ordenación por fecha descendente
            $table->index(['created_at'], 'idx_solicitudes_created_at');
        });

        Schema::table('historial_cambios', function (Blueprint $table) {
            // Lookup por producto (muy frecuente)
            $table->index(['producto_id'], 'idx_historial_producto_id');
            // Lookup polimórfico origen + origen_id
            $table->index(['origen', 'origen_id'], 'idx_historial_origen');
            // Ordenación por fecha descendente
            $table->index(['created_at'], 'idx_historial_created_at');
        });

        Schema::table('sicds', function (Blueprint $table) {
            // REGEXP/LIKE en codigo_sicd (búsqueda frecuente)
            $table->index(['codigo_sicd'], 'idx_sicds_codigo_sicd');
            // WHERE estado='pendiente'
            $table->index(['estado'], 'idx_sicds_estado');
            // Filtro por usuario
            $table->index(['usuario_id'], 'idx_sicds_usuario_id');
        });

        Schema::table('productos', function (Blueprint $table) {
            // Búsquedas LIKE en nombre y descripcion
            $table->index(['nombre'], 'idx_productos_nombre');
            // Lookup por código de barras (ya tiene unique pero lo hacemos explícito)
            // El unique index ya actúa como índice de búsqueda, no duplicar
        });

        Schema::table('users', function (Blueprint $table) {
            // Global scope filtra activo=1 en CADA query
            $table->index(['activo'], 'idx_users_activo');
            // Búsqueda por rol
            $table->index(['rol'], 'idx_users_rol');
        });

        Schema::table('containers', function (Blueprint $table) {
            // Global scope filtra activo=1
            $table->index(['activo'], 'idx_containers_activo');
        });

        Schema::table('gastos_menores', function (Blueprint $table) {
            // JOIN con productos frecuente
            $table->index(['producto_id'], 'idx_gastos_menores_producto_id');
            // Filtro por usuario
            $table->index(['user_id'], 'idx_gastos_menores_user_id');
            // Filtro por fecha
            $table->index(['fecha_emision'], 'idx_gastos_menores_fecha');
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            // WHERE estado='pendiente'/'recibido'
            $table->index(['estado'], 'idx_ordenes_compra_estado');
        });

        Schema::table('sicd_detalles', function (Blueprint $table) {
            // JOIN con productos
            $table->index(['producto_id'], 'idx_sicd_detalles_producto_id');
        });
    }

    public function down(): void
    {
        // Revertir índices
        Schema::table('sicd_detalles', function (Blueprint $table) {
            $table->dropIndex('idx_sicd_detalles_producto_id');
        });
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex('idx_ordenes_compra_estado');
        });
        Schema::table('gastos_menores', function (Blueprint $table) {
            $table->dropIndex('idx_gastos_menores_producto_id');
            $table->dropIndex('idx_gastos_menores_user_id');
            $table->dropIndex('idx_gastos_menores_fecha');
        });
        Schema::table('containers', function (Blueprint $table) {
            $table->dropIndex('idx_containers_activo');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_activo');
            $table->dropIndex('idx_users_rol');
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('idx_productos_nombre');
        });
        Schema::table('sicds', function (Blueprint $table) {
            $table->dropIndex('idx_sicds_codigo_sicd');
            $table->dropIndex('idx_sicds_estado');
            $table->dropIndex('idx_sicds_usuario_id');
        });
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropIndex('idx_historial_producto_id');
            $table->dropIndex('idx_historial_origen');
            $table->dropIndex('idx_historial_created_at');
        });
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropIndex('idx_solicitudes_estado_tipo');
            $table->dropIndex('idx_solicitudes_usuario_estado');
            $table->dropIndex('idx_solicitudes_created_at');
        });

        // Revertir FK constraints
        Schema::table('sicds', function (Blueprint $table) {
            $table->dropForeign(['boleta_id']);
        });
        Schema::table('historial_cambios', function (Blueprint $table) {
            $table->dropForeign(['contenedor_id']);
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['contenedor']);
        });

        // Revertir updated_at a nullable
        DB::statement('
            ALTER TABLE solicitudes
            MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT NULL
        ');
    }
};
