<script setup>
import { reactive } from "vue";

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
});

const emit = defineEmits(["close", "save"]);

const form = reactive({
    cod_informatica: "",
    tipo: "",
    estado: "",
    fecha_disponible_uso: "",
    vida_util_anios: "",
    id_persona: "",
    caracteristicas: [],
});

const resetForm = () => {
    form.cod_informatica = "";
    form.tipo = "";
    form.estado = "";
    form.fecha_disponible_uso = "";
    form.vida_util_anios = "";
    form.id_persona = "";
    form.caracteristicas = [];
};

const handleClose = () => {
    resetForm();
    emit("close");
};

const handleSubmit = () => {
    const caracteristicas = (form.caracteristicas ?? []).filter(
        (item) => item?.clave?.trim() && item?.valor?.trim(),
    );

    emit("save", {
        ...form,
        caracteristicas,
    });
};

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
                class="absolute inset-y-0 right-0 w-full max-w-xl bg-white shadow-xl"
            >
                <div class="flex h-full flex-col">
                    <div
                        class="flex items-center justify-between border-b border-ugel-azul/10 px-5 py-4"
                    >
                        <div>
                            <div
                                class="text-xs font-semibold uppercase tracking-wider text-ugel-azul/70"
                            >
                                Inventario
                            </div>
                            <div class="text-lg font-bold text-ugel-guinda">
                                Nuevo equipo
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
                        <form
                            class="grid grid-cols-1 gap-4 md:grid-cols-2"
                            @submit.prevent="handleSubmit"
                        >
                            <div>
                                <label
                                    for="nuevo_cod"
                                    class="block text-sm font-medium text-gray-700"
                                    >Código informática</label
                                >
                                <input
                                    id="nuevo_cod"
                                    v-model="form.cod_informatica"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                    placeholder="EQ-001"
                                    :disabled="loading"
                                />
                            </div>

                            <div>
                                <label
                                    for="nuevo_tipo"
                                    class="block text-sm font-medium text-gray-700"
                                    >Tipo</label
                                >
                                <input
                                    id="nuevo_tipo"
                                    v-model="form.tipo"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                    placeholder="Laptop, CPU..."
                                    :disabled="loading"
                                />
                            </div>

                            <div>
                                <label
                                    for="nuevo_estado"
                                    class="block text-sm font-medium text-gray-700"
                                    >Estado</label
                                >
                                <input
                                    id="nuevo_estado"
                                    v-model="form.estado"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                    placeholder="Operativo"
                                    :disabled="loading"
                                />
                            </div>

                            <div>
                                <label
                                    for="nuevo_fecha"
                                    class="block text-sm font-medium text-gray-700"
                                    >Fecha disponible</label
                                >
                                <input
                                    id="nuevo_fecha"
                                    v-model="form.fecha_disponible_uso"
                                    type="date"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                    :disabled="loading"
                                />
                            </div>

                            <div>
                                <label
                                    for="nuevo_vida"
                                    class="block text-sm font-medium text-gray-700"
                                    >Vida útil (años)</label
                                >
                                <input
                                    id="nuevo_vida"
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
                                    for="nuevo_persona"
                                    class="block text-sm font-medium text-gray-700"
                                    >Responsable</label
                                >
                                <select
                                    id="nuevo_persona"
                                    v-model="form.id_persona"
                                    class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                                    :disabled="loading"
                                >
                                    <option value="">Sin asignar</option>
                                    <option
                                        v-for="persona in personas"
                                        :key="persona.id"
                                        :value="persona.id"
                                    >
                                        {{ persona.nombre_completo }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div
                                            class="text-sm font-semibold text-ugel-guinda"
                                        >
                                            Características
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Agrega modelo, RAM, procesador, etc.
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
                                                :for="`car_clave_${index}`"
                                                class="block text-xs font-semibold text-gray-600"
                                            >
                                                Clave
                                            </label>
                                            <div class="relative mt-1">
                                                <input
                                                    :id="`car_clave_${index}`"
                                                    v-model="item.clave"
                                                    type="text"
                                                    list="caracteristicas-por-defecto"
                                                    class="datalist-input block w-full appearance-none rounded-lg border border-ugel-azul/30 px-3 py-2 pe-10 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
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
                                                :for="`car_valor_${index}`"
                                                class="block text-xs font-semibold text-gray-600"
                                            >
                                                Valor
                                            </label>
                                            <input
                                                :id="`car_valor_${index}`"
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
                                                class="inline-flex w-full items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-600 hover:text-white transition sm:w-auto"
                                                @click="
                                                    quitarCaracteristica(index)
                                                "
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
                        class="border-t border-ugel-azul/10 px-5 py-4 flex items-center justify-end gap-3 bg-white"
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
                            Registrar equipo
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
