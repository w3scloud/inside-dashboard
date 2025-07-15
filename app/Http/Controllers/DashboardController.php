<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Services\GraphQLAnalyticsService;
use App\Services\ShopifyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected $analyticsService;

    protected $shopifyService;

    public function __construct(GraphQLAnalyticsService $analyticsService, ShopifyService $shopifyService)
    {
        $this->analyticsService = $analyticsService;
        $this->shopifyService = $shopifyService;
    }

    /**
     * Display a listing of the dashboards.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Dashboard/Index', [
                'dashboards' => [],
                'store' => null,
                'error' => 'No active store found. Please connect your Shopify store.',
            ]);
        }

        $dashboards = $store->dashboards()
            ->orderBy('is_default', 'desc')
            ->orderBy('last_viewed_at', 'desc')
            ->get();

        // If no dashboards exist, create a default one
        if ($dashboards->isEmpty()) {
            $defaultDashboard = $this->createDefaultDashboard($store);
            $dashboards = collect([$defaultDashboard]);
        }

        // Redirect to the default dashboard
        $defaultDashboard = $dashboards->where('is_default', true)->first()
            ?? $dashboards->first();

        return redirect()->route('dashboard.show', $defaultDashboard->id);
    }

    /**
     * Show the form for creating a new dashboard.
     */
    public function create(): Response
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('dashboard')
                ->with('error', 'No active store found.');
        }

        return Inertia::render('Dashboard/Create', [
            'store' => $store,
        ]);
    }

    /**
     * Store a newly created dashboard in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('dashboard')
                ->with('error', 'No active store found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
        ]);

        $dashboard = new Dashboard([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
            'layout' => $this->getDefaultLayout(),
            'settings' => [
                'refresh_interval' => 300, // 5 minutes
                'date_range' => '30days',
                'timezone' => config('app.timezone'),
            ],
        ]);

        $store->dashboards()->save($dashboard);

        // If marked as default, update other dashboards
        if ($dashboard->is_default) {
            $dashboard->markAsDefault();
        }

        return redirect()->route('dashboard.show', $dashboard->id)
            ->with('success', 'Dashboard created successfully.');
    }

    /**
     * Display the specified dashboard.
     */
    public function show($id): Response
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('dashboard')
                ->with('error', 'No active store found.');
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        // Update last viewed timestamp
        $dashboard->touch('last_viewed_at');

        return Inertia::render('Dashboard/Show', [
            'dashboard' => $dashboard,
            'store' => $store,
            'availableWidgets' => $this->getAvailableWidgets(),
        ]);
    }

    /**
     * Show the form for editing the specified dashboard.
     */
    public function edit($id): Response
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('dashboard')
                ->with('error', 'No active store found.');
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        return Inertia::render('Dashboard/Edit', [
            'dashboard' => $dashboard,
            'store' => $store,
        ]);
    }

    /**
     * Update the specified dashboard in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('dashboard')
                ->with('error', 'No active store found.');
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $dashboard->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $dashboard->description,
            'is_default' => $validated['is_default'] ?? $dashboard->is_default,
            'settings' => $validated['settings'] ?? $dashboard->settings,
        ]);

        // If marked as default, update other dashboards
        if ($dashboard->is_default) {
            $dashboard->markAsDefault();
        }

        return redirect()->route('dashboard.show', $dashboard->id)
            ->with('success', 'Dashboard updated successfully.');
    }

    /**
     * Remove the specified dashboard from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('dashboard')
                ->with('error', 'No active store found.');
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        // Check if this is the only dashboard
        if ($store->dashboards()->count() <= 1) {
            return redirect()->route('dashboard.show', $dashboard->id)
                ->with('error', 'Cannot delete the only dashboard.');
        }

        // If this is the default dashboard, set another one as default
        if ($dashboard->is_default) {
            $newDefault = $store->dashboards()
                ->where('id', '!=', $dashboard->id)
                ->orderBy('last_viewed_at', 'desc')
                ->first();

            if ($newDefault) {
                $newDefault->markAsDefault();
            }
        }

        $dashboard->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Dashboard deleted successfully.');
    }

    /**
     * Fetch data for the dashboard using GraphQL Analytics Service.
     */
    public function fetchData($id, Request $request): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No active store found.',
            ], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        // Get date range from request
        $startDate = $request->input('start_date') ?
            Carbon::parse($request->input('start_date')) :
            now()->subDays(30);
        $endDate = $request->input('end_date') ?
            Carbon::parse($request->input('end_date')) :
            now();

        Log::info('DashboardController fetchData called', [
            'store_id' => $store->id,
            'dashboard_id' => $id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        try {
            // Get all analytics data using your working GraphQLAnalyticsService
            $salesAnalytics = $this->analyticsService->getSalesAnalytics($store, $startDate, $endDate);
            $productAnalytics = $this->analyticsService->getProductAnalytics($store);
            $inventoryAnalytics = $this->analyticsService->getInventoryAnalytics($store);

            Log::info('Analytics data retrieved in dashboard', [
                'sales_total' => $salesAnalytics['summary']['total_sales'] ?? 0,
                'sales_orders' => $salesAnalytics['summary']['total_orders'] ?? 0,
                'products_count' => isset($productAnalytics['summary']) ? $productAnalytics['summary']['total_products'] : 0,
            ]);

            // Process widgets data
            $widgetData = [];

            foreach ($dashboard->layout as $widget) {
                $widgetId = $widget['id'];
                $widgetType = $widget['type'] ?? 'metric';
                $dataSource = $widget['data_source'] ?? 'sales';

                try {
                    switch ($dataSource) {
                        case 'sales':
                            $widgetData[$widgetId] = $this->processSalesWidget($widget, $salesAnalytics);
                            break;

                        case 'products':
                            $widgetData[$widgetId] = $this->processProductWidget($widget, $productAnalytics);
                            break;

                        case 'inventory':
                            $widgetData[$widgetId] = $this->processInventoryWidget($widget, $inventoryAnalytics);
                            break;

                        default:
                            $widgetData[$widgetId] = $this->processDefaultWidget($widget, $salesAnalytics);
                            break;
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing widget', [
                        'widget_id' => $widgetId,
                        'widget_type' => $widgetType,
                        'data_source' => $dataSource,
                        'error' => $e->getMessage(),
                    ]);

                    // Provide fallback data for failed widgets
                    $widgetData[$widgetId] = [
                        'error' => true,
                        'message' => 'Unable to load widget data',
                        'value' => 0,
                    ];
                }
            }

            // Update last viewed
            $dashboard->touch('last_viewed_at');

            return response()->json([
                'success' => true,
                'data' => $widgetData,
                'summary' => [
                    'total_sales' => $salesAnalytics['summary']['total_sales'] ?? 0,
                    'total_orders' => $salesAnalytics['summary']['total_orders'] ?? 0,
                    'average_order_value' => $salesAnalytics['summary']['average_order_value'] ?? 0,
                    'currency' => $salesAnalytics['summary']['currency'] ?? 'USD',
                    'growth_rate' => $salesAnalytics['summary']['growth_rate'] ?? 0,
                ],
                'charts' => [
                    'daily_sales' => $salesAnalytics['daily_sales'] ?? [],
                    'hourly_sales' => $salesAnalytics['hourly_sales'] ?? [],
                    'top_products' => $salesAnalytics['top_products'] ?? [],
                    'status_breakdown' => $salesAnalytics['status_breakdown'] ?? [],
                ],
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'days' => $startDate->diffInDays($endDate) + 1,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching dashboard data', [
                'dashboard_id' => $id,
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage(),
                'data' => [],
                'summary' => [
                    'total_sales' => 0,
                    'total_orders' => 0,
                    'average_order_value' => 0,
                    'currency' => 'USD',
                ],
            ], 500);
        }
    }

    /**
     * Update the dashboard layout.
     */
    public function updateLayout($id, Request $request): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No active store found.',
            ], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $validated = $request->validate([
            'layout' => 'required|array',
        ]);

        $dashboard->update([
            'layout' => $validated['layout'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Layout updated successfully.',
        ]);
    }

    /**
     * Add a widget to the dashboard.
     */
    public function addWidget($id, Request $request): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No active store found.',
            ], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'data_source' => 'required|string',
            'chart_type' => 'nullable|string',
            'size' => 'nullable|array',
            'position' => 'nullable|array',
            'config' => 'nullable|array',
            'filters' => 'nullable|array',
        ]);

        $widget = [
            'id' => uniqid('widget_'),
            'type' => $validated['type'],
            'title' => $validated['title'],
            'data_source' => $validated['data_source'],
            'chart_type' => $validated['chart_type'] ?? null,
            'size' => $validated['size'] ?? ['w' => 6, 'h' => 4],
            'position' => $validated['position'] ?? ['x' => 0, 'y' => 0],
            'config' => $validated['config'] ?? [],
            'filters' => $validated['filters'] ?? [],
            'created_at' => now()->toISOString(),
        ];

        $dashboard->addWidget($widget);

        return response()->json([
            'success' => true,
            'message' => 'Widget added successfully.',
            'widget' => $widget,
        ]);
    }

    /**
     * Update a dashboard widget.
     */
    public function updateWidget($id, $widgetId, Request $request): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No active store found.',
            ], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|string',
            'chart_type' => 'nullable|string',
            'data_source' => 'nullable|string',
            'size' => 'nullable|array',
            'position' => 'nullable|array',
            'config' => 'nullable|array',
            'filters' => 'nullable|array',
        ]);

        $updatedWidget = array_filter($validated, function ($value) {
            return $value !== null;
        });

        $dashboard->updateWidget($widgetId, $updatedWidget);

        return response()->json([
            'success' => true,
            'message' => 'Widget updated successfully.',
            'widget' => $updatedWidget,
        ]);
    }

    /**
     * Remove a widget from the dashboard.
     */
    public function removeWidget($id, $widgetId): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'No active store found.',
            ], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $dashboard->removeWidget($widgetId);

        return response()->json([
            'success' => true,
            'message' => 'Widget removed successfully.',
        ]);
    }

    /**
     * Process sales widget data.
     */
    private function processSalesWidget($widget, $salesAnalytics): array
    {
        $widgetType = $widget['type'] ?? 'metric';
        $title = $widget['title'] ?? 'Sales';

        switch ($widgetType) {
            case 'metric':
                return [
                    'type' => 'metric',
                    'title' => $title,
                    'value' => $salesAnalytics['summary']['total_sales'] ?? 0,
                    'label' => 'Total Sales',
                    'currency' => $salesAnalytics['summary']['currency'] ?? 'USD',
                    'change' => $salesAnalytics['summary']['growth_rate'] ?? 0,
                    'subtitle' => ($salesAnalytics['summary']['total_orders'] ?? 0).' orders',
                ];

            case 'chart':
                $chartType = $widget['chart_type'] ?? 'line';
                $dailySales = $salesAnalytics['daily_sales'] ?? [];

                return [
                    'type' => 'chart',
                    'chart_type' => $chartType,
                    'title' => $title,
                    'data' => [
                        'labels' => array_column($dailySales, 'date'),
                        'datasets' => [
                            [
                                'label' => 'Sales',
                                'data' => array_column($dailySales, 'sales'),
                                'borderColor' => '#3B82F6',
                                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                                'tension' => 0.4,
                            ],
                        ],
                    ],
                    'options' => [
                        'responsive' => true,
                        'plugins' => [
                            'legend' => [
                                'display' => false,
                            ],
                        ],
                        'scales' => [
                            'y' => [
                                'beginAtZero' => true,
                            ],
                        ],
                    ],
                ];

            case 'list':
                $topProducts = $salesAnalytics['top_products'] ?? [];

                return [
                    'type' => 'list',
                    'title' => $title,
                    'items' => array_slice($topProducts, 0, 5),
                    'total' => count($topProducts),
                ];

            default:
                return [
                    'type' => 'metric',
                    'title' => $title,
                    'value' => $salesAnalytics['summary']['total_sales'] ?? 0,
                    'currency' => $salesAnalytics['summary']['currency'] ?? 'USD',
                ];
        }
    }

    /**
     * Process product widget data.
     */
    private function processProductWidget($widget, $productAnalytics): array
    {
        $widgetType = $widget['type'] ?? 'metric';
        $title = $widget['title'] ?? 'Products';

        if (isset($productAnalytics['error'])) {
            return [
                'type' => $widgetType,
                'title' => $title,
                'error' => true,
                'message' => 'Unable to load product data',
                'value' => 0,
            ];
        }

        switch ($widgetType) {
            case 'metric':
                return [
                    'type' => 'metric',
                    'title' => $title,
                    'value' => $productAnalytics['summary']['total_products'] ?? 0,
                    'label' => 'Total Products',
                    'subtitle' => ($productAnalytics['summary']['total_variants'] ?? 0).' variants',
                ];

            case 'list':
                $topProducts = array_slice($productAnalytics['by_vendor'] ?? [], 0, 10);

                return [
                    'type' => 'list',
                    'title' => $title,
                    'items' => array_map(function ($vendor, $count) {
                        return [
                            'title' => $vendor,
                            'value' => $count,
                            'subtitle' => $count.' products',
                        ];
                    }, array_keys($topProducts), $topProducts),
                    'total' => count($productAnalytics['by_vendor'] ?? []),
                ];

            case 'chart':
                $chartType = $widget['chart_type'] ?? 'doughnut';
                $inventoryStatus = $productAnalytics['inventory_status'] ?? [];

                return [
                    'type' => 'chart',
                    'chart_type' => $chartType,
                    'title' => $title,
                    'data' => [
                        'labels' => ['In Stock', 'Low Stock', 'Out of Stock'],
                        'datasets' => [
                            [
                                'data' => [
                                    $inventoryStatus['in_stock'] ?? 0,
                                    $inventoryStatus['low_stock'] ?? 0,
                                    $inventoryStatus['out_of_stock'] ?? 0,
                                ],
                                'backgroundColor' => [
                                    '#10B981',
                                    '#F59E0B',
                                    '#EF4444',
                                ],
                            ],
                        ],
                    ],
                ];

            default:
                return [
                    'type' => 'metric',
                    'title' => $title,
                    'value' => $productAnalytics['summary']['total_products'] ?? 0,
                ];
        }
    }

    /**
     * Process inventory widget data.
     */
    private function processInventoryWidget($widget, $inventoryAnalytics): array
    {
        $widgetType = $widget['type'] ?? 'metric';
        $title = $widget['title'] ?? 'Inventory';

        if (isset($inventoryAnalytics['error'])) {
            return [
                'type' => $widgetType,
                'title' => $title,
                'error' => true,
                'message' => 'Unable to load inventory data',
                'value' => 0,
            ];
        }

        switch ($widgetType) {
            case 'metric':
                $alerts = $inventoryAnalytics['alerts'] ?? [];

                return [
                    'type' => 'metric',
                    'title' => $title,
                    'value' => $alerts['needs_attention'] ?? 0,
                    'label' => 'Items Need Attention',
                    'subtitle' => 'Low stock alerts',
                ];

            case 'list':
                $lowStockItems = $inventoryAnalytics['low_stock_items'] ?? [];

                return [
                    'type' => 'list',
                    'title' => $title,
                    'items' => array_slice($lowStockItems, 0, 10),
                    'total' => count($lowStockItems),
                ];

            default:
                return [
                    'type' => 'metric',
                    'title' => $title,
                    'value' => ($inventoryAnalytics['alerts']['needs_attention'] ?? 0),
                ];
        }
    }

    /**
     * Process default widget data.
     */
    private function processDefaultWidget($widget, $salesAnalytics): array
    {
        return [
            'type' => 'metric',
            'title' => $widget['title'] ?? 'Sales Data',
            'value' => $salesAnalytics['summary']['total_sales'] ?? 0,
            'currency' => $salesAnalytics['summary']['currency'] ?? 'USD',
            'label' => 'Total Sales',
        ];
    }

    /**
     * Create a default dashboard for new stores.
     */
    private function createDefaultDashboard($store): Dashboard
    {
        $dashboard = new Dashboard([
            'name' => 'Main Dashboard',
            'description' => 'Default dashboard with essential metrics',
            'is_default' => true,
            'layout' => $this->getDefaultLayout(),
            'settings' => [
                'refresh_interval' => 300,
                'date_range' => '30days',
                'timezone' => config('app.timezone'),
            ],
        ]);

        $store->dashboards()->save($dashboard);

        return $dashboard;
    }

    /**
     * Get default layout for new dashboards.
     */
    private function getDefaultLayout(): array
    {
        return [
            [
                'id' => 'total_sales',
                'type' => 'metric',
                'title' => 'Total Sales',
                'data_source' => 'sales',
                'size' => ['w' => 3, 'h' => 2],
                'position' => ['x' => 0, 'y' => 0],
            ],
            [
                'id' => 'total_orders',
                'type' => 'metric',
                'title' => 'Total Orders',
                'data_source' => 'sales',
                'size' => ['w' => 3, 'h' => 2],
                'position' => ['x' => 3, 'y' => 0],
            ],
            [
                'id' => 'avg_order_value',
                'type' => 'metric',
                'title' => 'Average Order Value',
                'data_source' => 'sales',
                'size' => ['w' => 3, 'h' => 2],
                'position' => ['x' => 6, 'y' => 0],
            ],
            [
                'id' => 'total_products',
                'type' => 'metric',
                'title' => 'Total Products',
                'data_source' => 'products',
                'size' => ['w' => 3, 'h' => 2],
                'position' => ['x' => 9, 'y' => 0],
            ],
            [
                'id' => 'sales_chart',
                'type' => 'chart',
                'title' => 'Sales Over Time',
                'data_source' => 'sales',
                'chart_type' => 'line',
                'size' => ['w' => 8, 'h' => 4],
                'position' => ['x' => 0, 'y' => 2],
            ],
            [
                'id' => 'top_products',
                'type' => 'list',
                'title' => 'Top Products',
                'data_source' => 'sales',
                'size' => ['w' => 4, 'h' => 4],
                'position' => ['x' => 8, 'y' => 2],
            ],
        ];
    }

    /**
     * Get available widget types.
     */
    private function getAvailableWidgets(): array
    {
        return [
            [
                'type' => 'metric',
                'name' => 'Metric Card',
                'description' => 'Display a single key metric',
                'icon' => 'chart-bar',
            ],
            [
                'type' => 'chart',
                'name' => 'Chart',
                'description' => 'Visualize data with charts',
                'icon' => 'chart-line',
            ],
            [
                'type' => 'list',
                'name' => 'List',
                'description' => 'Show data in list format',
                'icon' => 'list-bullet',
            ],
            [
                'type' => 'table',
                'name' => 'Table',
                'description' => 'Display data in table format',
                'icon' => 'table-cells',
            ],
        ];
    }
}
