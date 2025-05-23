<script setup>
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
    layout: {
        type: Array,
        required: true,
    },
    cols: {
        type: Number,
        default: 3,
    },
    rowHeight: {
        type: Number,
        default: 150,
    },
    margin: {
        type: Array,
        default: () => [20, 20],
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
    display: 'grid',
    gridTemplateColumns: `repeat(${props.cols}, 1fr)`,
    gap: `${props.margin[1]}px ${props.margin[0]}px`,
    width: '100%',
    minHeight: '400px',
}));

const getItemStyle = (item) => {
    const minHeight = props.rowHeight * item.size.h;
    const maxHeight = props.rowHeight * item.size.h + 50;

    return {
        gridColumn: `span ${Math.min(item.size.w, props.cols)}`,
        gridRow: `span ${item.size.h}`,
        minHeight: `${minHeight}px`,
        maxHeight: `${maxHeight}px`,
        height: `${minHeight}px`,
        transition: 'all 0.2s ease',
        overflow: 'hidden',
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
    transition: box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    min-height: 150px;
    max-height: 500px;
}

.grid-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .grid-item {
        max-height: 400px;
    }
}

@media (max-width: 640px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .grid-item {
        max-height: 350px;
    }
}
</style>
