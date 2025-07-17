<template>
    <div class="dashboard-grid">
        <!-- Simple Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <!-- Sales Overview Widget -->
            <div
                v-if="hasWidget('sales_overview')"
                class="lg:col-span-2 xl:col-span-2"
            >
                <SalesOverviewWidget
                    :data="widgetData.sales_overview || {}"
                    :loading="loading"
                    @remove="$emit('remove-widget', 'sales_overview')"
                />
            </div>

            <!-- Product Performance Widget -->
            <div v-if="hasWidget('product_performance')">
                <ProductPerformanceWidget
                    :data="widgetData.product_performance || {}"
                    :loading="loading"
                    @remove="$emit('remove-widget', 'product_performance')"
                />
            </div>

            <!-- Customer Analytics Widget -->
            <div v-if="hasWidget('customer_analytics')">
                <CustomerAnalyticsWidget
                    :data="widgetData.customer_analytics || {}"
                    :loading="loading"
                    @remove="$emit('remove-widget', 'customer_analytics')"
                />
            </div>

            <!-- Inventory Status Widget -->
            <div v-if="hasWidget('inventory_status')">
                <InventoryStatusWidget
                    :data="widgetData.inventory_status || {}"
                    :loading="loading"
                    @remove="$emit('remove-widget', 'inventory_status')"
                />
            </div>

            <!-- Performance Metrics Widget -->
            <div v-if="hasWidget('performance_metrics')" class="lg:col-span-2">
                <PerformanceMetricsWidget
                    :data="widgetData.performance_metrics || {}"
                    :loading="loading"
                    @remove="$emit('remove-widget', 'performance_metrics')"
                />
            </div>

            <!-- Data Sources Widget -->
            <div v-if="hasWidget('data_sources')">
                <DataSourcesWidget
                    :data="widgetData.data_sources || {}"
                    :loading="loading"
                    @remove="$emit('remove-widget', 'data_sources')"
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

const hasWidget = (widgetType) => {
    return props.layout.some((item) => item.i === widgetType);
};
</script>

<style scoped>
.dashboard-grid {
    @apply w-full;
}
</style>
