<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        // Solo los administradores ven y eligen entre todas las empresas de su cuenta
        $usuario = $request->user();

        $empresas = Empresa::query()
            ->when(!$usuario->esAdmin(), fn($q) => $q->whereKey($usuario->id_empresa))
            ->orderBy('razon_social')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $empresas,
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Empresa::findOrFail($id),
        ]);
    }

    public function store(Request $request)
    {
        $this->validarLimitePlan('empresas', Empresa::class);

        $validated = $request->validate([
            'razon_social' => 'required|string|max:150',
            'ruc' => ['required', 'string', 'size:11', $this->unicoEnCuenta('empresas', 'ruc')],
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'nombre_gerente' => 'nullable|string|max:150',
            'cargo_gerente' => 'nullable|string|max:100',
            'cel_gerente' => 'nullable|string|max:50',
            'es_principal' => 'boolean',
        ]);

        if (!empty($validated['es_principal'])) {
            Empresa::query()->update(['es_principal' => false]);
        }

        $empresa = Empresa::create($validated);
        Auditoria::registrar('CREAR', 'empresa', $empresa->id_empresa, "Registró la empresa {$empresa->razon_social} (RUC {$empresa->ruc})");

        return response()->json([
            'status' => 'success',
            'message' => 'Empresa creada exitosamente.',
            'data' => $empresa,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);

        $validated = $request->validate([
            'razon_social' => 'sometimes|required|string|max:150',
            'ruc' => [
                'sometimes', 'required', 'string', 'size:11',
                $this->unicoEnCuenta('empresas', 'ruc')->ignore($id, 'id_empresa'),
            ],
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'nombre_gerente' => 'nullable|string|max:150',
            'cargo_gerente' => 'nullable|string|max:100',
            'cel_gerente' => 'nullable|string|max:50',
            'es_principal' => 'boolean',
        ]);

        if (!empty($validated['es_principal'])) {
            Empresa::where('id_empresa', '!=', $id)->update(['es_principal' => false]);
        }

        $empresa->update($validated);
        Auditoria::registrar('ACTUALIZAR', 'empresa', $empresa->id_empresa, "Actualizó la empresa {$empresa->razon_social}");

        return response()->json([
            'status' => 'success',
            'message' => 'Empresa actualizada exitosamente.',
            'data' => $empresa,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);

        if ((int) $request->user()->id_empresa === (int) $empresa->id_empresa) {
            return response()->json([
                'status' => 'error',
                'message' => 'No puede eliminar la empresa a la que pertenece su usuario.',
            ], 422);
        }

        if (Empresa::count() <= 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'No puede eliminar la única empresa registrada.',
            ], 422);
        }

        // Registros que dependen de la empresa
        $referencias = [
            'usuarios' => 'usuario(s)',
            'almacenes' => 'almacén(es)',
            'productos' => 'producto(s)',
            'cotizaciones' => 'cotización(es)',
            'ventas' => 'documento(s) de venta',
            'crm_oportunidades' => 'oportunidad(es) CRM',
            'helpdesk_atenciones' => 'atención(es) de helpdesk',
        ];

        $asociados = [];
        foreach ($referencias as $tabla => $etiqueta) {
            if (!Schema::hasTable($tabla) || !Schema::hasColumn($tabla, 'id_empresa')) {
                continue;
            }
            $total = DB::table($tabla)->where('id_empresa', $empresa->id_empresa)->count();
            if ($total > 0) {
                $asociados[] = "{$total} {$etiqueta}";
            }
        }

        if (!empty($asociados)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar: la empresa tiene ' . implode(', ', $asociados) . ' asociados.',
            ], 422);
        }

        $logo = $empresa->tieneLogo() ? $empresa->logo_path : null;

        DB::transaction(function () use ($empresa) {
            $empresa->delete();

            // Si era la principal, otra empresa toma su lugar
            if ($empresa->es_principal) {
                Empresa::orderBy('id_empresa')->limit(1)->update(['es_principal' => true]);
            }
        });

        if ($logo) {
            Storage::disk(config('filesystems.archivos'))->delete($logo);
        }

        Auditoria::registrar('ELIMINAR', 'empresa', $empresa->id_empresa, "Eliminó la empresa {$empresa->razon_social} (RUC {$empresa->ruc})");

        return response()->json([
            'status' => 'success',
            'message' => 'Empresa eliminada exitosamente.',
        ]);
    }

    public function uploadLogo(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048', // max 2MB
        ]);

        $anterior = $empresa->tieneLogo() ? $empresa->logo_path : null;

        $empresa->logo_path = $request->file('logo')->store("cuentas/{$empresa->id_cuenta}/logos", config('filesystems.archivos'));
        $empresa->save();

        if ($anterior) {
            Storage::disk(config('filesystems.archivos'))->delete($anterior);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logo actualizado exitosamente.',
            'data' => $empresa,
        ]);
    }

    public function deleteLogo($id)
    {
        $empresa = Empresa::findOrFail($id);

        if ($empresa->tieneLogo()) {
            Storage::disk(config('filesystems.archivos'))->delete($empresa->logo_path);
        }

        $empresa->logo_path = null;
        $empresa->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Logo eliminado exitosamente.',
            'data' => $empresa,
        ]);
    }

    // Ruta pública: las etiquetas <img> no pueden enviar el token Bearer
    public function logo($id)
    {
        $empresa = Empresa::findOrFail($id);

        if (!$empresa->tieneLogo()) {
            abort(404);
        }

        return Storage::disk(config('filesystems.archivos'))->response($empresa->logo_path, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
