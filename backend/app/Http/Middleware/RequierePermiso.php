<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida en el servidor el permiso del módulo (el menú del frontend solo oculta opciones).
 * Uso: ->middleware('permiso:cotizaciones') o 'permiso:productos,cotizaciones' (basta uno).
 */
class RequierePermiso
{
    public function handle(Request $request, Closure $next, string ...$claves): Response
    {
        $usuario = $request->user();

        foreach ($claves as $clave) {
            if ($usuario->hasPermiso($clave)) {
                return $next($request);
            }
        }

        return response()->json([
            'status' => 'error',
            'code' => 'sin_permiso',
            'message' => 'No tiene permiso para realizar esta acción.',
        ], 403);
    }
}
