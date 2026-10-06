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
                //no 5 mengubah *return response()->json(['message' => 'This action is unauthorized.'], 401);* menjadi *return response()->json(['message' => 'This action is unauthorized.'], 403);* agar status code response lebih sesuai dengan konteks. Status 403 (Forbidden) lebih tepat digunakan ketika user sudah terautentikasi tetapi tidak memiliki izin untuk mengakses resource tertentu, sedangkan status 401 (Unauthorized) biasanya digunakan ketika user belum terautentikasi.
                return response()->json(['message' => 'This action is unauthorized.'], 403);
            }
        });
    })->create();
