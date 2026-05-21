<script setup>
import { ref, watch, computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import QrcodeVue from "qrcode.vue";
import ModalFichaTecnica from "./Partials/ModalFichaTecnica.vue";
import PersonaCrearModal from "@/Pages/Personas/Partials/ModaCrear.vue";
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

const TIPOS_POR_CATEGORIA = {
    equipo: ["PC", "Laptop", "Todo en uno", "Monitor", "Teclado", "Mouse", "Otro (equipos)"],
    programa: ["Institucional", "Navegador", "Ofimática", "Soporte", "Antivirus", "Otro (programas)"]
};

const tiposDisponibles = computed(() => {
    const cat = (form.categoria || 'equipo').toLowerCase();
    return TIPOS_POR_CATEGORIA[cat] || [];
});

const ESTADOS_EQUIPO = ["LIBRE", "EN USO", "BAJA"];

const form = useForm({
    id: props.equipo.id,
    cod_informatica: props.equipo.cod_informatica,
    cod_patrimonial: props.equipo.cod_patrimonial ?? "",
    nombre: props.equipo.nombre ?? "",
    nombre_usuario: props.equipo.nombre_usuario ?? "",
    tipo: props.equipo.tipo,
    estado: props.equipo.estado,
    fecha_ingreso: props.equipo.fecha_ingreso,
    fecha_disponible_uso: props.equipo.fecha_disponible_uso,
    vida_util_anios: props.equipo.vida_util_anios,
    id_persona: props.equipo.id_persona ?? "",
    observacion_tecnica: props.equipo.observacion_tecnica ?? "",
    categoria: props.equipo.categoria ?? "equipo",
    ip: props.equipo.ip ?? "",
    clasificacion: props.equipo.clasificacion ?? "",
    caracteristicas: Array.isArray(props.equipo.caracteristicas)
        ? props.equipo.caracteristicas.map((c) => ({ ...c }))
        : [],
});

const showSuccess = ref(false);
const showModalFicha = ref(false);
const linkCopiado = ref(false);

const searchPersona = ref("");
const showPersonaDropdown = ref(false);

// Local responsive copy of personas to allow dynamic addition
const localPersonas = ref([...props.personas]);
const oficinas = ref([]);
const loadingOficinas = ref(false);
const showQuickRegister = ref(false);
const savingPersona = ref(false);

const fetchOficinas = async () => {
    if (oficinas.value.length > 0) return;
    loadingOficinas.value = true;
    try {
        const response = await axios.get(route('api.oficinas.index'));
        oficinas.value = response.data.data;
    } catch (error) {
        console.error("Error al obtener oficinas:", error);
    } finally {
        loadingOficinas.value = false;
    }
};

const openQuickRegister = async () => {
    await fetchOficinas();
    showQuickRegister.value = true;
};

const savePersona = async (payload) => {
    savingPersona.value = true;
    try {
        const response = await axios.post(route("personas.store"), payload);
        if (response.data?.data) {
            const nuevaPersona = response.data.data;
            
            // Add new persona to the local reactive list
            localPersonas.value.push(nuevaPersona);
            
            // Auto select the newly created persona
            selectPersona(nuevaPersona);
            
            showQuickRegister.value = false;
        }
    } catch (error) {
        console.error("Error al registrar persona:", error);
        alert(error.response?.data?.message || "No se pudo registrar a la persona.");
    } finally {
        savingPersona.value = false;
    }
};

watch(() => props.personas, (newVal) => {
    localPersonas.value = [...newVal];
}, { deep: true });

const normalizeText = (text) => text.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();

const filteredPersonas = computed(() => {
    const activePersonas = localPersonas.value.filter(p => p.estado === 'ACTIVO' || !p.estado);
    const q = normalizeText(searchPersona.value);
    if (!q) return activePersonas.slice(0, 50);
    return activePersonas.filter(p => {
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
        const p = localPersonas.value.find(x => x.id === val);
        if (p) {
            searchPersona.value = p.nombre_completo;
        }
        if (form.estado !== "BAJA") {
            form.estado = "EN USO";
        }
    } else {
        searchPersona.value = "";
        if (form.estado !== "BAJA") {
            form.estado = "LIBRE";
        }
    }
}, { immediate: true });

watch(() => form.tipo, (newTipo) => {
    if (!['pc', 'laptop', 'todo en uno'].includes((newTipo || '').toLowerCase())) {
        form.ip = "";
    }
});

watch(() => form.estado, (newEstado) => {
    if (newEstado !== 'BAJA') {
        form.observacion_tecnica = "";
    }
});

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
    if (form.categoria === 'programa') {
        form.estado = null;
        form.fecha_ingreso = null;
        form.fecha_disponible_uso = null;
        form.vida_util_anios = null;
        form.id_persona = null;
        form.ip = null;
        form.clasificacion = null;
        form.observacion_tecnica = null;
    }

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

const darDeBaja = () => {
    form.estado = "BAJA";
    form.id_persona = ""; // Liberar equipo
};

const restaurarEquipo = () => {
    form.estado = form.id_persona ? "EN USO" : "LIBRE";
};

const agregarCaracteristica = () => {
    form.caracteristicas.push({ clave: "", valor: "" });
};

const quitarCaracteristica = (index) => {
    form.caracteristicas.splice(index, 1);
};

const loadingIA = ref(false);
const mejorarObservacionConIA = async () => {
    if (!form.observacion_tecnica || !form.observacion_tecnica.trim()) return;

    loadingIA.value = true;
    try {
        const response = await axios.post(route('api.ai.mejorar-observacion'), {
            observacion: form.observacion_tecnica
        });
        if (response.data && response.data.success && response.data.resultado) {
            form.observacion_tecnica = response.data.resultado;
        } else {
            alert('No se pudo mejorar la observación. Inténtelo de nuevo.');
        }
    } catch (error) {
        console.error("Error al mejorar la observación con IA:", error);
        alert(error.response?.data?.message || 'Error al conectar con el servicio de IA. Verifique su conexión o intente más tarde.');
    } finally {
        loadingIA.value = false;
    }
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
                    <h2 class="text-xl font-semibold leading-tight text-ugel-guinda flex items-center gap-2">
                        <span>Detalles del equipo: {{ form.cod_informatica }}</span>
                        <span class="inline-flex items-center rounded-md bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-700 uppercase tracking-wide">
                            {{ form.categoria || 'equipo' }}
                        </span>
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
                                <!-- VISTA PARA PROGRAMAS -->
                                <div v-if="form.categoria === 'programa'" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Código informática (deshabilitado, autogenerado) -->
                                    <div>
                                        <label for="prog_cod" class="block text-sm font-medium text-gray-700">Código informática</label>
                                        <input
                                            id="prog_cod"
                                            v-model="form.cod_informatica"
                                            type="text"
                                            disabled
                                            class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                                        />
                                    </div>

                                    <!-- Nombre del programa -->
                                    <div>
                                        <label for="prog_nombre" class="block text-sm font-medium text-gray-700">Nombre del programa <span class="text-red-500">*</span></label>
                                        <input
                                            id="prog_nombre"
                                            v-model="form.nombre"
                                            type="text"
                                            required
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                            placeholder=""
                                        />
                                        <div v-if="form.errors.nombre" class="mt-1 text-xs text-red-500">{{ form.errors.nombre }}</div>
                                    </div>

                                    <!-- Tipo de programa -->
                                    <div class="md:col-span-2">
                                        <label for="prog_tipo" class="block text-sm font-medium text-gray-700">Tipo de programa <span class="text-red-500">*</span></label>
                                        <select
                                            id="prog_tipo"
                                            v-model="form.tipo"
                                            required
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        >
                                            <option value="" disabled>Seleccione un tipo</option>
                                            <option v-for="t in tiposDisponibles" :key="t" :value="t.toLowerCase()">{{ t }}</option>
                                        </select>
                                        <div v-if="form.errors.tipo" class="mt-1 text-xs text-red-500">{{ form.errors.tipo }}</div>
                                    </div>
                                </div>

                                <!-- VISTA PARA EQUIPOS (SECCIONADA) -->
                                <div v-else class="space-y-6">
                                    <!-- Sección 1: Información General -->
                                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-200/80 space-y-4">
                                        <div class="text-xs font-bold text-ugel-guinda uppercase tracking-wider border-b border-gray-200 pb-2">
                                            1. Información General del Equipo
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Código informática (deshabilitado, autogenerado) -->
                                            <div>
                                                <label for="equipo_cod" class="block text-sm font-medium text-gray-700">Código informática</label>
                                                <input
                                                    id="equipo_cod"
                                                    v-model="form.cod_informatica"
                                                    type="text"
                                                    disabled
                                                    class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                                                />
                                            </div>

                                            <!-- Código patrimonial -->
                                            <div>
                                                <label for="eq_patrimonial" class="block text-sm font-medium text-gray-700">Código patrimonial</label>
                                                <input
                                                    id="eq_patrimonial"
                                                    v-model="form.cod_patrimonial"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="74089900XXXX"
                                                />
                                                <div v-if="form.errors.cod_patrimonial" class="mt-1 text-xs text-red-500">{{ form.errors.cod_patrimonial }}</div>
                                            </div>

                                            <!-- Nombre de equipo -->
                                            <div v-if="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase())">
                                                <label for="eq_nombre" class="block text-sm font-medium text-gray-700">Nombre de equipo</label>
                                                <input
                                                    id="eq_nombre"
                                                    v-model="form.nombre"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="DESKTOP-EAOR902..."
                                                />
                                                <div v-if="form.errors.nombre" class="mt-1 text-xs text-red-500">{{ form.errors.nombre }}</div>
                                            </div>

                                            <!-- Tipo de equipo -->
                                            <div :class="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase()) ? '' : 'md:col-span-2'">
                                                <label for="equipo_tipo" class="block text-sm font-medium text-gray-700">Tipo de equipo <span class="text-red-500">*</span></label>
                                                <select
                                                    id="equipo_tipo"
                                                    v-model="form.tipo"
                                                    required
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                >
                                                    <option value="" disabled>Seleccione un tipo</option>
                                                    <option v-for="t in tiposDisponibles" :key="t" :value="t.toLowerCase()">{{ t }}</option>
                                                </select>
                                                <div v-if="form.errors.tipo" class="mt-1 text-xs text-red-500">{{ form.errors.tipo }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección 2: Estado y Asignación -->
                                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-200/80 space-y-4">
                                        <div class="text-xs font-bold text-ugel-guinda uppercase tracking-wider border-b border-gray-200 pb-2">
                                            2. Estado y Asignación de Responsable
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Estado -->
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-sm font-medium text-gray-700">Estado <span class="text-red-500">*</span></label>
                                                    <button 
                                                        type="button" 
                                                        v-if="form.estado !== 'BAJA'"
                                                        @click="darDeBaja"
                                                        class="text-xs text-red-600 hover:text-red-800 font-semibold"
                                                    >
                                                        Dar de baja
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        v-else
                                                        @click="restaurarEquipo"
                                                        class="text-xs text-green-600 hover:text-green-800 font-semibold"
                                                    >
                                                        Restaurar equipo
                                                    </button>
                                                </div>
                                                <div class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm flex items-center">
                                                    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold border', 
                                                        form.estado === 'LIBRE' ? 'bg-green-100 text-green-700 border-green-200' :
                                                        form.estado === 'EN USO' ? 'bg-blue-100 text-blue-700 border-blue-200' :
                                                        'bg-red-100 text-red-700 border-red-200']">
                                                        {{ form.estado }}
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 mt-1 leading-tight">
                                                    El estado se actualiza automáticamente al asignar o quitar un responsable.
                                                </p>
                                            </div>

                                            <!-- Cuenta -->
                                            <div v-if="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase())">
                                                <label for="eq_usuario" class="block text-sm font-medium text-gray-700">Cuenta</label>
                                                <input
                                                    id="eq_usuario"
                                                    v-model="form.nombre_usuario"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="JUANPEREZ, UHNEXUSB..."
                                                />
                                                <div v-if="form.errors.nombre_usuario" class="mt-1 text-xs text-red-500">{{ form.errors.nombre_usuario }}</div>
                                            </div>

                                            <!-- Responsable -->
                                            <div class="md:col-span-2 relative">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label for="search_persona" class="block text-sm font-medium text-gray-700">Responsable</label>
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1 text-xs font-semibold text-ugel-azul hover:text-ugel-guinda transition"
                                                        @click="openQuickRegister"
                                                        v-if="form.estado !== 'BAJA'"
                                                    >
                                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                        </svg>
                                                        Registro rápido
                                                    </button>
                                                </div>
                                                <input
                                                    id="search_persona"
                                                    v-model="searchPersona"
                                                    type="text"
                                                    autocomplete="off"
                                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul disabled:bg-gray-100 disabled:text-gray-400 cursor-text"
                                                    placeholder="Buscar persona por nombre..."
                                                    @focus="showPersonaDropdown = true"
                                                    @blur="handlePersonaBlur"
                                                    :disabled="form.estado === 'BAJA'"
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
                                                        class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center justify-between"
                                                        @click="selectPersona(p)"
                                                    >
                                                        <div>
                                                            <div class="font-medium">{{ p.nombre_completo }}</div>
                                                            <div class="text-xs text-gray-500" v-if="p.oficina">{{ p.oficina.nombre }} {{ p.oficina.area ? ' - ' + p.oficina.area.nombre : '' }}</div>
                                                        </div>
                                                        <div class="size-2 rounded-full bg-green-500 shrink-0" title="Activo"></div>
                                                    </div>
                                                    <div v-if="filteredPersonas.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                                        No se encontraron resultados
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Observación técnica de baja -->
                                            <div v-if="form.estado === 'BAJA'" class="md:col-span-2">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label for="equipo_observacion" class="block text-sm font-medium text-gray-700">Observación técnica de baja</label>
                                                    <button
                                                        type="button"
                                                        @click="mejorarObservacionConIA"
                                                        :disabled="loadingIA || !form.observacion_tecnica.trim()"
                                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-purple-700 hover:text-purple-900 bg-purple-50 hover:bg-purple-100 px-2.5 py-1 rounded-md border border-purple-200 transition-all duration-200 disabled:opacity-40 disabled:bg-gray-50 disabled:text-gray-400 disabled:border-gray-200 shadow-sm"
                                                        title="Optimizar texto usando IA"
                                                    >
                                                        <svg v-if="loadingIA" class="size-3.5 animate-spin text-purple-600" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <svg v-else class="size-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.187.904zM18 7.5l-.5 2.5-.5-2.5-2.5-.5 2.5-.5.5-2.5.5 2.5 2.5.5-2.5.5zM21 16l-.5 2.5-.5-2.5-2.5-.5 2.5-.5.5-2.5.5 2.5 2.5.5-2.5.5z" />
                                                        </svg>
                                                        <span>{{ loadingIA ? 'Mejorando...' : 'Mejorar con IA' }}</span>
                                                    </button>
                                                </div>
                                                <textarea
                                                    id="equipo_observacion"
                                                    v-model="form.observacion_tecnica"
                                                    rows="3"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="Describa el motivo o estado técnico para dar de baja el equipo..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección 3: Especificaciones y Ciclo de Vida -->
                                    <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-200/80 space-y-4">
                                        <div class="text-xs font-bold text-ugel-guinda uppercase tracking-wider border-b border-gray-200 pb-2">
                                            3. Especificaciones y Ciclo de Vida
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Clasificación -->
                                            <div>
                                                <label for="equipo_clasificacion" class="block text-sm font-medium text-gray-700">Clasificación</label>
                                                <select
                                                    id="equipo_clasificacion"
                                                    v-model="form.clasificacion"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                >
                                                    <option value="">Seleccione clasificación</option>
                                                    <option value="BUENO">Bueno</option>
                                                    <option value="REGULAR">Regular</option>
                                                    <option value="MALO">Malo</option>
                                                </select>
                                            </div>

                                            <!-- Dirección IP -->
                                            <div v-if="['pc', 'laptop', 'todo en uno'].includes((form.tipo || '').toLowerCase())">
                                                <label for="equipo_ip" class="block text-sm font-medium text-gray-700">Dirección IP</label>
                                                <input
                                                    id="equipo_ip"
                                                    v-model="form.ip"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                    placeholder="192.168.1.XX"
                                                />
                                            </div>

                                            <!-- Vida útil (años) -->
                                            <div :class="['pc', 'laptop', 'todo en uno'].includes((form.tipo || '').toLowerCase()) ? 'md:col-span-2' : ''">
                                                <label for="vida_util" class="block text-sm font-medium text-gray-700">Vida útil (años)</label>
                                                <input
                                                    id="vida_util"
                                                    v-model="form.vida_util_anios"
                                                    type="number"
                                                    min="0"
                                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>

                                            <!-- Fecha de ingreso -->
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

                                            <!-- Disponible desde -->
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
                                        </div>
                                    </div>
                                </div>

                                <!-- Características / Detalles (Comunes para Equipos y Programas) -->
                                <div class="mt-8 border-t border-gray-100 pt-6">
                                    <div class="mb-4 flex items-start justify-between">
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900">
                                                {{ form.categoria === 'programa' ? 'Características del programa' : 'Características / Detalles' }}
                                            </h4>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                {{ form.categoria === 'programa' ? 'Agrega detalles del software como versión, tipo de licencia, clave de activación, etc.' : 'Agrega detalles del equipo como color, modelo, RAM, procesador, etc.' }}
                                            </p>
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
                                                    :placeholder="form.categoria === 'programa' ? 'Ej: Versión, Licencia...' : 'Ej: Procesador, Marca...'"
                                                    class="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                                />
                                            </div>
                                            <div class="flex-1">
                                                <input
                                                    v-model="car.valor"
                                                    type="text"
                                                    :placeholder="form.categoria === 'programa' ? 'Ej: 2021, Anual, Key-XXXX...' : 'Ej: Intel Core i7, HP...'"
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
                                    <div v-if="equipo.persona.cargo" class="text-xs text-gray-500 font-medium mb-1.5">{{ equipo.persona.cargo }}</div>
                                    <div class="text-xs text-gray-500 flex flex-wrap items-center gap-3 mt-1">
                                        <span v-if="equipo.persona.oficina?.area" class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                            {{ equipo.persona.oficina.area.nombre }}
                                        </span>
                                        <span v-if="equipo.persona.celular" class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ equipo.persona.celular }}
                                        </span>
                                        <span v-if="equipo.persona.correo" class="flex items-center gap-1.5">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            {{ equipo.persona.correo }}
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
        
        <PersonaCrearModal
            :show="showQuickRegister"
            :oficinas="oficinas"
            :loading="savingPersona"
            @close="showQuickRegister = false"
            @save="savePersona"
        />

        <ModalFichaTecnica 
            :show="showModalFicha" 
            :equipo-id="equipo.id" 
            @close="showModalFicha = false" 
            @download="handleDownloadFicha" 
        />
    </AppLayout>
</template>
