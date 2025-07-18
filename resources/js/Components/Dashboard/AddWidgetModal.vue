<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="close">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                />
            </TransitionChild>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div
                    class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
                >
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                        >
                            <div>
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100"
                                >
                                    <PlusIcon
                                        class="h-6 w-6 text-indigo-600"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div class="mt-3 text-center sm:mt-5">
                                    <DialogTitle
                                        as="h3"
                                        class="text-base font-semibold leading-6 text-gray-900"
                                    >
                                        Add Widget to Dashboard
                                    </DialogTitle>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Choose a widget to add to your
                                            dashboard. You can configure it
                                            after adding.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Widget Selection -->
                            <div class="mt-6">
                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <div
                                        v-for="widget in availableWidgets"
                                        :key="widget.type"
                                        @click="selectWidget(widget.type)"
                                        :class="[
                                            'relative rounded-lg border p-4 cursor-pointer hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                                            selectedWidget === widget.type
                                                ? 'border-indigo-500 bg-indigo-50'
                                                : 'border-gray-300',
                                        ]"
                                    >
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <component
                                                    :is="
                                                        getWidgetIcon(
                                                            widget.icon
                                                        )
                                                    "
                                                    class="h-6 w-6 text-gray-600"
                                                />
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h4
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{ widget.name }}
                                                </h4>
                                                <p
                                                    class="text-xs text-gray-500 mt-1"
                                                >
                                                    {{ widget.description }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Selection indicator -->
                                        <div
                                            v-if="
                                                selectedWidget === widget.type
                                            "
                                            class="absolute top-2 right-2"
                                        >
                                            <CheckCircleIcon
                                                class="h-5 w-5 text-indigo-600"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action buttons -->
                            <div
                                class="mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3"
                            >
                                <button
                                    type="button"
                                    :disabled="!selectedWidget"
                                    @click="addWidget"
                                    :class="[
                                        'inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold shadow-sm sm:col-start-2',
                                        selectedWidget
                                            ? 'bg-indigo-600 text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                            : 'bg-gray-300 text-gray-500 cursor-not-allowed',
                                    ]"
                                >
                                    Add Widget
                                </button>
                                <button
                                    type="button"
                                    @click="close"
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0"
                                >
                                    Cancel
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import {
    PlusIcon,
    CheckCircleIcon,
    ChartBarIcon,
    CubeIcon,
    UsersIcon,
    ArchiveBoxIcon,
    ArrowTrendingUpIcon,
    GlobeAltIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    availableWidgets: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:show', 'add-widget']);

const selectedWidget = ref(null);

// Icon mapping
const iconComponents = {
    'chart-bar': ChartBarIcon,
    cube: CubeIcon,
    users: UsersIcon,
    archive: ArchiveBoxIcon,
    'trending-up': ArrowTrendingUpIcon,
    globe: GlobeAltIcon,
};

const getWidgetIcon = (iconName) => {
    return iconComponents[iconName] || ChartBarIcon;
};

const selectWidget = (widgetType) => {
    selectedWidget.value = widgetType;
};

const addWidget = () => {
    if (!selectedWidget.value) return;

    const widget = props.availableWidgets.find(
        (w) => w.type === selectedWidget.value
    );

    // Find a good position for the new widget
    const position = {
        x: 0,
        y: 0,
        w: widget?.default_size?.w || 4,
        h: widget?.default_size?.h || 4,
    };

    emit('add-widget', selectedWidget.value, position);

    // Reset and close
    selectedWidget.value = null;
    close();
};

const close = () => {
    selectedWidget.value = null;
    emit('update:show', false);
};
</script>

<style scoped>
/* Additional styles if needed */
</style>
