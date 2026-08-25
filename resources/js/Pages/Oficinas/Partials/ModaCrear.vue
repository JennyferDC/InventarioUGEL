<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import { reactive, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    areas: {
        type: Array,
        default: () => [],
    },
    defaultAreaId: {
        type: [Number, String],
        default: "",
    },
});

const emit = defineEmits(["close", "save"]);

const form = reactive({
    nombre: "",
    descripcion: "",
    area_id: "",
});

watch(
    () => props.show,
    (val) => {
        if (val) {
            form.area_id = props.defaultAreaId && props.defaultAreaId !== "todos" ? props.defaultAreaId : "";
        }
    },
    { immediate: true }
);

const resetForm = () => {
    form.nombre = "";
    form.descripcion = "";
    form.area_id = props.defaultAreaId && props.defaultAreaId !== "todos" ? props.defaultAreaId : "";
};

const handleClose = () => {
    resetForm();
    emit("close");
};

const handleSubmit = () => {
    emit("save", {
        nombre: form.nombre,
        descripcion: form.descripcion,
        area_id: form.area_id,
    });
};

defineExpose({ resetForm });
</script>

<template>
    <DialogModal :show="show" @close="handleClose" max-width="lg">
        <template #title>
            <span class="text-ugel-guinda font-semibold">Nueva oficina</span>
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
                        placeholder="Funciones principales de la oficina"
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
                class="me-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-70"
                @click="handleClose"
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
                Crear oficina
            </button>
        </template>
    </DialogModal>
</template>
