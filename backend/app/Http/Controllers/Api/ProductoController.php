<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Stock;
use App\Rules\ExisteEnCuenta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $idCategoria = $request->query('id_categoria');
        $tipo = $request->query('tipo');
        $idAlmacen = $request->query('id_almacen');

        $query = Producto::with(['categoria', 'stock.almacen'])
            ->where('id_empresa', $this->empresaActiva($request))
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

    public function show(Request $request, $id)
    {
        $producto = Producto::with(['categoria', 'stock.almacen', 'series'])
            ->where('id_empresa', $this->empresaActiva($request))
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $producto,
        ]);
    }

    public function store(Request $request)
    {
        $idEmpresa = $this->empresaActiva($request);

        $validated = $request->validate([
            'tipo' => 'required|in:PRODUCTO,SERVICIO',
            'id_categoria' => ['nullable', new ExisteEnCuenta(Categoria::class)],
            'codigo_interno' => [
                'required', 'string', 'max:50',
                Rule::unique('productos', 'codigo_interno')->where('id_empresa', $idEmpresa),
            ],
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

        $validated['id_empresa'] = $idEmpresa;
        $producto = Producto::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Producto registrado exitosamente.',
            'data' => $producto->load('categoria'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $idEmpresa = $this->empresaActiva($request);
        $producto = Producto::where('id_empresa', $idEmpresa)->findOrFail($id);

        $validated = $request->validate([
            'tipo' => 'sometimes|required|in:PRODUCTO,SERVICIO',
            'id_categoria' => ['nullable', new ExisteEnCuenta(Categoria::class)],
            'codigo_interno' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('productos', 'codigo_interno')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($id, 'id_producto'),
            ],
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

    public function destroy(Request $request, $id)
    {
        $producto = Producto::where('id_empresa', $this->empresaActiva($request))->findOrFail($id);
        $producto->delete();
        Auditoria::registrar('ELIMINAR', 'producto', $producto->id_producto, "Eliminó el producto {$producto->codigo_interno} - {$producto->nombre_producto}");

        return response()->json([
            'status' => 'success',
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
