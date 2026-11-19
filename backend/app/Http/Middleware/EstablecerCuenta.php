<?php

namespace App\Http\Middleware;

use App\Models\Cuenta;
use App\Support\CuentaActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Define la cuenta de la petición a partir del usuario autenticado.
 * Va siempre después de auth:sanctum.
 */
class EstablecerCuenta
{
    public function __construct(private CuentaActual $cuentaActual)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->activo) {
            return $this->rechazar('Su usuario está desactivado.', 'usuario_inactivo');
        }

        $cuenta = Cuenta::find($usuario->id_cuenta);

        if (!$cuenta) {
            return $this->rechazar('Su usuario no pertenece a ninguna cuenta.', 'sin_cuenta');
        }

        if (!$cuenta->estaActiva()) {
            return $this->rechazar('La cuenta está suspendida. Comuníquese con el proveedor del sistema.', 'cuenta_suspendida');
        }

        $this->cuentaActual->definir($cuenta->id_cuenta);

        return $next($request);
    }

    private function rechazar(string $mensaje, string $codigo): Response
    {
        return response()->json([
            'status' => 'error',
            'code' => $codigo,
            'message' => $mensaje,
        ], 403);
    }
}
