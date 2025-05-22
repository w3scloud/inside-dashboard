<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';

const props = defineProps({
    dashboard: Object,
    store: Object,
});

const form = useForm({
    name: props.dashboard.name,
    description: props.dashboard.description || '',
    is_default: props.dashboard.is_default,
});

const showDeleteModal = ref(false);

const submit = () => {
    form.put(route('dashboard.update', props.dashboard.id));
};

const deleteDashboard = () => {
    form.delete(route('dashboard.destroy', props.dashboard.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Edit Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Dashboard
                </h2>
                <DangerButton @click="showDeleteModal = true">
                    Delete Dashboard
                </DangerButton>
            </div>
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
                                <div class="flex justify-between">
                                    <DangerButton
                                        type="button"
                                        @click="showDeleteModal = true"
                                    >
                                        Delete Dashboard
                                    </DangerButton>

                                    <div class="flex space-x-3">
                                        <Link
                                            :href="
                                                route(
                                                    'dashboard.show',
                                                    dashboard.id
                                                )
                                            "
                                        >
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
                                            Update Dashboard
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete Dashboard
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to delete "{{ dashboard.name }}"? This
                    action cannot be undone and all widgets will be permanently
                    removed.
                </p>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteModal = false">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        @click="deleteDashboard"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Delete Dashboard
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
