<script setup>
import { ref, reactive, computed, watch } from "vue";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    equipos: {
        type: Array,
        default: () => [],
    },
    areas: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close"]);

const TIPOS_EQUIPO = ["PC", "LAPTOP", "TODO EN UNO", "COMPONENTE", "TECLADO", "MOUSE", "OTRO", "MONITOR"];
const ESTADOS_EQUIPO = ["LIBRE", "EN USO", "BAJA"];

const form = reactive({
    tipoReporte: "inventario_general",
    inventario_general: {
        tipos: [...TIPOS_EQUIPO],
        estados: [...ESTADOS_EQUIPO],
    },
    ficha_tecnica: {
        equipo_id: "",
    },
    historial_ingresos: {
        fecha_inicio: "",
        fecha_fin: "",
    },
    inventario_area: {
        areas: [],
    }
});

const loadingPDF = ref(false);
const loadingExcel = ref(false);

const handleClose = () => {
    emit("close");
};

const handleGenerarPDF = async () => {
    loadingPDF.value = true;
    try {
        const response = await axios.post(
            route("reportes.equipos.pdf"),
            {
                tipo_reporte: form.tipoReporte,
                filtros: form[form.tipoReporte],
            },
            { responseType: "blob" }
        );
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", `reporte_equipos_${new Date().getTime()}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error("Error generando PDF", error);
        alert("Ocurrió un error al generar el PDF.");
    } finally {
        loadingPDF.value = false;
    }
};

const handleGenerarExcel = async () => {
    loadingExcel.value = true;
    try {
        const response = await axios.post(
            route("reportes.equipos.excel"),
            {
                tipo_reporte: form.tipoReporte,
                filtros: form[form.tipoReporte],
            },
            { responseType: "blob" }
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", `reporte_equipos_${new Date().getTime()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error("Error generando EXCEL", error);
        alert("Ocurrió un error al generar el EXCEL.");
    } finally {
        loadingExcel.value = false;
    }
};

const toggleSelectAllTipos = () => {
    if (form.inventario_general.tipos.length === TIPOS_EQUIPO.length) {
        form.inventario_general.tipos = [];
    } else {
        form.inventario_general.tipos = [...TIPOS_EQUIPO];
    }
};

const toggleSelectAllEstados = () => {
    if (form.inventario_general.estados.length === ESTADOS_EQUIPO.length) {
        form.inventario_general.estados = [];
    } else {
        form.inventario_general.estados = [...ESTADOS_EQUIPO];
    }
};

const toggleSelectAllAreas = () => {
    if (form.inventario_area.areas.length === props.areas.length) {
        form.inventario_area.areas = [];
    } else {
        form.inventario_area.areas = props.areas.map(a => a.id);
    }
};

const searchEquipo = ref("");
const showEquipoDropdown = ref(false);

const normalizeText = (text) => {
    return text.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
};

const filteredEquipos = computed(() => {
    const q = normalizeText(searchEquipo.value);
    if (!q) return props.equipos.slice(0, 50);
    return props.equipos.filter(eq => {
        const text = normalizeText(`${eq.cod_informatica} ${eq.tipo} ${eq.persona?.nombre_completo || ''}`);
        return text.includes(q);
    }).slice(0, 50);
});

const selectEquipo = (eq) => {
    if (eq) {
        form.ficha_tecnica.equipo_id = eq.id;
        searchEquipo.value = `${eq.cod_informatica} - ${eq.tipo} ${eq.persona ? '('+eq.persona.nombre_completo+')' : ''}`;
    } else {
        form.ficha_tecnica.equipo_id = "";
        searchEquipo.value = "";
    }
    showEquipoDropdown.value = false;
};

const handleEquipoBlur = () => {
    setTimeout(() => {
        showEquipoDropdown.value = false;
    }, 200);
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            searchEquipo.value = "";
            form.ficha_tecnica.equipo_id = "";
        }
    }
);

const esFormularioValido = computed(() => {
    if (form.tipoReporte === 'inventario_general') {
        return form.inventario_general.tipos.length > 0 && form.inventario_general.estados.length > 0;
    }
    if (form.tipoReporte === 'ficha_tecnica') {
        return !!form.ficha_tecnica.equipo_id;
    }
    if (form.tipoReporte === 'historial_ingresos') {
        return !!form.historial_ingresos.fecha_inicio && !!form.historial_ingresos.fecha_fin;
    }
    if (form.tipoReporte === 'inventario_area') {
        return form.inventario_area.areas.length > 0;
    }
    return false;
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div class="absolute inset-0 bg-black/40" @click="handleClose" />
        </Transition>

        <Transition
            enter-active-class="transform transition duration-200 scale-95 opacity-0"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transform transition duration-200 scale-100 opacity-100"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div class="relative w-full max-w-lg bg-white rounded-xl shadow-xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between border-b border-ugel-azul/10 px-5 py-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-ugel-azul/70">
                            Inventario
                        </div>
                        <div class="text-lg font-bold text-ugel-guinda">
                            Generar Reportes
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                        @click="handleClose"
                    >
                        <svg
                            class="size-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-5 space-y-6 flex flex-col">
                    <div>
                        <label
                            for="tipo_reporte"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Tipo de reporte <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="tipo_reporte"
                            v-model="form.tipoReporte"
                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul transition-colors"
                            :disabled="loadingPDF || loadingExcel"
                        >
                            <option value="inventario_general">Inventario general</option>
                            <option value="ficha_tecnica">Ficha técnica de equipo</option>
                            <option value="historial_ingresos">Historial de ingresos</option>
                            <option value="inventario_area">Inventario de un área</option>
                        </select>
                    </div>

                    <!-- Filtros para "inventario_general" -->
                    <div v-if="form.tipoReporte === 'inventario_general'" class="space-y-4 border border-ugel-azul/10 rounded-lg p-4 bg-gray-50 flex-1">
                        <!-- Tipos -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-700">Filtrar por Tipos:</span>
                                <button type="button" @click="toggleSelectAllTipos" class="text-xs text-ugel-azul hover:underline font-medium" :disabled="loadingPDF || loadingExcel">
                                    {{ form.inventario_general.tipos.length === TIPOS_EQUIPO.length ? 'Desmarcar todos' : 'Seleccionar todos' }}
                                </button>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <label v-for="tipo in TIPOS_EQUIPO" :key="tipo" class="inline-flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" :value="tipo" v-model="form.inventario_general.tipos" class="rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul disabled:opacity-50" :disabled="loadingPDF || loadingExcel">
                                    <span class="text-xs text-gray-700 font-medium">{{ tipo }}</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Estados -->
                        <div class="pt-2 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-700">Filtrar por Estado:</span>
                                <button type="button" @click="toggleSelectAllEstados" class="text-xs text-ugel-azul hover:underline font-medium" :disabled="loadingPDF || loadingExcel">
                                    {{ form.inventario_general.estados.length === ESTADOS_EQUIPO.length ? 'Desmarcar todos' : 'Seleccionar todos' }}
                                </button>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <label v-for="estado in ESTADOS_EQUIPO" :key="estado" class="inline-flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" :value="estado" v-model="form.inventario_general.estados" class="rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul disabled:opacity-50" :disabled="loadingPDF || loadingExcel">
                                    <span class="text-xs text-gray-700 font-medium">{{ estado }}</span>
                                </label>
                            </div>
                        </div>

                        <div v-if="form.inventario_general.tipos.length === 0 || form.inventario_general.estados.length === 0" class="mt-2 text-xs text-red-500 font-medium">
                            Debes seleccionar al menos un tipo y un estado.
                        </div>
                    </div>

                    <!-- Filtros para "ficha_tecnica" -->
                    <div v-if="form.tipoReporte === 'ficha_tecnica'" class="space-y-4 border border-ugel-azul/10 rounded-lg p-4 bg-gray-50 flex-1">
                        <div>
                            <label for="eq_id" class="block text-sm font-semibold text-gray-700">Seleccione un equipo:</label>
                            <div class="relative mt-1">
                                <input
                                    id="eq_id"
                                    v-model="searchEquipo"
                                    type="text"
                                    class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-opacity-50"
                                    placeholder="Buscar por código, tipo o persona..."
                                    @focus="showEquipoDropdown = true"
                                    @blur="handleEquipoBlur"
                                />
                                <div
                                    v-if="showEquipoDropdown"
                                    class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                                >
                                    <div
                                        class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        @click="selectEquipo(null)"
                                    >
                                        -- Seleccionar --
                                    </div>
                                    <div
                                        v-for="eq in filteredEquipos"
                                        :key="eq.id"
                                        class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        @click="selectEquipo(eq)"
                                    >
                                        <div class="font-medium">{{ eq.cod_informatica }} - {{ eq.tipo }}</div>
                                        <div class="text-xs text-gray-500" v-if="eq.persona">{{ eq.persona.nombre_completo }}</div>
                                    </div>
                                    <div v-if="filteredEquipos.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                        No se encontraron resultados
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros para "historial_ingresos" -->
                    <div v-if="form.tipoReporte === 'historial_ingresos'" class="space-y-4 border border-ugel-azul/10 rounded-lg p-4 bg-gray-50 flex-1">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Fecha de Inicio:</label>
                                <input type="date" v-model="form.historial_ingresos.fecha_inicio" class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-opacity-50" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Fecha Fin:</label>
                                <input type="date" v-model="form.historial_ingresos.fecha_fin" class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-opacity-50" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros para "inventario_area" -->
                    <div v-if="form.tipoReporte === 'inventario_area'" class="space-y-4 border border-ugel-azul/10 rounded-lg p-4 bg-gray-50 flex-1">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-700">Áreas a incluir:</span>
                                <button type="button" @click="toggleSelectAllAreas" class="text-xs text-ugel-azul hover:underline font-medium" :disabled="loadingPDF || loadingExcel">
                                    {{ form.inventario_area.areas.length === props.areas.length ? 'Desmarcar todas' : 'Seleccionar todas' }}
                                </button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                                <label v-for="area in props.areas" :key="area.id" class="inline-flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" :value="area.id" v-model="form.inventario_area.areas" class="rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul disabled:opacity-50" :disabled="loadingPDF || loadingExcel">
                                    <span class="text-xs text-gray-700 font-medium">{{ area.nombre }}</span>
                                </label>
                            </div>
                            <div v-if="form.inventario_area.areas.length === 0" class="mt-2 text-xs text-red-500 font-medium">
                                Debes seleccionar al menos un área.
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50/50 p-4 rounded-lg border border-blue-100 flex-none">
                        <h4 class="text-xs font-bold text-ugel-azul mb-1 flex items-center gap-1.5">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Leyenda de reportes
                        </h4>
                        <ul class="text-xs text-gray-600 space-y-1 list-disc pl-5">
                            <li><strong>Inventario General:</strong> Lista todos los equipos filtrados por tipos y estados seleccionados.</li>
                            <li><strong>Ficha de equipo:</strong> Muestra el detalle técnico y configuración de un equipo específico.</li>
                            <li><strong>Historial ingresos:</strong> Equipos que ingresaron en el periodo de fechas indicado.</li>
                            <li><strong>Inventario de un área:</strong> Lista los equipos de los responsables de las áreas indicadas.</li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-ugel-azul/10 px-5 py-4 flex flex-col sm:flex-row items-center justify-end gap-3 bg-gray-50/50">
                    <button
                        type="button"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        @click="handleGenerarPDF"
                        :disabled="loadingPDF || loadingExcel || !esFormularioValido"
                    >
                        <svg
                            v-if="loadingPDF"
                            class="-ms-1 size-4 animate-spin text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        {{ loadingPDF ? 'Generando PDF...' : 'Generar PDF' }}
                    </button>

                    <button
                        type="button"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        @click="handleGenerarExcel"
                        :disabled="loadingPDF || loadingExcel || !esFormularioValido || form.tipoReporte === 'ficha_tecnica'"
                        :title="form.tipoReporte === 'ficha_tecnica' ? 'Ficha técnica requiere formato PDF, Excel no disponible.' : ''"
                    >
                        <svg
                            v-if="loadingExcel"
                            class="-ms-1 size-4 animate-spin text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ loadingExcel ? 'Generando EXCEL...' : 'Generar EXCEL' }}
                    </button>
                    
                    <button
                        type="button"
                        class="w-full sm:w-auto mt-2 sm:mt-0 ml-auto rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-ugel-azul focus:ring-offset-2 disabled:opacity-50"
                        @click="handleClose"
                        :disabled="loadingPDF || loadingExcel"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
