<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\GraphQLAnalyticsService;
use App\Services\ShopifyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    protected $shopifyService;

    public function __construct(GraphQLAnalyticsService $analyticsService, ShopifyService $shopifyService)
    {
        $this->analyticsService = $analyticsService;
        $this->shopifyService = $shopifyService;
    }

    /**
     * Get complete dashboard analytics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);
            $analytics = $this->analyticsService->getDashboardAnalytics($store);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load dashboard',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sales analytics for specific date range.
     */
    public function sales(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);

            $startDate = $request->has('start_date') ?
                Carbon::parse($request->start_date) :
                now()->subDays(30);

            $endDate = $request->has('end_date') ?
                Carbon::parse($request->end_date) :
                now();

            $analytics = $this->analyticsService->getSalesAnalytics($store, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load sales analytics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product analytics.
     */
    public function products(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);
            $analytics = $this->analyticsService->getProductAnalytics($store);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load product analytics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer analytics.
     */
    public function customers(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);
            $analytics = $this->analyticsService->getCustomerAnalytics($store);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load customer analytics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inventory analytics.
     */
    public function inventory(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);
            $analytics = $this->analyticsService->getInventoryAnalytics($store);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load inventory analytics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test GraphQL connection and capabilities.
     */
    public function testGraphQL(Request $request): JsonResponse
    {
        try {
            // dd($request->get('store_id'));
            $store = $this->getStore($request);
            $graphqlService = $this->shopifyService->graphql();

            $tests = [
                'orders' => $graphqlService->getOrders($store, ['first' => 1]),
                'customers' => $graphqlService->getCustomers($store, ['first' => 1]),
                'products' => $graphqlService->getProducts($store, ['first' => 1]),
            ];

            $results = [];
            foreach ($tests as $endpoint => $result) {
                $results[$endpoint] = [
                    'available' => ! isset($result['error']),
                    'status' => ! isset($result['error']) ? 'working' : 'error',
                    'message' => $result['error'] ?? 'GraphQL endpoint working',
                    'data_count' => isset($result[substr($endpoint, 0, -1)]) ? count($result[substr($endpoint, 0, -1)]) : 0,
                ];
            }

            return response()->json([
                'success' => true,
                'graphql_status' => $results,
                'store_domain' => $store->shop_domain,
                'tested_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'GraphQL test failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);
            $type = $request->input('type', 'sales'); // sales, products, customers, inventory
            $format = $request->input('format', 'json'); // json, csv

            switch ($type) {
                case 'sales':
                    $data = $this->analyticsService->getSalesAnalytics($store);
                    break;
                case 'products':
                    $data = $this->analyticsService->getProductAnalytics($store);
                    break;
                case 'customers':
                    $data = $this->analyticsService->getCustomerAnalytics($store);
                    break;
                case 'inventory':
                    $data = $this->analyticsService->getInventoryAnalytics($store);
                    break;
                default:
                    $data = $this->analyticsService->getDashboardAnalytics($store);
            }

            if ($format === 'csv') {
                // Convert to CSV format (implement based on your needs)
                return response()->json([
                    'success' => false,
                    'message' => 'CSV export not implemented yet',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'export_info' => [
                    'type' => $type,
                    'format' => $format,
                    'generated_at' => now()->toISOString(),
                    'store' => $store->shop_domain,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Export failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get store from request (implement based on your auth system).
     */
    protected function getStore(Request $request): Store
    {
        // Implement based on your authentication system
        // This could be from session, JWT token, etc.

        $storeId = $request->input('store_id') ?? session('store_id');

        if (! $storeId) {
            throw new \Exception('Store ID not provided');
        }

        $store = Store::find($storeId);

        if (! $store) {
            throw new \Exception('Store not found');
        }

        return $store;
    }

    public function debugAllOrders(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);
            $graphqlService = $this->shopifyService->graphql();

            // Get all orders without any date filter
            $query = '
            query getAllOrders($first: Int!) {
                orders(first: $first) {
                    edges {
                        node {
                            id
                            name
                            createdAt
                            processedAt
                            totalPriceSet {
                                shopMoney {
                                    amount
                                    currencyCode
                                }
                            }
                            displayFinancialStatus
                        }
                    }
                }
            }
        ';

            $response = $graphqlService->query($store, $query, ['first' => 100]);

            if (! $response || isset($response['errors'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'GraphQL query failed',
                    'details' => $response['errors'] ?? 'Unknown error',
                ]);
            }

            $orders = $response['data']['orders']['edges'] ?? [];

            // Extract order information
            $orderInfo = array_map(function ($edge) {
                $node = $edge['node'];

                return [
                    'id' => str_replace('gid://shopify/Order/', '', $node['id']),
                    'name' => $node['name'],
                    'created_at' => $node['createdAt'],
                    'amount' => $node['totalPriceSet']['shopMoney']['amount'],
                    'currency' => $node['totalPriceSet']['shopMoney']['currencyCode'],
                    'status' => $node['displayFinancialStatus'],
                ];
            }, $orders);

            // Group by date
            $ordersByDate = [];
            foreach ($orderInfo as $order) {
                $date = substr($order['created_at'], 0, 10); // Get YYYY-MM-DD
                if (! isset($ordersByDate[$date])) {
                    $ordersByDate[$date] = [];
                }
                $ordersByDate[$date][] = $order;
            }

            return response()->json([
                'success' => true,
                'total_orders' => count($orderInfo),
                'date_range' => [
                    'earliest' => count($orderInfo) > 0 ? min(array_column($orderInfo, 'created_at')) : null,
                    'latest' => count($orderInfo) > 0 ? max(array_column($orderInfo, 'created_at')) : null,
                ],
                'orders_by_date' => $ordersByDate,
                'sample_orders' => array_slice($orderInfo, 0, 10),
                'store_domain' => $store->shop_domain,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Debug failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Debug: Test specific date range query.
     */
    public function debugDateRange(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);
            $graphqlService = $this->shopifyService->graphql();

            $startDate = $request->input('start_date', '2024-01-01');
            $endDate = $request->input('end_date', '2024-12-31');

            // Test different date query formats
            $dateQueries = [
                'format1' => "created_at:>={$startDate} AND created_at:<={$endDate}",
                'format2' => "created_at:>='{$startDate}' AND created_at:<='{$endDate}'",
                'format3' => "created_at:>={$startDate}T00:00:00Z AND created_at:<={$endDate}T23:59:59Z",
                'format4' => "created_at:>={$startDate}T00:00:00.000Z AND created_at:<={$endDate}T23:59:59.999Z",
            ];

            $results = [];

            foreach ($dateQueries as $formatName => $dateQuery) {
                $query = '
                query getOrdersInRange($first: Int!, $query: String!) {
                    orders(first: $first, query: $query) {
                        edges {
                            node {
                                id
                                name
                                createdAt
                                totalPriceSet {
                                    shopMoney {
                                        amount
                                        currencyCode
                                    }
                                }
                            }
                        }
                    }
                }
            ';

                $response = $graphqlService->query($store, $query, [
                    'first' => 50,
                    'query' => $dateQuery,
                ]);

                $orderCount = 0;
                $orders = [];

                if ($response && isset($response['data']['orders']['edges'])) {
                    $orderCount = count($response['data']['orders']['edges']);
                    $orders = array_map(function ($edge) {
                        $node = $edge['node'];

                        return [
                            'name' => $node['name'],
                            'created_at' => $node['createdAt'],
                            'amount' => $node['totalPriceSet']['shopMoney']['amount'],
                        ];
                    }, $response['data']['orders']['edges']);
                }

                $results[$formatName] = [
                    'date_query' => $dateQuery,
                    'order_count' => $orderCount,
                    'orders' => $orders,
                    'errors' => $response['errors'] ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'requested_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'query_tests' => $results,
                'store_domain' => $store->shop_domain,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Date range debug failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function debugGraphQLRaw(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);

            // Test 1: Raw GraphQL query
            $url = "https://{$store->shop_domain}/admin/api/2023-07/graphql.json";

            $simpleQuery = '
            query {
                orders(first: 5) {
                    edges {
                        node {
                            id
                            name
                            createdAt
                        }
                    }
                }
            }
        ';

            $response1 = Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'query' => $simpleQuery,
            ]);

            // Test 2: Different GraphQL query format
            $queryWithVariables = '
            query getOrders($first: Int!) {
                orders(first: $first) {
                    edges {
                        node {
                            id
                            name
                            createdAt
                        }
                    }
                }
            }
        ';

            $response2 = Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'query' => $queryWithVariables,
                'variables' => ['first' => 5],
            ]);

            // Test 3: Try REST API for comparison
            $restUrl = "https://{$store->shop_domain}/admin/api/2023-07/orders.json?limit=5";

            $response3 = Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
            ])->get($restUrl);

            // Test 4: Test shop info to verify connection
            $shopUrl = "https://{$store->shop_domain}/admin/api/2023-07/shop.json";

            $response4 = Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
            ])->get($shopUrl);

            return response()->json([
                'success' => true,
                'store_domain' => $store->shop_domain,
                'tests' => [
                    'graphql_simple' => [
                        'status' => $response1->status(),
                        'successful' => $response1->successful(),
                        'data' => $response1->json(),
                        'query' => $simpleQuery,
                    ],
                    'graphql_with_variables' => [
                        'status' => $response2->status(),
                        'successful' => $response2->successful(),
                        'data' => $response2->json(),
                        'query' => $queryWithVariables,
                    ],
                    'rest_orders' => [
                        'status' => $response3->status(),
                        'successful' => $response3->successful(),
                        'data' => $response3->json(),
                    ],
                    'shop_info' => [
                        'status' => $response4->status(),
                        'successful' => $response4->successful(),
                        'data' => $response4->successful() ? 'Shop connection OK' : $response4->json(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Raw debug failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check what scopes the app actually has.
     */
    public function debugScopes(Request $request): JsonResponse
    {
        try {
            $store = $this->getStore($request);

            // Try to access different endpoints to see what permissions we have
            $tests = [
                'shop' => '/admin/api/2023-07/shop.json',
                'products' => '/admin/api/2023-07/products.json?limit=1',
                'orders' => '/admin/api/2023-07/orders.json?limit=1',
                'customers' => '/admin/api/2023-07/customers.json?limit=1',
                'inventory_levels' => '/admin/api/2023-07/inventory_levels.json',
                'locations' => '/admin/api/2023-07/locations.json',
            ];

            $results = [];

            foreach ($tests as $endpoint => $path) {
                $url = "https://{$store->shop_domain}{$path}";

                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $store->access_token,
                ])->get($url);

                $results[$endpoint] = [
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'error' => $response->successful() ? null : $response->json()['errors'] ?? 'Unknown error',
                    'has_protected_data_error' => $response->successful() ? false :
                        (isset($response->json()['errors']) && str_contains($response->json()['errors'], 'protected customer data')),
                ];
            }

            return response()->json([
                'success' => true,
                'store_domain' => $store->shop_domain,
                'scope_tests' => $results,
                'summary' => [
                    'working_endpoints' => array_keys(array_filter($results, function ($r) {
                        return $r['successful'];
                    })),
                    'protected_data_endpoints' => array_keys(array_filter($results, function ($r) {
                        return $r['has_protected_data_error'];
                    })),
                    'other_errors' => array_keys(array_filter($results, function ($r) {
                        return ! $r['successful'] && ! $r['has_protected_data_error'];
                    })),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Scope debug failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
