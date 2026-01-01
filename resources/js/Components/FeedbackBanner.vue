<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: "",
    },
    type: {
        type: String,
        default: "info", // success, error, info
    },
});

const backgroundClasses = {
    success: "bg-green-50 text-green-700 border-green-200",
    error: "bg-red-50 text-red-700 border-red-200",
    info: "bg-blue-50 text-blue-700 border-blue-200",
};
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="show"
            class="border rounded-xl px-4 py-3 text-sm font-medium shadow-sm"
            :class="backgroundClasses[props.type] || backgroundClasses.info"
        >
            <slot>{{ message }}</slot>
        </div>
    </transition>
</template>
