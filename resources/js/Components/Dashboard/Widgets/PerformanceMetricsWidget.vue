<template>
    <div class="widget-container">
        <div class="widget-header">
            <h3 class="widget-title">Performance Metrics</h3>
            <div class="widget-controls">
                <button @click="$emit('remove')" class="btn-icon text-red-600">
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div v-if="loading" class="widget-loading">
            <div
                class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"
            ></div>
        </div>

        <div v-else class="widget-content">
            <div class="metrics-grid">
                <div class="metric-item">
                    <div class="metric-icon">
                        <ChartBarIcon class="w-6 h-6 text-blue-500" />
                    </div>
                    <div class="metric-info">
                        <div class="metric-label">Catalog Health</div>
                        <div class="metric-value">
                            {{ data.catalog_health_score || 0 }}%
                        </div>
                    </div>
                </div>

                <div class="metric-item">
                    <div class="metric-icon">
                        <BoltIcon class="w-6 h-6 text-yellow-500" />
                    </div>
                    <div class="metric-info">
                        <div class="metric-label">Sales Velocity</div>
                        <div class="metric-value">
                            ${{ formatNumber(data.sales_velocity) }}/day
                        </div>
                    </div>
                </div>

                <div class="metric-item">
                    <div class="metric-icon">
                        <CheckCircleIcon class="w-6 h-6 text-green-500" />
                    </div>
                    <div class="metric-info">
                        <div class="metric-label">Order Fulfillment</div>
                        <div class="metric-value">
                            {{ data.order_fulfillment_rate || 0 }}%
                        </div>
                    </div>
                </div>

                <div class="metric-item">
                    <div class="metric-icon">
                        <HeartIcon class="w-6 h-6 text-red-500" />
                    </div>
                    <div class="metric-info">
                        <div class="metric-label">Customer Satisfaction</div>
                        <div class="metric-value">
                            {{ data.customer_satisfaction_score || 0 }}%
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="data.growth_metrics" class="growth-section mt-6">
                <h4 class="section-title">Growth Metrics</h4>
                <div class="growth-items">
                    <div class="growth-item">
                        <span class="growth-label">Sales Growth</span>
                        <span
                            class="growth-value"
                            :class="
                                getGrowthClass(data.growth_metrics.sales_growth)
                            "
                        >
                            {{ formatGrowth(data.growth_metrics.sales_growth) }}
                        </span>
                    </div>
                    <div class="growth-item">
                        <span class="growth-label">Customer Growth</span>
                        <span
                            class="growth-value"
                            :class="
                                getGrowthClass(
                                    data.growth_metrics.customer_growth
                                )
                            "
                        >
                            {{
                                formatGrowth(
                                    data.growth_metrics.customer_growth
                                )
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    XMarkIcon,
    ChartBarIcon,
    BoltIcon,
    CheckCircleIcon,
    HeartIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['remove']);

const formatNumber = (value) => {
    if (!value && value !== 0) return '0';
    return Number(value).toLocaleString();
};

const formatGrowth = (value) => {
    if (!value && value !== 0) return '0%';
    const sign = value > 0 ? '+' : '';
    return `${sign}${Number(value).toFixed(1)}%`;
};

const getGrowthClass = (value) => {
    if (!value && value !== 0) return 'text-gray-500';
    return value > 0
        ? 'text-green-600'
        : value < 0
        ? 'text-red-600'
        : 'text-gray-500';
};
</script>

<style scoped>
.widget-container {
    @apply bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col;
}

.widget-header {
    @apply flex justify-between items-center p-4 border-b border-gray-200;
}

.widget-title {
    @apply text-lg font-medium text-gray-900;
}

.widget-controls {
    @apply flex space-x-2;
}

.btn-icon {
    @apply p-1 rounded hover:bg-gray-100 transition-colors;
}

.widget-loading {
    @apply flex items-center justify-center flex-1 p-8;
}

.widget-content {
    @apply flex-1 p-4;
}

.metrics-grid {
    @apply grid grid-cols-1 sm:grid-cols-2 gap-4;
}

.metric-item {
    @apply flex items-center p-3 bg-gray-50 rounded-lg;
}

.metric-icon {
    @apply mr-3;
}

.metric-info {
    @apply flex-1;
}

.metric-label {
    @apply text-sm text-gray-600;
}

.metric-value {
    @apply text-lg font-semibold text-gray-900;
}

.growth-section {
    @apply border-t border-gray-200 pt-4;
}

.section-title {
    @apply text-sm font-medium text-gray-700 mb-3;
}

.growth-items {
    @apply space-y-2;
}

.growth-item {
    @apply flex justify-between items-center text-sm;
}

.growth-label {
    @apply text-gray-600;
}

.growth-value {
    @apply font-medium;
}
</style>
