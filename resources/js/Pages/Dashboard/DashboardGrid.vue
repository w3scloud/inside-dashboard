<script setup>
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
    layout: {
        type: Array,
        required: true,
    },
    cols: {
        type: Number,
        default: 12,
    },
    rowHeight: {
        type: Number,
        default: 150,
    },
    margin: {
        type: Array,
        default: () => [10, 10],
    },
    isDraggable: {
        type: Boolean,
        default: true,
    },
    isResizable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['layout-updated']);

const gridContainer = ref(null);
const containerWidth = ref(1200);

const gridStyle = computed(() => ({
    position: 'relative',
    width: '100%',
    minHeight: '400px',
}));

const getItemStyle = (item) => {
    const colWidth = containerWidth.value / props.cols;
    const x = item.position.x * colWidth;
    const y = item.position.y * (props.rowHeight + props.margin[1]);
    const width = item.size.w * colWidth - props.margin[0];
    const height =
        item.size.h * props.rowHeight + (item.size.h - 1) * props.margin[1];

    return {
        position: 'absolute',
        left: `${x + props.margin[0] / 2}px`,
        top: `${y + props.margin[1] / 2}px`,
        width: `${width}px`,
        height: `${height}px`,
        transition: 'all 0.2s ease',
    };
};

const updateContainerWidth = () => {
    if (gridContainer.value) {
        containerWidth.value = gridContainer.value.offsetWidth;
    }
};

const handleResize = () => {
    updateContainerWidth();
};

onMounted(() => {
    updateContainerWidth();
    window.addEventListener('resize', handleResize);
});

watch(
    () => props.layout,
    () => {
        // Emit layout changes
        emit('layout-updated', props.layout);
    },
    { deep: true }
);
</script>

<template>
    <div ref="gridContainer" :style="gridStyle" class="dashboard-grid">
        <div
            v-for="item in layout"
            :key="item.id"
            :style="getItemStyle(item)"
            class="grid-item"
        >
            <slot :item="item" />
        </div>
    </div>
</template>

<style scoped>
.dashboard-grid {
    background: transparent;
}

.grid-item {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.grid-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
