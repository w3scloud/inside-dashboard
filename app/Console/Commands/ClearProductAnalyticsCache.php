<?php

namespace App\Console\Commands;

use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearProductAnalyticsCache extends Command
{
    protected $signature = 'insight:clear-product-cache {store_id? : Store ID (default: 1)}';

    protected $description = 'Clear product analytics and products cache so dashboard refetches from Shopify';

    public function handle(): int
    {
        $storeId = $this->argument('store_id') ?? 1;
        $store = Store::find($storeId);

        if (! $store) {
            $this->error("Store {$storeId} not found.");

            return 1;
        }

        Cache::forget("product_analytics_graphql_{$store->id}");
        Cache::forget("all_products_{$store->id}_500");
        Cache::forget("all_products_{$store->id}_1000");
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget("products_{$store->id}_250_{$page}");
        }

        $this->info("Cleared product analytics and products cache for store: {$store->shop_domain} (id={$store->id}).");
        $this->info('Reload the dashboard to see fresh product count.');

        return 0;
    }
}
