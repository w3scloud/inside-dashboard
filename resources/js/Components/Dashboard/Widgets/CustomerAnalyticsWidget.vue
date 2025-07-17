<template>
    <div class="widget-container">
        <div class="widget-header">
            <h3 class="widget-title">Customer Analytics</h3>
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
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="metric-card">
                    <UsersIcon class="w-6 h-6 text-blue-500 mb-2" />
                    <div class="metric-value">
                        {{ formatNumber(data.total_customers) }}
                    </div>
                    <div class="metric-label">Total Customers</div>
                </div>

                <div class="metric-card">
                    <UserPlusIcon class="w-6 h-6 text-green-500 mb-2" />
                    <div class="metric-value">
                        {{ formatNumber(data.new_customers) }}
                    </div>
                    <div class="metric-label">New Customers</div>
                </div>

                <div class="metric-card">
                    <ArrowPathIcon class="w-6 h-6 text-purple-500 mb-2" />
                    <div class="metric-value">
                        {{ formatNumber(data.returning_customers) }}
                    </div>
                    <div class="metric-label">Returning</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    XMarkIcon,
    UsersIcon,
    UserPlusIcon,
    ArrowPathIcon,
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

.metric-card {
    @apply bg-gray-50 rounded-lg p-4 text-center;
}

.metric-value {
    @apply text-2xl font-bold text-gray-900 mb-1;
}

.metric-label {
    @apply text-sm text-gray-600;
}
</style>
