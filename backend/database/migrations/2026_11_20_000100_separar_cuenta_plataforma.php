<?php

use App\Support\CuentaPlataforma;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * La cuenta de plataforma deja de usarse como negocio.
     * En producción, la cuenta 2 tenía al SUPERADMIN y también datos de negocio: se crea la cuenta
     * "Administración de la plataforma" con sus SUPERADMIN y la cuenta 2 queda como cliente normal.
     * Si la cuenta de plataforma no tiene datos de negocio (o no existe), no hace nada.
     */
    public function up(): void
    {
        CuentaPlataforma::separarDeNegocio();
    }

    public function down(): void
    {
        // No se revierte: devolver los SUPERADMIN a la cuenta de negocio mezclaría otra vez ambos usos.
    }
};
