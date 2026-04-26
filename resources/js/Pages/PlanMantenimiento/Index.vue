<script setup>
import { ref, computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import ModaCrear from "./Partials/ModaCrear.vue";

const props = defineProps({
    planes: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref("");
const showCreateModal = ref(false);
const isCreating = ref(false);
const createErrors = ref({});

const filteredPlanes = computed(() => {
    return props.planes.filter(plan => {
        const fullTitle = `Plan anual de mantenimiento informático - ${plan.titulo}`;
        const matchesSearch = fullTitle.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              (plan.descripcion && plan.descripcion.toLowerCase().includes(searchQuery.value.toLowerCase()));
        return matchesSearch;
    });
});

const openCreateModal = () => {
    showCreateModal.value = true;
};

const handleSavePlan = (data) => {
    isCreating.value = true;
    createErrors.value = {};
    
    router.post(route('mantenimiento.store'), data, {
        onSuccess: () => {
            showCreateModal.value = false;
        },
        onError: (err) => {
            createErrors.value = err;
        },
        onFinish: () => {
            isCreating.value = false;
        },
        preserveScroll: true
    });
};
</script>

<template>
    <AppLayout title="Plan de Mantenimiento">
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-bold text-3xl text-ugel-guinda leading-tight">
                    Planes de Mantenimiento
                </h2>
            </div>
        </template>

        <section class="py-10 space-y-6">
            <div class="max-w-6xl mx-auto px-6 lg:px-0 space-y-6">
                <!-- Buscador y Botón -->
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="w-full sm:w-72 relative">
                        <label class="sr-only" for="search-plan">Buscar plan</label>
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-ugel-azul/70">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" />
                            </svg>
                        </span>
                        <input
                            id="search-plan"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar plan..."
                            class="w-full rounded-lg border border-ugel-azul/30 pl-10 pr-4 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul transition-shadow"
                        />
                    </div>
                    
                    <button 
                        @click="openCreateModal"
                        class="bg-ugel-azul hover:bg-ugel-guinda text-white px-4 py-2 rounded-lg font-semibold transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo plan
                    </button>
                </div>

                <!-- Grid de Planes -->
                <div v-if="filteredPlanes.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-12">
                    <Link 
                        v-for="plan in filteredPlanes" 
                        :key="plan.id"
                        :href="route('mantenimiento.show', plan.id)"
                        class="group flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden relative block"
                    >
                        <div class="h-1.5 w-full bg-gradient-to-r from-ugel-azul to-ugel-guinda opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <div class="p-6 flex-1 flex flex-col relative z-10">
                            <div class="flex items-start justify-between mb-5">
                                <div class="flex items-center justify-center size-12 rounded-xl bg-blue-50/80 text-ugel-azul group-hover:bg-ugel-azul group-hover:text-white transition-all duration-300 shadow-inner">
                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-ugel-azul group-hover:text-ugel-guinda text-xs font-bold px-2 py-1 rounded-md transition-colors flex items-center gap-1">
                                    Ver plan
                                    <svg class="size-3.5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-ugel-azul transition-colors line-clamp-3 leading-tight">
                                Plan anual de mantenimiento informático - {{ plan.titulo }}
                            </h3>
                            
                            <p class="text-sm text-gray-500 line-clamp-3 mb-4 flex-1">
                                {{ plan.descripcion || 'Sin descripción disponible para este plan.' }}
                            </p>

                            <div class="mt-auto space-y-2 pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 flex items-center gap-1">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        Oficinas
                                    </span>
                                    <span class="font-bold text-gray-900">{{ plan.cantidad_oficinas }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 flex items-center gap-1">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        Inicio
                                    </span>
                                    <span class="font-semibold text-gray-700">{{ plan.fecha_inicio || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 flex items-center gap-1">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        Fin
                                    </span>
                                    <span class="font-semibold text-gray-700">{{ plan.fecha_fin || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
                
                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center py-20 px-4 text-center h-full bg-white rounded-3xl border border-dashed border-gray-300">
                    <div class="size-20 bg-gray-50 text-gray-300 rounded-2xl flex items-center justify-center mb-5 rotate-3 hover:rotate-0 transition-transform">
                        <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No se encontraron planes</h3>
                    <p class="text-gray-500 max-w-sm mx-auto text-sm">
                        {{ searchQuery ? 'No hay resultados que coincidan con tu búsqueda actual.' : 'Aún no se han registrado planes de mantenimiento.' }}
                    </p>
                    <button v-if="searchQuery" @click="searchQuery = ''" class="mt-6 inline-flex items-center gap-2 text-white bg-ugel-azul hover:bg-ugel-guinda px-4 py-2 rounded-full text-sm font-semibold transition-colors">
                        Limpiar búsqueda
                    </button>
                </div>
                
            </div>
        </section>

        <ModaCrear 
            :show="showCreateModal"
            :loading="isCreating"
            :errors="createErrors"
            @close="showCreateModal = false"
            @save="handleSavePlan"
        />
    </AppLayout>
</template>
