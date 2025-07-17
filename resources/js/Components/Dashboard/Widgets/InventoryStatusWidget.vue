<template>
    <div class="widget-container">
        <div class="widget-header">
            <h3 class="widget-title">Inventory Status</h3>
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
            <div class="inventory-overview mb-4">
                <div class="inventory-item">
                    <div class="inventory-label">Total Products</div>
                    <div class="inventory-value">
                        {{ data.total_products || 0 }}
                    </div>
                </div>
            </div>

            <div class="inventory-status">
                <div class="status-item">
                    <div class="status-indicator bg-green-500"></div>
                    <span class="status-label">In Stock</span>
                    <span class="status-count">{{ data.in_stock || 0 }}</span>
                </div>

                <div class="status-item">
                    <div class="status-indicator bg-yellow-500"></div>
                    <span class="status-label">Low Stock</span>
                    <span class="status-count">{{ data.low_stock || 0 }}</span>
                </div>

                <div class="status-item">
                    <div class="status-indicator bg-red-500"></div>
                    <span class="status-label">Out of Stock</span>
                    <span class="status-count">{{
                        data.out_of_stock || 0
                    }}</span>
                </div>
            </div>

            <div
                v-if="lowStockProducts.length > 0"
                class="low-stock-alert mt-4"
            >
                <h4 class="alert-title">Low Stock Alert</h4>
                <div class="alert-products">
                    <div
                        v-for="product in lowStockProducts.slice(0, 3)"
                        :key="product.id"
                        class="alert-product"
                    >
                        {{ product.title || 'Unknown Product' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['remove']);

const lowStockProducts = computed(() => {
    return props.data.low_stock_products || [];
});
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

.inventory-overview {
    @apply text-center bg-gray-50 rounded-lg p-4;
}

.inventory-label {
    @apply text-sm text-gray-600 mb-1;
}

.inventory-value {
    @apply text-2xl font-bold text-gray-900;
}

.inventory-status {
    @apply space-y-3;
}

.status-item {
    @apply flex items-center justify-between p-2 border rounded;
}

.status-indicator {
    @apply w-3 h-3 rounded-full mr-3;
}

.status-label {
    @apply flex-1 text-sm font-medium text-gray-700;
}

.status-count {
    @apply text-sm font-semibold text-gray-900;
}

.low-stock-alert {
    @apply border-t border-gray-200 pt-4;
}

.alert-title {
    @apply text-sm font-medium text-red-600 mb-2;
}

.alert-products {
    @apply space-y-1;
}

.alert-product {
    @apply text-xs text-red-500 truncate;
}
</style>
