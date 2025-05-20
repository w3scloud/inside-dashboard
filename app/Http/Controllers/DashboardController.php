<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $analyticsService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the dashboard index.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Dashboard/NoStore');
        }

        // Get all dashboards for the store
        $dashboards = $store->dashboards()
            ->orderBy('is_default', 'desc')
            ->orderBy('last_viewed_at', 'desc')
            ->get()
            ->map(function ($dashboard) {
                return [
                    'id' => $dashboard->id,
                    'name' => $dashboard->name,
                    'description' => $dashboard->description,
                    'is_default' => $dashboard->is_default,
                    'last_viewed_at' => $dashboard->last_viewed_at ? $dashboard->last_viewed_at->diffForHumans() : null,
                ];
            });

        // If no dashboards exist, create a default one
        if ($dashboards->isEmpty()) {
            $dashboard = $this->createDefaultDashboard($store);

            return redirect()->route('dashboard.show', $dashboard->id);
        }

        // If there's only one dashboard, redirect to it
        if ($dashboards->count() === 1) {
            return redirect()->route('dashboard.show', $dashboards->first()['id']);
        }

        return Inertia::render('Dashboard/Index', [
            'dashboards' => $dashboards,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Show the form for creating a new dashboard.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Dashboard/NoStore');
        }

        return Inertia::render('Dashboard/Create', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Store a newly created dashboard in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
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
            'layout' => [],
            'settings' => [
                'date_range' => [
                    'start' => Carbon::now()->subDays(30)->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
                'refresh_interval' => 0,
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
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Dashboard/NoStore');
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        // Update last viewed timestamp
        $dashboard->updateLastViewed();

        // Get widgets from layout
        $widgets = $dashboard->getWidgetsFromLayout();

        // Get date range from settings
        $dateRange = $dashboard->settings['date_range'] ?? [
            'start' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'end' => Carbon::now()->format('Y-m-d'),
        ];

        return Inertia::render('Dashboard/Show', [
            'dashboard' => [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'description' => $dashboard->description,
                'is_default' => $dashboard->is_default,
                'layout' => $widgets,
                'settings' => $dashboard->settings,
            ],
            'date_range' => $dateRange,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified dashboard.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Dashboard/NoStore');
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        return Inertia::render('Dashboard/Edit', [
            'dashboard' => [
                'id' => $dashboard->id,
                'name' => $dashboard->name,
                'description' => $dashboard->description,
                'is_default' => $dashboard->is_default,
                'settings' => $dashboard->settings,
            ],
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Update the specified dashboard in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
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
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
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
     * Fetch data for the dashboard.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchData($id, Request $request)
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

        // Get date range from request or settings
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (! $startDate || ! $endDate) {
            $dateRange = $dashboard->settings['date_range'] ?? null;

            if ($dateRange) {
                $startDate = $dateRange['start'];
                $endDate = $dateRange['end'];
            } else {
                $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
            }
        }

        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        // Get widgets from layout
        $widgets = $dashboard->getWidgetsFromLayout();

        // Fetch data for each widget
        $widgetData = [];

        foreach ($widgets as $widget) {
            $widgetId = $widget['id'];
            $widgetType = $widget['type'];
            $dataSource = $widget['data_source'];
            $filters = $widget['filters'] ?? [];

            $data = $this->getWidgetData($store, $widgetType, $dataSource, $startDate, $endDate, $filters);

            $widgetData[$widgetId] = $data;
        }

        return response()->json([
            'success' => true,
            'data' => $widgetData,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Update dashboard layout.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLayout($id, Request $request)
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

        $dashboard->update(['layout' => $validated['layout']]);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard layout updated successfully.',
            'layout' => $dashboard->layout,
        ]);
    }

    /**
     * Add a widget to the dashboard.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addWidget($id, Request $request)
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
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'chart_type' => 'nullable|string',
            'data_source' => 'required|string',
            'size' => 'required|array',
            'position' => 'required|array',
            'config' => 'nullable|array',
            'filters' => 'nullable|array',
        ]);

        // Generate unique widget ID
        $widgetId = Str::uuid()->toString();

        $widget = [
            'id' => $widgetId,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'chart_type' => $validated['chart_type'] ?? null,
            'data_source' => $validated['data_source'],
            'size' => $validated['size'],
            'position' => $validated['position'],
            'config' => $validated['config'] ?? [],
            'filters' => $validated['filters'] ?? [],
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
     *
     * @param  int  $id
     * @param  string  $widgetId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateWidget($id, $widgetId, Request $request)
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
     *
     * @param  int  $id
     * @param  string  $widgetId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeWidget($id, $widgetId)
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
     * Get data for a specific widget.
     */
    private function getWidgetData($store, $widgetType, $dataSource, $startDate, $endDate, $filters = [])
    {
        switch ($dataSource) {
            case 'sales':
                return $this->getSalesData($store, $widgetType, $startDate, $endDate, $filters);

            case 'products':
                return $this->getProductsData($store, $widgetType, $startDate, $endDate, $filters);

            case 'inventory':
                return $this->getInventoryData($store, $widgetType, $filters);

            case 'customers':
                return $this->getCustomersData($store, $widgetType, $startDate, $endDate, $filters);

            default:
                return null;
        }
    }

    /**
     * Get sales data for a widget.
     */
    private function getSalesData($store, $widgetType, $startDate, $endDate, $filters)
    {
        // Use analytics service to get sales data
        $productPerformance = $this->analyticsService->getProductPerformance(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        switch ($widgetType) {
            case 'kpi':
                return [
                    'total_sales' => $productPerformance['total_sales'],
                    'total_orders' => $productPerformance['total_orders'],
                    'avg_order_value' => $productPerformance['avg_order_value'],
                ];

            case 'timeline':
                return [
                    'timeline' => $productPerformance['timeline'],
                ];

            case 'table':
                return [
                    'products' => array_slice($productPerformance['products'], 0, 10),
                ];

            default:
                return $productPerformance;
        }
    }

    /**
     * Get products data for a widget.
     */
    private function getProductsData($store, $widgetType, $startDate, $endDate, $filters)
    {
        // Use analytics service to get product data
        $productSummary = $this->analyticsService->getProductSummary(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        switch ($widgetType) {
            case 'kpi':
                return [
                    'total_products' => $productSummary['total_products'],
                    'active_products' => $productSummary['active_products'],
                ];

            case 'top_selling':
                return [
                    'products' => $productSummary['top_selling'],
                ];

            case 'low_selling':
                return [
                    'products' => $productSummary['low_selling'],
                ];

            default:
                return $productSummary;
        }
    }

    /**
     * Get inventory data for a widget.
     */
    private function getInventoryData($store, $widgetType, $filters)
    {
        // Use analytics service to get inventory data
        $inventorySummary = $this->analyticsService->getInventorySummary($store, $filters);

        switch ($widgetType) {
            case 'kpi':
                return [
                    'total_items' => $inventorySummary['total_items'],
                    'out_of_stock' => $inventorySummary['out_of_stock'],
                    'low_stock' => $inventorySummary['low_stock'],
                ];

            case 'pie_chart':
                return [
                    'stock_status' => $inventorySummary['stock_status'],
                ];

            default:
                return $inventorySummary;
        }
    }

    /**
     * Get customers data for a widget.
     */
    private function getCustomersData($store, $widgetType, $startDate, $endDate, $filters)
    {
        // Use analytics service to get customer data
        $customerSummary = $this->analyticsService->getCustomerSummary(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        $customerSegments = $this->analyticsService->getCustomerSegments(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        switch ($widgetType) {
            case 'kpi':
                return [
                    'total_customers' => $customerSummary['total_customers'],
                    'new_customers' => $customerSummary['new_customers'],
                    'returning_customers' => $customerSummary['returning_customers'],
                ];

            case 'top_customers':
                return [
                    'customers' => $customerSummary['top_customers'],
                ];

            case 'segments':
                return [
                    'segments' => $customerSegments['segments'],
                ];

            default:
                return array_merge($customerSummary, ['segments' => $customerSegments['segments']]);
        }
    }

    /**
     * Create a default dashboard for a store.
     */
    private function createDefaultDashboard($store)
    {
        $dashboard = new Dashboard([
            'name' => 'Overview',
            'description' => 'Default store overview dashboard',
            'is_default' => true,
            'layout' => $this->getDefaultLayout(),
            'settings' => [
                'date_range' => [
                    'start' => Carbon::now()->subDays(30)->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
                'refresh_interval' => 0,
            ],
            'last_viewed_at' => now(),
        ]);

        $store->dashboards()->save($dashboard);

        return $dashboard;
    }

    /**
     * Get default dashboard layout.
     */
    private function getDefaultLayout()
    {
        return [
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Total Sales',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 0, 'y' => 0],
                'config' => ['display' => 'currency'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Total Orders',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 1, 'y' => 0],
                'config' => ['display' => 'number'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Average Order Value',
                'type' => 'kpi',
                'data_source' => 'sales',
                'size' => ['w' => 1, 'h' => 1],
                'position' => ['x' => 2, 'y' => 0],
                'config' => ['display' => 'currency'],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Sales Over Time',
                'type' => 'timeline',
                'chart_type' => 'line',
                'data_source' => 'sales',
                'size' => ['w' => 3, 'h' => 2],
                'position' => ['x' => 0, 'y' => 1],
                'config' => [],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Top Selling Products',
                'type' => 'table',
                'data_source' => 'sales',
                'size' => ['w' => 2, 'h' => 2],
                'position' => ['x' => 0, 'y' => 3],
                'config' => [],
                'filters' => [],
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Inventory Status',
                'type' => 'pie_chart',
                'data_source' => 'inventory',
                'size' => ['w' => 1, 'h' => 2],
                'position' => ['x' => 2, 'y' => 3],
                'config' => [],
                'filters' => [],
            ],
        ];
    }
}
