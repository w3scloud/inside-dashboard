<?php

namespace App\Services;

use Carbon\Carbon;

class DataTransformationService
{
    /**
     * Transform orders data into product performance format.
     */
    public function transformOrdersToProductPerformance(array $orders, array $filters = []): array
    {
        $products = [];
        $timeline = [];
        $totalSales = 0;
        $totalOrders = count($orders);
        $avgOrderValue = 0;

        if (empty($orders)) {
            return [
                'products' => $products,
                'timeline' => $timeline,
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'avg_order_value' => $avgOrderValue,
            ];
        }

        // Group by date for timeline
        $dateData = [];

        foreach ($orders as $order) {
            // Skip cancelled or refunded orders
            if ($order['cancelled_at'] !== null || $order['financial_status'] === 'refunded') {
                $totalOrders--;

                continue;
            }

            $orderDate = substr($order['created_at'], 0, 10);
            $totalSales += (float) $order['total_price'];

            // Initialize date in timeline if not exists
            if (! isset($dateData[$orderDate])) {
                $dateData[$orderDate] = [
                    'date' => $orderDate,
                    'sales' => 0,
                    'orders' => 0,
                ];
            }

            $dateData[$orderDate]['sales'] += (float) $order['total_price'];
            $dateData[$orderDate]['orders']++;

            // Process line items
            foreach ($order['line_items'] as $item) {
                $productId = (string) $item['product_id'];

                // Skip if not matching filters
                if (! empty($filters['product_type']) && $item['product_type'] !== $filters['product_type']) {
                    continue;
                }

                if (! empty($filters['vendor']) && $item['vendor'] !== $filters['vendor']) {
                    continue;
                }

                // Initialize product if not exists
                if (! isset($products[$productId])) {
                    $products[$productId] = [
                        'id' => $productId,
                        'title' => $item['title'],
                        'vendor' => $item['vendor'] ?? '',
                        'product_type' => $item['product_type'] ?? '',
                        'total_sales' => 0,
                        'total_quantity' => 0,
                        'orders_count' => 0,
                    ];
                }

                $products[$productId]['total_sales'] += (float) $item['price'] * $item['quantity'];
                $products[$productId]['total_quantity'] += $item['quantity'];
                $products[$productId]['orders_count']++;
            }
        }

        // Convert to numeric arrays
        $products = array_values($products);
        $timeline = array_values($dateData);

        // Sort products by total sales
        usort($products, function ($a, $b) {
            return $b['total_sales'] <=> $a['total_sales'];
        });

        // Sort timeline by date
        usort($timeline, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        // Calculate average order value
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        return [
            'products' => $products,
            'timeline' => $timeline,
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'avg_order_value' => $avgOrderValue,
        ];
    }

    /**
     * Transform orders to product performance for a specific product.
     */
    public function transformOrdersToProductPerformanceById(array $orders, string $productId): array
    {
        $sales = [];
        $timeline = [];
        $totalSales = 0;
        $totalQuantity = 0;
        $avgPrice = 0;

        // Group by date for timeline
        $dateData = [];

        foreach ($orders as $order) {
            // Skip cancelled or refunded orders
            if ($order['cancelled_at'] !== null || $order['financial_status'] === 'refunded') {
                continue;
            }

            $orderDate = substr($order['created_at'], 0, 10);

            // Process line items for this product
            foreach ($order['line_items'] as $item) {
                if ((string) $item['product_id'] === $productId) {
                    // Initialize date in timeline if not exists
                    if (! isset($dateData[$orderDate])) {
                        $dateData[$orderDate] = [
                            'date' => $orderDate,
                            'sales' => 0,
                            'quantity' => 0,
                        ];
                    }

                    $itemSales = (float) $item['price'] * $item['quantity'];
                    $dateData[$orderDate]['sales'] += $itemSales;
                    $dateData[$orderDate]['quantity'] += $item['quantity'];

                    $totalSales += $itemSales;
                    $totalQuantity += $item['quantity'];

                    // Collect sale information
                    $sales[] = [
                        'order_id' => $order['id'],
                        'order_number' => $order['order_number'],
                        'date' => $orderDate,
                        'variant_id' => $item['variant_id'],
                        'variant_title' => $item['variant_title'] ?? 'Default',
                        'quantity' => $item['quantity'],
                        'price' => (float) $item['price'],
                        'total' => $itemSales,
                    ];
                }
            }
        }

        // Convert to numeric array
        $timeline = array_values($dateData);

        // Sort timeline by date
        usort($timeline, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        // Calculate average price
        $avgPrice = $totalQuantity > 0 ? $totalSales / $totalQuantity : 0;

        return [
            'sales' => $sales,
            'timeline' => $timeline,
            'total_sales' => $totalSales,
            'total_quantity' => $totalQuantity,
            'avg_price' => $avgPrice,
        ];
    }

    /**
     * Summarize product performance data.
     */
    public function summarizeProductPerformance(array $products, array $orders, array $filters = []): array
    {
        $productSales = [];
        $activeProducts = 0;

        foreach ($products as $product) {
            $productId = (string) $product['id'];
            $productSales[$productId] = [
                'id' => $productId,
                'title' => $product['title'],
                'vendor' => $product['vendor'] ?? '',
                'product_type' => $product['product_type'] ?? '',
                'total_sales' => 0,
                'total_quantity' => 0,
                'orders_count' => 0,
            ];
        }

        foreach ($orders as $order) {
            // Skip cancelled or refunded orders
            if ($order['cancelled_at'] !== null || $order['financial_status'] === 'refunded') {
                continue;
            }

            foreach ($order['line_items'] as $item) {
                $productId = (string) $item['product_id'];

                // Skip if not matching filters
                if (! empty($filters['product_type']) && $item['product_type'] !== $filters['product_type']) {
                    continue;
                }

                if (! empty($filters['vendor']) && $item['vendor'] !== $filters['vendor']) {
                    continue;
                }

                if (isset($productSales[$productId])) {
                    $productSales[$productId]['total_sales'] += (float) $item['price'] * $item['quantity'];
                    $productSales[$productId]['total_quantity'] += $item['quantity'];
                    $productSales[$productId]['orders_count']++;
                }
            }
        }

        // Count active products and sort by total sales
        foreach ($productSales as $productId => $product) {
            if ($product['orders_count'] > 0) {
                $activeProducts++;
            }
        }

        // Sort by total sales
        // Sort by total sales
        uasort($productSales, function ($a, $b) {
            return $b['total_sales'] <=> $a['total_sales'];
        });

        // Get top and low selling products
        $productSalesArray = array_values($productSales);
        $topSelling = array_slice($productSalesArray, 0, 5);

        // Get low selling active products (exclude products with 0 sales)
        $activeSelling = array_filter($productSalesArray, function ($product) {
            return $product['total_sales'] > 0;
        });

        usort($activeSelling, function ($a, $b) {
            return $a['total_sales'] <=> $b['total_sales'];
        });

        $lowSelling = array_slice($activeSelling, 0, 5);

        return [
            'total_products' => count($products),
            'active_products' => $activeProducts,
            'top_selling' => $topSelling,
            'low_selling' => $lowSelling,
        ];
    }

    /**
     * Transform inventory data.
     */
    public function transformInventoryData(array $inventoryData, array $filters = []): array
    {
        $inventory = [];
        $totalItems = 0;
        $outOfStock = 0;
        $lowStock = 0;
        $lowStockThreshold = $filters['low_stock_threshold'] ?? 5;

        foreach ($inventoryData as $locationId => $locationData) {
            $location = $locationData['location'];
            $inventoryLevels = $locationData['inventory_levels'];

            foreach ($inventoryLevels as $level) {
                $inventoryItemId = $level['inventory_item_id'];
                $available = (int) $level['available'];

                // Skip if not matching filters
                if (! empty($filters['location_id']) && $level['location_id'] !== $filters['location_id']) {
                    continue;
                }

                // Initialize inventory item if not exists
                if (! isset($inventory[$inventoryItemId])) {
                    $inventory[$inventoryItemId] = [
                        'inventory_item_id' => $inventoryItemId,
                        'total_available' => 0,
                        'locations' => [],
                    ];

                    $totalItems++;
                }

                $inventory[$inventoryItemId]['total_available'] += $available;
                $inventory[$inventoryItemId]['locations'][$locationId] = [
                    'location_id' => $locationId,
                    'location_name' => $location['name'],
                    'available' => $available,
                ];

                // Check stock status
                if ($inventory[$inventoryItemId]['total_available'] <= 0) {
                    $inventory[$inventoryItemId]['status'] = 'out_of_stock';
                    $outOfStock++;
                } elseif ($inventory[$inventoryItemId]['total_available'] <= $lowStockThreshold) {
                    $inventory[$inventoryItemId]['status'] = 'low_stock';
                    $lowStock++;
                } else {
                    $inventory[$inventoryItemId]['status'] = 'in_stock';
                }
            }
        }

        // Convert to numeric array
        $inventory = array_values($inventory);

        return [
            'inventory' => $inventory,
            'total_items' => $totalItems,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
        ];
    }

    /**
     * Summarize inventory data.
     */
    public function summarizeInventoryData(array $inventoryStatus): array
    {
        $totalItems = $inventoryStatus['total_items'];
        $outOfStock = $inventoryStatus['out_of_stock'];
        $lowStock = $inventoryStatus['low_stock'];
        $inStock = $totalItems - $outOfStock - $lowStock;

        $outOfStockPercentage = $totalItems > 0 ? ($outOfStock / $totalItems) * 100 : 0;
        $lowStockPercentage = $totalItems > 0 ? ($lowStock / $totalItems) * 100 : 0;
        $inStockPercentage = $totalItems > 0 ? ($inStock / $totalItems) * 100 : 0;

        return [
            'total_items' => $totalItems,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'in_stock' => $inStock,
            'out_of_stock_percentage' => $outOfStockPercentage,
            'low_stock_percentage' => $lowStockPercentage,
            'in_stock_percentage' => $inStockPercentage,
            'stock_status' => [
                ['label' => 'In Stock', 'value' => $inStock, 'percentage' => $inStockPercentage],
                ['label' => 'Low Stock', 'value' => $lowStock, 'percentage' => $lowStockPercentage],
                ['label' => 'Out of Stock', 'value' => $outOfStock, 'percentage' => $outOfStockPercentage],
            ],
        ];
    }

    /**
     * Transform product inventory data.
     */
    public function transformProductInventory(array $product, array $inventoryItems): array
    {
        $inventory = [];
        $totalQuantity = 0;
        $locations = [];

        foreach ($inventoryItems as $variantId => $item) {
            $variant = $item['variant'];
            $inventoryItem = $item['inventory_item'];

            $inventory[] = [
                'variant_id' => $variantId,
                'variant_title' => $variant['title'] ?? 'Default',
                'sku' => $variant['sku'] ?? '',
                'inventory_item_id' => $variant['inventory_item_id'],
                'inventory_quantity' => $variant['inventory_quantity'] ?? 0,
                'tracked' => $inventoryItem['tracked'] ?? false,
                'requires_shipping' => $inventoryItem['requires_shipping'] ?? true,
            ];

            $totalQuantity += $variant['inventory_quantity'] ?? 0;
        }

        return [
            'inventory' => $inventory,
            'total_quantity' => $totalQuantity,
            'locations' => $locations,
        ];
    }

    /**
     * Transform customer data.
     */
    public function transformCustomerData(
        array $customers,
        array $orders,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): array {
        $transformedCustomers = [];
        $timeline = [];
        $newCustomers = 0;

        // Group customers by creation date
        $dateData = [];

        foreach ($customers as $customer) {
            $customerId = (string) $customer['id'];
            $createdAt = Carbon::parse($customer['created_at']);

            // Check if customer was created in the date range
            if ($createdAt->between($startDate, $endDate)) {
                $newCustomers++;

                $createdDate = $createdAt->format('Y-m-d');

                // Initialize date in timeline if not exists
                if (! isset($dateData[$createdDate])) {
                    $dateData[$createdDate] = [
                        'date' => $createdDate,
                        'new_customers' => 0,
                        'orders' => 0,
                        'revenue' => 0,
                    ];
                }

                $dateData[$createdDate]['new_customers']++;
            }

            // Skip if not matching filters
            if (! empty($filters['tags']) && ! $this->customerHasTags($customer, $filters['tags'])) {
                continue;
            }

            // Initialize customer data
            $transformedCustomers[$customerId] = [
                'id' => $customerId,
                'email' => $customer['email'],
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'orders_count' => $customer['orders_count'],
                'total_spent' => (float) $customer['total_spent'],
                'tags' => $customer['tags'] ? explode(',', $customer['tags']) : [],
                'created_at' => $customer['created_at'],
                'accepts_marketing' => $customer['accepts_marketing'],
                'recent_orders' => [],
            ];
        }

        // Process orders
        foreach ($orders as $order) {
            $orderDate = substr($order['created_at'], 0, 10);
            $customerId = (string) $order['customer']['id'];

            // Skip cancelled or refunded orders
            if ($order['cancelled_at'] !== null || $order['financial_status'] === 'refunded') {
                continue;
            }

            // Update timeline data
            if (! isset($dateData[$orderDate])) {
                $dateData[$orderDate] = [
                    'date' => $orderDate,
                    'new_customers' => 0,
                    'orders' => 0,
                    'revenue' => 0,
                ];
            }

            $dateData[$orderDate]['orders']++;
            $dateData[$orderDate]['revenue'] += (float) $order['total_price'];

            // Update customer data
            if (isset($transformedCustomers[$customerId])) {
                // Add recent order
                if (count($transformedCustomers[$customerId]['recent_orders']) < 3) {
                    $transformedCustomers[$customerId]['recent_orders'][] = [
                        'id' => $order['id'],
                        'order_number' => $order['order_number'],
                        'date' => $orderDate,
                        'total' => (float) $order['total_price'],
                    ];
                }
            }
        }

        // Convert to numeric arrays
        $transformedCustomers = array_values($transformedCustomers);
        $timeline = array_values($dateData);

        // Sort timeline by date
        usort($timeline, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        // Calculate returning customers
        $returningCustomers = 0;
        foreach ($transformedCustomers as $customer) {
            if ($customer['orders_count'] > 1) {
                $returningCustomers++;
            }
        }

        return [
            'customers' => $transformedCustomers,
            'timeline' => $timeline,
            'total_customers' => count($transformedCustomers),
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
        ];
    }

    /**
     * Summarize customer data.
     */
    public function summarizeCustomerData(array $customerData): array
    {
        $totalCustomers = $customerData['total_customers'];
        $newCustomers = $customerData['new_customers'];
        $returningCustomers = $customerData['returning_customers'];

        // Calculate top customers by total spent
        $customers = $customerData['customers'];

        usort($customers, function ($a, $b) {
            return $b['total_spent'] <=> $a['total_spent'];
        });

        $topCustomers = array_slice($customers, 0, 5);

        // Calculate revenue metrics
        $totalRevenue = 0;
        $orderCount = 0;

        foreach ($customers as $customer) {
            $totalRevenue += $customer['total_spent'];
            $orderCount += $customer['orders_count'];
        }

        $avgOrderValue = $orderCount > 0 ? $totalRevenue / $orderCount : 0;
        $avgCustomerValue = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'top_customers' => $topCustomers,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
            'avg_customer_value' => $avgCustomerValue,
            'customer_segments' => [
                ['label' => 'New Customers', 'value' => $newCustomers],
                ['label' => 'Returning Customers', 'value' => $returningCustomers],
            ],
        ];
    }

    /**
     * Generate customer segments.
     */
    public function generateCustomerSegments(array $customers, array $orders): array
    {
        // Define segments
        $segments = [
            'new' => ['label' => 'New Customers', 'count' => 0, 'revenue' => 0],
            'loyal' => ['label' => 'Loyal Customers', 'count' => 0, 'revenue' => 0],
            'at_risk' => ['label' => 'At-Risk Customers', 'count' => 0, 'revenue' => 0],
            'inactive' => ['label' => 'Inactive Customers', 'count' => 0, 'revenue' => 0],
            'vip' => ['label' => 'VIP Customers', 'count' => 0, 'revenue' => 0],
        ];

        // Group orders by customer
        $customerOrders = [];

        foreach ($orders as $order) {
            $customerId = (string) $order['customer']['id'];

            if (! isset($customerOrders[$customerId])) {
                $customerOrders[$customerId] = [];
            }

            $customerOrders[$customerId][] = [
                'id' => $order['id'],
                'created_at' => $order['created_at'],
                'total_price' => (float) $order['total_price'],
            ];
        }

        // Calculate customer metrics and assign segments
        $now = Carbon::now();
        $metrics = [];

        foreach ($customers as $customer) {
            $customerId = (string) $customer['id'];
            $createdAt = Carbon::parse($customer['created_at']);
            $daysSinceCreation = $createdAt->diffInDays($now);

            $customerMetrics = [
                'id' => $customerId,
                'days_since_creation' => $daysSinceCreation,
                'order_count' => $customer['orders_count'],
                'total_spent' => (float) $customer['total_spent'],
                'avg_order_value' => $customer['orders_count'] > 0 ? $customer['total_spent'] / $customer['orders_count'] : 0,
                'days_since_last_order' => null,
                'segment' => null,
            ];

            // Calculate days since last order
            if (isset($customerOrders[$customerId]) && ! empty($customerOrders[$customerId])) {
                usort($customerOrders[$customerId], function ($a, $b) {
                    return strcmp($b['created_at'], $a['created_at']);
                });

                $lastOrder = $customerOrders[$customerId][0];
                $lastOrderDate = Carbon::parse($lastOrder['created_at']);
                $customerMetrics['days_since_last_order'] = $lastOrderDate->diffInDays($now);
            }

            // Determine segment
            if ($daysSinceCreation <= 30) {
                $customerMetrics['segment'] = 'new';
                $segments['new']['count']++;
                $segments['new']['revenue'] += $customerMetrics['total_spent'];
            } elseif ($customerMetrics['order_count'] >= 3 && $customerMetrics['days_since_last_order'] <= 60) {
                $customerMetrics['segment'] = 'loyal';
                $segments['loyal']['count']++;
                $segments['loyal']['revenue'] += $customerMetrics['total_spent'];
            } elseif ($customerMetrics['order_count'] >= 1 && $customerMetrics['days_since_last_order'] > 60 && $customerMetrics['days_since_last_order'] <= 120) {
                $customerMetrics['segment'] = 'at_risk';
                $segments['at_risk']['count']++;
                $segments['at_risk']['revenue'] += $customerMetrics['total_spent'];
            } elseif ($customerMetrics['days_since_last_order'] > 120 || $customerMetrics['days_since_last_order'] === null) {
                $customerMetrics['segment'] = 'inactive';
                $segments['inactive']['count']++;
                $segments['inactive']['revenue'] += $customerMetrics['total_spent'];
            }

            // Check for VIP status (high total spent)
            if ($customerMetrics['total_spent'] >= 500) {
                $customerMetrics['segment'] = 'vip';
                $segments['vip']['count']++;
                $segments['vip']['revenue'] += $customerMetrics['total_spent'];
            }

            $metrics[] = $customerMetrics;
        }

        // Convert to array for chart data
        $segmentData = array_values($segments);

        return [
            'segments' => $segmentData,
            'metrics' => $metrics,
        ];
    }

    /**
     * Enhance customer data with additional metrics.
     */
    public function enhanceCustomerData(array $customer): array
    {
        // Calculate additional metrics
        $avgOrderValue = $customer['orders_count'] > 0 ? $customer['total_spent'] / $customer['orders_count'] : 0;
        $customerSince = Carbon::parse($customer['created_at'])->diffForHumans();

        // Extract addresses
        $defaultAddress = $customer['default_address'] ?? null;
        $addresses = $customer['addresses'] ?? [];

        // Format tags
        $tags = $customer['tags'] ? explode(',', $customer['tags']) : [];

        return [
            'id' => $customer['id'],
            'email' => $customer['email'],
            'first_name' => $customer['first_name'],
            'last_name' => $customer['last_name'],
            'phone' => $customer['phone'] ?? null,
            'orders_count' => $customer['orders_count'],
            'total_spent' => (float) $customer['total_spent'],
            'avg_order_value' => $avgOrderValue,
            'created_at' => $customer['created_at'],
            'customer_since' => $customerSince,
            'accepts_marketing' => $customer['accepts_marketing'],
            'tags' => $tags,
            'default_address' => $defaultAddress,
            'addresses' => $addresses,
            'note' => $customer['note'] ?? null,
            'verified_email' => $customer['verified_email'] ?? false,
        ];
    }

    /**
     * Transform customer order history.
     */
    public function transformCustomerOrderHistory(array $orders): array
    {
        $transformedOrders = [];

        foreach ($orders as $order) {
            // Skip cancelled orders
            if ($order['cancelled_at'] !== null) {
                continue;
            }

            $transformedOrders[] = [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'created_at' => $order['created_at'],
                'processed_at' => $order['processed_at'],
                'financial_status' => $order['financial_status'],
                'fulfillment_status' => $order['fulfillment_status'],
                'total_price' => (float) $order['total_price'],
                'subtotal_price' => (float) $order['subtotal_price'],
                'total_tax' => (float) $order['total_tax'],
                'total_discounts' => (float) $order['total_discounts'],
                'total_line_items_price' => (float) $order['total_line_items_price'],
                'line_items' => array_map(function ($item) {
                    return [
                        'id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'title' => $item['title'],
                        'variant_title' => $item['variant_title'] ?? null,
                        'quantity' => $item['quantity'],
                        'price' => (float) $item['price'],
                        'total' => (float) $item['price'] * $item['quantity'],
                    ];
                }, $order['line_items']),
            ];
        }

        // Sort by created_at in descending order
        usort($transformedOrders, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $transformedOrders;
    }

    /**
     * Calculate customer purchase metrics.
     */
    public function calculateCustomerPurchaseMetrics(array $orderHistory): array
    {
        $totalOrders = count($orderHistory);
        $totalSpent = 0;
        $firstOrderDate = null;
        $lastOrderDate = null;
        $avgOrderValue = 0;
        $frequencyInDays = 0;
        $productCategories = [];
        $topProducts = [];

        if ($totalOrders > 0) {
            // Calculate total spent and avg order value
            foreach ($orderHistory as $order) {
                $totalSpent += $order['total_price'];

                // Collect product data
                foreach ($order['line_items'] as $item) {
                    $productId = (string) $item['product_id'];

                    if (! isset($topProducts[$productId])) {
                        $topProducts[$productId] = [
                            'id' => $productId,
                            'title' => $item['title'],
                            'quantity' => 0,
                            'total' => 0,
                        ];
                    }

                    $topProducts[$productId]['quantity'] += $item['quantity'];
                    $topProducts[$productId]['total'] += $item['total'];
                }
            }

            $avgOrderValue = $totalSpent / $totalOrders;

            // Determine first and last order dates
            $firstOrder = end($orderHistory);
            $lastOrder = reset($orderHistory);

            $firstOrderDate = $firstOrder['created_at'];
            $lastOrderDate = $lastOrder['created_at'];

            // Calculate frequency in days
            if ($totalOrders > 1) {
                $firstOrderTime = Carbon::parse($firstOrderDate);
                $lastOrderTime = Carbon::parse($lastOrderDate);
                $daysBetween = $firstOrderTime->diffInDays($lastOrderTime);
                $frequencyInDays = $daysBetween / ($totalOrders - 1);
            }

            // Sort top products by total
            uasort($topProducts, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            $topProducts = array_values($topProducts);
            $topProducts = array_slice($topProducts, 0, 5);
        }

        return [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'avg_order_value' => $avgOrderValue,
            'first_order' => $firstOrderDate,
            'last_order' => $lastOrderDate,
            'purchase_frequency_days' => $frequencyInDays,
            'top_products' => $topProducts,
        ];
    }

    /**
     * Helper method to check if a customer has specific tags.
     */
    private function customerHasTags(array $customer, array $tags): bool
    {
        if (empty($customer['tags'])) {
            return false;
        }

        $customerTags = explode(',', $customer['tags']);

        foreach ($tags as $tag) {
            if (in_array($tag, $customerTags)) {
                return true;
            }
        }

        return false;
    }
}
