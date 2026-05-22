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

// Dismissible alerts list
const localAlerts = ref([...(props.alertas || [])]);
const showNotificationsDropdown = ref(false);

const dismissAlert = (id) => {
    localAlerts.value = localAlerts.value.filter((alert) => alert.id !== id);
};

// Percentages for states
const totalStates = computed(() => {
    return (props.metrics.equipos_en_uso || 0) + (props.metrics.equipos_libres || 0) + (props.metrics.equipos_baja || 0) || 1;
});
const pctEnUso = computed(() => Math.round(((props.metrics.equipos_en_uso || 0) / totalStates.value) * 100));
const pctLibre = computed(() => Math.round(((props.metrics.equipos_libres || 0) / totalStates.value) * 100));
const pctBaja = computed(() => Math.round(((props.metrics.equipos_baja || 0) / totalStates.value) * 100));

// Percentages for classifications
const totalClasificados = computed(() => {
    return (props.distribucion_clasificacion.BUENO || 0) +
        (props.distribucion_clasificacion.REGULAR || 0) +
        (props.distribucion_clasificacion.MALO || 0) || 1;
});
const pctBueno = computed(() => Math.round(((props.distribucion_clasificacion.BUENO || 0) / totalClasificados.value) * 100));
const pctRegular = computed(() => Math.round(((props.distribucion_clasificacion.REGULAR || 0) / totalClasificados.value) * 100));
const pctMalo = computed(() => Math.round(((props.distribucion_clasificacion.MALO || 0) / totalClasificados.value) * 100));

// Current date formatted beautifully
const currentDateString = computed(() => {
    const date = new Date();
    const formatted = date.toLocaleDateString("es-ES", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
    // Capitalize first letter of weekday and month
    return formatted.replace(/^\w/, (c) => c.toUpperCase());
});

// Computed segments for SVG Donut Chart
const donutSegments = computed(() => {
    const enUso = props.metrics.equipos_en_uso || 0;
    const libres = props.metrics.equipos_libres || 0;
    const baja = props.metrics.equipos_baja || 0;
    const total = enUso + libres + baja || 1;
    
    const categories = [
        { 
            label: "En Uso", 
            value: enUso, 
            color: "#2563EB", // Solid vibrant blue
        },
        { 
            label: "Disponibles", 
            value: libres, 
            color: "#10B981", // Solid vibrant green
        },
        { 
            label: "En baja", 
            value: baja, 
            color: "#EF4444", // Solid vibrant red
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

// Friendly Spanish time difference for activity logs
const getTimeAgo = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) {
        return diffMins <= 5 ? "Hace instantes" : `Hace ${diffMins} minutos`;
    } else if (diffHours < 24) {
        return diffHours === 1 ? "Hace 1 hora" : `Hace ${diffHours} horas`;
    } else {
        return diffDays === 1 ? "Hace 1 día" : `Hace ${diffDays} días`;
    }
};

// Activity feed icon custom mapper
const getActivityIconInfo = (item) => {
    const desc = item.descripcion ? item.descripcion.toLowerCase() : "";
    
    if (desc.includes("baja")) {
        return {
            bg: "bg-[#FEF2F2]",
            color: "text-red-500",
            icon: "wrench"
        };
    } else if (desc.includes("mantenimiento") || desc.includes("soporte")) {
        return {
            bg: "bg-[#ECFDF5]",
            color: "text-emerald-500",
            icon: "gear"
        };
    } else if (desc.includes("programa") || desc.includes("instalado") || desc.includes("software")) {
        return {
            bg: "bg-[#FAF5FF]",
            color: "text-purple-500",
            icon: "box"
        };
    } else if (desc.includes("colaborador") || desc.includes("persona") || desc.includes("miembro")) {
        return {
            bg: "bg-[#ECFDF5]",
            color: "text-emerald-500",
            icon: "user"
        };
    } else {
        return {
            bg: "bg-[#EFF6FF]",
            color: "text-blue-500",
            icon: "desktop"
        };
    }
};

// Date range formatter in Spanish
const formatDateRange = (startStr, endStr) => {
    if (!startStr || !endStr) return "";
    const start = new Date(startStr);
    const end = new Date(endStr);
    const options = { day: "numeric", month: "short" };
    return `${start.toLocaleDateString("es-ES", options)} - ${end.toLocaleDateString("es-ES", options)}`;
};
</script>

<template>
    <AppLayout title="Dashboard de Inventario">
        <!-- Top Bar customized exactly to the image -->
        <template #header>
            <div class="flex items-center justify-between w-full py-1">
                <!-- Right: Date pill and Notification bell dropdown -->
                <div class="flex items-center gap-4 ml-auto">
                    <!-- Calendar pill -->
                    <div class="flex items-center gap-2.5 px-4 py-2 border border-slate-200/80 bg-white rounded-xl shadow-sm text-slate-700 font-bold text-xs sm:text-sm">
                        <svg class="size-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ currentDateString }}</span>
                    </div>
                    
                    <!-- Notification bell with Dropdown Wrapper -->
                    <div class="relative">
                        <button 
                            @click="showNotificationsDropdown = !showNotificationsDropdown"
                            class="relative flex items-center justify-center size-10 rounded-full border border-slate-200/80 bg-white shadow-sm text-slate-600 hover:text-blue-600 hover:border-blue-200 transition cursor-pointer focus:outline-none"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span v-if="localAlerts.length > 0" class="absolute -top-1 -right-1 flex items-center justify-center size-5 bg-red-500 text-[10px] font-black text-white rounded-full ring-2 ring-white animate-pulse">
                                {{ localAlerts.length }}
                            </span>
                        </button>

                        <!-- Click-shield transparent overlay -->
                        <div v-if="showNotificationsDropdown" class="fixed inset-0 z-40 cursor-default" @click="showNotificationsDropdown = false"></div>

                        <!-- Dropdown Menu -->
                        <div 
                            v-if="showNotificationsDropdown" 
                            class="absolute right-0 mt-2.5 w-80 sm:w-96 bg-white border border-slate-100 rounded-2xl shadow-xl z-50 overflow-hidden divide-y divide-slate-50 transition duration-200"
                        >
                            <div class="px-4 py-3 bg-slate-50/50 flex items-center justify-between">
                                <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Alertas y Notificaciones</span>
                                <span class="bg-blue-100 text-blue-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full">{{ localAlerts.length }} activas</span>
                            </div>
                            
                            <div class="max-h-[300px] overflow-y-auto divide-y divide-slate-50">
                                <div v-if="localAlerts.length === 0" class="p-8 text-center text-xs font-semibold text-slate-400">
                                    No tienes notificaciones pendientes.
                                </div>
                                
                                <div 
                                    v-for="alert in localAlerts" 
                                    :key="alert.id"
                                    class="p-4 flex items-start justify-between gap-3 hover:bg-slate-50/50 transition duration-150"
                                >
                                    <div class="flex items-start gap-3">
                                        <span class="shrink-0 mt-0.5" :class="alert.tipo === 'danger' ? 'text-red-500' : (alert.tipo === 'warning' ? 'text-amber-500' : 'text-blue-500')">
                                            <svg v-if="alert.tipo === 'danger' || alert.tipo === 'warning'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                        <p class="text-xs font-bold text-slate-600 leading-relaxed text-left">
                                            {{ alert.mensaje }}
                                        </p>
                                    </div>
                                    <button 
                                        @click="dismissAlert(alert.id)"
                                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 shrink-0 transition focus:outline-none"
                                        title="Descartar"
                                    >
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Main Body -->
        <div class="py-6 bg-[#f4f7fe] min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- 1. WELCOME BANNER WITH ILLUSTRATION -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#1E40AF] via-[#1D4ED8] to-[#3B82F6] p-8 text-white shadow-md border border-blue-500/10 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="relative z-10 space-y-3 max-w-xl text-left">
                        <h2 class="text-3xl font-extrabold tracking-tight">
                            ¡Bienvenido al<br>Sistema de Inventario UGEL!
                        </h2>
                        <p class="text-sm text-blue-100 font-medium leading-relaxed max-w-lg">
                            Gestiona y supervisa los equipos, programas y mantenimientos de la UGEL de forma rápida, eficiente y segura.
                        </p>
                    </div>
                    
                    <!-- High Fidelity Custom Graphic SVG -->
                    <div class="w-full md:w-1/3 shrink-0 flex justify-end">
                        <svg viewBox="0 0 380 200" class="w-full max-w-[280px] drop-shadow-2xl" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Laptop stand -->
                            <path d="M175 160 L205 160 L200 135 L180 135 Z" fill="#93C5FD" opacity="0.6"/>
                            <rect x="145" y="160" width="90" height="6" rx="3" fill="#E2E8F0" opacity="0.9"/>
                            <!-- Monitor Screen -->
                            <rect x="90" y="30" width="200" height="110" rx="10" fill="#1E293B" stroke="#FFFFFF" stroke-width="5"/>
                            <rect x="96" y="36" width="188" height="88" rx="5" fill="#0F172A"/>
                            
                            <!-- 3D Box in the Screen -->
                            <g transform="translate(162, 50)">
                                <path d="M28 0 L56 14 L28 28 L0 14 Z" fill="#60A5FA"/>
                                <path d="M0 14 L28 28 L28 54 L0 40 Z" fill="#2563EB"/>
                                <path d="M28 28 L56 14 L56 40 L28 54 Z" fill="#1D4ED8"/>
                                <path d="M28 18 L42 11" stroke="#93C5FD" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M28 18 L14 11" stroke="#93C5FD" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M28 18 L28 40" stroke="#93C5FD" stroke-width="1.5" stroke-linecap="round"/>
                            </g>

                            <!-- Checklist Card Floating (Left) -->
                            <g transform="translate(35, 55)">
                                <rect x="0" y="0" width="75" height="95" rx="12" fill="#FFFFFF" class="drop-shadow-lg"/>
                                <rect x="12" y="12" width="30" height="6" rx="3" fill="#E2E8F0"/>
                                
                                <!-- Check 1 -->
                                <circle cx="18" cy="38" r="7" fill="#E8F5E9"/>
                                <path d="M15 38 L17 40 L21 36" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <rect x="30" y="35" width="32" height="5" rx="2.5" fill="#E2E8F0"/>
                                
                                <!-- Check 2 -->
                                <circle cx="18" cy="56" r="7" fill="#E8F5E9"/>
                                <path d="M15 56 L17 58 L21 54" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <rect x="30" y="53" width="32" height="5" rx="2.5" fill="#E2E8F0"/>

                                <!-- Check 3 -->
                                <circle cx="18" cy="74" r="7" fill="#E8F5E9"/>
                                <path d="M15 74 L17 76 L21 72" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <rect x="30" y="71" width="22" height="5" rx="2.5" fill="#E2E8F0"/>
                            </g>

                            <!-- Pie chart (Right Top) -->
                            <g transform="translate(300, 25)">
                                <circle cx="28" cy="28" r="28" fill="#F59E0B"/>
                                <path d="M28 28 L28 0 A28 28 0 0 1 56 28 Z" fill="#3B82F6"/>
                                <circle cx="28" cy="28" r="10" fill="#1D4ED8"/>
                            </g>

                            <!-- Gear (Right Bottom) -->
                            <g transform="translate(305, 115)">
                                <circle cx="18" cy="18" r="9" fill="#94A3B8"/>
                                <path d="M18 4 L18 8 M18 28 L18 32 M4 18 L8 18 M28 18 L32 18 M8 8 L11 11 M25 25 L28 28 M8 28 L11 25 M25 8 L28 11" stroke="#F1F5F9" stroke-width="2.5" stroke-linecap="round"/>
                                <circle cx="18" cy="18" r="4" fill="#0F172A"/>
                            </g>
                        </svg>
                    </div>
                </div>

                <!-- 3. KPI METRICS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Equipos card -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Equipos Físicos</span>
                                <span class="text-4xl font-extrabold text-slate-800 block">{{ metrics.total_equipos }}</span>
                            </div>
                            <span class="size-12 rounded-full bg-[#EFF6FF] text-[#2563EB] flex items-center justify-center shrink-0">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-[10px] font-bold mt-4 pt-3.5 border-t border-slate-50 text-slate-500">
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-600"></span>{{ metrics.equipos_en_uso }} en uso</span>
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ metrics.equipos_libres }} libres</span>
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>{{ metrics.equipos_baja }} bajas</span>
                        </div>
                    </div>

                    <!-- Programas card -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Programas y Software</span>
                                <span class="text-4xl font-extrabold text-slate-800 block">{{ metrics.total_programas }}</span>
                            </div>
                            <span class="size-12 rounded-full bg-[#FAF5FF] text-[#9333EA] flex items-center justify-center shrink-0">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold mt-4 pt-3.5 border-t border-slate-50 text-[#4F46E5]">
                            <svg class="size-3.5 text-[#4F46E5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Programas para equipos</span>
                        </div>
                    </div>

                    <!-- Personas card -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Colaboradores</span>
                                <span class="text-4xl font-extrabold text-slate-800 block">{{ metrics.total_personas }}</span>
                            </div>
                            <span class="size-12 rounded-full bg-[#FFFBEB] text-[#D97706] flex items-center justify-center shrink-0">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold mt-4 pt-3.5 border-t border-slate-50 text-[#B45309]">
                            <svg class="size-3.5 text-[#B45309]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>Responsables de equipos en oficinas</span>
                        </div>
                    </div>

                    <!-- Mantenimientos card -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 relative overflow-hidden group hover:shadow-md transition duration-300">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Mantenimientos</span>
                                <span class="text-4xl font-extrabold text-slate-800 block">{{ metrics.mantenimientos_pendientes }}</span>
                            </div>
                            <span class="size-12 rounded-full bg-[#ECFDF5] text-[#059669] flex items-center justify-center shrink-0">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-bold mt-4 pt-3.5 border-t border-slate-50">
                            <span class="px-2 py-0.5 rounded-full bg-[#FFFBEB] text-[#D97706]">{{ metrics.mantenimientos_pendientes }} pendientes</span>
                            <span class="text-slate-200 font-semibold">|</span>
                            <span class="px-2 py-0.5 rounded-full bg-[#ECFDF5] text-[#059669]">{{ metrics.mantenimientos_realizados }} realizados</span>
                        </div>
                    </div>
                </div>

                <!-- 4. VISUAL CHARTS ROW -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: Device operational states -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-800">Estado de los Dispositivos</h3>
                                    <p class="text-xs text-slate-400 font-semibold mt-0.5">Resumen del estado operacional de los equipos físicos.</p>
                                </div>
                                <Link :href="route('equipos.index')" class="px-3.5 py-1.5 bg-[#EFF6FF] text-[#2563EB] hover:bg-blue-100 text-xs font-bold rounded-xl transition duration-300">
                                    Ver detalle
                                </Link>
                            </div>
                            
                            <!-- Donut graph & list -->
                            <div class="flex flex-col sm:flex-row items-center justify-around gap-6 mt-8">
                                <div class="relative flex items-center justify-center shrink-0">
                                    <svg viewBox="0 0 100 100" class="size-44 transform -rotate-90">
                                        <circle cx="50" cy="50" r="30" fill="transparent" stroke="#f1f5f9" stroke-width="10"/>
                                        <circle
                                            v-for="(seg, idx) in donutSegments"
                                            :key="idx"
                                            cx="50"
                                            cy="50"
                                            r="30"
                                            fill="transparent"
                                            :stroke="seg.color"
                                            stroke-width="10"
                                            :stroke-dasharray="seg.dashArray"
                                            :stroke-dashoffset="seg.dashOffset"
                                            stroke-linecap="round"
                                            class="transition-all duration-500"
                                        />
                                    </svg>
                                    <div class="absolute text-center">
                                        <span class="text-3xl font-black text-slate-800 block leading-none">{{ metrics.total_equipos }}</span>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mt-0.5">Equipos</span>
                                    </div>
                                </div>

                                <!-- Legend items stylized exactly as image -->
                                <div class="flex flex-col gap-2 w-full max-w-[220px]">
                                    <div class="flex items-center justify-between p-3 bg-white border border-slate-100/80 rounded-2xl shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#2563EB]"></span>
                                            <span class="text-xs font-extrabold text-slate-600">En uso</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-xs">
                                            <span class="font-extrabold text-[#2563EB]">{{ metrics.equipos_en_uso }}</span>
                                            <span class="text-slate-400 font-bold">({{ pctEnUso }}%)</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-white border border-slate-100/80 rounded-2xl shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#10B981]"></span>
                                            <span class="text-xs font-extrabold text-slate-600">Disponibles</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-xs">
                                            <span class="font-extrabold text-[#10B981]">{{ metrics.equipos_libres }}</span>
                                            <span class="text-slate-400 font-bold">({{ pctLibre }}%)</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-white border border-slate-100/80 rounded-2xl shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#EF4444]"></span>
                                            <span class="text-xs font-extrabold text-slate-600">En baja</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-xs">
                                            <span class="font-extrabold text-[#EF4444]">{{ metrics.equipos_baja }}</span>
                                            <span class="text-slate-400 font-bold">({{ pctBaja }}%)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Physical quality stats -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 flex flex-col justify-between h-full">
                        <div class="flex-1 flex flex-col justify-between h-full">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-800">Estado Físico de Conservación</h3>
                                <p class="text-xs text-slate-400 font-semibold mt-0.5">Clasificación de calidad de los equipos registrados.</p>
                            </div>
                            
                            <div class="space-y-7 mt-6">
                                <!-- BUENO -->
                                <div class="flex items-center justify-between gap-4">
                                    <div class="w-20 shrink-0 flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#10B981]"></span>
                                        <span class="text-[10px] font-extrabold text-slate-600 tracking-wider">BUENO</span>
                                    </div>
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div :style="{ width: pctBueno + '%' }" class="h-full bg-[#10B981] rounded-full transition-all duration-500"></div>
                                    </div>
                                    <div class="w-32 text-right shrink-0 text-xs font-extrabold text-slate-700">
                                        {{ distribucion_clasificacion.BUENO || 0 }} equipos ({{ pctBueno }}%)
                                    </div>
                                </div>

                                <!-- REGULAR -->
                                <div class="flex items-center justify-between gap-4">
                                    <div class="w-20 shrink-0 flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#F59E0B]"></span>
                                        <span class="text-[10px] font-extrabold text-slate-600 tracking-wider">REGULAR</span>
                                    </div>
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div :style="{ width: pctRegular + '%' }" class="h-full bg-[#F59E0B] rounded-full transition-all duration-500"></div>
                                    </div>
                                    <div class="w-32 text-right shrink-0 text-xs font-extrabold text-slate-700">
                                        {{ distribucion_clasificacion.REGULAR || 0 }} equipos ({{ pctRegular }}%)
                                    </div>
                                </div>

                                <!-- MALO -->
                                <div class="flex items-center justify-between gap-4">
                                    <div class="w-20 shrink-0 flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#EF4444]"></span>
                                        <span class="text-[10px] font-extrabold text-slate-600 tracking-wider">MALO</span>
                                    </div>
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div :style="{ width: pctMalo + '%' }" class="h-full bg-[#EF4444] rounded-full transition-all duration-500"></div>
                                    </div>
                                    <div class="w-32 text-right shrink-0 text-xs font-extrabold text-slate-700">
                                        {{ distribucion_clasificacion.MALO || 0 }} equipos ({{ pctMalo }}%)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. OFFICES & QUICK ACCESSIBILITY -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: Offices list -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 flex flex-col justify-between h-full">
                        <div class="flex-1 flex flex-col justify-between h-full">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-800">Oficinas Mayor Equipadas</h3>
                                <p class="text-xs text-slate-400 font-semibold mt-0.5">Top 5 oficinas con mayor concentración de equipos.</p>
                            </div>
                            
                            <div class="space-y-4 mt-6" v-if="distribucion_oficinas.length > 0">
                                <div v-for="(item, index) in distribucion_oficinas.slice(0, 5)" :key="index" class="flex items-start gap-3">
                                    <span class="size-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500 shrink-0 mt-0.5">
                                        {{ index + 1 }}
                                    </span>
                                    <div class="flex-1 space-y-1.5">
                                        <div class="flex items-center justify-between text-xs font-extrabold text-slate-700">
                                            <span>{{ item.oficina }}</span>
                                            <span class="text-slate-500">{{ item.total }} eq.</span>
                                        </div>
                                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div 
                                                :style="{ width: Math.min(100, (item.total / (distribucion_oficinas[0]?.total || 1)) * 100) + '%' }" 
                                                class="h-full bg-[#8B5CF6] rounded-full transition-all duration-500"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 text-xs font-semibold text-slate-400">
                                No hay asignaciones registradas para oficinas en este momento.
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-50 text-center">
                            <Link :href="route('oficinas.index')" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition inline-flex items-center gap-1">
                                <span>Ver oficinas y personal asignado</span>
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <!-- Right: Quick actions buttons -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 flex flex-col justify-between h-full">
                        <div class="flex-1 flex flex-col justify-between h-full">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-800">Accesos Rápidos</h3>
                                <p class="text-xs text-slate-400 font-semibold mt-0.5">Accede de forma directa a las principales herramientas de administración.</p>
                            </div>
                            
                            <!-- Grid matches picture exactly -->
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-6">
                                <Link :href="route('equipos.index')" class="flex flex-col items-center justify-center p-3.5 rounded-2xl border border-slate-100 bg-[#F8FAFC]/50 hover:bg-[#EFF6FF] hover:border-blue-200 transition duration-300 group shadow-sm">
                                    <span class="rounded-2xl bg-[#EFF6FF] p-3.5 text-[#2563EB] group-hover:scale-110 transition duration-300">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span class="text-[9px] font-black text-slate-600 mt-2.5 block text-center uppercase tracking-wide">Ver Equipos</span>
                                </Link>

                                <Link :href="route('programas.index')" class="flex flex-col items-center justify-center p-3.5 rounded-2xl border border-slate-100 bg-[#F8FAFC]/50 hover:bg-[#FAF5FF] hover:border-purple-200 transition duration-300 group shadow-sm">
                                    <span class="rounded-2xl bg-[#FAF5FF] p-3.5 text-[#9333EA] group-hover:scale-110 transition duration-300">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </span>
                                    <span class="text-[9px] font-black text-slate-600 mt-2.5 block text-center uppercase tracking-wide">Ver Programas</span>
                                </Link>

                                <Link :href="route('mantenimiento.index')" class="flex flex-col items-center justify-center p-3.5 rounded-2xl border border-slate-100 bg-[#F8FAFC]/50 hover:bg-[#ECFDF5] hover:border-emerald-200 transition duration-300 group shadow-sm">
                                    <span class="rounded-2xl bg-[#ECFDF5] p-3.5 text-[#10B981] group-hover:scale-110 transition duration-300">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span class="text-[9px] font-black text-slate-600 mt-2.5 block text-center uppercase tracking-wide">Mantenimientos</span>
                                </Link>

                                <Link :href="route('personas.index')" class="flex flex-col items-center justify-center p-3.5 rounded-2xl border border-slate-100 bg-[#F8FAFC]/50 hover:bg-[#FFFBEB] hover:border-amber-200 transition duration-300 group shadow-sm">
                                    <span class="rounded-2xl bg-[#FFFBEB] p-3.5 text-[#D97706] group-hover:scale-110 transition duration-300">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </span>
                                    <span class="text-[9px] font-black text-slate-600 mt-2.5 block text-center uppercase tracking-wide">Personas</span>
                                </Link>

                                <Link :href="route('miembros.index')" class="flex flex-col items-center justify-center p-3.5 rounded-2xl border border-slate-100 bg-[#F8FAFC]/50 hover:bg-[#F5F3FF] hover:border-indigo-200 transition duration-300 group shadow-sm">
                                    <span class="rounded-2xl bg-[#F5F3FF] p-3.5 text-[#6366F1] group-hover:scale-110 transition duration-300">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </span>
                                    <span class="text-[9px] font-black text-slate-600 mt-2.5 block text-center uppercase tracking-wide">Miembros</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. AUDIT & COMING UP TASKS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-8">
                    <!-- Left: Recent logs -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 flex flex-col justify-between h-full">
                        <div class="flex-1 flex flex-col justify-between h-full">
                            <div>
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-base font-extrabold text-slate-800">Actividad y Cambios Recientes</h3>
                                        <p class="text-xs text-slate-400 font-semibold mt-0.5">Últimos movimientos registrados en el historial del inventario.</p>
                                    </div>
                                    <Link :href="route('equipos.index')" class="px-3.5 py-1.5 bg-[#EFF6FF] text-[#2563EB] hover:bg-blue-100 text-xs font-bold rounded-xl transition duration-300">
                                        Ver todo
                                    </Link>
                                </div>
                                
                                <!-- Logs layout matching exact picture styling with scroll and clickability -->
                                <div class="max-h-[300px] overflow-y-auto pr-1 space-y-2.5 mt-6" v-if="movimientos_recientes.length > 0">
                                    <component
                                        :is="item.equipo ? Link : 'div'"
                                        :href="item.equipo ? route('equipos.showByCodigo', item.equipo.cod_informatica) : null"
                                        v-for="item in movimientos_recientes" 
                                        :key="item.id" 
                                        class="flex items-center justify-between gap-4 py-1.5 border-b border-slate-50 last:border-0 transition duration-150"
                                        :class="item.equipo ? 'hover:bg-slate-50/80 hover:scale-[1.01] hover:shadow-sm cursor-pointer rounded-xl px-2 -mx-2' : ''"
                                    >
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <span :class="[getActivityIconInfo(item).bg, getActivityIconInfo(item).color]" class="size-10 rounded-2xl flex items-center justify-center shrink-0 shadow-sm border border-slate-100/50">
                                                <!-- Desktop -->
                                                <svg v-if="getActivityIconInfo(item).icon === 'desktop'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <!-- Package / Box -->
                                                <svg v-else-if="getActivityIconInfo(item).icon === 'box'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-14L4 7m8 4v10m0-10L4 7v10l8 4" />
                                                </svg>
                                                <!-- Gear / Cog -->
                                                <svg v-else-if="getActivityIconInfo(item).icon === 'gear'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                                <!-- Wrench -->
                                                <svg v-else-if="getActivityIconInfo(item).icon === 'wrench'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <!-- Person user -->
                                                <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <span class="text-xs font-bold text-slate-700 block truncate">
                                                    {{ item.descripcion }}
                                                </span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-400 shrink-0">
                                            {{ getTimeAgo(item.fecha_hora) }}
                                        </span>
                                    </component>
                                </div>
                                <div v-else class="text-center py-12 text-xs font-semibold text-slate-400">
                                    No se registran actividades recientes en el inventario.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Coming up technical maintenance activities -->
                    <div class="bg-white shadow-sm border border-slate-100 rounded-3xl p-6 flex flex-col justify-between h-full">
                        <div class="flex-1 flex flex-col justify-between h-full">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-800">Próximos Mantenimientos</h3>
                                <p class="text-xs text-slate-400 font-semibold mt-0.5">Actividades preventivas programadas.</p>
                            </div>
                            
                            <!-- Calendar list premium display -->
                            <div class="max-h-[300px] overflow-y-auto pr-1 space-y-3 mt-6" v-if="proximos_mantenimientos.length > 0">
                                <div 
                                    v-for="item in proximos_mantenimientos" 
                                    :key="item.id" 
                                    class="p-4 bg-[#F8FAFC]/50 hover:bg-[#EFF6FF] border border-slate-100 hover:border-blue-100 rounded-2xl flex items-start justify-between gap-4 transition duration-300 shadow-sm"
                                >
                                    <div class="space-y-1.5 text-left min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <!-- Status dot indicator -->
                                            <span class="relative flex h-2 w-2 shrink-0">
                                                <span 
                                                    v-if="item.estado === 'En curso'"
                                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"
                                                ></span>
                                                <span 
                                                    class="relative inline-flex rounded-full h-2 w-2"
                                                    :class="item.estado === 'En curso' ? 'bg-red-500' : 'bg-amber-500'"
                                                ></span>
                                            </span>
                                            
                                            <!-- Status text badge -->
                                            <span 
                                                class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border"
                                                :class="item.estado === 'En curso' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-amber-50 text-amber-600 border-amber-100'"
                                            >
                                                {{ item.estado }}
                                            </span>
                                        </div>

                                        <span class="text-xs font-black text-slate-700 block truncate">
                                            {{ item.oficina }}
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-400 block truncate">
                                            {{ item.area }}
                                        </span>
                                        <p class="text-xs font-semibold text-slate-500 leading-relaxed mt-1 block">
                                            {{ item.actividad }}
                                        </p>
                                        <div class="text-[10px] font-bold text-slate-400 mt-1 flex items-center gap-1">
                                            <svg class="size-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>{{ formatDateRange(item.fecha_inicio, item.fecha_fin) }}</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-extrabold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg shrink-0">
                                        {{ item.dias }} {{ item.dias === 1 ? 'día' : 'días' }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Calendar empty illustration perfectly styled -->
                            <div v-else class="flex flex-col items-center justify-center py-12 px-4">
                                <div class="size-16 rounded-full bg-[#EFF6FF] flex items-center justify-center text-[#2563EB] mb-4 shadow-sm border border-blue-50">
                                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-slate-500 text-center mb-6 leading-relaxed max-w-[220px]">
                                    No hay mantenimientos preventivos pendientes en agenda.
                                </span>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-50 text-center">
                            <Link :href="route('mantenimiento.public')" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition inline-flex items-center gap-1">
                                <span>Ver calendario completo</span>
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
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
/* Disable default focus outline on links */
a:focus {
    outline: none;
}
</style>
