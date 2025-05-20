<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DataCollectionService
{
    protected $shopifyService;

    /**
     * Create a new service instance.
     */
    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    /**
     * Collect initial data for a store.
     */
    public function collectInitialData(Store $store): array
    {
        try {
            Log::info('Collecting initial data for store', ['shop' => $store->shop_domain]);

            // Collect shop details
            $shopDetails = $this->shopifyService->getShopDetails($store);

            if (! $shopDetails) {
                Log::error('Failed to get shop details', ['shop' => $store->shop_domain]);

                return ['success' => false, 'message' => 'Failed to get shop details'];
            }

            // Collect products
            $products = $this->collectProducts($store);

            // Collect customers
            $customers = $this->collectCustomers($store);

            // Collect orders
            $orders = $this->collectOrders($store);

            // Update store with shop details
            $store->updateFromShopify($shopDetails['shop']);

            return [
                'success' => true,
                'shop' => $shopDetails['shop'],
                'product_count' => count($products),
                'customer_count' => count($customers),
                'order_count' => count($orders),
            ];
        } catch (\Exception $e) {
            Log::error('Exception collecting initial data', [
                'shop' => $store->shop_domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'message' => 'Error collecting data: '.$e->getMessage()];
        }
    }

    /**
     * Collect products for a store.
     */
    public function collectProducts(Store $store): array
    {
        $cacheKey = "products_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store) {
            $allProducts = [];
            $page = 1;
            $hasMore = true;

            while ($hasMore) {
                $response = $this->shopifyService->getProducts($store, 250, $page);

                if (! $response || ! isset($response['products'])) {
                    break;
                }

                $products = $response['products'];
                $allProducts = array_merge($allProducts, $products);

                // Check if there are more products
                $hasMore = count($products) === 250;
                $page++;

                // Avoid rate limits
                if ($hasMore) {
                    usleep(500000); // 0.5 seconds
                }
            }

            return $allProducts;
        });
    }

    /**
     * Collect customers for a store.
     */
    public function collectCustomers(Store $store): array
    {
        $cacheKey = "customers_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store) {
            $allCustomers = [];
            $params = ['limit' => 250];
            $hasMore = true;

            while ($hasMore) {
                $response = $this->shopifyService->getCustomers($store, $params);

                if (! $response || ! isset($response['customers'])) {
                    break;
                }

                $customers = $response['customers'];
                $allCustomers = array_merge($allCustomers, $customers);

                // Check if there are more customers
                $hasMore = count($customers) === 250;

                if ($hasMore && isset($response['next'])) {
                    // Set params for next page
                    $params = ['limit' => 250, 'page_info' => $response['next']];
                } else {
                    $hasMore = false;
                }

                // Avoid rate limits
                if ($hasMore) {
                    usleep(500000); // 0.5 seconds
                }
            }

            return $allCustomers;
        });
    }

    /**
     * Collect orders for a store.
     */
    public function collectOrders(Store $store, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->subDays(90);
        $endDate = $endDate ?: Carbon::now();

        $cacheKey = "orders_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate) {
            $allOrders = [];
            $params = [
                'limit' => 250,
                'status' => 'any',
                'created_at_min' => $startDate->toIso8601String(),
                'created_at_max' => $endDate->toIso8601String(),
            ];
            $hasMore = true;

            while ($hasMore) {
                $response = $this->shopifyService->getOrders($store, $params);

                if (! $response || ! isset($response['orders'])) {
                    break;
                }

                $orders = $response['orders'];
                $allOrders = array_merge($allOrders, $orders);

                // Check if there are more orders
                $hasMore = count($orders) === 250;

                if ($hasMore && isset($response['next'])) {
                    // Set params for next page
                    $params = [
                        'limit' => 250,
                        'status' => 'any',
                        'page_info' => $response['next'],
                    ];
                } else {
                    $hasMore = false;
                }

                // Avoid rate limits
                if ($hasMore) {
                    usleep(500000); // 0.5 seconds
                }
            }

            return $allOrders;
        });
    }

    /**
     * Collect inventory for a store.
     */
    public function collectInventory(Store $store): array
    {
        $cacheKey = "inventory_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store) {
            // Get all locations
            $locationsData = $this->shopifyService->makeApiCall($store, 'GET', '/admin/api/2023-07/locations.json');
            $locations = $locationsData['locations'] ?? [];

            if (empty($locations)) {
                return [];
            }

            $inventoryByLocation = [];

            foreach ($locations as $location) {
                $locationId = $location['id'];
                $inventoryLevels = $this->shopifyService->getInventoryLevels($store, $locationId);

                if ($inventoryLevels && isset($inventoryLevels['inventory_levels'])) {
                    $inventoryByLocation[$locationId] = [
                        'location' => $location,
                        'inventory_levels' => $inventoryLevels['inventory_levels'],
                    ];
                }

                // Avoid rate limits
                usleep(500000); // 0.5 seconds
            }

            return $inventoryByLocation;
        });
    }
}
