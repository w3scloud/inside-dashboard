<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'auth/shopify', // Shopify OAuth initiation - just redirects to Shopify
        // Webhook routes are already excluded via withoutMiddleware
        // Shopify OAuth callback doesn't need CSRF (external redirect)
    ];

    /**
     * Determine if the request should be excluded from CSRF verification.
     */
    protected function inExceptArray($request): bool
    {
        // Check URI path first (more reliable)
        $path = $request->path();
        if ($path === 'auth/shopify' || $path === 'auth/callback') {
            return true;
        }

        // Allow Shopify OAuth callback (external redirect from Shopify)
        if ($request->routeIs('shopify.callback')) {
            return true;
        }

        // Allow Shopify auth POST - it just initiates OAuth redirect, no data modification
        if ($request->routeIs('shopify.auth')) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
