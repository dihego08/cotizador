<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\Empresa;
use App\Rules\ExisteEnCuenta;
use App\Support\CuentaActual;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    /**
     * Empresa con la que trabaja el usuario en esta petición.
     * Los administradores pueden elegir cualquier empresa de su cuenta con el selector
     * del frontend (header X-Empresa-Id); el resto siempre trabaja con su propia empresa.
     */
    protected function empresaActiva(Request $request): int
    {
        if ($request->attributes->has('empresa_activa')) {
            return $request->attributes->get('empresa_activa');
        }

        $usuario = $request->user();
        $idEmpresa = (int) $usuario->id_empresa;
        $idHeader = (int) $request->header('X-Empresa-Id');

        // Empresa está filtrada por cuenta: un id de otra cuenta no existe
        if ($usuario->esAdmin() && $idHeader > 0 && Empresa::whereKey($idHeader)->exists()) {
            $idEmpresa = $idHeader;
        }

        $request->attributes->set('empresa_activa', $idEmpresa);

        return $idEmpresa;
    }

    protected function idCuenta(): int
    {
        return app(CuentaActual::class)->id();
    }

    /**
     * Regla para el id_empresa que llega en el formulario:
     * un administrador puede usar cualquier empresa de su cuenta, el resto solo la suya.
     */
    protected function reglaEmpresa(Request $request)
    {
        $usuario = $request->user();

        return $usuario->esAdmin()
            ? new ExisteEnCuenta(Empresa::class)
            : Rule::in([$usuario->id_empresa]);
    }

    /**
     * Rule::unique limitado a la cuenta actual (la regla nativa no aplica el filtro de cuenta).
     */
    protected function unicoEnCuenta(string $tabla, string $columna)
    {
        return Rule::unique($tabla, $columna)->where('id_cuenta', $this->idCuenta());
    }

    /**
     * Rechaza la creación si la cuenta llegó al máximo de su plan para el recurso.
     */
    protected function validarLimitePlan(string $recurso, string $modelo): void
    {
        $limite = Cuenta::find($this->idCuenta())?->limite($recurso);

        if ($limite !== null && $modelo::count() >= $limite) {
            throw ValidationException::withMessages([
                'plan' => ["Su plan permite hasta {$limite} {$recurso}. Comuníquese con el proveedor para ampliarlo."],
            ]);
        }
    }
}
