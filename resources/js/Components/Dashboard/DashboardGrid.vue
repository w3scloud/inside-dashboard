<template>
    <div class="dashboard-grid">
        <!-- Debug Info (remove in production) -->
        <div v-if="layout.length === 0" class="text-center py-8">
            <div class="bg-gray-50 rounded-lg p-6">
                <svg
                    class="mx-auto h-12 w-12 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                    No widgets
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Add your first widget to get started with analytics.
                </p>
            </div>
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
                class="min-h-[300px]"
            >
                <!-- Widget not found fallback -->
                <div v-if="!getWidgetComponent(widget.i)" class="h-full">
                    <div
                        class="p-6 bg-yellow-50 rounded-lg border border-yellow-200 h-full flex flex-col justify-center"
                    >
                        <div class="text-center">
                            <svg
                                class="mx-auto h-12 w-12 text-yellow-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
                                />
                            </svg>
                            <h3
                                class="mt-2 text-sm font-medium text-yellow-800"
                            >
                                Widget Not Available
                            </h3>
                            <p class="mt-1 text-sm text-yellow-600">
                                Widget type "{{ getCleanWidgetType(widget.i) }}"
                                not implemented yet
                            </p>
                            <div class="mt-4">
                                <button
                                    @click="$emit('remove-widget', widget.i)"
                                    class="inline-flex items-center px-3 py-2 border border-yellow-300 shadow-sm text-sm leading-4 font-medium rounded-md text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                >
                                    Remove Widget
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Render actual widget -->
                <component
                    :is="getWidgetComponent(widget.i)"
                    :data="getWidgetData(widget.i)"
                    :loading="loading"
                    :widget-config="widget"
                    @remove="$emit('remove-widget', widget.i)"
                    class="h-full"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
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

// Show debug info (set to false in production)
const showDebug = ref(true);

// Widget component mapping
const widgetComponents = {
    sales_overview: SalesOverviewWidget,
    product_performance: ProductPerformanceWidget,
    customer_analytics: CustomerAnalyticsWidget,
    inventory_status: InventoryStatusWidget,
    performance_metrics: PerformanceMetricsWidget,
    data_sources: DataSourcesWidget,

    // Map new widget types to existing components (temporary solution)
    conversion_funnel: SalesOverviewWidget,
    revenue_trends: SalesOverviewWidget,
    traffic_sources: CustomerAnalyticsWidget,
    order_fulfillment: InventoryStatusWidget,
    marketing_roi: PerformanceMetricsWidget,
    geographic_sales: ProductPerformanceWidget,
    seasonal_trends: SalesOverviewWidget,
};

// Get clean widget type (remove timestamp suffix)
const getCleanWidgetType = (widgetType) => {
    if (!widgetType) return '';

    // Handle widget IDs like "conversion_funnel_1752762640_5172"
    const parts = widgetType.split('_');

    // If it has timestamp suffix, take first 2 parts
    if (parts.length > 2 && /^\d+$/.test(parts[parts.length - 1])) {
        return parts.slice(0, 2).join('_');
    }

    // If it's a simple type, return as is
    return widgetType;
};

// Get widget component by type
const getWidgetComponent = (widgetType) => {
    const cleanType = getCleanWidgetType(widgetType);
    return widgetComponents[cleanType];
};

// Get widget data by type
const getWidgetData = (widgetType) => {
    const cleanType = getCleanWidgetType(widgetType);

    // Data mapping for each widget type
    const dataMapping = {
        sales_overview: props.widgetData.sales_overview || {},
        product_performance: props.widgetData.product_performance || {},
        customer_analytics: props.widgetData.customer_analytics || {},
        inventory_status: props.widgetData.inventory_status || {},
        performance_metrics: props.widgetData.performance_metrics || {},
        data_sources: props.widgetData.data_sources || {},

        // Map new widgets to existing data structures
        conversion_funnel:
            props.widgetData.conversion_funnel ||
            props.widgetData.sales_overview ||
            {},
        revenue_trends:
            props.widgetData.revenue_trends ||
            props.widgetData.sales_overview ||
            {},
        traffic_sources:
            props.widgetData.traffic_sources ||
            props.widgetData.customer_analytics ||
            {},
        order_fulfillment:
            props.widgetData.order_fulfillment ||
            props.widgetData.inventory_status ||
            {},
        marketing_roi:
            props.widgetData.marketing_roi ||
            props.widgetData.performance_metrics ||
            {},
        geographic_sales:
            props.widgetData.geographic_sales ||
            props.widgetData.product_performance ||
            {},
        seasonal_trends:
            props.widgetData.seasonal_trends ||
            props.widgetData.sales_overview ||
            {},
    };

    return dataMapping[cleanType] || {};
};

// Get grid class based on widget size and type
const getGridClass = (widget) => {
    // Default to single column
    let colSpan = 'col-span-1';

    // Determine column span based on widget width or type
    const cleanType = getCleanWidgetType(widget.i);
    const width = widget.w || 4;

    // Large widgets
    if (
        width >= 8 ||
        ['conversion_funnel', 'revenue_trends', 'seasonal_trends'].includes(
            cleanType
        )
    ) {
        colSpan = 'xl:col-span-3 lg:col-span-2';
    }
    // Medium widgets
    else if (
        width >= 6 ||
        ['sales_overview', 'performance_metrics'].includes(cleanType)
    ) {
        colSpan = 'lg:col-span-2';
    }
    // Small widgets
    else {
        colSpan = 'col-span-1';
    }

    return colSpan;
};

// Debug computed properties
const debugInfo = computed(() => ({
    layoutCount: props.layout.length,
    widgetDataKeys: Object.keys(props.widgetData),
    mappedWidgets: props.layout.map((w) => ({
        id: w.i,
        cleanType: getCleanWidgetType(w.i),
        hasComponent: !!getWidgetComponent(w.i),
        hasData: Object.keys(getWidgetData(w.i)).length > 0,
    })),
}));

// Console debug (can be removed in production)
console.log('DashboardGrid mounted with:', debugInfo.value);
</script>

<style scoped>
.dashboard-grid {
    @apply w-full;
}

/* Ensure widgets have proper height */
.dashboard-grid .grid > div {
    min-height: 300px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-grid .grid {
        @apply grid-cols-1 gap-4;
    }
}
</style>
