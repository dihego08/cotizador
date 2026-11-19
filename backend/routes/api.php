<?php

use App\Http\Controllers\Api\AlmacenController;
use App\Http\Controllers\Api\AuditoriaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CotizacionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\HelpdeskController;
use App\Http\Controllers\Api\MarcaController;
use App\Http\Controllers\Api\MovimientoController;
use App\Http\Controllers\Api\PlataformaController;
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
| Cada ruta autenticada pasa por:
|   auth:sanctum       → token válido
|   cuenta             → define la cuenta del usuario (filtra todos los datos) y bloquea cuentas suspendidas
|   password.cambiado  → bloquea el sistema mientras el usuario tenga contraseña temporal
|   cuenta.cliente     → módulos del negocio; la cuenta de plataforma solo usa el panel de plataforma
|   permiso:{módulo}   → permiso del módulo validado en el servidor
| Las lecturas de catálogos que usan varias pantallas (productos, clientes, almacenes...)
| quedan abiertas a todo usuario de la cuenta; las escrituras exigen el permiso del módulo.
*/

// Rutas Públicas de Autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/recuperar-password', [AuthController::class, 'recuperarPassword'])->middleware('throttle:recuperar-password');
    Route::post('/restablecer-password', [AuthController::class, 'restablecerPassword'])->middleware('throttle:restablecer-password');
});

// Logo de empresa (público para poder usarlo en <img> e impresiones)
Route::get('/empresas/{id}/logo', [EmpresaController::class, 'logo']);

// Rutas Protegidas por Sanctum Token
Route::middleware(['auth:sanctum', 'cuenta', 'throttle:api'])->group(function () {

    // Auth & Perfil (disponibles aunque la contraseña sea temporal)
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/cambiar-password', [AuthController::class, 'cambiarPassword']);
    });

    Route::middleware('password.cambiado')->group(function () {

        // Módulos del negocio: no disponibles para la cuenta de plataforma, que solo administra cuentas
        Route::middleware('cuenta.cliente')->group(function () {

            // Métricas para Dashboard
            Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);

            // Empresas
            Route::get('/empresas', [EmpresaController::class, 'index']);
            Route::get('/empresas/{empresa}', [EmpresaController::class, 'show']);
            Route::middleware('permiso:empresas')->group(function () {
                Route::post('/empresas', [EmpresaController::class, 'store']);
                Route::put('/empresas/{empresa}', [EmpresaController::class, 'update']);
                Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy']);
                Route::post('/empresas/{id}/logo', [EmpresaController::class, 'uploadLogo']);
                Route::delete('/empresas/{id}/logo', [EmpresaController::class, 'deleteLogo']);
            });

            // Almacenes
            Route::get('/almacenes', [AlmacenController::class, 'index']);
            Route::get('/almacenes/{almacene}', [AlmacenController::class, 'show']);
            Route::apiResource('almacenes', AlmacenController::class)->except(['index', 'show'])->middleware('permiso:almacenes');

            // Categorías y Marcas
            Route::get('/categorias', [CategoriaController::class, 'index']);
            Route::get('/marcas', [MarcaController::class, 'index']);
            Route::middleware('permiso:categorias_marcas')->group(function () {
                Route::apiResource('categorias', CategoriaController::class)->except(['index', 'show']);
                Route::apiResource('marcas', MarcaController::class)->except(['index', 'show']);
            });

            // Productos y Catálogo
            Route::get('/productos', [ProductoController::class, 'index']);
            Route::get('/productos/{producto}', [ProductoController::class, 'show']);
            Route::apiResource('productos', ProductoController::class)->except(['index', 'show'])->middleware('permiso:productos');

            // Control de Stock
            Route::get('/stock', [StockController::class, 'index']);
            Route::put('/stock/{id}/limites', [StockController::class, 'updateLimites'])->middleware('permiso:stock');

            // Trazabilidad de Series
            Route::middleware('permiso:series')->group(function () {
                Route::get('/series', [SerieController::class, 'index']);
                Route::post('/series', [SerieController::class, 'store']);
                Route::put('/series/{id}/estado', [SerieController::class, 'updateEstado']);
            });

            // Movimientos de Almacén
            Route::middleware('permiso:movimientos')->group(function () {
                Route::get('/movimientos', [MovimientoController::class, 'index']);
                Route::post('/movimientos', [MovimientoController::class, 'store']);
            });

            // Clientes y Contactos
            Route::get('/clientes', [ClienteController::class, 'index']);
            Route::get('/clientes/{cliente}', [ClienteController::class, 'show']);
            Route::middleware('permiso:clientes')->group(function () {
                Route::apiResource('clientes', ClienteController::class)->except(['index', 'show']);
                Route::post('/clientes/{id}/contactos', [ClienteController::class, 'addContacto']);
                Route::delete('/clientes/{id}/contactos/{contactoId}', [ClienteController::class, 'removeContacto']);
            });

            // Mini-CRM & Pipeline Comercial
            Route::middleware('permiso:clientes')->group(function () {
                Route::apiResource('crm/oportunidades', CrmController::class);
                Route::patch('/crm/oportunidades/{id}/etapa', [CrmController::class, 'updateEtapa']);
                Route::post('/crm/oportunidades/{id}/vincular-cotizacion', [CrmController::class, 'vincularCotizacion']);
                Route::post('/crm/actividades', [CrmController::class, 'storeActividad']);
                Route::get('/crm/actividades/{id}/archivo', [CrmController::class, 'descargarArchivo']);
            });

            // Cotizaciones y Documentos de Venta / Cobranzas (Facturas, Boletas, Notas de Venta)
            Route::middleware('permiso:cotizaciones')->group(function () {
                Route::apiResource('cotizaciones', CotizacionController::class);
                Route::patch('/cotizaciones/{id}/estado', [CotizacionController::class, 'updateEstado']);

                Route::apiResource('ventas', VentaController::class);
                Route::post('/ventas/from-cotizacion/{id}', [VentaController::class, 'fromCotizacion']);
                Route::post('/ventas/{id}/pagos', [VentaController::class, 'registrarPago']);
                Route::patch('/ventas/{id}/anular', [VentaController::class, 'anular']);
            });

            // Tipo de Cambio
            Route::get('/tipo-cambio', [TipoCambioController::class, 'index']);
            Route::get('/tipo-cambio/actual', [TipoCambioController::class, 'actual']);
            Route::post('/tipo-cambio', [TipoCambioController::class, 'store'])->middleware('permiso:tipo_cambio');

            // Helpdesk / Mesa de Ayuda
            Route::prefix('helpdesk')->middleware('permiso:helpdesk')->group(function () {
                Route::get('/categorias', [HelpdeskController::class, 'categorias']);
                Route::get('/atenciones', [HelpdeskController::class, 'index']);
                Route::post('/atenciones', [HelpdeskController::class, 'store']);
                Route::get('/atenciones/{id}', [HelpdeskController::class, 'show']);
                Route::post('/atenciones/{id}/pausa', [HelpdeskController::class, 'registrarPausa']);
                Route::post('/atenciones/{id}/reanudar', [HelpdeskController::class, 'reanudarPausa']);
            });

            // Gestión de Usuarios y Permisos
            Route::get('/usuarios', [UsuarioController::class, 'index'])->middleware('permiso:usuarios,almacenes');
            Route::middleware('permiso:usuarios')->group(function () {
                Route::get('/modulos', [UsuarioController::class, 'modulos']);
                Route::apiResource('usuarios', UsuarioController::class)->except(['index']);
                Route::patch('/usuarios/{id}/toggle-estado', [UsuarioController::class, 'toggleEstado']);

                // Registro de actividad de la cuenta
                Route::get('/auditoria', [AuditoriaController::class, 'index']);
            });
        });

        // Panel de plataforma: cuentas de clientes (solo SUPERADMIN)
        Route::prefix('plataforma')->middleware('superadmin')->group(function () {
            Route::get('/cuentas', [PlataformaController::class, 'index']);
            Route::post('/cuentas', [PlataformaController::class, 'store']);
            Route::get('/cuentas/{id}', [PlataformaController::class, 'show']);
            Route::put('/cuentas/{id}', [PlataformaController::class, 'update']);
            Route::patch('/cuentas/{id}/estado', [PlataformaController::class, 'cambiarEstado']);
        });
    });
});
