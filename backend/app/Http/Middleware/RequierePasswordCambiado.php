<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mientras el usuario tenga una contraseña temporal, solo puede cambiarla
 * (las rutas de perfil y cambio de contraseña quedan fuera de este middleware).
 */
class RequierePasswordCambiado
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->debe_cambiar_password) {
            return response()->json([
                'status' => 'error',
                'code' => 'debe_cambiar_password',
                'message' => 'Debe cambiar su contraseña temporal antes de continuar.',
            ], 403);
        }

        return $next($request);
    }
}
