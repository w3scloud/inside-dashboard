<script setup>
import MainSidebar from '@/Components/Sidebar/MainSidebar.vue';
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex">
        <MainSidebar />

        <div class="flex-1 flex flex-col">
            <!-- Page Heading -->
            <header
                v-if="$slots.header"
                class="bg-white shadow-sm border-b border-gray-200"
            >
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
