<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GraphQLAnalyticsService
{
    protected $shopifyService;

    protected $graphqlService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
        $this->graphqlService = new ShopifyGraphQLService;
    }

    /**
     * Get comprehensive analytics dashboard.
     *
     * If no dates are provided we default to the last 30 days. Widgets
     * on the Vue side pass a date range which we honour here so that
     * all tiles and charts reflect the selected period.
     */
    public function getDashboardAnalytics(
        Store $store,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        // Normalise the date range
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        try {
            return [
                'sales_analytics' => $this->getSalesAnalytics($store, $startDate, $endDate),
                'product_analytics' => $this->getProductAnalytics($store),
                'customer_analytics' => $this->getCustomerAnalytics($store),
                'inventory_analytics' => $this->getInventoryAnalytics($store),
                'performance_metrics' => $this->getPerformanceMetrics($store),
                'data_sources' => $this->getDataSourceStatus($store),
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'generated_at' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            Log::error('Error generating dashboard analytics', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Failed to generate analytics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get sales analytics using GraphQL orders.
     */
    public function getSalesAnalytics(Store $store, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "sales_analytics_{$store->id}_".($startDate ? $startDate->format('Y-m-d') : 'default').'_'.($endDate ? $endDate->format('Y-m-d') : 'default');

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($store, $startDate, $endDate) {
            try {
                $ordersResult = $this->graphqlService->getOrdersByDateRange($store, $startDate, $endDate);

                if (isset($ordersResult['error'])) {
                    Log::warning('Orders result has error', ['error' => $ordersResult['error']]);

                    return $this->generateEmptySalesAnalytics($startDate, $endDate);
                }

                if (empty($ordersResult['orders'])) {
                    Log::info('No orders found in date range', [
                        'store_id' => $store->id,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                    ]);

                    return $this->generateEmptySalesAnalytics($startDate, $endDate);
                }

                return $this->analyzeSalesData($ordersResult['orders'], $startDate, $endDate);

            } catch (\Exception $e) {
                Log::error('Exception in getSalesAnalytics', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->generateEmptySalesAnalytics($startDate, $endDate);
            }
        });
    }

    /**
     * Get product analytics using GraphQL.
     */
    public function getProductAnalytics(Store $store): array
    {
        $cacheKey = "product_analytics_graphql_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store) {
            try {
                $productsResult = $this->graphqlService->getProducts($store, [
                    'first' => 250,
                ]);

                if (isset($productsResult['error']) || empty($productsResult['products'])) {
                    return ['error' => 'No products found or failed to fetch products'];
                }

                return $this->analyzeProductData($productsResult['products']);

            } catch (\Exception $e) {
                Log::error('Error getting product analytics', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return ['error' => 'Failed to fetch product analytics'];
            }
        });
    }

    /**
     * Get customer analytics using GraphQL.
     */
    public function getCustomerAnalytics(Store $store): array
    {
        $cacheKey = "customer_analytics_graphql_{$store->id}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($store) {
            try {
                $customersResult = $this->graphqlService->getCustomers($store, [
                    'first' => 250,
                ]);

                if (isset($customersResult['error']) || empty($customersResult['customers'])) {
                    return [
                        'total_customers' => 0,
                        'new_customers_30d' => 0,
                        'customer_segments' => [],
                        'top_customers' => [],
                        'note' => 'Customer data may require Protected Customer Data Access approval',
                    ];
                }

                return $this->analyzeCustomerData($customersResult['customers']);

            } catch (\Exception $e) {
                Log::error('Error getting customer analytics', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                return ['error' => 'Failed to fetch customer analytics'];
            }
        });
    }

    /**
     * Analyze sales data from GraphQL orders.
     */
    protected function analyzeSalesData(array $orders, Carbon $startDate, Carbon $endDate): array
    {
        $totalSales = 0;
        $totalOrders = count($orders);
        $dailySales = [];
        $productSales = [];
        $statusBreakdown = [];
        $hourlySales = [];

        // Initialize daily sales array
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dailySales[$current->toDateString()] = [
                'date' => $current->toDateString(),
                'sales' => 0,
                'orders' => 0,
            ];
            $current->addDay();
        }

        // Initialize hourly sales for today
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlySales[$hour] = [
                'hour' => $hour,
                'sales' => 0,
                'orders' => 0,
            ];
        }

        foreach ($orders as $order) {
            $amount = $order['total_price'];
            $totalSales += $amount;

            $orderDate = Carbon::parse($order['created_at']);
            $dateString = $orderDate->toDateString();

            // Daily sales
            if (isset($dailySales[$dateString])) {
                $dailySales[$dateString]['sales'] += $amount;
                $dailySales[$dateString]['orders']++;
            }

            // Hourly sales (for today only)
            if ($orderDate->isToday()) {
                $hour = $orderDate->hour;
                $hourlySales[$hour]['sales'] += $amount;
                $hourlySales[$hour]['orders']++;
            }

            // Status breakdown
            $status = $order['financial_status'];
            $statusBreakdown[$status] = ($statusBreakdown[$status] ?? 0) + 1;

            // Product sales analysis
            foreach ($order['line_items'] as $item) {
                $productTitle = $item['product_title'] ?? 'Unknown Product';
                $quantity = $item['quantity'];
                $itemTotal = $item['total'];

                if (! isset($productSales[$productTitle])) {
                    $productSales[$productTitle] = [
                        'title' => $productTitle,
                        'quantity' => 0,
                        'revenue' => 0,
                        'orders' => 0,
                        'avg_price' => 0,
                    ];
                }

                $productSales[$productTitle]['quantity'] += $quantity;
                $productSales[$productTitle]['revenue'] += $itemTotal;
                $productSales[$productTitle]['orders']++;
                $productSales[$productTitle]['avg_price'] = $productSales[$productTitle]['revenue'] / $productSales[$productTitle]['quantity'];
            }
        }

        // Sort products by revenue
        uasort($productSales, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        // Calculate growth metrics
        $midPoint = $startDate->copy()->addDays($startDate->diffInDays($endDate) / 2);
        $firstHalfSales = 0;
        $secondHalfSales = 0;

        foreach ($orders as $order) {
            $orderDate = Carbon::parse($order['created_at']);
            if ($orderDate->lte($midPoint)) {
                $firstHalfSales += $order['total_price'];
            } else {
                $secondHalfSales += $order['total_price'];
            }
        }

        $growthRate = $firstHalfSales > 0 ? (($secondHalfSales - $firstHalfSales) / $firstHalfSales) * 100 : 0;

        $dailySalesArray = array_values($dailySales);
        $hourlySalesArray = array_values($hourlySales);

        return [
            'summary' => [
                'total_sales' => round($totalSales, 2),
                'total_orders' => $totalOrders,
                'average_order_value' => $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0,
                'growth_rate' => round($growthRate, 2),
                'currency' => $orders[0]['currency'] ?? 'USD',
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'days' => $startDate->diffInDays($endDate) + 1,
                ],
            ],

            // Flat data used directly by some widgets
            'daily_sales' => $dailySalesArray,
            'hourly_sales' => $hourlySalesArray,
            'top_products' => array_slice(array_values($productSales), 0, 20),
            'status_breakdown' => $statusBreakdown,

            // Backwards‑compatible "charts" and "trends" structures expected by Vue
            'charts' => [
                'daily_trend' => $dailySalesArray,
                'hourly_today' => $hourlySalesArray,
                'status_pie' => array_map(function ($status, $count) {
                    return ['name' => ucfirst($status), 'value' => $count];
                }, array_keys($statusBreakdown), $statusBreakdown),
            ],
            'trends' => [
                'daily_sales' => $dailySalesArray,
                'hourly_sales' => $hourlySalesArray,
                // Reserved for future monthly aggregation used by the Revenue Trends widget
                'monthly_trends' => [],
            ],
        ];
    }

    /**
     * Analyze product data from GraphQL.
     */
    protected function analyzeProductData(array $products): array
    {
        $totalProducts = count($products);
        $totalVariants = 0;
        $totalInventory = 0;
        $byVendor = [];
        $byType = [];
        $byStatus = [];
        $inventoryStatus = [
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
        ];
        $priceRanges = [
            'under_25' => 0,
            '25_to_100' => 0,
            '100_to_500' => 0,
            'over_500' => 0,
        ];
        $lowStockItems = [];

        foreach ($products as $product) {
            $totalVariants += count($product['variants']);

            // Vendor analysis
            $vendor = $product['vendor'] ?: 'Unknown';
            $byVendor[$vendor] = ($byVendor[$vendor] ?? 0) + 1;

            // Product type analysis
            $type = $product['product_type'] ?: 'Uncategorized';
            $byType[$type] = ($byType[$type] ?? 0) + 1;

            // Status analysis
            $status = $product['status'];
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;

            // Inventory and price analysis
            $productInventory = 0;
            $hasStock = false;
            $minPrice = PHP_FLOAT_MAX;
            $maxPrice = 0;

            foreach ($product['variants'] as $variant) {
                $inventory = $variant['inventory_quantity'] ?? 0;
                $price = $variant['price'];

                $productInventory += $inventory;
                if ($inventory > 0) {
                    $hasStock = true;
                }

                $minPrice = min($minPrice, $price);
                $maxPrice = max($maxPrice, $price);

                // Low stock detection
                if ($inventory <= 5 && $inventory > 0) {
                    $lowStockItems[] = [
                        'product_title' => $product['title'],
                        'variant_title' => $variant['title'],
                        'sku' => $variant['sku'],
                        'inventory' => $inventory,
                    ];
                }
            }

            $totalInventory += $productInventory;

            // Inventory status
            if ($hasStock) {
                if ($productInventory <= 10) {
                    $inventoryStatus['low_stock']++;
                } else {
                    $inventoryStatus['in_stock']++;
                }
            } else {
                $inventoryStatus['out_of_stock']++;
            }

            // Price range analysis
            $avgPrice = $minPrice === PHP_FLOAT_MAX ? 0 : ($minPrice + $maxPrice) / 2;
            if ($avgPrice < 25) {
                $priceRanges['under_25']++;
            } elseif ($avgPrice < 100) {
                $priceRanges['25_to_100']++;
            } elseif ($avgPrice < 500) {
                $priceRanges['100_to_500']++;
            } else {
                $priceRanges['over_500']++;
            }
        }

        // Sort categories
        arsort($byVendor);
        arsort($byType);

        return [
            'summary' => [
                'total_products' => $totalProducts,
                'total_variants' => $totalVariants,
                'total_inventory' => $totalInventory,
                'published_products' => $byStatus['active'] ?? 0,
                'draft_products' => $byStatus['draft'] ?? 0,
            ],
            'inventory_status' => $inventoryStatus,
            'by_vendor' => array_slice($byVendor, 0, 10, true),
            'by_type' => array_slice($byType, 0, 10, true),
            'by_status' => $byStatus,
            'price_ranges' => $priceRanges,
            'low_stock_items' => array_slice($lowStockItems, 0, 20),
            'charts' => [
                'inventory_pie' => [
                    ['name' => 'In Stock', 'value' => $inventoryStatus['in_stock']],
                    ['name' => 'Low Stock', 'value' => $inventoryStatus['low_stock']],
                    ['name' => 'Out of Stock', 'value' => $inventoryStatus['out_of_stock']],
                ],
                'price_distribution' => [
                    ['name' => 'Under $25', 'value' => $priceRanges['under_25']],
                    ['name' => '$25-$100', 'value' => $priceRanges['25_to_100']],
                    ['name' => '$100-$500', 'value' => $priceRanges['100_to_500']],
                    ['name' => 'Over $500', 'value' => $priceRanges['over_500']],
                ],
            ],
        ];
    }

    /**
     * Analyze customer data from GraphQL.
     */
    protected function analyzeCustomerData(array $customers): array
    {
        $totalCustomers = count($customers);
        $newCustomers30d = 0;
        $totalSpent = 0;
        $totalOrders = 0;
        $customerSegments = [
            'new' => 0,
            'returning' => 0,
            'vip' => 0,
        ];
        $topCustomers = [];

        $thirtyDaysAgo = now()->subDays(30);

        foreach ($customers as $customer) {
            $createdAt = Carbon::parse($customer['created_at']);
            $customerTotalSpent = $customer['total_spent'];
            $customerOrdersCount = $customer['orders_count'];

            $totalSpent += $customerTotalSpent;
            $totalOrders += $customerOrdersCount;

            // New customers
            if ($createdAt->greaterThan($thirtyDaysAgo)) {
                $newCustomers30d++;
            }

            // Customer segmentation
            if ($customerOrdersCount === 0) {
                $customerSegments['new']++;
            } elseif ($customerTotalSpent > 500) {
                $customerSegments['vip']++;
            } else {
                $customerSegments['returning']++;
            }

            // Top customers by total spent
            if ($customerTotalSpent > 0) {
                $topCustomers[] = [
                    'id' => $customer['id'],
                    'name' => trim($customer['first_name'].' '.$customer['last_name']),
                    'email' => $customer['email'],
                    'total_spent' => $customerTotalSpent,
                    'orders_count' => $customerOrdersCount,
                    'avg_order_value' => $customerOrdersCount > 0 ? $customerTotalSpent / $customerOrdersCount : 0,
                ];
            }
        }

        // Sort top customers by total spent
        usort($topCustomers, function ($a, $b) {
            return $b['total_spent'] <=> $a['total_spent'];
        });

        return [
            'summary' => [
                'total_customers' => $totalCustomers,
                'new_customers_30d' => $newCustomers30d,
                'total_customer_value' => round($totalSpent, 2),
                'avg_customer_value' => $totalCustomers > 0 ? round($totalSpent / $totalCustomers, 2) : 0,
                'avg_orders_per_customer' => $totalCustomers > 0 ? round($totalOrders / $totalCustomers, 2) : 0,
            ],
            'segments' => $customerSegments,
            'top_customers' => array_slice($topCustomers, 0, 20),
            'charts' => [
                'segments_pie' => [
                    ['name' => 'New', 'value' => $customerSegments['new']],
                    ['name' => 'Returning', 'value' => $customerSegments['returning']],
                    ['name' => 'VIP', 'value' => $customerSegments['vip']],
                ],
            ],
        ];
    }

    /**
     * Get inventory analytics.
     */
    public function getInventoryAnalytics(Store $store): array
    {
        $productAnalytics = $this->getProductAnalytics($store);

        if (isset($productAnalytics['error'])) {
            return $productAnalytics;
        }

        return [
            'summary' => $productAnalytics['summary'],
            'status_breakdown' => $productAnalytics['inventory_status'],
            'low_stock_items' => $productAnalytics['low_stock_items'],
            'alerts' => [
                'out_of_stock' => $productAnalytics['inventory_status']['out_of_stock'],
                'low_stock' => $productAnalytics['inventory_status']['low_stock'],
                'needs_attention' => count($productAnalytics['low_stock_items']),
            ],
        ];
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(Store $store): array
    {
        $salesAnalytics = $this->getSalesAnalytics($store);
        $productAnalytics = $this->getProductAnalytics($store);
        $customerAnalytics = $this->getCustomerAnalytics($store);

        if (isset($salesAnalytics['error']) || isset($productAnalytics['error'])) {
            return ['error' => 'Unable to calculate performance metrics'];
        }

        $totalProducts = $productAnalytics['summary']['total_products'];
        $inStockProducts = $productAnalytics['inventory_status']['in_stock'];
        $totalSales = $salesAnalytics['summary']['total_sales'];
        $totalOrders = $salesAnalytics['summary']['total_orders'];

        return [
            'catalog_health_score' => $totalProducts > 0 ? round(($inStockProducts / $totalProducts) * 100, 1) : 0,
            'sales_velocity' => $totalOrders > 0 ? round($totalSales / 30, 2) : 0, // Daily average
            'order_fulfillment_rate' => 100, // Would need fulfillment data
            'customer_satisfaction_score' => 85, // Would need review/return data
            'inventory_turnover' => 'Calculated based on sales data',
            'growth_metrics' => [
                'sales_growth' => $salesAnalytics['summary']['growth_rate'] ?? 0,
                'customer_growth' => isset($customerAnalytics['summary']) ?
                    round(($customerAnalytics['summary']['new_customers_30d'] / max($customerAnalytics['summary']['total_customers'], 1)) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Generate empty sales analytics when no orders exist.
     */
    protected function generateEmptySalesAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $dailySales = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dailySales[] = [
                'date' => $current->toDateString(),
                'sales' => 0,
                'orders' => 0,
            ];
            $current->addDay();
        }

        $hourlySales = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlySales[] = [
                'hour' => $hour,
                'sales' => 0,
                'orders' => 0,
            ];
        }

        return [
            'summary' => [
                'total_sales' => 0,
                'total_orders' => 0,
                'average_order_value' => 0,
                'growth_rate' => 0,
                'currency' => 'USD',
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'days' => $startDate->diffInDays($endDate) + 1,
                ],
            ],
            'daily_sales' => $dailySales,
            'hourly_sales' => $hourlySales,
            'top_products' => [],
            'status_breakdown' => [],
            'charts' => [
                'daily_trend' => $dailySales,
                'hourly_today' => $hourlySales,
                'status_pie' => [],
            ],
            'note' => 'No orders found in the specified date range',
        ];
    }

    /**
     * Get data source status.
     */
    public function getDataSourceStatus(Store $store): array
    {
        try {
            // Test GraphQL endpoints
            $ordersTest = $this->graphqlService->getOrders($store, ['first' => 1]);
            $customersTest = $this->graphqlService->getCustomers($store, ['first' => 1]);
            $productsTest = $this->graphqlService->getProducts($store, ['first' => 1]);

            return [
                'graphql' => [
                    'orders' => [
                        'available' => ! isset($ordersTest['error']),
                        'status' => ! isset($ordersTest['error']) ? 'working' : 'error',
                        'message' => $ordersTest['error'] ?? 'Working',
                    ],
                    'customers' => [
                        'available' => ! isset($customersTest['error']),
                        'status' => ! isset($customersTest['error']) ? 'working' : 'error',
                        'message' => $customersTest['error'] ?? 'Working',
                    ],
                    'products' => [
                        'available' => ! isset($productsTest['error']),
                        'status' => ! isset($productsTest['error']) ? 'working' : 'error',
                        'message' => $productsTest['error'] ?? 'Working',
                    ],
                ],
                'rest_api' => [
                    'status' => 'limited',
                    'message' => 'Orders and customers require Protected Customer Data Access',
                ],
                'last_check' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to check data source status',
                'message' => $e->getMessage(),
            ];
        }
    }
}
