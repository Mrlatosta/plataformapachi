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
        // Railway termina el HTTPS en su proxy: confiar en X-Forwarded-* para que
        // Laravel genere URLs https (paginacion, redirecciones, assets)
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\CerrarSesionInactivos::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'medico.auth' => \App\Http\Middleware\MedicoAuth::class,
            'rol' => \App\Http\Middleware\EnsureRol::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
