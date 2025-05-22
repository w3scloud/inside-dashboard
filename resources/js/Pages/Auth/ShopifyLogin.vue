<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    shop: '',
});

const isLoading = ref(false);

const submit = () => {
    if (!form.shop) {
        form.setError('shop', 'Please enter your store domain');
        return;
    }

    isLoading.value = true;

    // Use regular form submission instead of AJAX to avoid CORS
    const formElement = document.createElement('form');
    formElement.method = 'POST';
    formElement.action = route('shopify.auth');

    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';
    formElement.appendChild(csrfInput);

    // Add shop domain
    const shopInput = document.createElement('input');
    shopInput.type = 'hidden';
    shopInput.name = 'shop';
    shopInput.value = form.shop;
    formElement.appendChild(shopInput);

    // Submit form
    document.body.appendChild(formElement);
    formElement.submit();
};

const normalizeShopDomain = (value) => {
    // Remove any protocol and trailing slashes
    let shop = value.replace(/^https?:\/\//, '').replace(/\/$/, '');

    // If it doesn't contain .myshopify.com, add it
    if (!shop.includes('.myshopify.com')) {
        shop = shop.replace('.myshopify.com', '') + '.myshopify.com';
    }

    return shop;
};

const handleShopInput = (event) => {
    const value = event.target.value;
    form.shop = normalizeShopDomain(value);
};
</script>

<template>
    <GuestLayout>
        <Head title="Connect to Shopify" />

        <div class="mb-8 text-center">
            <div
                class="mx-auto mb-4 h-16 w-16 rounded-full bg-green-100 flex items-center justify-center"
            >
                <svg
                    class="h-8 w-8 text-green-600"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M15.5 2.5c-1.4 0-2.7.6-3.5 1.6-.8-1-2.1-1.6-3.5-1.6C6.5 2.5 5 4 5 6v10.5c0 .8.7 1.5 1.5 1.5s1.5-.7 1.5-1.5V6c0-.6.4-1 1-1s1 .4 1 1v10.5c0 .8.7 1.5 1.5 1.5s1.5-.7 1.5-1.5V6c0-.6.4-1 1-1s1 .4 1 1v10.5c0 .8.7 1.5 1.5 1.5s1.5-.7 1.5-1.5V6c0-2-1.5-3.5-3.5-3.5z"
                    />
                </svg>
            </div>

            <h2 class="text-3xl font-bold text-gray-900">
                Welcome to Analytics Dashboard
            </h2>
            <p class="mt-3 text-lg text-gray-600">
                Connect your Shopify store to unlock powerful insights and
                visualizations
            </p>
        </div>

        <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div class="p-4">
                    <div class="mx-auto mb-2 h-8 w-8 text-blue-600">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"
                            />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900">Rich Dashboards</h3>
                    <p class="text-sm text-gray-600">
                        Interactive charts and KPIs
                    </p>
                </div>

                <div class="p-4">
                    <div class="mx-auto mb-2 h-8 w-8 text-green-600">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"
                            />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900">Sales Analytics</h3>
                    <p class="text-sm text-gray-600">
                        Track performance trends
                    </p>
                </div>

                <div class="p-4">
                    <div class="mx-auto mb-2 h-8 w-8 text-purple-600">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
                            />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900">Custom Reports</h3>
                    <p class="text-sm text-gray-600">
                        Export and schedule reports
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="shop" value="Your Shopify Store Domain" />

                <div class="mt-1 relative">
                    <TextInput
                        id="shop"
                        type="text"
                        class="block w-full pr-12"
                        v-model="form.shop"
                        @input="handleShopInput"
                        required
                        autofocus
                        placeholder="your-store-name"
                        :disabled="isLoading"
                    />
                    <div
                        class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
                    >
                        <span class="text-gray-500 text-sm"
                            >.myshopify.com</span
                        >
                    </div>
                </div>

                <InputError class="mt-2" :message="form.errors.shop" />

                <p class="mt-2 text-sm text-gray-600">
                    Enter your store's subdomain (e.g., "my-store" for
                    my-store.myshopify.com)
                </p>
            </div>

            <div class="flex items-center justify-center">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing || isLoading }"
                    :disabled="form.processing || isLoading"
                    class="w-full flex justify-center py-3 px-4 text-lg"
                >
                    <svg
                        v-if="isLoading"
                        class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span v-if="isLoading">Connecting to Shopify...</span>
                    <span v-else>Connect with Shopify</span>
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-8 border-t border-gray-200 pt-6">
            <div class="text-center text-sm text-gray-600">
                <h4 class="font-semibold mb-2">What happens next?</h4>
                <div class="space-y-1">
                    <p>
                        1. You'll be redirected to Shopify to authorize the app
                    </p>
                    <p>2. Grant necessary permissions for analytics data</p>
                    <p>3. Return to your personalized dashboard</p>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500">
                🔒 Secure connection. We never store your Shopify login
                credentials.
                <br />
                Your data is protected and used only for analytics purposes.
            </p>
        </div>

        <!-- Trust indicators -->
        <div class="mt-8 grid grid-cols-2 gap-4 text-center">
            <div
                class="flex items-center justify-center space-x-2 text-sm text-gray-600"
            >
                <svg
                    class="h-4 w-4 text-green-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span>SSL Encrypted</span>
            </div>
            <div
                class="flex items-center justify-center space-x-2 text-sm text-gray-600"
            >
                <svg
                    class="h-4 w-4 text-blue-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Shopify Partner</span>
            </div>
        </div>
    </GuestLayout>
</template>
