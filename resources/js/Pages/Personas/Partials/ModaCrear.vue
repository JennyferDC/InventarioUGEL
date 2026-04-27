<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import { reactive, ref, computed } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    oficinas: {
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
    nombre_completo: "",
    celular: "",
    id_oficina: "",
});

const searchOficina = ref("");
const showOficinaDropdown = ref(false);

const normalizeText = (text) => text.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();

const filteredOficinas = computed(() => {
    const q = normalizeText(searchOficina.value);
    if (!q) return props.oficinas;
    return props.oficinas.filter(o => {
        const text = normalizeText(`${o.nombre} ${o.area?.nombre || ''}`);
        return text.includes(q);
    });
});

const selectOficina = (o) => {
    if (o) {
        form.id_oficina = o.id;
        searchOficina.value = o.nombre;
    } else {
        form.id_oficina = "";
        searchOficina.value = "";
    }
    showOficinaDropdown.value = false;
};

const handleOficinaBlur = () => {
    setTimeout(() => {
        showOficinaDropdown.value = false;
    }, 200);
};

const resetForm = () => {
    form.nombre_completo = "";
    form.celular = "";
    form.id_oficina = "";
    searchOficina.value = "";
};

const handleClose = () => {
    resetForm();
    emit("close");
};

const handleSubmit = () => {
    emit("save", {
        nombre_completo: form.nombre_completo,
        celular: form.celular,
        id_oficina: form.id_oficina,
    });
};

defineExpose({ resetForm });
</script>

<template>
    <DialogModal :show="show" @close="handleClose" max-width="lg">
        <template #title>
            <span class="text-ugel-guinda font-semibold">Nueva persona</span>
        </template>

        <template #content>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label
                        for="persona_nombre"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Nombre completo
                    </label>
                    <input
                        id="persona_nombre"
                        v-model="form.nombre_completo"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Nombre y apellidos"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="persona_celular"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Celular
                    </label>
                    <input
                        id="persona_celular"
                        v-model="form.celular"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Ej. 999999999"
                        :disabled="loading"
                    />
                </div>

                <div class="relative">
                    <label
                        for="search_oficina"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Oficina
                    </label>
                    <input
                        id="search_oficina"
                        v-model="searchOficina"
                        type="text"
                        class="block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                        placeholder="Buscar oficina por nombre o área..."
                        @focus="showOficinaDropdown = true"
                        @blur="handleOficinaBlur"
                        :disabled="loading"
                    />
                    <div
                        v-if="showOficinaDropdown"
                        class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                    >
                        <div
                            class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            @click="selectOficina(null)"
                        >
                            -- Sin asignar --
                        </div>
                        <div
                            v-for="o in filteredOficinas"
                            :key="o.id"
                            class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            @click="selectOficina(o)"
                        >
                            <div class="font-medium">{{ o.nombre }}</div>
                            <div class="text-xs text-gray-500" v-if="o.area">{{ o.area.nombre }}</div>
                        </div>
                        <div v-if="filteredOficinas.length === 0" class="px-4 py-2 text-sm text-gray-500">
                            No se encontraron resultados
                        </div>
                    </div>
                </div>
            </form>
        </template>

        <template #footer>
            <button
                type="button"
                class="me-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-70"
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
                    loading || !form.nombre_completo.trim() || !form.id_oficina
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
                Crear persona
            </button>
        </template>
    </DialogModal>
</template>
