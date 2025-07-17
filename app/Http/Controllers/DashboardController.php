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
     * FIXED: Changed return type to handle both Inertia and redirect responses
     */
    public function index(Request $request)
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

        // Check if we should auto-redirect to the default dashboard
        // You can control this with a query parameter: ?redirect=false
        $shouldRedirect = $request->get('redirect', 'true') === 'true';

        if ($shouldRedirect) {
            // Redirect to the default dashboard
            $defaultDashboard = $dashboards->where('is_default', true)->first()
                ?? $dashboards->first();

            return redirect()->route('dashboard.show', $defaultDashboard->id);
        }

        // Return the dashboard listing page
        return Inertia::render('Dashboard/Index', [
            'dashboards' => $dashboards->map(function ($dashboard) {
                return [
                    'id' => $dashboard->id,
                    'name' => $dashboard->name,
                    'description' => $dashboard->description,
                    'is_default' => $dashboard->is_default,
                    'last_viewed_at' => $dashboard->last_viewed_at?->diffForHumans(),
                    'widgets_count' => count($dashboard->layout ?? []),
                    'created_at' => $dashboard->created_at->format('M j, Y'),
                ];
            }),
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Create a default dashboard for the store
     */
    private function createDefaultDashboard($store)
    {
        $dashboard = new Dashboard([
            'name' => 'Main Dashboard',
            'description' => 'Default dashboard with key metrics',
            'is_default' => true,
            'layout' => $this->getDefaultLayout(),
            'settings' => [
                'refresh_interval' => 300, // 5 minutes
                'date_range' => '30days',
                'timezone' => config('app.timezone'),
            ],
        ]);

        $store->dashboards()->save($dashboard);

        return $dashboard;
    }

    /**
     * Get default dashboard layout for Vue.js components
     */
    private function getDefaultLayout()
    {
        return [
            [
                'i' => 'sales-overview',
                'x' => 0,
                'y' => 0,
                'w' => 6,
                'h' => 4,
                'component' => 'SalesOverviewWidget',
                'title' => 'Sales Overview',
                'type' => 'sales_overview',
                'props' => [
                    'period' => '30days',
                ],
            ],
            [
                'i' => 'revenue-chart',
                'x' => 6,
                'y' => 0,
                'w' => 6,
                'h' => 4,
                'component' => 'RevenueChartWidget',
                'title' => 'Revenue Trend',
                'type' => 'revenue_chart',
                'props' => [
                    'chartType' => 'line',
                    'period' => '30days',
                ],
            ],
            [
                'i' => 'top-products',
                'x' => 0,
                'y' => 4,
                'w' => 4,
                'h' => 4,
                'component' => 'TopProductsWidget',
                'title' => 'Top Products',
                'type' => 'top_products',
                'props' => [
                    'limit' => 10,
                ],
            ],
            [
                'i' => 'customer-metrics',
                'x' => 4,
                'y' => 4,
                'w' => 4,
                'h' => 4,
                'component' => 'CustomerMetricsWidget',
                'title' => 'Customer Metrics',
                'type' => 'customer_metrics',
                'props' => [
                    'period' => '30days',
                ],
            ],
            [
                'i' => 'inventory-alerts',
                'x' => 8,
                'y' => 4,
                'w' => 4,
                'h' => 4,
                'component' => 'InventoryAlertsWidget',
                'title' => 'Inventory Alerts',
                'type' => 'inventory_alerts',
                'props' => [
                    'threshold' => 10,
                ],
            ],
        ];
    }

    /**
     * Get available widgets for dashboard customization
     */
    private function getAvailableWidgets()
    {
        return [
            'sales_overview' => [
                'name' => 'Sales Overview',
                'description' => 'Key sales metrics and KPIs',
                'category' => 'sales',
                'component' => 'SalesOverviewWidget',
                'icon' => 'chart-bar',
                'default_size' => ['w' => 6, 'h' => 4],
                'min_size' => ['w' => 3, 'h' => 2],
                'configurable' => ['period', 'metrics'],
            ],
            'revenue_chart' => [
                'name' => 'Revenue Chart',
                'description' => 'Revenue trends over time',
                'category' => 'sales',
                'component' => 'RevenueChartWidget',
                'icon' => 'trending-up',
                'default_size' => ['w' => 6, 'h' => 4],
                'min_size' => ['w' => 4, 'h' => 3],
                'configurable' => ['chartType', 'period', 'granularity'],
            ],
            'top_products' => [
                'name' => 'Top Products',
                'description' => 'Best performing products',
                'category' => 'products',
                'component' => 'TopProductsWidget',
                'icon' => 'package',
                'default_size' => ['w' => 4, 'h' => 4],
                'min_size' => ['w' => 3, 'h' => 3],
                'configurable' => ['limit', 'sortBy', 'period'],
            ],
            'customer_metrics' => [
                'name' => 'Customer Metrics',
                'description' => 'Customer acquisition and retention',
                'category' => 'customers',
                'component' => 'CustomerMetricsWidget',
                'icon' => 'users',
                'default_size' => ['w' => 4, 'h' => 4],
                'min_size' => ['w' => 3, 'h' => 3],
                'configurable' => ['metrics', 'period'],
            ],
            'inventory_alerts' => [
                'name' => 'Inventory Alerts',
                'description' => 'Low stock and inventory warnings',
                'category' => 'inventory',
                'component' => 'InventoryAlertsWidget',
                'icon' => 'alert-triangle',
                'default_size' => ['w' => 4, 'h' => 4],
                'min_size' => ['w' => 3, 'h' => 3],
                'configurable' => ['threshold', 'alertTypes'],
            ],
            'order_status' => [
                'name' => 'Order Status',
                'description' => 'Current order statuses',
                'category' => 'orders',
                'component' => 'OrderStatusWidget',
                'icon' => 'shopping-cart',
                'default_size' => ['w' => 4, 'h' => 3],
                'min_size' => ['w' => 3, 'h' => 2],
                'configurable' => ['statuses', 'timeframe'],
            ],
        ];
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
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
            'availableWidgets' => $this->getAvailableWidgets(),
            'defaultLayout' => $this->getDefaultLayout(),
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
            'layout' => 'nullable|array',
        ]);

        $dashboard = new Dashboard([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
            'layout' => $validated['layout'] ?? $this->getDefaultLayout(),
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

        // Get all dashboards for the dropdown
        $allDashboards = $store->dashboards()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->name,
                    'is_default' => $d->is_default,
                ];
            });

        return Inertia::render('Dashboard/Show', [
            'dashboard' => [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'description' => $dashboard->description,
                'is_default' => $dashboard->is_default,
                'layout' => $dashboard->layout ?? [],
                'settings' => $dashboard->settings ?? [],
                'last_viewed_at' => $dashboard->last_viewed_at?->diffForHumans(),
                'created_at' => $dashboard->created_at->format('M j, Y'),
            ],
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
            'allDashboards' => $allDashboards,
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
            'dashboard' => [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'description' => $dashboard->description,
                'is_default' => $dashboard->is_default,
                'layout' => $dashboard->layout ?? [],
                'settings' => $dashboard->settings ?? [],
            ],
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
            'availableWidgets' => $this->getAvailableWidgets(),
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
            'layout' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $dashboard->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $dashboard->description,
            'is_default' => $validated['is_default'] ?? $dashboard->is_default,
            'layout' => $validated['layout'] ?? $dashboard->layout,
            'settings' => array_merge($dashboard->settings ?? [], $validated['settings'] ?? []),
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
     * Fetch data for the dashboard widgets - API endpoint
     */
    public function fetchData($id, Request $request): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json(['error' => 'No active store found'], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $dateRange = $request->input('date_range', '30days');
        $widgetType = $request->input('widget_type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Parse date range
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } else {
            [$start, $end] = $this->parseDateRange($dateRange);
        }

        try {
            $data = $this->analyticsService->getDashboardData(
                $store,
                $dashboard,
                $start,
                $end,
                $widgetType
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'period' => $dateRange,
                    'store_id' => $store->id,
                    'dashboard_id' => $dashboard->id,
                ],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard data fetch failed', [
                'dashboard_id' => $id,
                'store_id' => $store->id,
                'widget_type' => $widgetType,
                'date_range' => $dateRange,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch dashboard data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update dashboard layout (for Vue.js grid layout)
     */
    public function updateLayout(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json(['error' => 'No active store found'], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $validated = $request->validate([
            'layout' => 'required|array',
            'layout.*' => 'required|array',
            'layout.*.i' => 'required|string',
            'layout.*.x' => 'required|integer|min:0',
            'layout.*.y' => 'required|integer|min:0',
            'layout.*.w' => 'required|integer|min:1',
            'layout.*.h' => 'required|integer|min:1',
        ]);

        $dashboard->update([
            'layout' => $validated['layout'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Layout updated successfully',
        ]);
    }

    /**
     * Parse date range string into Carbon dates
     */
    private function parseDateRange($range)
    {
        $end = Carbon::now();

        switch ($range) {
            case '7days':
                $start = $end->copy()->subDays(7);
                break;
            case '30days':
                $start = $end->copy()->subDays(30);
                break;
            case '90days':
                $start = $end->copy()->subDays(90);
                break;
            case 'this_month':
                $start = $end->copy()->startOfMonth();
                break;
            case 'last_month':
                $start = $end->copy()->subMonth()->startOfMonth();
                $end = $end->copy()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $start = $end->copy()->startOfYear();
                break;
            default:
                $start = $end->copy()->subDays(30);
        }

        return [$start, $end];
    }
}
