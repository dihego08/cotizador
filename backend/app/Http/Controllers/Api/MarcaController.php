<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Marca::orderBy('nombre_marca')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_marca' => 'required|string|max:100|unique:marcas,nombre_marca',
        ]);

        $marca = Marca::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Marca creada exitosamente.',
            'data' => $marca,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $marca = Marca::findOrFail($id);

        $validated = $request->validate([
            'nombre_marca' => 'required|string|max:100|unique:marcas,nombre_marca,' . $id . ',id_marca',
        ]);

        $marca->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Marca actualizada exitosamente.',
            'data' => $marca,
        ]);
    }

    public function destroy($id)
    {
        $marca = Marca::findOrFail($id);
        $marca->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Marca eliminada exitosamente.',
        ]);
    }
}
