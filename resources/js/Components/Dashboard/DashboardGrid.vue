<script setup>
import { ref, onMounted } from 'vue';
import { Responsive, WidthProvider } from 'vue-grid-layout';

const ResponsiveGridLayout = WidthProvider(Responsive);

const props = defineProps({
    layout: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['layout-updated']);

const currentLayout = ref(
    props.layout.map((widget) => ({
        i: widget.id,
        x: widget.position.x,
        y: widget.position.y,
        w: widget.size.w,
        h: widget.size.h,
        minW: 1,
        minH: 1,
    }))
);

const handleLayoutChange = (newLayout) => {
    // Convert grid layout back to widget format
    const updatedWidgets = props.layout.map((widget) => {
        const gridItem = newLayout.find((item) => item.i === widget.id);

        if (!gridItem) {
            return widget;
        }

        return {
            ...widget,
            position: { x: gridItem.x, y: gridItem.y },
            size: { w: gridItem.w, h: gridItem.h },
        };
    });

    emit('layout-updated', updatedWidgets);
};

onMounted(() => {
    // Force layout refresh
    setTimeout(() => {
        window.dispatchEvent(new Event('resize'));
    }, 200);
});
</script>

<template>
    <div>
        <ResponsiveGridLayout
            :layout="currentLayout"
            :col-num="3"
            :row-height="150"
            :is-draggable="true"
            :is-resizable="true"
            :margin="[20, 20]"
            :use-css-transforms="true"
            @layout-updated="handleLayoutChange"
        >
            <div v-for="item in currentLayout" :key="item.i" :data-grid="item">
                <slot :name="item.i"></slot>
            </div>
        </ResponsiveGridLayout>
    </div>
</template>
