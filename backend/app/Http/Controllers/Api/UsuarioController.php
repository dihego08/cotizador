<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
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
        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'nombre_usuario' => 'required|string|max:100',
            'rol' => 'required|string|max:50',
            'email' => 'required|email|max:100|unique:usuarios,email',
            'password' => 'required|string|min:6',
            'cargo' => 'nullable|string|max:100',
            'celular' => 'nullable|string|max:30',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:modulos,id_modulo',
        ]);

        $usuario = Usuario::create([
            'id_empresa' => $validated['id_empresa'],
            'nombre_usuario' => $validated['nombre_usuario'],
            'rol' => strtoupper($validated['rol']),
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'cargo' => $validated['cargo'] ?? null,
            'celular' => $validated['celular'] ?? null,
            'activo' => true,
        ]);

        if (!empty($validated['permisos'])) {
            $usuario->permisos()->sync($validated['permisos']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario creado exitosamente.',
            'data' => $usuario->load(['empresa', 'permisos']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $validated = $request->validate([
            'id_empresa' => 'sometimes|required|exists:empresas,id_empresa',
            'nombre_usuario' => 'sometimes|required|string|max:100',
            'rol' => 'sometimes|required|string|max:50',
            'email' => 'sometimes|required|email|max:100|unique:usuarios,email,' . $id . ',id_usuario',
            'password' => 'nullable|string|min:6',
            'cargo' => 'nullable|string|max:100',
            'celular' => 'nullable|string|max:30',
            'activo' => 'boolean',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:modulos,id_modulo',
        ]);

        $dataToUpdate = [
            'id_empresa' => $validated['id_empresa'] ?? $usuario->id_empresa,
            'nombre_usuario' => $validated['nombre_usuario'] ?? $usuario->nombre_usuario,
            'rol' => isset($validated['rol']) ? strtoupper($validated['rol']) : $usuario->rol,
            'email' => $validated['email'] ?? $usuario->email,
            'cargo' => $validated['cargo'] ?? $usuario->cargo,
            'celular' => $validated['celular'] ?? $usuario->celular,
            'activo' => $validated['activo'] ?? $usuario->activo,
        ];

        if (!empty($validated['password'])) {
            $dataToUpdate['password_hash'] = Hash::make($validated['password']);
        }

        $usuario->update($dataToUpdate);

        if (isset($validated['permisos'])) {
            $usuario->permisos()->sync($validated['permisos']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario actualizado exitosamente.',
            'data' => $usuario->load(['empresa', 'permisos']),
        ]);
    }

    public function toggleEstado($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->activo = !$usuario->activo;
        $usuario->save();

        return response()->json([
            'status' => 'success',
            'message' => $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.',
            'activo' => $usuario->activo,
        ]);
    }
}
