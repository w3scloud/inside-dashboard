<script setup>
import DataTableReport from '@/Components/Data/DataTableReport.vue';

const props = defineProps({
    table: Object,
    store: Object,
    columns: Array,
    rows: Array,
});
</script>

<template>
    <DataTableReport
        :table="table"
        :store="store"
        :columns="columns"
        :rows="rows"
    />
</template>

