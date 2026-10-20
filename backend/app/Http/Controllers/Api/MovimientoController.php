<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movimiento;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovimientoController extends Controller
{
    public function index(Request $request)
    {
        $idAlmacen = $request->query('id_almacen');
        $idProducto = $request->query('id_producto');
        $tipo = $request->query('tipo_movimiento');
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');

        $movimientos = Movimiento::with(['producto', 'almacen', 'almacenOrigen', 'almacenDestino', 'usuario'])
            ->when($idAlmacen, function ($q) use ($idAlmacen) {
                $q->where(fn($sub) => $sub->where('id_almacen', $idAlmacen)
                    ->orWhere('id_almacen_origen', $idAlmacen)
                    ->orWhere('id_almacen_destino', $idAlmacen));
            })
            ->when($idProducto, fn($q) => $q->where('id_producto', $idProducto))
            ->when($tipo, fn($q) => $q->where('tipo_movimiento', $tipo))
            ->when($fechaInicio, fn($q) => $q->whereDate('fecha_movimiento', '>=', $fechaInicio))
            ->when($fechaFin, fn($q) => $q->whereDate('fecha_movimiento', '<=', $fechaFin))
            ->orderBy('id_movimiento', 'desc')
            ->paginate($request->query('per_page', 25));

        return response()->json([
            'status' => 'success',
            'data' => $movimientos,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_producto' => 'required|exists:productos,id_producto',
            'id_almacen_origen' => 'required|exists:almacenes,id_almacen',
            'id_almacen_destino' => 'nullable|required_if:tipo_movimiento,TRANSFERENCIA|exists:almacenes,id_almacen',
            'tipo_movimiento' => 'required|in:ENTRADA,SALIDA,TRANSFERENCIA,AJUSTE',
            'cantidad' => 'required|numeric|min:0.01',
            'costo_unitario' => 'nullable|numeric|min:0',
            'referencia' => 'nullable|string|max:255',
        ]);

        $usuario = $request->user();

        $movimiento = DB::transaction(function () use ($validated, $usuario) {
            $idProducto = $validated['id_producto'];
            $almacenOrigenId = $validated['id_almacen_origen'];
            $cantidad = (float) $validated['cantidad'];

            // Obtener o inicializar stock en origen
            $stockOrigen = Stock::firstOrCreate(
                ['id_producto' => $idProducto, 'id_almacen' => $almacenOrigenId],
                ['cantidad_actual' => 0, 'stock_minimo' => 0, 'stock_maximo' => 0]
            );

            switch ($validated['tipo_movimiento']) {
                case 'ENTRADA':
                    $stockOrigen->increment('cantidad_actual', $cantidad);
                    break;

                case 'SALIDA':
                    if ($stockOrigen->cantidad_actual < $cantidad) {
                        throw ValidationException::withMessages([
                            'cantidad' => ["Stock insuficiente en el almacén de origen. Disponible: {$stockOrigen->cantidad_actual}"],
                        ]);
                    }
                    $stockOrigen->decrement('cantidad_actual', $cantidad);
                    break;

                case 'TRANSFERENCIA':
                    if ($stockOrigen->cantidad_actual < $cantidad) {
                        throw ValidationException::withMessages([
                            'cantidad' => ["Stock insuficiente para transferir. Disponible: {$stockOrigen->cantidad_actual}"],
                        ]);
                    }
                    $stockOrigen->decrement('cantidad_actual', $cantidad);

                    // Aumentar en almacén destino
                    $stockDestino = Stock::firstOrCreate(
                        ['id_producto' => $idProducto, 'id_almacen' => $validated['id_almacen_destino']],
                        ['cantidad_actual' => 0, 'stock_minimo' => 0, 'stock_maximo' => 0]
                    );
                    $stockDestino->increment('cantidad_actual', $cantidad);
                    break;

                case 'AJUSTE':
                    $stockOrigen->update(['cantidad_actual' => $cantidad]);
                    break;
            }

            return Movimiento::create([
                'id_producto' => $idProducto,
                'id_almacen' => $almacenOrigenId,
                'id_almacen_origen' => $almacenOrigenId,
                'id_almacen_destino' => $validated['id_almacen_destino'] ?? null,
                'id_usuario' => $usuario->id_usuario,
                'tipo_movimiento' => $validated['tipo_movimiento'],
                'cantidad' => $cantidad,
                'costo_unitario' => $validated['costo_unitario'] ?? 0,
                'fecha_movimiento' => now(),
                'referencia' => $validated['referencia'] ?? null,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Movimiento registrado y stock actualizado con éxito.',
            'data' => $movimiento->load(['producto', 'almacen', 'usuario']),
        ], 201);
    }
}
