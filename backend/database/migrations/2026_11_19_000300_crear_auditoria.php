<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de actividad: quién hizo qué en las operaciones sensibles.
     */
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->bigIncrements('id_auditoria');
            $table->unsignedInteger('id_cuenta');
            $table->integer('id_usuario')->nullable();
            $table->string('accion', 50);
            $table->string('entidad', 50);
            $table->string('id_registro', 50)->nullable();
            $table->string('descripcion', 255);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_cuenta', 'created_at'], 'idx_auditoria_cuenta_fecha');
            $table->foreign('id_cuenta', 'fk_auditoria_cuentas')
                ->references('id_cuenta')->on('cuentas')->cascadeOnUpdate();
            // Sin FK a usuarios: el registro debe sobrevivir aunque se elimine el usuario
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
