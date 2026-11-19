<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca la cuenta del dueño de la plataforma. Solo sus usuarios pueden ser SUPERADMIN.
     * La columna vale 1 o NULL: el índice único admite muchos NULL pero un solo 1,
     * así la base de datos garantiza que exista una única cuenta de plataforma.
     */
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->boolean('es_plataforma')->nullable()->after('estado');
            $table->unique('es_plataforma', 'uk_cuentas_plataforma');
        });

        // La cuenta de plataforma es la del primer SUPERADMIN existente (en producción, la cuenta 2)
        $idCuenta = DB::table('usuarios')->where('rol', 'SUPERADMIN')->orderBy('id_usuario')->value('id_cuenta');

        if ($idCuenta) {
            DB::table('cuentas')->where('id_cuenta', $idCuenta)->update(['es_plataforma' => true]);

            // Un SUPERADMIN fuera de la cuenta de plataforma queda como administrador de su cuenta
            DB::table('usuarios')
                ->where('rol', 'SUPERADMIN')
                ->where('id_cuenta', '!=', $idCuenta)
                ->update(['rol' => 'ADMIN']);
        }
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->dropUnique('uk_cuentas_plataforma');
            $table->dropColumn('es_plataforma');
        });
    }
};
