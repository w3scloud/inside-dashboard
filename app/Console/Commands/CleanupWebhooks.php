<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\WebhookManagementService;
use Illuminate\Console\Command;

class CleanupWebhooks extends Command
{
    protected $signature = 'shopify:cleanup-webhooks {store_id?} {--reset : Delete all webhooks and recreate them}';

    protected $description = 'Clean up webhook conflicts and setup proper webhooks';

    public function handle(WebhookManagementService $webhookService)
    {
        $storeId = $this->argument('store_id');
        $reset = $this->option('reset');

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
            $this->info("Processing webhooks for store: {$store->shop_domain}");

            if ($reset) {
                // Delete all existing webhooks
                $this->info('Deleting all existing webhooks...');
                $deleteResult = $webhookService->deleteAllWebhooks($store);

                if ($deleteResult['success']) {
                    $this->info('✅ Deleted '.count($deleteResult['deleted']).' webhooks');
                } else {
                    $this->warn('❌ Some webhooks could not be deleted:');
                    foreach ($deleteResult['errors'] as $error) {
                        $this->warn("  - {$error}");
                    }
                }

                // Wait a moment for Shopify to process the deletions
                sleep(2);
            }

            // Setup webhooks
            $this->info('Setting up webhooks...');
            $setupResult = $webhookService->setupWebhooks($store);

            if ($setupResult['success']) {
                $this->info('✅ Successfully registered '.count($setupResult['registered']).' webhooks:');
                foreach ($setupResult['registered'] as $topic) {
                    $this->line("  ✓ {$topic}");
                }
            } else {
                $this->warn('⚠️  Webhook setup completed with issues');
            }

            if (! empty($setupResult['errors'])) {
                $this->warn('Errors encountered:');
                foreach ($setupResult['errors'] as $error) {
                    $this->warn("  - {$error}");
                }
            }

            $this->newLine();
        }

        $this->info('🎉 Webhook cleanup completed!');

        return 0;
    }
}
