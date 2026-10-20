<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\Movimiento;
use App\Models\Stock;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $idEmpresa = $request->query('id_empresa', $request->user()->id_empresa);
        $tipoDoc = $request->query('tipo_documento');
        $estadoPago = $request->query('estado_pago');
        $search = $request->query('search');

        $ventas = Venta::with(['cliente', 'empresa', 'usuario', 'pagos', 'detalles'])
            ->when($idEmpresa, fn($q) => $q->where('id_empresa', $idEmpresa))
            ->when($tipoDoc, fn($q) => $q->where('tipo_documento', $tipoDoc))
            ->when($estadoPago, fn($q) => $q->where('estado_pago', $estadoPago))
            ->when($search, function ($q) use ($search) {
                $q->where('serie_correlativo', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn($c) => $c->where('razon_social', 'like', "%{$search}%")
                        ->orWhere('num_documento', 'like', "%{$search}%"));
            })
            ->orderBy('id_venta', 'desc')
            ->paginate($request->query('per_page', 25));

        return response()->json([
            'status' => 'success',
            'data' => $ventas,
        ]);
    }

    public function show($id)
    {
        $venta = Venta::with(['cliente', 'empresa', 'usuario', 'detalles.producto', 'pagos.usuario', 'cotizacion'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $venta,
        ]);
    }

    public function store(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'id_cotizacion' => 'nullable|exists:cotizaciones,id_cotizacion',
            'tipo_documento' => 'required|in:FACTURA,BOLETA,NOTA_VENTA',
            'serie' => 'nullable|string|max:10',
            'moneda' => 'nullable|string|max:5',
            'tipo_cambio' => 'nullable|numeric',
            'forma_pago' => 'nullable|string|max:50',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'observaciones' => 'nullable|string',
            'descontar_stock_almacen_id' => 'nullable|exists:almacenes,id_almacen',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'nullable|exists:productos,id_producto',
            'detalles.*.descripcion' => 'required|string|max:255',
            'detalles.*.marca' => 'nullable|string|max:100',
            'detalles.*.unidad_medida' => 'nullable|string|max:20',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        $venta = DB::transaction(function () use ($validated, $usuario) {
            $tipo = $validated['tipo_documento'];
            $idEmpresa = $validated['id_empresa'];

            // Serie por defecto según tipo
            $serie = $validated['serie'];
            if (empty($serie)) {
                $serie = match ($tipo) {
                    'FACTURA' => 'F001',
                    'BOLETA' => 'B001',
                    'NOTA_VENTA' => 'NV01',
                };
            }

            // Correlativo auto-incremental
            $lastVenta = Venta::where('id_empresa', $idEmpresa)
                ->where('tipo_documento', $tipo)
                ->where('serie', $serie)
                ->lockForUpdate()
                ->orderBy('correlativo', 'desc')
                ->first();

            $nextCorrelativo = $lastVenta ? ($lastVenta->correlativo + 1) : 1;
            $serieCorrelativo = sprintf("%s-%08d", $serie, $nextCorrelativo);

            // Subtotales
            $subtotalAcumulado = 0;
            $itemsDetalles = [];

            foreach ($validated['detalles'] as $item) {
                $cant = (float) $item['cantidad'];
                $pu = (float) $item['precio_unitario'];
                $itemSubtotal = round($cant * $pu, 2);
                $subtotalAcumulado += $itemSubtotal;

                $itemsDetalles[] = [
                    'id_producto' => $item['id_producto'] ?? null,
                    'descripcion' => $item['descripcion'],
                    'marca' => $item['marca'] ?? null,
                    'unidad_medida' => $item['unidad_medida'] ?? 'UND',
                    'cantidad' => $cant,
                    'precio_unitario' => $pu,
                    'subtotal' => $itemSubtotal,
                ];

                // Si se especificó almacén para descontar stock físico de hardware
                if (!empty($validated['descontar_stock_almacen_id']) && !empty($item['id_producto'])) {
                    $almId = $validated['descontar_stock_almacen_id'];
                    $stock = Stock::firstOrCreate(
                        ['id_producto' => $item['id_producto'], 'id_almacen' => $almId],
                        ['cantidad_actual' => 0]
                    );

                    $stock->decrement('cantidad_actual', $cant);

                    Movimiento::create([
                        'id_producto' => $item['id_producto'],
                        'id_almacen' => $almId,
                        'id_almacen_origen' => $almId,
                        'id_usuario' => $usuario->id_usuario,
                        'tipo_movimiento' => 'SALIDA',
                        'cantidad' => $cant,
                        'costo_unitario' => 0,
                        'fecha_movimiento' => now(),
                        'referencia' => "Venta: {$serieCorrelativo}",
                    ]);
                }
            }

            $igv = ($tipo === 'NOTA_VENTA') ? 0 : round($subtotalAcumulado * 0.18, 2);
            $total = round($subtotalAcumulado + $igv, 2);

            $venta = Venta::create([
                'id_empresa' => $idEmpresa,
                'id_cliente' => $validated['id_cliente'],
                'id_cotizacion' => $validated['id_cotizacion'] ?? null,
                'id_usuario' => $usuario->id_usuario,
                'tipo_documento' => $tipo,
                'serie' => $serie,
                'correlativo' => $nextCorrelativo,
                'serie_correlativo' => $serieCorrelativo,
                'moneda' => $validated['moneda'] ?? 'PEN',
                'tipo_cambio' => $validated['tipo_cambio'] ?? 1.000,
                'forma_pago' => $validated['forma_pago'] ?? 'Contado',
                'subtotal' => $subtotalAcumulado,
                'igv' => $igv,
                'total' => $total,
                'total_pagado' => 0.00,
                'saldo_pendiente' => $total,
                'estado_pago' => 'PENDIENTE',
                'estado_anulado' => false,
                'fecha_emision' => $validated['fecha_emision'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            foreach ($itemsDetalles as $det) {
                $det['id_venta'] = $venta->id_venta;
                VentaDetalle::create($det);
            }

            return $venta;
        });

        return response()->json([
            'status' => 'success',
            'message' => "Documento {$venta->serie_correlativo} emitido con éxito.",
            'data' => $venta->load(['detalles', 'cliente']),
        ], 201);
    }

    public function fromCotizacion(Request $request, $idCotizacion)
    {
        $cotizacion = Cotizacion::with('detalles')->findOrFail($idCotizacion);

        $validated = $request->validate([
            'tipo_documento' => 'required|in:FACTURA,BOLETA,NOTA_VENTA',
            'serie' => 'nullable|string|max:10',
            'descontar_stock_almacen_id' => 'nullable|exists:almacenes,id_almacen',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'forma_pago' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
        ]);

        $requestData = [
            'id_empresa' => $cotizacion->id_empresa,
            'id_cliente' => $cotizacion->id_cliente,
            'id_cotizacion' => $cotizacion->id_cotizacion,
            'tipo_documento' => $validated['tipo_documento'],
            'serie' => $validated['serie'] ?? null,
            'moneda' => $cotizacion->moneda,
            'tipo_cambio' => $cotizacion->tipo_cambio,
            'forma_pago' => $validated['forma_pago'] ?? $cotizacion->forma_pago,
            'fecha_emision' => $validated['fecha_emision'],
            'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
            'observaciones' => $validated['observaciones'] ?? "Generado a partir de Cotización {$cotizacion->numero_cotizacion}",
            'descontar_stock_almacen_id' => $validated['descontar_stock_almacen_id'] ?? null,
            'detalles' => $cotizacion->detalles->map(fn($d) => [
                'id_producto' => $d->id_producto,
                'descripcion' => $d->descripcion,
                'marca' => $d->marca,
                'unidad_medida' => $d->unidad_medida,
                'cantidad' => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
            ])->toArray(),
        ];

        $request->merge($requestData);
        return $this->store($request);
    }

    public function registrarPago(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);

        if ($venta->estado_anulado) {
            throw ValidationException::withMessages([
                'monto' => ['No se pueden registrar pagos en un documento anulado.'],
            ]);
        }

        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01|max:' . $venta->saldo_pendiente,
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:TRANSFERENCIA,EFECTIVO,YAPE_PLIN,TARJETA,CHEQUE,OTROS',
            'referencia_operacion' => 'nullable|string|max:100',
            'banco' => 'nullable|string|max:100',
            'notas' => 'nullable|string',
        ]);

        $pago = DB::transaction(function () use ($venta, $validated, $request) {
            $monto = (float) $validated['monto'];

            $pago = VentaPago::create([
                'id_venta' => $venta->id_venta,
                'id_usuario' => $request->user()->id_usuario,
                'monto' => $monto,
                'fecha_pago' => $validated['fecha_pago'],
                'metodo_pago' => $validated['metodo_pago'],
                'referencia_operacion' => $validated['referencia_operacion'] ?? null,
                'banco' => $validated['banco'] ?? null,
                'notas' => $validated['notas'] ?? null,
            ]);

            $nuevoPagado = round((float)$venta->total_pagado + $monto, 2);
            $nuevoSaldo = max(0, round((float)$venta->total - $nuevoPagado, 2));

            $estadoPago = ($nuevoSaldo <= 0.001) ? 'PAGADO' : 'PARCIAL';

            $venta->update([
                'total_pagado' => $nuevoPagado,
                'saldo_pendiente' => $nuevoSaldo,
                'estado_pago' => $estadoPago,
            ]);

            return $pago;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Cobranza registrada correctamente.',
            'data' => [
                'pago' => $pago,
                'venta' => $venta->fresh(['pagos']),
            ],
        ], 201);
    }

    public function anular($id)
    {
        $venta = Venta::findOrFail($id);

        if ($venta->total_pagado > 0) {
            throw ValidationException::withMessages([
                'venta' => ['No se puede anular un comprobante que ya tiene cobros registrados.'],
            ]);
        }

        $venta->update([
            'estado_anulado' => true,
            'estado_pago' => 'PENDIENTE',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Documento de venta anulado exitosamente.',
            'data' => $venta,
        ]);
    }
}
