<script setup>
import { ref, onMounted } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

const props = defineProps({
    initialStart: {
        type: String,
        required: true,
    },
    initialEnd: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['change']);

const dateRangeInput = ref(null);
const dateRangePicker = ref(null);

const formatDateRange = (start, end) => {
    const startDate = new Date(start);
    const endDate = new Date(end);

    const options = { month: 'short', day: 'numeric' };

    const formattedStart = startDate.toLocaleDateString('en-US', options);
    const formattedEnd = endDate.toLocaleDateString('en-US', options);

    return `${formattedStart} - ${formattedEnd}`;
};

onMounted(() => {
    if (dateRangeInput.value) {
        dateRangePicker.value = flatpickr(dateRangeInput.value, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [props.initialStart, props.initialEnd],
            onChange: function (selectedDates, dateStr) {
                if (selectedDates.length === 2) {
                    const [start, end] = selectedDates.map((date) => {
                        return date.toISOString().split('T')[0];
                    });

                    emit('change', { start, end });
                }
            },
        });
    }
});
</script>

<template>
    <div class="relative">
        <input
            ref="dateRangeInput"
            type="text"
            class="block w-52 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :placeholder="formatDateRange(initialStart, initialEnd)"
            readonly
        />
        <div
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
        >
            <svg
                class="h-5 w-5 text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>
    </div>
</template>
