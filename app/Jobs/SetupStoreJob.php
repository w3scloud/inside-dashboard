<?php

namespace App\Jobs;

use App\Models\Dashboard;
use App\Models\Store;
use App\Services\DataCollectionService;
use App\Services\ShopifyService;
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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ShopifyService $shopifyService, DataCollectionService $dataCollectionService)
    {
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

            // 2. Register webhooks
            $this->registerWebhooks($shopifyService);

            // 3. Collect initial data
            $initialData = $dataCollectionService->collectInitialData($this->store);

            if (! $initialData['success']) {
                Log::error('Failed to collect initial data', [
                    'shop' => $this->store->shop_domain,
                    'message' => $initialData['message'],
                ]);

                return;
            }

            // 4. Create default dashboard
            $this->createDefaultDashboard();

            Log::info('Store setup completed successfully', [
                'shop' => $this->store->shop_domain,
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

    /**
     * Register webhooks with Shopify.
     */
    private function registerWebhooks(ShopifyService $shopifyService)
    {
        $webhooks = config('shopify.webhooks');
        $baseUrl = config('app.url');
        $registeredWebhooks = [];

        foreach ($webhooks as $topic) {
            $address = $baseUrl.'/webhooks/'.$topic;
            $result = $shopifyService->registerWebhook($this->store, $topic, $address);

            if ($result && isset($result['webhook'])) {
                $registeredWebhooks[] = $topic;
                Log::info('Registered webhook', [
                    'shop' => $this->store->shop_domain,
                    'topic' => $topic,
                ]);
            } else {
                Log::warning('Failed to register webhook', [
                    'shop' => $this->store->shop_domain,
                    'topic' => $topic,
                ]);
            }
        }

        return $registeredWebhooks;
    }

    /**
     * Create a default dashboard for the store.
     */
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
            'name' => 'Overview',
            'description' => 'Default store overview dashboard',
            'is_default' => true,
            'layout' => $this->getDefaultLayout(),
            'settings' => [
                'date_range' => [
                    'start' => Carbon::now()->subDays(30)->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
                'refresh_interval' => 0,
            ],
            'last_viewed_at' => now(),
        ]);

        $this->store->dashboards()->save($dashboard);

        Log::info('Created default dashboard', [
            'shop' => $this->store->shop_domain,
            'dashboard_id' => $dashboard->id,
        ]);
    }

    /**
     * Get default dashboard layout.
     */
    private function getDefaultLayout()
    {
        return [
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Total Sales',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 0, 'y' => 0],
                'config' => ['display' => 'currency'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Total Orders',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 1, 'y' => 0],
                'config' => ['display' => 'number'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Average Order Value',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 2, 'y' => 0],
                'config' => ['display' => 'currency'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Sales Over Time',
                'type' => 'timeline',
                'chart_type' => 'line',
                'data_source' => 'sales',
                'size' => ['w' => 3, 'h' => 2],
                'position' => ['x' => 0, 'y' => 1],
                'config' => [],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Top Selling Products',
                'type' => 'table',
                'data_source' => 'sales',
                'size' => ['w' => 2, 'h' => 2],
                'position' => ['x' => 0, 'y' => 3],
                'config' => [],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Inventory Status',
                'type' => 'pie_chart',
                'data_source' => 'inventory',
                'size' => ['w' => 1, 'h' => 2],
                'position' => ['x' => 2, 'y' => 3],
                'config' => [],
                'filters' => [],
            ],
        ];
    }
}
