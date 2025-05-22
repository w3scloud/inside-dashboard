<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DataCollectionService
{
    protected $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    /**
     * Collect products with better error handling and caching.
     */
    public function collectProducts(Store $store): array
    {
        $cacheKey = "products_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($store) {
            try {
                $allProducts = [];
                $limit = 250;
                $pageInfo = null;
                $maxPages = 50;
                $currentPage = 0;

                Log::info('Starting product collection', ['store_id' => $store->id]);

                do {
                    $params = [
                        'limit' => $limit,
                        'fields' => 'id,title,vendor,product_type,created_at,updated_at,status,variants,images,handle',
                    ];

                    if ($pageInfo) {
                        $params['page_info'] = $pageInfo;
                    }

                    $response = $this->shopifyService->makeApiCall($store, 'GET', '/admin/api/2023-07/products.json', $params);

                    if (! $response || ! isset($response['products'])) {
                        Log::warning('Invalid products response', ['store_id' => $store->id, 'page' => $currentPage]);
                        break;
                    }

                    $products = $response['products'];
                    $allProducts = array_merge($allProducts, $products);

                    // Handle pagination
                    $pageInfo = $this->extractPageInfo($response);
                    $currentPage++;

                    Log::info('Products page fetched', [
                        'store_id' => $store->id,
                        'page' => $currentPage,
                        'products_in_page' => count($products),
                        'total_so_far' => count($allProducts),
                    ]);

                    // Rate limiting
                    usleep(500000);

                } while (! empty($products) && count($products) === $limit && $currentPage < $maxPages && $pageInfo);

                Log::info('Product collection completed', [
                    'store_id' => $store->id,
                    'total_products' => count($allProducts),
                    'pages_fetched' => $currentPage,
                ]);

                return $allProducts;

            } catch (\Exception $e) {
                Log::error('Error collecting products', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [];
            }
        });
    }

    /**
     * Collect customers with better error handling.
     */
    public function collectCustomers(Store $store): array
    {
        $cacheKey = "customers_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($store) {
            try {
                $allCustomers = [];
                $limit = 250;
                $pageInfo = null;
                $maxPages = 50;
                $currentPage = 0;

                Log::info('Starting customer collection', ['store_id' => $store->id]);

                do {
                    $params = [
                        'limit' => $limit,
                        'fields' => 'id,email,first_name,last_name,orders_count,total_spent,created_at,updated_at,accepts_marketing,tags,default_address',
                    ];

                    if ($pageInfo) {
                        $params['page_info'] = $pageInfo;
                    }

                    $response = $this->shopifyService->makeApiCall($store, 'GET', '/admin/api/2023-07/customers.json', $params);

                    if (! $response || ! isset($response['customers'])) {
                        Log::warning('Invalid customers response', ['store_id' => $store->id, 'page' => $currentPage]);
                        break;
                    }

                    $customers = $response['customers'];
                    $allCustomers = array_merge($allCustomers, $customers);

                    $pageInfo = $this->extractPageInfo($response);
                    $currentPage++;

                    Log::info('Customers page fetched', [
                        'store_id' => $store->id,
                        'page' => $currentPage,
                        'customers_in_page' => count($customers),
                        'total_so_far' => count($allCustomers),
                    ]);

                    // Rate limiting
                    usleep(500000);

                } while (! empty($customers) && count($customers) === $limit && $currentPage < $maxPages && $pageInfo);

                Log::info('Customer collection completed', [
                    'store_id' => $store->id,
                    'total_customers' => count($allCustomers),
                    'pages_fetched' => $currentPage,
                ]);

                return $allCustomers;

            } catch (\Exception $e) {
                Log::error('Error collecting customers', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Collect orders for a specific date range.
     */
    public function collectOrders(Store $store, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->subDays(90);
        $endDate = $endDate ?: Carbon::now();

        $cacheKey = "orders_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $startDate, $endDate) {
            try {
                $allOrders = [];
                $limit = 250;
                $pageInfo = null;
                $maxPages = 100;
                $currentPage = 0;

                Log::info('Starting order collection', [
                    'store_id' => $store->id,
                    'date_range' => [$startDate->toDateString(), $endDate->toDateString()],
                ]);

                do {
                    $params = [
                        'limit' => $limit,
                        'status' => 'any',
                        'created_at_min' => $startDate->toIso8601String(),
                        'created_at_max' => $endDate->toIso8601String(),
                        'fields' => 'id,created_at,updated_at,order_number,total_price,subtotal_price,total_tax,total_discounts,financial_status,fulfillment_status,cancelled_at,customer,line_items',
                    ];

                    if ($pageInfo) {
                        $params['page_info'] = $pageInfo;
                    }

                    $response = $this->shopifyService->makeApiCall($store, 'GET', '/admin/api/2023-07/orders.json', $params);

                    if (! $response || ! isset($response['orders'])) {
                        Log::warning('Invalid orders response', ['store_id' => $store->id, 'page' => $currentPage]);
                        break;
                    }

                    $orders = $response['orders'];
                    $allOrders = array_merge($allOrders, $orders);

                    $pageInfo = $this->extractPageInfo($response);
                    $currentPage++;

                    Log::info('Orders page fetched', [
                        'store_id' => $store->id,
                        'page' => $currentPage,
                        'orders_in_page' => count($orders),
                        'total_so_far' => count($allOrders),
                    ]);

                    // Rate limiting
                    usleep(500000);

                } while (! empty($orders) && count($orders) === $limit && $currentPage < $maxPages && $pageInfo);

                Log::info('Order collection completed', [
                    'store_id' => $store->id,
                    'total_orders' => count($allOrders),
                    'pages_fetched' => $currentPage,
                ]);

                return $allOrders;

            } catch (\Exception $e) {
                Log::error('Error collecting orders', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Collect inventory with better error handling.
     */
    public function collectInventory(Store $store): array
    {
        $cacheKey = "inventory_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store) {
            try {
                // First get all locations
                $locationsResponse = $this->shopifyService->makeApiCall($store, 'GET', '/admin/api/2023-07/locations.json');
                $locations = $locationsResponse['locations'] ?? [];

                if (empty($locations)) {
                    Log::info('No locations found for store', ['store_id' => $store->id]);

                    return [];
                }

                Log::info('Found locations for inventory collection', [
                    'store_id' => $store->id,
                    'locations_count' => count($locations),
                ]);

                $inventoryByLocation = [];

                foreach ($locations as $location) {
                    $locationId = $location['id'];

                    try {
                        // Get inventory levels for this location
                        $params = [
                            'location_ids' => $locationId,
                            'limit' => 250,
                        ];

                        $inventoryResponse = $this->shopifyService->makeApiCall(
                            $store,
                            'GET',
                            '/admin/api/2023-07/inventory_levels.json',
                            $params
                        );

                        if ($inventoryResponse && isset($inventoryResponse['inventory_levels'])) {
                            $inventoryByLocation[$locationId] = [
                                'location' => $location,
                                'inventory_levels' => $inventoryResponse['inventory_levels'],
                            ];

                            Log::info('Inventory levels fetched for location', [
                                'store_id' => $store->id,
                                'location_id' => $locationId,
                                'location_name' => $location['name'],
                                'inventory_items' => count($inventoryResponse['inventory_levels']),
                            ]);
                        }

                        // Rate limiting
                        usleep(500000);

                    } catch (\Exception $e) {
                        Log::error('Error fetching inventory for location', [
                            'store_id' => $store->id,
                            'location_id' => $locationId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Log::info('Inventory collection completed', [
                    'store_id' => $store->id,
                    'locations_processed' => count($inventoryByLocation),
                ]);

                return $inventoryByLocation;

            } catch (\Exception $e) {
                Log::error('Error collecting inventory', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Extract page info from Shopify API response.
     */
    private function extractPageInfo(array $response): ?string
    {
        // Check for Link header pagination
        if (isset($response['link'])) {
            $linkHeader = $response['link'];
            if (preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                $nextUrl = $matches[1];
                if (preg_match('/page_info=([^&]+)/', $nextUrl, $pageMatches)) {
                    return $pageMatches[1];
                }
            }
        }

        // Check for page_info in response
        if (isset($response['page_info'])) {
            return $response['page_info'];
        }

        return null;
    }

    /**
     * Clear all cached data for a store.
     */
    public function clearCache(Store $store): void
    {
        $patterns = [
            "products_{$store->id}",
            "customers_{$store->id}",
            "inventory_{$store->id}",
            "orders_{$store->id}_*",
            "product_performance_{$store->id}_*",
            "customer_data_{$store->id}_*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        Log::info('Cache cleared for store', ['store_id' => $store->id]);
    }
}
