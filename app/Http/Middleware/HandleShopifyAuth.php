<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\ShopifyService;
use Carbon\Carbon;
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
        Log::info('HandleShopifyAuth middleware', [
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
            'is_authenticated' => Auth::check(),
            'has_shop' => $request->has('shop'),
            'has_embedded' => $request->has('embedded'),
            'has_host' => $request->has('host'),
        ]);

        // Check if user is authenticated
        if (! Auth::check()) {
            // If coming from Shopify embedded app, preserve parameters
            if ($request->has('shop') || $request->has('embedded')) {
                $loginUrl = route('login');
                $shopifyParams = $request->only(['embedded', 'host', 'hmac', 'id_token', 'session', 'shop', 'timestamp', 'locale']);
                if (! empty($shopifyParams)) {
                    $loginUrl .= '?'.http_build_query($shopifyParams);
                }

                Log::info('Redirecting unauthenticated user to login (with params)', ['url' => $loginUrl]);

                return redirect($loginUrl);
            }

            Log::info('Redirecting unauthenticated user to login');

            return redirect()->route('login');
        }

        // Check if the user has an active Shopify store
        $user = Auth::user();

        // If we just auto-authenticated, try to get store from session first
        $store = null;
        $autoAuth = $request->session()->get('shopify_auto_auth');
        $storeId = $request->session()->get('shopify_store_id');

        Log::info('Checking for store in middleware', [
            'user_id' => $user->id,
            'auto_auth' => $autoAuth,
            'store_id_from_session' => $storeId,
        ]);

        if ($autoAuth && $storeId) {
            $store = Store::where('id', $storeId)
                ->where('is_active', true)
                ->where('user_id', $user->id)
                ->first();

            if ($store) {
                Log::info('Found store from session', [
                    'store_id' => $store->id,
                    'store_domain' => $store->shop_domain,
                ]);
                // Don't clear the session flag yet - keep it for this request
                // $request->session()->forget(['shopify_auto_auth', 'shopify_store_id']);
            } else {
                Log::warning('Store ID in session but not found in database', [
                    'store_id' => $storeId,
                    'user_id' => $user->id,
                ]);
            }
        }

        // Try to find store via relationship
        if (! $store) {
            $store = $user->stores()->active()->first();
        }

        // If not found via relationship, try to find by user_id directly (in case relationship isn't loaded)
        if (! $store) {
            $store = Store::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            // If found, log for debugging
            if ($store) {
                Log::info('Store found via direct query but not via relationship', [
                    'user_id' => $user->id,
                    'store_id' => $store->id,
                    'store_user_id' => $store->user_id,
                ]);
            }
        }

        if (! $store) {
            Log::warning('User authenticated but no active store found', [
                'user_id' => $user->id,
                'stores_count' => $user->stores()->count(),
                'all_stores' => $user->stores()->pluck('id', 'shop_domain')->toArray(),
            ]);
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

            // If coming from Shopify with shop parameter, allow through (might be in auto-auth flow)
            if ($request->has('shop') || $request->has('embedded')) {
                Log::info('User has no store but coming from Shopify, allowing through', [
                    'user_id' => $user->id,
                    'shop' => $request->input('shop'),
                ]);

                // Don't block - let the controller handle it
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
        $metadata = $store->metadata ?? [];
        $lastValidated = $metadata['token_last_validated'] ?? null;

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
            $metadata['token_last_validated'] = now()->toIso8601String();
            $store->update(['metadata' => $metadata]);

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
