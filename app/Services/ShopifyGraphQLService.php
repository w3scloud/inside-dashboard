<?php

namespace App\Services;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyGraphQLService
{
    /**
     * Execute a GraphQL query against Shopify's GraphQL API.
     */
    public function query(Store $store, string $query, array $variables = []): ?array
    {
        try {
            $url = "https://{$store->shop_domain}/admin/api/2023-07/graphql.json";

            $payload = [
                'query' => $query,
                'variables' => $variables,
            ];

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if (! $response->successful()) {
                Log::error('GraphQL HTTP request failed', [
                    'store' => $store->shop_domain,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // Check for GraphQL errors
            if (isset($data['errors'])) {
                Log::error('GraphQL query errors', [
                    'store' => $store->shop_domain,
                    'errors' => $data['errors'],
                    'query' => $query,
                ]);

                return null;
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('GraphQL query exception', [
                'store' => $store->shop_domain,
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }

    /**
     * Get orders using GraphQL with pagination support.
     */
    public function getOrders(Store $store, array $options = []): array
    {
        $first = $options['first'] ?? 50;
        $after = $options['after'] ?? null;
        $query = $options['query'] ?? null;

        // REMOVED PROTECTED FIELDS: email, shippingAddress fields
        $graphqlQuery = '
            query getOrders($first: Int!, $after: String, $query: String) {
                orders(first: $first, after: $after, query: $query) {
                    edges {
                        cursor
                        node {
                            id
                            name
                            createdAt
                            processedAt
                            updatedAt
                            totalPriceSet {
                                shopMoney {
                                    amount
                                    currencyCode
                                }
                            }
                            subtotalPriceSet {
                                shopMoney {
                                    amount
                                    currencyCode
                                }
                            }
                            totalTaxSet {
                                shopMoney {
                                    amount
                                    currencyCode
                                }
                            }
                            displayFinancialStatus
                            displayFulfillmentStatus
                            tags
                            note
                            lineItems(first: 100) {
                                edges {
                                    node {
                                        id
                                        title
                                        quantity
                                        variant {
                                            id
                                            title
                                            price
                                            sku
                                            inventoryQuantity
                                            product {
                                                id
                                                title
                                                handle
                                                vendor
                                                productType
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                        startCursor
                        endCursor
                    }
                }
            }
        ';

        $variables = [
            'first' => $first,
        ];

        if ($after) {
            $variables['after'] = $after;
        }

        if ($query) {
            $variables['query'] = $query;
        }

        $response = $this->query($store, $graphqlQuery, $variables);

        if (! $response || isset($response['errors'])) {
            return [
                'orders' => [],
                'pageInfo' => null,
                'error' => 'Failed to fetch orders',
            ];
        }

        return [
            'orders' => $this->transformOrdersResponse($response['data']['orders']['edges']),
            'pageInfo' => $response['data']['orders']['pageInfo'],
            'totalCost' => $response['extensions']['cost'] ?? null,
        ];
    }

    /**
     * Get orders within a specific date range.
     */
    public function getOrdersByDateRange(Store $store, Carbon $startDate, Carbon $endDate, int $limit = 250): array
    {
        // Use the working date format from your debug test
        $dateQuery = sprintf(
            'created_at:>=%s AND created_at:<=%s',
            $startDate->format('Y-m-d'),  // This format worked in your debug
            $endDate->format('Y-m-d')
        );

        Log::info('ShopifyGraphQLService getOrdersByDateRange', [
            'store' => $store->shop_domain,
            'date_query' => $dateQuery,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        $result = $this->getOrders($store, [
            'first' => $limit,
            'query' => $dateQuery,
        ]);

        Log::info('ShopifyGraphQLService getOrdersByDateRange result', [
            'store' => $store->shop_domain,
            'orders_found' => isset($result['orders']) ? count($result['orders']) : 0,
            'has_error' => isset($result['error']),
        ]);

        return $result;
    }

    /**
     * Get customer data using GraphQL.
     */
    public function getCustomers(Store $store, array $options = []): array
    {
        $first = $options['first'] ?? 50;
        $after = $options['after'] ?? null;
        $query = $options['query'] ?? null;

        // UPDATED QUERY - Only essential analytics data, no personal info
        $graphqlQuery = '
            query getCustomers($first: Int!, $after: String, $query: String) {
                customers(first: $first, after: $after, query: $query) {
                    edges {
                        cursor
                        node {
                            id
                            createdAt
                            updatedAt
                            emailMarketingConsent {
                                marketingState
                            }
                            numberOfOrders
                            amountSpent {
                                amount
                                currencyCode
                            }
                            tags
                        }
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                        startCursor
                        endCursor
                    }
                }
            }
        ';

        $variables = [
            'first' => $first,
        ];

        if ($after) {
            $variables['after'] = $after;
        }

        if ($query) {
            $variables['query'] = $query;
        }

        $response = $this->query($store, $graphqlQuery, $variables);

        if (! $response || ! isset($response['data']['customers'])) {
            Log::warning('Failed to fetch customers', [
                'store' => $store->shop_domain,
                'has_response' => (bool) $response,
                'has_errors' => isset($response['errors']) ? $response['errors'] : 'no errors',
            ]);

            return [
                'customers' => [],
                'pageInfo' => null,
                'error' => 'Failed to fetch customers',
            ];
        }

        return [
            'customers' => $this->transformCustomersResponse($response['data']['customers']['edges']),
            'pageInfo' => $response['data']['customers']['pageInfo'],
        ];
    }

    /**
     * Get product data using GraphQL.
     */
    public function getProducts(Store $store, array $options = []): array
    {
        $first = $options['first'] ?? 50;
        $after = $options['after'] ?? null;
        $query = $options['query'] ?? null;

        $graphqlQuery = '
            query getProducts($first: Int!, $after: String, $query: String) {
                products(first: $first, after: $after, query: $query) {
                    edges {
                        cursor
                        node {
                            id
                            title
                            handle
                            description
                            vendor
                            productType
                            createdAt
                            updatedAt
                            status
                            tags
                            totalInventory
                            images(first: 10) {
                                edges {
                                    node {
                                        id
                                        url
                                        altText
                                    }
                                }
                            }
                            variants(first: 100) {
                                edges {
                                    node {
                                        id
                                        title
                                        sku
                                        price
                                        compareAtPrice
                                        inventoryQuantity
                                        weight
                                        weightUnit
                                        requiresShipping
                                        inventoryManagement
                                        inventoryPolicy
                                    }
                                }
                            }
                            seo {
                                title
                                description
                            }
                        }
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                        startCursor
                        endCursor
                    }
                }
            }
        ';

        $variables = [
            'first' => $first,
        ];

        if ($after) {
            $variables['after'] = $after;
        }

        if ($query) {
            $variables['query'] = $query;
        }

        $response = $this->query($store, $graphqlQuery, $variables);

        if (! $response || ! isset($response['data']['products'])) {
            return [
                'products' => [],
                'pageInfo' => null,
                'error' => 'Failed to fetch products',
            ];
        }

        return [
            'products' => $this->transformProductsResponse($response['data']['products']['edges']),
            'pageInfo' => $response['data']['products']['pageInfo'],
        ];
    }

    /**
     * Transform orders response to consistent format.
     */
    protected function transformOrdersResponse(array $orderEdges): array
    {
        $orders = [];

        foreach ($orderEdges as $edge) {
            $node = $edge['node'];

            $order = [
                'id' => $this->extractId($node['id']),
                'name' => $node['name'],
                // 'email' => $node['email'] ?? null,  // REMOVED - protected field
                'created_at' => $node['createdAt'],
                'processed_at' => $node['processedAt'] ?? null,
                'updated_at' => $node['updatedAt'] ?? null,
                'total_price' => floatval($node['totalPriceSet']['shopMoney']['amount']),
                'subtotal_price' => floatval($node['subtotalPriceSet']['shopMoney']['amount'] ?? 0),
                'total_tax' => floatval($node['totalTaxSet']['shopMoney']['amount'] ?? 0),
                'currency' => $node['totalPriceSet']['shopMoney']['currencyCode'],
                'financial_status' => strtolower(str_replace('_', '_', $node['displayFinancialStatus'])),
                'fulfillment_status' => $node['displayFulfillmentStatus'] ? strtolower(str_replace('_', '_', $node['displayFulfillmentStatus'])) : null,
                'tags' => $node['tags'],
                'note' => $node['note'],
                'line_items' => $this->transformLineItems($node['lineItems']['edges']),
                // 'shipping_address' => null, // REMOVED - protected fields
                'cursor' => $edge['cursor'],
            ];

            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * Transform customers response to consistent format.
     */
    private function transformCustomersResponse(array $edges): array
    {
        $customers = [];

        foreach ($edges as $edge) {
            $customer = $edge['node'];

            // Map to expected format with only analytics data
            $customers[] = [
                'id' => $customer['id'],
                'created_at' => $customer['createdAt'],
                'updated_at' => $customer['updatedAt'],

                // Analytics fields with new API structure
                'accepts_marketing' => ($customer['emailMarketingConsent']['marketingState'] ?? '') === 'SUBSCRIBED',
                'orders_count' => $customer['numberOfOrders'] ?? 0,
                'total_spent' => $customer['amountSpent']['amount'] ?? 0,
                'currency' => $customer['amountSpent']['currencyCode'] ?? 'USD',
                'tags' => $customer['tags'] ?? [],

                'cursor' => $edge['cursor'],
            ];
        }

        return $customers;
    }

    /**
     * Transform products response to consistent format.
     */
    protected function transformProductsResponse(array $productEdges): array
    {
        $products = [];

        foreach ($productEdges as $edge) {
            $node = $edge['node'];

            $product = [
                'id' => $this->extractId($node['id']),
                'title' => $node['title'],
                'handle' => $node['handle'],
                'description' => $node['description'],
                'vendor' => $node['vendor'],
                'product_type' => $node['productType'],
                'created_at' => $node['createdAt'],
                'updated_at' => $node['updatedAt'],
                'status' => strtolower($node['status']),
                'tags' => $node['tags'],
                'total_inventory' => $node['totalInventory'],
                'images' => $this->transformImages($node['images']['edges']),
                'variants' => $this->transformVariants($node['variants']['edges']),
                'seo' => [
                    'title' => $node['seo']['title'] ?? null,
                    'description' => $node['seo']['description'] ?? null,
                ],
                'cursor' => $edge['cursor'],
            ];

            $products[] = $product;
        }

        return $products;
    }

    /**
     * Transform line items.
     */
    protected function transformLineItems(array $lineItemEdges): array
    {
        $lineItems = [];

        foreach ($lineItemEdges as $edge) {
            $node = $edge['node'];
            $variant = $node['variant'] ?? null;
            $product = $variant['product'] ?? null;

            $lineItems[] = [
                'id' => $this->extractId($node['id']),
                'title' => $node['title'],
                'quantity' => $node['quantity'],
                'price' => floatval($variant['price'] ?? 0),
                'total' => floatval($variant['price'] ?? 0) * $node['quantity'],
                'variant_id' => $variant ? $this->extractId($variant['id']) : null,
                'variant_title' => $variant['title'] ?? null,
                'variant_sku' => $variant['sku'] ?? null,
                'product_id' => $product ? $this->extractId($product['id']) : null,
                'product_title' => $product['title'] ?? null,
                'product_handle' => $product['handle'] ?? null,
                'product_vendor' => $product['vendor'] ?? null,
                'product_type' => $product['productType'] ?? null,
            ];
        }

        return $lineItems;
    }

    /**
     * Transform variants.
     */
    protected function transformVariants(array $variantEdges): array
    {
        $variants = [];

        foreach ($variantEdges as $edge) {
            $node = $edge['node'];

            $variants[] = [
                'id' => $this->extractId($node['id']),
                'title' => $node['title'],
                'sku' => $node['sku'],
                'price' => floatval($node['price']),
                'compare_at_price' => $node['compareAtPrice'] ? floatval($node['compareAtPrice']) : null,
                'inventory_quantity' => $node['inventoryQuantity'],
                'weight' => $node['weight'],
                'weight_unit' => $node['weightUnit'],
                'requires_shipping' => $node['requiresShipping'],
                'inventory_management' => $node['inventoryManagement'],
                'inventory_policy' => $node['inventoryPolicy'],
            ];
        }

        return $variants;
    }

    /**
     * Transform images.
     */
    protected function transformImages(array $imageEdges): array
    {
        $images = [];

        foreach ($imageEdges as $edge) {
            $node = $edge['node'];

            $images[] = [
                'id' => $this->extractId($node['id']),
                'url' => $node['url'],
                'alt_text' => $node['altText'],
            ];
        }

        return $images;
    }

    /**
     * Transform address.
     */
    protected function transformAddress(?array $address): ?array
    {
        if (! $address) {
            return null;
        }

        return [
            'first_name' => $address['firstName'] ?? null,
            'last_name' => $address['lastName'] ?? null,
            'address1' => $address['address1'] ?? null,
            'address2' => $address['address2'] ?? null,
            'city' => $address['city'] ?? null,
            'province' => $address['province'] ?? null,
            'country' => $address['country'] ?? null,
            'zip' => $address['zip'] ?? null,
        ];
    }

    /**
     * Extract numeric ID from GraphQL global ID.
     */
    protected function extractId(string $globalId): string
    {
        $parts = explode('/', $globalId);

        return end($parts);
    }
}
