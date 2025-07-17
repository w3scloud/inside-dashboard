<template>
    <div class="widget-container">
        <div class="widget-header">
            <h3 class="widget-title">Sales Overview</h3>
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
            <p class="text-sm text-gray-600 mt-2">Loading sales data...</p>
        </div>

        <div v-else class="widget-content">
            <!-- Key Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="metric-card">
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-value">
                        ${{ formatNumber(data.total_revenue) }}
                    </div>
                    <div
                        class="metric-change"
                        :class="getChangeClass(data.growth_rate)"
                    >
                        {{ formatChange(data.growth_rate) }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">Total Orders</div>
                    <div class="metric-value">
                        {{ formatNumber(data.total_orders) }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">Avg Order Value</div>
                    <div class="metric-value">
                        ${{ formatNumber(data.average_order_value) }}
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-label">Growth Rate</div>
                    <div
                        class="metric-value"
                        :class="getChangeClass(data.growth_rate)"
                    >
                        {{ formatChange(data.growth_rate) }}
                    </div>
                </div>
            </div>

            <!-- Sales Chart -->
            <div v-if="chartData" class="chart-container">
                <h4 class="chart-title">Daily Sales Trend</h4>
                <Line
                    :data="chartData"
                    :options="chartOptions"
                    class="sales-chart"
                />
            </div>

            <!-- No Data State -->
            <div v-else class="no-data">
                <div class="text-center py-8">
                    <ChartBarIcon
                        class="w-12 h-12 text-gray-400 mx-auto mb-4"
                    />
                    <p class="text-gray-500">
                        No sales data available for the selected period
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { XMarkIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            total_orders: 0,
            average_order_value: 0,
            growth_rate: 0,
            daily_sales: {},
        }),
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['remove']);

const chartData = computed(() => {
    if (
        !props.data.daily_sales ||
        Object.keys(props.data.daily_sales).length === 0
    ) {
        return null;
    }

    const dailySales = props.data.daily_sales;
    const dates = Object.keys(dailySales).sort();
    const values = dates.map((date) => dailySales[date]);

    return {
        labels: dates.map((date) =>
            new Date(date).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
            })
        ),
        datasets: [
            {
                label: 'Daily Revenue',
                data: values,
                borderColor: 'rgb(79, 70, 229)',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: 'rgb(79, 70, 229)',
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
                label: function (context) {
                    return `Revenue: $${context.parsed.y.toLocaleString()}`;
                },
            },
        },
    },
    scales: {
        x: {
            display: true,
            grid: {
                display: false,
            },
        },
        y: {
            display: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
                callback: function (value) {
                    return '$' + value.toLocaleString();
                },
            },
        },
    },
    interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false,
    },
};

const formatNumber = (value) => {
    if (!value && value !== 0) return '0';
    return Number(value).toLocaleString();
};

const formatChange = (value) => {
    if (!value && value !== 0) return '0%';
    const sign = value > 0 ? '+' : '';
    return `${sign}${Number(value).toFixed(1)}%`;
};

const getChangeClass = (value) => {
    if (!value && value !== 0) return 'text-gray-500';
    return value > 0
        ? 'text-green-600'
        : value < 0
        ? 'text-red-600'
        : 'text-gray-500';
};
</script>

<style scoped>
.widget-container {
    @apply bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col;
}

.widget-header {
    @apply flex justify-between items-center p-6 border-b border-gray-200;
}

.widget-title {
    @apply text-lg font-semibold text-gray-900;
}

.widget-controls {
    @apply flex space-x-2;
}

.btn-icon {
    @apply p-1 rounded hover:bg-gray-100 transition-colors;
}

.widget-loading {
    @apply flex flex-col items-center justify-center flex-1 p-8;
}

.widget-content {
    @apply flex-1 p-6 overflow-hidden;
}

.metric-card {
    @apply bg-gray-50 rounded-lg p-4;
}

.metric-label {
    @apply text-sm font-medium text-gray-500 mb-1;
}

.metric-value {
    @apply text-2xl font-bold text-gray-900 mb-1;
}

.metric-change {
    @apply text-sm font-medium;
}

.chart-container {
    @apply mt-6;
}

.chart-title {
    @apply text-sm font-medium text-gray-700 mb-4;
}

.sales-chart {
    @apply h-64;
}

.no-data {
    @apply flex items-center justify-center flex-1;
}
</style>
