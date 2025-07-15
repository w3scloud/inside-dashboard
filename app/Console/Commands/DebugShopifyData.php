<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\AnalyticsService;
use App\Services\ShopifyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DebugShopifyData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'shopify:debug-data {store_id? : The ID of the specific store to debug}';

    /**
     * The console command description.
     */
    protected $description = 'Debug Shopify data fetching issues and connection problems';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('🚀 Starting Shopify Debug Analysis...');

        $storeId = $this->argument('store_id');

        if ($storeId) {
            $store = Store::find($storeId);
            if (! $store) {
                $this->components->error("Store with ID {$storeId} not found.");

                return self::FAILURE;
            }
            $stores = collect([$store]);
        } else {
            $stores = Store::active()->get();
        }

        if ($stores->isEmpty()) {
            $this->components->error('No active stores found.');
            $this->line('   Try: php artisan shopify:debug-data [store_id]');

            return self::FAILURE;
        }

        foreach ($stores as $store) {
            $this->components->info("🔍 Debugging store: {$store->shop_domain} (ID: {$store->id})");
            $this->newLine();

            // Check store configuration
            $this->components->info('📋 Store Configuration:');
            $this->line("   Shop Domain: {$store->shop_domain}");
            $this->line('   Is Active: '.($store->is_active ? '✅ Yes' : '❌ No'));
            $this->line('   Access Token: '.($store->access_token ? '✅ Present ('.strlen($store->access_token).' chars)' : '❌ Missing'));
            $this->line('   Scopes: '.json_encode($store->scopes));
            $this->line('   Created: '.$store->created_at);
            $this->line('   Updated: '.$store->updated_at);
            $this->newLine();

            // Test basic connection
            $this->components->info('🔌 Testing Shopify API Connection...');
            try {
                $shopifyService = app(ShopifyService::class);
                $shopDetails = $shopifyService->getShopDetails($store);

                if ($shopDetails && isset($shopDetails['shop'])) {
                    $this->components->info('Connection successful!');
                    $this->line('   Shop Name: '.($shopDetails['shop']['name'] ?? 'Unknown'));
                    $this->line('   Plan: '.($shopDetails['shop']['plan_name'] ?? 'Unknown'));
                    $this->line('   Currency: '.($shopDetails['shop']['currency'] ?? 'Unknown'));
                    $this->line('   Timezone: '.($shopDetails['shop']['iana_timezone'] ?? 'Unknown'));
                } else {
                    $this->components->error('Connection failed - No shop details returned');
                    $this->line('   Check your access token and shop domain');

                    continue;
                }
            } catch (\Exception $e) {
                $this->components->error('Connection error: '.$e->getMessage());

                continue;
            }
            $this->newLine();

            // Test orders endpoint with different date ranges
            $this->components->info('📦 Testing Orders Endpoint...');
            $dateRanges = [
                ['days' => 30, 'label' => 'Last 30 days'],
                ['days' => 90, 'label' => 'Last 90 days'],
                ['days' => 365, 'label' => 'Last year'],
            ];

            $foundOrders = false;
            foreach ($dateRanges as $range) {
                try {
                    $ordersResult = $shopifyService->getOrders($store, [
                        'limit' => 50,
                        'created_at_min' => Carbon::now()->subDays($range['days'])->toISOString(),
                        'status' => 'any',
                    ]);

                    if ($ordersResult && isset($ordersResult['orders'])) {
                        $orderCount = count($ordersResult['orders']);

                        if ($orderCount > 0) {
                            $foundOrders = true;
                            $totalSales = 0;
                            $validOrders = 0;
                            $cancelledOrders = 0;
                            $refundedOrders = 0;

                            foreach ($ordersResult['orders'] as $order) {
                                if ($order['cancelled_at']) {
                                    $cancelledOrders++;
                                } elseif ($order['financial_status'] === 'refunded') {
                                    $refundedOrders++;
                                } else {
                                    $totalSales += (float) $order['total_price'];
                                    $validOrders++;
                                }
                            }

                            $this->components->info("{$range['label']}: Found {$orderCount} orders");
                            $this->line("   Valid orders: {$validOrders}");
                            $this->line("   Cancelled orders: {$cancelledOrders}");
                            $this->line("   Refunded orders: {$refundedOrders}");
                            $this->line('   Total sales: $'.number_format($totalSales, 2));

                            if ($validOrders > 0) {
                                $this->line('   Average order value: $'.number_format($totalSales / $validOrders, 2));

                                // Show sample order
                                $sampleOrder = null;
                                foreach ($ordersResult['orders'] as $order) {
                                    if (! $order['cancelled_at'] && $order['financial_status'] !== 'refunded') {
                                        $sampleOrder = $order;
                                        break;
                                    }
                                }

                                if ($sampleOrder) {
                                    $this->line('   Sample order:');
                                    $this->line("     Order #{$sampleOrder['order_number']} - ${$sampleOrder['total_price']}");
                                    $this->line("     Status: {$sampleOrder['financial_status']}");
                                    $this->line('     Date: '.Carbon::parse($sampleOrder['created_at'])->format('Y-m-d H:i:s'));
                                }
                            }
                            break; // Found orders, no need to check longer ranges
                        } else {
                            $this->line("   {$range['label']}: No orders found");
                        }
                    } else {
                        $this->components->error("{$range['label']}: API call failed");
                    }
                } catch (\Exception $e) {
                    $this->components->error("{$range['label']}: Error - ".$e->getMessage());
                }
            }

            if (! $foundOrders) {
                $this->components->warn('No orders found in any date range!');
                $this->line("   This explains why you're seeing zero values.");
                $this->line('   Solutions:');
                $this->line('   - Add test orders to your Shopify store');
                $this->line('   - Check if this is a development store with test data');
                $this->line('   - Verify the store actually has real orders');
            }
            $this->newLine();

            // Test products endpoint
            $this->components->info('🛍️ Testing Products Endpoint...');
            try {
                $productsResult = $shopifyService->getProducts($store, 10);

                if ($productsResult && isset($productsResult['products'])) {
                    $productCount = count($productsResult['products']);
                    $this->components->info("Products endpoint working - Found {$productCount} products");

                    if ($productCount > 0) {
                        $sampleProduct = $productsResult['products'][0];
                        $this->line('   Sample product: '.$sampleProduct['title']);
                        $this->line('   Vendor: '.($sampleProduct['vendor'] ?? 'None'));
                        $this->line('   Status: '.$sampleProduct['status']);
                    }
                } else {
                    $this->components->error('Products endpoint failed');
                }
            } catch (\Exception $e) {
                $this->components->error('Products endpoint error: '.$e->getMessage());
            }
            $this->newLine();

            // Test analytics service
            $this->components->info('📊 Testing Analytics Service...');
            try {
                $analyticsService = app(AnalyticsService::class);
                $startDate = Carbon::now()->subDays(30);
                $endDate = Carbon::now();

                $this->line("   Date range: {$startDate->toDateString()} to {$endDate->toDateString()}");

                $productPerformance = $analyticsService->getProductPerformance($store, $startDate, $endDate);

                $this->components->info('Analytics service working');
                $this->line('   Total Sales: $'.number_format($productPerformance['total_sales'], 2));
                $this->line('   Total Orders: '.number_format($productPerformance['total_orders']));
                $this->line('   Avg Order Value: $'.number_format($productPerformance['avg_order_value'], 2));
                $this->line('   Products with sales: '.count($productPerformance['products']));

                if ($productPerformance['total_sales'] == 0) {
                    $this->components->warn('Zero sales detected!');
                    $this->line('   Possible causes:');
                    $this->line('   - No orders in the selected date range (last 30 days)');
                    $this->line('   - All orders are cancelled or refunded');
                    $this->line('   - Store has no valid orders');
                    $this->line('   - Try extending the date range in your dashboard');
                }

            } catch (\Exception $e) {
                $this->components->error('Analytics service failed: '.$e->getMessage());
                $this->line('   Error in: '.$e->getFile().':'.$e->getLine());
            }
            $this->newLine();

            // Test cache
            $this->components->info('💾 Checking Cache...');
            $cacheKeys = [
                "products_{$store->id}",
                "customers_{$store->id}",
                "orders_{$store->id}_".Carbon::now()->subDays(30)->timestamp.'_'.Carbon::now()->timestamp,
            ];

            foreach ($cacheKeys as $key) {
                $hasCache = \Cache::has($key);
                $this->line("   {$key}: ".($hasCache ? '✅ Cached' : '❌ Not cached'));
            }
            $this->newLine();

            $this->line(str_repeat('━', 60));
            $this->newLine();
        }

        // Summary and recommendations
        $this->components->info('📋 Summary and Recommendations:');
        $this->line('1. Check if your store has actual orders in the last 30 days');
        $this->line('2. If using a development store, add test orders');
        $this->line('3. Try extending the date range in your dashboard (90+ days)');
        $this->line('4. Clear cache if you made changes: php artisan cache:clear');
        $this->line('5. Check Laravel logs: tail -f storage/logs/laravel.log');
        $this->newLine();

        $this->components->info('🎉 Debug analysis completed!');

        return self::SUCCESS;
    }
}
