<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HandleShopifyAuth
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // Check if the user has an active Shopify store
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            // Check if user has an inactive store
            $inactiveStore = $user->stores()->where('is_active', false)->first();

            if ($inactiveStore) {
                // Store exists but is inactive (app uninstalled)
                Log::info('User has inactive store, redirecting to login', [
                    'user_id' => $user->id,
                    'store_domain' => $inactiveStore->shop_domain,
                ]);

                Auth::logout();

                return redirect()->route('login')->with(
                    'error',
                    'Your Shopify app has been uninstalled. Please reinstall to continue.'
                );
            }

            // Check if we're in the OAuth flow
            if ($request->routeIs('shopify.auth') || $request->routeIs('shopify.callback')) {
                return $next($request);
            }

            Log::info('User has no store, redirecting to connect page', [
                'user_id' => $user->id,
            ]);

            // No store found, show connect store page
            return Inertia::render('Auth/ConnectStore', [
                'previouslyConnected' => (bool) $inactiveStore,
            ]);
        }

        // Check if the store's access token is valid
        if (! $this->validateAccessToken($store)) {
            Log::warning('Invalid access token detected', [
                'user_id' => $user->id,
                'store_domain' => $store->shop_domain,
            ]);

            // Mark store as inactive
            $store->update([
                'is_active' => false,
                'access_token' => null,
            ]);

            Auth::logout();

            return redirect()->route('login')->with(
                'error',
                'Your access to the Shopify store has expired. Please reconnect to continue.'
            );
        }

        // Share the store with all views
        Inertia::share('store', [
            'id' => $store->id,
            'name' => $store->name,
            'domain' => $store->shop_domain,
            'plan' => $store->plan_name,
        ]);

        // Share app settings with all views
        $settings = $this->getAppSettings($store);
        Inertia::share('settings', $settings);

        // Add the store to the request for controllers to use
        $request->attributes->add(['store' => $store]);

        return $next($request);
    }

    /**
     * Validate the store's access token.
     */
    private function validateAccessToken(Store $store): bool
    {
        // If the store has no access token, it's invalid
        if (! $store->access_token) {
            return false;
        }

        // If the store was recently validated, consider the token valid
        $lastValidated = $store->getSetting('token_last_validated', null);
        if ($lastValidated && now()->diffInMinutes(Carbon::parse($lastValidated)) < 60) {
            return true;
        }

        try {
            // Try to make a simple API call to check if the token is valid
            $shopifyService = app(ShopifyService::class);
            $shopInfo = $shopifyService->getShopDetails($store);

            if (! $shopInfo) {
                return false;
            }

            // Update token validation timestamp
            $store->setSetting('token_last_validated', now()->toIso8601String());

            return true;
        } catch (\Exception $e) {
            Log::error('Error validating access token', [
                'store' => $store->shop_domain,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the app settings for the store.
     */
    private function getAppSettings(Store $store): array
    {
        $metadata = $store->metadata ?? [];
        $settings = $metadata['settings'] ?? [];

        return [
            'theme' => $settings['theme'] ?? 'light',
            'dashboard_refresh_interval' => $settings['dashboard_refresh_interval'] ?? 0,
            'default_date_range' => $settings['default_date_range'] ?? 30,
            'email_notifications' => $settings['email_notifications'] ?? true,
        ];
    }
}
