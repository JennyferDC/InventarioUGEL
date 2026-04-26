<script setup>
import { ref, computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
});

const searchQuery = ref("");
const showUpcomingOnly = ref(false);

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const [year, month, day] = dateString.split('-');
    return `${day}/${month}/${year}`;
};

const getDays = (start, end) => {
    if (!start || !end) return 0;
    const s = new Date(start + 'T00:00:00');
    const e = new Date(end + 'T00:00:00');
    const diffTime = Math.abs(e - s);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
    return diffDays + 1;
};

const getStatusColor = (item) => {
    if (!item.fecha_inicio || !item.fecha_fin) return 'bg-gray-400';
    
    const now = new Date();
    now.setHours(0,0,0,0);
    
    const start = new Date(item.fecha_inicio + 'T00:00:00');
    const end = new Date(item.fecha_fin + 'T00:00:00');
    
    if (now >= start && now <= end) {
        // En curso
        return 'bg-red-500';
    } else if (now > end) {
        // Ya pasó
        return 'bg-green-500';
    } else {
        const diffTime = start - now;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        if (diffDays <= 7 && diffDays > 0) {
            // Próximo
            return 'bg-orange-500';
        } else {
            // Falta mucho
            return 'bg-green-500';
        }
    }
};

const planStartDate = computed(() => {
    if (!props.plan.items || props.plan.items.length === 0) return null;
    return props.plan.items.reduce((min, p) => p.fecha_inicio < min ? p.fecha_inicio : min, props.plan.items[0].fecha_inicio);
});

const planEndDate = computed(() => {
    if (!props.plan.items || props.plan.items.length === 0) return null;
    return props.plan.items.reduce((max, p) => p.fecha_fin > max ? p.fecha_fin : max, props.plan.items[0].fecha_fin);
});

const planStatus = computed(() => {
    if (!planStartDate.value || !planEndDate.value) return { text: 'Sin Fechas', color: 'bg-gray-100 text-gray-800 border-gray-200' };
    
    const now = new Date();
    now.setHours(0,0,0,0);
    
    const start = new Date(planStartDate.value + 'T00:00:00');
    const end = new Date(planEndDate.value + 'T00:00:00');
    
    if (now > end) return { text: 'Finalizado', color: 'bg-gray-100 text-gray-800 border-gray-200' };
    if (now < start) return { text: 'Pendiente', color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
    return { text: 'En curso', color: 'bg-green-100 text-green-800 border-green-200' };
});

const filteredItems = computed(() => {
    let items = props.plan.items || [];
    
    if (searchQuery.value) {
        items = items.filter(item => 
            item.oficina?.nombre.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }

    if (showUpcomingOnly.value) {
        const now = new Date();
        now.setHours(0,0,0,0);
        
        items = items.filter(item => {
            const start = new Date(item.fecha_inicio + 'T00:00:00');
            const end = new Date(item.fecha_fin + 'T00:00:00');
            
            if (now >= start && now <= end) return true;
            
            const diffTime = start - now;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            return diffDays > 0 && diffDays <= 15;
        });
        
        items = [...items].sort((a, b) => {
            const d1 = new Date(a.fecha_inicio + 'T00:00:00');
            const d2 = new Date(b.fecha_inicio + 'T00:00:00');
            return d1 - d2;
        });
    }

    return items;
});

// Agrupamos por área
const groupedItems = computed(() => {
    const groups = {};
    filteredItems.value.forEach(item => {
        const areaName = item.oficina?.area?.nombre || 'Sin Área Asignada';
        if (!groups[areaName]) {
            groups[areaName] = [];
        }
        groups[areaName].push(item);
    });
    return groups;
});
</script>

<template>
    <AppLayout :title="`Plan: ${plan.titulo}`">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('mantenimiento.index')" class="text-gray-500 hover:text-ugel-azul transition-colors">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-bold text-3xl text-ugel-guinda leading-tight">
                        {{ plan.titulo }}
                    </h2>
                </div>
            </div>
        </template>

        <section class="py-10 space-y-6">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-6">
                
                <!-- Info Plan -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row gap-6 justify-between items-start">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Detalles del Plan</h3>
                        <p class="text-gray-600">{{ plan.descripcion || 'Sin descripción detallada para este plan de mantenimiento.' }}</p>
                    </div>
                    <div class="flex gap-4 flex-wrap items-start">
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 min-w-[140px]">
                            <div class="text-xs text-gray-500 font-semibold mb-1 uppercase tracking-wider">Fecha Inicio</div>
                            <div class="font-bold text-gray-900">{{ formatDate(planStartDate) }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 min-w-[140px]">
                            <div class="text-xs text-gray-500 font-semibold mb-1 uppercase tracking-wider">Fecha Fin</div>
                            <div class="font-bold text-gray-900">{{ formatDate(planEndDate) }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 min-w-[140px]">
                            <div class="text-xs text-gray-500 font-semibold mb-1 uppercase tracking-wider">Estado Actual</div>
                            <div class="mt-1">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full border" :class="planStatus.color">
                                    {{ planStatus.text }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Items -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col lg:flex-row gap-4 lg:items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h3 class="font-bold text-lg text-gray-900">Cronograma por Áreas y Oficinas</h3>
                            <span class="bg-ugel-azul/10 text-ugel-azul text-xs font-bold px-3 py-1 rounded-full border border-ugel-azul/20">
                                {{ plan.items.length }} Oficinas
                            </span>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                            <!-- Buscador -->
                            <div class="w-full sm:w-64 relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" />
                                    </svg>
                                </span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Buscar por oficina..."
                                    class="w-full rounded-lg border border-gray-300 pl-10 pr-4 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul transition-shadow"
                                />
                            </div>
                            
                            <!-- Actividades proximas toggle -->
                            <button 
                                @click="showUpcomingOnly = !showUpcomingOnly"
                                class="w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-2 border"
                                :class="showUpcomingOnly ? 'bg-orange-50 text-orange-600 border-orange-200 hover:bg-orange-100' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                            >
                                <svg v-if="!showUpcomingOnly" class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                {{ showUpcomingOnly ? 'Ver todas' : 'Actividades próximas' }}
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <template v-if="Object.keys(groupedItems).length > 0">
                            <div v-for="(itemsInArea, areaName) in groupedItems" :key="areaName" class="mb-2">
                                <!-- Cabecera del Área -->
                                <div class="bg-gray-100/50 px-6 py-3 border-y border-gray-100 flex items-center gap-2">
                                    <svg class="size-5 text-ugel-azul" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <h4 class="font-bold text-ugel-azul uppercase text-sm tracking-wide">{{ areaName }}</h4>
                                    <span class="ml-2 bg-white text-gray-500 text-[10px] font-bold px-2 py-0.5 rounded-full border border-gray-200">{{ itemsInArea.length }}</span>
                                </div>
                                
                                <table class="w-full text-left text-sm whitespace-nowrap">
                                    <thead class="bg-white text-gray-400 border-b border-gray-100 text-xs uppercase tracking-wider">
                                        <tr>
                                            <th class="px-6 py-3 font-semibold w-12 text-center">Estado</th>
                                            <th class="px-6 py-3 font-semibold">Oficina</th>
                                            <th class="px-6 py-3 font-semibold">Actividad</th>
                                            <th class="px-6 py-3 font-semibold">Fecha Inicio</th>
                                            <th class="px-6 py-3 font-semibold">Fecha Fin</th>
                                            <th class="px-6 py-3 font-semibold text-center w-24">Días</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 bg-white">
                                        <tr v-for="item in itemsInArea" :key="item.id" class="hover:bg-gray-50/80 transition-colors">
                                            <td class="px-6 py-4 text-center align-middle">
                                                <div class="flex justify-center">
                                                    <div 
                                                        class="size-3.5 rounded-full shadow-sm ring-2 ring-white" 
                                                        :class="getStatusColor(item)" 
                                                        :title="getStatusColor(item) === 'bg-red-500' ? 'En curso' : (getStatusColor(item) === 'bg-orange-500' ? 'Próximo' : (getStatusColor(item) === 'bg-green-500' ? 'A tiempo / Finalizado' : 'Sin fecha'))"
                                                    ></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-gray-900">{{ item.oficina?.nombre || 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-gray-600 truncate max-w-[250px]" :title="item.actividad">{{ item.actividad }}</div>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-700">
                                                {{ formatDate(item.fecha_inicio) }}
                                            </td>
                                            <td class="px-6 py-4 font-medium text-gray-700">
                                                {{ formatDate(item.fecha_fin) }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="inline-flex items-center justify-center bg-gray-100 text-gray-800 font-bold px-2.5 py-1 rounded-md text-xs border border-gray-200">
                                                    {{ getDays(item.fecha_inicio, item.fecha_fin) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <div v-else class="p-16 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="size-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Sin coincidencias</h3>
                                <p class="text-sm font-medium text-gray-500 max-w-sm mx-auto">
                                    No se encontraron oficinas que coincidan con los filtros aplicados.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
