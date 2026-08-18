<script setup>
import DialogDrawer from "@/Components/DialogDrawer.vue";
import { reactive, ref, computed, watch, nextTick, onUnmounted } from "vue";

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
    correo: "",
    cargo: "",
    id_oficina: "",
});

const searchOficina = ref("");
const showOficinaDropdown = ref(false);
const inputRef = ref(null);
const dropdownStyle = ref({});
const teleportTarget = ref("body");

const updateDropdownPosition = () => {
    if (!inputRef.value) return;
    const rect = inputRef.value.getBoundingClientRect();
    
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    
    let maxHeight = 240;
    
    if (spaceBelow < 240 && spaceAbove > spaceBelow) {
        maxHeight = Math.min(240, spaceAbove - 10);
        const bottom = window.innerHeight - rect.top + 4;
        dropdownStyle.value = {
            position: 'fixed',
            bottom: `${bottom}px`,
            top: 'auto',
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            maxHeight: `${maxHeight}px`,
            zIndex: '9999',
        };
    } else {
        maxHeight = Math.min(240, spaceBelow - 10);
        const top = rect.bottom + 4;
        dropdownStyle.value = {
            position: 'fixed',
            top: `${top}px`,
            bottom: 'auto',
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            maxHeight: `${maxHeight}px`,
            zIndex: '9999',
        };
    }
};

const handleResize = () => {
    if (showOficinaDropdown.value) {
        showOficinaDropdown.value = false;
    }
};

watch(showOficinaDropdown, (newVal) => {
    if (newVal) {
        if (inputRef.value) {
            const dialog = inputRef.value.closest('dialog');
            teleportTarget.value = dialog || 'body';
        }
        nextTick(() => {
            updateDropdownPosition();
        });
        window.addEventListener('resize', handleResize);
    } else {
        window.removeEventListener('resize', handleResize);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});

const handleScroll = () => {
    if (showOficinaDropdown.value) {
        showOficinaDropdown.value = false;
    }
};

const selectedArea = computed(() => {
    if (!form.id_oficina) return "---";
    const ofi = props.oficinas?.find(o => o.id === form.id_oficina);
    return ofi?.area?.nombre || "---";
});

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

const formatNombreCompleto = (val) => {
    if (!val) return "";
    return val
        .toLowerCase()
        .replace(/(?:^|\s)\p{L}/gu, (letter) => letter.toUpperCase());
};

const formatCelular = (val) => {
    if (!val) return "";
    const digits = val.replace(/\D/g, "").slice(0, 9);
    if (digits.length <= 3) return digits;
    if (digits.length <= 6) return `${digits.slice(0, 3)} ${digits.slice(3)}`;
    return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6)}`;
};

const handleNombreInput = (e) => {
    form.nombre_completo = formatNombreCompleto(e.target.value);
};

const handleCelularInput = (e) => {
    form.celular = formatCelular(e.target.value);
};

const resetForm = () => {
    form.nombre_completo = "";
    form.celular = "";
    form.correo = "";
    form.cargo = "";
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
        correo: form.correo,
        cargo: form.cargo,
        id_oficina: form.id_oficina,
    });
};

defineExpose({ resetForm });
</script>

<template>
    <DialogDrawer :show="show" @close="handleClose" max-width="xl" @scroll="handleScroll">
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
                        Nombre completo <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="persona_nombre"
                        v-model="form.nombre_completo"
                        @input="handleNombreInput"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Nombre y apellidos"
                        :disabled="loading"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-3">
                        <label
                            for="persona_correo"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Correo electrónico
                        </label>
                        <input
                            id="persona_correo"
                            v-model="form.correo"
                            type="email"
                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                            placeholder="Ej. correo@ejemplo.com"
                            :disabled="loading"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label
                            for="persona_celular"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Celular
                        </label>
                        <input
                            id="persona_celular"
                            v-model="form.celular"
                            @input="handleCelularInput"
                            type="text"
                            maxlength="11"
                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                            placeholder="Ej. 987 654 321"
                            :disabled="loading"
                        />
                    </div>
                </div>

                <div>
                    <label
                        for="persona_cargo"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Cargo / Especialidad
                    </label>
                    <input
                        id="persona_cargo"
                        v-model="form.cargo"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Ej. Especialista en Soporte"
                        :disabled="loading"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-3">
                        <label
                            for="search_oficina"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Oficina <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="search_oficina"
                                ref="inputRef"
                                v-model="searchOficina"
                                type="text"
                                class="block w-full rounded-lg border border-ugel-azul/40 pl-3 pr-10 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                placeholder="Buscar oficina por nombre o área..."
                                @focus="showOficinaDropdown = true"
                                @blur="handleOficinaBlur"
                                :disabled="loading"
                                autocomplete="off"
                            />
                            <!-- Clear Button -->
                            <button
                                v-if="searchOficina || form.id_oficina"
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors"
                                @click="selectOficina(null)"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <Teleport :to="teleportTarget">
                            <div
                                v-if="showOficinaDropdown"
                                :style="dropdownStyle"
                                class="overflow-y-auto rounded-md bg-white py-1 shadow-xl ring-1 ring-black ring-opacity-10 scroll-light"
                            >
                                <div
                                    class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    @mousedown.prevent
                                    @click="selectOficina(null)"
                                >
                                    -- Sin asignar --
                                </div>
                                <div
                                    v-for="o in filteredOficinas"
                                    :key="o.id"
                                    class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    @mousedown.prevent
                                    @click="selectOficina(o)"
                                >
                                    <div class="font-medium">{{ o.nombre }}</div>
                                    <div class="text-xs text-gray-500" v-if="o.area">{{ o.area.nombre }}</div>
                                </div>
                                <div v-if="filteredOficinas.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                    No se encontraron resultados
                                </div>
                            </div>
                        </Teleport>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Área
                        </label>
                        <input
                            type="text"
                            :value="selectedArea"
                            disabled
                            class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                        />
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
    </DialogDrawer>
</template>

<style scoped>
.scroll-light::-webkit-scrollbar {
    width: 6px;
}
.scroll-light::-webkit-scrollbar-track {
    background: transparent;
}
.scroll-light::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}
.scroll-light::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}
</style>
