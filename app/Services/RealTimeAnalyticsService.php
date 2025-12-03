<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RealTimeAnalyticsService
{
    /**
     * Process webhook data and update real-time analytics.
     */
    public function processWebhookData(string $topic, array $webhookData, Store $store): void
    {
        try {
            Log::info('Processing webhook data for real-time analytics', [
                'topic' => $topic,
                'store_id' => $store->id,
                'entity_id' => $webhookData['id'] ?? null,
            ]);

            // Clear relevant caches based on webhook topic
            $this->clearRelevantCaches($topic, $store);

            // Process specific webhook types
            match (true) {
                str_starts_with($topic, 'orders/') => $this->processOrderWebhook($topic, $webhookData, $store),
                str_starts_with($topic, 'products/') => $this->processProductWebhook($topic, $webhookData, $store),
                str_starts_with($topic, 'customers/') => $this->processCustomerWebhook($topic, $webhookData, $store),
                str_starts_with($topic, 'inventory_') => $this->processInventoryWebhook($topic, $webhookData, $store),
                default => Log::debug('No specific processing for webhook topic', ['topic' => $topic]),
            };

        } catch (\Exception $e) {
            Log::error('Error processing webhook data for real-time analytics', [
                'topic' => $topic,
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Clear relevant caches based on webhook topic.
     */
    protected function clearRelevantCaches(string $topic, Store $store): void
    {
        $cachePatterns = [];

        // Determine which caches to clear based on topic
        if (str_starts_with($topic, 'orders/')) {
            $cachePatterns = [
                "sales_analytics_{$store->id}_*",
                "product_performance_{$store->id}_*",
                "customer_data_{$store->id}_*",
                "dashboard_analytics_{$store->id}",
            ];
        } elseif (str_starts_with($topic, 'products/')) {
            $cachePatterns = [
                "product_analytics_graphql_{$store->id}",
                "product_performance_{$store->id}_*",
                "product_summary_{$store->id}_*",
                "dashboard_analytics_{$store->id}",
            ];
        } elseif (str_starts_with($topic, 'customers/')) {
            $cachePatterns = [
                "customer_analytics_graphql_{$store->id}",
                "customer_data_{$store->id}_*",
                "dashboard_analytics_{$store->id}",
            ];
        } elseif (str_starts_with($topic, 'inventory_')) {
            $cachePatterns = [
                "product_analytics_graphql_{$store->id}",
                "product_performance_{$store->id}_*",
                "dashboard_analytics_{$store->id}",
            ];
        }

        // Clear matching caches
        foreach ($cachePatterns as $pattern) {
            $this->clearCachePattern($pattern);
        }

        Log::debug('Cleared caches for webhook', [
            'topic' => $topic,
            'store_id' => $store->id,
            'patterns' => $cachePatterns,
        ]);
    }

    /**
     * Clear cache by pattern (simplified - clears common cache keys).
     */
    protected function clearCachePattern(string $pattern): void
    {
        // Laravel cache doesn't support wildcard deletion natively
        // So we'll clear the most common cache keys
        // In production, you might want to use Redis with pattern matching or tag-based caching

        // For now, we'll clear specific known cache keys
        // This is a simplified approach - in production, consider using cache tags
        try {
            // Clear dashboard analytics cache
            if (str_contains($pattern, 'dashboard_analytics')) {
                Cache::forget($pattern);
            }
        } catch (\Exception $e) {
            Log::warning('Error clearing cache pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process order webhook.
     */
    protected function processOrderWebhook(string $topic, array $webhookData, Store $store): void
    {
        Log::info('Processing order webhook for analytics', [
            'topic' => $topic,
            'order_id' => $webhookData['id'] ?? null,
            'store_id' => $store->id,
        ]);

        // Additional processing can be added here
        // For example, updating real-time counters, triggering notifications, etc.
    }

    /**
     * Process product webhook.
     */
    protected function processProductWebhook(string $topic, array $webhookData, Store $store): void
    {
        Log::info('Processing product webhook for analytics', [
            'topic' => $topic,
            'product_id' => $webhookData['id'] ?? null,
            'store_id' => $store->id,
        ]);

        // Additional processing can be added here
    }

    /**
     * Process customer webhook.
     */
    protected function processCustomerWebhook(string $topic, array $webhookData, Store $store): void
    {
        Log::info('Processing customer webhook for analytics', [
            'topic' => $topic,
            'customer_id' => $webhookData['id'] ?? null,
            'store_id' => $store->id,
        ]);

        // Additional processing can be added here
    }

    /**
     * Process inventory webhook.
     */
    protected function processInventoryWebhook(string $topic, array $webhookData, Store $store): void
    {
        Log::info('Processing inventory webhook for analytics', [
            'topic' => $topic,
            'inventory_item_id' => $webhookData['inventory_item_id'] ?? $webhookData['id'] ?? null,
            'store_id' => $store->id,
        ]);

        // Additional processing can be added here
    }
}
