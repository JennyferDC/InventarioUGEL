<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import FeedbackBanner from "@/Components/FeedbackBanner.vue";
import axios from "axios";
import { computed, ref, watch } from "vue";
import ModalEliminar from "./Partials/ModalEliminar.vue";
import ModalEditar from "./Partials/ModalEditar.vue";
import ModalCrear from "./Partials/ModaCrear.vue";

const props = defineProps({
    equipos: {
        type: Array,
        default: () => [],
    },
    personas: {
        type: Array,
        default: () => [],
    },
});

const equipos = ref([...props.equipos]);

watch(
    () => props.equipos,
    (value) => {
        equipos.value = [...value];
    },
    { deep: true }
);

const searchTerm = ref("");
const filtroEstado = ref("todos");

const showDeleteModal = ref(false);
const showEditModal = ref(false);
const showCreateModal = ref(false);
const equipoSeleccionado = ref(null);
const equipoEditando = ref(null);
const deleting = ref(false);
const saving = ref(false);
const creating = ref(false);
const modalCrearRef = ref(null);

const successMessage = ref("");
const errorMessage = ref("");
let feedbackTimeout = null;

const showSuccess = computed(() => Boolean(successMessage.value));
const showError = computed(() => Boolean(errorMessage.value));

const clearFeedbackTimeout = () => {
    if (feedbackTimeout) {
        clearTimeout(feedbackTimeout);
        feedbackTimeout = null;
    }
};

const triggerMessage = (type, message) => {
    clearFeedbackTimeout();

    if (type === "success") {
        successMessage.value = message;
        errorMessage.value = "";
    } else {
        errorMessage.value = message;
        successMessage.value = "";
    }

    feedbackTimeout = setTimeout(() => {
        successMessage.value = "";
        errorMessage.value = "";
    }, 4000);
};

const abrirModalEditar = (equipo) => {
    equipoEditando.value = { ...equipo };
    showEditModal.value = true;
};

const cerrarModalEditar = () => {
    showEditModal.value = false;
    equipoEditando.value = null;
};

const guardarCambios = async (payload) => {
    if (!payload?.id) return;

    saving.value = true;

    try {
        const { data } = await axios.put(
            route("equipos.update", payload.id),
            payload
        );

        const updated = data?.data ?? payload;

        equipos.value = equipos.value.map((equipo) =>
            equipo.id === payload.id ? { ...equipo, ...updated } : equipo
        );
        triggerMessage("success", "Equipo actualizado correctamente.");
        cerrarModalEditar();
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo actualizar el equipo. Revisa los datos."
        );
    } finally {
        saving.value = false;
    }
};

const abrirModalCrear = () => {
    showCreateModal.value = true;
};

const cerrarModalCrear = () => {
    showCreateModal.value = false;
    modalCrearRef.value?.resetForm();
};

const crearEquipo = async (payload) => {
    if (!payload?.cod_informatica || !payload.tipo || !payload.estado) return;

    creating.value = true;

    try {
        const { data } = await axios.post(route("equipos.store"), payload);
        if (data?.data) {
            equipos.value = [...equipos.value, data.data].sort((a, b) =>
                a.cod_informatica.localeCompare(b.cod_informatica)
            );
        }

        triggerMessage("success", "Equipo registrado correctamente.");
        cerrarModalCrear();
    } catch (error) {
        const message =
            error.response?.data?.message ||
            "No se pudo registrar el equipo. Intenta nuevamente.";
        triggerMessage("error", message);
    } finally {
        creating.value = false;
    }
};

const filteredEquipos = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    const estado = filtroEstado.value.toLowerCase();

    return equipos.value.filter((equipo) => {
        const coincideBusqueda =
            !term ||
            equipo.cod_informatica?.toLowerCase().includes(term) ||
            equipo.tipo?.toLowerCase().includes(term) ||
            equipo.persona?.nombre_completo?.toLowerCase().includes(term);

        const coincideEstado =
            estado === "todos" ||
            (equipo.estado ?? "").toLowerCase() === estado;

        return coincideBusqueda && coincideEstado;
    });
});

const hayEquipos = computed(() => filteredEquipos.value.length > 0);

const abrirModalEliminar = (equipo) => {
    equipoSeleccionado.value = equipo;
    showDeleteModal.value = true;
};

const cerrarModalEliminar = () => {
    showDeleteModal.value = false;
    equipoSeleccionado.value = null;
};

const confirmarEliminacion = async () => {
    if (!equipoSeleccionado.value) return;

    deleting.value = true;

    try {
        await axios.delete(
            route("equipos.destroy", equipoSeleccionado.value.id)
        );

        equipos.value = equipos.value.filter(
            (equipo) => equipo.id !== equipoSeleccionado.value.id
        );

        triggerMessage("success", "Equipo eliminado correctamente.");
        cerrarModalEliminar();
    } catch (error) {
        triggerMessage(
            "error",
            error.response?.data?.message ||
                "No se pudo eliminar el equipo. Intenta nuevamente."
        );
    } finally {
        deleting.value = false;
    }
};
</script>

<template>
    <AppLayout title="Inventario">
        <template #header>
            <h2 class="font-bold text-3xl text-ugel-guinda leading-tight">
                Inventario de Equipos
            </h2>
        </template>

        <section class="py-10 space-y-6">
            <div class="max-w-6xl mx-auto px-6 lg:px-0 space-y-2">
                <FeedbackBanner
                    :show="showSuccess"
                    :message="successMessage"
                    type="success"
                />
                <FeedbackBanner
                    :show="showError"
                    :message="errorMessage"
                    type="error"
                />
            </div>

            <div class="max-w-6xl mx-auto px-6 lg:px-0 space-y-6">
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center"
                    >
                        <div class="w-full sm:w-72">
                            <label class="sr-only" for="search-equipo"
                                >Buscar equipo</label
                            >
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-ugel-azul/70"
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
                                            d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"
                                        />
                                    </svg>
                                </span>
                                <input
                                    id="search-equipo"
                                    v-model="searchTerm"
                                    type="text"
                                    placeholder="Buscar por código, tipo o responsable..."
                                    class="w-full rounded-lg border border-ugel-azul/30 pl-10 pr-4 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                                />
                            </div>
                        </div>

                        <div class="w-full sm:w-52">
                            <label class="sr-only" for="filtro-estado"
                                >Estado</label
                            >
                            <select
                                id="filtro-estado"
                                v-model="filtroEstado"
                                class="w-full rounded-lg border border-ugel-azul/30 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                            >
                                <option value="todos">Todos los estados</option>
                                <option value="operativo">Operativo</option>
                                <option value="mantenimiento">
                                    Mantenimiento
                                </option>
                                <option value="baja">De baja</option>
                            </select>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-ugel-azul/30 px-4 py-2 text-ugel-azul font-semibold shadow-sm hover:bg-ugel-azul hover:text-white transition-colors duration-150"
                        @click="abrirModalCrear"
                    >
                        + Nuevo equipo
                    </button>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-ugel-azul/20 bg-white/90 shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ugel-azul/20">
                            <thead class="bg-ugel-azul/5">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Código
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Tipo
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Estado
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Responsable
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Disponible desde
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Vida útil (años)
                                    </th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-ugel-azul"
                                    >
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                v-if="hayEquipos"
                                class="divide-y divide-ugel-azul/10"
                            >
                                <tr
                                    v-for="equipo in filteredEquipos"
                                    :key="equipo.id"
                                    class="hover:bg-ugel-azul/5 transition"
                                >
                                    <td
                                        class="px-6 py-4 text-sm text-gray-700 font-semibold"
                                    >
                                        {{ equipo.cod_informatica }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm text-ugel-guinda"
                                    >
                                        {{ equipo.tipo }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ equipo.estado || "Sin estado" }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{
                                            equipo.persona?.nombre_completo ||
                                            "No asignado"
                                        }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{
                                            equipo.fecha_disponible_uso
                                                ? new Date(
                                                      equipo.fecha_disponible_uso
                                                  ).toLocaleDateString(
                                                      "es-PE",
                                                      {
                                                          year: "numeric",
                                                          month: "short",
                                                          day: "numeric",
                                                      }
                                                  )
                                                : "Sin registro"
                                        }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{
                                            equipo.vida_util_anios ??
                                            "No definido"
                                        }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div
                                            class="flex items-center justify-center gap-3"
                                        >
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full border border-ugel-azul/40 p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                                                @click="
                                                    abrirModalEditar(equipo)
                                                "
                                            >
                                                <svg
                                                    class="size-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652l-1.688 1.687m-2.651-2.651L6.312 17.513a4.5 4.5 0 00-1.053 1.682l-.795 2.385a.563.563 0 00.711.71l2.385-.794a4.5 4.5 0 001.682-1.054L19.513 7.125m-2.651-2.651l2.651 2.651"
                                                    />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-600 hover:text-white transition"
                                                @click="
                                                    abrirModalEliminar(equipo)
                                                "
                                            >
                                                <svg
                                                    class="size-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center text-gray-600"
                                    >
                                        No hay equipos que coincidan con la
                                        búsqueda o filtros aplicados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <ModalEliminar
            :show="showDeleteModal"
            :equipo="equipoSeleccionado"
            :loading="deleting"
            @close="cerrarModalEliminar"
            @confirm="confirmarEliminacion"
        />

        <ModalEditar
            :show="showEditModal"
            :equipo="equipoEditando"
            :personas="personas"
            :loading="saving"
            @close="cerrarModalEditar"
            @save="guardarCambios"
        />

        <ModalCrear
            ref="modalCrearRef"
            :show="showCreateModal"
            :personas="personas"
            :loading="creating"
            @close="cerrarModalCrear"
            @save="crearEquipo"
        />
    </AppLayout>
</template>
