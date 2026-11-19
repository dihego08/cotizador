<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas del panel de plataforma (gestión de cuentas). Solo para el dueño del sistema.
 */
class RequiereSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->esSuperAdmin()) {
            return response()->json([
                'status' => 'error',
                'code' => 'sin_permiso',
                'message' => 'No tiene permiso para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
