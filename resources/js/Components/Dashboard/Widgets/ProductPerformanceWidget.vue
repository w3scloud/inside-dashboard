<template>
    <div class="widget-container">
        <div class="widget-header">
            <h3 class="widget-title">Product Performance</h3>
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
            <p class="text-sm text-gray-600 mt-2">Loading product data...</p>
        </div>

        <div v-else-if="error" class="widget-error">
            <ExclamationTriangleIcon class="w-6 h-6 text-red-500 mb-2" />
            <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <div v-else class="widget-content">
            <!-- Summary Stats -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="stat-card">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value">{{ data.total_products || 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Top Performer</div>
                    <div class="stat-value text-sm">
                        {{ topProduct?.title?.substring(0, 20) || 'N/A'
                        }}{{ topProduct?.title?.length > 20 ? '...' : '' }}
                    </div>
                </div>
            </div>

            <!-- Top Products List -->
            <div class="top-products">
                <h4 class="section-title">Top Products</h4>

                <div v-if="topProducts.length > 0" class="product-list">
                    <div
                        v-for="(product, index) in topProducts.slice(0, 5)"
                        :key="product.id || index"
                        class="product-item"
                    >
                        <div class="product-rank">{{ index + 1 }}</div>
                        <div class="product-info">
                            <div class="product-name">
                                {{ product.title || 'Unknown Product' }}
                            </div>
                            <div class="product-stats">
                                <span class="product-revenue"
                                    >${{ formatNumber(product.revenue) }}</span
                                >
                                <span class="product-quantity"
                                    >{{ product.quantity_sold || 0 }} sold</span
                                >
                            </div>
                        </div>
                        <div class="product-bar">
                            <div
                                class="bar-fill"
                                :style="{
                                    width:
                                        getBarWidth(
                                            product.revenue,
                                            maxRevenue
                                        ) + '%',
                                }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div v-else class="no-products">
                    <CubeIcon class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                    <p class="text-sm text-gray-500">
                        No product data available
                    </p>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div
                v-if="data.performance_metrics"
                class="performance-metrics mt-6"
            >
                <h4 class="section-title">Performance Insights</h4>
                <div class="metrics-grid">
                    <div class="metric-item">
                        <span class="metric-label">Revenue Concentration</span>
                        <span class="metric-value"
                            >{{
                                data.performance_metrics
                                    .revenue_concentration || 0
                            }}%</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    XMarkIcon,
    ExclamationTriangleIcon,
    CubeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            top_products: [],
            total_products: 0,
            performance_metrics: {},
        }),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['remove']);

const topProducts = computed(() => {
    return props.data.top_products || [];
});

const topProduct = computed(() => {
    return topProducts.value[0] || null;
});

const maxRevenue = computed(() => {
    if (topProducts.value.length === 0) return 1;
    return Math.max(...topProducts.value.map((p) => p.revenue || 0));
});

const formatNumber = (value) => {
    if (!value && value !== 0) return '0';
    return Number(value).toLocaleString();
};

const getBarWidth = (revenue, max) => {
    if (!max || !revenue) return 0;
    return Math.min((revenue / max) * 100, 100);
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
    @apply flex flex-col items-center justify-center flex-1 p-8;
}

.widget-error {
    @apply flex flex-col items-center justify-center flex-1 p-8;
}

.widget-content {
    @apply flex-1 p-4 overflow-hidden;
}

.stat-card {
    @apply bg-gray-50 rounded-lg p-3 text-center;
}

.stat-label {
    @apply text-xs font-medium text-gray-500 mb-1;
}

.stat-value {
    @apply text-lg font-semibold text-gray-900;
}

.section-title {
    @apply text-sm font-medium text-gray-700 mb-3;
}

.product-list {
    @apply space-y-3;
}

.product-item {
    @apply flex items-center space-x-3 p-2 hover:bg-gray-50 rounded;
}

.product-rank {
    @apply flex-shrink-0 w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-medium;
}

.product-info {
    @apply flex-1 min-w-0;
}

.product-name {
    @apply text-sm font-medium text-gray-900 truncate;
}

.product-stats {
    @apply flex space-x-2 text-xs text-gray-500 mt-1;
}

.product-revenue {
    @apply font-medium text-green-600;
}

.product-quantity {
    @apply text-gray-500;
}

.product-bar {
    @apply flex-shrink-0 w-16 h-2 bg-gray-200 rounded-full overflow-hidden;
}

.bar-fill {
    @apply h-full bg-indigo-500 transition-all duration-300;
}

.no-products {
    @apply text-center py-6;
}

.performance-metrics {
    @apply border-t border-gray-200 pt-4;
}

.metrics-grid {
    @apply space-y-2;
}

.metric-item {
    @apply flex justify-between items-center text-sm;
}

.metric-label {
    @apply text-gray-600;
}

.metric-value {
    @apply font-medium text-gray-900;
}
</style>
