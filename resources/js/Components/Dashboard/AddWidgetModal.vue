<script setup>
import { ref } from 'vue';

const props = defineProps({
    store: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'add']);

const widgetType = ref('kpi');
const widgetTitle = ref('');
const dataSource = ref('sales');
const chartType = ref('line');

const widgetTypes = [
    { value: 'kpi', label: 'KPI' },
    { value: 'timeline', label: 'Timeline Chart' },
    { value: 'bar_chart', label: 'Bar Chart' },
    { value: 'pie_chart', label: 'Pie Chart' },
    { value: 'table', label: 'Table' },
];

const dataSources = [
    { value: 'sales', label: 'Sales Data' },
    { value: 'products', label: 'Products Data' },
    { value: 'inventory', label: 'Inventory Data' },
    { value: 'customers', label: 'Customers Data' },
];

const chartTypes = [
    { value: 'line', label: 'Line Chart' },
    { value: 'bar', label: 'Bar Chart' },
    { value: 'pie', label: 'Pie Chart' },
];

const handleSubmit = () => {
    if (!widgetTitle.value) {
        alert('Please enter a widget title');
        return;
    }

    const widget = {
        title: widgetTitle.value,
        type: widgetType.value,
        chart_type: chartType.value,
        data_source: dataSource.value,
        size: { w: 1, h: 2 },
        position: { x: 0, y: 0 },
        config: {},
        filters: {},
    };

    if (widgetType.value === 'kpi') {
        widget.size = { w: 1, h: 1 };
    } else if (
        widgetType.value === 'timeline' ||
        widgetType.value === 'bar_chart'
    ) {
        widget.size = { w: 2, h: 2 };
    }

    emit('add', widget);
};
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-medium">Add Widget</h2>
                <button
                    class="text-gray-400 hover:text-gray-600"
                    @click="$emit('close')"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="mb-4">
                    <label
                        for="widgetTitle"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Widget Title
                    </label>
                    <input
                        id="widgetTitle"
                        v-model="widgetTitle"
                        type="text"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Enter widget title"
                        required
                    />
                </div>

                <div class="mb-4">
                    <label
                        for="widgetType"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Widget Type
                    </label>
                    <select
                        id="widgetType"
                        v-model="widgetType"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        <option
                            v-for="type in widgetTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                </div>

                <div class="mb-4">
                    <label
                        for="dataSource"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Data Source
                    </label>
                    <select
                        id="dataSource"
                        v-model="dataSource"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        <option
                            v-for="source in dataSources"
                            :key="source.value"
                            :value="source.value"
                        >
                            {{ source.label }}
                        </option>
                    </select>
                </div>

                <div
                    v-if="
                        widgetType === 'timeline' ||
                        widgetType === 'bar_chart' ||
                        widgetType === 'pie_chart'
                    "
                    class="mb-4"
                >
                    <label
                        for="chartType"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Chart Type
                    </label>
                    <select
                        id="chartType"
                        v-model="chartType"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        <option
                            v-for="type in chartTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Add Widget
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
