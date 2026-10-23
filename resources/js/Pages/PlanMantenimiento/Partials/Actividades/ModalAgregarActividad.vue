<script setup>
import { ref, watch, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    show: Boolean,
    planId: {
        type: [Number, String],
        required: true
    },
    oficinas: {
        type: Array,
        required: true
    },
    items: {
        type: Array,
        default: () => []
    }
});

const page = usePage();

const emit = defineEmits(["close"]);

const form = useForm({
    id_oficinas: "",
    actividad: "MANTENIMIENTO PREDICTIVO, PREVENTIVO Y CORRECTIVO",
    fecha_inicio: "",
    fecha_fin: ""
});

const searchOficina = ref("");
const showDropdown = ref(false);

const normalizeText = (text) => {
    return text.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
};

const filteredOficinas = computed(() => {
    const q = normalizeText(searchOficina.value);
    if (!q) return props.oficinas.slice(0, 50);
    return props.oficinas.filter(o => {
        const text = normalizeText(`${o.nombre} ${o.area?.nombre || ''}`);
        return text.includes(q);
    }).slice(0, 50);
});

const selectOficina = (oficina) => {
    if (oficina) {
        form.id_oficinas = oficina.id;
        searchOficina.value = oficina.nombre;
    } else {
        form.id_oficinas = "";
        searchOficina.value = "";
    }
    showDropdown.value = false;
};

const handleBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const [year, month, day] = dateString.split('-');
    return `${day}/${month}/${year}`;
};

const areaConflict = computed(() => {
    if (!form.id_oficinas || !form.fecha_inicio || !form.fecha_fin) return null;
    if (form.fecha_fin < form.fecha_inicio) return null;

    const selectedOficina = props.oficinas.find(o => o.id === form.id_oficinas);
    if (!selectedOficina) return null;

    const areaId = selectedOficina.area_id;

    return props.items.find(item => {
        const itemAreaId = item.oficina?.area_id ?? item.oficina?.area?.id;
        const sameArea = areaId ? (itemAreaId === areaId) : (item.id_oficinas === form.id_oficinas);
        if (!sameArea) return false;

        return item.fecha_inicio <= form.fecha_fin && item.fecha_fin >= form.fecha_inicio;
    });
});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.reset();
            form.clearErrors();
            searchOficina.value = "";
        }
    }
);

const submit = () => {
    form.clearErrors();

    if (!form.id_oficinas) {
        form.setError('id_oficinas', 'Debe seleccionar una oficina.');
        return;
    }

    if (form.fecha_inicio && form.fecha_fin && form.fecha_fin < form.fecha_inicio) {
        form.setError('fecha_fin', 'La fecha de fin debe ser posterior o igual a la fecha de inicio.');
        return;
    }

    if (areaConflict.value) {
        const areaName = areaConflict.value.oficina?.area?.nombre;
        const ofiName = areaConflict.value.oficina?.nombre || 'otra oficina';
        const msg = `Existe un cruce de fechas con la actividad '${areaConflict.value.actividad}' en ${ofiName}${areaName ? ' (' + areaName + ')' : ''} del ${formatDate(areaConflict.value.fecha_inicio)} al ${formatDate(areaConflict.value.fecha_fin)}. Las actividades de una misma área no pueden cruzarse en fechas.`;
        form.setError('fecha_inicio', msg);
        return;
    }

    form.post(route('mantenimiento.actividades.store', props.planId), {
        onSuccess: () => {
            closeModal();
        },
        preserveScroll: true
    });
};

const closeModal = () => {
    emit("close");
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="xl">
        <div class="p-6 relative bg-white">
            <button
                @click="closeModal"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
            >
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="size-6 text-ugel-azul" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Agregar Actividad
            </h2>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <InputLabel for="id_oficinas" value="Oficina" />
                    <div class="relative mt-1">
                        <input
                            id="id_oficinas"
                            v-model="searchOficina"
                            type="text"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul shadow-sm"
                            placeholder="Buscar por nombre o área..."
                            :disabled="form.processing"
                            @focus="showDropdown = true"
                            @blur="handleBlur"
                        />
                        <div
                            v-if="showDropdown"
                            class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                        >
                            <div
                                class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                @click="selectOficina(null)"
                            >
                                Seleccione una oficina
                            </div>
                            <div
                                v-for="oficina in filteredOficinas"
                                :key="oficina.id"
                                class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                @click="selectOficina(oficina)"
                            >
                                <div class="font-medium">{{ oficina.nombre }}</div>
                                <div class="text-xs text-gray-500">{{ oficina.area?.nombre || 'Sin área' }}</div>
                            </div>
                            <div v-if="filteredOficinas.length === 0" class="px-4 py-2 text-sm text-gray-500">
                                No se encontraron resultados
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.id_oficinas" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="actividad" value="Actividad" />
                    <TextInput
                        id="actividad"
                        v-model="form.actividad"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        placeholder="Ej. Mantenimiento Preventivo"
                    />
                    <InputError :message="form.errors.actividad" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="fecha_inicio" value="Fecha Inicio" />
                        <TextInput
                            id="fecha_inicio"
                            v-model="form.fecha_inicio"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.fecha_inicio" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="fecha_fin" value="Fecha Fin" />
                        <TextInput
                            id="fecha_fin"
                            v-model="form.fecha_fin"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.fecha_fin" class="mt-2" />
                    </div>
                </div>

                <!-- Alerta visual de cruce de fechas en la misma área -->
                <div v-if="areaConflict" class="p-3.5 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2.5 text-xs text-red-700">
                    <svg class="size-4 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <span class="font-bold">Cruce de fechas detectado:</span> La oficina seleccionada pertenece a <span class="font-semibold">{{ areaConflict.oficina?.area?.nombre || 'la misma área' }}</span>, donde ya está programada la actividad <em>"{{ areaConflict.actividad }}"</em> en <strong>{{ areaConflict.oficina?.nombre }}</strong> del {{ formatDate(areaConflict.fecha_inicio) }} al {{ formatDate(areaConflict.fecha_fin) }}.
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t pt-5 border-gray-100">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                        :disabled="form.processing"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-ugel-azul text-white rounded-lg hover:bg-ugel-guinda font-medium transition-colors flex items-center gap-2"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Guardar Actividad
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
