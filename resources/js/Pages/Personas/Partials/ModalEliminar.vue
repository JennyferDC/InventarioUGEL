<script setup>
import ConfirmationModal from "@/Components/ConfirmationModal.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    persona: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "confirm"]);
</script>

<template>
    <ConfirmationModal :show="show" @close="emit('close')">
        <template #title>Eliminar persona</template>

        <template #content>
            <p class="text-gray-700">
                ¿Estás seguro de que deseas eliminar a
                <span class="font-semibold text-ugel-guinda">
                    {{ persona?.nombre_completo || "esta persona" }}
                </span>
                ?
            </p>
            <p class="text-gray-500 mt-1 text-sm">
                Esta acción no se puede deshacer.
            </p>
        </template>

        <template #footer>
            <button
                type="button"
                class="me-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                @click="emit('close')"
                :disabled="loading"
            >
                Cancelar
            </button>

            <button
                type="button"
                class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                @click="emit('confirm')"
                :disabled="loading"
            >
                <svg
                    v-if="loading"
                    class="-ms-1 me-2 size-4 animate-spin text-white"
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
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
                Confirmar
            </button>
        </template>
    </ConfirmationModal>
</template>
