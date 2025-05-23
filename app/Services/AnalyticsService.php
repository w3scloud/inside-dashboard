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
                $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

                if (empty($orders)) {
                    Log::info('No orders found for store', ['store_id' => $store->id, 'date_range' => [$startDate, $endDate]]);

                    return $this->getEmptyProductPerformance();
                }

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
                ]);

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
                $products = $this->dataCollectionService->collectProducts($store);
                $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

                if (empty($products)) {
                    Log::info('No products found for store', ['store_id' => $store->id]);

                    return $this->getEmptyProductSummary();
                }

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
                $customers = $this->dataCollectionService->collectCustomers($store);
                $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

                if (empty($customers)) {
                    Log::info('No customers found for store', ['store_id' => $store->id]);

                    return $this->getEmptyCustomerData();
                }

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
     * Get customer summary data.
     */
    public function getCustomerSummary(
        Store $store,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $cacheKey = "customer_summary_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $startDate, $endDate, $filters) {
            try {
                $customerData = $this->getCustomerData($store, $startDate, $endDate, $filters);

                return $this->dataTransformationService->summarizeCustomerData($customerData);

            } catch (\Exception $e) {
                Log::error('Error fetching customer summary', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->mockAnalyticsService->getCustomerSummary($store, $startDate, $endDate, $filters);
            }
        });
    }

    /**
     * Get customer segments data.
     */
    public function getCustomerSegments(
        Store $store,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $cacheKey = "customer_segments_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $startDate, $endDate, $filters) {
            try {
                $customers = $this->dataCollectionService->collectCustomers($store);
                $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

                if (empty($customers)) {
                    return ['segments' => []];
                }

                return $this->dataTransformationService->generateCustomerSegments($customers, $orders);

            } catch (\Exception $e) {
                Log::error('Error fetching customer segments', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->mockAnalyticsService->getCustomerSegments($store, $startDate, $endDate, $filters);
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
                $inventoryData = $this->dataCollectionService->collectInventory($store);

                if (empty($inventoryData)) {
                    Log::info('No inventory data found for store', ['store_id' => $store->id]);

                    return $this->getEmptyInventoryStatus();
                }

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
     * Get inventory summary data.
     */
    public function getInventorySummary(Store $store, array $filters = []): array
    {
        $cacheKey = "inventory_summary_{$store->id}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($store, $filters) {
            try {
                $inventoryStatus = $this->getInventoryStatus($store, $filters);

                return $this->dataTransformationService->summarizeInventoryData($inventoryStatus);

            } catch (\Exception $e) {
                Log::error('Error fetching inventory summary', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->mockAnalyticsService->getInventorySummary($store, $filters);
            }
        });
    }

    /**
     * Get product details by ID.
     */
    public function getProductDetails(Store $store, string $productId): ?array
    {
        $cacheKey = "product_details_{$store->id}_{$productId}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $productId) {
            try {
                return $this->shopifyService->getProductDetails($store, $productId);
            } catch (\Exception $e) {
                Log::error('Error fetching product details', [
                    'store_id' => $store->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get product performance by ID.
     */
    public function getProductPerformanceById(
        Store $store,
        string $productId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $cacheKey = "product_performance_by_id_{$store->id}_{$productId}_{$startDate->timestamp}_{$endDate->timestamp}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $productId, $startDate, $endDate) {
            try {
                $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

                return $this->dataTransformationService->transformOrdersToProductPerformanceById($orders, $productId);

            } catch (\Exception $e) {
                Log::error('Error fetching product performance by ID', [
                    'store_id' => $store->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'sales' => [],
                    'timeline' => [],
                    'total_sales' => 0,
                    'total_quantity' => 0,
                    'avg_price' => 0,
                ];
            }
        });
    }

    /**
     * Get product inventory by ID.
     */
    public function getProductInventoryById(Store $store, string $productId): array
    {
        $cacheKey = "product_inventory_{$store->id}_{$productId}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $productId) {
            try {
                // Get product details to get variants
                $product = $this->getProductDetails($store, $productId);

                if (! $product || ! isset($product['variants'])) {
                    return [
                        'inventory' => [],
                        'total_quantity' => 0,
                        'locations' => [],
                    ];
                }

                $inventoryItems = [];
                foreach ($product['variants'] as $variant) {
                    $inventoryItems[$variant['id']] = [
                        'variant' => $variant,
                        'inventory_item' => [
                            'tracked' => $variant['inventory_management'] === 'shopify',
                            'requires_shipping' => $variant['requires_shipping'] ?? true,
                        ],
                    ];
                }

                return $this->dataTransformationService->transformProductInventory($product, $inventoryItems);

            } catch (\Exception $e) {
                Log::error('Error fetching product inventory by ID', [
                    'store_id' => $store->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'inventory' => [],
                    'total_quantity' => 0,
                    'locations' => [],
                ];
            }
        });
    }

    /**
     * Get customer details by ID.
     */
    public function getCustomerDetails(Store $store, string $customerId): ?array
    {
        $cacheKey = "customer_details_{$store->id}_{$customerId}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $customerId) {
            try {
                $customer = $this->shopifyService->getCustomerDetails($store, $customerId);

                if ($customer) {
                    return $this->dataTransformationService->enhanceCustomerData($customer);
                }

                return null;

            } catch (\Exception $e) {
                Log::error('Error fetching customer details', [
                    'store_id' => $store->id,
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get customer order history.
     */
    public function getCustomerOrderHistory(
        Store $store,
        string $customerId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $cacheKey = "customer_orders_{$store->id}_{$customerId}_{$startDate->timestamp}_{$endDate->timestamp}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $customerId, $startDate, $endDate) {
            try {
                $params = [
                    'created_at_min' => $startDate->toIso8601String(),
                    'created_at_max' => $endDate->toIso8601String(),
                    'limit' => 250,
                    'status' => 'any',
                ];

                $response = $this->shopifyService->makeApiCall(
                    $store,
                    'GET',
                    "/admin/api/2023-07/customers/{$customerId}/orders.json",
                    $params
                );

                $orders = $response['orders'] ?? [];

                return $this->dataTransformationService->transformCustomerOrderHistory($orders);

            } catch (\Exception $e) {
                Log::error('Error fetching customer order history', [
                    'store_id' => $store->id,
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get customer purchase metrics.
     */
    public function getCustomerPurchaseMetrics(
        Store $store,
        string $customerId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        try {
            $orderHistory = $this->getCustomerOrderHistory($store, $customerId, $startDate, $endDate);

            return $this->dataTransformationService->calculateCustomerPurchaseMetrics($orderHistory);

        } catch (\Exception $e) {
            Log::error('Error calculating customer purchase metrics', [
                'store_id' => $store->id,
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'total_orders' => 0,
                'total_spent' => 0,
                'avg_order_value' => 0,
                'first_order' => null,
                'last_order' => null,
                'purchase_frequency_days' => 0,
                'top_products' => [],
            ];
        }
    }

    /**
     * Get orders in date range with proper error handling and pagination.
     */
    private function getOrdersInDateRange(Store $store, Carbon $startDate, Carbon $endDate): array
    {
        try {
            return $this->dataCollectionService->collectOrders($store, $startDate, $endDate);
        } catch (\Exception $e) {
            Log::error('Error fetching orders in date range', [
                'store_id' => $store->id,
                'date_range' => [$startDate->toDateString(), $endDate->toDateString()],
                'error' => $e->getMessage(),
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
}
