<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { reactive, computed } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    errors: {
        type: Object,
        default: () => ({}),
    }
});

const emit = defineEmits(["close", "save"]);

const currentYear = new Date().getFullYear();
const years = [currentYear, currentYear + 1];

const form = reactive({
    anio: currentYear,
    descripcion: "",
});

const prefix = "Plan anual de mantenimiento informático";

const resetForm = () => {
    form.anio = currentYear;
    form.descripcion = "";
};

const handleClose = () => {
    resetForm();
    emit("close");
};

const handleSubmit = () => {
    emit("save", {
        titulo: form.anio.toString(),
        descripcion: form.descripcion,
    });
};

defineExpose({ resetForm });
</script>

<template>
    <DialogModal :show="show" @close="handleClose" max-width="lg">
        <template #title>
            <span class="text-ugel-guinda font-semibold">Nuevo Plan de Mantenimiento</span>
        </template>

        <template #content>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <InputLabel value="Título del Plan" />
                    <div class="flex items-center gap-2 mt-1">
                        <div class="flex-1 bg-gray-50 border border-gray-300 text-gray-500 text-sm rounded-lg px-3 py-2 select-none truncate">
                            {{ prefix }}
                        </div>
                        <span class="text-gray-400 font-bold">-</span>
                        <select
                            v-model="form.anio"
                            class="w-32 rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                            :disabled="loading"
                        >
                            <option v-for="year in years" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>
                    <InputError :message="errors.titulo" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="descripcion" value="Descripción" />
                    <textarea
                        id="descripcion"
                        v-model="form.descripcion"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Escribe una breve descripción del plan (opcional)"
                        :disabled="loading"
                    />
                    <InputError :message="errors.descripcion" class="mt-2" />
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
                Crear plan
            </button>
        </template>
    </DialogModal>
</template>
