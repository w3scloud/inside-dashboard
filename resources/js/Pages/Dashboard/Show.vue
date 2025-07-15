<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardGrid from '@/Components/Dashboard/DashboardGrid.vue';
import Widget from '@/Components/Dashboard/Widget.vue';
import DateRangePicker from '@/Components/Dashboard/DateRangePicker.vue';
import AddWidgetModal from '@/Components/Dashboard/AddWidgetModal.vue';
import axios from 'axios';

// Props from Laravel
const props = defineProps({
    dashboard: Object,
    store: Object,
    availableWidgets: Array,
});

// Reactive data
const loading = ref(false);
const widgetData = ref({});
const showAddWidgetModal = ref(false);
const refreshInterval = ref(null);

// FIX: Initialize dateRange with default values
const dateRange = ref({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split('T')[0], // 30 days ago
    end: new Date().toISOString().split('T')[0], // today
});

// Layout computed property
const layout = computed({
    get() {
        return props.dashboard?.layout || [];
    },
    set(newLayout) {
        updateLayout(newLayout);
    },
});

// Methods
const fetchData = async () => {
    if (!props.dashboard?.id || !props.store?.id) {
        console.error('Missing dashboard or store ID');
        return;
    }

    loading.value = true;

    try {
        console.log('Fetching dashboard data...', {
            dashboard_id: props.dashboard.id,
            store_id: props.store.id,
            date_range: dateRange.value,
        });

        const response = await axios.get(
            `/dashboard/${props.dashboard.id}/fetch-data`,
            {
                params: {
                    start_date: dateRange.value.start,
                    end_date: dateRange.value.end,
                    store_id: props.store.id,
                },
            }
        );

        console.log('Dashboard data response:', response.data);

        if (response.data.success) {
            widgetData.value = response.data.data || {};

            // Log summary for debugging
            console.log('Dashboard summary:', response.data.summary);
        } else {
            console.error('Dashboard API error:', response.data.message);
            // Show error to user
            alert('Failed to load dashboard data: ' + response.data.message);
        }
    } catch (error) {
        console.error('Error fetching dashboard data:', error);

        // More detailed error logging
        if (error.response) {
            console.error('Error response:', error.response.data);
            alert(
                'Server error: ' +
                    (error.response.data.message || error.message)
            );
        } else {
            alert('Network error: ' + error.message);
        }
    } finally {
        loading.value = false;
    }
};

const handleDateRangeChange = (newDateRange) => {
    console.log('Date range changed:', newDateRange);

    // FIX: Ensure proper date format
    dateRange.value = {
        start: newDateRange.start || dateRange.value.start,
        end: newDateRange.end || dateRange.value.end,
    };

    // Fetch new data
    fetchData();
};

const updateLayout = async (newLayout) => {
    try {
        await axios.put(`/dashboard/${props.dashboard.id}/layout`, {
            layout: newLayout,
        });

        console.log('Layout updated successfully');
    } catch (error) {
        console.error('Error updating layout:', error);
    }
};

const addWidget = async (widgetConfig) => {
    try {
        const response = await axios.post(
            `/dashboard/${props.dashboard.id}/widgets`,
            widgetConfig
        );

        if (response.data.success) {
            // Refresh dashboard to show new widget
            window.location.reload();
        }
    } catch (error) {
        console.error('Error adding widget:', error);
    }
};

const updateWidget = async (widgetId, updates) => {
    try {
        await axios.put(
            `/dashboard/${props.dashboard.id}/widgets/${widgetId}`,
            updates
        );

        // Refresh data
        fetchData();
    } catch (error) {
        console.error('Error updating widget:', error);
    }
};

const removeWidget = async (widgetId) => {
    try {
        await axios.delete(
            `/dashboard/${props.dashboard.id}/widgets/${widgetId}`
        );

        // Refresh dashboard
        window.location.reload();
    } catch (error) {
        console.error('Error removing widget:', error);
    }
};

// Auto-refresh functionality
const startAutoRefresh = (interval) => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }

    if (interval > 0) {
        refreshInterval.value = setInterval(fetchData, interval * 1000);
        console.log(`Auto-refresh started: ${interval} seconds`);
    }
};

// Watchers
watch(
    () => props.dashboard?.settings?.refresh_interval,
    (interval) => {
        startAutoRefresh(interval || 0);
    },
    { immediate: true }
);

// Lifecycle
onMounted(() => {
    console.log('Dashboard component mounted', {
        dashboard: props.dashboard,
        store: props.store,
        initial_date_range: dateRange.value,
    });

    // Initial data fetch
    fetchData();
});

// Cleanup on unmount
onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});
</script>

<template>
    <Head :title="dashboard?.name || 'Dashboard'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ dashboard?.name || 'Dashboard' }}
                </h2>

                <div class="flex items-center space-x-4">
                    <DateRangePicker
                        :initial-start="dateRange.start"
                        :initial-end="dateRange.end"
                        @change="handleDateRangeChange"
                    />

                    <button
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                        @click="showAddWidgetModal = true"
                    >
                        Add Widget
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Loading State -->
                <div
                    v-if="loading && layout.length === 0"
                    class="flex justify-center py-20"
                >
                    <div class="text-center">
                        <div class="text-3xl font-semibold">
                            Loading dashboard...
                        </div>
                        <p class="mt-2 text-gray-600">
                            Please wait while we fetch your data.
                        </p>
                    </div>
                </div>

                <!-- Debug Info (remove in production) -->
                <div
                    v-if="$page.props.app?.debug"
                    class="mb-4 p-4 bg-gray-100 rounded"
                >
                    <details>
                        <summary class="cursor-pointer font-semibold">
                            Debug Info
                        </summary>
                        <pre class="text-xs mt-2">
Dashboard: {{ dashboard }}</pre
                        >
                        <pre class="text-xs mt-2">Store: {{ store }}</pre>
                        <pre class="text-xs mt-2">
Date Range: {{ dateRange }}</pre
                        >
                        <pre class="text-xs mt-2">
Widget Data Keys: {{ Object.keys(widgetData) }}</pre
                        >
                    </details>
                </div>

                <!-- Dashboard Grid -->
                <DashboardGrid
                    v-if="layout.length > 0"
                    :layout="layout"
                    @layout-updated="updateLayout"
                >
                    <template #default="{ item }">
                        <Widget
                            :widget="item"
                            :data="widgetData[item.id]"
                            :loading="loading"
                            @update="updateWidget(item.id, $event)"
                            @remove="removeWidget(item.id)"
                        />
                    </template>
                </DashboardGrid>

                <!-- Empty State -->
                <div v-else-if="!loading" class="text-center py-20">
                    <div class="text-gray-500">
                        <h3 class="text-lg font-medium">
                            No widgets configured
                        </h3>
                        <p class="mt-2">
                            Add your first widget to get started.
                        </p>
                        <button
                            class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                            @click="showAddWidgetModal = true"
                        >
                            Add Your First Widget
                        </button>
                    </div>
                </div>

                <!-- Add Widget Modal -->
                <AddWidgetModal
                    v-if="showAddWidgetModal"
                    :store="store"
                    :available-widgets="availableWidgets"
                    @close="showAddWidgetModal = false"
                    @add="addWidget"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any component-specific styles here */
.dashboard-loading {
    min-height: 400px;
}
</style>
