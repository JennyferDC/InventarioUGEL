<script setup>
import { reactive, watch, ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

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
    equipo: {
        type: Object,
        default: null,
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
});

const emit = defineEmits(["close", "save"]);

const form = reactive({
    id: null,
    cod_informatica: "",
    tipo: "",
    estado: "",
    fecha_ingreso: "",
    fecha_disponible_uso: "",
    vida_util_anios: "",
    id_persona: "",
    caracteristicas: [],
});

const searchPersona = ref("");
const showDropdown = ref(false);

const filteredPersonas = computed(() => {
    const q = searchPersona.value.toLowerCase();
    if (!q) return props.personas.slice(0, 50);
    return props.personas.filter(p => {
        const text = `${p.nombre_completo} ${p.oficina?.area?.nombre || ''}`.toLowerCase();
        return text.includes(q);
    }).slice(0, 50);
});

const selectPersona = (persona) => {
    if (persona) {
        form.id_persona = persona.id;
        searchPersona.value = `${persona.nombre_completo} - ${persona.oficina?.area ? persona.oficina.area.nombre : ''}`;
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

watch(
    () => props.equipo,
    (value) => {
        form.id = value?.id ?? null;
        form.cod_informatica = value?.cod_informatica ?? "";
        form.tipo = value?.tipo ?? "";
        form.estado = value?.estado ?? "";
        form.fecha_ingreso = value?.fecha_ingreso ?? "";
        form.fecha_disponible_uso = value?.fecha_disponible_uso ?? "";
        form.vida_util_anios = value?.vida_util_anios ?? "";
        form.id_persona = value?.id_persona ?? "";
        form.caracteristicas = Array.isArray(value?.caracteristicas)
            ? value.caracteristicas.map((item) => ({
                  clave: item?.clave ?? "",
                  valor: item?.valor ?? "",
              }))
            : [];
            
        if (form.id_persona && props.personas.length) {
            const persona = props.personas.find(p => p.id === form.id_persona);
            if (persona) {
                searchPersona.value = `${persona.nombre_completo} - ${persona.oficina?.area ? persona.oficina.area.nombre : ''}`;
            }
        } else {
            searchPersona.value = "";
        }
    },
    { immediate: true },
);

const handleSubmit = () => {
    if (!form.id) return;

    const caracteristicas = (form.caracteristicas ?? []).filter(
        (item) => item?.clave?.trim() && item?.valor?.trim(),
    );

    emit("save", {
        id: form.id,
        cod_informatica: form.cod_informatica,
        tipo: form.tipo,
        estado: form.estado,
        fecha_ingreso: form.fecha_ingreso,
        fecha_disponible_uso: form.fecha_disponible_uso,
        vida_util_anios: form.vida_util_anios,
        id_persona: form.id_persona,
        caracteristicas,
    });
};

const agregarCaracteristica = () => {
    form.caracteristicas.push({ clave: "", valor: "" });
};

const quitarCaracteristica = (index) => {
    form.caracteristicas.splice(index, 1);
};
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
            <div class="absolute inset-0 bg-black/40" @click="emit('close')" />
        </Transition>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div class="flex min-h-screen items-center justify-center p-4 w-full h-full pointer-events-none">
                <div class="pointer-events-auto relative w-full max-w-3xl rounded-2xl bg-white shadow-xl flex flex-col max-h-[90vh]">
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
                                Editar equipo
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Link
                                v-if="form.cod_informatica"
                                :href="route('equipos.showByCodigo', form.cod_informatica)"
                                class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-ugel-azul bg-ugel-azul/10 hover:bg-ugel-azul/20 transition"
                                title="Abrir ficha completa"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span class="hidden sm:inline">Expandir</span>
                            </Link>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                            @click="emit('close')"
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
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-5">
                        <div v-if="errors && Object.keys(errors).length > 0 && (!errors.cod_informatica && !errors.tipo && !errors.estado || errors.global)" class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-600 border border-red-200">
                            <span v-if="errors.global">{{ errors.global }}</span>
                            <span v-else>Revisa los campos para corregir los errores.</span>
                        </div>
                        <form
                            class="grid grid-cols-1 gap-4 md:grid-cols-2"
                            @submit.prevent="handleSubmit"
                        >
                            <div>
                                <label
                                    for="equipo_cod"
                                    class="block text-sm font-medium text-gray-700"
                                    >Código informática <span class="text-red-500">*</span></label
                                >
                                <input
                                    id="equipo_cod"
                                    v-model="form.cod_informatica"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm focus:ring-ugel-azul transition-colors"
                                    :class="errors.cod_informatica ? 'border-red-500 focus:border-red-500' : 'border-ugel-azul/40 focus:border-ugel-azul'"
                                    placeholder="EQ-001"
                                    :disabled="loading"
                                />
                                <p v-if="errors.cod_informatica" class="mt-1 text-xs text-red-600">{{ errors.cod_informatica[0] }}</p>
                            </div>

                            <div>
                                <label
                                    for="equipo_tipo"
                                    class="block text-sm font-medium text-gray-700"
                                    >Tipo <span class="text-red-500">*</span></label
                                >
                                <select
                                    id="equipo_tipo"
                                    v-model="form.tipo"
                                    class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm focus:ring-ugel-azul transition-colors"
                                    :class="errors.tipo ? 'border-red-500 focus:border-red-500' : 'border-ugel-azul/40 focus:border-ugel-azul'"
                                    :disabled="loading"
                                >
                                    <option value="">Seleccione tipo</option>
                                    <option value="PC">PC</option>
                                    <option value="LAPTOP">LAPTOP</option>
                                    <option value="TODO EN UNO">TODO EN UNO</option>
                                    <option value="COMPONENTE">COMPONENTE</option>
                                    <option value="TECLADO">TECLADO</option>
                                    <option value="MOUSE">MOUSE</option>
                                    <option value="OTRO">OTRO</option>
                                    <option value="MONITOR">MONITOR</option>
                                </select>
                                <p v-if="errors.tipo" class="mt-1 text-xs text-red-600">{{ errors.tipo[0] }}</p>
                            </div>

                            <div>
                                <label
                                    for="equipo_estado"
                                    class="block text-sm font-medium text-gray-700"
                                    >Estado <span class="text-red-500">*</span></label
                                >
                                <select
                                    id="equipo_estado"
                                    v-model="form.estado"
                                    class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm focus:ring-ugel-azul transition-colors"
                                    :class="errors.estado ? 'border-red-500 focus:border-red-500' : 'border-ugel-azul/40 focus:border-ugel-azul'"
                                    :disabled="loading"
                                >
                                    <option value="">Seleccione estado</option>
                                    <option value="LIBRE">LIBRE</option>
                                    <option value="EN USO">EN USO</option>
                                    <option value="BAJA">BAJA</option>
                                </select>
                                <p v-if="errors.estado" class="mt-1 text-xs text-red-600">{{ errors.estado[0] }}</p>
                            </div>

                            <div>
                                <label
                                    for="equipo_vida"
                                    class="block text-sm font-medium text-gray-700"
                                    >Vida útil (años)</label
                                >
                                <input
                                    id="equipo_vida"
                                    v-model="form.vida_util_anios"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                    placeholder="4"
                                    :disabled="loading"
                                />
                            </div>

                            <div>
                                <label
                                    for="equipo_fecha_ingreso"
                                    class="block text-sm font-medium text-gray-700"
                                    >Fecha de ingreso</label
                                >
                                <p class="text-[11px] text-gray-500 mb-1">Es cuando el área de informática recibe el equipo</p>
                                <input
                                    id="equipo_fecha_ingreso"
                                    v-model="form.fecha_ingreso"
                                    type="date"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                    :disabled="loading"
                                />
                            </div>

                            <div>
                                <label
                                    for="equipo_fecha"
                                    class="block text-sm font-medium text-gray-700"
                                    >Disponible desde</label
                                >
                                <p class="text-[11px] text-gray-500 mb-1">Es cuando se comienza a utilizar el equipo</p>
                                <input
                                    id="equipo_fecha"
                                    v-model="form.fecha_disponible_uso"
                                    type="date"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                    :disabled="loading"
                                />
                            </div>

                            <div class="md:col-span-2 relative">
                                <label
                                    for="equipo_persona"
                                    class="block text-sm font-medium text-gray-700"
                                    >Responsable</label
                                >
                                <div class="relative mt-1">
                                    <input
                                        id="equipo_persona"
                                        v-model="searchPersona"
                                        type="text"
                                        class="block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                                        placeholder="Buscar por nombre o área..."
                                        :disabled="loading"
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
                                            Sin asignar
                                        </div>
                                        <div
                                            v-for="persona in filteredPersonas"
                                            :key="persona.id"
                                            class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            @click="selectPersona(persona)"
                                        >
                                            <div class="font-medium">{{ persona.nombre_completo }}</div>
                                            <div class="text-xs text-gray-500">{{ persona.oficina?.area ? persona.oficina.area.nombre : 'Sin área' }}</div>
                                        </div>
                                        <div v-if="filteredPersonas.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                            No se encontraron resultados
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div
                                            class="text-sm font-semibold text-ugel-guinda"
                                        >
                                            Características
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Agrega detalles del equipo como color, modelo, RAM, procesador, etc.
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-lg border border-ugel-azul/30 bg-white px-3 py-2 text-sm font-semibold text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                                        @click="agregarCaracteristica"
                                        :disabled="loading"
                                    >
                                        + Agregar
                                    </button>
                                </div>

                                <div class="mt-3 space-y-2">
                                    <div
                                        v-if="form.caracteristicas.length === 0"
                                        class="rounded-lg border border-dashed border-ugel-azul/20 bg-ugel-azul/5 px-4 py-3 text-sm text-gray-600"
                                    >
                                        No hay características agregadas.
                                    </div>

                                    <div
                                        v-for="(
                                            item, index
                                        ) in form.caracteristicas"
                                        :key="index"
                                        class="grid grid-cols-1 gap-2 rounded-lg border border-ugel-azul/15 bg-white p-3 sm:grid-cols-12 sm:items-end"
                                    >
                                        <div class="sm:col-span-5">
                                            <label
                                                :for="`car_edit_clave_${index}`"
                                                class="block text-xs font-semibold text-gray-600"
                                            >
                                                Característica
                                            </label>
                                            <div class="relative mt-1">
                                                <input
                                                    :id="`car_edit_clave_${index}`"
                                                    v-model="item.clave"
                                                    type="text"
                                                    list="caracteristicas-por-defecto"
                                                    class="datalist-input appearance-none block w-full rounded-lg border border-ugel-azul/30 px-3 py-2 pe-10 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                                    placeholder="Modelo, RAM, Procesador"
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
                                                :for="`car_edit_valor_${index}`"
                                                class="block text-xs font-semibold text-gray-600"
                                            >
                                                Detalle
                                            </label>
                                            <input
                                                :id="`car_edit_valor_${index}`"
                                                v-model="item.valor"
                                                type="text"
                                                class="mt-1 block w-full rounded-lg border border-ugel-azul/30 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                                placeholder="Ej: Dell, 16GB, i5"
                                                :disabled="loading"
                                            />
                                        </div>

                                        <div
                                            class="sm:col-span-1 sm:flex sm:justify-end"
                                        >
                                            <button
                                                type="button"
                                                class="inline-flex w-full items-center justify-center rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-600 hover:text-white transition sm:w-auto"
                                                @click="
                                                    quitarCaracteristica(index)
                                                "
                                                :disabled="loading"
                                                title="Quitar"
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
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-3h4m-4 0a1 1 0 00-1 1v1h6V5a1 1 0 00-1-1m-4 0h4"
                                                    />
                                                </svg>
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
                        class="flex justify-end space-x-2 border-t border-ugel-azul/10 p-5"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-ugel-azul/30 bg-white px-4 py-2 text-sm font-semibold text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                            @click="emit('close')"
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
                                !form.cod_informatica.trim() ||
                                !form.tipo.trim() ||
                                !form.estado.trim()
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
                            Guardar cambios
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
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
