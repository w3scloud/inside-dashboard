<script setup>
import { ref, computed } from 'vue';
import KpiWidget from './Widgets/KpiWidget.vue';
import ChartWidget from './Widgets/ChartWidget.vue';
import TableWidget from './Widgets/TableWidget.vue';
import PieChartWidget from './Widgets/PieChartWidget.vue';

const props = defineProps({
    widget: {
        type: Object,
        required: true,
    },
    data: {
        type: Object,
        default: () => ({}),
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update', 'remove']);

const showMenu = ref(false);
const isEditing = ref(false);
const widgetTitle = ref(props.widget.title);

const widgetComponent = computed(() => {
    switch (props.widget.type) {
        case 'kpi':
            return KpiWidget;
        case 'timeline':
        case 'bar_chart':
        case 'line_chart':
            return ChartWidget;
        case 'table':
            return TableWidget;
        case 'pie_chart':
            return PieChartWidget;
        default:
            return null;
    }
});

const handleEdit = () => {
    isEditing.value = true;
    showMenu.value = false;
};

const handleSave = () => {
    emit('update', { title: widgetTitle.value });
    isEditing.value = false;
};

const handleCancel = () => {
    widgetTitle.value = props.widget.title;
    isEditing.value = false;
};

const handleRemove = () => {
    if (confirm('Are you sure you want to remove this widget?')) {
        emit('remove');
    }
    showMenu.value = false;
};
</script>

<template>
    <div class="widget-container">
        <div class="widget-header">
            <div v-if="isEditing" class="flex items-center space-x-2">
                <input
                    v-model="widgetTitle"
                    class="rounded-md border border-gray-300 px-2 py-1 text-sm"
                    @keyup.enter="handleSave"
                />
                <button
                    class="text-green-600 hover:text-green-800"
                    @click="handleSave"
                >
                    Save
                </button>
                <button
                    class="text-gray-600 hover:text-gray-800"
                    @click="handleCancel"
                >
                    Cancel
                </button>
            </div>
            <h3 v-else class="widget-title">{{ widget.title }}</h3>

            <div class="relative">
                <button
                    class="text-gray-400 hover:text-gray-600"
                    @click="showMenu = !showMenu"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"
                        />
                    </svg>
                </button>

                <div v-if="showMenu" class="widget-menu">
                    <div class="py-1">
                        <button
                            class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                            @click="handleEdit"
                        >
                            Edit
                        </button>
                        <button
                            class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100"
                            @click="handleRemove"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="widget-content">
            <div v-if="loading" class="widget-loading">
                <svg
                    class="h-8 w-8 animate-spin text-indigo-500"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </div>

            <component
                v-else-if="widgetComponent"
                :is="widgetComponent"
                :widget="widget"
                :data="data"
            />

            <div v-else class="widget-error">
                <p class="text-sm text-gray-500">
                    Widget type not supported: {{ widget.type }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.widget-container {
    @apply h-full w-full overflow-hidden rounded-lg bg-white shadow;
    display: flex;
    flex-direction: column;
}

.widget-header {
    @apply flex items-center justify-between border-b px-4 py-3;
    flex-shrink: 0;
}

.widget-title {
    @apply text-sm font-medium;
}

.widget-menu {
    @apply absolute right-0 z-10 mt-2 w-40 divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5;
}

.widget-content {
    @apply flex-1 p-4;
    min-height: 0; /* Important: allows flex child to shrink */
    height: 100%;
    overflow: hidden;
}

.widget-loading {
    @apply flex h-full w-full items-center justify-center;
}

.widget-error {
    @apply flex h-full items-center justify-center;
}
</style>
