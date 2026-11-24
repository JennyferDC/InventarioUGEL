<script setup>
import { ref, computed, watch } from "vue";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    plan: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close"]);

const loadingPDF = ref(false);
const selectedOficinas = ref([]);

// Agrupamos las oficinas del plan por Unidad Funcional (Área)
const unidadesFuncionales = computed(() => {
    const map = {};
    (props.plan.items || []).forEach((item) => {
        if (!item.oficina) return;
        const area = item.oficina.area || { id: 0, nombre: "Sin Unidad Funcional" };
        if (!map[area.id]) {
            map[area.id] = {
                id: area.id,
                nombre: area.nombre,
                oficinas: new Map(),
            };
        }
        map[area.id].oficinas.set(item.oficina.id, {
            id: item.oficina.id,
            nombre: item.oficina.nombre,
        });
    });

    return Object.values(map)
        .map((a) => ({
            id: a.id,
            nombre: a.nombre,
            oficinas: Array.from(a.oficinas.values()).sort((x, y) =>
                x.nombre.localeCompare(y.nombre)
            ),
        }))
        .sort((x, y) => x.nombre.localeCompare(y.nombre));
});

// Total de IDs de oficinas disponibles en este plan
const allOficinaIds = computed(() => {
    const ids = [];
    unidadesFuncionales.value.forEach((u) => {
        u.oficinas.forEach((o) => ids.push(o.id));
    });
    return ids;
});

const totalOficinasCount = computed(() => allOficinaIds.value.length);

// Al abrir el modal, seleccionar todas las oficinas por defecto
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            selectedOficinas.value = [...allOficinaIds.value];
        }
    },
    { immediate: true }
);

const handleClose = () => {
    emit("close");
};

// Acciones globales Seleccionar todos y Desmarcar todos
const selectAll = () => {
    selectedOficinas.value = [...allOficinaIds.value];
};

const deselectAll = () => {
    selectedOficinas.value = [];
};

// Lógica de casillas para Unidades Funcionales completas
const isUnitSelected = (unit) => {
    if (!unit.oficinas.length) return false;
    return unit.oficinas.every((o) => selectedOficinas.value.includes(o.id));
};

const isUnitIndeterminate = (unit) => {
    const count = unit.oficinas.filter((o) => selectedOficinas.value.includes(o.id)).length;
    return count > 0 && count < unit.oficinas.length;
};

const getUnitSelectedCount = (unit) => {
    return unit.oficinas.filter((o) => selectedOficinas.value.includes(o.id)).length;
};

const toggleUnit = (unit) => {
    const unitOfficeIds = unit.oficinas.map((o) => o.id);
    const allSelected = unitOfficeIds.every((id) => selectedOficinas.value.includes(id));
    if (allSelected) {
        selectedOficinas.value = selectedOficinas.value.filter(
            (id) => !unitOfficeIds.includes(id)
        );
    } else {
        const set = new Set([...selectedOficinas.value, ...unitOfficeIds]);
        selectedOficinas.value = Array.from(set);
    }
};

const handleGenerarPDF = async () => {
    if (selectedOficinas.value.length === 0) return;

    loadingPDF.value = true;
    try {
        const response = await axios.post(
            route("mantenimiento.pdf", props.plan.id),
            {
                oficinas: selectedOficinas.value,
            },
            { responseType: "blob" }
        );

        const blob = new Blob([response.data], { type: "application/pdf" });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;

        let suffix = "seleccion";
        if (selectedOficinas.value.length === allOficinaIds.value.length) {
            suffix = "general";
        } else if (selectedOficinas.value.length === 1) {
            const of = props.plan.items?.find(
                (i) => i.oficina?.id === selectedOficinas.value[0]
            )?.oficina;
            suffix = of ? of.nombre.toLowerCase().replace(/\s+/g, "_") : "oficina";
        }

        link.setAttribute(
            "download",
            `cronograma_${props.plan.titulo}_${suffix}_${new Date().getTime()}.pdf`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        handleClose();
    } catch (error) {
        console.error("Error generando PDF del cronograma", error);
        alert("Ocurrió un error al generar el PDF del cronograma.");
    } finally {
        loadingPDF.value = false;
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
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

        <!-- Contenedor del Modal -->
        <Transition
            enter-active-class="transform transition duration-200 scale-95 opacity-0"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transform transition duration-200 scale-100 opacity-100"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                class="relative w-full max-w-xl bg-white rounded-xl shadow-xl overflow-hidden flex flex-col max-h-[90vh]"
            >
                <!-- Cabecera del modal -->
                <div class="flex items-center justify-between border-b border-ugel-azul/10 px-5 py-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-ugel-azul/70">
                            Plan de Mantenimiento
                        </div>
                        <div class="text-lg font-bold text-ugel-guinda">
                            Descargar Cronograma PDF
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

                <!-- Cuerpo del modal -->
                <div class="flex-1 overflow-y-auto px-5 py-5 space-y-5 flex flex-col">
                    <!-- Barra de acciones: Seleccionar / Desmarcar todos -->
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <span class="text-sm font-semibold text-gray-800">
                            Unidades Funcionales y Oficinas:
                        </span>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="selectAll"
                                class="text-xs text-ugel-azul hover:underline font-semibold"
                                :disabled="loadingPDF"
                            >
                                Seleccionar todos
                            </button>
                            <span class="text-gray-300">|</span>
                            <button
                                type="button"
                                @click="deselectAll"
                                class="text-xs text-ugel-guinda hover:underline font-semibold"
                                :disabled="loadingPDF"
                            >
                                Desmarcar todos
                            </button>
                        </div>
                    </div>

                    <!-- Lista de Unidades Funcionales con casillas -->
                    <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                        <div
                            v-for="unit in unidadesFuncionales"
                            :key="unit.id"
                            class="border border-ugel-azul/15 rounded-lg p-3 bg-gray-50/70"
                        >
                            <!-- Cabecera de la Unidad Funcional -->
                            <div class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                                <label class="inline-flex items-center space-x-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :checked="isUnitSelected(unit)"
                                        :indeterminate.prop="isUnitIndeterminate(unit)"
                                        @change="toggleUnit(unit)"
                                        class="rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul cursor-pointer size-4"
                                        :disabled="loadingPDF"
                                    />
                                    <span class="text-xs font-bold text-ugel-azul uppercase tracking-wide">
                                        {{ unit.nombre }}
                                    </span>
                                </label>
                                <span
                                    class="text-[11px] bg-white text-gray-600 font-semibold px-2 py-0.5 rounded-full border border-gray-200"
                                >
                                    {{ getUnitSelectedCount(unit) }} / {{ unit.oficinas.length }}
                                </span>
                            </div>

                            <!-- Oficinas de la unidad -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2.5">
                                <label
                                    v-for="oficina in unit.oficinas"
                                    :key="oficina.id"
                                    class="inline-flex items-center space-x-2 cursor-pointer hover:bg-white p-1 rounded transition-colors"
                                >
                                    <input
                                        type="checkbox"
                                        :value="oficina.id"
                                        v-model="selectedOficinas"
                                        class="rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul cursor-pointer size-3.5"
                                        :disabled="loadingPDF"
                                    />
                                    <span class="text-xs text-gray-700 font-medium">
                                        {{ oficina.nombre }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Alerta si no hay selección -->
                    <div
                        v-if="selectedOficinas.length === 0"
                        class="text-xs text-red-600 font-medium bg-red-50 p-2.5 rounded-lg border border-red-200"
                    >
                        Debes seleccionar al menos una oficina para generar el documento PDF.
                    </div>

                    <!-- Cuadro informativo (similar a ModalReportes) -->
                    <div class="bg-blue-50/50 p-4 rounded-lg border border-blue-100 flex-none">
                        <h4 class="text-xs font-bold text-ugel-azul mb-1 flex items-center gap-1.5">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Detalles del cronograma
                        </h4>
                        <ul class="text-xs text-gray-600 space-y-1 list-disc pl-5">
                            <li><strong>Plan:</strong> Plan anual de mantenimiento - {{ plan.titulo }}</li>
                            <li>
                                <strong>Oficinas:</strong> {{ selectedOficinas.length }} de
                                {{ totalOficinasCount }} oficinas seleccionadas.
                            </li>
                            <li>
                                <strong>Contenido del PDF:</strong> El reporte incluirá únicamente las actividades, fechas de inicio y fin, y estados correspondientes a las oficinas seleccionadas.
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Pie del modal -->
                <div
                    class="border-t border-ugel-azul/10 px-5 py-4 flex flex-col sm:flex-row items-center justify-end gap-3 bg-gray-50/50"
                >
                    <button
                        type="button"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        @click="handleGenerarPDF"
                        :disabled="loadingPDF || selectedOficinas.length === 0"
                    >
                        <svg
                            v-if="loadingPDF"
                            class="-ms-1 size-4 animate-spin text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                            />
                        </svg>
                        {{ loadingPDF ? "Generando PDF..." : "Generar PDF" }}
                    </button>

                    <button
                        type="button"
                        class="w-full sm:w-auto mt-2 sm:mt-0 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-ugel-azul focus:ring-offset-2 disabled:opacity-50"
                        @click="handleClose"
                        :disabled="loadingPDF"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
