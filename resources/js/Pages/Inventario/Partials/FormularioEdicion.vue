<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";
import PersonaCrearModal from "@/Pages/Personas/Partials/ModaCrear.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    personas: {
        type: Array,
        required: true,
    },
    tiposDisponibles: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(["submit"]);

// Autocomplete custody search
const searchPersona = ref("");
const showPersonaDropdown = ref(false);
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
        props.form.id_persona = p.id;
        searchPersona.value = p.nombre_completo;
    } else {
        props.form.id_persona = "";
        searchPersona.value = "";
    }
    showPersonaDropdown.value = false;
};

const handlePersonaBlur = () => {
    setTimeout(() => {
        showPersonaDropdown.value = false;
    }, 200);
};

// Watches to automatically manage form status and dynamic rules
watch(() => props.form.id_persona, (val) => {
    if (val) {
        const p = localPersonas.value.find(x => x.id === val);
        if (p) {
            searchPersona.value = p.nombre_completo;
        }
        if (props.form.estado !== "BAJA") {
            props.form.estado = "EN USO";
        }
    } else {
        searchPersona.value = "";
        if (props.form.estado !== "BAJA") {
            props.form.estado = "LIBRE";
        }
    }
}, { immediate: true });

watch(() => props.form.tipo, (newTipo) => {
    if (!['pc', 'laptop', 'todo en uno'].includes((newTipo || '').toLowerCase())) {
        props.form.ip = "";
    }
});

watch(() => props.form.estado, (newEstado) => {
    if (newEstado !== 'BAJA') {
        props.form.observacion_tecnica = "";
    }
});

// Observation improver with IA
const loadingIA = ref(false);
const mejorarObservacionConIA = async () => {
    if (!props.form.observacion_tecnica || !props.form.observacion_tecnica.trim()) return;

    loadingIA.value = true;
    try {
        const response = await axios.post(route('api.ai.mejorar-observacion'), {
            observacion: props.form.observacion_tecnica
        });
        if (response.data && response.data.success && response.data.resultado) {
            props.form.observacion_tecnica = response.data.resultado;
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

// Status changers
const darDeBaja = () => {
    props.form.estado = "BAJA";
    props.form.id_persona = ""; // Liberar equipo
};

const restaurarEquipo = () => {
    props.form.estado = props.form.id_persona ? "EN USO" : "LIBRE";
};

// Custom characteristics manager
const agregarCaracteristica = () => {
    props.form.caracteristicas.push({ clave: "", valor: "" });
};

const quitarCaracteristica = (index) => {
    props.form.caracteristicas.splice(index, 1);
};
</script>

<template>
    <div class="lg:col-span-2 bg-white shadow-xl rounded-2xl overflow-hidden">
        <div class="border-b border-ugel-azul/10 px-6 py-5 bg-gray-50">
            <h3 class="text-lg font-bold text-ugel-azul">Información y Edición</h3>
            <p class="mt-1 text-sm text-gray-500">
                Modifica los detalles del {{ form.categoria === 'programa' ? 'programa' : 'equipo' }} aquí.
            </p>
        </div>
        <div class="px-6 py-6">
            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-600 border border-red-200">
                Revisa los campos para corregir los errores.
            </div>

            <form @submit.prevent="emit('submit')" class="space-y-6">
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
                            :disabled="true"
                            class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
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
                                    :disabled="true"
                                    class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
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
                                        class="text-xs text-red-650 hover:text-red-800 font-semibold"
                                    >
                                        Dar de baja
                                    </button>
                                    <button 
                                        type="button" 
                                        v-else
                                        @click="restaurarEquipo"
                                        class="text-xs text-green-600 hover:text-green-800 font-semibold"
                                    >
                                        Restaurar {{ form.categoria === 'programa' ? 'programa' : 'equipo' }}
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
                                <label for="eq_usuario" class="block text-sm font-medium text-gray-700">Cuenta local</label>
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
                                    :placeholder="`Describa el motivo o estado técnico para dar de baja el ${form.categoria === 'programa' ? 'programa' : 'equipo'}...`"
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
                                <p class="text-[11px] text-gray-500 mb-1">Es cuando el área de informática recibe el {{ form.categoria === 'programa' ? 'programa' : 'equipo' }}</p>
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
                                <p class="text-[11px] text-gray-500 mb-1">Es cuando se comienza a utilizar el {{ form.categoria === 'programa' ? 'programa' : 'equipo' }}</p>
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

        <PersonaCrearModal
            :show="showQuickRegister"
            :oficinas="oficinas"
            :loading="savingPersona"
            @close="showQuickRegister = false"
            @save="savePersona"
        />
    </div>
</template>
