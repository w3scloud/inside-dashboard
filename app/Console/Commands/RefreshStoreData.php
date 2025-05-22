<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\DataCollectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshStoreData extends Command
{
    protected $signature = 'shopify:refresh-data {store_id?} {--clear-cache}';

    protected $description = 'Refresh data from Shopify store and clear cache';

    public function handle(DataCollectionService $dataCollectionService)
    {
        $storeId = $this->argument('store_id');

        if ($storeId) {
            $stores = Store::where('id', $storeId)->active()->get();
        } else {
            $stores = Store::active()->get();
        }

        if ($stores->isEmpty()) {
            $this->error('No active stores found.');

            return 1;
        }

        foreach ($stores as $store) {
            $this->info("Refreshing data for store: {$store->shop_domain}");

            // Clear cache if requested
            if ($this->option('clear-cache')) {
                $this->info('Clearing cache...');
                $dataCollectionService->clearCache($store);
            }

            $this->withProgressBar(['products', 'customers', 'orders', 'inventory'], function ($dataType) use ($store, $dataCollectionService) {
                try {
                    switch ($dataType) {
                        case 'products':
                            Cache::forget("products_{$store->id}");
                            $dataCollectionService->collectProducts($store);
                            break;
                        case 'customers':
                            Cache::forget("customers_{$store->id}");
                            $dataCollectionService->collectCustomers($store);
                            break;
                        case 'orders':
                            // Clear recent orders cache
                            $patterns = ["orders_{$store->id}_*"];
                            foreach ($patterns as $pattern) {
                                Cache::forget($pattern);
                            }
                            $dataCollectionService->collectOrders($store);
                            break;
                        case 'inventory':
                            Cache::forget("inventory_{$store->id}");
                            $dataCollectionService->collectInventory($store);
                            break;
                    }
                } catch (\Exception $e) {
                    $this->error("\nError refreshing {$dataType}: ".$e->getMessage());
                }
            });

            $this->newLine();
            $this->info("✅ Data refresh completed for {$store->shop_domain}");
        }

        return 0;
    }
}
