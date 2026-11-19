<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Cuenta;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
            if ($usuario) {
                Auditoria::registrar('LOGIN_FALLIDO', 'usuario', $usuario->id_usuario, "Contraseña incorrecta para {$usuario->email}", $usuario, $usuario->id_cuenta);
            }
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$usuario->activo) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta de usuario está desactivada.'],
            ]);
        }

        if (!Cuenta::find($usuario->id_cuenta)?->estaActiva()) {
            throw ValidationException::withMessages([
                'email' => ['La cuenta está suspendida. Comuníquese con el proveedor del sistema.'],
            ]);
        }

        $token = $usuario->createToken('auth-token')->plainTextToken;
        Auditoria::registrar('LOGIN', 'usuario', $usuario->id_usuario, "Inicio de sesión de {$usuario->email}", $usuario, $usuario->id_cuenta);

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => $this->datosUsuario($usuario),
        ]);
    }

    public function me(Request $request)
    {
        $usuario = $request->user()->load(['empresa', 'permisos']);

        return response()->json([
            'status' => 'success',
            'user' => $this->datosUsuario($usuario),
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

    public function cambiarPassword(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'password_actual' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:password_actual',
        ]);

        if (!Hash::check($validated['password_actual'], $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'password_actual' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $usuario->update([
            'password_hash' => Hash::make($validated['password']),
            'debe_cambiar_password' => false,
        ]);

        Auditoria::registrar('CAMBIAR_PASSWORD', 'usuario', $usuario->id_usuario, 'Cambió su contraseña');

        // Se cierran las demás sesiones; la actual sigue abierta
        $usuario->tokens()->where('id', '!=', $usuario->currentAccessToken()->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Contraseña actualizada correctamente.',
            'user' => $this->datosUsuario($usuario->load(['empresa', 'permisos'])),
        ]);
    }

    public function recuperarPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $usuario = Usuario::where('email', $request->input('email'))->first();

        // Solo se envía a usuarios activos de cuentas activas
        if ($usuario && $usuario->activo && Cuenta::find($usuario->id_cuenta)?->estaActiva()) {
            Password::sendResetLink(['email' => $usuario->email]);
        }

        // Respuesta idéntica exista o no el correo, para no revelar qué correos están registrados
        return response()->json([
            'status' => 'success',
            'message' => 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña.',
        ]);
    }

    public function restablecerPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Usuario $usuario, string $password) {
                $usuario->update([
                    'password_hash' => Hash::make($password),
                    'debe_cambiar_password' => false,
                ]);
                $usuario->tokens()->delete();
                Auditoria::registrar('RESTABLECER_PASSWORD', 'usuario', $usuario->id_usuario, 'Restableció su contraseña por correo', $usuario, $usuario->id_cuenta);
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['El enlace no es válido o ya venció. Solicite uno nuevo.'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Contraseña restablecida. Ya puede iniciar sesión.',
        ]);
    }

    private function datosUsuario(Usuario $usuario): array
    {
        return [
            'id_usuario' => $usuario->id_usuario,
            'nombre_usuario' => $usuario->nombre_usuario,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
            'cargo' => $usuario->cargo,
            'celular' => $usuario->celular,
            'empresa' => $usuario->empresa,
            'cuenta' => Cuenta::select('id_cuenta', 'nombre', 'plan', 'es_plataforma')->find($usuario->id_cuenta),
            'permisos' => $usuario->permisos->pluck('clave'),
            'is_admin' => $usuario->esAdmin(),
            'is_superadmin' => $usuario->esSuperAdmin(),
            'debe_cambiar_password' => (bool) $usuario->debe_cambiar_password,
        ];
    }
}
