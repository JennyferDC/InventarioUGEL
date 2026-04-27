<script setup>
import { ref, watch, computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import QrcodeVue from "qrcode.vue";
import ModalFichaTecnica from "./Partials/ModalFichaTecnica.vue";
import axios from "axios";

const props = defineProps({
    equipo: {
        type: Object,
        required: true,
    },
    otrosEquipos: {
        type: Array,
        default: () => [],
    },
    personas: {
        type: Array,
        required: true,
    },
    areas: {
        type: Array,
        required: true,
    },
});

const TIPOS_EQUIPO = [
    "PC",
    "LAPTOP",
    "TODO EN UNO",
    "COMPONENTE",
    "TECLADO",
    "MOUSE",
    "OTRO",
    "MONITOR",
];

const ESTADOS_EQUIPO = ["LIBRE", "EN USO", "BAJA"];

const form = useForm({
    id: props.equipo.id,
    cod_informatica: props.equipo.cod_informatica,
    tipo: props.equipo.tipo,
    estado: props.equipo.estado,
    fecha_ingreso: props.equipo.fecha_ingreso,
    fecha_disponible_uso: props.equipo.fecha_disponible_uso,
    vida_util_anios: props.equipo.vida_util_anios,
    id_persona: props.equipo.id_persona ?? "",
    caracteristicas: Array.isArray(props.equipo.caracteristicas)
        ? props.equipo.caracteristicas.map((c) => ({ ...c }))
        : [],
});

const showSuccess = ref(false);
const showModalFicha = ref(false);
const linkCopiado = ref(false);

const searchPersona = ref("");
const showPersonaDropdown = ref(false);

const normalizeText = (text) => text.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();

const filteredPersonas = computed(() => {
    const q = normalizeText(searchPersona.value);
    if (!q) return props.personas.slice(0, 50);
    return props.personas.filter(p => {
        const text = normalizeText(`${p.nombre_completo} ${p.oficina?.nombre || ''} ${p.oficina?.area?.nombre || ''}`);
        return text.includes(q);
    }).slice(0, 50);
});

const selectPersona = (p) => {
    if (p) {
        form.id_persona = p.id;
        searchPersona.value = p.nombre_completo;
    } else {
        form.id_persona = "";
        searchPersona.value = "";
    }
    showPersonaDropdown.value = false;
};

const handlePersonaBlur = () => {
    setTimeout(() => {
        showPersonaDropdown.value = false;
    }, 200);
};

watch(() => form.id_persona, (val) => {
    if (val) {
        const p = props.personas.find(x => x.id === val);
        if (p) {
            searchPersona.value = p.nombre_completo;
        }
    } else {
        searchPersona.value = "";
    }
}, { immediate: true });

const copiarLink = () => {
    navigator.clipboard.writeText(currentUrl.value);
    linkCopiado.value = true;
    setTimeout(() => {
        linkCopiado.value = false;
    }, 2000);
};

const downloadReporteAxios = async (tipo_reporte, filtros) => {
    try {
        const response = await axios.post(route('reportes.equipos.pdf'), {
            tipo_reporte,
            filtros
        }, {
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        
        let filename = 'reporte.pdf';
        const disposition = response.headers['content-disposition'];
        if (disposition && disposition.indexOf('attachment') !== -1) {
            const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            const matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) { 
                filename = matches[1].replace(/['"]/g, '');
            }
        }
        
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error("Error downloading PDF", error);
    }
};

const handleDownloadFicha = (opciones) => {
    downloadReporteAxios('ficha_tecnica', {
        equipo_id: form.id,
        incluir_datos_responsable: opciones.incluir_datos_responsable ? 1 : 0,
        incluir_otros_equipos: opciones.incluir_otros_equipos ? 1 : 0,
    });
};

const handleDownloadPersona = () => {
    if (!props.equipo.persona) return;
    downloadReporteAxios('inventario_persona', {
        persona_id: props.equipo.persona.id
    });
};

const handleSubmit = () => {
    form.put(route("equipos.update", form.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess.value = true;
            setTimeout(() => {
                showSuccess.value = false;
            }, 3000);
        },
    });
};

const agregarCaracteristica = () => {
    form.caracteristicas.push({ clave: "", valor: "" });
};

const quitarCaracteristica = (index) => {
    form.caracteristicas.splice(index, 1);
};

const currentUrl = computed(() => window.location.href);

const getEstadoClass = (estado) => {
    switch (estado) {
        case "LIBRE":
            return "bg-green-100 text-green-800";
        case "EN USO":
            return "bg-blue-100 text-blue-800";
        case "BAJA":
            return "bg-red-100 text-red-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};

const downloadQr = () => {
    const canvas = document.querySelector("#qr-container canvas");
    if (canvas) {
        const link = document.createElement("a");
        link.download = `QR-${form.cod_informatica}.png`;
        link.href = canvas.toDataURL("image/png");
        link.click();
    }
};
</script>

<template>
    <AppLayout title="Detalle del Equipo">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('equipos.index')"
                        class="rounded-full p-2 hover:bg-gray-200 transition text-gray-600"
                    >
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-ugel-guinda">
                        Detalles del equipo: {{ form.cod_informatica }}
                    </h2>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
                        @click="showModalFicha = true"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar ficha técnica
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing || !form.isDirty"
                        class="inline-flex items-center gap-2 rounded-lg bg-ugel-azul px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-ugel-guinda disabled:opacity-50 disabled:bg-gray-400 transition-all duration-200"
                        @click="handleSubmit"
                    >
                        <svg v-if="form.processing" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Guardar cambios
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Notificación de éxito -->
                <transition
                    enter-active-class="transform transition duration-300 ease-out"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showSuccess" class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    El equipo ha sido actualizado correctamente.
                                </p>
                            </div>
                        </div>
                    </div>
                </transition>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Lado izquierdo: Formulario de edición (similar al ModalEditar) -->
                    <div class="lg:col-span-2 bg-white shadow-xl rounded-2xl overflow-hidden">
                        <div class="border-b border-ugel-azul/10 px-6 py-5 bg-gray-50">
                            <h3 class="text-lg font-bold text-ugel-azul">Información y Edición</h3>
                            <p class="mt-1 text-sm text-gray-500">Modifica los detalles del equipo aquí.</p>
                        </div>
                        <div class="px-6 py-6">
                            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-600 border border-red-200">
                                Revisa los campos para corregir los errores.
                            </div>

                            <form @submit.prevent="handleSubmit" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Código informatica -->
                                    <div>
                                        <label for="equipo_cod" class="block text-sm font-medium text-gray-700">Código informática <span class="text-red-500">*</span></label>
                                        <input
                                            id="equipo_cod"
                                            v-model="form.cod_informatica"
                                            type="text"
                                            required
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        />
                                        <div v-if="form.errors.cod_informatica" class="mt-1 text-xs text-red-500">{{ form.errors.cod_informatica }}</div>
                                    </div>

                                    <!-- Tipo -->
                                    <div>
                                        <label for="equipo_tipo" class="block text-sm font-medium text-gray-700">Tipo de equipo <span class="text-red-500">*</span></label>
                                        <select
                                            id="equipo_tipo"
                                            v-model="form.tipo"
                                            required
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        >
                                            <option value="" disabled>Seleccione un tipo</option>
                                            <option v-for="t in TIPOS_EQUIPO" :key="t" :value="t">{{ t }}</option>
                                        </select>
                                        <div v-if="form.errors.tipo" class="mt-1 text-xs text-red-500">{{ form.errors.tipo }}</div>
                                    </div>

                                    <!-- Estado -->
                                    <div>
                                        <label for="equipo_estado" class="block text-sm font-medium text-gray-700">Estado <span class="text-red-500">*</span></label>
                                        <select
                                            id="equipo_estado"
                                            v-model="form.estado"
                                            required
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        >
                                            <option value="" disabled>Seleccione estado</option>
                                            <option v-for="e in ESTADOS_EQUIPO" :key="e" :value="e">{{ e }}</option>
                                        </select>
                                        <div v-if="form.errors.estado" class="mt-1 text-xs text-red-500">{{ form.errors.estado }}</div>
                                    </div>

                                    <!-- Vida útil -->
                                    <div>
                                        <label for="vida_util" class="block text-sm font-medium text-gray-700">Vida útil (años)</label>
                                        <input
                                            id="vida_util"
                                            v-model="form.vida_util_anios"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        />
                                    </div>

                                    <!-- Fechas -->
                                    <div>
                                        <label for="fecha_ingreso" class="block text-sm font-medium text-gray-700">Fecha de ingreso</label>
                                        <p class="text-[11px] text-gray-500 mb-1">Es cuando el área de informática recibe el equipo</p>
                                        <input
                                            id="fecha_ingreso"
                                            v-model="form.fecha_ingreso"
                                            type="date"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        />
                                    </div>
                                    <div>
                                        <label for="fecha_disp" class="block text-sm font-medium text-gray-700">Disponible desde</label>
                                        <p class="text-[11px] text-gray-500 mb-1">Es cuando se comienza a utilizar el equipo</p>
                                        <input
                                            id="fecha_disp"
                                            v-model="form.fecha_disponible_uso"
                                            type="date"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        />
                                    </div>

                                    <!-- Responsable -->
                                    <div class="md:col-span-2 relative">
                                        <label for="search_persona" class="block text-sm font-medium text-gray-700 mb-1">Responsable (Persona asignada)</label>
                                        <input
                                            id="search_persona"
                                            v-model="searchPersona"
                                            type="text"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                            placeholder="Buscar persona por nombre..."
                                            @focus="showPersonaDropdown = true"
                                            @blur="handlePersonaBlur"
                                        />
                                        <div
                                            v-if="showPersonaDropdown"
                                            class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                                        >
                                            <div
                                                class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                @click="selectPersona(null)"
                                            >
                                                -- Sin asignar --
                                            </div>
                                            <div
                                                v-for="p in filteredPersonas"
                                                :key="p.id"
                                                class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                @click="selectPersona(p)"
                                            >
                                                <div class="font-medium">{{ p.nombre_completo }}</div>
                                                <div class="text-xs text-gray-500" v-if="p.oficina">{{ p.oficina.nombre }} {{ p.oficina.area ? ' - ' + p.oficina.area.nombre : '' }}</div>
                                            </div>
                                            <div v-if="filteredPersonas.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                                No se encontraron resultados
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Características -->
                                <div class="mt-8 border-t border-gray-100 pt-6">
                                    <div class="mb-4 flex items-start justify-between">
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900">Características / Detalles</h4>
                                            <p class="text-[11px] text-gray-500 mt-0.5">Agrega detalles del equipo como color, modelo, RAM, procesador, etc.</p>
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-md bg-ugel-azul/10 px-2.5 py-1.5 text-xs font-semibold text-ugel-azul hover:bg-ugel-azul/20"
                                            @click="agregarCaracteristica"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Agregar
                                        </button>
                                    </div>

                                    <div v-if="form.caracteristicas.length === 0" class="rounded-lg border border-dashed border-gray-300 py-6 text-center text-sm text-gray-500">
                                        No hay características adicionales.
                                    </div>

                                    <div v-else class="space-y-3">
                                        <div
                                            v-for="(car, index) in form.caracteristicas"
                                            :key="index"
                                            class="flex items-start gap-3 rounded-lg bg-gray-50 p-3"
                                        >
                                            <div class="flex-1">
                                                <input
                                                    v-model="car.clave"
                                                    type="text"
                                                    placeholder="Ej: Procesador, Marca..."
                                                    class="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>
                                            <div class="flex-1">
                                                <input
                                                    v-model="car.valor"
                                                    type="text"
                                                    placeholder="Ej: Intel Core i7, HP..."
                                                    class="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>
                                            <button
                                                type="button"
                                                class="rounded-md p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700"
                                                @click="quitarCaracteristica(index)"
                                            >
                                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>


                            </form>
                        </div>
                    </div>

                    <!-- Lado derecho: QR y Equipos del responsable -->
                    <div class="space-y-6">
                        
                        <!-- Tarjeta QR -->
                        <div class="bg-white shadow-xl rounded-2xl overflow-hidden flex flex-col items-center justify-center p-8 text-center border-t-4 border-ugel-guinda">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Código QR del Equipo</h3>
                            <p class="text-sm text-gray-500 mb-6">
                                Escanea este código para acceder rápidamente a esta ficha.
                            </p>
                            <div id="qr-container" class="bg-white p-4 rounded-xl shadow-inner border border-gray-100 inline-block">
                                <qrcode-vue :value="currentUrl" :size="180" level="M" render-as="canvas" />
                            </div>
                            <div class="mt-4 flex items-center justify-center gap-2 mb-6">
                                <div class="text-xs font-mono bg-gray-100 text-gray-600 px-3 py-1.5 rounded-md">
                                    {{ form.cod_informatica }}
                                </div>
                                <button
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-md transition border border-transparent"
                                    :class="linkCopiado ? 'bg-green-100 text-green-700' : 'text-ugel-azul bg-ugel-azul/10 hover:bg-ugel-azul/20'"
                                    @click="copiarLink"
                                    title="Copiar enlace"
                                >
                                    {{ linkCopiado ? 'Enlace copiado' : 'Copiar enlace' }}
                                </button>
                            </div>

                            <button
                                type="button"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-ugel-guinda px-4 py-2 text-sm font-semibold text-ugel-guinda hover:bg-ugel-guinda hover:text-white transition"
                                @click="downloadQr"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descargar QR
                            </button>
                        </div>

                        <!-- Equipos del Responsable -->
                        <div v-if="equipo.persona" class="bg-white shadow-xl rounded-2xl overflow-hidden">
                            <div class="border-b border-gray-100 px-6 py-4 bg-gray-50 flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-bold text-gray-900">
                                            {{ equipo.persona.nombre_completo }}
                                        </h3>
                                        <span :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide',
                                            equipo.persona.estado === 'ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                        ]">
                                            {{ equipo.persona.estado || 'ACTIVO' }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 flex flex-wrap items-center gap-3 mt-1">
                                        <span v-if="equipo.persona.oficina?.area" class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                            {{ equipo.persona.oficina.area.nombre }}
                                        </span>
                                        <span v-if="equipo.persona.celular" class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ equipo.persona.celular }}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="p-1.5 text-ugel-azul hover:bg-ugel-azul/10 rounded-md transition border border-transparent hover:border-ugel-azul/20"
                                    title="Descargar PDF de equipos"
                                    @click="handleDownloadPersona"
                                >
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </button>
                            </div>
                            <div class="px-6 py-3 bg-gray-50/50 border-b border-gray-100">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Otros dispositivos asignados</h4>
                            </div>
                            <div class="p-0">
                                <ul v-if="otrosEquipos.length > 0" class="divide-y divide-gray-100">
                                    <li v-for="otro in otrosEquipos" :key="otro.id" class="px-6 py-4 hover:bg-gray-50 transition cursor-pointer group/item">
                                        <Link :href="route('equipos.showByCodigo', otro.cod_informatica)" class="flex justify-between items-center group/link w-full">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800 group-hover/item:text-ugel-azul group-hover/item:underline transition">{{ otro.cod_informatica }}</p>
                                                <p class="text-xs text-gray-500">{{ otro.tipo }}</p>
                                            </div>
                                            <span
                                                :class="[
                                                    'px-2 py-0.5 rounded-full text-xs font-medium',
                                                    getEstadoClass(otro.estado)
                                                ]"
                                            >
                                                {{ otro.estado }}
                                            </span>
                                        </Link>
                                    </li>
                                </ul>
                                <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                                    No tiene más equipos asignados.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <ModalFichaTecnica 
            :show="showModalFicha" 
            :equipo-id="equipo.id" 
            @close="showModalFicha = false" 
            @download="handleDownloadFicha" 
        />
    </AppLayout>
</template>
