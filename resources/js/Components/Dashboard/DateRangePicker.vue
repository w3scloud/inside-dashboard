<template>
    <div class="date-range-picker">
        <div class="flex items-center space-x-2">
            <!-- Quick presets -->
            <select
                v-model="selectedPreset"
                @change="handlePresetChange"
                class="form-select"
            >
                <option value="custom">Custom Range</option>
                <option value="7days">Last 7 Days</option>
                <option value="30days">Last 30 Days</option>
                <option value="90days">Last 90 Days</option>
                <option value="year">Last Year</option>
            </select>

            <!-- Custom date inputs (shown when Custom Range is selected) -->
            <div
                v-if="selectedPreset === 'custom'"
                class="flex items-center space-x-2"
            >
                <input
                    v-model="startDate"
                    type="date"
                    @change="handleDateChange"
                    class="form-input"
                    :max="endDate"
                />
                <span class="text-gray-500">to</span>
                <input
                    v-model="endDate"
                    type="date"
                    @change="handleDateChange"
                    class="form-input"
                    :min="startDate"
                    :max="today"
                />
            </div>

            <!-- Date range display for presets -->
            <div v-else class="text-sm text-gray-600">
                {{ formatDateRange() }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    initialStart: {
        type: String,
        default: () => {
            const date = new Date();
            date.setDate(date.getDate() - 30);
            return date.toISOString().split('T')[0];
        },
    },
    initialEnd: {
        type: String,
        default: () => new Date().toISOString().split('T')[0],
    },
});

const emit = defineEmits(['change']);

const selectedPreset = ref('30days');
const startDate = ref(props.initialStart);
const endDate = ref(props.initialEnd);

const today = computed(() => {
    return new Date().toISOString().split('T')[0];
});

const handlePresetChange = () => {
    if (selectedPreset.value === 'custom') {
        return; // Let user pick custom dates
    }

    const end = new Date();
    let start = new Date();

    switch (selectedPreset.value) {
        case '7days':
            start.setDate(end.getDate() - 7);
            break;
        case '30days':
            start.setDate(end.getDate() - 30);
            break;
        case '90days':
            start.setDate(end.getDate() - 90);
            break;
        case 'year':
            start.setFullYear(end.getFullYear() - 1);
            break;
    }

    startDate.value = start.toISOString().split('T')[0];
    endDate.value = end.toISOString().split('T')[0];

    emitChange();
};

const handleDateChange = () => {
    selectedPreset.value = 'custom';
    emitChange();
};

const emitChange = () => {
    emit('change', {
        start: startDate.value,
        end: endDate.value,
        preset: selectedPreset.value,
    });
};

const formatDateRange = () => {
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);

    const formatOptions = { month: 'short', day: 'numeric' };

    if (start.getFullYear() !== end.getFullYear()) {
        formatOptions.year = 'numeric';
    }

    return `${start.toLocaleDateString(
        'en-US',
        formatOptions
    )} - ${end.toLocaleDateString('en-US', formatOptions)}`;
};

// Watch for prop changes
watch(
    () => props.initialStart,
    (newStart) => {
        startDate.value = newStart;
    }
);

watch(
    () => props.initialEnd,
    (newEnd) => {
        endDate.value = newEnd;
    }
);

// Determine initial preset based on date range
onMounted(() => {
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

    if (diffDays === 7) {
        selectedPreset.value = '7days';
    } else if (diffDays === 30) {
        selectedPreset.value = '30days';
    } else if (diffDays === 90) {
        selectedPreset.value = '90days';
    } else if (diffDays === 365) {
        selectedPreset.value = 'year';
    } else {
        selectedPreset.value = 'custom';
    }
});
</script>

<style scoped>
.form-select {
    @apply block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md;
}

.form-input {
    @apply block w-auto px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm;
}
</style>
