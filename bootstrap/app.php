<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render's proxy terminates HTTPS and is the only way to reach the app.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?ExceptionResponse {
            $status = $response->statusCode();
            $request = $response->request;

            // Writes stay on their page: resources/js/lib/visit-errors.ts shows a toast.
            $isPageVisit = $request->isMethod('GET') && ! $request->expectsJson();
            $isDebugScreen = $status >= 500 && config('app.debug');

            if (! $isPageVisit || $isDebugScreen || ! in_array($status, [403, 404, 429, 500, 503], true)) {
                return null;
            }

            // No shared data: resolving the user would query the database, which
            // may be the very reason for a 500. hasUser() never queries.
            return $response->render('errors/show', [
                'status' => $status,
                'signedIn' => Auth::hasUser(),
            ]);
        });
    })->create();
