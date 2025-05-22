<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    reports: Array,
    store: Object,
    reportTypes: Object,
});

const getReportTypeName = (type) => {
    return props.reportTypes[type]?.name || type;
};

const getReportTypeDescription = (type) => {
    return props.reportTypes[type]?.description || '';
};
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Reports
                </h2>
                <Link
                    :href="route('reports.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    Create Report
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="reports.length === 0" class="text-center py-12">
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No reports
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Get started by creating your first report.
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('reports.create')"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                        >
                            Create Report
                        </Link>
                    </div>
                </div>

                <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="report in reports"
                        :key="report.id"
                        class="overflow-hidden rounded-lg bg-white shadow"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-md bg-indigo-500"
                                    >
                                        <svg
                                            class="h-5 w-5 text-white"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{ report.name }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ getReportTypeName(report.type) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p
                                    v-if="report.description"
                                    class="text-sm text-gray-600 mb-2"
                                >
                                    {{ report.description }}
                                </p>
                                <div
                                    class="flex items-center justify-between text-sm text-gray-500"
                                >
                                    <span>{{
                                        report.output_format.toUpperCase()
                                    }}</span>
                                    <span
                                        v-if="report.schedule_enabled"
                                        class="text-green-600"
                                    >
                                        Scheduled ({{
                                            report.schedule_frequency
                                        }})
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">
                                    Last generated:
                                    {{ report.last_generated_at || 'Never' }}
                                </p>
                            </div>

                            <div class="mt-4 flex space-x-3">
                                <Link
                                    :href="route('reports.show', report.id)"
                                    class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-indigo-500"
                                >
                                    View
                                </Link>
                                <Link
                                    :href="route('reports.edit', report.id)"
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Edit
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
