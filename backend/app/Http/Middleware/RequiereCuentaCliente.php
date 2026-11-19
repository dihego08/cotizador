<?php

namespace App\Http\Middleware;

use App\Models\Cuenta;
use App\Support\CuentaActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los módulos del negocio (productos, ventas, clientes...) son para las cuentas de clientes.
 * La cuenta de plataforma solo administra cuentas desde el panel de plataforma.
 */
class RequiereCuentaCliente
{
    public function __construct(private CuentaActual $cuentaActual)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (Cuenta::find($this->cuentaActual->id())?->esPlataforma()) {
            return response()->json([
                'status' => 'error',
                'code' => 'solo_plataforma',
                'message' => 'La cuenta de plataforma solo administra cuentas de clientes. Para usar los módulos ingrese con un usuario de una cuenta de cliente.',
            ], 403);
        }

        return $next($request);
    }
}
