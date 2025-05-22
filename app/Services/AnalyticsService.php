<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    protected $shopifyService;

    protected $dataTransformationService;

    protected $mockAnalyticsService;

    /**
     * Create a new service instance.
     */
    public function __construct(
        ShopifyService $shopifyService,
        DataTransformationService $dataTransformationService,
        MockAnalyticsService $mockAnalyticsService
    ) {
        $this->shopifyService = $shopifyService;
        $this->dataTransformationService = $dataTransformationService;
        $this->mockAnalyticsService = $mockAnalyticsService;
    }

    /**
     * Get product performance data.
     */
    public function getProductPerformance(
        Store $store,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        // For now, use mock data. In production, you can switch to real Shopify data
        if (config('app.env') === 'local' || config('app.debug')) {
            return $this->mockAnalyticsService->getProductPerformance($store, $startDate, $endDate, $filters);
        }

        $cacheKey = "product_performance_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($orders)) {
                return $this->mockAnalyticsService->getProductPerformance($store, $startDate, $endDate, $filters);
            }

            // Process orders into product performance data
            $productPerformance = $this->dataTransformationService->transformOrdersToProductPerformance($orders, $filters);

            return $productPerformance;
        });
    }

    /**
     * Get product performance data by ID.
     */
    public function getProductPerformanceById(
        Store $store,
        string $productId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        // Return mock data for now
        return [
            'sales' => [
                [
                    'order_id' => '1001',
                    'order_number' => '#1001',
                    'date' => $startDate->format('Y-m-d'),
                    'variant_id' => '2001',
                    'variant_title' => 'Blue / Large',
                    'quantity' => 2,
                    'price' => 29.99,
                    'total' => 59.98,
                ],
            ],
            'timeline' => [
                ['date' => $startDate->format('Y-m-d'), 'sales' => 120, 'quantity' => 4],
                ['date' => $startDate->addDay()->format('Y-m-d'), 'sales' => 180, 'quantity' => 6],
            ],
            'total_sales' => 2400,
            'total_quantity' => 80,
            'avg_price' => 30.00,
        ];
    }

    /**
     * Get product summary data.
     */
    public function getProductSummary(
        Store $store,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        if (config('app.env') === 'local' || config('app.debug')) {
            return $this->mockAnalyticsService->getProductSummary($store, $startDate, $endDate, $filters);
        }

        $cacheKey = "product_summary_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get all products
            $productsData = $this->shopifyService->getProducts($store, 250);
            $products = $productsData['products'] ?? [];

            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($products) || empty($orders)) {
                return $this->mockAnalyticsService->getProductSummary($store, $startDate, $endDate, $filters);
            }

            // Get product performance data
            $productPerformance = $this->dataTransformationService->summarizeProductPerformance($products, $orders, $filters);

            return $productPerformance;
        });
    }

    /**
     * Get inventory status data.
     */
    public function getInventoryStatus(Store $store, array $filters = []): array
    {
        if (config('app.env') === 'local' || config('app.debug')) {
            return $this->mockAnalyticsService->getInventoryStatus($store, $filters);
        }

        $cacheKey = "inventory_status_{$store->id}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $filters) {
            // Get all locations
            $locationsData = $this->shopifyService->makeApiCall($store, 'GET', '/admin/api/2023-07/locations.json');
            $locations = $locationsData['locations'] ?? [];

            if (empty($locations)) {
                return $this->mockAnalyticsService->getInventoryStatus($store, $filters);
            }

            // Get inventory levels for each location
            $inventoryData = [];
            foreach ($locations as $location) {
                $inventoryLevels = $this->shopifyService->getInventoryLevels($store, $location['id']);
                if ($inventoryLevels && isset($inventoryLevels['inventory_levels'])) {
                    $inventoryData[$location['id']] = [
                        'location' => $location,
                        'inventory_levels' => $inventoryLevels['inventory_levels'],
                    ];
                }
            }

            // Process inventory data
            $inventoryStatus = $this->dataTransformationService->transformInventoryData($inventoryData, $filters);

            return $inventoryStatus;
        });
    }

    /**
     * Get inventory summary data.
     */
    public function getInventorySummary(Store $store, array $filters = []): array
    {
        if (config('app.env') === 'local' || config('app.debug')) {
            return $this->mockAnalyticsService->getInventorySummary($store, $filters);
        }

        $cacheKey = "inventory_summary_{$store->id}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $filters) {
            // Get inventory status
            $inventoryStatus = $this->getInventoryStatus($store, $filters);

            // Summarize inventory data
            $inventorySummary = $this->dataTransformationService->summarizeInventoryData($inventoryStatus);

            return $inventorySummary;
        });
    }

    /**
     * Get product inventory data by ID.
     */
    public function getProductInventoryById(Store $store, string $productId): array
    {
        return [
            'inventory' => [
                [
                    'variant_id' => '2001',
                    'variant_title' => 'Blue / Large',
                    'sku' => 'BLU-LG-001',
                    'inventory_item_id' => '3001',
                    'inventory_quantity' => 45,
                    'tracked' => true,
                    'requires_shipping' => true,
                ],
            ],
            'total_quantity' => 45,
            'locations' => [],
        ];
    }

    /**
     * Get customer data.
     */
    public function getCustomerData(
        Store $store,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        if (config('app.env') === 'local' || config('app.debug')) {
            return $this->mockAnalyticsService->getCustomerData($store, $startDate, $endDate, $filters);
        }

        $cacheKey = "customer_data_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get customers
            $customersData = $this->shopifyService->getCustomers($store, ['limit' => 250]);
            $customers = $customersData['customers'] ?? [];

            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($customers) || empty($orders)) {
                return $this->mockAnalyticsService->getCustomerData($store, $startDate, $endDate, $filters);
            }

            // Process customer data
            $customerData = $this->dataTransformationService->transformCustomerData($customers, $orders, $startDate, $endDate, $filters);

            return $customerData;
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
        if (config('app.env') === 'local' || config('app.debug')) {
            return $this->mockAnalyticsService->getCustomerSummary($store, $startDate, $endDate, $filters);
        }

        $cacheKey = "customer_summary_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get customer data
            $customerData = $this->getCustomerData($store, $startDate, $endDate, $filters);

            // Summarize customer data
            $customerSummary = $this->dataTransformationService->summarizeCustomerData($customerData);

            return $customerSummary;
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
        if (config('app.env') === 'local' || config('app.debug')) {
            return $this->mockAnalyticsService->getCustomerSegments($store, $startDate, $endDate, $filters);
        }

        $cacheKey = "customer_segments_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get customer data
            $customerData = $this->getCustomerData($store, $startDate, $endDate, $filters);

            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($customerData['customers']) || empty($orders)) {
                return $this->mockAnalyticsService->getCustomerSegments($store, $startDate, $endDate, $filters);
            }

            // Process customer segments
            $customerSegments = $this->dataTransformationService->generateCustomerSegments($customerData['customers'], $orders);

            return $customerSegments;
        });
    }

    /**
     * Get customer details including orders.
     */
    public function getCustomerDetails(Store $store, string $customerId): ?array
    {
        // Get customer details from Shopify
        $customerData = $this->shopifyService->getCustomerDetails($store, $customerId);

        if (! $customerData) {
            return null;
        }

        // Enhance with additional metrics
        $enhancedCustomer = $this->dataTransformationService->enhanceCustomerData($customerData);

        return $enhancedCustomer;
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
        $params = [
            'customer_id' => $customerId,
            'created_at_min' => $startDate->toIso8601String(),
            'created_at_max' => $endDate->toIso8601String(),
            'limit' => 250,
            'status' => 'any',
        ];

        $ordersData = $this->shopifyService->getOrders($store, $params);
        $orders = $ordersData['orders'] ?? [];

        return $this->dataTransformationService->transformCustomerOrderHistory($orders);
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
        // Get customer order history
        $orderHistory = $this->getCustomerOrderHistory($store, $customerId, $startDate, $endDate);

        // Calculate purchase metrics
        $purchaseMetrics = $this->dataTransformationService->calculateCustomerPurchaseMetrics($orderHistory);

        return $purchaseMetrics;
    }

    /**
     * Helper method to get orders in a date range.
     */
    private function getOrdersInDateRange(Store $store, Carbon $startDate, Carbon $endDate): array
    {
        $params = [
            'created_at_min' => $startDate->toIso8601String(),
            'created_at_max' => $endDate->toIso8601String(),
            'limit' => 250,
            'status' => 'any',
        ];

        $ordersData = $this->shopifyService->getOrders($store, $params);

        return $ordersData['orders'] ?? [];
    }
}
