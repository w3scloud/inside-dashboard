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

        // USE YOUR EXISTING WORKING API ENDPOINT
        const response = await axios.get('/api/analytics/dashboard', {
            params: {
                store_id: props.store.id,
                start_date: dateRange.value.start,
                end_date: dateRange.value.end,
            },
        });

        console.log('API Response:', response.data);

        if (response.data.success) {
            // Transform the API response to match widget expectations
            const apiData = response.data.data;

            // Map API data to widget data structure
            widgetData.value = {
                // Sales Overview Widget
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

                // Product Performance Widget
                product_performance: {
                    top_products: apiData.product_analytics?.top_products || [],
                    total_products:
                        apiData.product_analytics?.summary?.total_products || 0,
                    performance_metrics:
                        apiData.product_analytics?.performance_metrics || {},
                },

                // Customer Analytics Widget
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

                // Inventory Widget
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

                // Performance Metrics Widget
                performance_metrics: apiData.performance_metrics || {},

                // Data Sources Status Widget
                data_sources: apiData.data_sources || {},
            };

            console.log('Transformed widget data:', widgetData.value);
        } else {
            console.error('API returned error:', response.data);
        }
    } catch (error) {
        console.error('Error fetching data:', error);

        // Show user-friendly error message
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
            .filter((item) => item && typeof item === 'object') // Remove null/undefined items
            .map((item) => ({
                i: item.i || item.id || `widget_${Date.now()}_${Math.random()}`, // Ensure 'i' exists
                x: parseInt(item.x) || 0,
                y: parseInt(item.y) || 0,
                w: parseInt(item.w) || 4,
                h: parseInt(item.h) || 4,
                // Keep any additional properties
                ...(item.widget_id && { widget_id: item.widget_id }),
            }))
            .filter((item) => item.i); // Remove items without valid 'i'

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
            // Log the actual data that failed validation
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
const validateLayoutItem = (item) => {
    return (
        item &&
        typeof item === 'object' &&
        typeof item.i === 'string' &&
        typeof item.x === 'number' &&
        typeof item.y === 'number' &&
        typeof item.w === 'number' &&
        typeof item.h === 'number'
    );
};

// Also fix the removeWidget method if it's not implemented
const removeWidget = async (widgetId) => {
    try {
        console.log('Removing widget:', widgetId);

        // Make API call to remove widget
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

// Computed properties for easy access to specific data
const salesMetrics = computed(() => {
    const sales = widgetData.value.sales_overview || {};
    return {
        totalRevenue: sales.total_revenue || 0,
        totalOrders: sales.total_orders || 0,
        averageOrderValue: sales.average_order_value || 0,
        growthRate: sales.growth_rate || 0,
    };
});

const productMetrics = computed(() => {
    const products = widgetData.value.product_performance || {};
    return {
        totalProducts: products.total_products || 0,
        topProducts: products.top_products || [],
    };
});

const customerMetrics = computed(() => {
    const customers = widgetData.value.customer_analytics || {};
    return {
        totalCustomers: customers.total_customers || 0,
        newCustomers: customers.new_customers || 0,
        returningCustomers: customers.returning_customers || 0,
    };
});

const inventoryMetrics = computed(() => {
    const inventory = widgetData.value.inventory_status || {};
    return {
        totalProducts: inventory.total_products || 0,
        inStock: inventory.in_stock || 0,
        lowStock: inventory.low_stock || 0,
        outOfStock: inventory.out_of_stock || 0,
    };
});

// Lifecycle hooks
onMounted(() => {
    console.log('Dashboard mounted:', {
        dashboard: props.dashboard,
        store: props.store,
        availableWidgets: props.availableWidgets,
    });

    // Initial data fetch
    fetchData();

    // Set up auto-refresh every 5 minutes (optional)
    refreshInterval.value = setInterval(() => {
        fetchData();
    }, 5 * 60 * 1000);
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});

// Watch for date range changes
watch(
    dateRange,
    (newDateRange, oldDateRange) => {
        console.log('Date range watcher triggered:', {
            newDateRange,
            oldDateRange,
        });
        if (
            newDateRange.start !== oldDateRange.start ||
            newDateRange.end !== oldDateRange.end
        ) {
            fetchData();
        }
    },
    { deep: true }
);

// Watch for store changes
watch(
    () => props.store,
    (newStore, oldStore) => {
        console.log('Store changed:', { newStore, oldStore });
        if (newStore?.id !== oldStore?.id) {
            fetchData();
        }
    },
    { deep: true }
);
</script>

<template>
    <Head :title="dashboard?.name || 'Dashboard'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-800"
                    >
                        {{ dashboard?.name || 'Dashboard' }}
                    </h2>
                    <p
                        v-if="dashboard?.description"
                        class="text-sm text-gray-600 mt-1"
                    >
                        {{ dashboard.description }}
                    </p>
                </div>

                <div class="flex items-center space-x-4">
                    <DateRangePicker
                        :initial-start="dateRange.start"
                        :initial-end="dateRange.end"
                        @change="handleDateRangeChange"
                    />

                    <button
                        @click="refreshData"
                        :disabled="loading"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        <svg
                            :class="{ 'animate-spin': loading }"
                            class="w-4 h-4 mr-2"
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
                    <!-- Key Metrics Summary Cards -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
                    >
                        <!-- Total Revenue -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-6 w-6 text-gray-400"
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

                        <!-- Total Orders -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-6 w-6 text-gray-400"
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

                        <!-- Average Order Value -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-6 w-6 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="text-sm font-medium text-gray-500 truncate"
                                            >
                                                Avg Order Value
                                            </dt>
                                            <dd
                                                class="text-lg font-medium text-gray-900"
                                            >
                                                ${{
                                                    salesMetrics.averageOrderValue.toFixed(
                                                        2
                                                    )
                                                }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Customers -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="h-6 w-6 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt
                                                class="text-sm font-medium text-gray-500 truncate"
                                            >
                                                Total Customers
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
