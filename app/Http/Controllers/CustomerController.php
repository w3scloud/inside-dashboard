<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerController extends Controller
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
     * Display a listing of customers with analytics.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Customers/NoStore');
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

        // Fetch customer data
        $customerData = $this->analyticsService->getCustomerData(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        // Get customer summary
        $customerSummary = $this->analyticsService->getCustomerSummary(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        return Inertia::render('Customers/Index', [
            'customerData' => $customerData,
            'summary' => $customerSummary,
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
     * Display customer segments analytics.
     *
     * @return \Inertia\Response
     */
    public function segments(Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Customers/NoStore');
        }

        // Get date range from request or use default (last 90 days)
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays(90);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        }

        // Get filters from request
        $filters = $request->input('filters', []);

        // Fetch customer segments data
        $segmentsData = $this->analyticsService->getCustomerSegments(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        // Get customer summary with segments
        $customerSummary = $this->analyticsService->getCustomerSummary(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        return Inertia::render('Customers/Segments', [
            'segmentsData' => $segmentsData,
            'summary' => $customerSummary,
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
     * Display the specified customer with analytics.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id, Request $request)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Customers/NoStore');
        }

        // Get date range from request or use default (last 365 days)
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays(365);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        }

        // Fetch customer details from Shopify
        $customer = $this->analyticsService->getCustomerDetails($store, $id);

        if (! $customer) {
            return redirect()->route('customers.index')
                ->with('error', 'Customer not found.');
        }

        // Fetch customer order history
        $orderHistory = $this->analyticsService->getCustomerOrderHistory(
            $store,
            $id,
            $startDate,
            $endDate
        );

        // Fetch customer purchase metrics
        $purchaseMetrics = $this->analyticsService->getCustomerPurchaseMetrics(
            $store,
            $id,
            $startDate,
            $endDate
        );

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'orderHistory' => $orderHistory,
            'metrics' => $purchaseMetrics,
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
