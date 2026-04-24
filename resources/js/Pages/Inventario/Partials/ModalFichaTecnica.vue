<script setup>
import { reactive } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    equipoId: {
        type: [Number, String],
        required: true,
    },
});

const emit = defineEmits(["close", "download"]);

const form = reactive({
    incluir_datos_responsable: true,
    incluir_otros_equipos: false,
});

const handleDownload = () => {
    emit("download", { ...form });
    emit("close");
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div class="fixed inset-0 bg-black/40" @click="emit('close')" />
        </Transition>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl flex flex-col">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h3 class="text-lg font-bold text-gray-900">Opciones de Ficha Técnica</h3>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-gray-500 transition"
                        @click="emit('close')"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-6 space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <div class="flex items-center h-5">
                            <input
                                v-model="form.incluir_datos_responsable"
                                type="checkbox"
                                class="size-4 rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul"
                            />
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 block">Incluir datos del responsable</span>
                            <span class="text-gray-500">Muestra el nombre, celular y área del responsable asignado.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <div class="flex items-center h-5">
                            <input
                                v-model="form.incluir_otros_equipos"
                                type="checkbox"
                                class="size-4 rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul"
                            />
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 block">Incluir otros equipos de la persona</span>
                            <span class="text-gray-500">Muestra los datos básicos de los otros equipos asignados al mismo responsable.</span>
                        </div>
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4 bg-gray-50 rounded-b-2xl">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                        @click="emit('close')"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-ugel-azul px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-ugel-guinda transition"
                        @click="handleDownload"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Descargar
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
