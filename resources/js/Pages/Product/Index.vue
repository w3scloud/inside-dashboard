<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    productData: Object,
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
    <Head title="Products Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Products Analytics
                </h2>
                <div class="flex space-x-4">
                    <Link
                        :href="route('products.performance')"
                        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
                    >
                        Performance
                    </Link>
                    <Link
                        :href="route('products.inventory')"
                        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
                    >
                        Inventory
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
                            Total Sales
                        </dt>
                        <dd
                            class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            {{ formatCurrency(productData?.total_sales || 0) }}
                        </dd>
                    </div>

                    <div
                        class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6"
                    >
                        <dt class="truncate text-sm font-medium text-gray-500">
                            Total Products
                        </dt>
                        <dd
                            class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            {{ formatNumber(summary?.total_products || 0) }}
                        </dd>
                    </div>

                    <div
                        class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6"
                    >
                        <dt class="truncate text-sm font-medium text-gray-500">
                            Active Products
                        </dt>
                        <dd
                            class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            {{ formatNumber(summary?.active_products || 0) }}
                        </dd>
                    </div>

                    <div
                        class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6"
                    >
                        <dt class="truncate text-sm font-medium text-gray-500">
                            Average Order Value
                        </dt>
                        <dd
                            class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            {{
                                formatCurrency(
                                    productData?.avg_order_value || 0
                                )
                            }}
                        </dd>
                    </div>
                </div>

                <!-- Top Products Table -->
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3
                            class="text-lg font-medium leading-6 text-gray-900 mb-4"
                        >
                            Top Selling Products
                        </h3>

                        <div
                            v-if="productData?.products?.length > 0"
                            class="overflow-x-auto"
                        >
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Product
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Revenue
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Quantity Sold
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
                                        v-for="product in productData.products.slice(
                                            0,
                                            10
                                        )"
                                        :key="product.id"
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ product.title }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ product.vendor }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >
                                            {{
                                                formatCurrency(
                                                    product.total_sales
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >
                                            {{
                                                formatNumber(
                                                    product.total_quantity
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >
                                            {{
                                                formatNumber(
                                                    product.orders_count
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'products.show',
                                                        product.id
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
                                    d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No product data
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                No sales data available for the selected period.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
