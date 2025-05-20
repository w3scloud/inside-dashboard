<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReportController extends Controller
{
    protected $reportingService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Display a listing of the reports.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Reports/NoStore');
        }

        $reports = $store->reports()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'name' => $report->name,
                    'description' => $report->description,
                    'type' => $report->type,
                    'output_format' => $report->output_format,
                    'schedule_enabled' => $report->schedule_enabled,
                    'schedule_frequency' => $report->schedule_frequency,
                    'last_generated_at' => $report->last_generated_at ? $report->last_generated_at->diffForHumans() : null,
                ];
            });

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
            'reportTypes' => config('shopify.report_types'),
        ]);
    }

    /**
     * Show the form for creating a new report.
     *
     * @return \Inertia\Response
     */
    public function create(Request $request)
    {
        $dashboardId = $request->input('dashboardId');
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        $dashboards = $store->dashboards()
            ->orderBy('is_default', 'desc')
            ->orderBy('last_viewed_at', 'desc')
            ->get()
            ->map(function ($dashboard) {
                return [
                    'id' => $dashboard->id,
                    'name' => $dashboard->name,
                ];
            });

        return Inertia::render('Reports/Create', [
            'dashboards' => $dashboards,
            'selectedDashboardId' => $dashboardId,
            'reportTypes' => config('shopify.report_types'),
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Store a newly created report in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:'.implode(',', array_keys(config('shopify.report_types'))),
            'config' => 'required|array',
            'config.date_range' => 'required|array',
            'config.date_range.start' => 'required|date',
            'config.date_range.end' => 'required|date|after_or_equal:config.date_range.start',
            'output_format' => 'required|string|in:pdf,csv',
            'schedule_enabled' => 'boolean',
            'schedule_frequency' => 'required_if:schedule_enabled,true|nullable|string|in:daily,weekly,monthly',
            'schedule_time' => 'required_if:schedule_enabled,true|nullable|string',
            'schedule_day' => 'required_if:schedule_frequency,weekly,monthly|nullable|integer',
            'schedule_recipients' => 'required_if:schedule_enabled,true|nullable|array',
            'schedule_recipients.*' => 'email',
            'filters' => 'nullable|array',
        ]);

        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('reports.index')
                ->with('error', 'No active store found.');
        }

        $report = new Report([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'config' => $validated['config'],
            'output_format' => $validated['output_format'],
            'schedule_enabled' => $validated['schedule_enabled'] ?? false,
            'schedule_frequency' => $validated['schedule_frequency'] ?? null,
            'schedule_time' => $validated['schedule_time'] ?? null,
            'schedule_day' => $validated['schedule_day'] ?? null,
            'schedule_recipients' => $validated['schedule_recipients'] ?? [],
            'filters' => $validated['filters'] ?? [],
        ]);

        $store->reports()->save($report);

        return redirect()->route('reports.show', $report->id)
            ->with('success', 'Report created successfully.');
    }

    /**
     * Display the specified report.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Reports/NoStore');
        }

        $report = $store->reports()->findOrFail($id);

        // Get date range from config or use default
        $dateRange = $report->config['date_range'] ?? [
            'start' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'end' => Carbon::now()->format('Y-m-d'),
        ];

        return Inertia::render('Reports/Show', [
            'report' => [
                'id' => $report->id,
                'name' => $report->name,
                'description' => $report->description,
                'type' => $report->type,
                'config' => $report->config,
                'output_format' => $report->output_format,
                'schedule_enabled' => $report->schedule_enabled,
                'schedule_frequency' => $report->schedule_frequency,
                'schedule_time' => $report->schedule_time,
                'schedule_day' => $report->schedule_day,
                'schedule_recipients' => $report->schedule_recipients,
                'last_generated_at' => $report->last_generated_at ? $report->last_generated_at->format('Y-m-d H:i:s') : null,
                'filters' => $report->filters,
            ],
            'date_range' => $dateRange,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
            'reportTypes' => config('shopify.report_types'),
        ]);
    }

    /**
     * Show the form for editing the specified report.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();
        $report = $store->reports()->findOrFail($id);

        return Inertia::render('Reports/Edit', [
            'report' => [
                'id' => $report->id,
                'name' => $report->name,
                'description' => $report->description,
                'type' => $report->type,
                'config' => $report->config,
                'output_format' => $report->output_format,
                'schedule_enabled' => $report->schedule_enabled,
                'schedule_frequency' => $report->schedule_frequency,
                'schedule_time' => $report->schedule_time,
                'schedule_day' => $report->schedule_day,
                'schedule_recipients' => $report->schedule_recipients,
                'filters' => $report->filters,
            ],
            'reportTypes' => config('shopify.report_types'),
        ]);
    }

    /**
     * Update the specified report in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();
        $report = $store->reports()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:'.implode(',', array_keys(config('shopify.report_types'))),
            'config' => 'required|array',
            'config.date_range' => 'required|array',
            'config.date_range.start' => 'required|date',
            'config.date_range.end' => 'required|date|after_or_equal:config.date_range.start',
            'output_format' => 'required|string|in:pdf,csv',
            'schedule_enabled' => 'boolean',
            'schedule_frequency' => 'required_if:schedule_enabled,true|nullable|string|in:daily,weekly,monthly',
            'schedule_time' => 'required_if:schedule_enabled,true|nullable|string',
            'schedule_day' => 'required_if:schedule_frequency,weekly,monthly|nullable|integer',
            'schedule_recipients' => 'required_if:schedule_enabled,true|nullable|array',
            'schedule_recipients.*' => 'email',
            'filters' => 'nullable|array',
        ]);

        $report->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $report->description,
            'type' => $validated['type'],
            'config' => $validated['config'],
            'output_format' => $validated['output_format'],
            'schedule_enabled' => $validated['schedule_enabled'] ?? false,
            'schedule_frequency' => $validated['schedule_frequency'] ?? null,
            'schedule_time' => $validated['schedule_time'] ?? null,
            'schedule_day' => $validated['schedule_day'] ?? null,
            'schedule_recipients' => $validated['schedule_recipients'] ?? [],
            'filters' => $validated['filters'] ?? [],
        ]);

        return redirect()->route('reports.show', $report->id)
            ->with('success', 'Report updated successfully.');
    }

    /**
     * Remove the specified report from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();
        $report = $store->reports()->findOrFail($id);

        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Report deleted successfully.');
    }

    /**
     * Generate a report.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate($id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();
        $report = $store->reports()->findOrFail($id);

        $result = $this->reportingService->generateReport($report);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Report generated successfully',
                'data' => $result['data'],
                'file' => $result['file'] ?? null,
                'report' => [
                    'id' => $report->id,
                    'name' => $report->name,
                    'last_generated_at' => $report->last_generated_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to generate report',
            ], 500);
        }
    }

    /**
     * Download a report file.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function download($id)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();
        $report = $store->reports()->findOrFail($id);

        // Generate the report if needed
        if (! $report->last_generated_at || $report->last_generated_at->diffInHours(now()) > 1) {
            $result = $this->reportingService->generateReport($report);

            if (! $result['success'] || ! isset($result['file'])) {
                return redirect()->route('reports.show', $report->id)
                    ->with('error', 'Failed to generate report for download.');
            }

            $filePath = $result['file']['path'];
        } else {
            // Determine the expected file path based on naming convention
            $fileName = strtolower(str_replace(' ', '_', $report->name)).'_'.now()->format('Y-m-d');
            $filePath = "reports/{$store->id}/{$fileName}.{$report->output_format}";

            // Check if file exists
            if (! Storage::exists($filePath)) {
                // If not, generate it
                $result = $this->reportingService->generateReport($report);

                if (! $result['success'] || ! isset($result['file'])) {
                    return redirect()->route('reports.show', $report->id)
                        ->with('error', 'Failed to generate report for download.');
                }

                $filePath = $result['file']['path'];
            }
        }

        // Set the appropriate content type
        $contentType = $report->output_format === 'pdf' ? 'application/pdf' : 'text/csv';

        // Return the file as a download
        return response()->download(
            Storage::path($filePath),
            "{$report->name}.{$report->output_format}",
            ['Content-Type' => $contentType]
        );
    }
}
