<?php

namespace App\Http\Middleware;

use App\Support\CuentaActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cada petición empieza sin cuenta definida. Evita que la cuenta de una petición anterior
 * quede activa en procesos que atienden varias peticiones (Octane, pruebas).
 */
class ReiniciarCuenta
{
    public function __construct(private CuentaActual $cuentaActual)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->cuentaActual->limpiar();

        return $next($request);
    }
}
