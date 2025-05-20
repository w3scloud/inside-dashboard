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

const items = computed(() => {
    const dataSource = props.widget.data_source;

    if (dataSource === 'sales') {
        return props.data?.products || [];
    } else if (dataSource === 'products') {
        return props.data?.products || [];
    } else if (dataSource === 'customers') {
        return props.data?.customers || [];
    }

    return [];
});

const columns = computed(() => {
    const dataSource = props.widget.data_source;

    if (dataSource === 'sales' || dataSource === 'products') {
        return [
            { key: 'title', label: 'Product' },
            { key: 'total_sales', label: 'Revenue', format: 'currency' },
            { key: 'total_quantity', label: 'Quantity', format: 'number' },
        ];
    } else if (dataSource === 'customers') {
        return [
            { key: 'name', label: 'Customer' },
            { key: 'total_spent', label: 'Spent', format: 'currency' },
            { key: 'orders_count', label: 'Orders', format: 'number' },
        ];
    }

    return [];
});

const formatValue = (value, format) => {
    if (format === 'currency') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(value);
    } else if (format === 'number') {
        return new Intl.NumberFormat('en-US').format(value);
    } else if (format === 'percentage') {
        return `${value.toFixed(1)}%`;
    }

    return value;
};

const getValue = (item, column) => {
    if (column.key === 'name' && !item.name) {
        return `${item.first_name || ''} ${item.last_name || ''}`.trim();
    }

    return item[column.key];
};
</script>

<template>
    <div class="h-full w-full overflow-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                    >
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-for="(item, index) in items" :key="index">
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-900"
                    >
                        {{ formatValue(getValue(item, column), column.format) }}
                    </td>
                </tr>
                <tr v-if="items.length === 0">
                    <td
                        :colspan="columns.length"
                        class="px-6 py-4 text-center text-sm text-gray-500"
                    >
                        No data available
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
