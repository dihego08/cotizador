<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\SerieProducto;
use App\Rules\ExisteEnCuenta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SerieController extends Controller
{
    public function index(Request $request)
    {
        $idProducto = $request->query('id_producto');
        $idAlmacen = $request->query('id_almacen');
        $estado = $request->query('estado');
        $search = $request->query('search');

        $idEmpresa = $this->empresaActiva($request);

        $series = SerieProducto::with(['producto', 'almacen'])
            ->whereHas('producto', fn($q) => $q->where('id_empresa', $idEmpresa))
            ->when($idProducto, fn($q) => $q->where('id_producto', $idProducto))
            ->when($idAlmacen, fn($q) => $q->where('id_almacen', $idAlmacen))
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->when($search, function ($q) use ($search) {
                $q->where(fn($sub) => $sub->where('numero_serie', 'like', "%{$search}%")
                    ->orWhereHas('producto', fn($p) => $p->where('nombre_producto', 'like', "%{$search}%")));
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
        $deLaEmpresa = fn($q) => $q->where('id_empresa', $this->empresaActiva($request));

        $validated = $request->validate([
            'id_producto' => ['required', new ExisteEnCuenta(Producto::class, $deLaEmpresa)],
            'id_almacen' => ['required', new ExisteEnCuenta(Almacen::class, $deLaEmpresa)],
            // El número de serie es único por producto (dos fabricantes pueden repetirlo)
            'numero_serie' => [
                'required', 'string', 'max:100',
                Rule::unique('series_productos', 'numero_serie')->where('id_producto', $request->input('id_producto')),
            ],
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
        $serie = SerieProducto::whereHas('producto', fn($q) => $q->where('id_empresa', $this->empresaActiva($request)))
            ->findOrFail($id);

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
