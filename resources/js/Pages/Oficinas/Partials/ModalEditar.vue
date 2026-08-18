<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import { reactive, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    oficina: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    areas: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "save"]);

const form = reactive({
    id: null,
    nombre: "",
    descripcion: "",
    area_id: "",
});

watch(
    () => props.oficina,
    (value) => {
        form.id = value?.id ?? null;
        form.nombre = value?.nombre ?? "";
        form.descripcion = value?.descripcion ?? "";
        form.area_id = value?.area_id ?? "";
    },
    { immediate: true }
);

const handleSubmit = () => {
    if (!form.id) return;
    emit("save", { ...form });
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="lg">
        <template #title>
            <span class="text-ugel-guinda">Editar oficina</span>
        </template>

        <template #content>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label
                        for="nombre"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Nombre
                    </label>
                    <input
                        id="nombre"
                        v-model="form.nombre"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Ej. Recursos Humanos"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="descripcion"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Descripción
                    </label>
                    <textarea
                        id="descripcion"
                        v-model="form.descripcion"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Describe las funciones principales"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="area_id"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Unidad a la que pertenece
                    </label>
                    <select
                        id="area_id"
                        v-model="form.area_id"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        :disabled="loading"
                    >
                        <option value="" disabled>Seleccione una unidad</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">
                            {{ area.nombre }}
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
                :disabled="loading || !form.nombre.trim() || !form.area_id"
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
