<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected $graphqlService;

    public function __construct()
    {
        $this->graphqlService = new ShopifyGraphQLService;
    }

    /**
     * Get orders with automatic fallback to GraphQL.
     */
    public function getOrders(Store $store, array $params = []): ?array
    {
        // Try REST API first
        $endpoint = '/admin/api/2023-07/orders.json';
        $defaultParams = [
            'limit' => 50,
            'status' => 'any',
        ];

        $params = array_merge($defaultParams, $params);
        $result = $this->makeApiCall($store, 'GET', $endpoint, $params);

        // If REST fails (protected customer data), use GraphQL
        if (! $result) {
            Log::info('REST orders failed, using GraphQL fallback', [
                'store' => $store->shop_domain,
            ]);

            $graphqlResult = $this->graphqlService->getOrders($store, [
                'first' => $params['limit'],
            ]);

            if ($graphqlResult && ! isset($graphqlResult['error'])) {
                return [
                    'orders' => $graphqlResult['orders'],
                    'source' => 'graphql',
                ];
            }
        }

        return $result;
    }

    /**
     * Get customers with GraphQL fallback.
     */
    public function getCustomers(Store $store, array $params = []): ?array
    {
        // Try REST API first
        $endpoint = '/admin/api/2023-07/customers.json';
        $defaultParams = [
            'limit' => 50,
        ];

        $params = array_merge($defaultParams, $params);
        $result = $this->makeApiCall($store, 'GET', $endpoint, $params);

        // If REST fails, use GraphQL
        if (! $result) {
            Log::info('REST customers failed, using GraphQL fallback', [
                'store' => $store->shop_domain,
            ]);

            $graphqlResult = $this->graphqlService->getCustomers($store, [
                'first' => $params['limit'],
            ]);

            if ($graphqlResult && ! isset($graphqlResult['error'])) {
                return [
                    'customers' => $graphqlResult['customers'],
                    'source' => 'graphql',
                ];
            }
        }

        return $result;
    }

    /**
     * Get GraphQL service instance.
     */
    public function graphql(): ShopifyGraphQLService
    {
        return $this->graphqlService;
    }

    // Keep all your existing methods...
    public function makeApiCall(Store $store, string $method, string $endpoint, array $params = []): ?array
    {
        // Your existing implementation
    }

    public function getShopDetails(Store $store): ?array
    {
        return $this->makeApiCall($store, 'GET', '/admin/api/2023-07/shop.json');
    }

    public function getProducts(Store $store, int $limit = 50, int $page = 1): ?array
    {
        $endpoint = '/admin/api/2023-07/products.json';
        $params = [
            'limit' => $limit,
            'page' => $page,
        ];

        return $this->makeApiCall($store, 'GET', $endpoint, $params);
    }
}
