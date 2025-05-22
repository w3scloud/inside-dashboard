<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    dashboards: Array,
    store: Object,
});
</script>

<template>
    <Head title="Dashboards" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Dashboards
                </h2>
                <Link
                    :href="route('dashboard.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    Create Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="dashboards.length === 0" class="text-center py-12">
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
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No dashboards
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Get started by creating your first dashboard.
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('dashboard.create')"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                        >
                            Create Dashboard
                        </Link>
                    </div>
                </div>

                <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="dashboard in dashboards"
                        :key="dashboard.id"
                        class="overflow-hidden rounded-lg bg-white shadow"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-md"
                                        :class="
                                            dashboard.is_default
                                                ? 'bg-indigo-500'
                                                : 'bg-gray-400'
                                        "
                                    >
                                        <svg
                                            class="h-5 w-5 text-white"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{ dashboard.name }}
                                        <span
                                            v-if="dashboard.is_default"
                                            class="ml-2 inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800"
                                        >
                                            Default
                                        </span>
                                    </h3>
                                    <p
                                        v-if="dashboard.description"
                                        class="text-sm text-gray-500"
                                    >
                                        {{ dashboard.description }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500">
                                    Last viewed:
                                    {{ dashboard.last_viewed_at || 'Never' }}
                                </p>
                            </div>
                            <div class="mt-4 flex space-x-3">
                                <Link
                                    :href="
                                        route('dashboard.show', dashboard.id)
                                    "
                                    class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-indigo-500"
                                >
                                    View
                                </Link>
                                <Link
                                    :href="
                                        route('dashboard.edit', dashboard.id)
                                    "
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
