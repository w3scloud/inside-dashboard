<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Services\GraphQLAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    protected $analyticsService;

    public function __construct(GraphQLAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display a listing of dashboards.
     */
    public function index(): InertiaResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('stores.create')
                ->with('error', 'Please connect a Shopify store first.');
        }

        // Get or create default dashboard
        $dashboard = $store->dashboards()->where('is_default', true)->first();

        if (! $dashboard) {
            $dashboard = $store->dashboards()->create([
                'name' => 'Main Dashboard',
                'description' => 'Your primary analytics dashboard',
                'is_default' => true,
                'layout' => $this->getDefaultLayout(),
                'settings' => [],
            ]);
        }

        return redirect()->route('dashboard.show', $dashboard->id);
    }

    /**
     * Show the specified dashboard.
     */
    public function show($id): InertiaResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('dashboard')
                ->with('error', 'No active store found.');
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        // Update last viewed timestamp
        $dashboard->update(['last_viewed_at' => now()]);

        // Get all dashboards for navigation
        $allDashboards = $store->dashboards()
            ->select('id', 'name', 'description', 'is_default', 'last_viewed_at', 'created_at')
            ->orderBy('is_default', 'desc')
            ->orderBy('last_viewed_at', 'desc')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->name,
                    'description' => $d->description,
                    'is_default' => $d->is_default,
                    'last_viewed_at' => $d->last_viewed_at?->diffForHumans(),
                    'created_at' => $d->created_at->format('M j, Y'),
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
     * THIS METHOD IS DEPRECATED - Use AnalyticsController::dashboard instead
     * Kept for backward compatibility, but redirects to the working endpoint
     */
    public function fetchData($id, Request $request): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json(['error' => 'No active store found'], 404);
        }

        // Log the deprecated call
        Log::info('Deprecated fetchData method called, redirecting to analytics endpoint', [
            'dashboard_id' => $id,
            'store_id' => $store->id,
            'params' => $request->all(),
        ]);

        // Instead of duplicating logic, redirect to the working analytics endpoint
        // or call the analytics service directly
        try {
            $startDate = $request->input('start_date') ?
                Carbon::parse($request->input('start_date')) :
                now()->subDays(30);

            $endDate = $request->input('end_date') ?
                Carbon::parse($request->input('end_date')) :
                now();

            // Use the working getDashboardAnalytics method
            $data = $this->analyticsService->getDashboardAnalytics($store);

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'store_id' => $store->id,
                    'dashboard_id' => $id,
                ],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard data fetch failed', [
                'dashboard_id' => $id,
                'store_id' => $store->id,
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

        Log::info('Dashboard layout updated', [
            'dashboard_id' => $id,
            'layout_items' => count($validated['layout']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Layout updated successfully',
        ]);
    }

    /**
     * Add a widget to the dashboard
     */
    public function addWidget(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json(['error' => 'No active store found'], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $validated = $request->validate([
            'widget_type' => 'required|string',
            'position' => 'required|array',
            'position.x' => 'required|integer|min:0',
            'position.y' => 'required|integer|min:0',
            'position.w' => 'required|integer|min:1',
            'position.h' => 'required|integer|min:1',
        ]);

        $currentLayout = $dashboard->layout ?? [];

        $newWidget = [
            'i' => $validated['widget_type'],
            'x' => $validated['position']['x'],
            'y' => $validated['position']['y'],
            'w' => $validated['position']['w'],
            'h' => $validated['position']['h'],
        ];

        $currentLayout[] = $newWidget;

        $dashboard->update([
            'layout' => $currentLayout,
        ]);

        Log::info('Widget added to dashboard', [
            'dashboard_id' => $id,
            'widget_type' => $validated['widget_type'],
            'position' => $validated['position'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Widget added successfully',
            'widget' => $newWidget,
        ]);
    }

    /**
     * Remove a widget from the dashboard
     */
    public function removeWidget(Request $request, $id, $widgetId): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json(['error' => 'No active store found'], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);
        $currentLayout = $dashboard->layout ?? [];

        // Remove the widget from layout
        $newLayout = array_filter($currentLayout, function ($item) use ($widgetId) {
            return $item['i'] !== $widgetId;
        });

        $dashboard->update([
            'layout' => array_values($newLayout), // Reset array keys
        ]);

        Log::info('Widget removed from dashboard', [
            'dashboard_id' => $id,
            'widget_id' => $widgetId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Widget removed successfully',
        ]);
    }

    /**
     * Update widget configuration
     */
    public function updateWidget(Request $request, $id, $widgetId): JsonResponse
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return response()->json(['error' => 'No active store found'], 404);
        }

        $dashboard = $store->dashboards()->findOrFail($id);

        $validated = $request->validate([
            'position' => 'sometimes|array',
            'position.x' => 'sometimes|integer|min:0',
            'position.y' => 'sometimes|integer|min:0',
            'position.w' => 'sometimes|integer|min:1',
            'position.h' => 'sometimes|integer|min:1',
            'config' => 'sometimes|array',
        ]);

        $currentLayout = $dashboard->layout ?? [];
        $widgetSettings = $dashboard->settings['widgets'] ?? [];

        // Update layout if position is provided
        if (isset($validated['position'])) {
            foreach ($currentLayout as &$item) {
                if ($item['i'] === $widgetId) {
                    $item = array_merge($item, $validated['position']);
                    break;
                }
            }
        }

        // Update widget config if provided
        if (isset($validated['config'])) {
            $widgetSettings[$widgetId] = array_merge(
                $widgetSettings[$widgetId] ?? [],
                $validated['config']
            );
        }

        $dashboard->update([
            'layout' => $currentLayout,
            'settings' => array_merge($dashboard->settings ?? [], [
                'widgets' => $widgetSettings,
            ]),
        ]);

        Log::info('Widget updated', [
            'dashboard_id' => $id,
            'widget_id' => $widgetId,
            'updates' => array_keys($validated),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Widget updated successfully',
        ]);
    }

    /**
     * Create a new dashboard
     */
    public function create(): InertiaResponse
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
     * Store a newly created dashboard
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
            'settings' => [],
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
     * Show the form for editing the specified dashboard
     */
    public function edit($id): InertiaResponse
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
     * Update the specified dashboard
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
     * Remove the specified dashboard
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
     * Get available widget types with their configurations
     */
    private function getAvailableWidgets(): array
    {
        return [
            [
                'type' => 'sales_overview',
                'name' => 'Sales Overview',
                'description' => 'Key sales metrics and revenue trends',
                'category' => 'sales',
                'component' => 'SalesOverviewWidget',
                'icon' => 'chart-bar',
                'default_size' => ['w' => 6, 'h' => 4],
                'min_size' => ['w' => 4, 'h' => 3],
                'configurable' => ['period', 'metrics'],
            ],
            [
                'type' => 'product_performance',
                'name' => 'Product Performance',
                'description' => 'Top selling products and performance metrics',
                'category' => 'products',
                'component' => 'ProductPerformanceWidget',
                'icon' => 'cube',
                'default_size' => ['w' => 6, 'h' => 4],
                'min_size' => ['w' => 4, 'h' => 3],
                'configurable' => ['limit', 'sortBy', 'period'],
            ],
            [
                'type' => 'customer_analytics',
                'name' => 'Customer Analytics',
                'description' => 'Customer acquisition and behavior insights',
                'category' => 'customers',
                'component' => 'CustomerAnalyticsWidget',
                'icon' => 'users',
                'default_size' => ['w' => 6, 'h' => 4],
                'min_size' => ['w' => 4, 'h' => 3],
                'configurable' => ['metrics', 'segments'],
            ],
            [
                'type' => 'inventory_status',
                'name' => 'Inventory Status',
                'description' => 'Stock levels and inventory alerts',
                'category' => 'inventory',
                'component' => 'InventoryStatusWidget',
                'icon' => 'archive',
                'default_size' => ['w' => 4, 'h' => 3],
                'min_size' => ['w' => 3, 'h' => 2],
                'configurable' => ['threshold', 'alertTypes'],
            ],
            [
                'type' => 'revenue_trends',
                'name' => 'Revenue Trends',
                'description' => 'Revenue trends and forecasting',
                'category' => 'sales',
                'component' => 'RevenueTrendsWidget',
                'icon' => 'trending-up',
                'default_size' => ['w' => 8, 'h' => 4],
                'min_size' => ['w' => 6, 'h' => 3],
                'configurable' => ['period', 'chartType'],
            ],
            [
                'type' => 'geographic_data',
                'name' => 'Geographic Data',
                'description' => 'Sales by location and regional insights',
                'category' => 'analytics',
                'component' => 'GeographicDataWidget',
                'icon' => 'globe',
                'default_size' => ['w' => 6, 'h' => 4],
                'min_size' => ['w' => 4, 'h' => 3],
                'configurable' => ['mapType', 'metrics'],
            ],
        ];
    }

    /**
     * Get default dashboard layout
     */
    private function getDefaultLayout(): array
    {
        return [
            ['i' => 'sales_overview', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 4],
            ['i' => 'product_performance', 'x' => 6, 'y' => 0, 'w' => 6, 'h' => 4],
            ['i' => 'customer_analytics', 'x' => 0, 'y' => 4, 'w' => 6, 'h' => 4],
            ['i' => 'inventory_status', 'x' => 6, 'y' => 4, 'w' => 6, 'h' => 4],
        ];
    }

    /**
     * Parse date range string to Carbon dates
     */
    private function parseDateRange(string $dateRange): array
    {
        $endDate = now();

        switch ($dateRange) {
            case '7days':
                $startDate = now()->subDays(7);
                break;
            case '30days':
                $startDate = now()->subDays(30);
                break;
            case '90days':
                $startDate = now()->subDays(90);
                break;
            case 'year':
                $startDate = now()->subYear();
                break;
            default:
                $startDate = now()->subDays(30);
        }

        return [$startDate, $endDate];
    }
}
