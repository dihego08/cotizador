<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Empresa::all(),
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
        $validated = $request->validate([
            'razon_social' => 'required|string|max:150',
            'ruc' => 'required|string|size:11|unique:empresas,ruc',
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
            'ruc' => 'sometimes|required|string|size:11|unique:empresas,ruc,' . $id . ',id_empresa',
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

        return response()->json([
            'status' => 'success',
            'message' => 'Empresa actualizada exitosamente.',
            'data' => $empresa,
        ]);
    }
}
