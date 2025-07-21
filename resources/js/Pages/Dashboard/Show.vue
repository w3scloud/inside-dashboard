<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-800"
                    >
                        {{ dashboard.name }}
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

                    <!-- Add Widget Button -->
                    <button
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

        <!-- Add Widget Modal -->
        <AddWidgetModal
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
import axios from 'axios';

// Props from Laravel
const props = defineProps({
    dashboard: Object,
    store: Object,
    availableWidgets: Array,
});

// Reactive data
const loading = ref(false);
const widgetData = ref({});
const showAddWidgetModal = ref(false);
const refreshInterval = ref(null);
const showDebugInfo = ref(true); // Set to false in production

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

            // Enhanced widget data mapping for ALL widget types
            widgetData.value = {
                // Existing widgets
                sales_overview: {
                    total_revenue:
                        apiData.sales_analytics?.summary?.total_sales || 0,
                    total_orders:
                        apiData.sales_analytics?.summary?.total_orders || 0,
                    average_order_value:
                        apiData.sales_analytics?.summary?.average_order_value ||
                        0,
                    growth_rate:
                        apiData.sales_analytics?.summary?.growth_rate || 0,
                    daily_sales:
                        apiData.sales_analytics?.trends?.daily_sales || {},
                },

                product_performance: {
                    top_products: apiData.product_analytics?.top_products || [],
                    total_products:
                        apiData.product_analytics?.summary?.total_products || 0,
                    performance_metrics:
                        apiData.product_analytics?.performance_metrics || {},
                },

                customer_analytics: {
                    total_customers:
                        apiData.customer_analytics?.summary?.total_customers ||
                        0,
                    new_customers:
                        apiData.customer_analytics?.summary
                            ?.new_customers_30d || 0,
                    returning_customers:
                        apiData.customer_analytics?.summary
                            ?.returning_customers || 0,
                    segments: apiData.customer_analytics?.segments || [],
                },

                inventory_status: {
                    total_products:
                        apiData.inventory_analytics?.summary?.total_products ||
                        0,
                    in_stock:
                        apiData.inventory_analytics?.inventory_status
                            ?.in_stock || 0,
                    low_stock:
                        apiData.inventory_analytics?.inventory_status
                            ?.low_stock || 0,
                    out_of_stock:
                        apiData.inventory_analytics?.inventory_status
                            ?.out_of_stock || 0,
                    low_stock_products:
                        apiData.inventory_analytics?.low_stock_products || [],
                },

                performance_metrics: apiData.performance_metrics || {},
                data_sources: apiData.data_sources || {},

                // NEW WIDGET DATA MAPPINGS
                conversion_funnel: {
                    visitors: apiData.traffic_analytics?.visitors || 1500,
                    conversions:
                        apiData.sales_analytics?.summary?.total_orders || 0,
                    conversion_rate:
                        apiData.sales_analytics?.conversion_rate || 2.5,
                    funnel_steps: [
                        {
                            name: 'Visitors',
                            value: apiData.traffic_analytics?.visitors || 1500,
                        },
                        {
                            name: 'Product Views',
                            value: apiData.product_analytics?.views || 800,
                        },
                        {
                            name: 'Add to Cart',
                            value:
                                apiData.sales_analytics?.cart_additions || 250,
                        },
                        {
                            name: 'Checkout',
                            value: apiData.sales_analytics?.checkouts || 120,
                        },
                        {
                            name: 'Purchase',
                            value:
                                apiData.sales_analytics?.summary
                                    ?.total_orders || 0,
                        },
                    ],
                },

                revenue_trends: {
                    total_revenue:
                        apiData.sales_analytics?.summary?.total_sales || 0,
                    total_orders:
                        apiData.sales_analytics?.summary?.total_orders || 0,
                    average_order_value:
                        apiData.sales_analytics?.summary?.average_order_value ||
                        0,
                    growth_rate:
                        apiData.sales_analytics?.summary?.growth_rate || 0,
                    trend_data: apiData.sales_analytics?.trends || {},
                    monthly_revenue:
                        apiData.sales_analytics?.monthly_trends || {},
                },

                traffic_sources: {
                    total_visitors:
                        apiData.traffic_analytics?.total_visitors || 1200,
                    sources: apiData.traffic_analytics?.sources || [],
                    top_sources: [
                        { name: 'Direct', visitors: 420, percentage: 35 },
                        {
                            name: 'Search Engine',
                            visitors: 336,
                            percentage: 28,
                        },
                        { name: 'Social Media', visitors: 228, percentage: 19 },
                        { name: 'Email', visitors: 144, percentage: 12 },
                        { name: 'Referral', visitors: 72, percentage: 6 },
                    ],
                },

                order_fulfillment: {
                    pending_orders: apiData.fulfillment_analytics?.pending || 5,
                    fulfilled_orders:
                        apiData.fulfillment_analytics?.fulfilled || 45,
                    shipped_orders:
                        apiData.fulfillment_analytics?.shipped || 38,
                    delivered_orders:
                        apiData.fulfillment_analytics?.delivered || 35,
                    average_fulfillment_time:
                        apiData.fulfillment_analytics?.avg_time || 2.5,
                    fulfillment_rate: apiData.fulfillment_analytics?.rate || 95,
                },

                marketing_roi: {
                    total_spend: apiData.marketing_analytics?.spend || 2500,
                    total_revenue:
                        apiData.sales_analytics?.summary?.total_sales || 0,
                    roi: apiData.marketing_analytics?.roi || 320,
                    cost_per_acquisition:
                        apiData.marketing_analytics?.cpa || 45,
                    campaigns: apiData.marketing_analytics?.campaigns || [
                        {
                            name: 'Google Ads',
                            spend: 1200,
                            revenue: 4800,
                            roi: 300,
                        },
                        {
                            name: 'Facebook Ads',
                            spend: 800,
                            revenue: 2400,
                            roi: 200,
                        },
                        {
                            name: 'Email Campaign',
                            spend: 300,
                            revenue: 1200,
                            roi: 300,
                        },
                        {
                            name: 'Influencer',
                            spend: 200,
                            revenue: 800,
                            roi: 300,
                        },
                    ],
                },

                geographic_sales: {
                    countries: apiData.geographic_analytics?.countries || [],
                    top_regions: [
                        { name: 'United States', sales: 15420, percentage: 45 },
                        { name: 'Canada', sales: 6850, percentage: 20 },
                        { name: 'United Kingdom', sales: 4110, percentage: 12 },
                        { name: 'Australia', sales: 3425, percentage: 10 },
                        { name: 'Germany', sales: 2740, percentage: 8 },
                        { name: 'Others', sales: 1715, percentage: 5 },
                    ],
                    total_countries:
                        apiData.geographic_analytics?.total_countries || 25,
                },

                seasonal_trends: {
                    monthly_data: apiData.seasonal_analytics?.monthly || {
                        Jan: 8500,
                        Feb: 9200,
                        Mar: 10800,
                        Apr: 12200,
                        May: 11800,
                        Jun: 13500,
                        Jul: 15200,
                        Aug: 14800,
                        Sep: 13200,
                        Oct: 12800,
                        Nov: 16500,
                        Dec: 18200,
                    },
                    seasonal_products: apiData.seasonal_analytics?.products || [
                        {
                            name: 'Summer Collection',
                            peak_month: 'July',
                            sales: 15200,
                        },
                        {
                            name: 'Winter Collection',
                            peak_month: 'December',
                            sales: 18200,
                        },
                        {
                            name: 'Spring Collection',
                            peak_month: 'April',
                            sales: 12200,
                        },
                    ],
                    peak_seasons: ['November', 'December', 'July'],
                },
            };

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
        console.log('=== ADD WIDGET DEBUG ===');
        console.log('Widget Type:', widgetType);
        console.log('Position:', position);
        console.log('Dashboard ID:', props.dashboard.id);

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

    fetchData();

    // Set up auto-refresh every 5 minutes
    refreshInterval.value = setInterval(() => {
        if (!loading.value) {
            fetchData();
        }
    }, 5 * 60 * 1000);
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});
</script>
