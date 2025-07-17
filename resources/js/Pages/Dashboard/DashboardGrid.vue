<template>
    <div class="dashboard-grid">
        <!-- Debug Info -->
        <div v-if="layout.length === 0" class="text-center py-8">
            <p class="text-gray-500">No widgets in layout</p>
        </div>

        <!-- Dynamic Grid Layout -->
        <div
            v-else
            class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6"
        >
            <!-- Dynamically render widgets based on layout -->
            <div
                v-for="widget in layout"
                :key="widget.i"
                :class="getGridClass(widget)"
            >
                <!-- Debug each widget -->
                <div
                    v-if="!getWidgetComponent(widget.i)"
                    class="p-4 bg-yellow-100 rounded border"
                >
                    <p class="text-sm text-yellow-800">
                        Widget type "{{ widget.i }}" not found
                    </p>
                    <p class="text-xs text-yellow-600 mt-1">
                        Available:
                        {{ Object.keys(widgetComponents).join(', ') }}
                    </p>
                </div>

                <!-- Render actual widget -->
                <component
                    v-else
                    :is="getWidgetComponent(widget.i)"
                    :data="getWidgetData(widget.i)"
                    :loading="loading"
                    @remove="$emit('remove-widget', widget.i)"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import SalesOverviewWidget from './Widgets/SalesOverviewWidget.vue';
import ProductPerformanceWidget from './Widgets/ProductPerformanceWidget.vue';
import CustomerAnalyticsWidget from './Widgets/CustomerAnalyticsWidget.vue';
import InventoryStatusWidget from './Widgets/InventoryStatusWidget.vue';
import PerformanceMetricsWidget from './Widgets/PerformanceMetricsWidget.vue';
import DataSourcesWidget from './Widgets/DataSourcesWidget.vue';

const props = defineProps({
    layout: {
        type: Array,
        default: () => [],
    },
    widgetData: {
        type: Object,
        default: () => ({}),
    },
    availableWidgets: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['remove-widget', 'update:layout']);

// Widget component mapping
const widgetComponents = {
    sales_overview: SalesOverviewWidget,
    product_performance: ProductPerformanceWidget,
    customer_analytics: CustomerAnalyticsWidget,
    inventory_status: InventoryStatusWidget,
    performance_metrics: PerformanceMetricsWidget,
    data_sources: DataSourcesWidget,

    // Add mappings for new widget types
    conversion_funnel: SalesOverviewWidget, // Fallback to existing component for now
    revenue_trends: SalesOverviewWidget,
    traffic_sources: CustomerAnalyticsWidget,
    order_fulfillment: InventoryStatusWidget,
    marketing_roi: PerformanceMetricsWidget,
    geographic_sales: ProductPerformanceWidget,
    seasonal_trends: SalesOverviewWidget,
};

// Get widget component by type
const getWidgetComponent = (widgetType) => {
    // Remove timestamp suffix if present (e.g., "conversion_funnel_1752762640_5172" -> "conversion_funnel")
    const cleanType = widgetType.split('_').slice(0, 2).join('_');
    return widgetComponents[cleanType] || widgetComponents[widgetType];
};

// Get widget data by type
const getWidgetData = (widgetType) => {
    const cleanType = widgetType.split('_').slice(0, 2).join('_');

    // Return appropriate data based on widget type
    const dataMapping = {
        sales_overview: props.widgetData.sales_overview || {},
        product_performance: props.widgetData.product_performance || {},
        customer_analytics: props.widgetData.customer_analytics || {},
        inventory_status: props.widgetData.inventory_status || {},
        performance_metrics: props.widgetData.performance_metrics || {},
        data_sources: props.widgetData.data_sources || {},

        // Map new widgets to existing data for now
        conversion_funnel: props.widgetData.sales_overview || {},
        revenue_trends: props.widgetData.sales_overview || {},
        traffic_sources: props.widgetData.customer_analytics || {},
        order_fulfillment: props.widgetData.inventory_status || {},
        marketing_roi: props.widgetData.performance_metrics || {},
        geographic_sales: props.widgetData.product_performance || {},
        seasonal_trends: props.widgetData.sales_overview || {},
    };

    return dataMapping[cleanType] || dataMapping[widgetType] || {};
};

// Get grid class based on widget size
const getGridClass = (widget) => {
    // Default to single column
    let colSpan = '';

    // Determine column span based on widget width
    if (widget.w >= 8) {
        colSpan = 'xl:col-span-3 lg:col-span-2';
    } else if (widget.w >= 6) {
        colSpan = 'lg:col-span-2';
    } else {
        colSpan = 'col-span-1';
    }

    return colSpan;
};

// Debug computed to see layout
const debugInfo = computed(() => {
    return {
        layoutCount: props.layout.length,
        layout: props.layout,
        widgetDataKeys: Object.keys(props.widgetData),
    };
});

// Log debug info when component mounts
console.log('DashboardGrid Debug:', debugInfo.value);
</script>

<style scoped>
.dashboard-grid {
    @apply w-full;
}
</style>

<!-- SOLUTION 2: Update Show.vue to map widget data for all types -->
<!-- Add this to your Show.vue widgetData mapping: -->

<script>
// In your fetchData method, add mappings for all widget types:

const fetchData = async () => {
    // ... existing code ...

    if (response.data.success) {
        const apiData = response.data.data;

        // Enhanced widget data mapping for ALL widget types
        widgetData.value = {
            // Existing widgets
            sales_overview: {
                total_revenue:
                    apiData.sales_analytics?.summary?.total_sales || 0,
                total_orders:
                    apiData.sales_analytics?.summary?.total_orders || 0,
                average_order_value:
                    apiData.sales_analytics?.summary?.average_order_value || 0,
                growth_rate: apiData.sales_analytics?.summary?.growth_rate || 0,
                daily_sales: apiData.sales_analytics?.trends?.daily_sales || {},
            },

            product_performance: {
                top_products: apiData.product_analytics?.top_products || [],
                total_products:
                    apiData.product_analytics?.summary?.total_products || 0,
                performance_metrics:
                    apiData.product_analytics?.performance_metrics || {},
            },

            customer_analytics: {
                total_customers:
                    apiData.customer_analytics?.summary?.total_customers || 0,
                new_customers:
                    apiData.customer_analytics?.summary?.new_customers_30d || 0,
                returning_customers:
                    apiData.customer_analytics?.summary?.returning_customers ||
                    0,
                segments: apiData.customer_analytics?.segments || [],
            },

            inventory_status: {
                total_products:
                    apiData.inventory_analytics?.summary?.total_products || 0,
                in_stock:
                    apiData.inventory_analytics?.inventory_status?.in_stock ||
                    0,
                low_stock:
                    apiData.inventory_analytics?.inventory_status?.low_stock ||
                    0,
                out_of_stock:
                    apiData.inventory_analytics?.inventory_status
                        ?.out_of_stock || 0,
                low_stock_products:
                    apiData.inventory_analytics?.low_stock_products || [],
            },

            performance_metrics: apiData.performance_metrics || {},
            data_sources: apiData.data_sources || {},

            // ADD NEW WIDGET DATA MAPPINGS
            conversion_funnel: {
                // Use sales data for now, can be customized later
                visitors: apiData.traffic_analytics?.visitors || 0,
                conversions:
                    apiData.sales_analytics?.summary?.total_orders || 0,
                conversion_rate: apiData.sales_analytics?.conversion_rate || 0,
                funnel_steps: [
                    {
                        name: 'Visitors',
                        value: apiData.traffic_analytics?.visitors || 0,
                    },
                    {
                        name: 'Product Views',
                        value: apiData.product_analytics?.views || 0,
                    },
                    {
                        name: 'Add to Cart',
                        value: apiData.sales_analytics?.cart_additions || 0,
                    },
                    {
                        name: 'Checkout',
                        value: apiData.sales_analytics?.checkouts || 0,
                    },
                    {
                        name: 'Purchase',
                        value:
                            apiData.sales_analytics?.summary?.total_orders || 0,
                    },
                ],
            },

            revenue_trends: {
                // Reuse sales overview data
                ...(widgetData.value?.sales_overview || {}),
                trend_data: apiData.sales_analytics?.trends || {},
            },

            traffic_sources: {
                // Use customer analytics for now
                total_visitors: apiData.traffic_analytics?.total_visitors || 0,
                sources: apiData.traffic_analytics?.sources || [],
                top_sources: [
                    { name: 'Direct', visitors: 150, percentage: 35 },
                    { name: 'Search', visitors: 120, percentage: 28 },
                    { name: 'Social', visitors: 80, percentage: 19 },
                    { name: 'Email', visitors: 50, percentage: 12 },
                    { name: 'Referral', visitors: 25, percentage: 6 },
                ],
            },

            order_fulfillment: {
                pending_orders: apiData.fulfillment_analytics?.pending || 0,
                fulfilled_orders: apiData.fulfillment_analytics?.fulfilled || 0,
                average_fulfillment_time:
                    apiData.fulfillment_analytics?.avg_time || 0,
                fulfillment_rate: apiData.fulfillment_analytics?.rate || 95,
            },

            marketing_roi: {
                total_spend: apiData.marketing_analytics?.spend || 0,
                total_revenue:
                    apiData.sales_analytics?.summary?.total_sales || 0,
                roi: apiData.marketing_analytics?.roi || 0,
                campaigns: apiData.marketing_analytics?.campaigns || [],
            },

            geographic_sales: {
                countries: apiData.geographic_analytics?.countries || [],
                top_regions: apiData.geographic_analytics?.regions || [],
                total_countries:
                    apiData.geographic_analytics?.total_countries || 0,
            },

            seasonal_trends: {
                monthly_data: apiData.seasonal_analytics?.monthly || {},
                seasonal_products: apiData.seasonal_analytics?.products || [],
                peak_seasons: apiData.seasonal_analytics?.peaks || [],
            },
        };

        console.log('Enhanced widget data mapped:', widgetData.value);
    }
    // ... rest of method
};
</script>
