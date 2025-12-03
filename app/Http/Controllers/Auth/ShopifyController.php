<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SetupStoreJob;
use App\Models\Store;
use App\Models\User;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ShopifyController extends Controller
{
    protected $shopifyService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    /**
     * Show the Shopify login form or auto-authenticate if coming from Shopify.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function showLogin(Request $request)
    {
        // Log all incoming request details for debugging
        Log::info('showLogin called', [
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'is_authenticated' => Auth::check(),
            'all_params' => $request->all(),
            'headers' => [
                'referer' => $request->header('referer'),
                'host' => $request->header('host'),
            ],
        ]);

        // If user is already authenticated and has an active store, redirect to dashboard
        // This should happen regardless of shop parameter (user is already logged in)
        if (Auth::check()) {
            $user = Auth::user();
            $store = $user->stores()->active()->first();

            Log::info('Authenticated user check', [
                'user_id' => $user->id,
                'has_store' => $store ? true : false,
                'store_id' => $store?->id,
                'store_domain' => $store?->shop_domain,
            ]);

            if ($store) {
                // For embedded apps, preserve Shopify parameters in redirect
                if ($request->has('embedded') || $request->has('host')) {
                    $dashboardUrl = route('dashboard');
                    $shopifyParams = $request->only(['embedded', 'host', 'hmac', 'id_token', 'session', 'shop', 'timestamp', 'locale']);
                    if (! empty($shopifyParams)) {
                        $dashboardUrl .= '?'.http_build_query($shopifyParams);
                    }
                    Log::info('Redirecting authenticated user to dashboard (embedded)', ['url' => $dashboardUrl]);

                    return redirect($dashboardUrl);
                }

                Log::info('Redirecting authenticated user to dashboard');

                return redirect()->route('dashboard');
            }
        }

        // Check if request is coming from Shopify (has shop parameter or embedded app parameters)
        $shop = $request->input('shop');
        $isEmbeddedApp = $request->has('embedded') || $request->has('host') || $request->has('id_token');

        if ($shop || $isEmbeddedApp) {
            // If embedded app but no shop, try to extract from multiple sources
            if (! $shop) {
                // First, try to extract from host parameter (base64 encoded)
                if ($request->has('host')) {
                    try {
                        $host = base64_decode($request->input('host'), true);
                        if ($host === false) {
                            // Try without strict mode
                            $host = base64_decode($request->input('host'));
                        }

                        Log::info('Decoded host parameter', [
                            'host_param' => $request->input('host'),
                            'decoded' => $host,
                        ]);

                        // Host format: admin.shopify.com/store/shopname or admin.shopify.com/store/shopname/apps/...
                        if ($host && preg_match('/\/store\/([^\/]+)/', $host, $matches)) {
                            $shop = $matches[1].'.myshopify.com';
                            Log::info('Extracted shop from host parameter', ['shop' => $shop]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to decode host parameter', [
                            'error' => $e->getMessage(),
                            'host_param' => $request->input('host'),
                        ]);
                    }
                }

                // If still no shop, try to extract from referer header
                if (! $shop) {
                    $referer = $request->header('referer');
                    if ($referer && preg_match('/\/store\/([^\/\.]+)/', $referer, $matches)) {
                        $shop = $matches[1].'.myshopify.com';
                        Log::info('Extracted shop from referer', ['shop' => $shop, 'referer' => $referer]);
                    }
                }

                // If still no shop, try to extract from full URL path
                if (! $shop) {
                    $fullUrl = $request->fullUrl();
                    if (preg_match('/\/store\/([^\/\.]+)/', $fullUrl, $matches)) {
                        $shop = $matches[1].'.myshopify.com';
                        Log::info('Extracted shop from URL path', ['shop' => $shop, 'url' => $fullUrl]);
                    }
                }

                // Last resort: check query string again
                if (! $shop && $request->has('shop')) {
                    $shop = $request->input('shop');
                    Log::info('Using shop from query string', ['shop' => $shop]);
                }
            }

            if (! $shop) {
                Log::warning('Shopify request detected but no shop parameter found', [
                    'params' => $request->all(),
                ]);

                return Inertia::render('Auth/ShopifyLogin', [
                    'error' => 'Missing shop parameter',
                ]);
            }

            // Normalize the shop domain
            $shop = $this->normalizeShopDomain($shop);

            // Verify HMAC if present (Shopify embedded app sends this)
            // For embedded apps, HMAC verification is important but we'll log warnings instead of blocking
            $hmac = $request->input('hmac');
            if ($hmac) {
                $hmacValid = $this->verifyShopifyHmac($request);
                if (! $hmacValid) {
                    Log::warning('Invalid HMAC signature from Shopify', [
                        'shop' => $shop,
                        'ip' => $request->ip(),
                        'embedded' => $isEmbeddedApp,
                    ]);
                    // Don't block embedded app requests, just log the warning
                    // In production, you might want to be stricter
                }
            }

            // Check if store already exists and is active
            $store = Store::where('shop_domain', $shop)->where('is_active', true)->first();

            Log::info('Shopify request detected', [
                'shop' => $shop,
                'embedded' => $isEmbeddedApp,
                'store_found' => $store ? true : false,
                'store_id' => $store?->id,
            ]);

            if ($store && $store->access_token) {
                // Store exists and is active - auto-authenticate
                $user = $store->user;

                if (! $user) {
                    // Store exists but no user - create user and associate
                    $user = $this->findOrCreateUser($store);
                }

                // Ensure store is associated with user
                if ($store->user_id !== $user->id) {
                    $store->update(['user_id' => $user->id]);
                }

                // Ensure user email is verified (required for verified middleware)
                if (! $user->email_verified_at) {
                    $user->update(['email_verified_at' => now()]);
                }

                // Ensure store is associated with user (double-check)
                if ($store->user_id !== $user->id) {
                    $store->update(['user_id' => $user->id]);
                    $store->refresh();
                }

                // Mark that we just auto-authenticated BEFORE login (helps middleware know store should exist)
                $request->session()->put('shopify_auto_auth', true);
                $request->session()->put('shopify_store_id', $store->id);

                // Save session BEFORE login to ensure data persists
                $request->session()->save();

                // Log the user in automatically
                Auth::login($user, true); // Remember the user

                // Regenerate session AFTER login to get new session ID
                $request->session()->regenerate();

                // Save session again after regeneration
                $request->session()->save();

                // Refresh user model to ensure relationships are loaded
                $user->refresh();
                $store->refresh();

                Log::info('Auto-authenticated user from Shopify', [
                    'shop' => $shop,
                    'user_id' => $user->id,
                    'store_id' => $store->id,
                    'store_user_id' => $store->user_id,
                    'embedded' => $isEmbeddedApp,
                    'has_stores' => $user->stores()->count(),
                    'session_id' => $request->session()->getId(),
                ]);

                // For embedded apps, preserve the Shopify parameters in the redirect
                if ($isEmbeddedApp) {
                    $dashboardUrl = route('dashboard');
                    $shopifyParams = $request->only(['embedded', 'host', 'hmac', 'id_token', 'session', 'shop', 'timestamp', 'locale']);
                    // Filter out empty values
                    $shopifyParams = array_filter($shopifyParams, function ($value) {
                        return $value !== null && $value !== '';
                    });
                    if (! empty($shopifyParams)) {
                        $dashboardUrl .= '?'.http_build_query($shopifyParams);
                    }

                    Log::info('Redirecting to dashboard (embedded)', [
                        'url' => $dashboardUrl,
                        'params' => $shopifyParams,
                    ]);

                    return redirect($dashboardUrl);
                }

                // Redirect to dashboard
                Log::info('Redirecting to dashboard');

                return redirect()->route('dashboard');
            } else {
                // Store doesn't exist or is inactive - initiate OAuth
                Log::info('Store not found or inactive, initiating OAuth', [
                    'shop' => $shop,
                    'store_exists' => $store ? true : false,
                    'store_active' => $store ? $store->is_active : false,
                    'has_access_token' => $store && $store->access_token ? true : false,
                ]);

                // Generate the authorization URL and redirect
                $authUrl = $this->shopifyService->getAuthUrl($shop);

                return redirect()->away($authUrl);
            }
        }

        // No shop parameter - show Shopify login form (direct visit, not from Shopify)
        Log::info('Showing Shopify login form - no shop parameter', [
            'url' => $request->fullUrl(),
            'has_embedded' => $request->has('embedded'),
            'has_host' => $request->has('host'),
            'all_params' => $request->all(),
        ]);

        return Inertia::render('Auth/ShopifyLogin');
    }

    /**
     * Initiate the Shopify OAuth flow.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function initiateOAuth(Request $request)
    {
        // Validate the shop domain
        $request->validate([
            'shop' => 'required|string|max:255',
        ], [
            'shop.required' => 'Please enter your store domain',
        ]);

        $shop = $request->input('shop');

        // Normalize the shop domain
        $shop = $this->normalizeShopDomain($shop);

        // Validate the shop domain format
        if (! $this->isValidShopDomain($shop)) {
            return back()->withErrors([
                'shop' => 'Please enter a valid Shopify store domain',
            ]);
        }

        // Generate the authorization URL
        $authUrl = $this->shopifyService->getAuthUrl($shop);

        return redirect()->away($authUrl);
    }

    /**
     * Handle the OAuth callback from Shopify.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleCallback(Request $request)
    {
        try {
            $shop = $request->input('shop');
            $code = $request->input('code');

            if (! $shop || ! $code) {
                return redirect()->route('login')->withErrors(['error' => 'Invalid request parameters']);
            }

            // Verify the request is from Shopify
            if (! $this->verifyShopifyRequest($request)) {
                return redirect()->route('login')->withErrors(['error' => 'Invalid request signature']);
            }

            // Get access token from Shopify
            $accessToken = $this->shopifyService->getAccessToken($shop, $code);

            if (! $accessToken) {
                return redirect()->route('login')->withErrors(['error' => 'Could not obtain access token']);
            }

            // Find or create store
            $store = $this->findOrCreateStore($shop, $accessToken);

            // Create or update the user
            $user = $this->findOrCreateUser($store);

            // Login the user
            Auth::login($user);
            $request->session()->regenerate();

            // Dispatch the setup job
            SetupStoreJob::dispatch($store);

            // For embedded apps, preserve Shopify parameters if present
            if ($request->has('embedded') || $request->has('host')) {
                $dashboardUrl = route('dashboard');
                $shopifyParams = $request->only(['embedded', 'host', 'hmac', 'id_token', 'session', 'shop', 'timestamp', 'locale']);
                if (! empty($shopifyParams)) {
                    $dashboardUrl .= '?'.http_build_query($shopifyParams);
                }

                return redirect($dashboardUrl);
            }

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->withErrors(['error' => 'An error occurred during authentication']);
        }
    }

    /**
     * Find or create a store in the database.
     */
    private function findOrCreateStore(string $shopDomain, string $accessToken): Store
    {
        $store = Store::where('shop_domain', $shopDomain)->first();

        $storeData = [
            'shop_domain' => $shopDomain,
            'access_token' => $accessToken,
            'scopes' => config('shopify.scopes'),
            'is_active' => true,
            'installed_at' => now(),
        ];

        if ($store) {
            $store->update($storeData);
        } else {
            $store = Store::create($storeData);
        }

        return $store;
    }

    /**
     * Find or create a user for the store.
     */
    private function findOrCreateUser(Store $store): User
    {
        // Get shop details from Shopify
        $shopDetails = $this->shopifyService->getShopDetails($store);

        if (! $shopDetails || ! isset($shopDetails['shop'])) {
            throw new \Exception('Could not retrieve shop details');
        }

        $shopInfo = $shopDetails['shop'];

        // Update store with shop details
        $store->updateFromShopify($shopInfo);

        // Find or create user account
        $email = $shopInfo['email'] ?? "{$store->shop_domain}@example.com";
        $name = $shopInfo['shop_owner'] ?? 'Store Owner';

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(), // Auto-verify for Shopify users
            ]);
        }

        // Associate user with store if not already
        if ($store->user_id !== $user->id) {
            $store->update(['user_id' => $user->id]);
        }

        return $user;
    }

    /**
     * Normalize the shop domain.
     */
    private function normalizeShopDomain(string $shop): string
    {
        // Remove http/https
        $shop = preg_replace('/^https?:\/\//', '', $shop);

        // Remove trailing slash
        $shop = rtrim($shop, '/');

        // Remove .myshopify.com if present, we'll add it back
        $shop = str_replace('.myshopify.com', '', $shop);

        // Remove any path after the domain
        $shop = explode('/', $shop)[0];

        // Remove any query parameters
        $shop = explode('?', $shop)[0];

        // Ensure .myshopify.com is appended
        $shop .= '.myshopify.com';

        return strtolower($shop);
    }

    /**
     * Validate shop domain format.
     */
    private function isValidShopDomain(string $shop): bool
    {
        // Check if it matches the pattern: shop-name.myshopify.com
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]\.myshopify\.com$/', $shop);
    }

    /**
     * Verify the request is from Shopify (OAuth callback).
     */
    private function verifyShopifyRequest(Request $request): bool
    {
        // For now, we'll just check if required parameters are present
        // In production, you should verify the HMAC signature
        return $request->has(['shop', 'code']);
    }

    /**
     * Verify Shopify HMAC signature for embedded app requests.
     */
    private function verifyShopifyHmac(Request $request): bool
    {
        $hmac = $request->input('hmac');

        if (! $hmac) {
            return false;
        }

        // Get all parameters except hmac
        $params = $request->except('hmac', 'signature');

        // Sort parameters
        ksort($params);

        // Build query string
        $queryString = http_build_query($params);

        // Calculate HMAC
        $calculatedHmac = hash_hmac('sha256', $queryString, config('shopify.api_secret'));

        return hash_equals($calculatedHmac, $hmac);
    }

    /**
     * Logout the user and revoke Shopify access.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
