<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $shopifyService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    /**
     * Handle Shopify webhook requests.
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, string $topic)
    {
        // Verify webhook
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();

        if (! $hmac || ! $this->shopifyService->verifyWebhook($hmac, $data)) {
            Log::warning('Invalid webhook HMAC', [
                'topic' => $topic,
                'ip' => $request->ip(),
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

        // Process webhook based on topic
        try {
            switch ($topic) {
                case 'app/uninstalled':
                    return $this->handleAppUninstalled($webhookData);

                case 'shop/update':
                    return $this->handleShopUpdate($webhookData);

                case 'products/create':
                case 'products/update':
                case 'products/delete':
                    return $this->handleProductsWebhook($topic, $webhookData);

                case 'orders/create':
                case 'orders/updated':
                case 'orders/cancelled':
                    return $this->handleOrdersWebhook($topic, $webhookData);

                case 'customers/create':
                case 'customers/update':
                case 'customers/delete':
                    return $this->handleCustomersWebhook($topic, $webhookData);

                case 'inventory_levels/connect':
                case 'inventory_levels/update':
                case 'inventory_items/update':
                    return $this->handleInventoryWebhook($topic, $webhookData);

                default:
                    Log::info('Unhandled webhook topic', [
                        'topic' => $topic,
                    ]);

                    return response('Not Implemented', 501);
            }
        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Handle app uninstalled webhook.
     *
     * @return \Illuminate\Http\Response
     */
    private function handleAppUninstalled(array $webhookData)
    {
        $domain = $webhookData['domain'] ?? $webhookData['myshopify_domain'] ?? null;

        if (! $domain) {
            Log::warning('Missing domain in app/uninstalled webhook', [
                'data' => $webhookData,
            ]);

            return response('Bad Request', 400);
        }

        // Find and mark store as inactive
        $store = Store::where('shop_domain', $domain)->first();

        if ($store) {
            $store->markAsInactive();

            Log::info('Store marked as inactive due to app uninstallation', [
                'store' => $domain,
            ]);
        } else {
            Log::warning('Store not found for app/uninstalled webhook', [
                'domain' => $domain,
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Handle shop update webhook.
     *
     * @return \Illuminate\Http\Response
     */
    private function handleShopUpdate(array $webhookData)
    {
        $domain = $webhookData['domain'] ?? $webhookData['myshopify_domain'] ?? null;

        if (! $domain) {
            Log::warning('Missing domain in shop/update webhook', [
                'data' => $webhookData,
            ]);

            return response('Bad Request', 400);
        }

        // Find and update store details
        $store = Store::where('shop_domain', $domain)->first();

        if ($store) {
            $store->updateFromShopify($webhookData);

            Log::info('Store details updated from webhook', [
                'store' => $domain,
            ]);
        } else {
            Log::warning('Store not found for shop/update webhook', [
                'domain' => $domain,
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Handle products webhooks.
     *
     * @return \Illuminate\Http\Response
     */
    private function handleProductsWebhook(string $topic, array $webhookData)
    {
        // In a real application, you might update a products cache or trigger related tasks
        Log::info('Received products webhook', [
            'topic' => $topic,
            'product_id' => $webhookData['id'] ?? null,
        ]);

        return response('OK', 200);
    }

    /**
     * Handle orders webhooks.
     *
     * @return \Illuminate\Http\Response
     */
    private function handleOrdersWebhook(string $topic, array $webhookData)
    {
        // In a real application, you might update an orders cache or trigger related tasks
        Log::info('Received orders webhook', [
            'topic' => $topic,
            'order_id' => $webhookData['id'] ?? null,
        ]);

        return response('OK', 200);
    }

    /**
     * Handle customers webhooks.
     *
     * @return \Illuminate\Http\Response
     */
    private function handleCustomersWebhook(string $topic, array $webhookData)
    {
        // In a real application, you might update a customers cache or trigger related tasks
        Log::info('Received customers webhook', [
            'topic' => $topic,
            'customer_id' => $webhookData['id'] ?? null,
        ]);

        return response('OK', 200);
    }

    /**
     * Handle inventory webhooks.
     *
     * @return \Illuminate\Http\Response
     */
    private function handleInventoryWebhook(string $topic, array $webhookData)
    {
        // In a real application, you might update an inventory cache or trigger related tasks
        Log::info('Received inventory webhook', [
            'topic' => $topic,
            'inventory_item_id' => $webhookData['inventory_item_id'] ?? $webhookData['id'] ?? null,
        ]);

        return response('OK', 200);
    }
}
