<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Almacen;
use App\Models\Usuario;
use App\Rules\ExisteEnCuenta;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    public function index(Request $request)
    {
        $almacenes = Almacen::with(['empresa', 'responsable'])
            ->where('id_empresa', $this->empresaActiva($request))
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $almacenes,
        ]);
    }

    public function show(Request $request, $id)
    {
        $almacen = Almacen::with(['empresa', 'responsable', 'stock.producto'])
            ->where('id_empresa', $this->empresaActiva($request))
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $almacen,
        ]);
    }

    public function store(Request $request)
    {
        $this->validarLimitePlan('almacenes', Almacen::class);

        $validated = $request->validate([
            'id_empresa' => ['required', $this->reglaEmpresa($request)],
            'id_responsable' => ['nullable', new ExisteEnCuenta(Usuario::class)],
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
        $almacen = Almacen::where('id_empresa', $this->empresaActiva($request))->findOrFail($id);

        $validated = $request->validate([
            'id_empresa' => ['sometimes', 'required', $this->reglaEmpresa($request)],
            'id_responsable' => ['nullable', new ExisteEnCuenta(Usuario::class)],
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

    public function destroy(Request $request, $id)
    {
        $almacen = Almacen::where('id_empresa', $this->empresaActiva($request))->findOrFail($id);
        $almacen->delete();
        Auditoria::registrar('ELIMINAR', 'almacen', $almacen->id_almacen, "Eliminó el almacén {$almacen->nombre_almacen}");

        return response()->json([
            'status' => 'success',
            'message' => 'Almacén eliminado correctamente.',
        ]);
    }
}
