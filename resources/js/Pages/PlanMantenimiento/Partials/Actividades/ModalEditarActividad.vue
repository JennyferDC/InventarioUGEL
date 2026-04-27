<script setup>
import { ref, watch, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const page = usePage();

const props = defineProps({
    show: Boolean,
    item: {
        type: Object,
        default: () => null
    },
    oficinas: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(["close"]);

const form = useForm({
    id_oficinas: "",
    actividad: "",
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

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.item) {
            form.id_oficinas = props.item.id_oficinas;
            form.actividad = props.item.actividad;
            form.fecha_inicio = props.item.fecha_inicio;
            form.fecha_fin = props.item.fecha_fin;
            form.clearErrors();
            
            const selectedOficina = props.oficinas.find(o => o.id === props.item.id_oficinas);
            if (selectedOficina) {
                searchOficina.value = selectedOficina.nombre;
            } else {
                searchOficina.value = "";
            }
        }
    }
);

const submit = () => {
    if (!props.item) return;
    
    form.put(route('mantenimiento.actividades.update', props.item.id), {
        onSuccess: () => {
            closeModal();
            if (!page.props.jetstream) page.props.jetstream = { flash: {} };
            page.props.jetstream.flash = {
                bannerStyle: 'success',
                banner: 'Actividad actualizada exitosamente.',
            };
        },
        onError: () => {
            if (!page.props.jetstream) page.props.jetstream = { flash: {} };
            page.props.jetstream.flash = {
                bannerStyle: 'danger',
                banner: 'Ocurrió un error al actualizar la actividad.',
            };
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Editar Actividad
            </h2>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <InputLabel for="edit_id_oficinas" value="Oficina" />
                    <div class="relative mt-1">
                        <input
                            id="edit_id_oficinas"
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
                    <InputLabel for="edit_actividad" value="Actividad" />
                    <TextInput
                        id="edit_actividad"
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
                        <InputLabel for="edit_fecha_inicio" value="Fecha Inicio" />
                        <TextInput
                            id="edit_fecha_inicio"
                            v-model="form.fecha_inicio"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.fecha_inicio" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="edit_fecha_fin" value="Fecha Fin" />
                        <TextInput
                            id="edit_fecha_fin"
                            v-model="form.fecha_fin"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.fecha_fin" class="mt-2" />
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
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
