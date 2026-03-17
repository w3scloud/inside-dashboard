<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    table: {
        type: Object,
        required: true,
    },
    store: {
        type: Object,
        required: false,
        default: null,
    },
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head :title="table.label + ' Report'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ table.label }} Report
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Explore and analyze your {{ table.label.toLowerCase() }} data directly
                        inside the dashboard.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('reports')"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        All Reports
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-4">
                    <section class="space-y-4">
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div
                                class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 px-4 py-3"
                            >
                                <div class="flex items-center space-x-2">
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-indigo-100 text-indigo-600"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.6"
                                                d="M3 7h18M3 12h18M3 17h18"
                                            />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">
                                            Data preview
                                        </h3>
                                        <p class="text-xs text-gray-500">
                                            A tabular snapshot of {{ table.label.toLowerCase() }}
                                            for the selected date range.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2 text-xs text-gray-500">
                                    <span class="hidden sm:inline-block">Date range</span>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                    >
                                        Last 30 days
                                        <svg
                                            class="ml-1 h-3 w-3 text-gray-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.6"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="px-4 py-4">
                                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    v-for="column in columns"
                                                    :key="column.key"
                                                    scope="col"
                                                    class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wide whitespace-nowrap"
                                                >
                                                    {{ column.label }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-gray-100 bg-white"
                                        >
                                            <tr v-if="rows.length === 0">
                                                <td
                                                    :colspan="columns.length"
                                                    class="px-3 py-8 text-center text-gray-400"
                                                >
                                                    No data available yet for this table.
                                                </td>
                                            </tr>
                                            <tr
                                                v-for="(row, index) in rows"
                                                :key="index"
                                                class="hover:bg-gray-50"
                                            >
                                                <td
                                                    v-for="column in columns"
                                                    :key="column.key"
                                                    class="px-3 py-2 whitespace-nowrap text-gray-800"
                                                >
                                                    {{ row[column.key] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

