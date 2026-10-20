<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    public function index(Request $request)
    {
        $idEmpresa = $request->query('id_empresa', $request->user()->id_empresa);
        $estado = $request->query('estado');
        $search = $request->query('search');

        $cotizaciones = Cotizacion::with(['cliente', 'usuario', 'empresa', 'detalles'])
            ->when($idEmpresa, fn($q) => $q->where('id_empresa', $idEmpresa))
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->when($search, function ($q) use ($search) {
                $q->where('numero_cotizacion', 'like', "%{$search}%")
                    ->orWhere('asunto', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn($c) => $c->where('razon_social', 'like', "%{$search}%"));
            })
            ->orderBy('id_cotizacion', 'desc')
            ->paginate($request->query('per_page', 25));

        return response()->json([
            'status' => 'success',
            'data' => $cotizaciones,
        ]);
    }

    public function show($id)
    {
        $cotizacion = Cotizacion::with(['cliente.contactos', 'usuario', 'empresa', 'detalles.producto'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $cotizacion,
        ]);
    }

    public function store(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'id_empresa' => 'required|exists:empresas,id_empresa',
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'asunto' => 'nullable|string|max:255',
            'atencion' => 'nullable|string|max:150',
            'forma_pago' => 'nullable|string|max:50',
            'moneda' => 'nullable|string|max:5',
            'tipo_cambio' => 'nullable|numeric',
            'validez_dias' => 'nullable|integer',
            'vigencia' => 'nullable|string|max:100',
            'tiempo_entrega' => 'nullable|string|max:100',
            'observacion' => 'nullable|string',
            'condiciones_comerciales' => 'nullable|string',
            'alcance' => 'nullable|string',
            'restricciones' => 'nullable|string',
            'fecha_emision' => 'required|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'nullable|exists:productos,id_producto',
            'detalles.*.descripcion' => 'required|string|max:255',
            'detalles.*.nota_item' => 'nullable|string',
            'detalles.*.marca' => 'nullable|string|max:100',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.unidad_medida' => 'nullable|string|max:20',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        $cotizacion = DB::transaction(function () use ($validated, $usuario) {
            $year = date('Y');
            $lastQuote = Cotizacion::where('numero_cotizacion', 'like', "COT-{$year}-%")
                ->orderBy('id_cotizacion', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastQuote && preg_match("/COT-{$year}-(\d+)/", $lastQuote->numero_cotizacion, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
            $correlativo = sprintf("COT-%s-%04d", $year, $nextNumber);

            // Calcular subtotales
            $subtotalAcumulado = 0;
            $detallesToInsert = [];

            foreach ($validated['detalles'] as $item) {
                $cant = (float) $item['cantidad'];
                $precio = (float) $item['precio_unitario'];
                $itemSubtotal = round($cant * $precio, 2);
                $subtotalAcumulado += $itemSubtotal;

                $detallesToInsert[] = [
                    'id_producto' => $item['id_producto'] ?? null,
                    'descripcion' => $item['descripcion'],
                    'nota_item' => $item['nota_item'] ?? null,
                    'marca' => $item['marca'] ?? null,
                    'cantidad' => $cant,
                    'unidad_medida' => $item['unidad_medida'] ?? 'UND',
                    'porcentaje_utilidad' => 0,
                    'precio_unitario' => $precio,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $igv = round($subtotalAcumulado * 0.18, 2);
            $total = round($subtotalAcumulado + $igv, 2);

            $cotizacion = Cotizacion::create([
                'id_empresa' => $validated['id_empresa'],
                'numero_cotizacion' => $correlativo,
                'asunto' => $validated['asunto'] ?? null,
                'id_cliente' => $validated['id_cliente'],
                'atencion' => $validated['atencion'] ?? null,
                'forma_pago' => $validated['forma_pago'] ?? 'Contado',
                'moneda' => $validated['moneda'] ?? 'USD',
                'tipo_cambio' => $validated['tipo_cambio'] ?? 3.75,
                'subtotal' => $subtotalAcumulado,
                'igv' => $igv,
                'total' => $total,
                'validez_dias' => $validated['validez_dias'] ?? 15,
                'vigencia' => $validated['vigencia'] ?? '15 días calendario',
                'tiempo_entrega' => $validated['tiempo_entrega'] ?? null,
                'observacion' => $validated['observacion'] ?? null,
                'condiciones_comerciales' => $validated['condiciones_comerciales'] ?? null,
                'alcance' => $validated['alcance'] ?? null,
                'restricciones' => $validated['restricciones'] ?? null,
                'fecha_emision' => $validated['fecha_emision'],
                'estado' => 'PENDIENTE',
                'id_usuario' => $usuario->id_usuario,
            ]);

            foreach ($detallesToInsert as $d) {
                $d['id_cotizacion'] = $cotizacion->id_cotizacion;
                CotizacionDetalle::create($d);
            }

            return $cotizacion;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Cotización creada exitosamente.',
            'data' => $cotizacion->load(['detalles', 'cliente']),
        ], 201);
    }

    public function updateEstado(Request $request, $id)
    {
        $cotizacion = Cotizacion::findOrFail($id);

        $validated = $request->validate([
            'estado' => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
        ]);

        $cotizacion->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Cotización marcada como {$validated['estado']}.",
            'data' => $cotizacion,
        ]);
    }
}
