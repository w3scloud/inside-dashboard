<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const menuItems = [
    {
        label: 'Dashboard',
        routeName: 'dashboard',
        iconPath:
            'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6',
    },
    {
        label: 'Orders',
        routeName: 'dashboard',
        params: { section: 'orders' },
        iconPath:
            'M3 7h18M5 11h14M7 15h10M9 19h6', // simple list / orders style icon
    },
    {
        label: 'Customers',
        routeName: 'dashboard',
        params: { section: 'customers' },
        iconPath:
            'M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 12a4 4 0 100-8 4 4 0 000 8z',
    },
    {
        label: 'Reports',
        routeName: 'reports',
        iconPath:
            'M9 17v-6a1 1 0 011-1h4m4 10H6a2 2 0 01-2-2V7a2 2 0 012-2h6l6 6v6a2 2 0 01-2 2z',
    },
    {
        label: 'Data',
        routeName: 'data',
        iconPath:
            'M4 6v12a2 2 0 002 2h12M4 6a2 2 0 012-2h12m-14 2h14M9 10h6M9 14h3',
    },
];

const dataChildren = [
    { id: 'abandoned_checkouts', label: 'Abandoned Checkouts' },
    {
        id: 'abandoned_checkouts_discount_codes',
        label: 'Abandoned Checkouts Discount Codes',
    },
    {
        id: 'abandoned_checkouts_line_item',
        label: 'Abandoned Checkouts Line Item',
    },
    {
        id: 'abandoned_checkouts_shipping_line',
        label: 'Abandoned Checkouts Shipping Line',
    },
    { id: 'collections', label: 'Collections' },
    { id: 'collections_products', label: 'Collections Products' },
    { id: 'countries', label: 'Countries' },
    { id: 'customer_address', label: 'Customer Address' },
    {
        id: 'customer_saved_searches',
        label: 'Customer Saved Searches',
    },
    { id: 'customers', label: 'Customers' },
    {
        id: 'discount_entitled_collections',
        label: 'Discount Entitled Collections',
    },
    { id: 'discount_entitled_country', label: 'Discount Entitled Country' },
    { id: 'discount_entitled_products', label: 'Discount Entitled Products' },
    { id: 'discount_entitled_variants', label: 'Discount Entitled Variants' },
    {
        id: 'discount_prerequisite_collection',
        label: 'Discount Prerequisite Collection ID',
    },
    {
        id: 'discount_prerequisite_customers',
        label: 'Discount Prerequisite Customers',
    },
    {
        id: 'discount_prerequisite_product',
        label: 'Discount Prerequisite Product',
    },
];

const isActive = (item) =>
    route().current(item.routeName) || route().current(`${item.routeName}.*`);

const sidebarRef = ref(null);
const dataPanelRef = ref(null);
const dataMenuOpen = ref(false);
const selectedChildId = ref(null);

const toggleDataMenu = () => {
    dataMenuOpen.value = !dataMenuOpen.value;
};

const openDataChild = (child) => {
    selectedChildId.value = child.id;
    dataMenuOpen.value = false;

    // Navigate to internal data report page with dedicated route per table
    const routeName = `data.${child.id}`;
    window.location.href = route(routeName);
};

const handleDocumentClick = (event) => {
    const sidebar = sidebarRef.value;
    const panel = dataPanelRef.value;

    const clickInsideSidebar = sidebar && sidebar.contains(event.target);
    const clickInsidePanel = panel && panel.contains(event.target);

    if (!clickInsideSidebar && !clickInsidePanel) {
        dataMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
    <div class="relative w-20 bg-gray-900 text-gray-100 flex flex-col">
        <aside ref="sidebarRef" class="flex-1 flex flex-col items-center">
            <!-- Top logo icon -->
            <div
                class="h-16 w-full flex items-center justify-center border-b border-gray-800"
            >
                <svg
                    class="h-7 w-7 text-white"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 17l6-6 4 4 7-7M21 21H3V5"
                    />
                </svg>
            </div>

            <nav class="flex-1 py-4 space-y-3 w-full flex flex-col items-center">
                <div
                    v-for="item in menuItems"
                    :key="item.routeName"
                    class="w-full flex justify-center"
                >
                    <!-- Dashboard / Orders / Customers / Reports (normal links) -->
                    <Link
                        v-if="item.routeName !== 'data'"
                        :href="item.params ? route(item.routeName, item.params) : route(item.routeName)"
                        class="flex flex-col items-center justify-center h-14 w-14 rounded-lg text-[11px] font-medium transition-colors"
                        :class="[
                            isActive(item)
                                ? 'bg-gray-800 text-white'
                                : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                        ]"
                    >
                        <svg
                            class="h-5 w-5 mb-1 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :d="item.iconPath"
                            />
                        </svg>
                        <span class="leading-none text-center">
                            {{ item.label }}
                        </span>
                    </Link>

                    <!-- Data parent: toggle-only, no navigation -->
                    <button
                        v-else
                        type="button"
                        class="flex flex-col items-center justify-center h-14 w-14 rounded-lg text-[11px] font-medium transition-colors focus:outline-none"
                        :class="[
                            dataMenuOpen ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                        ]"
                        @click.stop="toggleDataMenu"
                    >
                        <svg
                            class="h-5 w-5 mb-1 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :d="item.iconPath"
                            />
                        </svg>
                        <span class="leading-none text-center">
                            {{ item.label }}
                        </span>
                    </button>
                </div>
            </nav>
        </aside>

        <!-- Data dropdown panel shown outside sidebar -->
        <div
            v-if="dataMenuOpen"
            ref="dataPanelRef"
            class="fixed z-40 top-24 left-20"
        >
            <div
                class="bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden w-80"
            >
                <div
                    class="flex items-center px-3 py-2 border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-600"
                >
                    <svg
                        class="h-4 w-4 text-gray-500 mr-1.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"
                        />
                    </svg>
                    <span>Tables &amp; Reports</span>
                </div>

                <div class="max-h-[26rem] overflow-y-auto py-1 text-xs">
                    <button
                        v-for="child in dataChildren"
                        :key="child.id"
                        type="button"
                        class="flex w-full items-center px-3 py-1.5 text-left transition-colors focus:outline-none"
                        :class="[
                            selectedChildId === child.id
                                ? 'bg-gray-100 text-gray-900'
                                : 'text-gray-800 hover:bg-gray-50',
                        ]"
                        @click.stop="openDataChild(child)"
                    >
                        <span
                            class="mr-2 inline-flex h-4 w-4 items-center justify-center rounded-[2px] border border-yellow-300 bg-yellow-50 text-[9px] leading-none text-yellow-700"
                        >
                            ▤
                        </span>
                        <span class="truncate">
                            {{ child.label }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

