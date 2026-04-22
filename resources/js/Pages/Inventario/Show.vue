<script setup>
import { ref, watch, computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import QrcodeVue from "qrcode.vue";

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
                                        <input
                                            id="fecha_ingreso"
                                            v-model="form.fecha_ingreso"
                                            type="date"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        />
                                    </div>
                                    <div>
                                        <label for="fecha_disp" class="block text-sm font-medium text-gray-700">Disponible desde</label>
                                        <input
                                            id="fecha_disp"
                                            v-model="form.fecha_disponible_uso"
                                            type="date"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        />
                                    </div>

                                    <!-- Responsable -->
                                    <div class="md:col-span-2">
                                        <label for="id_persona" class="block text-sm font-medium text-gray-700">Responsable (Persona asignada)</label>
                                        <select
                                            id="id_persona"
                                            v-model="form.id_persona"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                        >
                                            <option value="">Sin asignar</option>
                                            <optgroup v-for="area in areas" :key="area.id" :label="area.nombre">
                                                <option
                                                    v-for="persona in personas.filter((p) => p.id_area === area.id)"
                                                    :key="persona.id"
                                                    :value="persona.id"
                                                >
                                                    {{ persona.nombre_completo }}
                                                </option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>

                                <!-- Características -->
                                <div class="mt-8 border-t border-gray-100 pt-6">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h4 class="text-sm font-bold text-gray-900">Características / Detalles</h4>
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

                                <div class="hidden sm:flex justify-end pt-4 border-t border-gray-100">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-6 py-2.5 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-200 disabled:opacity-50 transition"
                                    >
                                        Actualizar información
                                    </button>
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
                            <div class="mt-4 text-xs font-mono bg-gray-100 text-gray-600 px-3 py-1 rounded-md mb-6">
                                {{ form.cod_informatica }}
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
                            <div class="border-b border-gray-100 px-6 py-4 bg-gray-50">
                                <h3 class="text-sm font-bold text-gray-900">
                                    Otros equipos asignados
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Responsable: {{ equipo.persona.nombre_completo }}
                                </p>
                            </div>
                            <div class="p-0">
                                <ul v-if="otrosEquipos.length > 0" class="divide-y divide-gray-100">
                                    <li v-for="otro in otrosEquipos" :key="otro.id" class="px-6 py-4 hover:bg-gray-50 transition">
                                        <Link :href="route('equipos.showByCodigo', otro.cod_informatica)" class="flex justify-between items-center group">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800 group-hover:text-ugel-azul transition">{{ otro.cod_informatica }}</p>
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
    </AppLayout>
</template>
