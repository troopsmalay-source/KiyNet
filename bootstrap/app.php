<?php

use App\Http\Middleware\AddRequestId;
use App\Http\Middleware\EnsureUserHasRole;
use App\Support\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Request correlation for every response, including 404s that never
        // reach route middleware (API contract §2 requires meta.request_id).
        $middleware->prepend(AddRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Frozen API error envelope; web requests keep Laravel's default handling.
        $exceptions->render(function (Throwable $e, Request $request) {
            return app(ApiExceptionRenderer::class)->render($e, $request);
        });
    })->create();
