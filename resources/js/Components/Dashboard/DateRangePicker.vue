<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    initialStart: String,
    initialEnd: String,
});

const emit = defineEmits(['change']);

const startDate = ref(props.initialStart || '');
const endDate = ref(props.initialEnd || '');

const emitChange = () => {
    emit('change', {
        start: startDate.value,
        end: endDate.value,
    });
};

// Watch for changes
watch([startDate, endDate], () => {
    if (startDate.value && endDate.value) {
        emitChange();
    }
});
</script>

<template>
    <div class="flex items-center space-x-2">
        <div>
            <label class="block text-xs text-gray-700">From</label>
            <input
                v-model="startDate"
                type="date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
        </div>
        <div>
            <label class="block text-xs text-gray-700">To</label>
            <input
                v-model="endDate"
                type="date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
        </div>
    </div>
</template>
