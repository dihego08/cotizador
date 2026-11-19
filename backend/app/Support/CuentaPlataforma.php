<?php

namespace App\Support;

use App\Models\Cuenta;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * La cuenta de plataforma es solo para administrar las cuentas de clientes:
 * no tiene datos de negocio y sus usuarios son SUPERADMIN.
 * Como todo usuario pertenece a una empresa, la cuenta tiene una empresa interna.
 */
class CuentaPlataforma
{
    public const NOMBRE = 'Administración de la plataforma';
    public const RUC_INTERNO = '00000000000';

    // Tablas que indican que una cuenta se usa como negocio
    private const TABLAS_NEGOCIO = [
        'productos', 'clientes', 'almacenes', 'cotizaciones', 'ventas',
        'crm_oportunidades', 'helpdesk_atenciones', 'categorias', 'marcas',
    ];

    /**
     * Devuelve la cuenta de plataforma; si no existe, la crea con su empresa interna.
     */
    public static function asegurar(): Cuenta
    {
        return Cuenta::plataforma() ?? DB::transaction(fn () => self::crear());
    }

    public static function empresaInterna(Cuenta $cuenta): Empresa
    {
        return Empresa::withoutGlobalScope('cuenta')
            ->where('id_cuenta', $cuenta->id_cuenta)
            ->orderByDesc('es_principal')
            ->firstOrFail();
    }

    /**
     * Si la cuenta marcada como plataforma también se usa como negocio, crea una cuenta de
     * plataforma nueva y mueve allí a sus SUPERADMIN. Los datos de negocio y el resto de usuarios
     * se quedan en la cuenta original, que pasa a ser una cuenta de cliente normal.
     * Devuelve la nueva cuenta de plataforma, o null si no hubo nada que separar.
     */
    public static function separarDeNegocio(): ?Cuenta
    {
        $actual = Cuenta::plataforma();

        if (!$actual || !self::tieneUsoDeNegocio($actual)) {
            return null;
        }

        return DB::transaction(function () use ($actual) {
            $superadmins = Usuario::withoutGlobalScope('cuenta')
                ->where('id_cuenta', $actual->id_cuenta)
                ->where('rol', 'SUPERADMIN')
                ->pluck('id_usuario');

            // Solo puede haber una cuenta con la marca (índice único): primero se quita a la actual
            Cuenta::whereKey($actual->id_cuenta)->update(['es_plataforma' => null]);
            $nueva = self::crear();
            $empresa = self::empresaInterna($nueva);

            // Se actualiza con el query builder: el modelo no permite cambiar un registro de cuenta
            DB::table('usuarios')->whereIn('id_usuario', $superadmins)->update([
                'id_cuenta' => $nueva->id_cuenta,
                'id_empresa' => $empresa->id_empresa,
            ]);

            // Sus sesiones abiertas pertenecían a la cuenta anterior
            PersonalAccessToken::where('tokenable_type', Usuario::class)->whereIn('tokenable_id', $superadmins)->delete();

            return $nueva;
        });
    }

    public static function tieneUsoDeNegocio(Cuenta $cuenta): bool
    {
        $usuariosComunes = DB::table('usuarios')
            ->where('id_cuenta', $cuenta->id_cuenta)
            ->where('rol', '!=', 'SUPERADMIN')
            ->exists();

        if ($usuariosComunes) {
            return true;
        }

        foreach (self::TABLAS_NEGOCIO as $tabla) {
            if (DB::table($tabla)->where('id_cuenta', $cuenta->id_cuenta)->exists()) {
                return true;
            }
        }

        return false;
    }

    private static function crear(): Cuenta
    {
        $cuenta = Cuenta::create(['nombre' => self::NOMBRE, 'estado' => 'ACTIVA']);
        $cuenta->forceFill(['es_plataforma' => true])->save();

        $empresa = new Empresa([
            'razon_social' => self::NOMBRE,
            'ruc' => self::RUC_INTERNO,
            'es_principal' => true,
        ]);
        $empresa->id_cuenta = $cuenta->id_cuenta;
        $empresa->save();

        return $cuenta;
    }
}
