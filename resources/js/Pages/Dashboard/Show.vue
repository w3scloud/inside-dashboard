<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardGrid from '@/Components/Dashboard/DashboardGrid.vue';
import Widget from '@/Components/Dashboard/Widget.vue';
import DateRangePicker from '@/Components/Dashboard/DateRangePicker.vue';
import AddWidgetModal from '@/Components/Dashboard/AddWidgetModal.vue';
import axios from 'axios';

const props = defineProps({
    dashboard: Object,
    date_range: Object,
    store: Object,
});

const layout = ref(props.dashboard.layout || []);
const dateRange = ref(props.date_range);
const widgetData = ref({});
const loading = ref(true);
const showAddWidgetModal = ref(false);
const refreshInterval = ref(null);

// Fetch dashboard data
const fetchData = async () => {
    loading.value = true;

    try {
        const response = await axios.get(
            route('dashboard.data', props.dashboard.id),
            {
                params: {
                    start_date: dateRange.value.start,
                    end_date: dateRange.value.end,
                },
            }
        );

        if (response.data.success) {
            widgetData.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    } finally {
        loading.value = false;
    }
};

// Handle date range change
const handleDateRangeChange = (newRange) => {
    dateRange.value = newRange;
    fetchData();
};

// Add widget
const addWidget = async (widget) => {
    try {
        const response = await axios.post(
            route('dashboard.widget.add', props.dashboard.id),
            widget
        );

        if (response.data.success) {
            layout.value.push(response.data.widget);
            showAddWidgetModal.value = false;
            fetchData();
        }
    } catch (error) {
        console.error('Error adding widget:', error);
    }
};

// Update widget
const updateWidget = async (widgetId, widgetData) => {
    try {
        const response = await axios.put(
            route('dashboard.widget.update', {
                id: props.dashboard.id,
                widgetId,
            }),
            widgetData
        );

        if (response.data.success) {
            const index = layout.value.findIndex((w) => w.id === widgetId);
            if (index !== -1) {
                layout.value[index] = { ...layout.value[index], ...widgetData };
            }
            fetchData();
        }
    } catch (error) {
        console.error('Error updating widget:', error);
    }
};

// Remove widget
const removeWidget = async (widgetId) => {
    try {
        const response = await axios.delete(
            route('dashboard.widget.remove', {
                id: props.dashboard.id,
                widgetId,
            })
        );

        if (response.data.success) {
            layout.value = layout.value.filter((w) => w.id !== widgetId);
        }
    } catch (error) {
        console.error('Error removing widget:', error);
    }
};

// Update layout
const updateLayout = async (newLayout) => {
    layout.value = newLayout;

    try {
        await axios.put(route('dashboard.layout.update', props.dashboard.id), {
            layout: newLayout,
        });
    } catch (error) {
        console.error('Error updating layout:', error);
    }
};

// Setup refresh interval if enabled
watch(
    () => props.dashboard.settings?.refresh_interval,
    (interval) => {
        if (refreshInterval.value) {
            clearInterval(refreshInterval.value);
            refreshInterval.value = null;
        }

        if (interval && interval > 0) {
            refreshInterval.value = setInterval(fetchData, interval * 1000);
        }
    },
    { immediate: true }
);

onMounted(() => {
    fetchData();
});
</script>

<template>
    <Head :title="dashboard.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ dashboard.name }}
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

                <DashboardGrid
                    v-else
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

                <AddWidgetModal
                    v-if="showAddWidgetModal"
                    :store="store"
                    @close="showAddWidgetModal = false"
                    @add="addWidget"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
