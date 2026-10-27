<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarcaController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Marca::withCount('productos')->orderBy('nombre_marca')->get(),
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

        DB::transaction(function () use ($marca, $validated) {
            $nombreAnterior = $marca->nombre_marca;
            $marca->update($validated);

            // Mantener sincronizados los productos que usan el nombre anterior
            if ($nombreAnterior !== $marca->nombre_marca) {
                Producto::where('marca', $nombreAnterior)->update(['marca' => $marca->nombre_marca]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Marca actualizada exitosamente.',
            'data' => $marca,
        ]);
    }

    public function destroy($id)
    {
        $marca = Marca::withCount('productos')->findOrFail($id);

        if ($marca->productos_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "No se puede eliminar: la marca está asignada a {$marca->productos_count} producto(s).",
            ], 422);
        }

        $marca->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Marca eliminada exitosamente.',
        ]);
    }
}
