<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    equipoId: {
        type: Number,
        required: true,
    },
    codInformatica: {
        type: String,
        required: true,
    },
    programasDisponibles: {
        type: Array,
        default: () => [],
    },
    programasAsignados: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "saved"]);

const localProgramas = ref([...props.programasAsignados]);
const searchPrograma = ref("");
const selectedProgramasLeft = ref([]);
const selectedProgramasRight = ref([]);
const savingProgramas = ref(false);

watch(() => props.programasAsignados, (newVal) => {
    localProgramas.value = [...(newVal || [])];
}, { deep: true });

const leftPrograms = computed(() => {
    return props.programasDisponibles.filter(
        p => !localProgramas.value.some(lp => lp.id === p.id)
    );
});

const filteredLeftPrograms = computed(() => {
    const q = searchPrograma.value.trim().toLowerCase();
    if (!q) return leftPrograms.value;
    return leftPrograms.value.filter(p => 
        (p.nombre && p.nombre.toLowerCase().includes(q)) || 
        (p.cod_informatica && p.cod_informatica.toLowerCase().includes(q)) ||
        (p.tipo && p.tipo.toLowerCase().includes(q))
    );
});

const toggleSelectLeft = (program) => {
    const idx = selectedProgramasLeft.value.findIndex(p => p.id === program.id);
    if (idx > -1) {
        selectedProgramasLeft.value.splice(idx, 1);
    } else {
        selectedProgramasLeft.value.push(program);
    }
};

const toggleSelectRight = (program) => {
    const idx = selectedProgramasRight.value.findIndex(p => p.id === program.id);
    if (idx > -1) {
        selectedProgramasRight.value.splice(idx, 1);
    } else {
        selectedProgramasRight.value.push(program);
    }
};

const isSelectedLeft = (program) => {
    return selectedProgramasLeft.value.some(p => p.id === program.id);
};

const isSelectedRight = (program) => {
    return selectedProgramasRight.value.some(p => p.id === program.id);
};

const transferToRight = (programs) => {
    programs.forEach(prog => {
        if (!localProgramas.value.some(lp => lp.id === prog.id)) {
            localProgramas.value.push(prog);
        }
    });
    selectedProgramasLeft.value = [];
};

const transferToLeft = (programs) => {
    localProgramas.value = localProgramas.value.filter(
        lp => !programs.some(p => p.id === lp.id)
    );
    selectedProgramasRight.value = [];
};

const addSelectedToRight = () => {
    transferToRight(selectedProgramasLeft.value);
};

const removeSelectedFromRight = () => {
    transferToLeft(selectedProgramasRight.value);
};

const addAllToRight = () => {
    transferToRight(filteredLeftPrograms.value);
};

const removeAllFromRight = () => {
    transferToLeft([...localProgramas.value]);
};

const handleClose = () => {
    emit("close");
};

const saveProgramas = async () => {
    savingProgramas.value = true;
    try {
        const response = await axios.post(route('equipos.updateProgramas', props.equipoId), {
            programa_ids: localProgramas.value.map(p => p.id)
        });
        if (response.data && response.data.data) {
            emit('saved', response.data.data.programas || []);
        }
    } catch (error) {
        console.error("Error al guardar programas:", error);
        alert(error.response?.data?.message || "No se pudieron guardar los programas del equipo.");
    } finally {
        savingProgramas.value = false;
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <!-- Backdrop -->
        <transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="handleClose" />
        </transition>

        <!-- Modal Content -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[90vh] overflow-hidden border border-gray-100">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 bg-gray-50/50">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <svg class="size-5 text-ugel-azul" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Administrar Programas Asignados
                        </h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Asocie o remueva programas y utilidades del equipo físico <span class="font-bold text-ugel-guinda">{{ codInformatica }}</span></p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-gray-500 p-1.5 hover:bg-gray-100 rounded-lg transition"
                        @click="handleClose"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body / Columns -->
                <div class="p-6 grid grid-cols-1 md:grid-cols-12 bg-gray-50/30 overflow-y-auto flex-1">
                    
                    <!-- Columna Izquierda: Software Disponible -->
                    <div class="md:col-span-5 bg-white border border-gray-200/80 rounded-xl shadow-sm flex flex-col max-h-[55vh]">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Software Disponible</span>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-650 border border-gray-200/50">
                                    {{ filteredLeftPrograms.length }} de {{ leftPrograms.length }}
                                </span>
                            </div>
                            
                            <!-- Buscador -->
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchPrograma"
                                    type="text"
                                    class="block w-full rounded-lg border border-gray-300 bg-white pl-9 pr-8 py-1.5 text-xs placeholder-gray-400 focus:border-ugel-azul focus:outline-none focus:ring-1 focus:ring-ugel-azul"
                                    placeholder="Buscar por nombre, código o tipo..."
                                />
                                <button 
                                    v-if="searchPrograma" 
                                    @click="searchPrograma = ''" 
                                    type="button" 
                                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-650"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Listado Izquierda -->
                        <div class="p-2 overflow-y-auto flex-1 divide-y divide-gray-100/50 min-h-[30vh]">
                            <div 
                                v-for="prog in filteredLeftPrograms" 
                                :key="prog.id" 
                                @click="toggleSelectLeft(prog)"
                                @dblclick="transferToRight([prog])"
                                :class="[
                                    'p-2.5 rounded-lg transition-all duration-150 cursor-pointer flex items-center justify-between group my-0.5 border border-transparent',
                                    isSelectedLeft(prog) ? 'bg-blue-50/60 border-blue-200/80 shadow-sm' : 'hover:bg-gray-50'
                                ]"
                            >
                                <div class="flex items-center gap-3 pr-2 min-w-0">
                                    <input
                                        type="checkbox"
                                        :checked="isSelectedLeft(prog)"
                                        @click.stop="toggleSelectLeft(prog)"
                                        class="size-3.5 rounded border-gray-300 text-ugel-azul focus:ring-ugel-azul cursor-pointer shrink-0"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-gray-800 truncate leading-snug">{{ prog.nombre }}</p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[9px] font-semibold text-gray-400 capitalize">{{ prog.tipo || 'Programa' }}</span>
                                            <span class="text-[9px] font-mono text-gray-400">• {{ prog.cod_informatica }}</span>
                                        </div>
                                    </div>
                                </div>
                                <button 
                                    type="button" 
                                    @click.stop="transferToRight([prog])"
                                    class="opacity-0 group-hover:opacity-100 transition p-1 hover:bg-blue-100 hover:text-ugel-azul text-gray-400 rounded shrink-0"
                                    title="Instalar en el equipo"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="filteredLeftPrograms.length === 0" class="py-12 text-center text-xs text-gray-450 flex flex-col items-center justify-center">
                                <svg class="size-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                No hay software disponible para asociar.
                            </div>
                        </div>

                        <!-- Footer Izquierda: Acción rápida -->
                        <div class="p-3 bg-gray-50/50 border-t border-gray-100 rounded-b-xl flex items-center justify-between text-xs">
                            <span class="text-[10px] text-gray-400">Doble clic para transferir rápido</span>
                            <button
                                type="button"
                                :disabled="filteredLeftPrograms.length === 0"
                                @click="addAllToRight"
                                class="text-ugel-azul hover:text-ugel-guinda font-semibold disabled:opacity-40 disabled:text-gray-400 transition"
                            >
                                Instalar todos ({{ filteredLeftPrograms.length }})
                            </button>
                        </div>
                    </div>

                    <!-- Columna Centro: Botones de control -->
                    <div class="md:col-span-2 flex md:flex-col items-center justify-center gap-2.5 py-2 md:py-0">
                        <!-- Agregar seleccionados -->
                        <button
                            type="button"
                            :disabled="selectedProgramasLeft.length === 0"
                            @click="addSelectedToRight"
                            class="inline-flex items-center justify-center gap-1 rounded-lg bg-white border border-gray-300 hover:border-blue-300 hover:bg-blue-50/30 p-2 text-xs font-semibold text-gray-700 shadow-sm disabled:opacity-40 disabled:bg-gray-50 disabled:border-gray-200 disabled:text-gray-450 transition-all duration-150 shrink-0 w-11 h-9 md:w-12 md:h-10"
                            title="Asignar seleccionados"
                        >
                            <svg class="size-4 text-gray-600 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            <svg class="size-4 text-gray-600 md:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Quitar seleccionados -->
                        <button
                            type="button"
                            :disabled="selectedProgramasRight.length === 0"
                            @click="removeSelectedFromRight"
                            class="inline-flex items-center justify-center gap-1 rounded-lg bg-white border border-gray-300 hover:border-red-300 hover:bg-red-50/30 p-2 text-xs font-semibold text-gray-700 shadow-sm disabled:opacity-40 disabled:bg-gray-50 disabled:border-gray-200 disabled:text-gray-455 transition-all duration-150 shrink-0 w-11 h-9 md:w-12 md:h-10"
                            title="Desasignar seleccionados"
                        >
                            <svg class="size-4 text-gray-600 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <svg class="size-4 text-gray-600 md:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                            </svg>
                        </button>

                        <!-- Agregar todos -->
                        <button
                            type="button"
                            :disabled="leftPrograms.length === 0"
                            @click="addAllToRight"
                            class="inline-flex items-center justify-center gap-1 rounded-lg bg-white border border-gray-300 hover:border-blue-300 hover:bg-blue-50/30 p-2 text-xs font-bold text-gray-750 shadow-sm disabled:opacity-40 disabled:bg-gray-50 disabled:border-gray-200 disabled:text-gray-400 transition-all duration-150 shrink-0 w-11 h-9 md:w-12 md:h-10"
                            title="Asignar todo"
                        >
                            <span class="hidden md:inline-flex items-center font-mono">»</span>
                            <span class="md:hidden font-mono">▼▼</span>
                        </button>

                        <!-- Quitar todos -->
                        <button
                            type="button"
                            :disabled="localProgramas.length === 0"
                            @click="removeAllFromRight"
                            class="inline-flex items-center justify-center gap-1 rounded-lg bg-white border border-gray-300 hover:border-red-300 hover:bg-red-50/30 p-2 text-xs font-bold text-gray-750 shadow-sm disabled:opacity-40 disabled:bg-gray-50 disabled:border-gray-200 disabled:text-gray-400 transition-all duration-150 shrink-0 w-11 h-9 md:w-12 md:h-10"
                            title="Desasignar todo"
                        >
                            <span class="hidden md:inline-flex items-center font-mono">«</span>
                            <span class="md:hidden font-mono">▲▲</span>
                        </button>
                    </div>

                    <!-- Columna Derecha: Software Asignado -->
                    <div class="md:col-span-5 bg-white border border-gray-200/80 rounded-xl shadow-sm flex flex-col max-h-[55vh]">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl flex items-center justify-between min-h-[73px]">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Software en este Equipo</span>
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 border border-blue-200/50">
                                {{ localProgramas.length }}
                            </span>
                        </div>

                        <!-- Listado Derecha -->
                        <div class="p-2 overflow-y-auto flex-1 divide-y divide-gray-100/50 min-h-[30vh]">
                            <div 
                                v-for="prog in localProgramas" 
                                :key="prog.id" 
                                @click="toggleSelectRight(prog)"
                                @dblclick="transferToLeft([prog])"
                                :class="[
                                    'p-2.5 rounded-lg transition-all duration-150 cursor-pointer flex items-center justify-between group my-0.5 border border-transparent',
                                    isSelectedRight(prog) ? 'bg-red-50/60 border-red-200/80 shadow-sm' : 'hover:bg-gray-50'
                                ]"
                            >
                                <div class="flex items-center gap-3 pr-2 min-w-0">
                                    <input
                                        type="checkbox"
                                        :checked="isSelectedRight(prog)"
                                        @click.stop="toggleSelectRight(prog)"
                                        class="size-3.5 rounded border-gray-300 text-red-500 focus:ring-red-500 cursor-pointer shrink-0"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-gray-800 truncate leading-snug">{{ prog.nombre }}</p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[9px] font-semibold text-gray-400 capitalize">{{ prog.tipo || 'Programa' }}</span>
                                            <span class="text-[9px] font-mono text-gray-400">• {{ prog.cod_informatica }}</span>
                                        </div>
                                    </div>
                                </div>
                                <button 
                                    type="button" 
                                    @click.stop="transferToLeft([prog])"
                                    class="opacity-0 group-hover:opacity-100 transition p-1 hover:bg-red-100 hover:text-red-600 text-gray-400 rounded shrink-0"
                                    title="Desinstalar del equipo"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="localProgramas.length === 0" class="py-12 text-center text-xs text-gray-450 flex flex-col items-center justify-center">
                                <svg class="size-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                El equipo no tiene programas asignados.
                            </div>
                        </div>

                        <!-- Footer Derecha: Acción rápida -->
                        <div class="p-3 bg-gray-50/50 border-t border-gray-100 rounded-b-xl flex items-center justify-between text-xs">
                            <span class="text-[10px] text-gray-400">Doble clic para transferir rápido</span>
                            <button
                                type="button"
                                :disabled="localProgramas.length === 0"
                                @click="removeAllFromRight"
                                class="text-red-650 hover:text-red-800 font-semibold disabled:opacity-40 disabled:text-gray-400 transition"
                            >
                                Quitar todos ({{ localProgramas.length }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Bar -->
                <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4 bg-gray-50 rounded-b-2xl">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                        @click="handleClose"
                        :disabled="savingProgramas"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-ugel-azul px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-ugel-guinda disabled:opacity-50 disabled:bg-gray-400 transition"
                        @click="saveProgramas"
                        :disabled="savingProgramas"
                    >
                        <svg v-if="savingProgramas" class="size-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
