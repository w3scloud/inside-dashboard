<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\AnalyticsService;
use App\Services\ShopifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugShopifyConnection extends Command
{
    protected $signature = 'shopify:debug {store_id}';

    protected $description = 'Debug Shopify connection for a specific store';

    public function handle(ShopifyService $shopifyService)
    {
        $storeId = $this->argument('store_id');
        $store = Store::find($storeId);

        if (! $store) {
            $this->error("Store with ID {$storeId} not found.");

            return 1;
        }

        $this->info("Debugging Shopify connection for store: {$store->shop_domain}");

        // Test basic connection
        $this->info('Testing basic shop details...');
        $shopDetails = $shopifyService->getShopDetails($store);

        if ($shopDetails) {
            $this->info('✅ Successfully connected to Shopify');
            $this->line('Shop Name: '.($shopDetails['shop']['name'] ?? 'N/A'));
            $this->line('Plan: '.($shopDetails['shop']['plan_name'] ?? 'N/A'));
            $this->line('Currency: '.($shopDetails['shop']['currency'] ?? 'N/A'));
        } else {
            $this->error('❌ Failed to connect to Shopify');

            return 1;
        }

        // Test products endpoint
        $this->info("\nTesting products endpoint...");
        $products = $shopifyService->getProducts($store, 5);

        if ($products && isset($products['products'])) {
            $this->info('✅ Products endpoint working');
            $this->line('Found '.count($products['products']).' products');
        } else {
            $this->error('❌ Products endpoint failed');
        }

        // Test customers endpoint
        $this->info("\nTesting customers endpoint...");
        $customers = $shopifyService->getCustomers($store, ['limit' => 5]);

        if ($customers && isset($customers['customers'])) {
            $this->info('✅ Customers endpoint working');
            $this->line('Found '.count($customers['customers']).' customers');
        } else {
            $this->error('❌ Customers endpoint failed');
        }

        // Test orders endpoint
        $this->info("\nTesting orders endpoint...");
        $orders = $shopifyService->getOrders($store, ['limit' => 5]);

        if ($orders && isset($orders['orders'])) {
            $this->info('✅ Orders endpoint working');
            $this->line('Found '.count($orders['orders']).' orders');
        } else {
            $this->error('❌ Orders endpoint failed');
        }

        $this->info("\n🎉 Shopify connection debug completed!");

        return 0;
    }
}

// TestRealShopifyData Command
class TestRealShopifyData extends Command
{
    protected $signature = 'shopify:test-data {store_id}';

    protected $description = 'Test real Shopify data fetching and transformation';

    public function handle(AnalyticsService $analyticsService)
    {
        $storeId = $this->argument('store_id');
        $store = Store::find($storeId);

        if (! $store) {
            $this->error("Store with ID {$storeId} not found.");

            return 1;
        }

        $this->info("Testing real Shopify data for store: {$store->shop_domain}");

        try {
            // Test product performance
            $this->info("\nTesting product performance data...");
            $startDate = now()->subDays(30);
            $endDate = now();

            $productPerformance = $analyticsService->getProductPerformance($store, $startDate, $endDate);

            $this->info('✅ Product performance data retrieved');
            $this->line('Total Sales: $'.number_format($productPerformance['total_sales'], 2));
            $this->line('Total Orders: '.number_format($productPerformance['total_orders']));
            $this->line('Products Found: '.count($productPerformance['products']));

            // Test customer data
            $this->info("\nTesting customer data...");
            $customerData = $analyticsService->getCustomerData($store, $startDate, $endDate);

            $this->info('✅ Customer data retrieved');
            $this->line('Total Customers: '.number_format($customerData['total_customers']));
            $this->line('New Customers: '.number_format($customerData['new_customers']));

            // Test inventory data
            $this->info("\nTesting inventory data...");
            $inventoryStatus = $analyticsService->getInventoryStatus($store);

            $this->info('✅ Inventory data retrieved');
            $this->line('Total Items: '.number_format($inventoryStatus['total_items']));
            $this->line('Out of Stock: '.number_format($inventoryStatus['out_of_stock']));

            $this->info("\n🎉 All data tests completed successfully!");

        } catch (\Exception $e) {
            $this->error('❌ Error testing data: '.$e->getMessage());
            Log::error('Error in test command', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }

        return 0;
    }
}
