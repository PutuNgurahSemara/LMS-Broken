<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                // Bug 6 FIX: AuthorizationException harus mengembalikan 403 Forbidden, bukan 401 Unauthorized.
                // 401 = belum login / token tidak valid. 403 = sudah login tapi tidak berhak.
                // Mengirim 401 di sini membuat client mengira perlu login ulang, padahal masalahnya adalah hak akses.
                return response()->json(['message' => 'This action is unauthorized.'], 403);
            }
        });
    })->create();
