<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\SerieProducto;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $idAlmacen = $request->query('id_almacen');
        $idCategoria = $request->query('id_categoria');
        $soloBajoStock = $request->boolean('solo_bajo_stock');
        $search = $request->query('search');

        $query = Stock::with(['producto.categoria', 'almacen'])
            ->when($idAlmacen, fn($q) => $q->where('id_almacen', $idAlmacen))
            ->when($soloBajoStock, fn($q) => $q->whereColumn('cantidad_actual', '<=', 'stock_minimo'))
            ->when($search, function ($q) use ($search) {
                $q->whereHas('producto', function ($sub) use ($search) {
                    $sub->where('nombre_producto', 'like', "%{$search}%")
                        ->orWhere('codigo_interno', 'like', "%{$search}%")
                        ->orWhere('marca', 'like', "%{$search}%");
                });
            })
            ->when($idCategoria, function ($q) use ($idCategoria) {
                $q->whereHas('producto', fn($sub) => $sub->where('id_categoria', $idCategoria));
            });

        $stock = $query->paginate($request->query('per_page', 25));

        return response()->json([
            'status' => 'success',
            'data' => $stock,
        ]);
    }

    public function updateLimites(Request $request, $id)
    {
        $stock = Stock::findOrFail($id);

        $validated = $request->validate([
            'stock_minimo' => 'required|numeric|min:0',
            'stock_maximo' => 'required|numeric|min:0',
        ]);

        $stock->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Límites de stock actualizados correctamente.',
            'data' => $stock->load(['producto', 'almacen']),
        ]);
    }
}
