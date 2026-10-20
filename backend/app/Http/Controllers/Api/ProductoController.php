<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Stock;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $idCategoria = $request->query('id_categoria');
        $tipo = $request->query('tipo');
        $idAlmacen = $request->query('id_almacen');

        $query = Producto::with(['categoria', 'stock.almacen'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nombre_producto', 'like', "%{$search}%")
                        ->orWhere('codigo_interno', 'like', "%{$search}%")
                        ->orWhere('codigo_barra', 'like', "%{$search}%")
                        ->orWhere('codigo_proveedor', 'like', "%{$search}%")
                        ->orWhere('numero_parte', 'like', "%{$search}%")
                        ->orWhere('marca', 'like', "%{$search}%")
                        ->orWhere('modelo', 'like', "%{$search}%");
                });
            })
            ->when($idCategoria, fn($q) => $q->where('id_categoria', $idCategoria))
            ->when($tipo, fn($q) => $q->where('tipo', $tipo));

        $productos = $query->orderBy('nombre_producto')->paginate($request->query('per_page', 25));

        return response()->json([
            'status' => 'success',
            'data' => $productos,
        ]);
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'stock.almacen', 'series'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $producto,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:PRODUCTO,SERVICIO',
            'id_categoria' => 'nullable|exists:categorias,id_categoria',
            'codigo_interno' => 'required|string|max:50|unique:productos,codigo_interno',
            'codigo_proveedor' => 'nullable|string|max:50',
            'nombre_producto' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'numero_parte' => 'nullable|string|max:100',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'unidad_medida' => 'required|string|max:20',
            'moneda' => 'nullable|string|max:5',
            'precio_costo' => 'nullable|numeric|min:0',
            'porcentaje_utilidad' => 'nullable|numeric|min:0',
            'precio_venta' => 'nullable|numeric|min:0',
            'codigo_barra' => 'nullable|string|max:100',
        ]);

        $costo = $validated['precio_costo'] ?? 0;
        $utilidad = $validated['porcentaje_utilidad'] ?? 0;
        
        if (empty($validated['precio_venta']) && $costo > 0) {
            $validated['precio_venta'] = round($costo * (1 + ($utilidad / 100)), 2);
        }

        $producto = Producto::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Producto registrado exitosamente.',
            'data' => $producto->load('categoria'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $validated = $request->validate([
            'tipo' => 'sometimes|required|in:PRODUCTO,SERVICIO',
            'id_categoria' => 'nullable|exists:categorias,id_categoria',
            'codigo_interno' => 'sometimes|required|string|max:50|unique:productos,codigo_interno,' . $id . ',id_producto',
            'codigo_proveedor' => 'nullable|string|max:50',
            'nombre_producto' => 'sometimes|required|string|max:150',
            'descripcion' => 'nullable|string',
            'numero_parte' => 'nullable|string|max:100',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'unidad_medida' => 'sometimes|required|string|max:20',
            'moneda' => 'nullable|string|max:5',
            'precio_costo' => 'nullable|numeric|min:0',
            'porcentaje_utilidad' => 'nullable|numeric|min:0',
            'precio_venta' => 'nullable|numeric|min:0',
            'codigo_barra' => 'nullable|string|max:100',
        ]);

        $producto->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Producto actualizado exitosamente.',
            'data' => $producto->load('categoria'),
        ]);
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
