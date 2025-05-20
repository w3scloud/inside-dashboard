<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
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
     * Display a listing of products with analytics.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Products/NoStore');
        }

        // Get date range from request or use default (last 30 days)
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays(30);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        }

        // Get filters from request
        $filters = $request->input('filters', []);

        // Fetch product performance data
        $productData = $this->analyticsService->getProductPerformance(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        // Get product summary
        $productSummary = $this->analyticsService->getProductSummary(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        return Inertia::render('Products/Index', [
            'productData' => $productData,
            'summary' => $productSummary,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'filters' => $filters,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Display product performance analytics.
     *
     * @return \Inertia\Response
     */
    public function performance(Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Products/NoStore');
        }

        // Get date range from request or use default (last 30 days)
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays(30);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        }

        // Get filters from request
        $filters = $request->input('filters', []);

        // Fetch product performance data
        $productData = $this->analyticsService->getProductPerformance(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        return Inertia::render('Products/Performance', [
            'productData' => $productData,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'filters' => $filters,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Display inventory status analytics.
     *
     * @return \Inertia\Response
     */
    public function inventory(Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Products/NoStore');
        }

        // Get filters from request
        $filters = $request->input('filters', []);

        // Fetch inventory data
        $inventoryStatus = $this->analyticsService->getInventoryStatus(
            $store,
            $filters
        );

        // Get inventory summary
        $inventorySummary = $this->analyticsService->getInventorySummary(
            $store,
            $filters
        );

        return Inertia::render('Products/Inventory', [
            'inventoryStatus' => $inventoryStatus,
            'summary' => $inventorySummary,
            'filters' => $filters,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Display the specified product with analytics.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id, Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Products/NoStore');
        }

        // Get date range from request or use default (last 30 days)
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays(30);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        }

        // Fetch product details from Shopify
        $product = $this->analyticsService->getProductDetails($store, $id);

        if (! $product) {
            return redirect()->route('products.index')
                ->with('error', 'Product not found.');
        }

        // Fetch product performance data for this product
        $performanceData = $this->analyticsService->getProductPerformanceById(
            $store,
            $id,
            $startDate,
            $endDate
        );

        // Fetch inventory data for this product
        $inventoryData = $this->analyticsService->getProductInventoryById(
            $store,
            $id
        );

        return Inertia::render('Products/Show', [
            'product' => $product,
            'performanceData' => $performanceData,
            'inventoryData' => $inventoryData,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }
}
