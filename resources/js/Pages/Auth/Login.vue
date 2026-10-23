<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: null,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const toggleShowPassword = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? "on" : "",
    })).post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Iniciar Sesión - UGEL Huánuco" />

    <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 bg-slate-50 font-sans antialiased text-slate-800">
        <!-- Formulario Centrado Horizontal y Verticalmente -->
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-200/70 p-6 sm:p-8">
            
            <!-- Encabezado Simplificado Centrado -->
            <div class="text-center mb-6">
                <!-- Logo oficial de la UGEL Huánuco -->
                <div class="flex justify-center mb-3">
                    <AuthenticationCardLogo class="h-12 sm:h-14 w-auto object-contain" />
                </div>

                <!-- Subtítulo Sistema de Inventario -->
                <h2 class="text-sm font-semibold text-blue-600 mt-0.5">
                    Sistema de Inventario
                </h2>

                <!-- Descripción breve -->
                <p class="text-xs sm:text-sm text-slate-500 mt-1.5">
                    Ingresa tus credenciales para acceder al sistema.
                </p>
            </div>

            <!-- Mensaje de Estado (si existe) -->
            <div
                v-if="status"
                class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium text-center"
            >
                {{ status }}
            </div>

            <!-- Formulario de Acceso -->
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Campo Correo electrónico -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Correo electrónico
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nombre@ejemplo.com"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"
                        :class="{ 'border-red-500 focus:ring-red-500': form.errors.email }"
                    />
                    <InputError class="mt-1.5 text-xs text-red-600" :message="form.errors.email" />
                </div>

                <!-- Campo Contraseña -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                            Contraseña
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-blue-600 hover:text-blue-700 font-medium hover:underline transition"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full pl-3.5 pr-10 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"
                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.password }"
                        />
                        <button
                            type="button"
                            @click="toggleShowPassword"
                            tabindex="-1"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition focus:outline-none"
                            title="Mostrar u ocultar contraseña"
                        >
                            <svg v-if="showPassword" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                            <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600" :message="form.errors.password" />
                </div>

                <!-- Opción Recordar sesión -->
                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            name="remember"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 size-4 transition cursor-pointer"
                        />
                        <span class="text-xs text-slate-600">
                            Recordar sesión
                        </span>
                    </label>
                </div>

                <!-- Botón Iniciar sesión -->
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin size-4 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Iniciando sesión...' : 'Iniciar sesión' }}</span>
                    </button>
                </div>
            </form>

            <!-- Pie Discreto Institucional -->
            <div class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-400">
                Área de Informática y TIC • © 2026
            </div>
        </div>
    </div>
</template>
