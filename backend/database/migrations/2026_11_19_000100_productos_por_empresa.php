<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada producto o servicio pertenece a una empresa.
     * Reemplaza al script actualizacion_productos_empresa.sql: si ya se ejecutó, no hace nada.
     */
    public function up(): void
    {
        if (Schema::hasColumn('productos', 'id_empresa')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->integer('id_empresa')->nullable()->after('id_producto');
        });

        // Los productos existentes pasan a la empresa principal (o a la primera registrada)
        $idEmpresa = DB::table('empresas')->orderByDesc('es_principal')->orderBy('id_empresa')->value('id_empresa');
        if ($idEmpresa) {
            DB::table('productos')->whereNull('id_empresa')->update(['id_empresa' => $idEmpresa]);
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->integer('id_empresa')->nullable(false)->change();
            $table->index('id_empresa', 'idx_productos_empresa');
            $table->foreign('id_empresa', 'fk_productos_empresas')
                ->references('id_empresa')->on('empresas')->cascadeOnUpdate();

            // El código interno pasa a ser único por empresa
            $table->dropUnique('uk_productos_codigo_interno');
            $table->unique(['id_empresa', 'codigo_interno'], 'uk_productos_empresa_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('uk_productos_empresa_codigo');
            $table->unique('codigo_interno', 'uk_productos_codigo_interno');
            $table->dropForeign('fk_productos_empresas');
            $table->dropIndex('idx_productos_empresa');
            $table->dropColumn('id_empresa');
        });
    }
};
