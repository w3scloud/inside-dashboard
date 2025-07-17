<template>
    <div class="widget-container">
        <div class="widget-header">
            <h3 class="widget-title">Data Sources</h3>
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
            <div class="data-sources">
                <div class="source-section">
                    <h4 class="source-title">GraphQL API</h4>
                    <div class="source-items">
                        <div
                            v-for="(source, key) in graphqlSources"
                            :key="key"
                            class="source-item"
                        >
                            <div
                                class="source-status"
                                :class="getStatusClass(source.status)"
                            ></div>
                            <span class="source-name">{{
                                formatSourceName(key)
                            }}</span>
                            <span class="source-message">{{
                                source.message
                            }}</span>
                        </div>
                    </div>
                </div>

                <div class="source-section">
                    <h4 class="source-title">REST API</h4>
                    <div class="source-item">
                        <div
                            class="source-status"
                            :class="getStatusClass(restApiStatus)"
                        ></div>
                        <span class="source-name">Orders & Customers</span>
                        <span class="source-message">{{ restApiMessage }}</span>
                    </div>
                </div>

                <div class="last-check">
                    <span class="check-label">Last Check:</span>
                    <span class="check-time">{{
                        formatTime(data.last_check)
                    }}</span>
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

const graphqlSources = computed(() => {
    return props.data.graphql_api || {};
});

const restApiStatus = computed(() => {
    return props.data.rest_api?.status || 'unknown';
});

const restApiMessage = computed(() => {
    return props.data.rest_api?.message || 'Status unknown';
});

const getStatusClass = (status) => {
    switch (status) {
        case 'working':
            return 'bg-green-500';
        case 'error':
            return 'bg-red-500';
        case 'limited':
            return 'bg-yellow-500';
        default:
            return 'bg-gray-400';
    }
};

const formatSourceName = (key) => {
    return key.charAt(0).toUpperCase() + key.slice(1);
};

const formatTime = (timestamp) => {
    if (!timestamp) return 'Never';
    return new Date(timestamp).toLocaleString();
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

.source-section {
    @apply mb-4;
}

.source-title {
    @apply text-sm font-medium text-gray-700 mb-2;
}

.source-items {
    @apply space-y-2;
}

.source-item {
    @apply flex items-center space-x-3 text-sm;
}

.source-status {
    @apply w-3 h-3 rounded-full;
}

.source-name {
    @apply font-medium text-gray-900;
}

.source-message {
    @apply text-gray-500 text-xs;
}

.last-check {
    @apply flex justify-between items-center text-xs text-gray-500 border-t border-gray-200 pt-3 mt-4;
}

.check-label {
    @apply font-medium;
}

.check-time {
    @apply text-gray-400;
}
</style>
