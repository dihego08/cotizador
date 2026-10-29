<?php

use App\Http\Controllers\Api\AlmacenController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CotizacionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\HelpdeskController;
use App\Http\Controllers\Api\MarcaController;
use App\Http\Controllers\Api\MovimientoController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\SerieController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\TipoCambioController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\CrmController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas Públicas de Autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Logo de empresa (público para poder usarlo en <img> e impresiones)
Route::get('/empresas/{id}/logo', [EmpresaController::class, 'logo']);

// Rutas Protegidas por Sanctum Token
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & Perfil
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Métricas para Dashboard
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);

    // Empresas
    Route::apiResource('empresas', EmpresaController::class);
    Route::post('/empresas/{id}/logo', [EmpresaController::class, 'uploadLogo']);
    Route::delete('/empresas/{id}/logo', [EmpresaController::class, 'deleteLogo']);

    // Almacenes
    Route::apiResource('almacenes', AlmacenController::class);

    // Categorías y Marcas
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('marcas', MarcaController::class);

    // Productos y Catálogo
    Route::apiResource('productos', ProductoController::class);

    // Control de Stock
    Route::get('/stock', [StockController::class, 'index']);
    Route::put('/stock/{id}/limites', [StockController::class, 'updateLimites']);

    // Trazabilidad de Series
    Route::get('/series', [SerieController::class, 'index']);
    Route::post('/series', [SerieController::class, 'store']);
    Route::put('/series/{id}/estado', [SerieController::class, 'updateEstado']);

    // Movimientos de Almacén
    Route::get('/movimientos', [MovimientoController::class, 'index']);
    Route::post('/movimientos', [MovimientoController::class, 'store']);

    // Clientes y Contactos
    Route::apiResource('clientes', ClienteController::class);
    Route::post('/clientes/{id}/contactos', [ClienteController::class, 'addContacto']);
    Route::delete('/clientes/{id}/contactos/{contactoId}', [ClienteController::class, 'removeContacto']);

    // Mini-CRM & Pipeline Comercial
    Route::apiResource('crm/oportunidades', CrmController::class);
    Route::patch('/crm/oportunidades/{id}/etapa', [CrmController::class, 'updateEtapa']);
    Route::post('/crm/oportunidades/{id}/vincular-cotizacion', [CrmController::class, 'vincularCotizacion']);
    Route::post('/crm/actividades', [CrmController::class, 'storeActividad']);
    Route::get('/crm/actividades/{id}/archivo', [CrmController::class, 'descargarArchivo']);

    // Cotizaciones
    Route::apiResource('cotizaciones', CotizacionController::class);
    Route::patch('/cotizaciones/{id}/estado', [CotizacionController::class, 'updateEstado']);

    // Documentos de Venta y Cobranzas (Facturas, Boletas, Notas de Venta)
    Route::apiResource('ventas', VentaController::class);
    Route::post('/ventas/from-cotizacion/{id}', [VentaController::class, 'fromCotizacion']);
    Route::post('/ventas/{id}/pagos', [VentaController::class, 'registrarPago']);
    Route::patch('/ventas/{id}/anular', [VentaController::class, 'anular']);

    // Tipo de Cambio
    Route::get('/tipo-cambio', [TipoCambioController::class, 'index']);
    Route::get('/tipo-cambio/actual', [TipoCambioController::class, 'actual']);
    Route::post('/tipo-cambio', [TipoCambioController::class, 'store']);

    // Helpdesk / Mesa de Ayuda
    Route::prefix('helpdesk')->group(function () {
        Route::get('/categorias', [HelpdeskController::class, 'categorias']);
        Route::get('/atenciones', [HelpdeskController::class, 'index']);
        Route::post('/atenciones', [HelpdeskController::class, 'store']);
        Route::get('/atenciones/{id}', [HelpdeskController::class, 'show']);
        Route::post('/atenciones/{id}/pausa', [HelpdeskController::class, 'registrarPausa']);
        Route::post('/atenciones/{id}/reanudar', [HelpdeskController::class, 'reanudarPausa']);
    });

    // Gestión de Usuarios y Permisos
    Route::get('/modulos', [UsuarioController::class, 'modulos']);
    Route::apiResource('usuarios', UsuarioController::class);
    Route::patch('/usuarios/{id}/toggle-estado', [UsuarioController::class, 'toggleEstado']);
});
