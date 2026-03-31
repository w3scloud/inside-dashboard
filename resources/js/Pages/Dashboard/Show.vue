<template>
    <Head :title="section === 'orders' ? 'Orders Dashboard' : 'Dashboard'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-800"
                    >
                        {{ section === 'orders' ? 'Orders Dashboard' : dashboard.name }}
                    </h2>
                    <p
                        v-if="dashboard.description"
                        class="text-sm text-gray-600 mt-1"
                    >
                        {{ dashboard.description }}
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Date Range Picker -->
                    <DateRangePicker
                        v-model="dateRange"
                        @change="handleDateRangeChange"
                    />

                    <!-- Refresh Button -->
                    <button
                        @click="refreshData"
                        :disabled="loading"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:bg-gray-50 disabled:opacity-25 transition"
                    >
                        <svg
                            class="w-4 h-4 mr-2"
                            :class="{ 'animate-spin': loading }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Refresh
                    </button>

                    <!-- Add Widget Button (hidden for Orders dashboard) -->
                    <button
                        v-if="section !== 'orders'"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 active:bg-indigo-600 disabled:opacity-25 transition"
                        @click="showAddWidgetModal = true"
                    >
                        <svg
                            class="w-4 h-4 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Add Widget
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Loading State -->
                <div
                    v-if="loading && Object.keys(widgetData).length === 0"
                    class="flex justify-center py-20"
                >
                    <div class="text-center">
                        <div
                            class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"
                        ></div>
                        <div class="text-lg font-semibold mt-4 text-gray-700">
                            Loading dashboard...
                        </div>
                        <p class="mt-2 text-gray-600">
                            Please wait while we fetch your data.
                        </p>
                    </div>
                </div>

                <!-- Dashboard Content -->
                <!-- Special static Orders dashboard design -->
                <div v-else-if="section === 'orders'">
                    <!-- Hero banner -->
                    <div class="mb-6 rounded-xl bg-gradient-to-r from-indigo-700 via-indigo-800 to-indigo-900 text-white shadow-lg overflow-hidden">
                        <div class="px-8 py-6 flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-indigo-200">
                                    Shopify
                                </div>
                                <h1 class="mt-1 text-2xl font-semibold">
                                    Orders Dashboard
                                </h1>
                                <p class="mt-2 text-sm text-indigo-100 max-w-xl">
                                    An overview of Shopify orders and draft orders insights.
                                </p>
                            </div>
                            <div class="mt-4 md:mt-0 flex items-center space-x-4 text-sm">
                                <div class="bg-indigo-800/60 px-4 py-2 rounded-lg border border-indigo-600/50">
                                    <span class="text-indigo-200">Timeline Filter</span>
                                    <div class="font-medium">Last 12 Months</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI row mimicking Zoho style (static data) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                        <div class="rounded-md bg-indigo-800 text-indigo-50 px-4 py-4 shadow">
                            <div class="text-xs uppercase tracking-wide">
                                Orders · Jan 2026
                            </div>
                            <div class="mt-3 text-2xl font-semibold">
                                21<span class="text-red-300 text-base align-top ml-1">▼</span>
                            </div>
                            <div class="mt-1 text-[11px] text-indigo-200">
                                Dec 2025: 41
                            </div>
                        </div>

                        <div class="rounded-md bg-indigo-800 text-indigo-50 px-4 py-4 shadow">
                            <div class="text-xs uppercase tracking-wide">
                                Average Order Value · Jan 2026
                            </div>
                            <div class="mt-3 text-2xl font-semibold">
                                $0.23K<span class="text-red-300 text-base align-top ml-1">▼</span>
                            </div>
                            <div class="mt-1 text-[11px] text-indigo-200">
                                Dec 2025: $0.24K
                            </div>
                        </div>

                        <div class="rounded-md bg-indigo-800 text-indigo-50 px-4 py-4 shadow">
                            <div class="text-xs uppercase tracking-wide">
                                YTD Orders
                            </div>
                            <div class="mt-3 text-2xl font-semibold">
                                0.33K
                            </div>
                            <div class="mt-1 text-[11px] text-indigo-200">
                                Dec 2025: 0.30K
                            </div>
                        </div>

                        <div class="rounded-md bg-indigo-800 text-indigo-50 px-4 py-4 shadow">
                            <div class="text-xs uppercase tracking-wide">
                                Fulfilled Orders · Jan 2026
                            </div>
                            <div class="mt-3 text-2xl font-semibold">
                                19<span class="text-emerald-300 text-base align-top ml-1">▲</span>
                            </div>
                            <div class="mt-1 text-[11px] text-indigo-200">
                                Dec 2025: 16
                            </div>
                        </div>

                        <div class="rounded-md bg-indigo-800 text-indigo-50 px-4 py-4 shadow">
                            <div class="text-xs uppercase tracking-wide">
                                Fulfilment % · Jan 2026
                            </div>
                            <div class="mt-3 text-2xl font-semibold">
                                90.48%<span class="text-emerald-300 text-base align-top ml-1">▲</span>
                            </div>
                            <div class="mt-1 text-[11px] text-indigo-200">
                                Dec 2025: 89.02%
                            </div>
                        </div>
                    </div>

                    <!-- Static chart placeholders -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Monthly Orders Trend with Forecast
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="ordersTrendChartRef" class="w-full h-full"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Monthly Average Order Value vs Orders
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="aovOrdersChartRef" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Second row: Month-over-Month charts -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Month-over-Month Orders Trend
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="momOrdersTrendRef" class="w-full h-full"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Month-over-Month Orders Growth %
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="momOrdersGrowthRef" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Third row: Orders by source / cumulative MTD -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Orders by Source
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="ordersBySourceRef" class="w-full h-full"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Cumulative Orders MTD
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="cumulativeOrdersMtdRef" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Fourth row: Financial / Fulfilment status -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Orders by Financial Status
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="ordersByFinancialStatusRef" class="w-full h-full"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Orders by Fulfilment Status
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="ordersByFulfilmentStatusRef" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Fifth row: Transaction status / Gift card usage -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Monthly Orders Transaction Status
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="ordersTransactionStatusRef" class="w-full h-full"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Gift Card Usage in Orders
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="giftCardUsageRef" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Sixth row: Devices / Restocked value -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Orders by Devices
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="ordersByDevicesRef" class="w-full h-full"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Monthly Restocked Orders Value
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="restockedOrdersValueRef" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Seventh row: Draft orders trend / sales -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Monthly Draft Orders Trend
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="draftOrdersTrendRef" class="w-full h-full"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Monthly Draft Orders Sales
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="draftOrdersSalesRef" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Eighth row: Draft billing location / discounts -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Draft Orders by Billing Location
                                </h3>
                            </div>
                            <div class="h-64">
                                <DraftBillingLocationMap />
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Discount Applied on Draft Orders
                                </h3>
                            </div>
                            <div class="h-64">
                                <canvas ref="draftOrdersDiscountRef" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generic dashboard content with widgets -->
                <div v-else>
                    <!-- Quick Stats Row -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-6 w-6 text-green-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="text-sm font-medium text-gray-500 truncate"
                                            >
                                                Total Revenue
                                            </dt>
                                            <dd
                                                class="text-lg font-medium text-gray-900"
                                            >
                                                ${{
                                                    salesMetrics.totalRevenue.toLocaleString()
                                                }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-6 w-6 text-blue-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="text-sm font-medium text-gray-500 truncate"
                                            >
                                                Total Orders
                                            </dt>
                                            <dd
                                                class="text-lg font-medium text-gray-900"
                                            >
                                                {{
                                                    salesMetrics.totalOrders.toLocaleString()
                                                }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-6 w-6 text-purple-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="text-sm font-medium text-gray-500 truncate"
                                            >
                                                Products
                                            </dt>
                                            <dd
                                                class="text-lg font-medium text-gray-900"
                                            >
                                                {{
                                                    productMetrics.totalProducts.toLocaleString()
                                                }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-6 w-6 text-orange-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="text-sm font-medium text-gray-500 truncate"
                                            >
                                                Customers
                                            </dt>
                                            <dd
                                                class="text-lg font-medium text-gray-900"
                                            >
                                                {{
                                                    customerMetrics.totalCustomers.toLocaleString()
                                                }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Widget Grid -->
                    <DashboardGrid
                        v-if="layout.length > 0"
                        v-model:layout="layout"
                        :widget-data="widgetData"
                        :available-widgets="availableWidgets"
                        :loading="loading"
                        @remove-widget="removeWidget"
                    />

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No widgets
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Get started by adding a widget to your dashboard.
                        </p>
                        <div class="mt-6">
                            <button
                                @click="showAddWidgetModal = true"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg
                                    class="-ml-1 mr-2 h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Add Widget
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Widget Modal (not used for Orders dashboard) -->
        <AddWidgetModal
            v-if="section !== 'orders'"
            v-model:show="showAddWidgetModal"
            :available-widgets="availableWidgets"
            @add-widget="addWidget"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardGrid from '@/Components/Dashboard/DashboardGrid.vue';
import Widget from '@/Components/Dashboard/Widget.vue';
import DateRangePicker from '@/Components/Dashboard/DateRangePicker.vue';
import AddWidgetModal from '@/Components/Dashboard/AddWidgetModal.vue';
import DraftBillingLocationMap from '@/Components/Dashboard/DraftBillingLocationMap.vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

// Props from Laravel
const props = defineProps({
    dashboard: Object,
    store: Object,
    availableWidgets: Array,
    section: {
        type: String,
        default: null,
    },
});

// Reactive data
const loading = ref(false);
const widgetData = ref({});
const showAddWidgetModal = ref(false);
const refreshInterval = ref(null);
const showDebugInfo = ref(false); // Set to false in production

// Chart refs & instances (Orders dashboard only)
const ordersTrendChartRef = ref(null);
const aovOrdersChartRef = ref(null);
let ordersTrendChartInstance = null;
let aovOrdersChartInstance = null;
const momOrdersTrendRef = ref(null);
const momOrdersGrowthRef = ref(null);
let momOrdersTrendInstance = null;
let momOrdersGrowthInstance = null;
const ordersBySourceRef = ref(null);
const ordersByFinancialStatusRef = ref(null);
const ordersByFulfilmentStatusRef = ref(null);
let ordersBySourceInstance = null;
let ordersByFinancialStatusInstance = null;
let ordersByFulfilmentStatusInstance = null;
const ordersTransactionStatusRef = ref(null);
const giftCardUsageRef = ref(null);
const ordersByDevicesRef = ref(null);
const restockedOrdersValueRef = ref(null);
let ordersTransactionStatusInstance = null;
let giftCardUsageInstance = null;
let ordersByDevicesInstance = null;
let restockedOrdersValueInstance = null;
const cumulativeOrdersMtdRef = ref(null);
let cumulativeOrdersMtdInstance = null;
const draftOrdersTrendRef = ref(null);
const draftOrdersSalesRef = ref(null);
const draftOrdersDiscountRef = ref(null);
let draftOrdersTrendInstance = null;
let draftOrdersSalesInstance = null;
let draftOrdersDiscountInstance = null;

// Initialize dateRange with default values
const dateRange = ref({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split('T')[0], // 30 days ago
    end: new Date().toISOString().split('T')[0], // today
});

// Layout computed property
const layout = computed({
    get() {
        return props.dashboard?.layout || [];
    },
    set(newLayout) {
        updateLayout(newLayout);
    },
});

// Computed metrics for quick stats
const salesMetrics = computed(() => ({
    totalRevenue: widgetData.value.sales_overview?.total_revenue || 0,
    totalOrders: widgetData.value.sales_overview?.total_orders || 0,
    averageOrderValue:
        widgetData.value.sales_overview?.average_order_value || 0,
    growthRate: widgetData.value.sales_overview?.growth_rate || 0,
}));

const productMetrics = computed(() => ({
    totalProducts: widgetData.value.product_performance?.total_products || 0,
    topProducts: widgetData.value.product_performance?.top_products || [],
}));

const customerMetrics = computed(() => ({
    totalCustomers: widgetData.value.customer_analytics?.total_customers || 0,
    newCustomers: widgetData.value.customer_analytics?.new_customers || 0,
    returningCustomers:
        widgetData.value.customer_analytics?.returning_customers || 0,
}));

// Methods
const fetchData = async () => {
    if (!props.store?.id) {
        console.error('Missing store ID');
        return;
    }

    loading.value = true;

    try {
        console.log('Fetching dashboard data...', {
            store_id: props.store.id,
            date_range: dateRange.value,
        });

        const response = await axios.get('/api/analytics/dashboard', {
            params: {
                store_id: props.store.id,
                start_date: dateRange.value.start,
                end_date: dateRange.value.end,
            },
        });

        console.log('API Response:', response.data);

        if (response.data.success) {
            const apiData = response.data.data;

            // For now we don't rely on API‑driven widget data (orders dashboard is static),
            // so just keep the raw analytics blob available if needed later.
            widgetData.value = apiData || {};

            console.log(
                'Enhanced widget data mapped:',
                Object.keys(widgetData.value)
            );
        } else {
            console.error('API returned error:', response.data);
        }
    } catch (error) {
        console.error('Error fetching data:', error);

        if (error.response?.status === 404) {
            console.error('Store not found or no active store');
        } else if (error.response?.status === 500) {
            console.error('Server error:', error.response.data);
        }
    } finally {
        loading.value = false;
    }
};

const handleDateRangeChange = (newDateRange) => {
    console.log('Date range changed:', newDateRange);
    dateRange.value = {
        start: newDateRange.start,
        end: newDateRange.end,
    };
    // fetchData will be called automatically via watcher
};

const refreshData = () => {
    console.log('Refreshing data...');
    fetchData();
};

const updateLayout = async (newLayout) => {
    try {
        console.log('Updating layout - RAW:', newLayout);

        // Clean and validate the layout data before sending
        const cleanLayout = newLayout
            .filter((item) => item && typeof item === 'object')
            .map((item) => ({
                i: item.i || item.id || `widget_${Date.now()}_${Math.random()}`,
                x: parseInt(item.x) || 0,
                y: parseInt(item.y) || 0,
                w: parseInt(item.w) || 4,
                h: parseInt(item.h) || 4,
                ...(item.widget_id && { widget_id: item.widget_id }),
            }))
            .filter((item) => item.i);

        console.log('Cleaned layout:', cleanLayout);

        if (cleanLayout.length === 0) {
            console.warn('No valid layout items to update');
            return;
        }

        const response = await axios.put(
            `/dashboard/${props.dashboard.id}/layout`,
            {
                layout: cleanLayout,
            }
        );

        console.log('Layout updated successfully');
    } catch (error) {
        console.error('Error updating layout:', error);

        if (error.response?.status === 422) {
            console.error('Validation errors:', error.response.data.errors);
            console.error('Failed layout data:', error.config.data);
        }
    }
};

const addWidget = async (widgetType, position) => {
    try {
        const widgetData = {
            widget_type: widgetType,
            position: {
                x: parseInt(position?.x) || 0,
                y: parseInt(position?.y) || 0,
                w: parseInt(position?.w) || 4,
                h: parseInt(position?.h) || 4,
            },
        };

        console.log('Sending widget data:', widgetData);

        const response = await axios.post(
            `/dashboard/${props.dashboard.id}/widget`,
            widgetData
        );

        console.log('API Response:', response.data);

        if (response.data.success) {
            console.log('✅ Widget added successfully');

            // Create properly structured widget for layout
            const newWidget = {
                i: response.data.widget.i,
                x: response.data.widget.x,
                y: response.data.widget.y,
                w: response.data.widget.w,
                h: response.data.widget.h,
                ...(response.data.widget.widget_id && {
                    widget_id: response.data.widget.widget_id,
                }),
            };

            console.log('Adding widget to layout:', newWidget);

            // Update layout with proper structure
            const currentLayout = Array.isArray(layout.value)
                ? layout.value
                : [];
            const newLayout = [...currentLayout, newWidget];

            // Update local state
            layout.value = newLayout;
            showAddWidgetModal.value = false;

            // Refresh data
            await fetchData();
        } else {
            console.error('❌ API returned success=false:', response.data);
            alert('Server error: ' + (response.data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('❌ Exception in addWidget:', error);

        if (error.response?.status === 422) {
            console.error('Validation Errors:', error.response.data.errors);
            alert(
                'Validation Error: ' +
                    JSON.stringify(error.response.data.errors)
            );
        } else if (error.response?.status === 404) {
            alert('Dashboard not found');
        } else {
            alert(
                'Server Error: ' +
                    (error.response?.data?.message ||
                        error.response?.data?.error ||
                        'Unknown')
            );
        }
    }
};

const removeWidget = async (widgetId) => {
    try {
        console.log('Removing widget:', widgetId);

        const response = await axios.delete(
            `/dashboard/${props.dashboard.id}/widget/${widgetId}`
        );

        if (response.data.success) {
            console.log('Widget removed successfully');

            // Update local layout by removing the widget
            const newLayout = layout.value.filter(
                (widget) => widget.i !== widgetId
            );
            layout.value = newLayout;

            console.log('Widget removed from dashboard successfully!');
        } else {
            console.error('Failed to remove widget:', response.data);
        }
    } catch (error) {
        console.error('Error removing widget:', error);
        alert('Failed to remove widget. Please try again.');
    }
};

// Watch for layout changes
watch(
    layout,
    (newLayout, oldLayout) => {
        console.log('=== LAYOUT CHANGED ===');
        console.log('From:', oldLayout?.length || 0, 'items');
        console.log('To:', newLayout?.length || 0, 'items');
        console.log('New layout:', newLayout);
    },
    { deep: true }
);

// Watch for widget data changes
watch(
    widgetData,
    (newData) => {
        console.log('=== WIDGET DATA CHANGED ===');
        console.log('Available widget data keys:', Object.keys(newData));
    },
    { deep: true }
);

// Watch for date range changes
watch(
    dateRange,
    (newRange) => {
        console.log('Date range changed, fetching new data...', newRange);
        fetchData();
    },
    { deep: true }
);

// Lifecycle hooks
onMounted(() => {
    console.log('Dashboard Show mounted');
    console.log('Initial props:', {
        dashboard: props.dashboard,
        store: props.store,
        availableWidgets: props.availableWidgets?.length || 0,
    });

    // For static Orders dashboard we don't need live API data; charts use fixed data below.
    if (props.section !== 'orders') {
        fetchData();
    }

    // Set up auto-refresh every 5 minutes
    if (props.section !== 'orders') {
        refreshInterval.value = setInterval(() => {
            if (!loading.value) {
                fetchData();
            }
        }, 5 * 60 * 1000);
    }

    // Initialise static charts for Orders dashboard
    if (props.section === 'orders') {
        // Example months and data broadly matching your screenshot
        const labels = [
            'Apr 2025',
            'Jun 2025',
            'Aug 2025',
            'Oct 2025',
            'Dec 2025',
            'Feb 2026',
            'Apr 2026',
            'Jun 2026',
        ];

        const ordersData = [38, 42, 49, 51, 42, 47.5, 49.5, 51.4];
        const forecastData = [46.4, 47.6, 48.6, 49.4, 50.3, 51.4];

        if (ordersTrendChartRef.value) {
            ordersTrendChartInstance = new Chart(ordersTrendChartRef.value, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Orders',
                            data: ordersData,
                            borderColor: '#4f46e5',
                            backgroundColor: '#4f46e5',
                            tension: 0.3,
                            pointRadius: 4,
                        },
                        {
                            label: 'Forecast',
                            data: forecastData,
                            borderColor: '#a855f7',
                            borderDash: [6, 4],
                            backgroundColor: '#a855f7',
                            tension: 0.3,
                            pointRadius: 3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                        },
                    },
                },
            });
        }

        // Average Order Value vs Orders (bar + line-like bubbles)
        const aovLabels = [
            'Apr 2025',
            'May 2025',
            'Jun 2025',
            'Jul 2025',
            'Aug 2025',
            'Sep 2025',
            'Oct 2025',
            'Nov 2025',
            'Dec 2025',
            'Jan 2026',
        ];
        const aovValues = [
            248.68, 195.12, 205.81, 200.06, 259.03, 259.0, 227.2, 230.37, 259.38,
            228.12,
        ];
        const ordersBubbles = [31, 42, 33, 48, 49, 51, 40, 42, 41, 21];

        if (aovOrdersChartRef.value) {
            aovOrdersChartInstance = new Chart(aovOrdersChartRef.value, {
                data: {
                    labels: aovLabels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Average Order Value',
                            data: aovValues,
                            backgroundColor: '#22c55e80',
                            borderColor: '#22c55e',
                            borderWidth: 1,
                            yAxisID: 'y',
                        },
                        {
                            type: 'bubble',
                            label: 'Orders',
                            data: ordersBubbles.map((v, idx) => ({
                                x: idx,
                                y: aovValues[idx],
                                r: 10 + (v / 60) * 10,
                            })),
                            backgroundColor: '#6366f180',
                            borderColor: '#6366f1',
                            yAxisID: 'y',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Average Order Value (USD)',
                            },
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                            },
                        },
                    },
                },
            });
        }

        // Month-over-Month Orders Trend (bars per year)
        const monthLabels = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec',
        ];
        const orders2024 = [30, 37, 34, 31, 33, 37, 48, 51, 42, 37, 40, 42];
        const orders2025 = [30, 34, 34, 32, 39, 42, 49, 48, 40, 38, 38, 36];
        const orders2026 = [21, 0, 0, 0, 0, 26, 33, 0, 0, 0, 0, 41];

        if (momOrdersTrendRef.value) {
            momOrdersTrendInstance = new Chart(momOrdersTrendRef.value, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: '2024',
                            data: orders2024,
                            backgroundColor: '#93c5fd',
                        },
                        {
                            label: '2025',
                            data: orders2025,
                            backgroundColor: '#fecaca',
                        },
                        {
                            label: '2026',
                            data: orders2026,
                            backgroundColor: '#c4b5fd',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Orders',
                            },
                        },
                    },
                },
            });
        }

        // Month-over-Month Orders Growth % (by year)
        const growth2024 = Array(12).fill(100);
        const growth2025 = [70, 100, 100, 79, 97, 100, 127, 130, 100, 100, 100, 100];
        const growth2026 = Array(12).fill(100);

        if (momOrdersGrowthRef.value) {
            momOrdersGrowthInstance = new Chart(momOrdersGrowthRef.value, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: '2024',
                            data: growth2024,
                            backgroundColor: '#93c5fd',
                        },
                        {
                            label: '2025',
                            data: growth2025,
                            backgroundColor: '#fecaca',
                        },
                        {
                            label: '2026',
                            data: growth2026,
                            backgroundColor: '#c4b5fd',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.raw}%`,
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (v) => `${v}%`,
                            },
                            title: {
                                display: true,
                                text: 'Growth %',
                            },
                        },
                    },
                },
            });
        }

        // Orders by Source (stacked area‑like chart)
        const sourceLabels = [
            'Apr 2025',
            'May 2025',
            'Jun 2025',
            'Jul 2025',
            'Aug 2025',
            'Sep 2025',
            'Oct 2025',
            'Nov 2025',
            'Dec 2025',
            'Jan 2026',
        ];
        const posData = [10, 12, 11, 13, 14, 16, 15, 13, 12, 11];
        const shopifyDraftData = [8, 9, 10, 11, 12, 13, 12, 11, 10, 9];
        const webData = [7, 8, 9, 10, 11, 12, 13, 12, 11, 10];

        if (ordersBySourceRef.value) {
            ordersBySourceInstance = new Chart(ordersBySourceRef.value, {
                type: 'line',
                data: {
                    labels: sourceLabels,
                    datasets: [
                        {
                            label: 'pos',
                            data: posData,
                            backgroundColor: '#bfdbfe80',
                            borderColor: '#60a5fa',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                        },
                        {
                            label: 'shopify_draft_order',
                            data: shopifyDraftData,
                            backgroundColor: '#fde68a80',
                            borderColor: '#fbbf24',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                        },
                        {
                            label: 'web',
                            data: webData,
                            backgroundColor: '#fecaca80',
                            borderColor: '#f472b6',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Orders' },
                        },
                    },
                },
            });
        }

        // Orders by Financial Status (stacked bar)
        const financialLabels = sourceLabels;
        const statusColors = {
            authorized: '#22c55e',
            paid: '#3b82f6',
            partially_paid: '#a855f7',
            partially_refunded: '#f97316',
            pending: '#eab308',
            refunded: '#ef4444',
            voided: '#6b7280',
        };

        const statusData = {
            authorized: [1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
            paid: [35, 36, 37, 38, 39, 40, 41, 40, 39, 38],
            partially_paid: [5, 6, 5, 6, 7, 7, 6, 6, 5, 5],
            partially_refunded: [4, 3, 4, 3, 4, 3, 4, 3, 4, 3],
            pending: [8, 7, 8, 7, 8, 7, 8, 7, 8, 7],
            refunded: [3, 4, 3, 4, 3, 4, 3, 4, 3, 4],
            voided: [4, 5, 4, 5, 4, 5, 4, 5, 4, 5],
        };

        if (ordersByFinancialStatusRef.value) {
            ordersByFinancialStatusInstance = new Chart(
                ordersByFinancialStatusRef.value,
                {
                    type: 'bar',
                    data: {
                        labels: financialLabels,
                        datasets: Object.keys(statusData).map((key) => ({
                            label: key.replace('_', ' '),
                            data: statusData[key],
                            backgroundColor: statusColors[key],
                            stack: 'financial',
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                        scales: {
                            x: { stacked: true },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: (v) => `${v}%`,
                                },
                            },
                        },
                    },
                }
            );
        }

        // Orders by Fulfilment Status (stacked bar)
        const fulfilmentLabels = sourceLabels;
        const fulfilmentData = {
            fulfilled: [35, 40, 42, 45, 46, 48, 49, 47, 46, 70],
            partial: [30, 25, 23, 20, 19, 21, 22, 25, 24, 10],
            restocked: [35, 35, 35, 35, 35, 31, 29, 28, 30, 20],
        };
        const fulfilmentColors = {
            fulfilled: '#3b82f6',
            partial: '#f97316',
            restocked: '#eab308',
        };

        if (ordersByFulfilmentStatusRef.value) {
            ordersByFulfilmentStatusInstance = new Chart(
                ordersByFulfilmentStatusRef.value,
                {
                    type: 'bar',
                    data: {
                        labels: fulfilmentLabels,
                        datasets: Object.keys(fulfilmentData).map((key) => ({
                            label: key,
                            data: fulfilmentData[key],
                            backgroundColor: fulfilmentColors[key],
                            stack: 'fulfilment',
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                        scales: {
                            x: { stacked: true },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: (v) => `${v}%`,
                                },
                            },
                        },
                    },
                }
            );
        }

        // Monthly Orders Transaction Status (stacked bar)
        const txLabels = ['May 2025', 'Jun 2025', 'Jul 2025', 'Sep 2025', 'Nov 2025', 'Dec 2025'];
        const txStatusColors = {
            error: '#f97316',
            failure: '#ef4444',
            pending: '#eab308',
            success: '#22c55e',
        };
        const txData = {
            error: [30, 40, 30, 20, 0, 30],
            failure: [0, 0, 0, 20, 0, 0],
            pending: [0, 0, 0, 10, 0, 0],
            success: [70, 60, 70, 50, 100, 70],
        };

        if (ordersTransactionStatusRef.value) {
            ordersTransactionStatusInstance = new Chart(
                ordersTransactionStatusRef.value,
                {
                    type: 'bar',
                    data: {
                        labels: txLabels,
                        datasets: Object.keys(txData).map((key) => ({
                            label: key,
                            data: txData[key],
                            backgroundColor: txStatusColors[key],
                            stack: 'tx',
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                        scales: {
                            x: { stacked: true },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: (v) => `${v}%`,
                                },
                                title: { display: true, text: 'Transactions' },
                            },
                        },
                    },
                }
            );
        }

        // Gift Card Usage in Orders (grouped bar)
        const giftLabels = sourceLabels;
        const giftNo = [27, 32, 27, 39, 42, 45, 36, 32, 32, 19];
        const giftYes = [28, 32, 28, 40, 44, 44, 38, 32, 38, 16];

        if (giftCardUsageRef.value) {
            giftCardUsageInstance = new Chart(giftCardUsageRef.value, {
                type: 'bar',
                data: {
                    labels: giftLabels,
                    datasets: [
                        {
                            label: 'No',
                            data: giftNo,
                            backgroundColor: '#f97316',
                        },
                        {
                            label: 'Yes',
                            data: giftYes,
                            backgroundColor: '#22c55e',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Orders' } },
                    },
                },
            });
        }

        // Orders by Devices (bubble chart)
        const deviceLabels = ['Android', 'Macintosh', 'Windows', 'iPad', 'iPhone'];
        const deviceValues = [250, 416, 423, 131, 230];

        if (ordersByDevicesRef.value) {
            ordersByDevicesInstance = new Chart(ordersByDevicesRef.value, {
                type: 'bubble',
                data: {
                    labels: deviceLabels,
                    datasets: [
                        {
                            label: 'Devices',
                            data: deviceValues.map((v, idx) => ({
                                x: idx + 1,
                                y: v,
                                r: 10 + (v / 450) * 20,
                            })),
                            backgroundColor: [
                                '#93c5fd',
                                '#c4b5fd',
                                '#a7f3d0',
                                '#fde68a',
                                '#fecaca',
                            ],
                            borderColor: '#4b5563',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            ticks: {
                                callback: (v) => deviceLabels[v - 1] ?? '',
                            },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Orders' },
                        },
                    },
                },
            }
            );
        }

        // Monthly Restocked Orders Value (step line)
        const restockLabels = sourceLabels;
        const restockValues = [2000, 2000, 2500, 3000, 2800, 3000, 3500, 3500, 3000, 67];

        if (restockedOrdersValueRef.value) {
            restockedOrdersValueInstance = new Chart(restockedOrdersValueRef.value, {
                type: 'line',
                data: {
                    labels: restockLabels,
                    datasets: [
                        {
                            label: 'Restocked Value',
                            data: restockValues,
                            borderColor: '#4f46e5',
                            backgroundColor: '#4f46e5',
                            stepped: true,
                            pointRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { display: true, text: 'Value (USD)' },
                            ticks: {
                                callback: (v) => `${v / 1000}K`,
                            },
                        },
                    },
                },
            });
        }

        // Cumulative Orders MTD (simple cumulative line)
        const mtdLabels = ['Day 1', 'Day 5', 'Day 10', 'Day 15', 'Day 20', 'Day 25', 'Day 30'];
        const mtdValues = [3, 8, 15, 22, 28, 31, 33];

        if (cumulativeOrdersMtdRef.value) {
            cumulativeOrdersMtdInstance = new Chart(cumulativeOrdersMtdRef.value, {
                type: 'line',
                data: {
                    labels: mtdLabels,
                    datasets: [
                        {
                            label: 'Cumulative Orders',
                            data: mtdValues,
                            borderColor: '#0ea5e9',
                            backgroundColor: '#0ea5e9',
                            tension: 0.2,
                            pointRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Orders' },
                        },
                    },
                },
            });
        }

        // Monthly Draft Orders Trend
        const draftTrendLabels = sourceLabels;
        const draftTrendValues = [17, 33, 29, 37, 40, 31, 28, 31, 20, 8];

        if (draftOrdersTrendRef.value) {
            draftOrdersTrendInstance = new Chart(draftOrdersTrendRef.value, {
                type: 'line',
                data: {
                    labels: draftTrendLabels,
                    datasets: [
                        {
                            label: 'Draft Orders',
                            data: draftTrendValues,
                            borderColor: '#4f46e5',
                            backgroundColor: '#4f46e5',
                            tension: 0.3,
                            pointRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { display: true, text: 'Draft Orders' },
                        },
                    },
                },
            });
        }

        // Monthly Draft Orders Sales
        const draftSalesLabels = sourceLabels;
        const draftSalesValues = [4000, 6000, 4500, 7700, 10000, 10000, 5500, 5600, 8300, 2000];

        if (draftOrdersSalesRef.value) {
            draftOrdersSalesInstance = new Chart(draftOrdersSalesRef.value, {
                type: 'line',
                data: {
                    labels: draftSalesLabels,
                    datasets: [
                        {
                            label: 'Draft Sales',
                            data: draftSalesValues,
                            borderColor: '#16a34a',
                            backgroundColor: '#16a34a',
                            tension: 0.3,
                            pointRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: (v) => `${v / 1000}K`,
                            },
                            title: { display: true, text: 'Sales (USD)' },
                        },
                    },
                },
            });
        }

        // Discount Applied on Draft Orders (bar + bubbles)
        const discountLabels = sourceLabels;
        const discountAmount = [71, 117, 67, 116, 163, 169, 95, 114, 138, 22];
        const discountDraftOrders = [17, 33, 19, 29, 37, 40, 27, 31, 28, 22];

        if (draftOrdersDiscountRef.value) {
            draftOrdersDiscountInstance = new Chart(draftOrdersDiscountRef.value, {
                data: {
                    labels: discountLabels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Applied Discount Amount',
                            data: discountAmount,
                            backgroundColor: '#fbbf24',
                            borderColor: '#f59e0b',
                            borderWidth: 1,
                            yAxisID: 'y',
                        },
                        {
                            type: 'bubble',
                            label: 'Draft Orders',
                            data: discountDraftOrders.map((v, idx) => ({
                                x: idx,
                                y: discountAmount[idx],
                                r: 10 + (v / 50) * 8,
                            })),
                            backgroundColor: '#f9731680',
                            borderColor: '#ea580c',
                            yAxisID: 'y',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { display: true, text: 'Discount Amount (USD)' },
                        },
                    },
                },
            });
        }
    }
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }

    if (ordersTrendChartInstance) {
        ordersTrendChartInstance.destroy();
        ordersTrendChartInstance = null;
    }

    if (aovOrdersChartInstance) {
        aovOrdersChartInstance.destroy();
        aovOrdersChartInstance = null;
    }

    if (momOrdersTrendInstance) {
        momOrdersTrendInstance.destroy();
        momOrdersTrendInstance = null;
    }

    if (momOrdersGrowthInstance) {
        momOrdersGrowthInstance.destroy();
        momOrdersGrowthInstance = null;
    }

    if (ordersBySourceInstance) {
        ordersBySourceInstance.destroy();
        ordersBySourceInstance = null;
    }

    if (ordersByFinancialStatusInstance) {
        ordersByFinancialStatusInstance.destroy();
        ordersByFinancialStatusInstance = null;
    }

    if (ordersByFulfilmentStatusInstance) {
        ordersByFulfilmentStatusInstance.destroy();
        ordersByFulfilmentStatusInstance = null;
    }

    if (ordersTransactionStatusInstance) {
        ordersTransactionStatusInstance.destroy();
        ordersTransactionStatusInstance = null;
    }

    if (giftCardUsageInstance) {
        giftCardUsageInstance.destroy();
        giftCardUsageInstance = null;
    }

    if (ordersByDevicesInstance) {
        ordersByDevicesInstance.destroy();
        ordersByDevicesInstance = null;
    }

    if (restockedOrdersValueInstance) {
        restockedOrdersValueInstance.destroy();
        restockedOrdersValueInstance = null;
    }

    if (draftOrdersTrendInstance) {
        draftOrdersTrendInstance.destroy();
        draftOrdersTrendInstance = null;
    }

    if (draftOrdersSalesInstance) {
        draftOrdersSalesInstance.destroy();
        draftOrdersSalesInstance = null;
    }

    if (cumulativeOrdersMtdInstance) {
        cumulativeOrdersMtdInstance.destroy();
        cumulativeOrdersMtdInstance = null;
    }

    if (draftOrdersDiscountInstance) {
        draftOrdersDiscountInstance.destroy();
        draftOrdersDiscountInstance = null;
    }
});
</script>
