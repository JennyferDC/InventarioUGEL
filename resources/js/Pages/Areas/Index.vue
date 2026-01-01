<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import FeedbackBanner from "@/Components/FeedbackBanner.vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { computed, ref, watch } from "vue";
import ModalEliminar from "./Partials/ModalEliminar.vue";
import ModalEditar from "./Partials/ModalEditar.vue";
import ModalCrear from "./Partials/ModaCrear.vue";

const props = defineProps({
    areas: {
        type: Array,
        default: () => [],
    },
});

const areas = ref([...props.areas]);

watch(
    () => props.areas,
    (value) => {
        areas.value = [...value];
    },
    { deep: true }
);

const searchTerm = ref("");

const showDeleteModal = ref(false);
const showEditModal = ref(false);
const showCreateModal = ref(false);
const areaSeleccionada = ref(null);
const areaEditando = ref(null);
const deleting = ref(false);
const saving = ref(false);
const creating = ref(false);
const modalCrearRef = ref(null);

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

const filteredAreas = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();

    if (!term) return areas.value;

    return areas.value.filter((area) => {
        const nombre = area.nombre?.toLowerCase() ?? "";
        const descripcion = area.descripcion?.toLowerCase() ?? "";
        return nombre.includes(term) || descripcion.includes(term);
    });
});

const hayAreas = computed(() => filteredAreas.value.length > 0);

const abrirModalEliminar = (area) => {
    areaSeleccionada.value = area;
    showDeleteModal.value = true;
};

const cerrarModalEliminar = () => {
    showDeleteModal.value = false;
    areaSeleccionada.value = null;
};

const confirmarEliminacion = async () => {
    if (!areaSeleccionada.value) return;

    deleting.value = true;

    try {
        await axios.delete(route("areas.destroy", areaSeleccionada.value.id));
        areas.value = areas.value.filter(
            (area) => area.id !== areaSeleccionada.value.id
        );
        cerrarModalEliminar();
        triggerMessage("success", "Área eliminada correctamente.");
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo eliminar el área. Intenta nuevamente."
        );
    } finally {
        deleting.value = false;
    }
};

const abrirModalEditar = (area) => {
    areaEditando.value = { ...area };
    showEditModal.value = true;
};

const cerrarModalEditar = () => {
    showEditModal.value = false;
    areaEditando.value = null;
};

const guardarCambios = async (payload) => {
    if (!payload?.id) return;

    saving.value = true;

    try {
        const { data } = await axios.put(
            route("areas.update", payload.id),
            payload
        );

        const updated = data?.data ?? payload;

        areas.value = areas.value.map((area) =>
            area.id === payload.id ? { ...area, ...updated } : area
        );
        triggerMessage("success", "Área actualizada correctamente.");
        cerrarModalEditar();
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo actualizar el área. Revisa los datos e intenta otra vez."
        );
    } finally {
        saving.value = false;
    }
};

const abrirModalCrear = () => {
    showCreateModal.value = true;
};

const cerrarModalCrear = () => {
    showCreateModal.value = false;
    modalCrearRef.value?.resetForm();
};

const crearArea = async (payload) => {
    if (!payload?.nombre) return;

    creating.value = true;
    try {
        const { data } = await axios.post(route("areas.store"), payload);
        if (data?.data) {
            areas.value = [...areas.value, data.data].sort((a, b) =>
                a.nombre.localeCompare(b.nombre)
            );
        }
        triggerMessage("success", "Área creada correctamente.");
        cerrarModalCrear();
    } catch (error) {
        const message =
            error.response?.data?.message ||
            "No se pudo crear el área. Intenta nuevamente.";
        triggerMessage("error", message);
    } finally {
        creating.value = false;
    }
};
</script>

<template>
    <AppLayout title="Áreas">
        <template #header>
            <h2 class="font-bold text-3xl text-ugel-guinda leading-tight">
                Áreas
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
                        <label class="sr-only" for="search">Buscar área</label>
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
                                id="search"
                                v-model="searchTerm"
                                type="text"
                                placeholder="Buscar por nombre o descripción..."
                                class="w-full rounded-lg border border-ugel-azul/30 pl-10 pr-4 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-ugel-azul px-4 py-2 text-white font-semibold shadow-sm hover:bg-ugel-guinda transition-colors duration-150"
                        @click="abrirModalCrear"
                    >
                        + Nueva área
                    </button>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-ugel-azul/20 bg-white/90 shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ugel-azul/20">
                            <thead class="bg-ugel-azul/5">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        #
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Nombre
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Descripción
                                    </th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                v-if="hayAreas"
                                class="divide-y divide-ugel-azul/10"
                            >
                                <tr
                                    v-for="area in filteredAreas"
                                    :key="area.id"
                                    class="hover:bg-ugel-azul/5 transition"
                                >
                                    <td
                                        class="px-6 py-4 text-sm text-gray-600 font-semibold"
                                    >
                                        #{{ area.id }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm text-ugel-guinda font-semibold"
                                    >
                                        {{ area.nombre }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{
                                            area.descripcion ||
                                            "Sin descripción"
                                        }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div
                                            class="flex items-center justify-center gap-3"
                                        >
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full border border-ugel-azul/40 p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                                                @click="abrirModalEditar(area)"
                                            >
                                                <svg
                                                    class="size-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652l-1.688 1.687m-2.651-2.651L6.312 17.513a4.5 4.5 0 00-1.053 1.682l-.795 2.385a.563.563 0 00.711.71l2.385-.794a4.5 4.5 0 001.682-1.054L19.513 7.125m-2.651-2.651l2.651 2.651"
                                                    />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-600 hover:text-white transition"
                                                @click="
                                                    abrirModalEliminar(area)
                                                "
                                            >
                                                <svg
                                                    class="size-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-6 py-12 text-center text-gray-600"
                                    >
                                        No hay áreas que coincidan con la
                                        búsqueda.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <ModalEliminar
            :show="showDeleteModal"
            :area="areaSeleccionada"
            :loading="deleting"
            @close="cerrarModalEliminar"
            @confirm="confirmarEliminacion"
        />

        <ModalEditar
            :show="showEditModal"
            :area="areaEditando"
            :loading="saving"
            @close="cerrarModalEditar"
            @save="guardarCambios"
        />

        <ModalCrear
            ref="modalCrearRef"
            :show="showCreateModal"
            :loading="creating"
            @close="cerrarModalCrear"
            @save="crearArea"
        />
    </AppLayout>
</template>
