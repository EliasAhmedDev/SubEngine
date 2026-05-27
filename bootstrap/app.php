<?php

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleApi();
    })
    ->withMiddleware(function (Middleware $middleware) {
        // Prepend ensures this runs BEFORE anything else
        $middleware->prepend(ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // FIX: The correct Laravel method name is shouldRenderJsonWhen
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Explicit API contract for validation failures
        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        });
    })
    ->create();
