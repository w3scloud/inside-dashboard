<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    /**
     * Generate the Shopify OAuth URL.
     */
    public function getAuthUrl(string $shop): string
    {
        $apiKey = config('shopify.api_key');
        $scopes = implode(',', config('shopify.scopes'));
        $redirectUri = route('shopify.callback');

        return "https://{$shop}/admin/oauth/authorize?client_id={$apiKey}&scope={$scopes}&redirect_uri={$redirectUri}";
    }

    /**
     * Get access token from Shopify.
     */
    public function getAccessToken(string $shop, string $code): ?string
    {
        try {
            $apiKey = config('shopify.api_key');
            $apiSecret = config('shopify.api_secret');

            $response = Http::post("https://{$shop}/admin/oauth/access_token", [
                'client_id' => $apiKey,
                'client_secret' => $apiSecret,
                'code' => $code,
            ]);

            $data = $response->json();

            if (isset($data['access_token'])) {
                return $data['access_token'];
            }

            Log::error('Failed to get access token', [
                'shop' => $shop,
                'response' => $data,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting access token', [
                'shop' => $shop,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify webhook HMAC.
     */
    public function verifyWebhook(string $hmac, string $data): bool
    {
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, config('shopify.api_secret'), true));

        return hash_equals($calculatedHmac, $hmac);
    }

    /**
     * Get shop details from Shopify.
     */
    public function getShopDetails(Store $store): ?array
    {
        return $this->makeApiCall($store, 'GET', '/admin/api/2023-07/shop.json');
    }

    /**
     * Make an API call to Shopify.
     */
    public function makeApiCall(Store $store, string $method, string $endpoint, array $params = []): ?array
    {
        if (! $store->access_token) {
            Log::error('No access token for store', ['shop' => $store->shop_domain]);

            return null;
        }

        try {
            $baseUrl = "https://{$store->shop_domain}";
            $url = $baseUrl.$endpoint;

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
                'Content-Type' => 'application/json',
            ]);

            if ($method === 'GET') {
                $response = $response->get($url, $params);
            } elseif ($method === 'POST') {
                $response = $response->post($url, $params);
            } elseif ($method === 'PUT') {
                $response = $response->put($url, $params);
            } elseif ($method === 'DELETE') {
                $response = $response->delete($url, $params);
            }

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Shopify API error', [
                'shop' => $store->shop_domain,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception calling Shopify API', [
                'shop' => $store->shop_domain,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get products from Shopify.
     */
    public function getProducts(Store $store, int $limit = 50, int $page = 1): ?array
    {
        $endpoint = '/admin/api/2023-07/products.json';
        $params = [
            'limit' => $limit,
            'page' => $page,
        ];

        return $this->makeApiCall($store, 'GET', $endpoint, $params);
    }

    /**
     * Get product details from Shopify.
     */
    public function getProductDetails(Store $store, string $productId): ?array
    {
        $endpoint = "/admin/api/2023-07/products/{$productId}.json";
        $result = $this->makeApiCall($store, 'GET', $endpoint);

        return $result ? $result['product'] : null;
    }

    /**
     * Get orders from Shopify.
     */
    public function getOrders(Store $store, array $params = []): ?array
    {
        $endpoint = '/admin/api/2023-07/orders.json';
        $defaultParams = [
            'limit' => 50,
            'status' => 'any',
        ];

        $params = array_merge($defaultParams, $params);

        return $this->makeApiCall($store, 'GET', $endpoint, $params);
    }

    /**
     * Get customers from Shopify.
     */
    public function getCustomers(Store $store, array $params = []): ?array
    {
        $endpoint = '/admin/api/2023-07/customers.json';
        $defaultParams = [
            'limit' => 50,
        ];

        $params = array_merge($defaultParams, $params);

        return $this->makeApiCall($store, 'GET', $endpoint, $params);
    }

    /**
     * Get customer details from Shopify.
     */
    public function getCustomerDetails(Store $store, string $customerId): ?array
    {
        $endpoint = "/admin/api/2023-07/customers/{$customerId}.json";
        $result = $this->makeApiCall($store, 'GET', $endpoint);

        return $result ? $result['customer'] : null;
    }

    /**
     * Get inventory levels for a location.
     */
    public function getInventoryLevels(Store $store, string $locationId): ?array
    {
        $endpoint = '/admin/api/2023-07/inventory_levels.json';
        $params = [
            'location_id' => $locationId,
        ];

        return $this->makeApiCall($store, 'GET', $endpoint, $params);
    }

    /**
     * Register a webhook with Shopify.
     */
    public function registerWebhook(Store $store, string $topic, string $address): ?array
    {
        $endpoint = '/admin/api/2023-07/webhooks.json';
        $payload = [
            'webhook' => [
                'topic' => $topic,
                'address' => $address,
                'format' => 'json',
            ],
        ];

        return $this->makeApiCall($store, 'POST', $endpoint, $payload);
    }

    /**
     * Get all webhooks for a store.
     */
    public function getWebhooks(Store $store): ?array
    {
        $endpoint = '/admin/api/2023-07/webhooks.json';

        return $this->makeApiCall($store, 'GET', $endpoint);
    }
}
