<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::withCount('productos')->orderBy('nombre_categoria')->get();

        return response()->json([
            'status' => 'success',
            'data' => $categorias,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_categoria' => 'required|string|max:100|unique:categorias,nombre_categoria',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $categoria = Categoria::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría registrada con éxito.',
            'data' => $categoria,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $validated = $request->validate([
            'nombre_categoria' => 'sometimes|required|string|max:100|unique:categorias,nombre_categoria,' . $id . ',id_categoria',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $categoria->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría actualizada con éxito.',
            'data' => $categoria,
        ]);
    }

    public function destroy($id)
    {
        $categoria = Categoria::withCount('productos')->findOrFail($id);

        if ($categoria->productos_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "No se puede eliminar: la categoría está asignada a {$categoria->productos_count} producto(s).",
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría eliminada con éxito.',
        ]);
    }
}
