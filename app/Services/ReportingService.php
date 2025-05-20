<?php

namespace App\Services;

use App\Models\Report;
use Barryvdh\DomPdf\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class ReportingService
{
    protected $shopifyService;

    protected $analyticsService;

    /**
     * Create a new service instance.
     */
    public function __construct(
        ShopifyService $shopifyService,
        AnalyticsService $analyticsService
    ) {
        $this->shopifyService = $shopifyService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Generate a report.
     */
    public function generateReport(Report $report): array
    {
        try {
            Log::info('Generating report', ['report_id' => $report->id, 'type' => $report->type]);

            $data = $this->getReportData($report);

            if (empty($data)) {
                Log::error('Failed to generate report data', ['report_id' => $report->id]);

                return [
                    'success' => false,
                    'message' => 'Failed to generate report data',
                ];
            }

            $filePath = $this->createReportFile($report, $data);

            // Update last generated timestamp
            $report->updateLastGenerated();

            return [
                'success' => true,
                'data' => $data,
                'file' => [
                    'path' => $filePath,
                    'url' => Storage::url($filePath),
                    'format' => $report->output_format,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Exception generating report', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error generating report: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get report data based on report type.
     */
    protected function getReportData(Report $report): array
    {
        $store = $report->store;

        if (! $store) {
            return [];
        }

        // Get date range from report config
        $dateRange = $report->config['date_range'] ?? null;

        if (! $dateRange) {
            return [];
        }

        $startDate = Carbon::parse($dateRange['start']);
        $endDate = Carbon::parse($dateRange['end']);
        $filters = $report->filters ?? [];

        switch ($report->type) {
            case 'sales_summary':
                return $this->getSalesSummaryData($store, $startDate, $endDate, $filters);

            case 'product_performance':
                return $this->getProductPerformanceData($store, $startDate, $endDate, $filters);

            case 'inventory_status':
                return $this->getInventoryStatusData($store, $filters);

            case 'customer_insights':
                return $this->getCustomerInsightsData($store, $startDate, $endDate, $filters);

            default:
                Log::warning('Unknown report type', ['type' => $report->type]);

                return [];
        }
    }

    /**
     * Get sales summary data.
     */
    protected function getSalesSummaryData($store, $startDate, $endDate, $filters): array
    {
        // Get orders in date range
        $params = [
            'created_at_min' => $startDate->toIso8601String(),
            'created_at_max' => $endDate->toIso8601String(),
            'limit' => 250,
            'status' => 'any',
        ];

        $ordersData = $this->shopifyService->getOrders($store, $params);
        $orders = $ordersData['orders'] ?? [];

        if (empty($orders)) {
            return [
                'title' => 'Sales Summary',
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'metrics' => [],
                'orders' => [],
                'timeline' => [],
            ];
        }

        // Process orders
        $totalSales = 0;
        $totalOrders = 0;
        $ordersTimeline = [];
        $processedOrders = [];

        foreach ($orders as $order) {
            // Skip cancelled orders
            if ($order['cancelled_at'] !== null) {
                continue;
            }

            $orderDate = substr($order['created_at'], 0, 10);
            $totalSales += (float) $order['total_price'];
            $totalOrders++;

            // Add to timeline
            if (! isset($ordersTimeline[$orderDate])) {
                $ordersTimeline[$orderDate] = [
                    'date' => $orderDate,
                    'sales' => 0,
                    'orders' => 0,
                ];
            }

            $ordersTimeline[$orderDate]['sales'] += (float) $order['total_price'];
            $ordersTimeline[$orderDate]['orders']++;

            // Add to processed orders
            $processedOrders[] = [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'created_at' => $order['created_at'],
                'total_price' => (float) $order['total_price'],
                'financial_status' => $order['financial_status'],
                'fulfillment_status' => $order['fulfillment_status'] ?? 'unfulfilled',
                'customer' => [
                    'id' => $order['customer']['id'],
                    'email' => $order['customer']['email'],
                    'name' => $order['customer']['first_name'].' '.$order['customer']['last_name'],
                ],
            ];
        }

        // Convert timeline to array and sort by date
        $timeline = array_values($ordersTimeline);

        usort($timeline, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        // Calculate metrics
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        // Sort orders by date (newest first)
        usort($processedOrders, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return [
            'title' => 'Sales Summary',
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'metrics' => [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'avg_order_value' => $avgOrderValue,
            ],
            'orders' => $processedOrders,
            'timeline' => $timeline,
        ];
    }

    /**
     * Get product performance data.
     */
    protected function getProductPerformanceData($store, $startDate, $endDate, $filters): array
    {
        // Use analytics service to get product performance
        $productPerformance = $this->analyticsService->getProductPerformance(
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

        return [
            'title' => 'Product Performance',
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'metrics' => [
                'total_sales' => $productPerformance['total_sales'],
                'total_products' => $productSummary['total_products'],
                'active_products' => $productSummary['active_products'],
            ],
            'products' => $productPerformance['products'],
            'timeline' => $productPerformance['timeline'],
            'top_selling' => $productSummary['top_selling'],
            'low_selling' => $productSummary['low_selling'],
        ];
    }

    /**
     * Get inventory status data.
     */
    protected function getInventoryStatusData($store, $filters): array
    {
        // Use analytics service to get inventory status
        $inventoryStatus = $this->analyticsService->getInventoryStatus($store, $filters);

        // Get inventory summary
        $inventorySummary = $this->analyticsService->getInventorySummary($store, $filters);

        return [
            'title' => 'Inventory Status',
            'metrics' => [
                'total_items' => $inventorySummary['total_items'],
                'out_of_stock' => $inventorySummary['out_of_stock'],
                'low_stock' => $inventorySummary['low_stock'],
                'in_stock' => $inventorySummary['in_stock'],
            ],
            'inventory' => $inventoryStatus['inventory'],
            'stock_status' => $inventorySummary['stock_status'],
        ];
    }

    /**
     * Get customer insights data.
     */
    protected function getCustomerInsightsData($store, $startDate, $endDate, $filters): array
    {
        // Use analytics service to get customer data
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

        // Get customer segments
        $customerSegments = $this->analyticsService->getCustomerSegments(
            $store,
            $startDate,
            $endDate,
            $filters
        );

        return [
            'title' => 'Customer Insights',
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'metrics' => [
                'total_customers' => $customerSummary['total_customers'],
                'new_customers' => $customerSummary['new_customers'],
                'returning_customers' => $customerSummary['returning_customers'],
                'total_revenue' => $customerSummary['total_revenue'],
                'avg_order_value' => $customerSummary['avg_order_value'],
                'avg_customer_value' => $customerSummary['avg_customer_value'],
            ],
            'customers' => $customerData['customers'],
            'timeline' => $customerData['timeline'],
            'top_customers' => $customerSummary['top_customers'],
            'segments' => $customerSegments['segments'],
        ];
    }

    /**
     * Create a report file.
     */
    protected function createReportFile(Report $report, array $data): string
    {
        $store = $report->store;
        $format = $report->output_format;
        $fileName = strtolower(str_replace(' ', '_', $report->name)).'_'.now()->format('Y-m-d');
        $filePath = "reports/{$store->id}/{$fileName}.{$format}";

        if ($format === 'pdf') {
            return $this->createPdfReport($report, $data, $filePath);
        } else {
            return $this->createCsvReport($report, $data, $filePath);
        }
    }

    /**
     * Create a PDF report.
     */
    protected function createPdfReport(Report $report, array $data, string $filePath): string
    {
        $store = $report->store;
        $title = $data['title'] ?? 'Report';
        $dateRange = $data['date_range'] ?? ['start' => now()->format('Y-m-d'), 'end' => now()->format('Y-m-d')];

        $html = view('reports.pdf.'.$report->type, [
            'title' => $title,
            'store' => $store,
            'report' => $report,
            'date_range' => $dateRange,
            'data' => $data,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ])->render();

        $pdf = Pdf::loadHTML($html);

        Storage::put($filePath, $pdf->output());

        return $filePath;
    }

    /**
     * Create a CSV report.
     */
    protected function createCsvReport(Report $report, array $data, string $filePath): string
    {
        $rows = $this->prepareCsvData($report->type, $data);

        // Create CSV
        $csv = Writer::createFromString('');
        $csv->insertOne(array_keys($rows[0] ?? []));
        $csv->insertAll($rows);

        Storage::put($filePath, $csv->getContent());

        return $filePath;
    }

    /**
     * Prepare data for CSV export.
     */
    protected function prepareCsvData(string $reportType, array $data): array
    {
        $rows = [];

        switch ($reportType) {
            case 'sales_summary':
                // Export orders
                foreach ($data['orders'] as $order) {
                    $rows[] = [
                        'Order Number' => $order['order_number'],
                        'Date' => substr($order['created_at'], 0, 10),
                        'Customer' => $order['customer']['name'],
                        'Email' => $order['customer']['email'],
                        'Total' => $order['total_price'],
                        'Financial Status' => $order['financial_status'],
                        'Fulfillment Status' => $order['fulfillment_status'],
                    ];
                }
                break;

            case 'product_performance':
                // Export products
                foreach ($data['products'] as $product) {
                    $rows[] = [
                        'Product ID' => $product['id'],
                        'Title' => $product['title'],
                        'Vendor' => $product['vendor'],
                        'Product Type' => $product['product_type'],
                        'Total Sales' => $product['total_sales'],
                        'Total Quantity' => $product['total_quantity'],
                        'Orders Count' => $product['orders_count'],
                    ];
                }
                break;

            case 'inventory_status':
                // Export inventory
                foreach ($data['inventory'] as $item) {
                    $rows[] = [
                        'Inventory Item ID' => $item['inventory_item_id'],
                        'Total Available' => $item['total_available'],
                        'Status' => $item['status'],
                    ];
                }
                break;

            case 'customer_insights':
                // Export customers
                foreach ($data['customers'] as $customer) {
                    $rows[] = [
                        'Customer ID' => $customer['id'],
                        'Email' => $customer['email'],
                        'Name' => $customer['first_name'].' '.$customer['last_name'],
                        'Orders Count' => $customer['orders_count'],
                        'Total Spent' => $customer['total_spent'],
                        'Created At' => $customer['created_at'],
                        'Accepts Marketing' => $customer['accepts_marketing'] ? 'Yes' : 'No',
                    ];
                }
                break;
        }

        return $rows;
    }
}
