<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = Usuario::with(['empresa', 'permisos'])
            ->where('email', $validated['email'])
            ->first();

        if (!$usuario || !Hash::check($validated['password'], $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$usuario->activo) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta de usuario está desactivada.'],
            ]);
        }

        $token = $usuario->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_usuario' => $usuario->nombre_usuario,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'cargo' => $usuario->cargo,
                'celular' => $usuario->celular,
                'empresa' => $usuario->empresa,
                'permisos' => $usuario->permisos->pluck('clave'),
                'is_admin' => in_array(strtoupper($usuario->rol), ['ADMIN', 'ADMINISTRADOR']),
            ],
        ]);
    }

    public function me(Request $request)
    {
        $usuario = $request->user()->load(['empresa', 'permisos']);

        return response()->json([
            'status' => 'success',
            'user' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre_usuario' => $usuario->nombre_usuario,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'cargo' => $usuario->cargo,
                'celular' => $usuario->celular,
                'empresa' => $usuario->empresa,
                'permisos' => $usuario->permisos->pluck('clave'),
                'is_admin' => in_array(strtoupper($usuario->rol), ['ADMIN', 'ADMINISTRADOR']),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
