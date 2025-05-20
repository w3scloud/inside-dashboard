<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    protected $shopifyService;

    protected $dataTransformationService;

    /**
     * Create a new service instance.
     */
    public function __construct(
        ShopifyService $shopifyService,
        DataTransformationService $dataTransformationService
    ) {
        $this->shopifyService = $shopifyService;
        $this->dataTransformationService = $dataTransformationService;
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
        $cacheKey = "product_performance_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($orders)) {
                return [
                    'products' => [],
                    'timeline' => [],
                    'total_sales' => 0,
                    'total_orders' => 0,
                    'avg_order_value' => 0,
                ];
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
        $cacheKey = "product_performance_{$store->id}_{$productId}_{$startDate->timestamp}_{$endDate->timestamp}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $productId, $startDate, $endDate) {
            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($orders)) {
                return [
                    'sales' => [],
                    'timeline' => [],
                    'total_sales' => 0,
                    'total_quantity' => 0,
                    'avg_price' => 0,
                ];
            }

            // Filter orders to only include the specific product
            $productPerformance = $this->dataTransformationService->transformOrdersToProductPerformanceById($orders, $productId);

            return $productPerformance;
        });
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
        $cacheKey = "product_summary_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get all products
            $productsData = $this->shopifyService->getProducts($store, 250);
            $products = $productsData['products'] ?? [];

            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($products) || empty($orders)) {
                return [
                    'total_products' => count($products),
                    'active_products' => 0,
                    'top_selling' => [],
                    'low_selling' => [],
                ];
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
        $cacheKey = "inventory_status_{$store->id}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $filters) {
            // Get all locations
            $locationsData = $this->shopifyService->makeApiCall($store, 'GET', '/admin/api/2023-07/locations.json');
            $locations = $locationsData['locations'] ?? [];

            if (empty($locations)) {
                return [
                    'inventory' => [],
                    'total_items' => 0,
                    'out_of_stock' => 0,
                    'low_stock' => 0,
                ];
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
        $cacheKey = "product_inventory_{$store->id}_{$productId}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $productId) {
            // Get product variants
            $productData = $this->shopifyService->getProductDetails($store, $productId);
            $variants = $productData['variants'] ?? [];

            if (empty($variants)) {
                return [
                    'inventory' => [],
                    'total_quantity' => 0,
                    'locations' => [],
                ];
            }

            // Get inventory items for each variant
            $inventoryItems = [];
            foreach ($variants as $variant) {
                if (isset($variant['inventory_item_id'])) {
                    $inventoryItemData = $this->shopifyService->makeApiCall(
                        $store,
                        'GET',
                        "/admin/api/2023-07/inventory_items/{$variant['inventory_item_id']}.json"
                    );

                    if ($inventoryItemData && isset($inventoryItemData['inventory_item'])) {
                        $inventoryItems[$variant['id']] = [
                            'variant' => $variant,
                            'inventory_item' => $inventoryItemData['inventory_item'],
                        ];
                    }
                }
            }

            // Process inventory data for the product
            $productInventory = $this->dataTransformationService->transformProductInventory($productData, $inventoryItems);

            return $productInventory;
        });
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
        $cacheKey = "customer_data_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get customers
            $customersData = $this->shopifyService->getCustomers($store, ['limit' => 250]);
            $customers = $customersData['customers'] ?? [];

            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($customers) || empty($orders)) {
                return [
                    'customers' => [],
                    'timeline' => [],
                    'total_customers' => count($customers),
                    'new_customers' => 0,
                    'returning_customers' => 0,
                ];
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
        $cacheKey = "customer_segments_{$store->id}_{$startDate->timestamp}_{$endDate->timestamp}_".md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store, $startDate, $endDate, $filters) {
            // Get customer data
            $customerData = $this->getCustomerData($store, $startDate, $endDate, $filters);

            // Get orders in date range
            $orders = $this->getOrdersInDateRange($store, $startDate, $endDate);

            if (empty($customerData['customers']) || empty($orders)) {
                return [
                    'segments' => [],
                    'metrics' => [],
                ];
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
