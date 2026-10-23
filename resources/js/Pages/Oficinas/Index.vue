<script setup>
import { ref, computed, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import ModalCrear from "./Partials/ModaCrear.vue";
import ModalEditar from "./Partials/ModalEditar.vue";
import FeedbackBanner from "@/Components/FeedbackBanner.vue";

const props = defineProps({
    oficinas: {
        type: Array,
        required: true,
    },
    areas: {
        type: Array,
        required: true,
    },
});

const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const areaParam = urlParams ? urlParams.get('area') : null;
const activeArea = ref(areaParam ? parseInt(areaParam) : "todos");
const searchQuery = ref("");
const isDropdownOpen = ref(false);

const oficinasList = ref([...props.oficinas]);

watch(
    () => props.oficinas,
    (value) => {
        oficinasList.value = [...value];
    },
    { deep: true }
);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const oficinaEditando = ref(null);
const creating = ref(false);
const saving = ref(false);
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

const abrirModalCrear = () => {
    showCreateModal.value = true;
};

const cerrarModalCrear = () => {
    showCreateModal.value = false;
    modalCrearRef.value?.resetForm();
};

const crearOficina = async (payload) => {
    if (!payload?.nombre || !payload?.area_id) return;

    creating.value = true;
    try {
        const { data } = await axios.post(route("oficinas.store"), payload);
        if (data?.data) {
            oficinasList.value = [...oficinasList.value, data.data].sort((a, b) =>
                a.nombre.localeCompare(b.nombre)
            );
        }
        triggerMessage("success", "Oficina creada correctamente.");
        cerrarModalCrear();
    } catch (error) {
        const message =
            error.response?.data?.message ||
            "No se pudo crear la oficina. Intenta nuevamente.";
        triggerMessage("error", message);
    } finally {
        creating.value = false;
    }
};

const abrirModalEditar = (oficina) => {
    oficinaEditando.value = { ...oficina };
    showEditModal.value = true;
};

const cerrarModalEditar = () => {
    showEditModal.value = false;
    oficinaEditando.value = null;
};

const guardarCambios = async (payload) => {
    if (!payload?.id) return;

    saving.value = true;

    try {
        const { data } = await axios.put(
            route("oficinas.update", payload.id),
            payload
        );

        const updated = data?.data ?? payload;

        oficinasList.value = oficinasList.value.map((oficina) =>
            oficina.id === payload.id ? { ...oficina, ...updated } : oficina
        );
        triggerMessage("success", "Oficina actualizada correctamente.");
        cerrarModalEditar();
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo actualizar la oficina. Revisa los datos e intenta otra vez."
        );
    } finally {
        saving.value = false;
    }
};

const selectArea = (areaId) => {
    activeArea.value = areaId;
    isDropdownOpen.value = false;
};

const getAreaName = computed(() => {
    if (activeArea.value === "todos") return "Todas las unidades";
    const area = props.areas.find(a => a.id === activeArea.value);
    return area ? area.nombre : "Todas las unidades";
});

const getCountForArea = (areaId) => {
    if (areaId === "todos") return props.oficinas.length;
    return props.oficinas.filter(o => o.area_id === areaId).length;
};

const filteredOficinas = computed(() => {
    return oficinasList.value.filter(oficina => {
        const matchesArea = activeArea.value === "todos" || oficina.area_id === activeArea.value;
        const matchesSearch = oficina.nombre.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              (oficina.descripcion && oficina.descripcion.toLowerCase().includes(searchQuery.value.toLowerCase()));
        return matchesArea && matchesSearch;
    });
});
</script>

<template>
    <AppLayout title="Oficinas">
        <template #header>
            <h2 class="font-bold text-2xl sm:text-3xl text-ugel-guinda leading-tight">
                Directorio de Oficinas
            </h2>
        </template>

        <section class="py-6 sm:py-8 space-y-4 sm:space-y-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-2">
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

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                <!-- Filtros y Buscador -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 sm:gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center flex-wrap gap-2.5 sm:gap-3 flex-1">
                        
                        <!-- Buscador -->
                        <div class="w-full sm:w-64 md:w-72 lg:w-80 relative">
                            <label class="sr-only" for="search-oficina">Buscar oficina</label>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-ugel-azul/70">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" />
                                </svg>
                            </span>
                            <input
                                id="search-oficina"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar oficina..."
                                class="w-full rounded-lg border border-ugel-azul/30 pl-10 pr-4 py-2 text-xs sm:text-sm focus:border-ugel-azul focus:ring-ugel-azul transition-shadow shadow-sm"
                            />
                        </div>

                        <!-- Select Personalizado de Unidades -->
                        <div class="relative w-full sm:w-64 md:w-72">
                            <!-- Overlay para cerrar clickeando fuera -->
                            <div v-if="isDropdownOpen" @click="isDropdownOpen = false" class="fixed inset-0 z-10"></div>
                            
                            <div class="relative z-20">
                                <button 
                                    @click="isDropdownOpen = !isDropdownOpen"
                                    type="button" 
                                    class="w-full flex items-center justify-between rounded-lg border border-ugel-azul/30 bg-white px-3 py-2 text-xs sm:text-sm text-gray-700 shadow-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul transition-all"
                                >
                                    <span class="truncate">{{ getAreaName }}</span>
                                    <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <ul 
                                    v-if="isDropdownOpen"
                                    class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-xs sm:text-sm shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-30"
                                >
                                    <li 
                                        @click="selectArea('todos')"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-ugel-azul/5 flex items-center justify-between transition-colors"
                                        :class="activeArea === 'todos' ? 'bg-ugel-azul/10 text-ugel-azul font-bold' : 'text-gray-900'"
                                    >
                                        <span class="block truncate">Todas las unidades</span>
                                        <span class="inline-flex items-center justify-center bg-gray-100 text-gray-600 rounded-full px-2 py-0.5 text-xs font-medium">
                                            {{ getCountForArea('todos') }}
                                        </span>
                                    </li>
                                    <li 
                                        v-for="area in areas" 
                                        :key="area.id"
                                        @click="selectArea(area.id)"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-ugel-azul/5 flex items-center justify-between transition-colors"
                                        :class="activeArea === area.id ? 'bg-ugel-azul/10 text-ugel-azul font-bold' : 'text-gray-900'"
                                    >
                                        <span class="block truncate">{{ area.nombre }}</span>
                                        <span class="inline-flex items-center justify-center bg-gray-100 text-gray-600 rounded-full px-2 py-0.5 text-xs font-medium">
                                            {{ getCountForArea(area.id) }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-ugel-azul px-3.5 sm:px-4 py-2 text-xs sm:text-sm text-white font-semibold shadow-sm hover:bg-ugel-guinda transition-colors duration-150 w-full sm:w-auto shrink-0"
                        @click="abrirModalCrear"
                    >
                        + Nueva oficina
                    </button>
                </div>

                <!-- Grid de Oficinas -->
                <div v-if="filteredOficinas.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 pb-6 sm:pb-12">
                    <div 
                        v-for="oficina in filteredOficinas" 
                        :key="oficina.id"
                        @click="abrirModalEditar(oficina)"
                        class="group flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden relative cursor-pointer"
                    >
                        <div class="h-1.5 w-full bg-gradient-to-r from-ugel-azul to-ugel-guinda opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <div class="p-5 sm:p-6 flex-1 flex flex-col relative z-10">
                            <div class="flex items-start justify-between mb-4 sm:mb-5">
                                <div class="flex items-center justify-center size-11 sm:size-12 rounded-xl bg-blue-50/80 text-ugel-azul group-hover:bg-ugel-azul group-hover:text-white transition-all duration-300 shadow-inner shrink-0">
                                    <svg class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <!-- Botón Personas -->
                                <Link 
                                    :href="route('personas.index', { oficina: oficina.id })" 
                                    @click.stop
                                    class="text-ugel-azul hover:text-ugel-guinda text-xs font-bold px-2.5 py-1 rounded-md hover:bg-gray-50 transition-colors flex items-center gap-1 z-20 relative"
                                >
                                    Ver personal
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                            
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1.5 sm:mb-2 group-hover:text-ugel-azul transition-colors line-clamp-2 leading-tight">
                                {{ oficina.nombre }}
                            </h3>
                            
                            <p class="text-xs sm:text-sm text-gray-500 line-clamp-3 mb-4 flex-1">
                                {{ oficina.descripcion || 'Sin descripción disponible para esta oficina.' }}
                            </p>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center py-16 sm:py-20 px-4 text-center h-full bg-white rounded-3xl border border-dashed border-gray-300">
                    <div class="size-16 sm:size-20 bg-gray-50 text-gray-300 rounded-2xl flex items-center justify-center mb-4 sm:mb-5 rotate-3 hover:rotate-0 transition-transform">
                        <svg class="size-8 sm:size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">No se encontraron oficinas</h3>
                    <p class="text-gray-500 max-w-sm mx-auto text-xs sm:text-sm">
                        {{ searchQuery ? 'No hay resultados que coincidan con tu búsqueda actual.' : 'Aún no se han registrado oficinas para esta unidad.' }}
                    </p>
                    <button v-if="searchQuery" @click="searchQuery = ''" class="mt-4 sm:mt-6 inline-flex items-center gap-2 text-white bg-ugel-azul hover:bg-ugel-guinda px-4 py-2 rounded-full text-xs sm:text-sm font-semibold transition-colors">
                        Limpiar búsqueda
                    </button>
                </div>
                
            </div>
        </section>

        <ModalEditar
            :show="showEditModal"
            :oficina="oficinaEditando"
            :areas="areas"
            :loading="saving"
            @close="cerrarModalEditar"
            @save="guardarCambios"
        />

        <ModalCrear
            ref="modalCrearRef"
            :show="showCreateModal"
            :areas="areas"
            :default-area-id="activeArea !== 'todos' ? activeArea : ''"
            :loading="creating"
            @close="cerrarModalCrear"
            @save="crearOficina"
        />
    </AppLayout>
</template>
