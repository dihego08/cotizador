<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SerieProducto;
use Illuminate\Http\Request;

class SerieController extends Controller
{
    public function index(Request $request)
    {
        $idProducto = $request->query('id_producto');
        $idAlmacen = $request->query('id_almacen');
        $estado = $request->query('estado');
        $search = $request->query('search');

        $series = SerieProducto::with(['producto', 'almacen'])
            ->when($idProducto, fn($q) => $q->where('id_producto', $idProducto))
            ->when($idAlmacen, fn($q) => $q->where('id_almacen', $idAlmacen))
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->when($search, function ($q) use ($search) {
                $q->where('numero_serie', 'like', "%{$search}%")
                    ->orWhereHas('producto', fn($p) => $p->where('nombre_producto', 'like', "%{$search}%"));
            })
            ->orderBy('id_serie', 'desc')
            ->paginate($request->query('per_page', 25));

        return response()->json([
            'status' => 'success',
            'data' => $series,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_producto' => 'required|exists:productos,id_producto',
            'id_almacen' => 'required|exists:almacenes,id_almacen',
            'numero_serie' => 'required|string|max:100|unique:series_productos,numero_serie',
            'estado' => 'nullable|in:DISPONIBLE,VENDIDO,BAJA,TRANSITO',
        ]);

        $serie = SerieProducto::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Número de serie registrado con éxito.',
            'data' => $serie->load(['producto', 'almacen']),
        ], 201);
    }

    public function updateEstado(Request $request, $id)
    {
        $serie = SerieProducto::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:DISPONIBLE,VENDIDO,BAJA,TRANSITO',
        ]);

        $serie->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Estado de serie actualizado con éxito.',
            'data' => $serie->load(['producto', 'almacen']),
        ]);
    }
}
