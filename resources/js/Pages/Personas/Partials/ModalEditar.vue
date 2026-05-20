<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import { reactive, watch, ref, computed, nextTick, onUnmounted } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    persona: {
        type: Object,
        default: null,
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

const emit = defineEmits(["close", "save", "toggle-status"]);

const form = reactive({
    id: null,
    nombre_completo: "",
    celular: "",
    correo: "",
    cargo: "",
    id_oficina: "",
    estado: "ACTIVO",
});

const searchOficina = ref("");
const showOficinaDropdown = ref(false);
const inputRef = ref(null);
const dropdownStyle = ref({});

const updateDropdownPosition = () => {
    if (!inputRef.value) return;
    const rect = inputRef.value.getBoundingClientRect();
    
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const dropdownHeight = 192; // max-h-48 is 192px
    
    let top = rect.bottom + window.scrollY;
    
    if (spaceBelow < dropdownHeight && spaceAbove > spaceBelow) {
        top = rect.top + window.scrollY - dropdownHeight - 4;
    } else {
        top = rect.bottom + window.scrollY + 4;
    }
    
    dropdownStyle.value = {
        position: 'absolute',
        top: `${top}px`,
        left: `${rect.left + window.scrollX}px`,
        width: `${rect.width}px`,
        zIndex: '9999',
    };
};

const handleResize = () => {
    if (showOficinaDropdown.value) {
        showOficinaDropdown.value = false;
    }
};

watch(showOficinaDropdown, (newVal) => {
    if (newVal) {
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

watch(
    () => props.persona,
    (value) => {
        form.id = value?.id ?? null;
        form.nombre_completo = value?.nombre_completo ?? "";
        form.celular = value?.celular ?? "";
        form.correo = value?.correo ?? "";
        form.cargo = value?.cargo ?? "";
        form.id_oficina = value?.id_oficina ?? "";
        form.estado = value?.estado ?? "ACTIVO";

        if (form.id_oficina) {
            const o = props.oficinas.find(x => x.id === form.id_oficina);
            if (o) searchOficina.value = o.nombre;
            else searchOficina.value = "";
        } else {
            searchOficina.value = "";
        }
    },
    { immediate: true }
);

const handleSubmit = () => {
    if (!form.id) return;

    emit("save", {
        id: form.id,
        nombre_completo: form.nombre_completo,
        celular: form.celular,
        correo: form.correo,
        cargo: form.cargo,
        id_oficina: form.id_oficina,
        estado: form.estado,
    });
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="xl">
        <template #title>
            <span class="text-ugel-guinda font-semibold">Editar persona</span>
        </template>

        <template #content>
            <div class="max-h-[calc(100vh-14rem)] overflow-y-auto pr-2 scroll-light" @scroll="handleScroll">
                <div class="mb-4 flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700">Estado de cuenta:</span>
                    <span 
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold border"
                        :class="persona?.estado === 'ACTIVO' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'"
                    >
                        {{ persona?.estado || 'ACTIVO' }}
                    </span>
                </div>
                <button
                    type="button"
                    class="text-xs font-bold px-3 py-1.5 rounded-md transition-colors border"
                    :class="persona?.estado === 'ACTIVO' ? 'text-red-600 bg-red-50 hover:bg-red-100 border-red-200' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border-emerald-200'"
                    @click="emit('toggle-status', persona)"
                >
                    <span v-if="persona?.estado === 'ACTIVO'">Desactivar cuenta</span>
                    <span v-else>Activar cuenta</span>
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label
                        for="nombre_completo_editar"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Nombre completo
                    </label>
                    <input
                        id="nombre_completo_editar"
                        v-model="form.nombre_completo"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Nombre y apellidos"
                        :disabled="loading"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label
                            for="persona_celular_editar"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Celular
                        </label>
                        <input
                            id="persona_celular_editar"
                            v-model="form.celular"
                            type="text"
                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                            placeholder="Ej. 987654321"
                            :disabled="loading"
                        />
                    </div>
                    <div>
                        <label
                            for="persona_correo_editar"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Correo electrónico
                        </label>
                        <input
                            id="persona_correo_editar"
                            v-model="form.correo"
                            type="email"
                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                            placeholder="Ej. correo@ejemplo.com"
                            :disabled="loading"
                        />
                    </div>
                </div>

                <div>
                    <label
                        for="persona_cargo_editar"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Cargo / Especialidad
                    </label>
                    <input
                        id="persona_cargo_editar"
                        v-model="form.cargo"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Ej. Especialista en Soporte"
                        :disabled="loading"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative">
                        <label
                            for="search_oficina_editar"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Oficina
                        </label>
                        <input
                            id="search_oficina_editar"
                            ref="inputRef"
                            v-model="searchOficina"
                            type="text"
                            class="block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                            placeholder="Buscar oficina por nombre o área..."
                            @focus="showOficinaDropdown = true"
                            @blur="handleOficinaBlur"
                            :disabled="loading"
                            autocomplete="off"
                        />
                        
                        <Teleport to="body">
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

                    <div>
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

                <div v-if="persona?.equipos?.length" class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center justify-between">
                        <span>Equipos Asignados ({{ persona.equipos.length }})</span>
                    </h3>
                    <div class="space-y-3">
                        <a :href="route('equipos.showByCodigo', equipo.cod_informatica)" target="_blank" v-for="equipo in persona.equipos" :key="equipo.id" class="flex items-center justify-between bg-white px-4 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 hover:shadow-md hover:border-ugel-azul/30 transition-all cursor-pointer group">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-ugel-azul/10 text-ugel-azul group-hover:bg-ugel-azul group-hover:text-white transition-colors">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <div>
                                    <span class="text-sm font-bold text-gray-800 group-hover:text-ugel-azul transition-colors block">
                                        {{ equipo.cod_informatica }}
                                    </span>
                                    <p class="text-xs text-gray-500">{{ equipo.tipo }}</p>
                                </div>
                            </div>
                            <span :class="[
                                'px-2 py-0.5 rounded-full text-xs font-semibold transition-colors',
                                equipo.estado === 'LIBRE' ? 'bg-green-100 text-green-800 group-hover:bg-green-200' :
                                equipo.estado === 'EN USO' ? 'bg-blue-100 text-blue-800 group-hover:bg-blue-200' :
                                'bg-red-100 text-red-800 group-hover:bg-red-200'
                            ]">
                                {{ equipo.estado }}
                            </span>
                        </a>
                    </div>
                </div>
                <div v-else-if="persona && (!persona.equipos || persona.equipos.length === 0)" class="mt-6 pt-6 border-t border-gray-200">
                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 bg-gray-50">
                        Esta persona no tiene equipos asignados actualmente.
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <button
                type="button"
                class="me-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-70"
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
                Guardar cambios
            </button>
        </template>
    </DialogModal>
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
