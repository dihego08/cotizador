<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Modulo;
use App\Models\Usuario;
use App\Rules\ExisteEnCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UsuarioController extends Controller
{
    private const ROLES_ADMIN = ['ADMIN', 'ADMINISTRADOR', 'SUPERADMIN'];

    public function modulos()
    {
        $modulos = Modulo::with('submodulos')
            ->whereNull('id_padre')
            ->orderBy('orden')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $modulos,
        ]);
    }

    public function index(Request $request)
    {
        $idEmpresa = $request->query('id_empresa');

        $usuarios = Usuario::with(['empresa', 'permisos'])
            ->when($idEmpresa, fn($q) => $q->where('id_empresa', $idEmpresa))
            ->orderBy('nombre_usuario')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $usuarios,
        ]);
    }

    public function show($id)
    {
        $usuario = Usuario::with(['empresa', 'permisos'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $usuario,
        ]);
    }

    public function store(Request $request)
    {
        $this->validarLimitePlan('usuarios', Usuario::class);

        $validated = $request->validate([
            'id_empresa' => ['required', new ExisteEnCuenta(Empresa::class)],
            'nombre_usuario' => 'required|string|max:100',
            'rol' => 'required|string|max:50',
            'email' => 'required|email|max:100|unique:usuarios,email',
            'password' => 'required|string|min:8',
            'cargo' => 'nullable|string|max:100',
            'celular' => 'nullable|string|max:30',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:modulos,id_modulo',
        ]);

        $rol = strtoupper($validated['rol']);
        $this->autorizarRol($request->user(), $rol);

        $usuario = Usuario::create([
            'id_empresa' => $validated['id_empresa'],
            'nombre_usuario' => $validated['nombre_usuario'],
            'rol' => $rol,
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            // La contraseña la definió otra persona: el usuario debe cambiarla al ingresar
            'debe_cambiar_password' => true,
            'cargo' => $validated['cargo'] ?? null,
            'celular' => $validated['celular'] ?? null,
            'activo' => true,
        ]);

        if (!empty($validated['permisos'])) {
            $usuario->permisos()->sync($validated['permisos']);
        }

        Auditoria::registrar('CREAR', 'usuario', $usuario->id_usuario, "Creó el usuario {$usuario->email} con rol {$rol}");

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario creado exitosamente.',
            'data' => $usuario->load(['empresa', 'permisos']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $actual = $request->user();
        $this->autorizarSobre($actual, $usuario);

        $validated = $request->validate([
            'id_empresa' => ['sometimes', 'required', new ExisteEnCuenta(Empresa::class)],
            'nombre_usuario' => 'sometimes|required|string|max:100',
            'rol' => 'sometimes|required|string|max:50',
            'email' => 'sometimes|required|email|max:100|unique:usuarios,email,' . $id . ',id_usuario',
            'password' => 'nullable|string|min:8',
            'cargo' => 'nullable|string|max:100',
            'celular' => 'nullable|string|max:30',
            'activo' => 'boolean',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:modulos,id_modulo',
        ]);

        $rol = isset($validated['rol']) ? strtoupper($validated['rol']) : $usuario->rol;

        if ($rol !== $usuario->rol) {
            if ($actual->is($usuario)) {
                throw ValidationException::withMessages(['rol' => ['No puede cambiar su propio rol.']]);
            }
            $this->autorizarRol($actual, $rol);
        }

        $dataToUpdate = [
            'id_empresa' => $validated['id_empresa'] ?? $usuario->id_empresa,
            'nombre_usuario' => $validated['nombre_usuario'] ?? $usuario->nombre_usuario,
            'rol' => $rol,
            'email' => $validated['email'] ?? $usuario->email,
            'cargo' => $validated['cargo'] ?? $usuario->cargo,
            'celular' => $validated['celular'] ?? $usuario->celular,
            'activo' => $validated['activo'] ?? $usuario->activo,
        ];

        $cambioPassword = !empty($validated['password']);
        if ($cambioPassword) {
            $dataToUpdate['password_hash'] = Hash::make($validated['password']);
            // Contraseña asignada por otra persona: se pide cambiarla en el próximo ingreso
            $dataToUpdate['debe_cambiar_password'] = !$actual->is($usuario);
        }

        $rolAnterior = $usuario->rol;
        $usuario->update($dataToUpdate);

        if (isset($validated['permisos'])) {
            $usuario->permisos()->sync($validated['permisos']);
        }

        $cambios = array_filter([
            $rolAnterior !== $rol ? "rol {$rolAnterior} → {$rol}" : null,
            $cambioPassword ? 'contraseña' : null,
            isset($validated['permisos']) ? 'permisos' : null,
        ]);
        Auditoria::registrar('ACTUALIZAR', 'usuario', $usuario->id_usuario, "Actualizó al usuario {$usuario->email}" . ($cambios ? ' (' . implode(', ', $cambios) . ')' : ''));

        // Con contraseña nueva o desactivado, se cierran sus sesiones abiertas
        if (($cambioPassword && !$actual->is($usuario)) || !$usuario->activo) {
            $usuario->tokens()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario actualizado exitosamente.',
            'data' => $usuario->load(['empresa', 'permisos']),
        ]);
    }

    public function toggleEstado(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $actual = $request->user();
        $this->autorizarSobre($actual, $usuario);

        if ($actual->is($usuario)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No puede desactivar su propio usuario.',
            ], 422);
        }

        $usuario->activo = !$usuario->activo;
        $usuario->save();

        if (!$usuario->activo) {
            $usuario->tokens()->delete();
        }

        Auditoria::registrar($usuario->activo ? 'ACTIVAR' : 'DESACTIVAR', 'usuario', $usuario->id_usuario, ($usuario->activo ? 'Activó' : 'Desactivó') . " al usuario {$usuario->email}");

        return response()->json([
            'status' => 'success',
            'message' => $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.',
            'activo' => $usuario->activo,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $this->autorizarSobre($request->user(), $usuario);

        if ((int) $request->user()->id_usuario === (int) $usuario->id_usuario) {
            return response()->json([
                'status' => 'error',
                'message' => 'No puede eliminar su propio usuario.',
            ], 422);
        }

        // Tablas que guardan quién realizó cada operación (no todas tienen FK)
        $referencias = [
            'cotizaciones' => ['id_usuario'],
            'ventas' => ['id_usuario'],
            'venta_pagos' => ['id_usuario'],
            'movimientos' => ['id_usuario'],
            'crm_oportunidades' => ['id_usuario'],
            'crm_actividades' => ['id_usuario'],
            'helpdesk_atenciones' => ['id_tecnico'],
        ];

        foreach ($referencias as $tabla => $columnas) {
            if (!Schema::hasTable($tabla)) {
                continue;
            }
            foreach ($columnas as $columna) {
                if (DB::table($tabla)->where($columna, $usuario->id_usuario)->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No se puede eliminar: el usuario tiene operaciones registradas en el sistema. Desactívelo en su lugar.',
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($usuario) {
            $usuario->permisos()->detach();
            $usuario->tokens()->delete();
            $usuario->delete();
        });

        Auditoria::registrar('ELIMINAR', 'usuario', $usuario->id_usuario, "Eliminó al usuario {$usuario->email}");

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario eliminado exitosamente.',
        ]);
    }

    /**
     * Evita que alguien asigne más privilegios de los que tiene:
     * solo un administrador asigna ADMIN y solo el super administrador asigna SUPERADMIN.
     */
    private function autorizarRol(Usuario $actual, string $rol): void
    {
        if ($rol === 'SUPERADMIN' && !$actual->esSuperAdmin()) {
            throw ValidationException::withMessages(['rol' => ['No puede asignar el rol SUPERADMIN.']]);
        }

        if (in_array($rol, self::ROLES_ADMIN) && !$actual->esAdmin()) {
            throw ValidationException::withMessages(['rol' => ['Solo un administrador puede asignar el rol de administrador.']]);
        }
    }

    /**
     * Un usuario con permiso "usuarios" que no es administrador no puede modificar administradores.
     */
    private function autorizarSobre(Usuario $actual, Usuario $objetivo): void
    {
        if ($objetivo->esSuperAdmin() && !$actual->esSuperAdmin()) {
            abort(403, 'No puede modificar al super administrador.');
        }

        if ($objetivo->esAdmin() && !$actual->esAdmin()) {
            abort(403, 'Solo un administrador puede modificar a otro administrador.');
        }
    }
}
