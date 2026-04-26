<script setup>
import { computed, ref, onMounted } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
import Banner from "@/Components/Banner.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);
const isSidebarCollapsed = ref(false);

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const navigationGroups = computed(() => [
    {
        name: "Principal",
        items: [
            {
                name: "Dashboard",
                href: route("dashboard"),
                active: route().current("dashboard"),
                icon: "dashboard",
            },
            {
                name: "Inventario",
                href: route("equipos.index"),
                active: route().current("equipos.*"),
                icon: "inventory",
            },
            {
                name: "Plan de mantenimiento",
                href: route("mantenimiento.index"),
                active: route().current("mantenimiento.*"),
                icon: "maintenance",
            },
        ],
    },
    {
        name: "Organización UGEL",
        items: [
            {
                name: "Áreas",
                href: route("areas.index"),
                active: route().current("areas.*"),
                icon: "areas",
            },
            {
                name: "Oficinas",
                href: route("oficinas.index"),
                active: route().current("oficinas.*"),
                icon: "offices",
            },
            {
                name: "Personas",
                href: route("personas.index"),
                active: route().current("personas.*"),
                icon: "people",
            },
        ],
    },
    {
        name: "Informática",
        items: [
            {
                name: "Miembros",
                href: route("miembros.index"),
                active: route().current("miembros.*"),
                icon: "members",
            },
            {
                name: "Archivos",
                href: route("archivos.index"),
                active: route().current("archivos.*"),
                icon: "files",
            },
        ],
    },
]);

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <!-- Layout Wrapper -->
        <div class="flex h-screen overflow-hidden bg-gray-100">
            
            <!-- Mobile Sidebar Overlay -->
            <div
                v-if="showingNavigationDropdown"
                class="fixed inset-0 z-40 bg-black/40 lg:hidden"
                @click="showingNavigationDropdown = false"
            ></div>

            <!-- Sidebar -->
            <aside
                :class="[
                    showingNavigationDropdown ? 'translate-x-0' : '-translate-x-full',
                    isSidebarCollapsed ? 'lg:w-20' : 'lg:w-72',
                    'fixed inset-y-0 left-0 z-50 w-72 lg:static lg:translate-x-0 flex flex-col bg-white shadow-lg lg:shadow-none border-r border-ugel-azul/10 transition-all duration-300 ease-in-out'
                ]"
            >
                <!-- Sidebar Header (Fixed Height: h-20 = 5rem = 80px) -->
                <div class="h-20 flex items-center px-4 border-b border-ugel-azul/10 shrink-0" :class="isSidebarCollapsed ? 'justify-between lg:justify-center' : 'justify-between'">
                    <Link :href="route('dashboard')" class="items-start gap-1 overflow-hidden" :class="isSidebarCollapsed ? 'flex-col flex lg:hidden' : 'flex-col flex'">
                        <ApplicationMark class="block h-9 w-auto shrink-0" />

                    </Link>
                    <button
                        @click="toggleSidebar"
                        class="hidden lg:flex items-center justify-center p-1.5 rounded-lg text-ugel-azul/50 hover:text-ugel-azul hover:bg-ugel-azul/5 transition"
                    >
                        <svg v-if="!isSidebarCollapsed" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                        <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                    <!-- Mobile close button -->
                    <button
                        class="lg:hidden p-2 text-ugel-azul"
                        @click="showingNavigationDropdown = false"
                    >
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Sidebar Navigation -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden flex flex-col py-4 custom-scrollbar">
                    <nav class="px-3 space-y-4">
                        <div v-for="group in navigationGroups" :key="group.name" class="space-y-1.5">
                            <h3 v-if="!isSidebarCollapsed" class="px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                {{ group.name }}
                            </h3>
                            <div v-else class="my-2 border-t border-gray-200 w-8 mx-auto"></div>
                            
                            <Link
                                v-for="item in group.items"
                                :key="item.name"
                                :href="item.href"
                                class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold transition relative"
                                :class="[
                                    item.active ? 'bg-ugel-azul text-white shadow-sm' : 'text-ugel-azul hover:bg-ugel-azul/10',
                                    isSidebarCollapsed ? 'justify-center gap-0' : 'justify-start gap-3'
                                ]"
                                :title="isSidebarCollapsed ? item.name : ''"
                            >
                                <span
                                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg transition"
                                    :class="item.active ? 'bg-white/20' : 'bg-transparent'"
                                >
                                    <svg v-if="item.icon === 'dashboard'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                                    <svg v-else-if="item.icon === 'inventory'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-14L4 7m8 4v10m0-10L4 7v10l8 4"/></svg>
                                    <svg v-else-if="item.icon === 'areas'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0119.5 21h-15a2.25 2.25 0 01-2.25-2.25V5.25A2.25 2.25 0 014.5 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h3m-3 3h3m-3 3h3m3-6h3m-3 3h3m-3 3h3"/></svg>
                                    <svg v-else-if="item.icon === 'offices'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    <svg v-else-if="item.icon === 'people'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128C16.75 18.671 18 17.621 18 16v-1c0-1.657-2.239-3-5-3s-5 1.343-5 3v1c0 1.621 1.25 2.671 3 3.128"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11a3 3 0 100-6 3 3 0 000 6z"/></svg>
                                    <svg v-else-if="item.icon === 'members'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V18a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <svg v-else-if="item.icon === 'files'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                    <svg v-else-if="item.icon === 'maintenance'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 10c0 2.761-2.686 5-6 5s-6-2.239-6-5 2.686-5 6-5 6 2.239 6 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 20c1.6-2.4 3.8-3.6 6-3.6S16.4 17.6 18 20"/></svg>
                                </span>
                                <span 
                                    class="whitespace-nowrap transition-all duration-300"
                                    :class="isSidebarCollapsed ? 'opacity-0 w-0 hidden lg:block' : 'opacity-100 w-auto'"
                                >
                                    {{ item.name }}
                                </span>
                            </Link>
                        </div>
                    </nav>

                    <!-- Logout Button -->
                    <div class="mt-auto px-3 mt-2">
                        <form @submit.prevent="logout" class="w-full">
                            <button 
                                type="submit" 
                                class="w-full flex items-center rounded-lg bg-red-50 hover:bg-red-100 text-red-600 py-2 px-2 text-sm font-semibold transition shadow-sm border border-red-100"
                                :class="isSidebarCollapsed ? 'justify-center' : 'justify-center gap-2'"
                                :title="isSidebarCollapsed ? 'Cerrar sesión' : ''"
                            >
                                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span 
                                    class="whitespace-nowrap transition-all duration-300"
                                    :class="isSidebarCollapsed ? 'opacity-0 w-0 hidden lg:block' : 'opacity-100 w-auto'"
                                >
                                    Cerrar sesión
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- User Profile / Settings -->
                <div class="flex flex-col px-4 py-6 border-t border-ugel-azul/10">
                    <div
                        class="w-full flex items-center gap-3 px-1 text-left"
                        :class="isSidebarCollapsed ? 'justify-center' : 'justify-start'"
                    >
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-ugel-azul/10 text-ugel-azul font-bold ring-2 ring-white">
                            {{ ($page.props.auth.user.name || "U").slice(0, 1).toUpperCase() }}
                        </span>
                        <span 
                            class="min-w-0 flex-1 transition-all duration-300"
                            :class="isSidebarCollapsed ? 'opacity-0 w-0 hidden lg:block' : 'opacity-100 w-auto'"
                        >
                            <span class="block truncate text-sm font-semibold text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </span>
                            <span class="block truncate text-[11px] text-gray-500 font-medium">
                                {{ $page.props.auth.user.email }}
                            </span>
                        </span>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-gray-50/50">
                
                <!-- Top Header (Fixed Height: h-20 = 5rem = 80px) -->
                <header class="h-20 bg-white border-b border-gray-200/80 shadow-sm shrink-0 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 transition-all duration-300">
                    <div class="flex items-center gap-4 w-full">
                        <button
                            class="lg:hidden p-2 -ml-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                            @click="showingNavigationDropdown = true"
                        >
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div class="flex-1 min-w-0">
                            <slot name="header" />
                        </div>
                    </div>
                </header>

                <!-- Page Content (Scrollable) -->
                <main class="flex-1 overflow-y-auto overflow-x-hidden relative">
                    <div class="absolute inset-0">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
}
</style>
