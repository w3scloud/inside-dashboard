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
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Replace default CSRF middleware with our custom one
        $middleware->validateCsrfTokens(except: [
            'auth/shopify', // Shopify OAuth initiation
            'shopify.callback', // OAuth callback from Shopify (external)
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'shopify.store' => \App\Http\Middleware\HandleShopifyAuth::class,
            'shopify.validate' => \App\Http\Middleware\ValidateShopifyStore::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
