<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import Chart from 'chart.js/auto';

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

const chartRef = ref(null);
const chart = ref(null);

const chartData = computed(() => {
    if (!props.data || !props.data.stock_status) {
        return {
            labels: [],
            datasets: [
                {
                    data: [],
                    backgroundColor: [],
                    hoverOffset: 4,
                },
            ],
        };
    }

    const stockStatus = props.data.stock_status || [];

    // Custom colors for specific status types
    const backgroundColors = {
        'In Stock': 'rgba(34, 197, 94, 0.8)',
        'Low Stock': 'rgba(251, 191, 36, 0.8)',
        'Out of Stock': 'rgba(239, 68, 68, 0.8)',
    };

    return {
        labels: stockStatus.map((item) => item.label),
        datasets: [
            {
                data: stockStatus.map((item) => item.value),
                backgroundColor: stockStatus.map(
                    (item) => backgroundColors[item.label] || '#A1A1AA'
                ),
                hoverOffset: 4,
            },
        ],
    };
});

const createChart = () => {
    if (!chartRef.value) return;

    const ctx = chartRef.value.getContext('2d');

    chart.value = new Chart(ctx, {
        type: 'pie',
        data: chartData.value,
        options: {
            responsive: true,
            maintainAspectRatio: false, // This is crucial!
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            let total = context.dataset.data.reduce(
                                (acc, curr) => acc + curr,
                                0
                            );
                            let percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        },
                    },
                },
            },
        },
    });
};

const destroyChart = () => {
    if (chart.value) {
        chart.value.destroy();
        chart.value = null;
    }
};

const updateChart = () => {
    if (!chart.value) return;

    chart.value.data = chartData.value;
    chart.value.update('none');
};

watch(
    () => props.data,
    () => {
        if (chart.value) {
            updateChart();
        } else {
            createChart();
        }
    },
    { deep: true }
);

onMounted(() => {
    setTimeout(() => {
        createChart();
    }, 100);
});

onBeforeUnmount(() => {
    destroyChart();
});
</script>

<template>
    <div class="chart-container">
        <canvas ref="chartRef"></canvas>
    </div>
</template>

<style scoped>
.chart-container {
    width: 100%;
    height: 100%;
    position: relative;
    min-height: 200px;
    max-height: 300px; /* Prevent infinite growth */
}

.chart-container canvas {
    max-height: 100% !important;
}
</style>
