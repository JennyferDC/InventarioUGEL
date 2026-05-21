<script setup>
import { reactive, ref, computed, watch } from "vue";
import axios from "axios";
import PersonaCrearModal from "@/Pages/Personas/Partials/ModaCrear.vue";

const CARACTERISTICAS_POR_DEFECTO = [
    "COMPUTADORA",
    "PROCESADOR",
    "RAM",
    "ALMACENAMIENTO",
    "PANTALLA",
    "LAN",
    "USB",
    "VGA",
    "HDMI",
    "SISTEMA OPERATIVO",
    "DESCRIPCIÓN",
    "MARCA",
];

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    personas: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    categoriaInicial: {
        type: String,
        default: "equipo",
    },
});

const emit = defineEmits(["close", "save", "persona-creada"]);

const showQuickRegister = ref(false);
const oficinas = ref([]);
const loadingOficinas = ref(false);
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
            emit("persona-creada", nuevaPersona);
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

const searchPersona = ref("");
const showDropdown = ref(false);

const normalizeText = (text) => text.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();

const filteredPersonas = computed(() => {
    const activePersonas = props.personas.filter(p => p.estado === 'ACTIVO' || !p.estado);
    const q = normalizeText(searchPersona.value);
    if (!q) return activePersonas.slice(0, 50);
    return activePersonas.filter(p => {
        const text = normalizeText(`${p.nombre_completo} ${p.oficina?.nombre || ''} ${p.oficina?.area?.nombre || ''}`);
        return text.includes(q);
    }).slice(0, 50);
});

const selectPersona = (persona) => {
    if (persona) {
        form.id_persona = persona.id;
        searchPersona.value = persona.nombre_completo;
    } else {
        form.id_persona = "";
        searchPersona.value = "";
    }
    showDropdown.value = false;
};

const handleBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
};

const form = reactive({
    cod_patrimonial: "",
    nombre: "",
    nombre_usuario: "",
    tipo: "",
    estado: "LIBRE",
    fecha_ingreso: new Date().toISOString().split('T')[0],
    fecha_disponible_uso: "",
    vida_util_anios: "",
    id_persona: "",
    caracteristicas: [],
    observacion_tecnica: "",
    categoria: "equipo",
    ip: "",
    clasificacion: "",
});

const resetForm = () => {
    form.cod_patrimonial = "";
    form.nombre = "";
    form.nombre_usuario = "";
    form.tipo = "";
    form.estado = "LIBRE";
    form.fecha_ingreso = new Date().toISOString().split('T')[0];
    form.fecha_disponible_uso = "";
    form.vida_util_anios = "";
    form.id_persona = "";
    form.caracteristicas = [];
    form.observacion_tecnica = "";
    form.categoria = props.categoriaInicial || "equipo";
    form.ip = "";
    form.clasificacion = "";
    searchPersona.value = "";
};

const handleClose = () => {
    resetForm();
    emit("close");
};

const handleSubmit = () => {
    const caracteristicas = (form.caracteristicas ?? []).filter(
        (item) => item?.clave?.trim() && item?.valor?.trim(),
    );

    // If it's a program, reset equipment-specific fields so they aren't saved accidentally
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

    emit("save", {
        ...form,
        caracteristicas,
    });
};

const darDeBaja = () => {
    form.estado = "BAJA";
    form.id_persona = "";
    searchPersona.value = "";
};

const restaurarEquipo = () => {
    form.estado = form.id_persona ? "EN USO" : "LIBRE";
    form.observacion_tecnica = "";
};

const TIPOS_POR_CATEGORIA = {
    equipo: ["PC", "Laptop", "Todo en uno", "Monitor", "Teclado", "Mouse", "Otro (equipos)"],
    programa: ["Institucional", "Navegador", "Ofimática", "Soporte", "Antivirus", "Otro (programas)"]
};

const tiposDisponibles = computed(() => {
    return TIPOS_POR_CATEGORIA[form.categoria] || [];
});

watch(() => form.tipo, (newTipo) => {
    if (!['pc', 'laptop', 'todo en uno'].includes((newTipo || '').toLowerCase())) {
        form.ip = "";
    }
});

watch(() => form.id_persona, (val) => {
    if (val) {
        if (form.estado !== "BAJA") {
            form.estado = "EN USO";
        }
    } else {
        if (form.estado !== "BAJA") {
            form.estado = "LIBRE";
        }
    }
});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            resetForm();
            form.categoria = props.categoriaInicial;
        }
    }
);

const agregarCaracteristica = () => {
    form.caracteristicas.push({ clave: "", valor: "" });
};

const quitarCaracteristica = (index) => {
    form.caracteristicas.splice(index, 1);
};

defineExpose({ resetForm });
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50">
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
            enter-active-class="transform transition duration-200"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition duration-200"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                class="absolute inset-y-0 right-0 w-full max-w-xl bg-white shadow-xl flex flex-col h-full"
            >
                <div
                    class="flex items-center justify-between border-b border-ugel-azul/10 px-5 py-4 shrink-0"
                >
                    <div>
                        <div
                            class="text-xs font-semibold uppercase tracking-wider text-ugel-azul/70"
                        >
                            Inventario
                        </div>
                        <div class="text-lg font-bold text-ugel-guinda">
                            Nuevo {{ form.categoria === 'programa' ? 'programa' : 'equipo' }}
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

                <div class="flex-1 overflow-y-auto px-5 py-5">
                    <div v-if="errors && Object.keys(errors).length > 0 && (!errors.tipo && !errors.nombre || errors.global)" class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-600 border border-red-200">
                        <span v-if="errors.global">{{ errors.global }}</span>
                        <span v-else>Revisa los campos para corregir los errores.</span>
                    </div>

                    <form
                        class="grid grid-cols-1 gap-4 md:grid-cols-2"
                        @submit.prevent="handleSubmit"
                    >
                        <!-- Selector de Categoría (Visual Badge) -->
                        <div class="col-span-1 md:col-span-2 bg-gray-50 p-3 rounded-lg border border-gray-200 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Categoría de Registro:</span>
                            <span :class="[
                                'px-3 py-1 text-xs font-bold uppercase rounded-md tracking-wider border',
                                form.categoria === 'programa' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-rose-50 text-rose-700 border-rose-200'
                            ]">
                                {{ form.categoria === 'programa' ? 'PROGRAMA / SOFTWARE' : 'EQUIPO / HARDWARE' }}
                            </span>
                        </div>

                        <!-- VISTA PARA PROGRAMAS -->
                        <div v-if="form.categoria === 'programa'" class="col-span-1 md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <!-- Nombre del Programa -->
                            <div class="md:col-span-2">
                                <label for="prog_nombre" class="block text-sm font-medium text-gray-700">Nombre del programa <span class="text-red-500">*</span></label>
                                <input
                                    id="prog_nombre"
                                    v-model="form.nombre"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-1"
                                    placeholder=""
                                    required
                                    :disabled="loading"
                                />
                                <p v-if="errors.nombre" class="mt-1 text-xs text-red-600">{{ errors.nombre[0] }}</p>
                            </div>

                            <!-- Tipo de Programa -->
                            <div class="md:col-span-2">
                                <label for="prog_tipo" class="block text-sm font-medium text-gray-700">Tipo de programa <span class="text-red-500">*</span></label>
                                <select
                                    id="prog_tipo"
                                    v-model="form.tipo"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-1"
                                    required
                                    :disabled="loading"
                                >
                                    <option value="">Seleccione tipo</option>
                                    <option v-for="t in tiposDisponibles" :key="t" :value="t.toLowerCase()">{{ t }}</option>
                                </select>
                                <p v-if="errors.tipo" class="mt-1 text-xs text-red-600">{{ errors.tipo[0] }}</p>
                            </div>
                        </div>

                        <!-- VISTA PARA EQUIPOS (SECCIONADA) -->
                        <div v-else class="col-span-1 md:col-span-2 space-y-6">
                            <!-- Sección 1: Información General -->
                            <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-200/80 space-y-4">
                                <div class="text-xs font-bold text-ugel-guinda uppercase tracking-wider border-b border-gray-200 pb-2">
                                    1. Información General del Equipo
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="eq_patrimonial" class="block text-sm font-medium text-gray-700">Código patrimonial</label>
                                        <input
                                            id="eq_patrimonial"
                                            v-model="form.cod_patrimonial"
                                            type="text"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-1"
                                            placeholder="74089900XXXX"
                                            :disabled="loading"
                                        />
                                        <p v-if="errors.cod_patrimonial" class="mt-1 text-xs text-red-600">{{ errors.cod_patrimonial[0] }}</p>
                                    </div>
                                    <div v-if="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase())">
                                        <label for="eq_nombre" class="block text-sm font-medium text-gray-700">Nombre de equipo</label>
                                        <input
                                            id="eq_nombre"
                                            v-model="form.nombre"
                                            type="text"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-1"
                                            placeholder="DESKTOP-EAOR902..."
                                            :disabled="loading"
                                        />
                                        <p v-if="errors.nombre" class="mt-1 text-xs text-red-600">{{ errors.nombre[0] }}</p>
                                    </div>
                                    <div :class="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase()) ? 'md:col-span-2' : ''">
                                        <label for="eq_tipo" class="block text-sm font-medium text-gray-700">Tipo de equipo <span class="text-red-500">*</span></label>
                                        <select
                                            id="eq_tipo"
                                            v-model="form.tipo"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-1"
                                            required
                                            :disabled="loading"
                                        >
                                            <option value="">Seleccione tipo</option>
                                            <option v-for="t in tiposDisponibles" :key="t" :value="t.toLowerCase()">{{ t }}</option>
                                        </select>
                                        <p v-if="errors.tipo" class="mt-1 text-xs text-red-600">{{ errors.tipo[0] }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección 2: Estado y Asignación -->
                            <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-200/80 space-y-4">
                                <div class="text-xs font-bold text-ugel-guinda uppercase tracking-wider border-b border-gray-200 pb-2">
                                    2. Estado y Asignación de Responsable
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                        <div class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm flex items-center transition-colors border-ugel-azul/40 bg-gray-50">
                                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold border', 
                                                form.estado === 'LIBRE' ? 'bg-green-100 text-green-700 border-green-200' :
                                                form.estado === 'EN USO' ? 'bg-blue-100 text-blue-700 border-blue-200' :
                                                'bg-red-100 text-red-700 border-red-200']">
                                                {{ form.estado }}
                                            </span>
                                        </div>
                                        <p v-if="errors.estado" class="mt-1 text-xs text-red-600">{{ errors.estado[0] }}</p>
                                        <p class="text-[11px] text-gray-500 mt-1 leading-tight">
                                            El estado se actualiza automáticamente al asignar un responsable.
                                        </p>
                                    </div>

                                    <div v-if="!['monitor', 'teclado', 'mouse', 'otro (equipos)'].includes((form.tipo || '').toLowerCase())">
                                        <label for="eq_usuario" class="block text-sm font-medium text-gray-700">Cuenta local</label>
                                        <input
                                            id="eq_usuario"
                                            v-model="form.nombre_usuario"
                                            type="text"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul focus:ring-1"
                                            placeholder="JUANPEREZ, UHNEXUSB..."
                                            :disabled="loading"
                                        />
                                        <p v-if="errors.nombre_usuario" class="mt-1 text-xs text-red-600">{{ errors.nombre_usuario[0] }}</p>
                                    </div>

                                    <div class="md:col-span-2 relative">
                                        <div class="flex items-center justify-between">
                                            <label for="eq_persona" class="block text-sm font-medium text-gray-700">Responsable</label>
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-ugel-azul hover:text-ugel-guinda transition"
                                                @click="openQuickRegister"
                                            >
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                </svg>
                                                Registro rápido
                                            </button>
                                        </div>
                                        <div class="relative mt-1">
                                            <input
                                                id="eq_persona"
                                                v-model="searchPersona"
                                                type="text"
                                                autocomplete="off"
                                                class="block w-full rounded-lg border px-3 py-2 text-sm text-gray-700 transition-colors disabled:bg-gray-100 disabled:text-gray-400 cursor-text border-ugel-azul/40 focus:border-ugel-azul focus:ring-ugel-azul"
                                                placeholder="Buscar por nombre o área..."
                                                :disabled="loading || form.estado === 'BAJA'"
                                                @focus="showDropdown = true"
                                                @blur="handleBlur"
                                            />
                                            <div
                                                v-if="showDropdown"
                                                class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                                            >
                                                <div
                                                    class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                    @click="selectPersona(null)"
                                                >
                                                    -- Sin asignar --
                                                </div>
                                                <div
                                                    v-for="persona in filteredPersonas"
                                                    :key="persona.id"
                                                    class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center justify-between"
                                                    @click="selectPersona(persona)"
                                                >
                                                    <div>
                                                        <div class="font-medium">{{ persona.nombre_completo }}</div>
                                                        <div class="text-xs text-gray-500" v-if="persona.oficina">{{ persona.oficina.nombre }} {{ persona.oficina.area ? ' - ' + persona.oficina.area.nombre : '' }}</div>
                                                    </div>
                                                    <div class="size-2 rounded-full bg-green-500 shrink-0" title="Activo"></div>
                                                </div>
                                                <div v-if="filteredPersonas.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                                    No se encontraron resultados
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="form.estado === 'BAJA'" class="md:col-span-2">
                                        <label for="eq_observacion_tecnica" class="block text-sm font-medium text-gray-700">Observación técnica de baja</label>
                                        <textarea
                                            id="eq_observacion_tecnica"
                                            v-model="form.observacion_tecnica"
                                            rows="3"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            placeholder="Describa el motivo de la baja del equipo..."
                                            :disabled="loading"
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
                                    <div>
                                        <label for="eq_clasificacion" class="block text-sm font-medium text-gray-700">Clasificación</label>
                                        <select
                                            id="eq_clasificacion"
                                            v-model="form.clasificacion"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            :disabled="loading"
                                        >
                                            <option value="">Seleccione clasificación</option>
                                            <option value="BUENO">Bueno</option>
                                            <option value="REGULAR">Regular</option>
                                            <option value="MALO">Malo</option>
                                        </select>
                                    </div>

                                    <div v-if="['pc', 'laptop', 'todo en uno'].includes((form.tipo || '').toLowerCase())">
                                        <label for="eq_ip" class="block text-sm font-medium text-gray-700">Dirección IP</label>
                                        <input
                                            id="eq_ip"
                                            v-model="form.ip"
                                            type="text"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            placeholder="192.168.1.XX"
                                            :disabled="loading"
                                        />
                                    </div>

                                    <div>
                                        <label for="eq_vida" class="block text-sm font-medium text-gray-700">Vida útil (años)</label>
                                        <input
                                            id="eq_vida"
                                            v-model="form.vida_util_anios"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            placeholder="4"
                                            :disabled="loading"
                                        />
                                    </div>

                                    <div>
                                        <label for="eq_fecha_ingreso" class="block text-sm font-medium text-gray-700">Fecha de ingreso</label>
                                        <input
                                            id="eq_fecha_ingreso"
                                            v-model="form.fecha_ingreso"
                                            type="date"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            :disabled="loading"
                                        />
                                    </div>

                                    <div>
                                        <label for="eq_fecha_disponible" class="block text-sm font-medium text-gray-700">Disponible desde</label>
                                        <input
                                            id="eq_fecha_disponible"
                                            v-model="form.fecha_disponible_uso"
                                            type="date"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            :disabled="loading"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CARACTERÍSTICAS TÉCNICAS -->
                        <div class="md:col-span-2 mt-4">
                            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                                <div>
                                    <div class="text-sm font-semibold text-ugel-guinda">
                                        {{ form.categoria === 'programa' ? 'Características del programa' : 'Características adicionales' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ form.categoria === 'programa' ? 'Agrega detalles del software como versión, tipo de licencia, clave de activación, etc.' : 'Agrega detalles técnicos del hardware como procesador, RAM, disco duro, etc.' }}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-ugel-azul/30 bg-white px-3 py-1.5 text-sm font-semibold text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                                    @click="agregarCaracteristica"
                                    :disabled="loading"
                                >
                                    + Agregar
                                </button>
                            </div>

                            <div class="mt-3 space-y-2">
                                <div
                                    v-if="form.caracteristicas.length === 0"
                                    class="rounded-lg border border-dashed border-ugel-azul/20 bg-ugel-azul/5 px-4 py-3 text-sm text-gray-600 text-center"
                                >
                                    No hay características adicionales agregadas.
                                </div>

                                <div
                                    v-for="(item, index) in form.caracteristicas"
                                    :key="index"
                                    class="grid grid-cols-1 gap-2 rounded-lg border border-ugel-azul/15 bg-white p-3 sm:grid-cols-12 sm:items-end"
                                >
                                    <div class="sm:col-span-5">
                                        <label
                                            :for="`car_clave_${index}`"
                                            class="block text-xs font-semibold text-gray-600"
                                        >
                                            Característica
                                        </label>
                                        <div class="relative mt-1">
                                            <input
                                                :id="`car_clave_${index}`"
                                                v-model="item.clave"
                                                type="text"
                                                list="caracteristicas-por-defecto"
                                                class="datalist-input block w-full appearance-none rounded-lg border border-ugel-azul/30 px-3 py-2 pe-10 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                                placeholder="Modelo, RAM, Procesador..."
                                                :disabled="loading"
                                            />
                                            <div
                                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-gray-400"
                                            >
                                                <svg
                                                    class="size-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M19 9l-7 7-7-7"
                                                    />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-6">
                                        <label
                                            :for="`car_valor_${index}`"
                                            class="block text-xs font-semibold text-gray-600"
                                        >
                                            Detalle
                                        </label>
                                        <input
                                            :id="`car_valor_${index}`"
                                            v-model="item.valor"
                                            type="text"
                                            class="mt-1 block w-full rounded-lg border border-ugel-azul/30 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                            placeholder="Ej: Dell, 16GB, i7..."
                                            :disabled="loading"
                                        />
                                    </div>

                                    <div
                                        class="sm:col-span-1 sm:flex sm:justify-end"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex w-full items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-600 hover:text-white transition sm:w-auto"
                                            @click="quitarCaracteristica(index)"
                                            :disabled="loading"
                                        >
                                            Quitar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <datalist id="caracteristicas-por-defecto">
                            <option
                                v-for="opcion in CARACTERISTICAS_POR_DEFECTO"
                                :key="opcion"
                                :value="opcion"
                            />
                        </datalist>
                    </form>
                </div>

                <div
                    class="border-t border-ugel-azul/10 px-5 py-4 flex items-center justify-end gap-3 bg-white shrink-0"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        @click="handleClose"
                        :disabled="loading"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-ugel-azul px-4 py-2 text-sm font-semibold text-white shadow hover:bg-ugel-guinda disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="handleSubmit"
                        :disabled="
                            loading ||
                            !form.tipo.trim() ||
                            (form.categoria === 'equipo' && !form.estado.trim()) ||
                            (form.categoria === 'programa' && !form.nombre.trim())
                        "
                    >
                        <svg
                            v-if="loading"
                            class="-ms-1 me-2 size-4 animate-spin text-white"
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
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        Registrar {{ form.categoria === 'programa' ? 'programa' : 'equipo' }}
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Modal de registro rápido de persona -->
        <PersonaCrearModal
            :show="showQuickRegister"
            :oficinas="oficinas"
            :loading="savingPersona"
            @close="showQuickRegister = false"
            @save="savePersona"
        />
    </div>
</template>

<style scoped>
.datalist-input::-webkit-calendar-picker-indicator {
    opacity: 0;
    display: none;
}

.datalist-input::-webkit-list-button {
    opacity: 0;
    display: none;
}
</style>
