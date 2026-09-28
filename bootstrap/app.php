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
    ->withMiddleware(function (Middleware $middleware) {
        // Bug 5 FIX: RoleMiddleware didaftarkan di bootstrap/app.php (bukan di Kernel.php)
        // Laravel 12 menggunakan bootstrap/app.php sebagai titik konfigurasi middleware.
        // Sebelumnya alias 'role' hanya ada di Kernel.php yang sudah deprecated dan tidak dibaca Laravel 12.
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
