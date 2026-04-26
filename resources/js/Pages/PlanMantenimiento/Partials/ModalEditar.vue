<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import { reactive, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    plan: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "save"]);

const form = reactive({
    id: null,
    titulo: "",
    descripcion: "",
});

watch(
    () => props.plan,
    (value) => {
        form.id = value?.id ?? null;
        form.titulo = value?.titulo ?? "";
        form.descripcion = value?.descripcion ?? "";
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
            <span class="text-ugel-guinda">Editar Plan de Mantenimiento</span>
        </template>

        <template #content>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label
                        for="titulo"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Título
                    </label>
                    <input
                        id="titulo"
                        v-model="form.titulo"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Título del plan"
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
                        placeholder="Describe el plan de mantenimiento"
                        :disabled="loading"
                    />
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
                :disabled="loading || !form.titulo.trim()"
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
