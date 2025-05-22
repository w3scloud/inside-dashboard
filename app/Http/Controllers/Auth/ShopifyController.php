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
     * Show the Shopify login form.
     *
     * @return \Inertia\Response
     */
    public function showLogin()
    {
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

            // Dispatch the setup job
            SetupStoreJob::dispatch($store);

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
     * Verify the request is from Shopify.
     */
    private function verifyShopifyRequest(Request $request): bool
    {
        // For now, we'll just check if required parameters are present
        // In production, you should verify the HMAC signature
        return $request->has(['shop', 'code']);
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
