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
const isOficinaDropdownOpen = ref(false);
const searchSelectOficina = ref("");

const filteredSelectOficinas = computed(() => {
    if (!searchSelectOficina.value) return props.oficinas;
    const q = searchSelectOficina.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    return props.oficinas.filter(o => {
        const text = `${o.nombre} ${o.area?.nombre || ''}`.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        return text.includes(q);
    });
});

const getOficinaName = computed(() => {
    if (filtroOficina.value === "todos") return "Todas las oficinas";
    const oficina = props.oficinas.find(o => o.id === filtroOficina.value);
    return oficina ? oficina.nombre : "Todas las oficinas";
});

const selectOficina = (oficinaId) => {
    filtroOficina.value = oficinaId;
    isOficinaDropdownOpen.value = false;
    searchSelectOficina.value = "";
};

const sortConfig = ref({
    key: null,
    direction: null
});

const setSort = (key) => {
    if (sortConfig.value.key === key) {
        if (sortConfig.value.direction === 'asc') {
            sortConfig.value.direction = 'desc';
        } else if (sortConfig.value.direction === 'desc') {
            sortConfig.value.key = null;
            sortConfig.value.direction = null;
        }
    } else {
        sortConfig.value.key = key;
        sortConfig.value.direction = 'asc';
    }
};

const getSortIcon = (key) => {
    const isSorted = sortConfig.value.key === key;
    const baseClasses = "size-3.5 transition-all duration-200";
    
    if (!isSorted) {
        return `<svg class="${baseClasses} text-ugel-azul/30 hover:text-ugel-azul/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                </svg>`;
    }
    
    if (sortConfig.value.direction === 'asc') {
        return `<svg class="${baseClasses} text-ugel-azul font-bold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                </svg>`;
    }
    
    return `<svg class="${baseClasses} text-ugel-azul font-bold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>`;
};

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

    let result = personas.value.filter((persona) => {
        const coincideBusqueda =
            !term ||
            persona.nombre_completo?.toLowerCase().includes(term) ||
            persona.oficina?.nombre?.toLowerCase().includes(term);

        const coincideOficina =
            oficinaId === "todos" || Number(oficinaId) === Number(persona.id_oficina);

        return coincideBusqueda && coincideOficina;
    });

    if (sortConfig.value.key) {
        result.sort((a, b) => {
            let valA, valB;
            if (sortConfig.value.key === 'estado') {
                valA = a.estado || '';
                valB = b.estado || '';
            } else if (sortConfig.value.key === 'equipos') {
                valA = a.equipos_count || 0;
                valB = b.equipos_count || 0;
            } else if (sortConfig.value.key === 'oficinas') {
                valA = a.oficina?.nombre || '';
                valB = b.oficina?.nombre || '';
            }

            if (valA < valB) return sortConfig.value.direction === 'asc' ? -1 : 1;
            if (valA > valB) return sortConfig.value.direction === 'asc' ? 1 : -1;
            return 0;
        });
    } else {
        result.sort((a, b) => {
            if (a.estado === 'ACTIVO' && b.estado === 'INACTIVO') return -1;
            if (a.estado === 'INACTIVO' && b.estado === 'ACTIVO') return 1;
            return b.id - a.id;
        });
    }

    return result;
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

                        <!-- Select Personalizado de Oficinas -->
                        <div class="relative w-full sm:w-72">
                            <!-- Overlay para cerrar clickeando fuera -->
                            <div v-if="isOficinaDropdownOpen" @click="isOficinaDropdownOpen = false" class="fixed inset-0 z-10"></div>
                            
                            <div class="relative z-20">
                                <button 
                                    @click="isOficinaDropdownOpen = !isOficinaDropdownOpen"
                                    type="button" 
                                    class="w-full flex items-center justify-between rounded-lg border border-ugel-azul/30 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul transition-all"
                                >
                                    <span class="truncate">{{ getOficinaName }}</span>
                                    <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div 
                                    v-if="isOficinaDropdownOpen"
                                    class="absolute mt-1 max-h-60 w-full overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none flex flex-col"
                                >
                                    <div class="p-2 border-b border-gray-100 bg-gray-50/50 sticky top-0 z-10">
                                        <input 
                                            type="text" 
                                            v-model="searchSelectOficina" 
                                            placeholder="Buscar oficina..." 
                                            class="w-full rounded-md border border-ugel-azul/30 px-3 py-1.5 text-sm focus:border-ugel-azul focus:ring-ugel-azul shadow-sm"
                                            autofocus
                                        >
                                    </div>
                                    <ul class="overflow-y-auto py-1">
                                    <li 
                                        v-if="!searchSelectOficina"
                                        @click="selectOficina('todos')"
                                        class="cursor-pointer select-none relative py-2 px-3 hover:bg-ugel-azul/5 transition-colors"
                                        :class="filtroOficina === 'todos' ? 'bg-ugel-azul/10 text-ugel-azul font-bold' : 'text-gray-900'"
                                    >
                                        <span class="block truncate">Todas las oficinas</span>
                                    </li>
                                    <li 
                                        v-if="filteredSelectOficinas.length === 0"
                                        class="py-3 px-3 text-gray-500 text-center text-xs"
                                    >
                                        No se encontraron resultados
                                    </li>
                                    <li 
                                        v-for="oficina in filteredSelectOficinas" 
                                        :key="oficina.id"
                                        @click="selectOficina(oficina.id)"
                                        class="cursor-pointer select-none relative py-2 px-3 hover:bg-ugel-azul/5 flex flex-col transition-colors"
                                        :class="filtroOficina === oficina.id ? 'bg-ugel-azul/10 text-ugel-azul' : 'text-gray-900'"
                                    >
                                        <span class="block font-medium truncate" :class="filtroOficina === oficina.id ? 'font-bold' : ''">{{ oficina.nombre }}</span>
                                        <span class="block text-[10px] text-gray-500 uppercase tracking-wide truncate mt-0.5" v-if="oficina.area">
                                            {{ oficina.area.nombre }}
                                        </span>
                                    </li>
                                    </ul>
                                </div>
                            </div>
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
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul">Contacto</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul cursor-pointer group hover:bg-ugel-azul/10 transition-colors" @click="setSort('oficinas')">
                                        <div class="flex items-center gap-1">
                                            Oficina / Área
                                            <span v-html="getSortIcon('oficinas')"></span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-ugel-azul cursor-pointer group hover:bg-ugel-azul/10 transition-colors" @click="setSort('estado')">
                                        <div class="flex items-center justify-center gap-1">
                                            Estado
                                            <span v-html="getSortIcon('estado')"></span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-ugel-azul cursor-pointer group hover:bg-ugel-azul/10 transition-colors" @click="setSort('equipos')">
                                        <div class="flex items-center justify-center gap-1">
                                            Equipos
                                            <span v-html="getSortIcon('equipos')"></span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-ugel-azul">Acciones</th>
                                </tr>
                            </thead>
                            <tbody v-if="hayPersonas" class="divide-y divide-ugel-azul/10">
                                <tr v-for="persona in filteredPersonas" :key="persona.id" class="hover:bg-ugel-azul/5 transition">
                                    <td class="px-6 py-4 text-sm text-gray-600 font-semibold">#{{ persona.id }}</td>
                                    <td class="px-6 py-4 text-sm text-ugel-guinda font-semibold">
                                        {{ persona.nombre_completo }}
                                        <div v-if="persona.cargo" class="text-xs text-gray-500 font-normal mt-0.5">{{ persona.cargo }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="flex items-center gap-1.5 mb-1" v-if="persona.celular">
                                            <svg class="size-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ persona.celular }}
                                        </div>
                                        <div class="flex items-center gap-1.5 text-xs" v-if="persona.correo">
                                            <svg class="size-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            {{ persona.correo }}
                                        </div>
                                        <div v-if="!persona.celular && !persona.correo" class="text-gray-400">-</div>
                                    </td>
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
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
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
