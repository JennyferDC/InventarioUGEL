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
        <template #title>
            <span v-if="persona?.estado === 'ACTIVO'">Dar de baja persona</span>
            <span v-else>Activar cuenta de persona</span>
        </template>

        <template #content>
            <p class="text-gray-700">
                <span v-if="persona?.estado === 'ACTIVO'">
                    ¿Estás seguro de que deseas desactivar la cuenta de
                </span>
                <span v-else>
                    ¿Estás seguro de que deseas reactivar la cuenta de
                </span>
                <span class="font-semibold text-ugel-guinda">
                    {{ persona?.nombre_completo || "esta persona" }}
                </span>?
            </p>

            <div v-if="persona?.estado === 'ACTIVO'" class="mt-4">
                <div v-if="persona?.equipos?.length > 0">
                    <p class="text-sm font-semibold text-red-600 mb-2">
                        Al dar de baja a esta persona, los siguientes equipos quedarán libres:
                    </p>
                    <ul class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                        <li v-for="equipo in persona.equipos" :key="equipo.id" class="flex items-center justify-between bg-red-50/50 px-3 py-2 rounded-lg border border-red-100">
                            <div class="flex items-center gap-2">
                                <span class="flex size-6 items-center justify-center rounded-md bg-red-100 text-red-600">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <div>
                                    <a :href="route('equipos.show', equipo.id)" target="_blank" class="text-xs font-bold text-gray-800 hover:text-ugel-azul hover:underline">
                                        {{ equipo.cod_informatica }}
                                    </a>
                                    <p class="text-[10px] text-gray-500">{{ equipo.tipo }}</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div v-else>
                    <p class="text-sm text-gray-500">
                        Esta persona no tiene equipos asignados actualmente.
                    </p>
                </div>
            </div>
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
                class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow disabled:opacity-50 disabled:cursor-not-allowed"
                :class="persona?.estado === 'ACTIVO' ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700'"
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
                <span v-if="persona?.estado === 'ACTIVO'">Confirmar Baja</span>
                <span v-else>Confirmar Activación</span>
            </button>
        </template>
    </ConfirmationModal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
}
</style>
