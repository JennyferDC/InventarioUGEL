<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    distribucion_clasificacion: {
        type: Object,
        required: true,
    },
    distribucion_tipos: {
        type: Array,
        default: () => [],
    },
    distribucion_oficinas: {
        type: Array,
        default: () => [],
    },
    movimientos_recientes: {
        type: Array,
        default: () => [],
    },
    proximos_mantenimientos: {
        type: Array,
        default: () => [],
    },
    alertas: {
        type: Array,
        default: () => [],
    },
});

// Format dates in Spanish
const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    return date.toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });
};

// Helper to check for multiple lines in movement log
const getDescripcionLines = (descripcion) => {
    if (!descripcion) return [];
    return descripcion.split("\n").map((l) => l.trim()).filter(Boolean);
};

// Dismissible alerts list
const localAlerts = ref([...(props.alertas || [])]);
const dismissAlert = (id) => {
    localAlerts.value = localAlerts.value.filter((alert) => alert.id !== id);
};

// Percentages for states
const totalStates = props.metrics.equipos_en_uso + props.metrics.equipos_libres + props.metrics.equipos_baja || 1;
const pctEnUso = Math.round((props.metrics.equipos_en_uso / totalStates) * 100);
const pctLibre = Math.round((props.metrics.equipos_libres / totalStates) * 100);
const pctBaja = Math.round((props.metrics.equipos_baja / totalStates) * 100);

// Percentages for classifications
const totalClasificados =
    (props.distribucion_clasificacion.BUENO || 0) +
    (props.distribucion_clasificacion.REGULAR || 0) +
    (props.distribucion_clasificacion.MALO || 0) || 1;
const pctBueno = Math.round(((props.distribucion_clasificacion.BUENO || 0) / totalClasificados) * 100);
const pctRegular = Math.round(((props.distribucion_clasificacion.REGULAR || 0) / totalClasificados) * 100);
const pctMalo = Math.round(((props.distribucion_clasificacion.MALO || 0) / totalClasificados) * 100);

// Simple greeting based on current local hour
const greeting = computed(() => {
    const hours = new Date().getHours();
    if (hours < 12) return "Buenos días";
    if (hours < 19) return "Buenas tardes";
    return "Buenas noches";
});

// Current date formatted beautifully
const currentDateString = computed(() => {
    return new Date().toLocaleDateString("es-ES", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
});

// Computed segments for SVG Donut Chart
const donutSegments = computed(() => {
    const enUso = props.metrics.equipos_en_uso || 0;
    const libres = props.metrics.equipos_libres || 0;
    const baja = props.metrics.equipos_baja || 0;
    const total = enUso + libres + baja;
    
    if (total === 0) return [];
    
    const categories = [
        { 
            label: "En Uso", 
            value: enUso, 
            color: "#93c5fd", // soft pastel blue-300
            strokeClass: "stroke-blue-300",
            bgClass: "bg-blue-50/50 text-blue-800 border-blue-100/40" 
        },
        { 
            label: "Disponibles", 
            value: libres, 
            color: "#86efac", // soft pastel green-300
            strokeClass: "stroke-emerald-300",
            bgClass: "bg-emerald-50/50 text-emerald-800 border-emerald-100/40" 
        },
        { 
            label: "De Baja", 
            value: baja, 
            color: "#fca5a5", // soft pastel red-300
            strokeClass: "stroke-rose-300",
            bgClass: "bg-rose-50/50 text-rose-800 border-rose-100/40" 
        },
    ];
    
    let currentOffset = 0;
    
    return categories.map((cat) => {
        const percentage = (cat.value / total) * 100;
        const dash = (percentage * 188.5) / 100;
        const offset = currentOffset;
        currentOffset -= dash;
        
        return {
            ...cat,
            percentage: Math.round(percentage),
            dashArray: `${dash} 188.5`,
            dashOffset: offset,
        };
    });
});
</script>

<template>
    <AppLayout title="Dashboard de Inventario">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-800 flex items-center gap-2">
                        <span>Panel de Gestión de Inventario</span>
                        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 uppercase tracking-wider border border-indigo-100">
                            Activo
                        </span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Control de equipos informáticos, programas y mantenimiento.</p>
                </div>
                <div class="text-sm font-semibold text-gray-600 bg-white border border-gray-100 shadow-sm rounded-xl px-4 py-2 self-start md:self-auto flex items-center gap-2">
                    <svg class="size-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="capitalize">{{ currentDateString }}</span>
                </div>
            </div>
        </template>
 
        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- 1. GREETING & ALERTS SECTION -->
                <div class="flex flex-col gap-4">
                    <!-- Glassmorphic Welcome Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-50/80 via-purple-50/50 to-sky-50/80 p-6 md:p-8 shadow-sm text-slate-800 border border-indigo-100/50">
                        <div class="absolute right-0 bottom-0 translate-y-8 translate-x-8 opacity-[0.06] pointer-events-none text-indigo-900">
                            <svg class="size-64 fill-current" viewBox="0 0 24 24">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                            </svg>
                        </div>
                        <div class="relative z-10 space-y-2 max-w-2xl">
                            <span class="inline-flex items-center rounded-full bg-indigo-100/80 px-3 py-1 text-xs font-semibold text-indigo-800 border border-indigo-200/40 backdrop-blur-sm">
                                👋 {{ greeting }}
                            </span>
                            <h3 class="text-2xl md:text-3xl font-extrabold tracking-tight text-indigo-950">
                                ¡Bienvenido al Inventario de Informática UGEL!
                            </h3>
                            <p class="text-sm text-slate-600 font-medium leading-relaxed">
                                Desde este panel principal puedes supervisar el estado técnico de los equipos de la UGEL,
                                gestionar el ciclo de vida de los programas y software institucionales, y realizar seguimiento
                                a los cronogramas de mantenimiento preventivo.
                            </p>
                        </div>
                    </div>

                    <!-- Alerts Center -->
                    <transition-group 
                        tag="div" 
                        name="list" 
                        class="space-y-3"
                        v-if="localAlerts.length > 0"
                    >
                        <div 
                            v-for="alert in localAlerts" 
                            :key="alert.id" 
                            class="flex items-start justify-between gap-3 p-4 rounded-xl border transition-all duration-300"
                            :class="[
                                alert.tipo === 'danger' ? 'bg-red-50 text-red-800 border-red-100' :
                                alert.tipo === 'warning' ? 'bg-amber-50 text-amber-800 border-amber-100' :
                                'bg-blue-50 text-blue-800 border-blue-100'
                            ]"
                        >
                            <div class="flex gap-3">
                                <!-- Icon -->
                                <span class="shrink-0 mt-0.5">
                                    <svg v-if="alert.tipo === 'danger'" class="size-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <svg v-else-if="alert.tipo === 'warning'" class="size-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <svg v-else class="size-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <p class="text-sm font-semibold leading-relaxed">{{ alert.mensaje }}</p>
                            </div>
                            <button 
                                type="button" 
                                class="rounded-lg p-1 hover:bg-black/5 transition shrink-0" 
                                @click="dismissAlert(alert.id)"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </transition-group>
                </div>

                <!-- 2. KPI METRICS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- Card 1: Equipos -->
                    <div class="relative overflow-hidden bg-white shadow-md hover:shadow-lg transition-all duration-300 rounded-2xl p-6 border border-gray-100 group">
                        <div class="absolute top-0 left-0 h-1.5 w-full bg-gradient-to-r from-pink-300 to-rose-200"></div>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Equipos Físicos</span>
                                <span class="text-3xl font-extrabold text-gray-800 mt-1 block group-hover:scale-105 transition-transform duration-300 origin-left">
                                    {{ metrics.total_equipos }}
                                </span>
                            </div>
                            <div class="rounded-xl bg-rose-50/80 p-3 text-rose-500 shrink-0 transition-colors group-hover:bg-rose-100/80">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-gray-50 pt-3 flex items-center justify-between text-xs text-gray-500 font-medium">
                            <span class="flex items-center gap-1.5 text-blue-500">
                                <span class="h-2 w-2 rounded-full bg-blue-300"></span>
                                {{ metrics.equipos_en_uso }} uso
                            </span>
                            <span class="flex items-center gap-1.5 text-emerald-500">
                                <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                                {{ metrics.equipos_libres }} libres
                            </span>
                            <span class="flex items-center gap-1.5 text-rose-500">
                                <span class="h-2 w-2 rounded-full bg-rose-300"></span>
                                {{ metrics.equipos_baja }} bajas
                            </span>
                        </div>
                    </div>
 
                    <!-- Card 2: Programas -->
                    <div class="relative overflow-hidden bg-white shadow-md hover:shadow-lg transition-all duration-300 rounded-2xl p-6 border border-gray-100 group">
                        <div class="absolute top-0 left-0 h-1.5 w-full bg-gradient-to-r from-sky-300 to-indigo-200"></div>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Programas y Software</span>
                                <span class="text-3xl font-extrabold text-gray-800 mt-1 block group-hover:scale-105 transition-transform duration-300 origin-left">
                                    {{ metrics.total_programas }}
                                </span>
                            </div>
                            <div class="rounded-xl bg-sky-50/80 p-3 text-sky-500 shrink-0 transition-colors group-hover:bg-sky-100/80">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-gray-50 pt-3 text-xs text-gray-500 font-semibold flex items-center gap-1.5">
                            <svg class="size-3.5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Programas para equipos
                        </div>
                    </div>
 
                    <!-- Card 3: Personas -->
                    <div class="relative overflow-hidden bg-white shadow-md hover:shadow-lg transition-all duration-300 rounded-2xl p-6 border border-gray-100 group">
                        <div class="absolute top-0 left-0 h-1.5 w-full bg-gradient-to-r from-amber-300 to-yellow-200"></div>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Colaboradores</span>
                                <span class="text-3xl font-extrabold text-gray-800 mt-1 block group-hover:scale-105 transition-transform duration-300 origin-left">
                                    {{ metrics.total_personas }}
                                </span>
                            </div>
                            <div class="rounded-xl bg-amber-50/80 p-3 text-amber-500 shrink-0 transition-colors group-hover:bg-amber-100/80">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-gray-50 pt-3 text-xs text-gray-500 font-semibold flex items-center gap-1.5">
                            <svg class="size-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            Responsables de equipos en oficinas
                        </div>
                    </div>
 
                    <!-- Card 4: Mantenimiento -->
                    <div class="relative overflow-hidden bg-white shadow-md hover:shadow-lg transition-all duration-300 rounded-2xl p-6 border border-gray-100 group">
                        <div class="absolute top-0 left-0 h-1.5 w-full bg-gradient-to-r from-emerald-300 to-teal-200"></div>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Mantenimientos</span>
                                <span class="text-3xl font-extrabold text-gray-800 mt-1 block group-hover:scale-105 transition-transform duration-300 origin-left">
                                    {{ metrics.mantenimientos_pendientes }}
                                </span>
                            </div>
                            <div class="rounded-xl bg-emerald-50/80 p-3 text-emerald-500 shrink-0 transition-colors group-hover:bg-emerald-100/80">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-gray-50 pt-3 flex items-center justify-between text-xs text-gray-500 font-medium">
                            <span class="text-amber-500 font-bold">
                                {{ metrics.mantenimientos_pendientes }} pendientes
                            </span>
                            <span class="text-slate-400">|</span>
                            <span class="text-emerald-500 font-semibold">
                                {{ metrics.mantenimientos_realizados }} realizados
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. VISUAL CHARTS & ANALYTICS SECTION -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left Block (2 columns width on lg): Device Status & Quality Breakdown -->
                    <div class="lg:col-span-2 space-y-8">
                        <div class="bg-white shadow-md rounded-2xl overflow-hidden border border-gray-100 flex flex-col">
                            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">Estado de los Dispositivos</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Distribución proporcional de los equipos físicos.</p>
                                </div>
                                <span class="bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full border border-indigo-100">
                                    General
                                </span>
                            </div>
                            <div class="p-6">
                                <!-- Donut Chart and Legend Grid -->
                                <div class="flex flex-col md:flex-row items-center justify-around gap-8 py-2">
                                    <!-- Donut Chart Container -->
                                    <div class="relative flex items-center justify-center shrink-0">
                                        <svg viewBox="0 0 100 100" class="size-48 transform -rotate-90">
                                            <!-- Track/Background Circle -->
                                            <circle
                                                cx="50"
                                                cy="50"
                                                r="30"
                                                fill="transparent"
                                                stroke="#f8fafc"
                                                stroke-width="9"
                                            />
                                            <!-- Dynamic Segments -->
                                            <circle
                                                v-for="(seg, idx) in donutSegments"
                                                :key="idx"
                                                cx="50"
                                                cy="50"
                                                r="30"
                                                fill="transparent"
                                                :class="seg.strokeClass"
                                                stroke-width="9"
                                                :stroke-dasharray="seg.dashArray"
                                                :stroke-dashoffset="seg.dashOffset"
                                                class="transition-all duration-500 ease-out cursor-pointer hover:stroke-[11px]"
                                                :title="seg.label"
                                            />
                                        </svg>
                                        <!-- Inside donut text -->
                                        <div class="absolute text-center">
                                            <span class="text-3xl font-extrabold text-slate-700 block leading-none">
                                                {{ metrics.total_equipos }}
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mt-1">
                                                Equipos
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Legend cards -->
                                    <div class="flex flex-col gap-3 w-full max-w-sm">
                                        <div 
                                            v-for="(seg, idx) in donutSegments" 
                                            :key="idx"
                                            class="flex items-center justify-between p-3.5 rounded-xl border transition-all duration-300 hover:shadow-sm"
                                            :class="seg.bgClass"
                                        >
                                            <div class="flex items-center gap-3">
                                                <span class="size-3 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                                <span class="text-xs font-bold">{{ seg.label }}</span>
                                            </div>
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-base font-extrabold">{{ seg.value }}</span>
                                                <span class="text-[10px] font-semibold opacity-75">({{ seg.percentage }}%)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quality breakdown of physical assets (BUENO, REGULAR, MALO) -->
                        <div class="bg-white shadow-md rounded-2xl overflow-hidden border border-gray-100">
                            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-base font-bold text-gray-900">Estado Físico de Conservación</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Clasificación de calidad de los equipos registrados.</p>
                            </div>
                            <div class="p-6 space-y-5">
                                <!-- Bueno -->
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-xs font-bold text-gray-700">
                                        <span class="flex items-center gap-1.5">
                                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                            BUENO
                                        </span>
                                        <span>{{ distribucion_clasificacion.BUENO || 0 }} equipos ({{ pctBueno }}%)</span>
                                    </div>
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div :style="{ width: pctBueno + '%' }" class="bg-gradient-to-r from-emerald-300 to-emerald-200 h-full rounded-full transition-all duration-500"></div>
                                    </div>
                                </div>

                                <!-- Regular -->
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-xs font-bold text-gray-700">
                                        <span class="flex items-center gap-1.5">
                                            <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                            REGULAR
                                        </span>
                                        <span>{{ distribucion_clasificacion.REGULAR || 0 }} equipos ({{ pctRegular }}%)</span>
                                    </div>
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div :style="{ width: pctRegular + '%' }" class="bg-gradient-to-r from-amber-300 to-yellow-200 h-full rounded-full transition-all duration-500"></div>
                                    </div>
                                </div>

                                <!-- Malo -->
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-xs font-bold text-gray-700">
                                        <span class="flex items-center gap-1.5">
                                            <span class="h-2.5 w-2.5 rounded-full bg-rose-300"></span>
                                            MALO
                                        </span>
                                        <span class="text-rose-500">{{ distribucion_clasificacion.MALO || 0 }} equipos ({{ pctMalo }}%)</span>
                                    </div>
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div :style="{ width: pctMalo + '%' }" class="bg-gradient-to-r from-rose-300 to-rose-200 h-full rounded-full transition-all duration-500"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (1 column width on lg): Top Offices equipped -->
                    <div class="bg-white shadow-md rounded-2xl overflow-hidden border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-base font-bold text-gray-900">Oficinas Mayor Equipadas</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Top 5 oficinas con mayor concentración de equipos.</p>
                            </div>
                            <div class="p-6">
                                <div v-if="distribucion_oficinas.length > 0" class="space-y-5">
                                    <div 
                                        v-for="(item, index) in distribucion_oficinas" 
                                        :key="index"
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <div class="flex items-center gap-3 w-full">
                                            <!-- Ranking number badge -->
                                            <span class="size-6 rounded-lg bg-slate-100 flex items-center justify-center text-xs font-extrabold text-slate-600 shrink-0">
                                                {{ index + 1 }}
                                            </span>
                                            <div class="w-full space-y-1">
                                                <div class="flex justify-between text-xs font-bold text-gray-800">
                                                    <span class="truncate max-w-[130px] sm:max-w-none">{{ item.oficina }}</span>
                                                    <span class="text-slate-600 shrink-0">{{ item.total }} eq.</span>
                                                </div>
                                                <!-- Tiny progress meter -->
                                                <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                                                    <div 
                                                        :style="{ width: Math.min(100, (item.total / (distribucion_oficinas[0]?.total || 1)) * 100) + '%' }" 
                                                        class="bg-gradient-to-r from-purple-300 to-pink-200 h-full rounded-full transition-all duration-500"
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 text-sm text-gray-500">
                                    No hay registros de asignaciones a oficinas.
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-center">
                            <Link 
                                :href="route('personas.index')"
                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center gap-1"
                            >
                                <span>Ver oficinas y personal asignado</span>
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
 
                <!-- 4. QUICK ACTIONS ACTION CENTER -->
                <div class="bg-white shadow-md rounded-2xl overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-sm font-bold text-gray-800">Accesos Rápidos</h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <Link 
                            :href="route('equipos.index')" 
                            class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 hover:border-rose-200 hover:bg-rose-50/30 text-center transition group"
                        >
                            <span class="rounded-full bg-rose-50/80 p-3 text-rose-500 group-hover:scale-110 transition duration-300">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <span class="text-xs font-bold text-gray-700 mt-3 block">Ver Equipos</span>
                        </Link>
 
                        <Link 
                            :href="route('programas.index')" 
                            class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 hover:border-sky-200 hover:bg-sky-50/30 text-center transition group"
                        >
                            <span class="rounded-full bg-sky-50/80 p-3 text-sky-500 group-hover:scale-110 transition duration-300">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                            <span class="text-xs font-bold text-gray-700 mt-3 block">Ver Programas</span>
                        </Link>
 
                        <Link 
                            :href="route('mantenimiento.index')" 
                            class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 hover:border-emerald-200 hover:bg-emerald-50/30 text-center transition group"
                        >
                            <span class="rounded-full bg-emerald-50/80 p-3 text-emerald-500 group-hover:scale-110 transition duration-300">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <span class="text-xs font-bold text-gray-700 mt-3 block">Mantenimientos</span>
                        </Link>

                        <Link 
                            :href="route('personas.index')" 
                            class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/30 text-center transition group"
                        >
                            <span class="rounded-full bg-amber-50/80 p-3 text-amber-500 group-hover:scale-110 transition duration-300">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </span>
                            <span class="text-xs font-bold text-gray-700 mt-3 block">Personas</span>
                        </Link>
 
                        <Link 
                            :href="route('miembros.index')" 
                            class="flex flex-col items-center justify-center p-4 rounded-xl border border-gray-100 hover:border-purple-200 hover:bg-purple-50/30 text-center transition group"
                        >
                            <span class="rounded-full bg-purple-50/80 p-3 text-purple-500 group-hover:scale-110 transition duration-300">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </span>
                            <span class="text-xs font-bold text-gray-700 mt-3 block">Gestionar Miembros</span>
                        </Link>
                    </div>
                </div>
 
                <!-- 5. RECENT ACTIVITY TIMELINE & PENDING TASKS -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-12">
                    
                    <!-- Left: Audit/History log feed (2 columns wide) -->
                    <div class="lg:col-span-2 bg-white shadow-md rounded-2xl overflow-hidden border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">Actividad y Cambios Recientes</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Últimos movimientos registrados en el historial del inventario.</p>
                                </div>
                                <span class="bg-gray-100 text-gray-700 text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full">
                                    Auditoría
                                </span>
                            </div>
                            
                            <div class="p-6">
                                <div v-if="movimientos_recientes.length > 0" class="relative border-l-2 border-gray-100 ml-4 pl-6 space-y-6">
                                    <div v-for="item in movimientos_recientes" :key="item.id" class="relative group">
                                        <!-- Timeline dot -->
                                        <span class="absolute -left-[31px] top-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-white ring-2"
                                              :class="[
                                                  item.tipo_accion === 'CREACION' ? 'ring-emerald-300' : 
                                                  item.tipo_accion === 'ELIMINACION' ? 'ring-rose-300' : 'ring-indigo-300'
                                              ]">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                  :class="[
                                                      item.tipo_accion === 'CREACION' ? 'bg-emerald-300' : 
                                                      item.tipo_accion === 'ELIMINACION' ? 'bg-rose-300' : 'bg-indigo-300'
                                                  ]"></span>
                                        </span>
 
                                        <div class="space-y-1">
                                            <!-- Title / Actions -->
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border leading-none"
                                                      :class="[
                                                          item.tipo_accion === 'CREACION' ? 'bg-emerald-50 text-emerald-700 border-emerald-100/50' : 
                                                          item.tipo_accion === 'ELIMINACION' ? 'bg-rose-50 text-rose-700 border-rose-100/50' : 
                                                          'bg-indigo-50 text-indigo-700 border-indigo-100/50'
                                                      ]">
                                                    {{ item.tipo_accion }}
                                                </span>
                                                <span class="text-xs font-semibold text-gray-400">
                                                    {{ formatDate(item.fecha_hora) }}
                                                </span>
                                            </div>

                                            <!-- Description (with bullet check) -->
                                            <div class="text-xs font-medium text-gray-700 leading-relaxed mt-1">
                                                <!-- List format for multiple lines -->
                                                <div v-if="getDescripcionLines(item.descripcion).length > 1" class="mt-1">
                                                    <ul class="list-disc pl-4 space-y-1 text-gray-600">
                                                        <li v-for="(line, idx) in getDescripcionLines(item.descripcion)" :key="idx">
                                                            {{ line }}
                                                        </li>
                                                    </ul>
                                                </div>
                                                <p v-else class="text-gray-600 whitespace-pre-line leading-relaxed">
                                                    {{ item.descripcion }}
                                                </p>
                                            </div>

                                            <!-- Author metadata -->
                                            <div class="text-[10px] text-gray-400 flex items-center gap-1 pt-1.5">
                                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span>{{ item.usuario?.name || 'Usuario' }} ({{ item.usuario?.email }})</span>
                                                <span v-if="item.equipo">
                                                    | Cód: 
                                                    <Link 
                                                        :href="route('equipos.showByCodigo', item.equipo.cod_informatica)"
                                                        class="text-indigo-600 hover:text-indigo-800 hover:underline font-bold"
                                                    >
                                                        {{ item.equipo.cod_informatica }}
                                                    </Link>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 text-sm text-gray-500">
                                    No hay registros de movimientos recientes.
                                </div>
                            </div>
                        </div>
                        
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-center">
                            <span class="text-xs font-semibold text-gray-400">
                                Los cambios a los equipos se guardan en tiempo real para auditorías técnicas.
                            </span>
                        </div>
                    </div>

                    <!-- Right: Maintenance scheduled tasks (1 column wide) -->
                    <div class="bg-white shadow-md rounded-2xl overflow-hidden border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-base font-bold text-gray-900">Próximos Mantenimientos</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Actividades preventivas programadas.</p>
                            </div>
                            <div class="p-6">
                                <div v-if="proximos_mantenimientos.length > 0" class="space-y-4">
                                    <div 
                                        v-for="item in proximos_mantenimientos" 
                                        :key="item.id"
                                        class="p-4 rounded-xl border border-gray-100 hover:bg-slate-50/80 transition duration-200 space-y-2 group"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 uppercase tracking-wide border border-amber-100">
                                                PENDIENTE
                                            </span>
                                            <span class="text-[11px] font-semibold text-gray-400 flex items-center gap-1">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ item.fecha_realizada }}
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-800 group-hover:text-indigo-600 transition truncate">
                                                {{ item.cronograma?.titulo || 'Mantenimiento' }}
                                            </h4>
                                            <div class="text-[11px] text-gray-500 mt-1 flex items-center justify-between">
                                                <span>Cód. equipo: 
                                                    <Link 
                                                        v-if="item.equipo"
                                                        :href="route('equipos.showByCodigo', item.equipo.cod_informatica)"
                                                        class="text-indigo-600 hover:text-indigo-800 hover:underline font-bold"
                                                    >
                                                        {{ item.equipo.cod_informatica }}
                                                    </Link>
                                                </span>
                                                <span class="uppercase font-semibold">({{ item.equipo?.tipo }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 text-sm text-gray-500">
                                    No hay mantenimientos preventivos pendientes en agenda.
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-center">
                            <Link 
                                :href="route('mantenimiento.index')"
                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center gap-1"
                            >
                                <span>Ver cronograma de mantenimiento completo</span>
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Smooth slide and opacity transitions for alert dismissals */
.list-enter-active,
.list-leave-active {
    transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateY(-15px);
}
</style>
