<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import FeedbackBanner from "@/Components/FeedbackBanner.vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { computed, ref, watch, onMounted } from "vue";
import ModalEliminar from "./Partials/ModalEliminar.vue";
import ModalEditar from "./Partials/ModalEditar.vue";
import ModalCrear from "./Partials/ModaCrear.vue";

const props = defineProps({
    personas: {
        type: Array,
        default: () => [],
    },
    oficinas: {
        type: Array,
        default: () => [],
    },
});

const personas = ref([...props.personas]);

watch(
    () => props.personas,
    (value) => {
        personas.value = [...value];
    },
    { deep: true }
);

const searchTerm = ref("");
const filtroOficina = ref("todos");

onMounted(() => {
    // Leer el parámetro 'oficina' de la URL si existe
    const params = new URLSearchParams(window.location.search);
    if (params.has('oficina')) {
        filtroOficina.value = Number(params.get('oficina'));
    }
});

const showDeleteModal = ref(false);
const showEditModal = ref(false);
const showCreateModal = ref(false);
const personaSeleccionada = ref(null);
const personaEditando = ref(null);
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

const filteredPersonas = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    const oficinaId = filtroOficina.value;

    return personas.value.filter((persona) => {
        const coincideBusqueda =
            !term ||
            persona.nombre_completo?.toLowerCase().includes(term) ||
            persona.oficina?.nombre?.toLowerCase().includes(term);

        const coincideOficina =
            oficinaId === "todos" || Number(oficinaId) === Number(persona.id_oficina);

        return coincideBusqueda && coincideOficina;
    });
});

const hayPersonas = computed(() => filteredPersonas.value.length > 0);

const abrirModalEliminar = (persona) => {
    personaSeleccionada.value = persona;
    showDeleteModal.value = true;
};

const cerrarModalEliminar = () => {
    showDeleteModal.value = false;
    personaSeleccionada.value = null;
};

const abrirModalDarDeBaja = (persona) => {
    cerrarModalEditar();
    abrirModalEliminar(persona);
};

const confirmarEliminacion = async () => {
    if (!personaSeleccionada.value) return;

    deleting.value = true;

    try {
        const { data } = await axios.delete(
            route("personas.destroy", personaSeleccionada.value.id)
        );
        const updated = data?.data;
        if (updated) {
            personas.value = personas.value.map((persona) =>
                persona.id === updated.id ? { ...persona, ...updated } : persona
            );
        }
        cerrarModalEliminar();
        triggerMessage("success", "Estado de la cuenta actualizado correctamente.");
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo actualizar el estado de la persona. Intenta nuevamente."
        );
    } finally {
        deleting.value = false;
    }
};

const abrirModalEditar = (persona) => {
    personaEditando.value = { ...persona };
    showEditModal.value = true;
};

const cerrarModalEditar = () => {
    showEditModal.value = false;
    personaEditando.value = null;
};

const guardarCambios = async (payload) => {
    if (!payload?.id) return;

    saving.value = true;

    try {
        const { data } = await axios.put(
            route("personas.update", payload.id),
            payload
        );

        const updated = data?.data ?? payload;

        personas.value = personas.value.map((persona) =>
            persona.id === payload.id ? { ...persona, ...updated } : persona
        );
        triggerMessage("success", "Persona actualizada correctamente.");
        cerrarModalEditar();
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo actualizar la persona. Revisa los datos."
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

const crearPersona = async (payload) => {
    if (!payload?.nombre_completo || !payload.id_oficina) return;

    creating.value = true;

    try {
        const { data } = await axios.post(route("personas.store"), payload);
        if (data?.data) {
            personas.value = [...personas.value, data.data].sort((a, b) =>
                a.nombre_completo.localeCompare(b.nombre_completo)
            );
        }
        triggerMessage("success", "Persona creada correctamente.");
        cerrarModalCrear();
    } catch (error) {
        const message =
            error.response?.data?.message ||
            "No se pudo crear la persona. Intenta nuevamente.";
        triggerMessage("error", message);
    } finally {
        creating.value = false;
    }
};
</script>

<template>
    <AppLayout title="Personas">
        <template #header>
            <h2 class="font-bold text-3xl text-ugel-guinda leading-tight">
                Personas
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
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center w-full">
                        <div class="w-full sm:w-72 relative">
                            <label class="sr-only" for="search-persona">Buscar persona</label>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-ugel-azul/70">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" />
                                </svg>
                            </span>
                            <input
                                id="search-persona"
                                v-model="searchTerm"
                                type="text"
                                placeholder="Buscar por nombre u oficina..."
                                class="w-full rounded-lg border border-ugel-azul/30 pl-10 pr-4 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul transition-shadow"
                            />
                        </div>

                        <div class="w-full sm:w-72">
                            <label class="sr-only" for="filtro-oficina">Filtrar por oficina</label>
                            <select
                                id="filtro-oficina"
                                v-model="filtroOficina"
                                class="w-full rounded-lg border border-ugel-azul/30 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                            >
                                <option value="todos">Todas las oficinas</option>
                                <option
                                    v-for="oficina in oficinas"
                                    :key="oficina.id"
                                    :value="oficina.id"
                                >
                                    {{ oficina.nombre }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center whitespace-nowrap gap-2 rounded-lg bg-ugel-azul px-4 py-2 text-white font-semibold shadow-sm hover:bg-ugel-guinda transition-colors duration-150"
                        @click="abrirModalCrear"
                    >
                        + Nueva persona
                    </button>
                </div>

                <div class="overflow-hidden rounded-xl border border-ugel-azul/20 bg-white/90 shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ugel-azul/20">
                            <thead class="bg-ugel-azul/5">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul">Nombre completo</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul">Oficina / Área</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-ugel-azul">Estado</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-ugel-azul">Equipos</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-ugel-azul">Acciones</th>
                                </tr>
                            </thead>
                            <tbody v-if="hayPersonas" class="divide-y divide-ugel-azul/10">
                                <tr v-for="persona in filteredPersonas" :key="persona.id" class="hover:bg-ugel-azul/5 transition">
                                    <td class="px-6 py-4 text-sm text-gray-600 font-semibold">#{{ persona.id }}</td>
                                    <td class="px-6 py-4 text-sm text-ugel-guinda font-semibold">{{ persona.nombre_completo }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-700">{{ persona.oficina?.nombre ?? "Sin oficina" }}</div>
                                        <div class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">{{ persona.oficina?.area?.nombre ?? "Sin área" }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span 
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold border"
                                            :class="persona.estado === 'ACTIVO' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'"
                                        >
                                            {{ persona.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center bg-blue-50 text-blue-700 border border-blue-200 font-bold rounded-full px-3 py-1 text-xs">
                                            {{ persona.equipos_count ?? 0 }} 
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-3">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full border border-ugel-azul/40 p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                                                @click="abrirModalEditar(persona)"
                                            >
                                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652l-1.688 1.687m-2.651-2.651L6.312 17.513a4.5 4.5 0 00-1.053 1.682l-.795 2.385a.563.563 0 00.711.71l2.385-.794a4.5 4.5 0 001.682-1.054L19.513 7.125m-2.651-2.651l2.651 2.651" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-600">
                                        No hay personas registradas o que coincidan con los filtros aplicados.
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
            :persona="personaSeleccionada"
            :loading="deleting"
            @close="cerrarModalEliminar"
            @confirm="confirmarEliminacion"
        />

        <ModalEditar
            :show="showEditModal"
            :persona="personaEditando"
            :oficinas="oficinas"
            :loading="saving"
            @close="cerrarModalEditar"
            @save="guardarCambios"
            @toggle-status="abrirModalDarDeBaja"
            />

        <ModalCrear
            ref="modalCrearRef"
            :show="showCreateModal"
            :oficinas="oficinas"
            :loading="creating"
            @close="cerrarModalCrear"
            @save="crearPersona"
        />
    </AppLayout>
</template>
