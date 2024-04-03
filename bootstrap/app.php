<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {

            // user closed route
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api/v1/user')
                ->group(base_path('routes/api/v1/user/index.php'));

            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api/v1/admin')
                ->group(base_path('routes/api/v1/admin/index.php'));

            Route::middleware(['api'])
                ->prefix('api/v1/auth')
                ->group(base_path('routes/api/v1/auth/index.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
