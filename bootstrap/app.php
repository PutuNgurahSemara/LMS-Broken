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
    // Masalah 5: Middleware didaftarkan di berkas yang salah.
    // Perbaikan: alias middleware dipindahkan ke bootstrap/app.php yang sebelumnya ditempatkan di App/Http/Kernel.php.
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class,]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
