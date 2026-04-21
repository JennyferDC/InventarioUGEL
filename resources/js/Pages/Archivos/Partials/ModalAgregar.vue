<script setup>
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "save"]);

const fileInputRef = ref(null);

const form = reactive({
    items: [],
});

const totalArchivos = computed(() => form.items.length);

const hoyISO = () => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
};

const obtenerTituloPorDefecto = (filename) => {
    if (!filename) return "";
    const lastDot = filename.lastIndexOf(".");
    return lastDot > 0 ? filename.slice(0, lastDot) : filename;
};

const resetForm = () => {
    form.items = [];
    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
};

watch(
    () => props.show,
    (value) => {
        if (!value) {
            resetForm();
        }
    },
    { immediate: true },
);

const agregarArchivos = (files) => {
    if (!files?.length) return;

    const nuevos = Array.from(files)
        .filter(Boolean)
        .map((file) => ({
            archivo: file,
            nombre_archivo: obtenerTituloPorDefecto(file.name),
            fecha_carga: hoyISO(),
        }));

    form.items = [...form.items, ...nuevos];
};

const quitarArchivo = (index) => {
    form.items = form.items.filter((_, i) => i !== index);
};

const onBrowse = () => {
    if (props.loading) return;
    fileInputRef.value?.click();
};

const onFileChange = (e) => {
    const files = e.target.files ?? [];
    agregarArchivos(files);
};

const onDrop = (e) => {
    if (props.loading) return;
    e.preventDefault();
    const files = e.dataTransfer?.files ?? [];
    agregarArchivos(files);
};

const onDragOver = (e) => {
    if (props.loading) return;
    e.preventDefault();
};

const handleClose = () => {
    emit("close");
};

const handleSubmit = () => {
    if (!form.items.length) return;
    const validos = form.items.filter(
        (item) =>
            item.archivo &&
            String(item.nombre_archivo ?? "").trim() &&
            item.fecha_carga,
    );
    if (validos.length !== form.items.length) return;

    emit("save", validos);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div class="absolute inset-0 bg-black/40" @click="handleClose" />
        </Transition>

        <Transition
            enter-active-class="transform transition duration-200"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition duration-200"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                class="absolute inset-y-0 right-0 w-full max-w-xl bg-white shadow-xl"
            >
                <div class="flex h-full flex-col">
                    <div
                        class="flex items-center justify-between border-b border-ugel-azul/10 px-5 py-4"
                    >
                        <div>
                            <div
                                class="text-xs font-semibold uppercase tracking-wider text-ugel-azul/70"
                            >
                                Archivos
                            </div>
                            <div class="text-lg font-bold text-ugel-guinda">
                                Agregar archivo
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                            @click="handleClose"
                        >
                            <svg
                                class="size-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-5 space-y-5">
                        <div>
                            <div
                                class="rounded-xl border-2 border-dashed border-ugel-azul/25 bg-ugel-azul/5 p-6 text-center"
                                @drop="onDrop"
                                @dragover="onDragOver"
                            >
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    multiple
                                    class="hidden"
                                    @change="onFileChange"
                                    :disabled="loading"
                                />

                                <div
                                    class="mx-auto flex size-12 items-center justify-center rounded-full bg-white text-ugel-azul shadow"
                                >
                                    <svg
                                        class="size-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3"
                                        />
                                    </svg>
                                </div>

                                <div
                                    class="mt-4 text-sm font-semibold text-gray-700"
                                >
                                    Suelta tus archivos aquí
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    o
                                    <button
                                        type="button"
                                        class="font-semibold text-ugel-azul hover:text-ugel-guinda transition"
                                        @click="onBrowse"
                                        :disabled="loading"
                                    >
                                        selecciona desde tu equipo
                                    </button>
                                </div>

                                <div
                                    v-if="totalArchivos"
                                    class="mt-4 text-xs text-gray-600"
                                >
                                    {{ totalArchivos }} archivo(s)
                                    seleccionado(s)
                                </div>
                            </div>
                        </div>

                        <div v-if="totalArchivos" class="space-y-4">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="rounded-xl border border-ugel-azul/10 bg-white p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            Archivo
                                        </div>
                                        <div
                                            class="mt-1 text-sm font-semibold text-ugel-guinda break-words"
                                        >
                                            {{ item.archivo?.name }}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-full border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-600 hover:text-white transition"
                                        @click="quitarArchivo(index)"
                                        :disabled="loading"
                                        title="Quitar"
                                    >
                                        <svg
                                            class="size-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-4">
                                    <div>
                                        <label
                                            :for="`archivo_titulo_${index}`"
                                            class="block text-sm font-medium text-gray-700"
                                        >
                                            Título
                                        </label>
                                        <input
                                            :id="`archivo_titulo_${index}`"
                                            v-model="item.nombre_archivo"
                                            type="text"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/30 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            :disabled="loading"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            :for="`archivo_fecha_${index}`"
                                            class="block text-sm font-medium text-gray-700"
                                        >
                                            Fecha de carga
                                        </label>
                                        <input
                                            :id="`archivo_fecha_${index}`"
                                            v-model="item.fecha_carga"
                                            type="date"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/30 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            :disabled="loading"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="border-t border-ugel-azul/10 px-5 py-4 flex items-center justify-end gap-3 bg-white"
                    >
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                            @click="handleClose"
                            :disabled="loading"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg bg-ugel-azul px-4 py-2 text-sm font-semibold text-white shadow hover:bg-ugel-guinda disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="handleSubmit"
                            :disabled="loading || !totalArchivos"
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
                            Subir archivo(s)
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
