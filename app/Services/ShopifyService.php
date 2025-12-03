<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected $graphqlService;

    public function __construct()
    {
        $this->graphqlService = new ShopifyGraphQLService;
    }

    /**
     * Generate the Shopify OAuth URL.
     */
    public function getAuthUrl(string $shop): string
    {
        $apiKey = config('shopify.api_key');
        $scopes = implode(',', config('shopify.scopes'));
        $redirectUri = route('shopify.callback', [], true); // Get absolute URL

        // URL encode the redirect URI
        $redirectUri = urlencode($redirectUri);

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
     * Make an API call to Shopify with enhanced error handling.
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
            ])->timeout(30);

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

            // Handle specific protected customer data error
            $responseBody = $response->json();
            if (isset($responseBody['errors']) &&
                str_contains($responseBody['errors'], 'protected customer data')) {

                Log::warning('Protected customer data access required', [
                    'shop' => $store->shop_domain,
                    'endpoint' => $endpoint,
                    'error' => $responseBody['errors'],
                ]);

                // Return structured error for calling code to handle
                return [
                    'error' => 'protected_customer_data',
                    'message' => 'This app requires Protected Customer Data Access approval from Shopify',
                    'endpoint' => $endpoint,
                    'documentation' => 'https://shopify.dev/docs/apps/launch/protected-customer-data',
                ];
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
     * Get orders with automatic fallback to GraphQL.
     */
    public function getOrders(Store $store, array $params = []): ?array
    {
        // Try REST API first
        $endpoint = '/admin/api/2023-07/orders.json';
        $defaultParams = [
            'limit' => 50,
            'status' => 'any',
        ];

        $params = array_merge($defaultParams, $params);
        $result = $this->makeApiCall($store, 'GET', $endpoint, $params);

        // If REST fails with protected data error, try GraphQL
        if (is_array($result) && isset($result['error']) && $result['error'] === 'protected_customer_data') {
            Log::info('REST orders failed, trying GraphQL fallback', [
                'store' => $store->shop_domain,
            ]);

            $graphqlResult = $this->graphqlService->getOrders($store, [
                'first' => $params['limit'],
            ]);

            if ($graphqlResult && ! isset($graphqlResult['error'])) {
                return [
                    'orders' => $graphqlResult['orders'],
                    'source' => 'graphql',
                    'pageInfo' => $graphqlResult['pageInfo'] ?? null,
                ];
            }

            // If GraphQL also fails, return structured error
            return [
                'orders' => [],
                'error' => 'protected_customer_data_required',
                'message' => 'Both REST and GraphQL require Protected Customer Data Access approval',
                'source' => 'both_failed',
            ];
        }

        return $result;
    }

    /**
     * Get orders within a date range using best available method.
     */
    public function getOrdersByDateRange(Store $store, Carbon $startDate, Carbon $endDate): array
    {
        // Try GraphQL first for date ranges (more flexible)
        try {
            $graphqlResult = $this->graphqlService->getOrdersByDateRange($store, $startDate, $endDate);

            if ($graphqlResult && ! isset($graphqlResult['error']) && ! empty($graphqlResult['orders'])) {
                Log::info('Successfully retrieved orders via GraphQL', [
                    'store' => $store->shop_domain,
                    'count' => count($graphqlResult['orders']),
                    'date_range' => [$startDate->toDateString(), $endDate->toDateString()],
                ]);

                return $graphqlResult['orders'];
            }
        } catch (\Exception $e) {
            Log::error('GraphQL date range query failed', [
                'store' => $store->shop_domain,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to REST API with date parameters
        try {
            $params = [
                'created_at_min' => $startDate->toISOString(),
                'created_at_max' => $endDate->toISOString(),
                'limit' => 250,
                'status' => 'any',
            ];

            $result = $this->getOrders($store, $params);

            if ($result && isset($result['orders'])) {
                return $result['orders'];
            }
        } catch (\Exception $e) {
            Log::error('REST date range query failed', [
                'store' => $store->shop_domain,
                'error' => $e->getMessage(),
            ]);
        }

        Log::warning('No orders found in date range', [
            'store' => $store->shop_domain,
            'date_range' => [$startDate->toDateString(), $endDate->toDateString()],
        ]);

        return [];
    }

    /**
     * Get customers with GraphQL fallback.
     */
    public function getCustomers(Store $store, array $params = []): ?array
    {
        // Try REST API first
        $endpoint = '/admin/api/2023-07/customers.json';
        $defaultParams = [
            'limit' => 50,
        ];

        $params = array_merge($defaultParams, $params);
        $result = $this->makeApiCall($store, 'GET', $endpoint, $params);

        // If REST fails with protected data error, try GraphQL
        if (is_array($result) && isset($result['error']) && $result['error'] === 'protected_customer_data') {
            Log::info('REST customers failed, trying GraphQL fallback', [
                'store' => $store->shop_domain,
            ]);

            $graphqlResult = $this->graphqlService->getCustomers($store, [
                'first' => $params['limit'],
            ]);

            if ($graphqlResult && ! isset($graphqlResult['error'])) {
                return [
                    'customers' => $graphqlResult['customers'],
                    'source' => 'graphql',
                ];
            }

            // If both fail, return structured error
            return [
                'customers' => [],
                'error' => 'protected_customer_data_required',
                'message' => 'Customer data requires Protected Customer Data Access approval',
            ];
        }

        return $result;
    }

    /**
     * Get products from Shopify with pagination support.
     */
    public function getProducts(Store $store, int $limit = 50, int $page = 1): ?array
    {
        $cacheKey = "products_{$store->id}_{$limit}_{$page}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $limit, $page) {
            $endpoint = '/admin/api/2023-07/products.json';
            $params = [
                'limit' => min($limit, 250), // Shopify max limit
                'page' => $page,
            ];

            return $this->makeApiCall($store, 'GET', $endpoint, $params);
        });
    }

    /**
     * Get all products with automatic pagination.
     */
    public function getAllProducts(Store $store, int $maxProducts = 1000): array
    {
        $cacheKey = "all_products_{$store->id}_{$maxProducts}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $maxProducts) {
            $allProducts = [];
            $page = 1;
            $limit = 250; // Max per page
            $maxPages = ceil($maxProducts / $limit);

            do {
                $response = $this->getProducts($store, $limit, $page);

                if (! $response || ! isset($response['products'])) {
                    break;
                }

                $products = $response['products'];
                $allProducts = array_merge($allProducts, $products);

                Log::info('Fetched products page', [
                    'store_id' => $store->id,
                    'page' => $page,
                    'products_count' => count($products),
                    'total_so_far' => count($allProducts),
                ]);

                $page++;

                // Rate limiting
                if ($page > 1) {
                    usleep(500000); // 0.5 second delay
                }

            } while (count($products) === $limit && $page <= $maxPages && count($allProducts) < $maxProducts);

            return $allProducts;
        });
    }

    /**
     * Get product details from Shopify.
     */
    public function getProductDetails(Store $store, string $productId): ?array
    {
        $cacheKey = "product_details_{$store->id}_{$productId}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $productId) {
            $endpoint = "/admin/api/2023-07/products/{$productId}.json";
            $result = $this->makeApiCall($store, 'GET', $endpoint);

            return $result ? $result['product'] : null;
        });
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
     * Get all locations for a store.
     */
    public function getLocations(Store $store): ?array
    {
        $cacheKey = "locations_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($store) {
            return $this->makeApiCall($store, 'GET', '/admin/api/2023-07/locations.json');
        });
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

    /**
     * Delete a webhook.
     */
    public function deleteWebhook(Store $store, string $webhookId): ?array
    {
        $endpoint = "/admin/api/2023-07/webhooks/{$webhookId}.json";

        return $this->makeApiCall($store, 'DELETE', $endpoint);
    }

    /**
     * Get GraphQL service instance.
     */
    public function graphql(): ShopifyGraphQLService
    {
        return $this->graphqlService;
    }

    /**
     * Test store connection and permissions.
     */
    public function testConnection(Store $store): array
    {
        $tests = [
            'shop' => $this->getShopDetails($store),
            'products' => $this->getProducts($store, 1),
            'locations' => $this->getLocations($store),
            'orders' => $this->getOrders($store, ['limit' => 1]),
            'customers' => $this->getCustomers($store, ['limit' => 1]),
        ];

        $results = [];
        foreach ($tests as $endpoint => $result) {
            if ($result === null) {
                $results[$endpoint] = [
                    'status' => 'failed',
                    'message' => 'API call failed',
                ];
            } elseif (is_array($result) && isset($result['error'])) {
                $results[$endpoint] = [
                    'status' => 'error',
                    'error' => $result['error'],
                    'message' => $result['message'] ?? 'Unknown error',
                ];
            } else {
                $results[$endpoint] = [
                    'status' => 'success',
                    'message' => 'Working',
                ];
            }
        }

        return $results;
    }

    /**
     * Get comprehensive store analytics using available data.
     */
    public function getStoreAnalytics(Store $store): array
    {
        try {
            $analytics = [];

            // Shop details
            $shopDetails = $this->getShopDetails($store);
            if ($shopDetails && isset($shopDetails['shop'])) {
                $analytics['shop'] = [
                    'name' => $shopDetails['shop']['name'] ?? 'Unknown',
                    'domain' => $shopDetails['shop']['domain'] ?? '',
                    'currency' => $shopDetails['shop']['currency'] ?? 'USD',
                    'plan' => $shopDetails['shop']['plan_name'] ?? 'Unknown',
                    'created_at' => $shopDetails['shop']['created_at'] ?? null,
                ];
            }

            // Products analytics
            $products = $this->getAllProducts($store, 500);
            if (! empty($products)) {
                $analytics['products'] = $this->analyzeProducts($products);
            }

            // Try to get orders analytics
            $ordersResult = $this->getOrders($store, ['limit' => 100]);
            if ($ordersResult && isset($ordersResult['orders']) && ! empty($ordersResult['orders'])) {
                $analytics['orders'] = $this->analyzeOrders($ordersResult['orders']);
            } elseif (isset($ordersResult['error'])) {
                $analytics['orders'] = [
                    'error' => $ordersResult['error'],
                    'message' => $ordersResult['message'],
                ];
            }

            // Locations
            $locations = $this->getLocations($store);
            if ($locations && isset($locations['locations'])) {
                $analytics['locations'] = [
                    'total' => count($locations['locations']),
                    'active' => count(array_filter($locations['locations'], function ($loc) {
                        return $loc['active'] ?? false;
                    })),
                ];
            }

            return $analytics;

        } catch (\Exception $e) {
            Log::error('Error getting store analytics', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Failed to retrieve store analytics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze products data.
     */
    protected function analyzeProducts(array $products): array
    {
        $totalProducts = count($products);
        $totalVariants = 0;
        $totalInventory = 0;
        $byVendor = [];
        $byType = [];
        $byStatus = [];
        $outOfStock = 0;

        foreach ($products as $product) {
            $variants = $product['variants'] ?? [];
            $totalVariants += count($variants);

            // Status
            $status = $product['status'] ?? 'unknown';
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;

            // Vendor
            $vendor = $product['vendor'] ?: 'Unknown';
            $byVendor[$vendor] = ($byVendor[$vendor] ?? 0) + 1;

            // Product type
            $type = $product['product_type'] ?: 'Uncategorized';
            $byType[$type] = ($byType[$type] ?? 0) + 1;

            // Inventory
            $productInventory = 0;
            $hasStock = false;

            foreach ($variants as $variant) {
                $qty = $variant['inventory_quantity'] ?? 0;
                $productInventory += $qty;
                if ($qty > 0) {
                    $hasStock = true;
                }
            }

            $totalInventory += $productInventory;

            if (! $hasStock) {
                $outOfStock++;
            }
        }

        return [
            'total_products' => $totalProducts,
            'total_variants' => $totalVariants,
            'total_inventory' => $totalInventory,
            'out_of_stock' => $outOfStock,
            'by_status' => $byStatus,
            'by_vendor' => array_slice($byVendor, 0, 10, true),
            'by_type' => array_slice($byType, 0, 10, true),
        ];
    }

    /**
     * Analyze orders data.
     */
    protected function analyzeOrders(array $orders): array
    {
        $totalOrders = count($orders);
        $totalRevenue = 0;
        $byStatus = [];

        foreach ($orders as $order) {
            $amount = floatval($order['total_price'] ?? 0);
            $totalRevenue += $amount;

            $status = $order['financial_status'] ?? 'unknown';
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
        }

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Check if protected customer data access is available.
     */
    public function hasProtectedDataAccess(Store $store): array
    {
        // Test orders endpoint
        $ordersTest = $this->makeApiCall($store, 'GET', '/admin/api/2023-07/orders.json', ['limit' => 1]);

        if (is_array($ordersTest) && isset($ordersTest['error']) && $ordersTest['error'] === 'protected_customer_data') {
            return [
                'has_access' => false,
                'status' => 'pending_approval',
                'message' => 'Protected Customer Data Access approval required',
                'documentation' => 'https://shopify.dev/docs/apps/launch/protected-customer-data',
            ];
        }

        if ($ordersTest && isset($ordersTest['orders'])) {
            return [
                'has_access' => true,
                'status' => 'approved',
                'message' => 'Protected Customer Data Access is active',
            ];
        }

        return [
            'has_access' => false,
            'status' => 'unknown',
            'message' => 'Unable to determine protected data access status',
            'raw_response' => $ordersTest,
        ];
    }
}
