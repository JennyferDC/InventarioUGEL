<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import { reactive, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    persona: {
        type: Object,
        default: null,
    },
    areas: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "save"]);

const form = reactive({
    id: null,
    nombre_completo: "",
    id_area: "",
});

watch(
    () => props.persona,
    (value) => {
        form.id = value?.id ?? null;
        form.nombre_completo = value?.nombre_completo ?? "";
        form.id_area = value?.id_area ?? "";
    },
    { immediate: true }
);

const handleSubmit = () => {
    if (!form.id) return;

    emit("save", {
        id: form.id,
        nombre_completo: form.nombre_completo,
        id_area: form.id_area,
    });
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="lg">
        <template #title>
            <span class="text-ugel-guinda font-semibold">Editar persona</span>
        </template>

        <template #content>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label
                        for="nombre_completo"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Nombre completo
                    </label>
                    <input
                        id="nombre_completo"
                        v-model="form.nombre_completo"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Nombre y apellidos"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="id_area"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Área
                    </label>
                    <select
                        id="id_area"
                        v-model="form.id_area"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                        :disabled="loading"
                    >
                        <option value="" disabled>Selecciona un área</option>
                        <option
                            v-for="area in areas"
                            :key="area.id"
                            :value="area.id"
                        >
                            {{ area.nombre }}
                        </option>
                    </select>
                </div>
            </form>

                <div v-if="persona?.equipos?.length" class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center justify-between">
                        <span>Equipos Asignados ({{ persona.equipos.length }})</span>
                    </h3>
                    <ul class="space-y-3">
                        <li v-for="equipo in persona.equipos" :key="equipo.id" class="flex items-center justify-between bg-gray-50 px-4 py-3 rounded-xl border border-gray-100">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-ugel-azul/10 text-ugel-azul">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ equipo.cod_informatica }}</p>
                                    <p class="text-xs text-gray-500">{{ equipo.tipo }}</p>
                                </div>
                            </div>
                            <span :class="[
                                'px-2 py-0.5 rounded-full text-xs font-semibold',
                                equipo.estado === 'LIBRE' ? 'bg-green-100 text-green-800' :
                                equipo.estado === 'EN USO' ? 'bg-blue-100 text-blue-800' :
                                'bg-red-100 text-red-800'
                            ]">
                                {{ equipo.estado }}
                            </span>
                        </li>
                    </ul>
                </div>
                <div v-else-if="persona && (!persona.equipos || persona.equipos.length === 0)" class="mt-6 pt-6 border-t border-gray-200">
                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 bg-gray-50">
                        Esta persona no tiene equipos asignados actualmente.
                    </div>
                </div>
        </template>

        <template #footer>
            <button
                type="button"
                class="me-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-70"
                @click="emit('close')"
                :disabled="loading"
            >
                Cancelar
            </button>

            <button
                type="button"
                class="inline-flex items-center rounded-lg bg-ugel-azul px-4 py-2 text-sm font-semibold text-white shadow hover:bg-ugel-guinda disabled:opacity-50 disabled:cursor-not-allowed"
                @click="handleSubmit"
                :disabled="
                    loading || !form.nombre_completo.trim() || !form.id_area
                "
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
                Guardar cambios
            </button>
        </template>
    </DialogModal>
</template>
