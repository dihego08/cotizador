<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas del sistema a partir de database/schema/esquema_base.sql.
     * En producción las tablas ya existen (se crearon con scripts SQL), así que
     * solo se registra la migración sin tocar nada.
     */
    public function up(): void
    {
        if (Schema::hasTable('empresas')) {
            return;
        }

        DB::unprepared('SET FOREIGN_KEY_CHECKS=0;');
        DB::unprepared(file_get_contents(database_path('schema/esquema_base.sql')));
        DB::unprepared('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        // El esquema base no se revierte: borraría todos los datos del sistema.
    }
};
