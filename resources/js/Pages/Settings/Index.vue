<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    settings: Object,
    store: Object,
});

const form = useForm({
    theme: props.settings.theme,
    dashboard_refresh_interval: props.settings.dashboard_refresh_interval,
    default_date_range: props.settings.default_date_range,
    email_notifications: props.settings.email_notifications,
});

const submit = () => {
    form.put(route('settings.update'));
};
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Store Information -->
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3
                            class="text-lg font-medium leading-6 text-gray-900 mb-4"
                        >
                            Store Information
                        </h3>
                        <dl
                            class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2"
                        >
                            <div>
                                <dt class="text-sm font-medium text-gray-500">
                                    Store Name
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ store.name }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">
                                    Domain
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ store.domain }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">
                                    Plan
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ store.plan || 'N/A' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">
                                    Owner
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ store.owner || 'N/A' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- App Settings -->
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3
                            class="text-lg font-medium leading-6 text-gray-900 mb-4"
                        >
                            App Settings
                        </h3>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="theme" value="Theme" />
                                <select
                                    id="theme"
                                    v-model="form.theme"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="auto">Auto</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.theme"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="dashboard_refresh_interval"
                                    value="Dashboard Refresh Interval (seconds)"
                                />
                                <select
                                    id="dashboard_refresh_interval"
                                    v-model="form.dashboard_refresh_interval"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="0">Disabled</option>
                                    <option value="30">30 seconds</option>
                                    <option value="60">1 minute</option>
                                    <option value="300">5 minutes</option>
                                    <option value="600">10 minutes</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="
                                        form.errors.dashboard_refresh_interval
                                    "
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Automatically refresh dashboard data at
                                    specified intervals
                                </p>
                            </div>

                            <div>
                                <InputLabel
                                    for="default_date_range"
                                    value="Default Date Range (days)"
                                />
                                <select
                                    id="default_date_range"
                                    v-model="form.default_date_range"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="7">Last 7 days</option>
                                    <option value="30">Last 30 days</option>
                                    <option value="90">Last 90 days</option>
                                    <option value="365">Last year</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.default_date_range"
                                />
                            </div>

                            <div class="flex items-center">
                                <Checkbox
                                    id="email_notifications"
                                    v-model:checked="form.email_notifications"
                                />
                                <label
                                    for="email_notifications"
                                    class="ml-2 text-sm text-gray-600"
                                >
                                    Enable email notifications for reports and
                                    alerts
                                </label>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <div class="flex justify-end">
                                    <PrimaryButton
                                        :class="{
                                            'opacity-25': form.processing,
                                        }"
                                        :disabled="form.processing"
                                    >
                                        Save Settings
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
