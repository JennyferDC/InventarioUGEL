<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import FeedbackBanner from "@/Components/FeedbackBanner.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import DialogModal from "@/Components/DialogModal.vue";
import axios from "axios";
import { computed, reactive, ref, watch } from "vue";
import ModalAgregar from "./Partials/ModalAgregar.vue";

const props = defineProps({
    archivos: {
        type: Array,
        default: () => [],
    },
});

const archivos = ref([...props.archivos]);

watch(
    () => props.archivos,
    (value) => {
        archivos.value = [...value];
    },
    { deep: true },
);

const searchTerm = ref("");

const showCreateDrawer = ref(false);
const creating = ref(false);

const showDeleteModal = ref(false);
const deleting = ref(false);
const archivoSeleccionado = ref(null);

const showEditModal = ref(false);
const saving = ref(false);
const formEditar = reactive({
    id: null,
    nombre_archivo: "",
});

const successMessage = ref("");
const errorMessage = ref("");
let feedbackTimeout = null;

const showSuccess = computed(() => Boolean(successMessage.value));
const showError = computed(() => Boolean(errorMessage.value));

const clearFeedbackTimeout = () => {
    if (feedbackTimeout) {
        clearTimeout(feedbackTimeout);
        feedbackTimeout = null;
    }
};

const triggerMessage = (type, message) => {
    clearFeedbackTimeout();

    if (type === "success") {
        successMessage.value = message;
        errorMessage.value = "";
    } else {
        errorMessage.value = message;
        successMessage.value = "";
    }

    feedbackTimeout = setTimeout(() => {
        successMessage.value = "";
        errorMessage.value = "";
    }, 4000);
};

const filteredArchivos = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    if (!term) return archivos.value;

    return archivos.value.filter((item) =>
        (item.nombre_archivo ?? "").toLowerCase().includes(term),
    );
});

const abrirAgregar = () => {
    showCreateDrawer.value = true;
};

const cerrarAgregar = () => {
    showCreateDrawer.value = false;
};

const subirArchivo = async (payload) => {
    creating.value = true;
    try {
        const fd = new FormData();

        if (Array.isArray(payload)) {
            payload.forEach((item) => {
                fd.append("archivos[]", item.archivo);
                fd.append("nombres[]", item.nombre_archivo);
                fd.append("fechas[]", item.fecha_carga);
            });
        } else {
            fd.append("archivo", payload.archivo);
            fd.append("nombre_archivo", payload.nombre_archivo);
            fd.append("fecha_carga", payload.fecha_carga);
        }

        const { data } = await axios.post(route("archivos.store"), fd, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });

        if (data?.data) {
            const nuevos = Array.isArray(data.data) ? data.data : [data.data];
            archivos.value = [...nuevos, ...archivos.value];
        }
        triggerMessage(
            "success",
            Array.isArray(payload)
                ? "Archivos cargados correctamente."
                : "Archivo cargado correctamente.",
        );
        cerrarAgregar();
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo cargar el archivo. Intenta nuevamente.",
        );
    } finally {
        creating.value = false;
    }
};

const abrirEditar = (archivo) => {
    if (!archivo?.id) return;
    formEditar.id = archivo.id;
    formEditar.nombre_archivo = archivo.nombre_archivo ?? "";
    showEditModal.value = true;
};

const cerrarEditar = () => {
    showEditModal.value = false;
    formEditar.id = null;
    formEditar.nombre_archivo = "";
};

const guardarNombre = async () => {
    if (!formEditar.id) return;
    saving.value = true;
    try {
        const { data } = await axios.put(
            route("archivos.update", formEditar.id),
            {
                nombre_archivo: formEditar.nombre_archivo,
            },
        );

        const updated = data?.data ?? null;
        if (updated) {
            archivos.value = archivos.value.map((item) =>
                item.id === formEditar.id ? { ...item, ...updated } : item,
            );
        }
        triggerMessage("success", "Nombre actualizado correctamente.");
        cerrarEditar();
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo actualizar el nombre del archivo.",
        );
    } finally {
        saving.value = false;
    }
};

const abrirEliminar = (archivo) => {
    archivoSeleccionado.value = archivo;
    showDeleteModal.value = true;
};

const cerrarEliminar = () => {
    showDeleteModal.value = false;
    archivoSeleccionado.value = null;
};

const confirmarEliminacion = async () => {
    if (!archivoSeleccionado.value?.id) return;
    deleting.value = true;
    try {
        await axios.delete(
            route("archivos.destroy", archivoSeleccionado.value.id),
        );
        archivos.value = archivos.value.filter(
            (item) => item.id !== archivoSeleccionado.value.id,
        );
        triggerMessage("success", "Archivo eliminado correctamente.");
        cerrarEliminar();
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo eliminar el archivo. Intenta nuevamente.",
        );
    } finally {
        deleting.value = false;
    }
};

const obtenerExtension = (archivo) => {
    const fuente = archivo?.ruta || archivo?.nombre_archivo || "";
    const nombre = fuente.split("/").pop() || "";
    const parts = nombre.split(".");
    if (parts.length < 2) return "";
    return String(parts.pop() || "").toUpperCase();
};
</script>

<template>
    <AppLayout title="Archivos">
        <template #header>
            <h2 class="font-bold text-3xl text-ugel-guinda leading-tight">
                Archivos
            </h2>
        </template>

        <section class="py-10 space-y-6">
            <div class="max-w-6xl mx-auto px-6 lg:px-0 space-y-2">
                <FeedbackBanner
                    :show="showSuccess"
                    :message="successMessage"
                    type="success"
                />
                <FeedbackBanner
                    :show="showError"
                    :message="errorMessage"
                    type="error"
                />
            </div>

            <div class="max-w-6xl mx-auto px-6 lg:px-0 space-y-6">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="w-full sm:w-80">
                        <label class="sr-only" for="search-archivo"
                            >Buscar archivo</label
                        >
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3 text-ugel-azul/70"
                            >
                                <svg
                                    class="size-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"
                                    />
                                </svg>
                            </span>
                            <input
                                id="search-archivo"
                                v-model="searchTerm"
                                type="text"
                                placeholder="Buscar por título..."
                                class="w-full rounded-lg border border-ugel-azul/30 pl-10 pr-4 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                            />
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-ugel-azul px-4 py-2 text-white font-semibold shadow-sm hover:bg-ugel-guinda transition-colors duration-150"
                        @click="abrirAgregar"
                    >
                        + Agregar archivo
                    </button>
                </div>

                <div
                    v-if="filteredArchivos.length"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <article
                        v-for="archivo in filteredArchivos"
                        :key="archivo.id"
                        class="rounded-2xl border border-ugel-azul/15 bg-white/90 shadow-sm p-5 hover:shadow-md transition"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div
                                    class="text-xs font-semibold uppercase tracking-wider text-ugel-azul/70"
                                >
                                    Archivo
                                </div>
                                <div
                                    class="mt-1 text-base font-bold text-ugel-guinda break-words"
                                >
                                    {{ archivo.nombre_archivo }}
                                </div>

                                <div
                                    v-if="obtenerExtension(archivo)"
                                    class="mt-2"
                                >
                                    <span
                                        class="inline-flex items-center rounded-full bg-ugel-azul/10 px-2.5 py-1 text-[11px] font-bold tracking-wide text-ugel-azul"
                                    >
                                        {{ obtenerExtension(archivo) }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    Fecha de carga:
                                    <span class="font-semibold text-gray-700">
                                        {{
                                            archivo.fecha_carga
                                                ? new Date(
                                                      archivo.fecha_carga,
                                                  ).toLocaleDateString("es-PE")
                                                : "-"
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-full border border-ugel-azul/40 p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                                    @click="abrirEditar(archivo)"
                                    title="Editar nombre"
                                >
                                    <svg
                                        class="size-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652l-1.688 1.687m-2.651-2.651L6.312 17.513a4.5 4.5 0 00-1.053 1.682l-.795 2.385a.563.563 0 00.711.71l2.385-.794a4.5 4.5 0 001.682-1.054L19.513 7.125m-2.651-2.651l2.651 2.651"
                                        />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-full border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-600 hover:text-white transition"
                                    @click="abrirEliminar(archivo)"
                                    title="Eliminar"
                                >
                                    <svg
                                        class="size-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a
                                v-if="archivo.url"
                                :href="archivo.url"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-ugel-azul hover:text-ugel-guinda transition"
                            >
                                Ver / Descargar
                                <svg
                                    class="size-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M13.5 4.5H19.5V10.5M10.5 13.5L19.5 4.5M6 19.5H18a1.5 1.5 0 001.5-1.5v-6"
                                    />
                                </svg>
                            </a>
                            <div v-else class="text-xs text-gray-500">
                                Sin URL
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="rounded-2xl border border-dashed border-ugel-azul/20 bg-white/70 p-10 text-center text-gray-600"
                >
                    No hay archivos registrados.
                </div>
            </div>
        </section>

        <ModalAgregar
            :show="showCreateDrawer"
            :loading="creating"
            @close="cerrarAgregar"
            @save="subirArchivo"
        />

        <DialogModal :show="showEditModal" @close="cerrarEditar" max-width="lg">
            <template #title>
                <span class="text-ugel-guinda font-semibold"
                    >Editar nombre</span
                >
            </template>
            <template #content>
                <div class="space-y-3">
                    <label
                        for="edit-nombre"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Título
                    </label>
                    <input
                        id="edit-nombre"
                        v-model="formEditar.nombre_archivo"
                        type="text"
                        class="w-full rounded-lg border border-ugel-azul/30 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        :disabled="saving"
                    />
                </div>
            </template>
            <template #footer>
                <button
                    type="button"
                    class="me-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    @click="cerrarEditar"
                    :disabled="saving"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-ugel-azul px-4 py-2 text-sm font-semibold text-white shadow hover:bg-ugel-guinda disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="guardarNombre"
                    :disabled="saving || !formEditar.nombre_archivo.trim()"
                >
                    <svg
                        v-if="saving"
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
                    Guardar
                </button>
            </template>
        </DialogModal>

        <ConfirmationModal :show="showDeleteModal" @close="cerrarEliminar">
            <template #title>Eliminar archivo</template>
            <template #content>
                ¿Seguro que deseas eliminar
                <span class="font-semibold text-ugel-guinda">{{
                    archivoSeleccionado?.nombre_archivo
                }}</span
                >? Esta acción no se puede deshacer.
            </template>
            <template #footer>
                <button
                    type="button"
                    class="me-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    @click="cerrarEliminar"
                    :disabled="deleting"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="confirmarEliminacion"
                    :disabled="deleting"
                >
                    <svg
                        v-if="deleting"
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
                    Eliminar
                </button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
