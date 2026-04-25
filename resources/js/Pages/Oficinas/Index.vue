<script setup>
import { ref, computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

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

const activeArea = ref("todos");
const searchQuery = ref("");
const isDropdownOpen = ref(false);

const selectArea = (areaId) => {
    activeArea.value = areaId;
    isDropdownOpen.value = false;
};

const getAreaName = computed(() => {
    if (activeArea.value === "todos") return "Todas las áreas";
    const area = props.areas.find(a => a.id === activeArea.value);
    return area ? area.nombre : "Todas las áreas";
});

const getCountForArea = (areaId) => {
    if (areaId === "todos") return props.oficinas.length;
    return props.oficinas.filter(o => o.area_id === areaId).length;
};

const filteredOficinas = computed(() => {
    return props.oficinas.filter(oficina => {
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
            <h2 class="font-bold text-3xl text-ugel-guinda leading-tight">
                Directorio de Oficinas
            </h2>
        </template>

        <section class="py-10 space-y-6">
            <div class="max-w-6xl mx-auto px-6 lg:px-0 space-y-6">
                <!-- Filtros y Buscador (Misma estructura que Personas/Index.vue) -->
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center w-full">
                        
                        <!-- Buscador -->
                        <div class="w-full sm:w-72 relative">
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
                                class="w-full rounded-lg border border-ugel-azul/30 pl-10 pr-4 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul transition-shadow"
                            />
                        </div>

                        <!-- Select Personalizado de Áreas -->
                        <div class="relative w-full sm:w-72">
                            <!-- Overlay para cerrar clickeando fuera -->
                            <div v-if="isDropdownOpen" @click="isDropdownOpen = false" class="fixed inset-0 z-10"></div>
                            
                            <div class="relative z-20">
                                <button 
                                    @click="isDropdownOpen = !isDropdownOpen"
                                    type="button" 
                                    class="w-full flex items-center justify-between rounded-lg border border-ugel-azul/30 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul transition-all"
                                >
                                    <span class="truncate">{{ getAreaName }}</span>
                                    <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <ul 
                                    v-if="isDropdownOpen"
                                    class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                >
                                    <li 
                                        @click="selectArea('todos')"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-ugel-azul/5 flex items-center justify-between transition-colors"
                                        :class="activeArea === 'todos' ? 'bg-ugel-azul/10 text-ugel-azul font-bold' : 'text-gray-900'"
                                    >
                                        <span class="block truncate">Todas las áreas</span>
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
                </div>

                <!-- Grid de Oficinas -->
                <div v-if="filteredOficinas.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-12">
                    <div 
                        v-for="oficina in filteredOficinas" 
                        :key="oficina.id"
                        class="group flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden relative"
                    >
                        <div class="h-1.5 w-full bg-gradient-to-r from-ugel-azul to-ugel-guinda opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <div class="p-6 flex-1 flex flex-col relative z-10">
                            <div class="flex items-start justify-between mb-5">
                                <div class="flex items-center justify-center size-12 rounded-xl bg-blue-50/80 text-ugel-azul group-hover:bg-ugel-azul group-hover:text-white transition-all duration-300 shadow-inner">
                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <!-- Botón Personas -->
                                <Link :href="route('personas.index', { oficina: oficina.id })" class="text-ugel-azul hover:text-ugel-guinda text-xs font-bold px-2 py-1 rounded-md hover:bg-gray-50 transition-colors flex items-center gap-1">
                                    Ver personal
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-ugel-azul transition-colors line-clamp-2 leading-tight">
                                {{ oficina.nombre }}
                            </h3>
                            
                            <p class="text-sm text-gray-500 line-clamp-3 mb-4 flex-1">
                                {{ oficina.descripcion || 'Sin descripción disponible para esta oficina.' }}
                            </p>
                            
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
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No se encontraron oficinas</h3>
                    <p class="text-gray-500 max-w-sm mx-auto text-sm">
                        {{ searchQuery ? 'No hay resultados que coincidan con tu búsqueda actual.' : 'Aún no se han registrado oficinas para esta área administrativa.' }}
                    </p>
                    <button v-if="searchQuery" @click="searchQuery = ''" class="mt-6 inline-flex items-center gap-2 text-white bg-ugel-azul hover:bg-ugel-guinda px-4 py-2 rounded-full text-sm font-semibold transition-colors">
                        Limpiar búsqueda
                    </button>
                </div>
                
            </div>
        </section>
    </AppLayout>
</template>
