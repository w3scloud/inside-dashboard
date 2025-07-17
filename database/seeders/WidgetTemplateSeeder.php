<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WidgetTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=WidgetTemplateSeeder
     */
    public function run(): void
    {
        $now = Carbon::now();

        $templates = [
            [
                'type' => 'sales_overview',
                'name' => 'Sales Overview',
                'description' => 'Key sales metrics and performance indicators including revenue, orders, and growth trends',
                'icon' => 'chart-bar',
                'default_size' => json_encode(['w' => 6, 'h' => 4]),
                'min_size' => json_encode(['w' => 4, 'h' => 3]),
                'max_size' => json_encode(['w' => 12, 'h' => 6]),
                'default_config' => json_encode([
                    'show_total_revenue' => true,
                    'show_total_orders' => true,
                    'show_average_order_value' => true,
                    'show_growth_rate' => true,
                    'chart_type' => 'line',
                    'time_period' => 'last_30_days',
                    'currency_format' => 'USD',
                    'show_comparison' => true,
                ]),
                'available_data_sources' => json_encode(['sales_analytics', 'order_analytics']),
                'supported_chart_types' => json_encode(['line', 'bar', 'area']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'product_performance',
                'name' => 'Product Performance',
                'description' => 'Top performing products, bestsellers, and inventory insights',
                'icon' => 'cube',
                'default_size' => json_encode(['w' => 6, 'h' => 5]),
                'min_size' => json_encode(['w' => 4, 'h' => 4]),
                'max_size' => json_encode(['w' => 12, 'h' => 8]),
                'default_config' => json_encode([
                    'show_top_products' => true,
                    'product_limit' => 10,
                    'sort_by' => 'revenue',
                    'show_product_images' => true,
                    'show_stock_levels' => true,
                    'chart_type' => 'bar',
                ]),
                'available_data_sources' => json_encode(['product_analytics', 'inventory_analytics']),
                'supported_chart_types' => json_encode(['bar', 'pie', 'table', 'horizontal_bar']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'customer_analytics',
                'name' => 'Customer Analytics',
                'description' => 'Customer segmentation, new vs returning customers, and behavior analysis',
                'icon' => 'users',
                'default_size' => json_encode(['w' => 4, 'h' => 4]),
                'min_size' => json_encode(['w' => 3, 'h' => 3]),
                'max_size' => json_encode(['w' => 8, 'h' => 6]),
                'default_config' => json_encode([
                    'show_total_customers' => true,
                    'show_new_customers' => true,
                    'show_returning_customers' => true,
                    'show_customer_lifetime_value' => false,
                    'chart_type' => 'pie',
                    'segment_by' => 'behavior',
                ]),
                'available_data_sources' => json_encode(['customer_analytics', 'sales_analytics']),
                'supported_chart_types' => json_encode(['pie', 'doughnut', 'bar']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'inventory_status',
                'name' => 'Inventory Status',
                'description' => 'Stock levels, low stock alerts, and inventory management insights',
                'icon' => 'archive',
                'default_size' => json_encode(['w' => 4, 'h' => 3]),
                'min_size' => json_encode(['w' => 3, 'h' => 2]),
                'max_size' => json_encode(['w' => 6, 'h' => 5]),
                'default_config' => json_encode([
                    'show_total_products' => true,
                    'show_low_stock_alerts' => true,
                    'low_stock_threshold' => 10,
                    'show_out_of_stock' => true,
                    'alert_color' => 'red',
                    'chart_type' => 'gauge',
                ]),
                'available_data_sources' => json_encode(['inventory_analytics', 'product_analytics']),
                'supported_chart_types' => json_encode(['gauge', 'bar', 'table', 'progress']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'revenue_trends',
                'name' => 'Revenue Trends',
                'description' => 'Revenue trends over time with forecasting and growth analysis',
                'icon' => 'trending-up',
                'default_size' => json_encode(['w' => 8, 'h' => 5]),
                'min_size' => json_encode(['w' => 6, 'h' => 4]),
                'max_size' => json_encode(['w' => 12, 'h' => 8]),
                'default_config' => json_encode([
                    'time_period' => 'last_30_days',
                    'show_forecast' => true,
                    'comparison_period' => 'previous_period',
                    'show_trend_line' => true,
                    'chart_type' => 'line',
                    'include_taxes' => true,
                ]),
                'available_data_sources' => json_encode(['sales_analytics', 'revenue_analytics']),
                'supported_chart_types' => json_encode(['line', 'area', 'bar']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'traffic_sources',
                'name' => 'Traffic Sources',
                'description' => 'Website traffic sources and customer acquisition channels',
                'icon' => 'globe',
                'default_size' => json_encode(['w' => 5, 'h' => 4]),
                'min_size' => json_encode(['w' => 4, 'h' => 3]),
                'max_size' => json_encode(['w' => 8, 'h' => 6]),
                'default_config' => json_encode([
                    'show_top_sources' => true,
                    'source_limit' => 8,
                    'show_conversion_rates' => false,
                    'group_similar_sources' => true,
                    'chart_type' => 'pie',
                ]),
                'available_data_sources' => json_encode(['traffic_analytics', 'marketing_analytics']),
                'supported_chart_types' => json_encode(['pie', 'doughnut', 'bar']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'order_fulfillment',
                'name' => 'Order Fulfillment',
                'description' => 'Order processing status, fulfillment rates, and shipping analytics',
                'icon' => 'truck',
                'default_size' => json_encode(['w' => 4, 'h' => 3]),
                'min_size' => json_encode(['w' => 3, 'h' => 2]),
                'max_size' => json_encode(['w' => 6, 'h' => 5]),
                'default_config' => json_encode([
                    'show_pending_orders' => true,
                    'show_fulfilled_orders' => true,
                    'show_shipping_status' => true,
                    'show_average_fulfillment_time' => true,
                    'chart_type' => 'progress',
                ]),
                'available_data_sources' => json_encode(['order_analytics', 'fulfillment_analytics']),
                'supported_chart_types' => json_encode(['progress', 'bar', 'table']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'conversion_funnel',
                'name' => 'Conversion Funnel',
                'description' => 'Sales funnel analysis from visitors to customers',
                'icon' => 'funnel',
                'default_size' => json_encode(['w' => 6, 'h' => 5]),
                'min_size' => json_encode(['w' => 4, 'h' => 4]),
                'max_size' => json_encode(['w' => 8, 'h' => 7]),
                'default_config' => json_encode([
                    'show_visitor_to_customer' => true,
                    'show_cart_abandonment' => true,
                    'show_conversion_rates' => true,
                    'funnel_steps' => ['visitors', 'product_views', 'add_to_cart', 'checkout', 'purchase'],
                    'chart_type' => 'funnel',
                ]),
                'available_data_sources' => json_encode(['conversion_analytics', 'traffic_analytics']),
                'supported_chart_types' => json_encode(['funnel', 'bar']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'marketing_roi',
                'name' => 'Marketing ROI',
                'description' => 'Marketing campaign performance and return on investment',
                'icon' => 'chart-line',
                'default_size' => json_encode(['w' => 6, 'h' => 4]),
                'min_size' => json_encode(['w' => 4, 'h' => 3]),
                'max_size' => json_encode(['w' => 10, 'h' => 6]),
                'default_config' => json_encode([
                    'show_campaign_performance' => true,
                    'show_roi_calculation' => true,
                    'show_cost_per_acquisition' => true,
                    'include_organic_traffic' => false,
                    'chart_type' => 'bar',
                ]),
                'available_data_sources' => json_encode(['marketing_analytics', 'sales_analytics']),
                'supported_chart_types' => json_encode(['bar', 'line', 'table']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'geographic_sales',
                'name' => 'Geographic Sales',
                'description' => 'Sales distribution by country, region, and city',
                'icon' => 'map',
                'default_size' => json_encode(['w' => 6, 'h' => 5]),
                'min_size' => json_encode(['w' => 4, 'h' => 4]),
                'max_size' => json_encode(['w' => 8, 'h' => 7]),
                'default_config' => json_encode([
                    'show_country_breakdown' => true,
                    'show_top_cities' => true,
                    'map_type' => 'world',
                    'color_scheme' => 'blue',
                    'chart_type' => 'map',
                ]),
                'available_data_sources' => json_encode(['geographic_analytics', 'sales_analytics']),
                'supported_chart_types' => json_encode(['map', 'bar', 'table']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'seasonal_trends',
                'name' => 'Seasonal Trends',
                'description' => 'Seasonal sales patterns and trending products by time periods',
                'icon' => 'calendar',
                'default_size' => json_encode(['w' => 8, 'h' => 5]),
                'min_size' => json_encode(['w' => 6, 'h' => 4]),
                'max_size' => json_encode(['w' => 12, 'h' => 7]),
                'default_config' => json_encode([
                    'time_granularity' => 'monthly',
                    'show_year_over_year' => true,
                    'highlight_peaks' => true,
                    'show_seasonal_products' => true,
                    'chart_type' => 'line',
                ]),
                'available_data_sources' => json_encode(['seasonal_analytics', 'sales_analytics']),
                'supported_chart_types' => json_encode(['line', 'area', 'heatmap']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Clear existing templates first (optional)
        DB::table('widget_templates')->truncate();

        // Insert all templates
        foreach ($templates as $template) {
            DB::table('widget_templates')->updateOrInsert(
                ['type' => $template['type']],
                $template
            );
        }

        $this->command->info('Widget templates seeded successfully!');
        $this->command->info('Total templates created: '.count($templates));
    }
}
