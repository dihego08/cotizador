<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    public function index(Request $request)
    {
        $idEmpresa = $request->query('id_empresa', $request->user()->id_empresa);

        $almacenes = Almacen::with(['empresa', 'responsable'])
            ->when($idEmpresa, fn($q) => $q->where('id_empresa', $idEmpresa))
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $almacenes,
        ]);
    }

    public function show($id)
    {
        $almacen = Almacen::with(['empresa', 'responsable', 'stock.producto'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $almacen,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'id_responsable' => 'nullable|exists:usuarios,id_usuario',
            'nombre_almacen' => 'required|string|max:100',
            'ubicacion' => 'nullable|string|max:255',
        ]);

        $almacen = Almacen::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Almacén registrado con éxito.',
            'data' => $almacen->load(['empresa', 'responsable']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $almacen = Almacen::findOrFail($id);

        $validated = $request->validate([
            'id_empresa' => 'sometimes|required|exists:empresas,id_empresa',
            'id_responsable' => 'nullable|exists:usuarios,id_usuario',
            'nombre_almacen' => 'sometimes|required|string|max:100',
            'ubicacion' => 'nullable|string|max:255',
        ]);

        $almacen->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Almacén actualizado con éxito.',
            'data' => $almacen->load(['empresa', 'responsable']),
        ]);
    }

    public function destroy($id)
    {
        $almacen = Almacen::findOrFail($id);
        $almacen->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Almacén eliminado correctamente.',
        ]);
    }
}
