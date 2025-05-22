<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;

class MockAnalyticsService
{
    /**
     * Get mock product performance data.
     */
    public function getProductPerformance(Store $store, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        // Generate mock timeline data
        $timeline = [];
        $totalSales = 0;
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $sales = rand(500, 5000);
            $orders = rand(5, 50);

            $timeline[] = [
                'date' => $currentDate->format('Y-m-d'),
                'sales' => $sales,
                'orders' => $orders,
            ];

            $totalSales += $sales;
            $currentDate->addDay();
        }

        // Generate mock product data
        $products = [
            [
                'id' => '1',
                'title' => 'Wireless Bluetooth Headphones',
                'vendor' => 'TechCorp',
                'product_type' => 'Electronics',
                'total_sales' => 15000,
                'total_quantity' => 75,
                'orders_count' => 45,
            ],
            [
                'id' => '2',
                'title' => 'Organic Cotton T-Shirt',
                'vendor' => 'EcoWear',
                'product_type' => 'Clothing',
                'total_sales' => 8500,
                'total_quantity' => 170,
                'orders_count' => 85,
            ],
            [
                'id' => '3',
                'title' => 'Stainless Steel Water Bottle',
                'vendor' => 'HydroLife',
                'product_type' => 'Accessories',
                'total_sales' => 6200,
                'total_quantity' => 124,
                'orders_count' => 62,
            ],
            [
                'id' => '4',
                'title' => 'Yoga Mat Premium',
                'vendor' => 'FitnessPro',
                'product_type' => 'Sports',
                'total_sales' => 4800,
                'total_quantity' => 60,
                'orders_count' => 40,
            ],
            [
                'id' => '5',
                'title' => 'Coffee Beans Colombian',
                'vendor' => 'BrewMaster',
                'product_type' => 'Food',
                'total_sales' => 3200,
                'total_quantity' => 80,
                'orders_count' => 32,
            ],
        ];

        return [
            'products' => $products,
            'timeline' => $timeline,
            'total_sales' => $totalSales,
            'total_orders' => count($timeline) * 25, // Average orders per day
            'avg_order_value' => $totalSales / (count($timeline) * 25),
        ];
    }

    /**
     * Get mock product summary data.
     */
    public function getProductSummary(Store $store, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        return [
            'total_products' => 156,
            'active_products' => 142,
            'top_selling' => [
                [
                    'id' => '1',
                    'title' => 'Wireless Bluetooth Headphones',
                    'total_sales' => 15000,
                    'total_quantity' => 75,
                    'orders_count' => 45,
                ],
                [
                    'id' => '2',
                    'title' => 'Organic Cotton T-Shirt',
                    'total_sales' => 8500,
                    'total_quantity' => 170,
                    'orders_count' => 85,
                ],
            ],
            'low_selling' => [
                [
                    'id' => '5',
                    'title' => 'Coffee Beans Colombian',
                    'total_sales' => 3200,
                    'total_quantity' => 80,
                    'orders_count' => 32,
                ],
            ],
        ];
    }

    /**
     * Get mock inventory status data.
     */
    public function getInventoryStatus(Store $store, array $filters = []): array
    {
        return [
            'inventory' => [
                [
                    'inventory_item_id' => '1001',
                    'total_available' => 45,
                    'status' => 'in_stock',
                ],
                [
                    'inventory_item_id' => '1002',
                    'total_available' => 3,
                    'status' => 'low_stock',
                ],
                [
                    'inventory_item_id' => '1003',
                    'total_available' => 0,
                    'status' => 'out_of_stock',
                ],
            ],
            'total_items' => 156,
            'out_of_stock' => 12,
            'low_stock' => 8,
        ];
    }

    /**
     * Get mock inventory summary data.
     */
    public function getInventorySummary(Store $store, array $filters = []): array
    {
        $totalItems = 156;
        $outOfStock = 12;
        $lowStock = 8;
        $inStock = $totalItems - $outOfStock - $lowStock;

        return [
            'total_items' => $totalItems,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'in_stock' => $inStock,
            'stock_status' => [
                ['label' => 'In Stock', 'value' => $inStock],
                ['label' => 'Low Stock', 'value' => $lowStock],
                ['label' => 'Out of Stock', 'value' => $outOfStock],
            ],
        ];
    }

    /**
     * Get mock customer data.
     */
    public function getCustomerData(Store $store, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        // Generate timeline
        $timeline = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $timeline[] = [
                'date' => $currentDate->format('Y-m-d'),
                'new_customers' => rand(2, 15),
                'orders' => rand(10, 60),
                'revenue' => rand(1000, 8000),
            ];

            $currentDate->addDay();
        }

        $customers = [
            [
                'id' => '1001',
                'email' => 'john.doe@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'orders_count' => 8,
                'total_spent' => 1240.50,
                'created_at' => now()->subDays(90)->toIsoString(),
            ],
            [
                'id' => '1002',
                'email' => 'jane.smith@example.com',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'orders_count' => 12,
                'total_spent' => 2150.75,
                'created_at' => now()->subDays(120)->toIsoString(),
            ],
        ];

        return [
            'customers' => $customers,
            'timeline' => $timeline,
            'total_customers' => 1247,
            'new_customers' => 89,
            'returning_customers' => 1158,
        ];
    }

    /**
     * Get mock customer summary data.
     */
    public function getCustomerSummary(Store $store, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        return [
            'total_customers' => 1247,
            'new_customers' => 89,
            'returning_customers' => 1158,
            'total_revenue' => 125000,
            'avg_order_value' => 78.50,
            'avg_customer_value' => 100.24,
            'top_customers' => [
                [
                    'id' => '1002',
                    'email' => 'jane.smith@example.com',
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'orders_count' => 12,
                    'total_spent' => 2150.75,
                ],
                [
                    'id' => '1001',
                    'email' => 'john.doe@example.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'orders_count' => 8,
                    'total_spent' => 1240.50,
                ],
            ],
        ];
    }

    /**
     * Get mock customer segments data.
     */
    public function getCustomerSegments(Store $store, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        return [
            'segments' => [
                ['label' => 'New Customers', 'count' => 89, 'revenue' => 7120],
                ['label' => 'Loyal Customers', 'count' => 156, 'revenue' => 45600],
                ['label' => 'VIP Customers', 'count' => 23, 'revenue' => 34500],
                ['label' => 'At-Risk Customers', 'count' => 67, 'revenue' => 12800],
                ['label' => 'Inactive Customers', 'count' => 912, 'revenue' => 25000],
            ],
        ];
    }
}
