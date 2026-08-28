<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

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
    <Head title="Acceso al Sistema - Inventario UGEL" />

    <div class="min-h-screen w-full flex flex-col lg:flex-row bg-slate-950 font-sans text-slate-800 antialiased selection:bg-blue-600 selection:text-white">
        
        <!-- Left Hero Panel: Institutional Branding & Features (visible on lg+) -->
        <div class="relative hidden lg:flex lg:w-1/2 xl:w-7/12 flex-col justify-between overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-12 xl:p-16 border-r border-slate-800/60">
            
            <!-- Ambient Glow & Decorative Grid Effects -->
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 -right-40 w-96 h-96 bg-red-700/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-40 left-1/3 w-80 h-80 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <!-- Subtle Grid Pattern Background -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b15_1px,transparent_1px),linear-gradient(to_bottom,#1e293b15_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] pointer-events-none"></div>

            <!-- Top Header in Hero -->
            <div class="relative z-10">
                <div class="flex items-center gap-3.5">
                    <div class="h-12 w-12 rounded-2xl bg-white p-1.5 shadow-xl shadow-blue-500/10 ring-1 ring-white/20 flex items-center justify-center">
                        <img
                            :src="($page.props.app_url || '') + '/logo.png'"
                            alt="UGEL Huánuco Logo"
                            class="h-full w-full object-contain"
                            @error="(e) => { e.target.style.display = 'none'; }"
                        />
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            <span class="size-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                            Área de Informática y TIC
                        </span>
                        <h2 class="text-lg font-bold text-white tracking-wide">UGEL Huánuco</h2>
                    </div>
                </div>
            </div>

            <!-- Central Content: Hero Title & Key Features -->
            <div class="relative z-10 my-auto py-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white/5 border border-white/10 text-slate-300 text-xs font-medium backdrop-blur-md mb-6">
                    <svg class="size-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Sistema de Gestión e Inventario Institucional
                </div>

                <h1 class="text-3xl xl:text-4xl 2xl:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Control Integral de Equipos y
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-indigo-300 to-sky-400">
                        Tecnología Educativa
                    </span>
                </h1>

                <p class="mt-4 text-base xl:text-lg text-slate-400 max-w-xl leading-relaxed">
                    Plataforma centralizada para la administración, trazabilidad patrimonial, asignación por áreas y programación de mantenimiento técnico de los activos TIC.
                </p>

                <!-- Value Props / Feature Badges -->
                <div class="mt-8 grid grid-cols-1 gap-4 max-w-lg">
                    <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06] backdrop-blur-sm transition hover:bg-white/[0.06]">
                        <div class="size-10 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Inventario TIC & Periféricos</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Control de especificaciones, códigos QR, estados operativos y hojas técnicas.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06] backdrop-blur-sm transition hover:bg-white/[0.06]">
                        <div class="size-10 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Cronograma de Mantenimiento</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Seguimiento de intervenciones preventivas, correctivas y fechas programadas.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06] backdrop-blur-sm transition hover:bg-white/[0.06]">
                        <div class="size-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Asignación por Unidades & Oficinas</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Historial transparente de custodios, actas y desplazamientos de bienes.</p>
                        </div>
                    </div>
                </div>

                <!-- Public Maintenance Quick Access Link -->
                <div class="mt-8 pt-6 border-t border-slate-800/80">
                    <Link
                        :href="route('mantenimiento.public')"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-blue-400 hover:text-blue-300 transition group"
                    >
                        <span class="size-2 rounded-full bg-emerald-400"></span>
                        Consultar Cronograma de Mantenimiento Público
                        <svg class="size-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Footer in Hero -->
            <div class="relative z-10 flex items-center justify-between text-xs text-slate-500 pt-6 border-t border-slate-800/60">
                <span>© 2026 UGEL Huánuco. Todos los derechos reservados.</span>
                <span class="font-mono text-slate-600">v2.4.0</span>
            </div>
        </div>

        <!-- Right Panel: Login Form Container -->
        <div class="w-full lg:w-1/2 xl:w-5/12 flex flex-col justify-between p-6 sm:p-10 md:p-14 lg:p-12 xl:p-16 bg-slate-900/50 lg:bg-slate-950">
            
            <!-- Mobile Brand Header (Visible only on small/medium screens) -->
            <div class="lg:hidden flex flex-col items-center text-center mb-8 pt-4">
                <div class="h-16 w-16 rounded-2xl bg-white p-2 shadow-2xl ring-1 ring-white/20 mb-4 flex items-center justify-center">
                    <img
                        :src="($page.props.app_url || '') + '/logo.png'"
                        alt="Logo UGEL Huánuco"
                        class="h-full w-full object-contain"
                        @error="(e) => { e.target.style.display = 'none'; }"
                    />
                </div>
                <h1 class="text-xl font-bold text-white tracking-tight">Inventario de Informática</h1>
                <p class="text-xs text-slate-400 font-medium mt-1">UGEL Huánuco • Área de TIC</p>
            </div>

            <!-- Spacer for lg alignment -->
            <div class="hidden lg:block"></div>

            <!-- Main Form Card -->
            <div class="w-full max-w-md mx-auto">
                
                <!-- Card Header -->
                <div class="mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/20 text-xs font-semibold mb-3">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Acceso Institucional
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Iniciar Sesión
                    </h2>
                    <p class="text-sm text-slate-400 mt-2">
                        Ingresa tus credenciales autorizadas para acceder a la gestión de inventario.
                    </p>
                </div>

                <!-- Status Flash Alert -->
                <div
                    v-if="status"
                    class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm"
                >
                    <svg class="size-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ status }}</span>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                            Correo Electrónico
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="tu-correo@ugel.gob.pe"
                                class="block w-full pl-11 pr-4 py-3 bg-slate-900/80 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.email }"
                            />
                        </div>
                        <InputError class="mt-2 text-xs" :message="form.errors.email" />
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                                Contraseña
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs font-medium text-blue-400 hover:text-blue-300 transition hover:underline"
                            >
                                ¿Olvidaste tu contraseña?
                            </Link>
                        </div>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••••••"
                                class="block w-full pl-11 pr-11 py-3 bg-slate-900/80 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.password }"
                            />
                            <!-- Toggle Show/Hide Password -->
                            <button
                                type="button"
                                @click="toggleShowPassword"
                                tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200 transition focus:outline-none"
                                title="Mostrar / Ocultar contraseña"
                            >
                                <svg v-if="showPassword" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                                <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-2 text-xs" :message="form.errors.password" />
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                name="remember"
                                class="rounded-md border-slate-700 bg-slate-900 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-950 size-4 transition cursor-pointer"
                            />
                            <span class="text-xs font-medium text-slate-400 group-hover:text-slate-300 transition select-none">
                                Recordar mi sesión
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="relative w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-500 hover:via-blue-600 hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-950 shadow-lg shadow-blue-700/30 hover:shadow-blue-600/40 active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                        >
                            <!-- Spinner when processing -->
                            <svg
                                v-if="form.processing"
                                class="animate-spin -ml-1 mr-2 size-4 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <span v-if="!form.processing">Ingresar al Sistema</span>
                            <span v-else>Verificando credenciales...</span>

                            <svg
                                v-if="!form.processing"
                                class="size-4 transition-transform group-hover:translate-x-0.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Security & Notice Footer in Form -->
                <div class="mt-8 pt-6 border-t border-slate-800/80 flex flex-col items-center text-center gap-2">
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium">
                        <svg class="size-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Conexión segura SSL/TLS • Acceso exclusivo para personal autorizado
                    </div>

                    <!-- Mobile Maintenance Link -->
                    <div class="lg:hidden mt-2">
                        <Link
                            :href="route('mantenimiento.public')"
                            class="text-xs text-blue-400 hover:text-blue-300 underline font-medium"
                        >
                            Ver Cronograma Público de Mantenimiento
                        </Link>
                    </div>
                </div>

            </div>

            <!-- Bottom Support Help info -->
            <div class="mt-8 text-center text-xs text-slate-500">
                ¿Problemas para acceder? Contacta al soporte técnico del Área de Informática.
            </div>

        </div>

    </div>
</template>
