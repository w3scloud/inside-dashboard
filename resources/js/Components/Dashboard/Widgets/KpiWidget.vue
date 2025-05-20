<script setup>
import { computed } from 'vue';

const props = defineProps({
    widget: {
        type: Object,
        required: true,
    },
    data: {
        type: Object,
        default: () => ({}),
    },
});

const display = computed(() => props.widget.config?.display || 'number');

const metricKey = computed(() => {
    const defaultKeys = {
        sales: 'total_sales',
        products: 'total_products',
        inventory: 'total_items',
        customers: 'total_customers',
    };

    return defaultKeys[props.widget.data_source] || 'value';
});

const value = computed(() => {
    if (!props.data) return 0;
    return props.data[metricKey.value] || 0;
});

const formattedValue = computed(() => {
    if (display.value === 'currency') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(value.value);
    } else if (display.value === 'percentage') {
        return `${value.value.toFixed(1)}%`;
    } else {
        return new Intl.NumberFormat('en-US').format(value.value);
    }
});
</script>

<template>
    <div class="flex h-full flex-col items-center justify-center text-center">
        <div class="text-3xl font-bold">{{ formattedValue }}</div>
        <div class="mt-2 text-sm text-gray-500">{{ widget.title }}</div>
    </div>
</template>
