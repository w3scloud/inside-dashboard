<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    store: Object,
});

const form = useForm({
    name: '',
    description: '',
    is_default: false,
});

const submit = () => {
    form.post(route('dashboard.store'));
};
</script>

<template>
    <Head title="Create Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Create Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Dashboard Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    placeholder="e.g., Sales Overview, Product Analytics"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.name"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="description"
                                    value="Description (Optional)"
                                />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Describe what this dashboard will track..."
                                ></textarea>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.description"
                                />
                            </div>

                            <div class="flex items-center">
                                <Checkbox
                                    id="is_default"
                                    v-model:checked="form.is_default"
                                />
                                <label
                                    for="is_default"
                                    class="ml-2 text-sm text-gray-600"
                                >
                                    Set as default dashboard
                                </label>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <div class="flex justify-end space-x-3">
                                    <Link :href="route('dashboard')">
                                        <SecondaryButton type="button">
                                            Cancel
                                        </SecondaryButton>
                                    </Link>
                                    <PrimaryButton
                                        :class="{
                                            'opacity-25': form.processing,
                                        }"
                                        :disabled="form.processing"
                                    >
                                        Create Dashboard
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Section -->
                <div
                    class="mt-8 overflow-hidden bg-blue-50 shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-blue-900">
                            Getting Started
                        </h3>
                        <div class="mt-4 text-sm text-blue-700">
                            <p class="mb-2">
                                After creating your dashboard, you can:
                            </p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li>
                                    Add various widgets to track KPIs, sales,
                                    inventory, and customers
                                </li>
                                <li>
                                    Customize the layout by dragging and
                                    resizing widgets
                                </li>
                                <li>
                                    Set date ranges and filters for your data
                                </li>
                                <li>
                                    Export reports and schedule automated
                                    delivery
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
