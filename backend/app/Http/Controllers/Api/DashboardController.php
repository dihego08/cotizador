<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\HelpdeskAtencion;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Stock;
use App\Models\TipoCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(Request $request)
    {
        $idEmpresa = $request->user()->id_empresa;

        $totalProductos = Producto::count();
        $totalStockBajo = Stock::whereColumn('cantidad_actual', '<=', 'stock_minimo')->where('stock_minimo', '>', 0)->count();
        
        $cotizacionesPendientes = Cotizacion::where('id_empresa', $idEmpresa)
            ->where('estado', 'PENDIENTE')
            ->count();

        $cotizacionesAprobadas = Cotizacion::where('id_empresa', $idEmpresa)
            ->where('estado', 'APROBADA')
            ->count();

        $totalVentasMes = Cotizacion::where('id_empresa', $idEmpresa)
            ->where('estado', 'APROBADA')
            ->whereMonth('fecha_emision', now()->month)
            ->whereYear('fecha_emision', now()->year)
            ->sum('total');

        $ticketsAbiertos = HelpdeskAtencion::where('id_empresa', $idEmpresa)
            ->whereIn('estado_cierre', ['EN_PROCESO', 'ESCALADO_N2', 'ESPERA_REPUESTO'])
            ->count();

        $ticketsResueltos = HelpdeskAtencion::where('id_empresa', $idEmpresa)
            ->where('estado_cierre', 'CONCLUIDO')
            ->count();

        $ultimosMovimientos = Movimiento::with(['producto', 'almacen', 'usuario'])
            ->orderBy('id_movimiento', 'desc')
            ->take(6)
            ->get();

        $ultimasCotizaciones = Cotizacion::with(['cliente'])
            ->where('id_empresa', $idEmpresa)
            ->orderBy('id_cotizacion', 'desc')
            ->take(5)
            ->get();

        $tipoCambioHoy = TipoCambio::orderBy('fecha', 'desc')->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_productos' => $totalProductos,
                'stock_bajo_alerta' => $totalStockBajo,
                'cotizaciones_pendientes' => $cotizacionesPendientes,
                'cotizaciones_aprobadas' => $cotizacionesAprobadas,
                'total_ventas_mes' => (float)$totalVentasMes,
                'tickets_abiertos' => $ticketsAbiertos,
                'tickets_resueltos' => $ticketsResueltos,
                'ultimos_movimientos' => $ultimosMovimientos,
                'ultimas_cotizaciones' => $ultimasCotizaciones,
                'tipo_cambio_hoy' => $tipoCambioHoy,
            ],
        ]);
    }
}
