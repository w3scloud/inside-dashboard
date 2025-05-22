<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValidateShopifyStore
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('login')
                ->with('error', 'Please connect your Shopify store to continue.');
        }

        // Validate that the store has a valid access token
        if (! $store->access_token) {
            return redirect()->route('login')
                ->with('error', 'Your store connection has expired. Please reconnect.');
        }

        // Add store to request for controllers
        $request->merge(['store' => $store]);

        return $next($request);
    }
}
