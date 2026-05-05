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

const filteredAreas = computed(() => areas.value);

const hayAreas = computed(() => areas.value.length > 0);

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
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-3xl text-ugel-guinda leading-tight">
                    Directorio de Áreas
                </h2>
            </div>
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
                <div class="flex justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-ugel-azul px-4 py-2 text-white font-semibold shadow-sm hover:bg-ugel-guinda transition-colors duration-150"
                        @click="abrirModalCrear"
                    >
                        + Nueva área
                    </button>
                </div>
                <!-- Grid de Áreas -->
                <div v-if="hayAreas" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-12">
                    <div 
                        v-for="area in filteredAreas" 
                        :key="area.id"
                        @click="abrirModalEditar(area)"
                        class="group flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden relative cursor-pointer"
                    >
                        <div class="h-1.5 w-full bg-gradient-to-r from-ugel-azul to-ugel-guinda opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <div class="p-6 flex-1 flex flex-col relative z-10">
                            <div class="flex items-start justify-between mb-5">
                                <div class="flex items-center justify-center size-12 rounded-xl bg-blue-50/80 text-ugel-azul group-hover:bg-ugel-azul group-hover:text-white transition-all duration-300 shadow-inner">
                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="flex items-center gap-1 text-ugel-azul font-medium text-sm bg-ugel-azul/10 px-3 py-1 rounded-full group-hover:bg-ugel-azul group-hover:text-white transition-colors">
                                    Editar
                                </div>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-ugel-azul transition-colors line-clamp-2 leading-tight">
                                {{ area.nombre }}
                            </h3>
                            
                            <p class="text-sm text-gray-500 line-clamp-3 mb-4 flex-1">
                                {{ area.descripcion || 'Sin descripción.' }}
                            </p>
                            
                            <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                                    {{ area.oficinas_count || 0 }} {{ area.oficinas_count === 1 ? 'oficina' : 'oficinas' }}
                                </span>
                                
                                <a 
                                    :href="route('oficinas.index') + '?area=' + area.id"
                                    @click.stop
                                    class="text-ugel-azul hover:text-ugel-guinda text-xs font-bold px-2 py-1 rounded-md hover:bg-gray-50 transition-colors flex items-center gap-1 z-20 relative"
                                >
                                    Ver oficinas
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center py-20 px-4 text-center h-full bg-white rounded-3xl border border-dashed border-gray-300">
                    <div class="size-20 bg-gray-50 text-gray-300 rounded-2xl flex items-center justify-center mb-5 rotate-3 hover:rotate-0 transition-transform">
                        <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No se encontraron áreas</h3>
                    <p class="text-gray-500 max-w-sm mx-auto text-sm">
                        Aún no se han registrado áreas en el sistema.
                    </p>
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
