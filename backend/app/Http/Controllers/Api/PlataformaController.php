<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Cuenta;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Support\CuentaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Panel del dueño de la plataforma: alta y administración de las cuentas (clientes del SaaS).
 * Trabaja sobre todas las cuentas, por eso quita el filtro de cuenta de forma explícita.
 */
class PlataformaController extends Controller
{
    public function index()
    {
        $cuentas = Cuenta::withCount([
            'empresas' => fn($q) => $q->withoutGlobalScope('cuenta'),
            'usuarios' => fn($q) => $q->withoutGlobalScope('cuenta'),
        ])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $cuentas,
        ]);
    }

    public function show($id)
    {
        $cuenta = Cuenta::findOrFail($id);

        $cuenta->setRelation('empresas', Empresa::withoutGlobalScope('cuenta')
            ->where('id_cuenta', $cuenta->id_cuenta)
            ->orderBy('razon_social')
            ->get(['id_empresa', 'id_cuenta', 'razon_social', 'ruc', 'es_principal']));

        $cuenta->setRelation('usuarios', Usuario::withoutGlobalScope('cuenta')
            ->where('id_cuenta', $cuenta->id_cuenta)
            ->orderBy('nombre_usuario')
            ->get(['id_usuario', 'id_cuenta', 'id_empresa', 'nombre_usuario', 'email', 'rol', 'activo', 'debe_cambiar_password']));

        return response()->json([
            'status' => 'success',
            'data' => $cuenta,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'plan' => 'nullable|string|max:50',
            'max_empresas' => 'nullable|integer|min:1',
            'max_usuarios' => 'nullable|integer|min:1',
            'max_almacenes' => 'nullable|integer|min:1',
            'notas' => 'nullable|string',
            'empresa.razon_social' => 'required|string|max:150',
            'empresa.ruc' => 'required|string|size:11',
            'empresa.direccion' => 'nullable|string|max:255',
            'empresa.telefono' => 'nullable|string|max:20',
            'empresa.email' => 'nullable|email|max:100',
            'admin.nombre_usuario' => 'required|string|max:100',
            'admin.email' => 'required|email|max:100|unique:usuarios,email',
            'admin.password' => 'required|string|min:8',
        ]);

        $cuenta = DB::transaction(function () use ($validated) {
            $cuenta = Cuenta::create([
                'nombre' => $validated['nombre'],
                'estado' => 'ACTIVA',
                'plan' => $validated['plan'] ?? null,
                'max_empresas' => $validated['max_empresas'] ?? null,
                'max_usuarios' => $validated['max_usuarios'] ?? null,
                'max_almacenes' => $validated['max_almacenes'] ?? null,
                'notas' => $validated['notas'] ?? null,
            ]);

            // Los registros se crean dentro de la cuenta nueva, no en la del super administrador
            app(CuentaActual::class)->ejecutarComo($cuenta->id_cuenta, function () use ($validated) {
                $empresa = Empresa::create([
                    ...$validated['empresa'],
                    'es_principal' => true,
                ]);

                Usuario::create([
                    'id_empresa' => $empresa->id_empresa,
                    'nombre_usuario' => $validated['admin']['nombre_usuario'],
                    'rol' => 'ADMIN',
                    'email' => $validated['admin']['email'],
                    'password_hash' => Hash::make($validated['admin']['password']),
                    'debe_cambiar_password' => true,
                    'activo' => true,
                ]);
            });

            return $cuenta;
        });

        Auditoria::registrar('CREAR', 'cuenta', $cuenta->id_cuenta, "Creó la cuenta {$cuenta->nombre}");

        return response()->json([
            'status' => 'success',
            'message' => 'Cuenta creada. El administrador deberá cambiar su contraseña en el primer ingreso.',
            'data' => $cuenta,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $cuenta = Cuenta::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:150',
            'plan' => 'nullable|string|max:50',
            'max_empresas' => 'nullable|integer|min:1',
            'max_usuarios' => 'nullable|integer|min:1',
            'max_almacenes' => 'nullable|integer|min:1',
            'notas' => 'nullable|string',
        ]);

        $cuenta->update($validated);
        Auditoria::registrar('ACTUALIZAR', 'cuenta', $cuenta->id_cuenta, "Actualizó el plan de la cuenta {$cuenta->nombre}");

        return response()->json([
            'status' => 'success',
            'message' => 'Cuenta actualizada.',
            'data' => $cuenta,
        ]);
    }

    public function cambiarEstado(Request $request, $id)
    {
        $cuenta = Cuenta::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:ACTIVA,SUSPENDIDA',
        ]);

        if ($cuenta->esPlataforma() && $validated['estado'] === 'SUSPENDIDA') {
            return response()->json([
                'status' => 'error',
                'message' => 'No puede suspender la cuenta de la plataforma.',
            ], 422);
        }

        $cuenta->update(['estado' => $validated['estado']]);
        Auditoria::registrar($validated['estado'] === 'ACTIVA' ? 'REACTIVAR' : 'SUSPENDER', 'cuenta', $cuenta->id_cuenta, ($validated['estado'] === 'ACTIVA' ? 'Reactivó' : 'Suspendió') . " la cuenta {$cuenta->nombre}");

        // Al suspender se cierran todas las sesiones abiertas de la cuenta
        if ($validated['estado'] === 'SUSPENDIDA') {
            $idsUsuarios = Usuario::withoutGlobalScope('cuenta')->where('id_cuenta', $cuenta->id_cuenta)->pluck('id_usuario');
            PersonalAccessToken::where('tokenable_type', Usuario::class)->whereIn('tokenable_id', $idsUsuarios)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => $validated['estado'] === 'ACTIVA' ? 'Cuenta reactivada.' : 'Cuenta suspendida.',
            'data' => $cuenta,
        ]);
    }
}
