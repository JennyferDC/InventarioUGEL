<script setup>
import DialogModal from "@/Components/DialogModal.vue";
import { reactive, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    miembro: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "save"]);

const form = reactive({
    id: null,
    name: "",
    email: "",
    password: "",
    rol: "MIEMBRO",
    activo: true,
});

watch(
    () => props.miembro,
    (value) => {
        form.id = value?.id ?? null;
        form.name = value?.name ?? "";
        form.email = value?.email ?? "";
        form.password = "";
        form.rol = value?.rol ?? "MIEMBRO";
        form.activo = value?.activo ?? true;
    },
    { immediate: true },
);

const handleSubmit = () => {
    if (!form.id) return;

    emit("save", {
        id: form.id,
        name: form.name,
        email: form.email,
        password: form.password,
        rol: form.rol,
        activo: form.activo,
    });
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="lg">
        <template #title>
            <span class="text-ugel-guinda font-semibold">Editar miembro</span>
        </template>

        <template #content>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label
                        for="miembro_nombre"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Nombre
                    </label>
                    <input
                        id="miembro_nombre"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Nombre completo"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="miembro_email"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Email
                    </label>
                    <input
                        id="miembro_email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="correo@ejemplo.com"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <label
                        for="miembro_password"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Contraseña (opcional)
                    </label>
                    <input
                        id="miembro_password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                        placeholder="Dejar en blanco para mantener"
                        :disabled="loading"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="miembro_rol"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Rol
                        </label>
                        <select
                            id="miembro_rol"
                            v-model="form.rol"
                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                            :disabled="loading"
                        >
                            <option value="MIEMBRO">MIEMBRO</option>
                            <option value="ADMIN">ADMIN</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="miembro_activo"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Estado
                        </label>
                        <select
                            id="miembro_activo"
                            v-model="form.activo"
                            class="mt-1 block w-full rounded-lg border border-ugel-azul/40 px-3 py-2 text-sm text-gray-700 focus:border-ugel-azul focus:ring-ugel-azul"
                            :disabled="loading"
                        >
                            <option :value="true">Activo</option>
                            <option :value="false">Inactivo</option>
                        </select>
                    </div>
                </div>
            </form>
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
                    loading ||
                    !form.name.trim() ||
                    !form.email.trim() ||
                    !form.rol
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
