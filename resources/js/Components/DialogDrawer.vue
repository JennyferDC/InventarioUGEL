<script setup>
import Drawer from './Drawer.vue';

const emit = defineEmits(['close', 'scroll']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Drawer
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white shadow-sm z-10 shrink-0">
            <div class="text-lg font-medium text-gray-900">
                <slot name="title" />
            </div>
            <button @click="close" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 transition-colors focus:outline-none focus:ring-2 focus:ring-ugel-azul focus:ring-offset-2 rounded-full">
                <span class="sr-only">Cerrar</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-4 relative scroll-light" @scroll="emit('scroll', $event)">
            <slot name="content" />
        </div>

        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end shrink-0">
            <slot name="footer" />
        </div>
    </Drawer>
</template>
