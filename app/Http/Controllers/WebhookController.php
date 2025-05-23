<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\RealTimeAnalyticsService;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $shopifyService;

    protected $realTimeAnalyticsService;

    public function __construct(
        ShopifyService $shopifyService,
        RealTimeAnalyticsService $realTimeAnalyticsService
    ) {
        $this->shopifyService = $shopifyService;
        $this->realTimeAnalyticsService = $realTimeAnalyticsService;
    }

    /**
     * Handle Shopify webhook requests.
     */
    public function handle(Request $request, string $topic): \Illuminate\Http\Response
    {
        // Convert URL format back to webhook topic format
        $topic = str_replace('-', '/', $topic);

        // Verify webhook
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();

        if (! $hmac || ! $this->shopifyService->verifyWebhook($hmac, $data)) {
            Log::warning('Invalid webhook HMAC', [
                'topic' => $topic,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return response('Unauthorized', 401);
        }

        // Parse webhook data
        $webhookData = json_decode($data, true);

        if (! $webhookData) {
            Log::warning('Invalid webhook data', [
                'topic' => $topic,
                'data' => $data,
            ]);

            return response('Bad Request', 400);
        }

        // Find store by domain
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        if (! $shopDomain) {
            Log::warning('Missing shop domain in webhook', [
                'topic' => $topic,
                'headers' => $request->headers->all(),
            ]);

            return response('Bad Request', 400);
        }

        $store = Store::where('shop_domain', $shopDomain)->first();
        if (! $store) {
            Log::warning('Store not found for webhook', [
                'topic' => $topic,
                'shop_domain' => $shopDomain,
            ]);

            return response('Not Found', 404);
        }

        // Process webhook based on topic
        try {
            $this->processWebhook($topic, $webhookData, $store);

            // Log successful webhook processing
            $this->logWebhookEvent($store, $topic, $webhookData);

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Exception processing webhook', [
                'topic' => $topic,
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Process webhook based on topic.
     */
    protected function processWebhook(string $topic, array $webhookData, Store $store): void
    {
        switch ($topic) {
            case 'app/uninstalled':
                $this->handleAppUninstalled($webhookData, $store);
                break;

            case 'shop/update':
                $this->handleShopUpdate($webhookData, $store);
                break;

            case 'orders/create':
            case 'orders/updated':
                $this->handleOrdersWebhook($topic, $webhookData, $store);
                break;

            case 'orders/cancelled':
                $this->handleOrderCancelled($webhookData, $store);
                break;

            case 'products/create':
            case 'products/update':
            case 'products/delete':
                $this->handleProductsWebhook($topic, $webhookData, $store);
                break;

            case 'customers/create':
            case 'customers/update':
            case 'customers/delete':
                $this->handleCustomersWebhook($topic, $webhookData, $store);
                break;

            case 'inventory_levels/connect':
            case 'inventory_levels/update':
            case 'inventory_items/update':
                $this->handleInventoryWebhook($topic, $webhookData, $store);
                break;

            default:
                Log::info('Unhandled webhook topic', [
                    'topic' => $topic,
                    'store_id' => $store->id,
                ]);
        }
    }

    /**
     * Handle app uninstalled webhook.
     */
    protected function handleAppUninstalled(array $webhookData, Store $store): void
    {
        Log::info('Processing app uninstallation', [
            'store_id' => $store->id,
            'shop_domain' => $store->shop_domain,
        ]);

        // Mark store as inactive
        $store->markAsInactive();

        // Log the uninstallation event
        DB::table('analytics_events')->insert([
            'store_id' => $store->id,
            'event_type' => 'app_uninstalled',
            'entity_id' => $store->shop_domain,
            'event_data' => json_encode($webhookData),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Handle shop update webhook.
     */
    protected function handleShopUpdate(array $webhookData, Store $store): void
    {
        Log::info('Processing shop update', [
            'store_id' => $store->id,
            'shop_domain' => $store->shop_domain,
        ]);

        // Update store details
        $store->updateFromShopify($webhookData);

        // Log the update event
        $this->logAnalyticsEvent($store, 'shop_updated', $store->shop_domain, $webhookData);
    }

    /**
     * Handle orders webhooks.
     */
    protected function handleOrdersWebhook(string $topic, array $webhookData, Store $store): void
    {
        Log::info('Processing orders webhook', [
            'topic' => $topic,
            'store_id' => $store->id,
            'order_id' => $webhookData['id'] ?? null,
        ]);

        // Process with real-time analytics
        $this->realTimeAnalyticsService->processWebhookData($topic, $webhookData, $store);

        // Log the event
        $this->logAnalyticsEvent(
            $store,
            $topic === 'orders/create' ? 'order_created' : 'order_updated',
            $webhookData['id'] ?? '',
            $webhookData,
            (float) ($webhookData['total_price'] ?? 0)
        );
    }

    /**
     * Handle order cancelled webhook.
     */
    protected function handleOrderCancelled(array $webhookData, Store $store): void
    {
        Log::info('Processing order cancellation', [
            'store_id' => $store->id,
            'order_id' => $webhookData['id'] ?? null,
        ]);

        // TODO: Implement cancellation logic (subtract from analytics)
        // This would require more complex logic to reverse the order impact

        $this->logAnalyticsEvent(
            $store,
            'order_cancelled',
            $webhookData['id'] ?? '',
            $webhookData,
            -(float) ($webhookData['total_price'] ?? 0) // Negative value
        );
    }

    /**
     * Handle products webhooks.
     */
    protected function handleProductsWebhook(string $topic, array $webhookData, Store $store): void
    {
        Log::info('Processing products webhook', [
            'topic' => $topic,
            'store_id' => $store->id,
            'product_id' => $webhookData['id'] ?? null,
        ]);

        // Process with real-time analytics
        $this->realTimeAnalyticsService->processWebhookData($topic, $webhookData, $store);

        // Log the event
        $eventType = match ($topic) {
            'products/create' => 'product_created',
            'products/update' => 'product_updated',
            'products/delete' => 'product_deleted',
            default => 'product_changed',
        };

        $this->logAnalyticsEvent($store, $eventType, $webhookData['id'] ?? '', $webhookData);
    }

    /**
     * Handle customers webhooks.
     */
    protected function handleCustomersWebhook(string $topic, array $webhookData, Store $store): void
    {
        Log::info('Processing customers webhook', [
            'topic' => $topic,
            'store_id' => $store->id,
            'customer_id' => $webhookData['id'] ?? null,
        ]);

        // Process with real-time analytics
        $this->realTimeAnalyticsService->processWebhookData($topic, $webhookData, $store);

        // Log the event
        $eventType = match ($topic) {
            'customers/create' => 'customer_created',
            'customers/update' => 'customer_updated',
            'customers/delete' => 'customer_deleted',
            default => 'customer_changed',
        };

        $this->logAnalyticsEvent(
            $store,
            $eventType,
            $webhookData['id'] ?? '',
            $webhookData,
            (float) ($webhookData['total_spent'] ?? 0)
        );
    }

    /**
     * Handle inventory webhooks.
     */
    protected function handleInventoryWebhook(string $topic, array $webhookData, Store $store): void
    {
        Log::info('Processing inventory webhook', [
            'topic' => $topic,
            'store_id' => $store->id,
            'inventory_item_id' => $webhookData['inventory_item_id'] ?? $webhookData['id'] ?? null,
        ]);

        // Process with real-time analytics
        $this->realTimeAnalyticsService->processWebhookData($topic, $webhookData, $store);

        // Log the event
        $this->logAnalyticsEvent(
            $store,
            'inventory_updated',
            $webhookData['inventory_item_id'] ?? $webhookData['id'] ?? '',
            $webhookData
        );
    }

    /**
     * Log analytics event to database.
     */
    protected function logAnalyticsEvent(
        Store $store,
        string $eventType,
        string $entityId,
        array $eventData,
        ?float $value = null
    ): void {
        try {
            DB::table('analytics_events')->insert([
                'store_id' => $store->id,
                'event_type' => $eventType,
                'entity_id' => $entityId,
                'event_data' => json_encode($eventData),
                'value' => $value,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log analytics event', [
                'store_id' => $store->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log webhook event for debugging and monitoring.
     */
    protected function logWebhookEvent(Store $store, string $topic, array $webhookData): void
    {
        Log::info('Webhook processed successfully', [
            'store_id' => $store->id,
            'shop_domain' => $store->shop_domain,
            'topic' => $topic,
            'entity_id' => $webhookData['id'] ?? 'unknown',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
