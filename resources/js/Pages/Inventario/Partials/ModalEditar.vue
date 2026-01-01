<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import { reactive, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    equipo: {
        type: Object,
        default: null,
    },
    personas: {
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
    cod_informatica: "",
    tipo: "",
    estado: "",
    fecha_disponible_uso: "",
    vida_util_anios: "",
    id_persona: "",
});

watch(
    () => props.equipo,
    (value) => {
        form.id = value?.id ?? null;
        form.cod_informatica = value?.cod_informatica ?? "";
        form.tipo = value?.tipo ?? "";
        form.estado = value?.estado ?? "";
        form.fecha_disponible_uso = value?.fecha_disponible_uso ?? "";
        form.vida_util_anios = value?.vida_util_anios ?? "";
        form.id_persona = value?.id_persona ?? "";
    },
    { immediate: true }
);

const handleSubmit = () => {
    if (!form.id) return;

    emit("save", {
        id: form.id,
        cod_informatica: form.cod_informatica,
        tipo: form.tipo,
        estado: form.estado,
        fecha_disponible_uso: form.fecha_disponible_uso,
        vida_util_anios: form.vida_util_anios,
        id_persona: form.id_persona,
    });
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="4xl">
        <template #title>
            <span class="text-ugel-guinda font-semibold">Editar equipo</span>
        </template>

        <template #content>
            <form
                class="grid grid-cols-1 gap-4 md:grid-cols-2"
                @submit.prevent="handleSubmit"
            >
                <div>
                    <label
                        for="equipo_cod"
                        class="block text-sm font-medium text-gray-700"
                        >Código informática</label
                    >
                    <input
                        id="equipo_cod"
                        v-model="form.cod_informatica"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="EQ-001"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="equipo_tipo"
                        class="block text-sm font-medium text-gray-700"
                        >Tipo</label
                    >
                    <input
                        id="equipo_tipo"
                        v-model="form.tipo"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Laptop, CPU, Impresora..."
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="equipo_estado"
                        class="block text-sm font-medium text-gray-700"
                        >Estado</label
                    >
                    <input
                        id="equipo_estado"
                        v-model="form.estado"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Operativo"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="equipo_fecha"
                        class="block text-sm font-medium text-gray-700"
                        >Fecha disponible</label
                    >
                    <input
                        id="equipo_fecha"
                        v-model="form.fecha_disponible_uso"
                        type="date"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="equipo_vida"
                        class="block text-sm font-medium text-gray-700"
                        >Vida útil (años)</label
                    >
                    <input
                        id="equipo_vida"
                        v-model="form.vida_util_anios"
                        type="number"
                        min="0"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="4"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="equipo_persona"
                        class="block text-sm font-medium text-gray-700"
                        >Responsable</label
                    >
                    <select
                        id="equipo_persona"
                        v-model="form.id_persona"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                        :disabled="loading"
                    >
                        <option value="">Sin asignar</option>
                        <option
                            v-for="persona in personas"
                            :key="persona.id"
                            :value="persona.id"
                        >
                            {{ persona.nombre_completo }}
                        </option>
                    </select>
                </div>
            </form>
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
                class="inline-flex items-center rounded-lg bg-ugel-azul px-4 py-2 text-sm font-semibold text-white shadow hover:bg-ugel-guinda disabled:opacity-50 disabled:cursor-not-allowed"
                @click="handleSubmit"
                :disabled="
                    loading ||
                    !form.cod_informatica.trim() ||
                    !form.tipo.trim() ||
                    !form.estado.trim()
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
