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
const chartContainer = ref(null);

const chartType = computed(() => {
    if (props.widget.chart_type === 'bar') {
        return 'bar';
    } else if (props.widget.chart_type === 'line') {
        return 'line';
    } else {
        return 'line'; // Default
    }
});

const chartData = computed(() => {
    if (!props.data || !props.data.timeline) {
        return {
            labels: [],
            datasets: [],
        };
    }

    const timeline = props.data.timeline || [];

    return {
        labels: timeline.map((item) => item.date),
        datasets: [
            {
                label: 'Sales',
                data: timeline.map((item) => item.sales),
                borderColor: 'rgb(79, 70, 229)',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true,
            },
            {
                label: 'Orders',
                data: timeline.map((item) => item.orders || 0),
                borderColor: 'rgb(234, 88, 12)',
                backgroundColor: 'rgba(234, 88, 12, 0.1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true,
                yAxisID: 'y1',
            },
        ],
    };
});

const createChart = () => {
    if (!chartRef.value) return;

    const ctx = chartRef.value.getContext('2d');

    chart.value = new Chart(ctx, {
        type: chartType.value,
        data: chartData.value,
        options: {
            responsive: true,
            maintainAspectRatio: false, // This is crucial!
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales ($)',
                    },
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Orders',
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date',
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
    chart.value.update('none'); // Use 'none' for immediate update without animation
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
    // Small delay to ensure the container is properly sized
    setTimeout(() => {
        createChart();
    }, 100);
});

onBeforeUnmount(() => {
    destroyChart();
});
</script>

<template>
    <div ref="chartContainer" class="chart-container">
        <canvas ref="chartRef"></canvas>
    </div>
</template>

<style scoped>
.chart-container {
    width: 100%;
    height: 100%;
    position: relative;
    min-height: 200px;
    max-height: 400px; /* Prevent infinite growth */
}

.chart-container canvas {
    max-height: 100% !important;
}
</style>
