<?php

namespace App\Jobs;

use App\Models\Dashboard;
use App\Models\Store;
use App\Services\DataCollectionService;
use App\Services\ShopifyService;
use App\Services\WebhookManagementService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SetupStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function handle(
        ShopifyService $shopifyService,
        DataCollectionService $dataCollectionService,
        WebhookManagementService $webhookService
    ) {
        try {
            Log::info('Setting up store', ['shop' => $this->store->shop_domain]);

            // 1. Verify access token is still valid
            $shopDetails = $shopifyService->getShopDetails($this->store);

            if (! $shopDetails) {
                Log::error('Failed to get shop details, access token may be invalid', [
                    'shop' => $this->store->shop_domain,
                ]);

                return;
            }

            // 2. Setup webhooks with proper conflict handling
            $webhookResult = $webhookService->setupWebhooks($this->store);

            Log::info('Webhook setup completed', [
                'shop' => $this->store->shop_domain,
                'registered' => $webhookResult['registered'],
                'errors' => $webhookResult['errors'],
                'success' => $webhookResult['success'],
            ]);

            // 3. Collect initial data (with better error handling)
            $initialData = $dataCollectionService->collectInitialData($this->store);

            if (! $initialData['success']) {
                Log::warning('Initial data collection had issues', [
                    'shop' => $this->store->shop_domain,
                    'message' => $initialData['message'],
                    'errors' => $initialData['errors'],
                ]);
            }

            // 4. Create default dashboard
            $this->createDefaultDashboard();

            // 5. Update store metadata with setup completion
            $metadata = $this->store->metadata ?? [];
            $metadata['setup_completed_at'] = now()->toIso8601String();
            $metadata['last_webhook_setup'] = now()->toIso8601String();
            $metadata['initial_data_collection'] = $initialData;
            $this->store->update(['metadata' => $metadata]);

            Log::info('Store setup completed successfully', [
                'shop' => $this->store->shop_domain,
                'webhooks_registered' => count($webhookResult['registered']),
                'product_count' => $initialData['product_count'],
                'customer_count' => $initialData['customer_count'],
                'order_count' => $initialData['order_count'],
            ]);

        } catch (\Exception $e) {
            Log::error('Exception during store setup', [
                'shop' => $this->store->shop_domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function createDefaultDashboard()
    {
        // Check if dashboard already exists
        $existingDashboard = $this->store->dashboards()->exists();

        if ($existingDashboard) {
            Log::info('Default dashboard already exists', [
                'shop' => $this->store->shop_domain,
            ]);

            return;
        }

        $dashboard = new Dashboard([
            'name' => 'Store Overview',
            'description' => 'Default analytics dashboard for your store',
            'is_default' => true,
            'layout' => $this->getDefaultLayout(),
            'settings' => [
                'date_range' => [
                    'start' => Carbon::now()->subDays(30)->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
                'refresh_interval' => 300, // 5 minutes
                'theme' => 'light',
            ],
            'last_viewed_at' => now(),
        ]);

        $this->store->dashboards()->save($dashboard);

        Log::info('Created default dashboard', [
            'shop' => $this->store->shop_domain,
            'dashboard_id' => $dashboard->id,
        ]);
    }

    private function getDefaultLayout()
    {
        return [
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Total Revenue',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 0, 'y' => 0],
                'config' => ['display' => 'currency', 'metric' => 'total_sales'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Orders Count',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 1, 'y' => 0],
                'config' => ['display' => 'number', 'metric' => 'total_orders'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Average Order Value',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 2, 'y' => 0],
                'config' => ['display' => 'currency', 'metric' => 'avg_order_value'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Sales Trends',
                'type' => 'timeline',
                'chart_type' => 'line',
                'data_source' => 'sales',
                'size' => ['w' => 3, 'h' => 2],
                'position' => ['x' => 0, 'y' => 1],
                'config' => ['show_orders' => true, 'show_revenue' => true],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Top Products',
                'type' => 'table',
                'data_source' => 'sales',
                'size' => ['w' => 2, 'h' => 2],
                'position' => ['x' => 0, 'y' => 3],
                'config' => ['limit' => 10, 'sort_by' => 'revenue'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Inventory Status',
                'type' => 'pie_chart',
                'data_source' => 'inventory',
                'size' => ['w' => 1, 'h' => 2],
                'position' => ['x' => 2, 'y' => 3],
                'config' => ['show_percentages' => true],
                'filters' => [],
            ],
        ];
    }
}
