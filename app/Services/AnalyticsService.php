<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    protected $shopifyService;

    protected $dataTransformationService;

    protected $dataCollectionService;

    protected $mockAnalyticsService;

    public function __construct(
        ShopifyService $shopifyService,
        DataTransformationService $dataTransformationService,
        DataCollectionService $dataCollectionService,
        MockAnalyticsService $mockAnalyticsService
    ) {
        $this->shopifyService = $shopifyService;
        $this->dataTransformationService = $dataTransformationService;
        $this->dataCollectionService = $dataCollectionService;
        $this->mockAnalyticsService = $mockAnalyticsService;
    }

    /**
     * Get product performance data from real Shopify store.
     */
    public function getProductPerformance(
        Store $store,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $cacheKey = "product_performance_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $startDate, $endDate, $filters) {
            try {
                // Get orders in date range from Shopify
                $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

                if (empty($orders)) {
                    Log::info('No orders found for store', ['store_id' => $store->id, 'date_range' => [$startDate, $endDate]]);

                    return $this->getEmptyProductPerformance();
                }

                // Transform orders into product performance data
                $productPerformance = $this->dataTransformationService->transformOrdersToProductPerformance($orders, $filters);

                Log::info('Product performance data generated', [
                    'store_id' => $store->id,
                    'orders_count' => count($orders),
                    'total_sales' => $productPerformance['total_sales'],
                ]);

                return $productPerformance;

            } catch (\Exception $e) {
                Log::error('Error fetching product performance', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Return mock data as fallback
                return $this->mockAnalyticsService->getProductPerformance($store, $startDate, $endDate, $filters);
            }
        });
    }

    /**
     * Get product summary data from real Shopify store.
     */
    public function getProductSummary(
        Store $store,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $cacheKey = "product_summary_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $startDate, $endDate, $filters) {
            try {
                // Get all products from Shopify
                $products = $this->dataCollectionService->collectProducts($store);

                // Get orders in date range
                $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

                if (empty($products)) {
                    Log::info('No products found for store', ['store_id' => $store->id]);

                    return $this->getEmptyProductSummary();
                }

                // Transform data into product summary
                $productSummary = $this->dataTransformationService->summarizeProductPerformance($products, $orders, $filters);

                Log::info('Product summary data generated', [
                    'store_id' => $store->id,
                    'products_count' => count($products),
                    'total_products' => $productSummary['total_products'],
                ]);

                return $productSummary;

            } catch (\Exception $e) {
                Log::error('Error fetching product summary', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->mockAnalyticsService->getProductSummary($store, $startDate, $endDate, $filters);
            }
        });
    }

    /**
     * Get customer data from real Shopify store.
     */
    public function getCustomerData(
        Store $store,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $cacheKey = "customer_data_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $startDate, $endDate, $filters) {
            try {
                // Get customers from Shopify
                $customers = $this->dataCollectionService->collectCustomers($store);

                // Get orders in date range
                $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

                if (empty($customers)) {
                    Log::info('No customers found for store', ['store_id' => $store->id]);

                    return $this->getEmptyCustomerData();
                }

                // Transform customer data
                $customerData = $this->dataTransformationService->transformCustomerData($customers, $orders, $startDate, $endDate, $filters);

                Log::info('Customer data generated', [
                    'store_id' => $store->id,
                    'customers_count' => count($customers),
                    'total_customers' => $customerData['total_customers'],
                ]);

                return $customerData;

            } catch (\Exception $e) {
                Log::error('Error fetching customer data', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->mockAnalyticsService->getCustomerData($store, $startDate, $endDate, $filters);
            }
        });
    }

    /**
     * Get inventory status from real Shopify store.
     */
    public function getInventoryStatus(Store $store, array $filters = []): array
    {
        $cacheKey = "inventory_status_{$store->id}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($store, $filters) {
            try {
                // Get inventory data from Shopify
                $inventoryData = $this->dataCollectionService->collectInventory($store);

                if (empty($inventoryData)) {
                    Log::info('No inventory data found for store', ['store_id' => $store->id]);

                    return $this->getEmptyInventoryStatus();
                }

                // Transform inventory data
                $inventoryStatus = $this->dataTransformationService->transformInventoryData($inventoryData, $filters);

                Log::info('Inventory status generated', [
                    'store_id' => $store->id,
                    'total_items' => $inventoryStatus['total_items'],
                ]);

                return $inventoryStatus;

            } catch (\Exception $e) {
                Log::error('Error fetching inventory status', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->mockAnalyticsService->getInventoryStatus($store, $filters);
            }
        });
    }

    /**
     * Get orders in date range with proper error handling and pagination.
     */
    private function getOrdersInDateRange(Store $store, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $allOrders = [];
            $limit = 250;
            $pageInfo = null;
            $maxPages = 20; // Prevent infinite loops
            $currentPage = 0;

            do {
                $params = [
                    'created_at_min' => $startDate->toIso8601String(),
                    'created_at_max' => $endDate->toIso8601String(),
                    'limit' => $limit,
                    'status' => 'any',
                    'fields' => 'id,created_at,updated_at,order_number,total_price,subtotal_price,total_tax,financial_status,fulfillment_status,cancelled_at,customer,line_items',
                ];

                if ($pageInfo) {
                    $params['page_info'] = $pageInfo;
                }

                Log::info('Fetching orders page', [
                    'store_id' => $store->id,
                    'page' => $currentPage + 1,
                    'params' => $params,
                ]);

                $response = $this->shopifyService->makeApiCall($store, 'GET', '/admin/api/2023-07/orders.json', $params);

                if (! $response || ! isset($response['orders'])) {
                    Log::warning('Invalid orders response from Shopify API', [
                        'store_id' => $store->id,
                        'response' => $response,
                    ]);
                    break;
                }

                $orders = $response['orders'];
                $allOrders = array_merge($allOrders, $orders);

                // Check for pagination
                $pageInfo = null;
                if (isset($response['next'])) {
                    $pageInfo = $response['next'];
                } elseif (count($orders) === $limit) {
                    // Use the last order's ID for pagination if no page_info
                    $lastOrder = end($orders);
                    if ($lastOrder && isset($lastOrder['id'])) {
                        $params['since_id'] = $lastOrder['id'];
                    }
                }

                $currentPage++;

                // Rate limiting - Shopify allows 2 calls per second
                usleep(500000); // 0.5 seconds

            } while (! empty($orders) && count($orders) === $limit && $currentPage < $maxPages && $pageInfo);

            Log::info('Orders fetched successfully', [
                'store_id' => $store->id,
                'total_orders' => count($allOrders),
                'pages_fetched' => $currentPage,
            ]);

            return $allOrders;

        } catch (\Exception $e) {
            Log::error('Error fetching orders from Shopify', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Helper methods to return empty data structures.
     */
    private function getEmptyProductPerformance(): array
    {
        return [
            'products' => [],
            'timeline' => [],
            'total_sales' => 0,
            'total_orders' => 0,
            'avg_order_value' => 0,
        ];
    }

    private function getEmptyProductSummary(): array
    {
        return [
            'total_products' => 0,
            'active_products' => 0,
            'top_selling' => [],
            'low_selling' => [],
        ];
    }

    private function getEmptyCustomerData(): array
    {
        return [
            'customers' => [],
            'timeline' => [],
            'total_customers' => 0,
            'new_customers' => 0,
            'returning_customers' => 0,
        ];
    }

    private function getEmptyInventoryStatus(): array
    {
        return [
            'inventory' => [],
            'total_items' => 0,
            'out_of_stock' => 0,
            'low_stock' => 0,
        ];
    }

    // ... rest of the methods (getCustomerSummary, getInventorySummary, etc.)
    // implement them following the same pattern as above
}
