<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customerData: Object,
    summary: Object,
    date_range: Object,
    filters: Object,
    store: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US').format(value);
};
</script>

<template>
    <Head title="Customers Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Customers Analytics
                </h2>
                <div class="flex space-x-4">
                    <Link
                        :href="route('customers.segments')"
                        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
                    >
                        Customer Segments
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8"
                >
                    <div
                        class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6"
                    >
                        <dt class="truncate text-sm font-medium text-gray-500">
                            Total Customers
                        </dt>
                        <dd
                            class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            {{ formatNumber(summary?.total_customers || 0) }}
                        </dd>
                    </div>

                    <div
                        class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6"
                    >
                        <dt class="truncate text-sm font-medium text-gray-500">
                            New Customers
                        </dt>
                        <dd
                            class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            {{ formatNumber(summary?.new_customers || 0) }}
                        </dd>
                    </div>

                    <div
                        class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6"
                    >
                        <dt class="truncate text-sm font-medium text-gray-500">
                            Returning Customers
                        </dt>
                        <dd
                            class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            {{
                                formatNumber(summary?.returning_customers || 0)
                            }}
                        </dd>
                    </div>

                    <div
                        class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6"
                    >
                        <dt class="truncate text-sm font-medium text-gray-500">
                            Average Customer Value
                        </dt>
                        <dd
                            class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            {{
                                formatCurrency(summary?.avg_customer_value || 0)
                            }}
                        </dd>
                    </div>
                </div>

                <!-- Top Customers Table -->
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3
                            class="text-lg font-medium leading-6 text-gray-900 mb-4"
                        >
                            Top Customers
                        </h3>

                        <div
                            v-if="summary?.top_customers?.length > 0"
                            class="overflow-x-auto"
                        >
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Customer
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Email
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Total Spent
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Orders
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-200 bg-white"
                                >
                                    <tr
                                        v-for="customer in summary.top_customers"
                                        :key="customer.id"
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ customer.first_name }}
                                                {{ customer.last_name }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >
                                            {{ customer.email }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >
                                            {{
                                                formatCurrency(
                                                    customer.total_spent
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >
                                            {{
                                                formatNumber(
                                                    customer.orders_count
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'customers.show',
                                                        customer.id
                                                    )
                                                "
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View Details
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-12">
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
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No customer data
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                No customer data available for the selected
                                period.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
