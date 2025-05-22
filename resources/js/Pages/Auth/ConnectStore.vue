<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    previouslyConnected: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    shop: '',
});

const submit = () => {
    form.post(route('shopify.auth'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Connect Shopify Store" />

        <div class="mb-4 text-center">
            <h2 class="text-2xl font-bold text-gray-900">
                Connect Your Shopify Store
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                <span v-if="previouslyConnected">
                    Your store connection has expired. Please reconnect to
                    continue.
                </span>
                <span v-else>
                    Connect your Shopify store to access powerful analytics and
                    insights.
                </span>
            </p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="shop" value="Store Domain" />

                <TextInput
                    id="shop"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.shop"
                    required
                    autofocus
                    placeholder="your-store.myshopify.com"
                />

                <InputError class="mt-2" :message="form.errors.shop" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Connect Store
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500">
                By connecting your store, you agree to our terms of service and
                privacy policy. Your store data will be used to generate
                analytics and insights.
            </p>
        </div>
    </GuestLayout>
</template>
