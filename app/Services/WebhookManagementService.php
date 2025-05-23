<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

class WebhookManagementService
{
    protected $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    /**
     * Setup webhooks for a store, checking for existing ones first.
     */
    public function setupWebhooks(Store $store): array
    {
        $webhookTopics = config('shopify.webhooks');
        $baseUrl = config('app.url');
        $registeredWebhooks = [];
        $errors = [];

        // First, get existing webhooks
        $existingWebhooks = $this->getExistingWebhooks($store);
        $existingTopics = collect($existingWebhooks)->pluck('topic')->toArray();

        Log::info('Found existing webhooks', [
            'store' => $store->shop_domain,
            'existing_topics' => $existingTopics,
        ]);

        foreach ($webhookTopics as $topic) {
            try {
                $address = $baseUrl.'/webhooks/'.str_replace('/', '-', $topic);

                // Check if webhook already exists for this topic and address
                $existingWebhook = collect($existingWebhooks)->first(function ($webhook) use ($topic, $address) {
                    return $webhook['topic'] === $topic && $webhook['address'] === $address;
                });

                if ($existingWebhook) {
                    Log::info('Webhook already exists', [
                        'store' => $store->shop_domain,
                        'topic' => $topic,
                        'webhook_id' => $existingWebhook['id'],
                    ]);
                    $registeredWebhooks[] = $topic;

                    continue;
                }

                // Check if there's a webhook for this topic with different address
                $conflictingWebhook = collect($existingWebhooks)->first(function ($webhook) use ($topic) {
                    return $webhook['topic'] === $topic;
                });

                if ($conflictingWebhook) {
                    // Update existing webhook to new address
                    $result = $this->updateWebhook($store, $conflictingWebhook['id'], $address);
                    if ($result) {
                        Log::info('Updated existing webhook', [
                            'store' => $store->shop_domain,
                            'topic' => $topic,
                            'old_address' => $conflictingWebhook['address'],
                            'new_address' => $address,
                        ]);
                        $registeredWebhooks[] = $topic;
                    } else {
                        $errors[] = "Failed to update webhook for topic: {$topic}";
                    }

                    continue;
                }

                // Register new webhook
                $result = $this->shopifyService->registerWebhook($store, $topic, $address);
                if ($result && isset($result['webhook'])) {
                    $registeredWebhooks[] = $topic;
                    Log::info('Registered new webhook', [
                        'store' => $store->shop_domain,
                        'topic' => $topic,
                        'webhook_id' => $result['webhook']['id'],
                    ]);
                } else {
                    $errors[] = "Failed to register webhook for topic: {$topic}";
                    Log::warning('Failed to register webhook', [
                        'store' => $store->shop_domain,
                        'topic' => $topic,
                        'result' => $result,
                    ]);
                }

            } catch (\Exception $e) {
                $errors[] = "Exception registering webhook for {$topic}: ".$e->getMessage();
                Log::error('Exception registering webhook', [
                    'store' => $store->shop_domain,
                    'topic' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'registered' => $registeredWebhooks,
            'errors' => $errors,
            'success' => count($registeredWebhooks) > 0,
        ];
    }

    /**
     * Get existing webhooks for a store.
     */
    public function getExistingWebhooks(Store $store): array
    {
        try {
            $response = $this->shopifyService->getWebhooks($store);

            return $response['webhooks'] ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching existing webhooks', [
                'store' => $store->shop_domain,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Update an existing webhook.
     */
    public function updateWebhook(Store $store, string $webhookId, string $newAddress): bool
    {
        try {
            $endpoint = "/admin/api/2023-07/webhooks/{$webhookId}.json";
            $payload = [
                'webhook' => [
                    'id' => $webhookId,
                    'address' => $newAddress,
                ],
            ];

            $result = $this->shopifyService->makeApiCall($store, 'PUT', $endpoint, $payload);

            return $result && isset($result['webhook']);

        } catch (\Exception $e) {
            Log::error('Error updating webhook', [
                'store' => $store->shop_domain,
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete all webhooks for a store (useful for cleanup).
     */
    public function deleteAllWebhooks(Store $store): array
    {
        $deleted = [];
        $errors = [];

        try {
            $existingWebhooks = $this->getExistingWebhooks($store);

            foreach ($existingWebhooks as $webhook) {
                try {
                    $endpoint = "/admin/api/2023-07/webhooks/{$webhook['id']}.json";
                    $result = $this->shopifyService->makeApiCall($store, 'DELETE', $endpoint);

                    if ($result !== null) {
                        $deleted[] = $webhook['topic'];
                        Log::info('Deleted webhook', [
                            'store' => $store->shop_domain,
                            'topic' => $webhook['topic'],
                            'webhook_id' => $webhook['id'],
                        ]);
                    } else {
                        $errors[] = "Failed to delete webhook: {$webhook['topic']}";
                    }

                } catch (\Exception $e) {
                    $errors[] = "Exception deleting webhook {$webhook['topic']}: ".$e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Exception fetching webhooks: '.$e->getMessage();
        }

        return [
            'deleted' => $deleted,
            'errors' => $errors,
            'success' => count($deleted) > 0,
        ];
    }
}
