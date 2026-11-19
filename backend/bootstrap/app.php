<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\ReiniciarCuenta::class);

        $middleware->alias([
            'cuenta' => \App\Http\Middleware\EstablecerCuenta::class,
            'permiso' => \App\Http\Middleware\RequierePermiso::class,
            'superadmin' => \App\Http\Middleware\RequiereSuperAdmin::class,
            'password.cambiado' => \App\Http\Middleware\RequierePasswordCambiado::class,
            'cuenta.cliente' => \App\Http\Middleware\RequiereCuentaCliente::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
