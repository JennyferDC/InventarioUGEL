<script setup>
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
import Banner from "@/Components/Banner.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);

const navigation = computed(() => [
    {
        name: "Inventario",
        href: route("equipos.index"),
        active: route().current("equipos.*"),
        icon: "inventory",
    },
    {
        name: "Áreas",
        href: route("areas.index"),
        active: route().current("areas.*"),
        icon: "areas",
    },
    {
        name: "Personas",
        href: route("personas.index"),
        active: route().current("personas.*"),
        icon: "people",
    },
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
]);

const switchToTeam = (team) => {
    router.put(
        route("current-team.update"),
        {
            team_id: team.id,
        },
        {
            preserveState: false,
        },
    );
};

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-100 md:flex">
            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="fixed inset-0 z-40 bg-black/40 md:hidden"
                @click="showingNavigationDropdown = false"
            />

            <aside
                :class="{
                    'translate-x-0': showingNavigationDropdown,
                    '-translate-x-full': !showingNavigationDropdown,
                }"
                class="fixed inset-y-0 left-0 z-50 w-72 transform bg-white shadow-lg transition-transform duration-200 md:translate-x-0 md:static md:z-auto md:shadow-none md:border-r md:border-ugel-azul/10"
            >
                <div
                    class="flex h-16 items-center justify-between px-4 border-b border-ugel-azul/10"
                >
                    <!-- Logo -->
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center gap-3"
                    >
                        <ApplicationMark class="block h-9 w-auto" />
                        <span class="font-bold text-ugel-guinda tracking-wide"
                            >Inventario</span
                        >
                    </Link>

                    <!-- Hamburger -->
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition md:hidden"
                        @click="showingNavigationDropdown = false"
                    >
                        <svg
                            class="size-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Primary Navigation Menu -->
                <nav class="px-3 py-4 space-y-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition"
                        :class="
                            item.active
                                ? 'bg-ugel-azul text-white shadow-sm'
                                : 'text-ugel-azul hover:bg-ugel-azul/10'
                        "
                    >
                        <span
                            class="inline-flex size-9 items-center justify-center rounded-xl border transition"
                            :class="
                                item.active
                                    ? 'border-white/20 bg-white/10'
                                    : 'border-ugel-azul/20 bg-white group-hover:border-ugel-azul/30'
                            "
                        >
                            <svg
                                v-if="item.icon === 'inventory'"
                                class="size-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-14L4 7m8 4v10m0-10L4 7v10l8 4"
                                />
                            </svg>
                            <svg
                                v-else-if="item.icon === 'areas'"
                                class="size-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 21h16.5M4.5 3h15a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0119.5 21h-15a2.25 2.25 0 01-2.25-2.25V5.25A2.25 2.25 0 014.5 3z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M7.5 7.5h3m-3 3h3m-3 3h3m3-6h3m-3 3h3m-3 3h3"
                                />
                            </svg>
                            <svg
                                v-else-if="item.icon === 'people'"
                                class="size-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 19.128C16.75 18.671 18 17.621 18 16v-1c0-1.657-2.239-3-5-3s-5 1.343-5 3v1c0 1.621 1.25 2.671 3 3.128"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 11a3 3 0 100-6 3 3 0 000 6z"
                                />
                            </svg>
                            <svg
                                v-else-if="item.icon === 'members'"
                                class="size-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M17 20h5V18a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                            <svg
                                v-else-if="item.icon === 'files'"
                                class="size-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"
                                />
                            </svg>
                            <svg
                                v-else
                                class="size-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M18 10c0 2.761-2.686 5-6 5s-6-2.239-6-5 2.686-5 6-5 6 2.239 6 5z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 20c1.6-2.4 3.8-3.6 6-3.6S16.4 17.6 18 20"
                                />
                            </svg>
                        </span>
                        <span class="truncate">{{ item.name }}</span>
                    </Link>
                </nav>

                <div class="mt-auto px-4 pb-6">
                    <!-- Settings Dropdown -->
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    class="w-full flex items-center gap-3 rounded-xl border border-ugel-azul/15 bg-white px-3 py-2 text-left hover:border-ugel-azul/25 transition"
                                    type="button"
                                >
                                    <img
                                        v-if="
                                            $page.props.jetstream
                                                .managesProfilePhotos
                                        "
                                        class="size-9 rounded-full object-cover"
                                        :src="
                                            $page.props.auth.user
                                                .profile_photo_url
                                        "
                                        :alt="$page.props.auth.user.name"
                                    />
                                    <span
                                        v-else
                                        class="inline-flex size-9 items-center justify-center rounded-full bg-ugel-azul/10 text-ugel-azul font-bold"
                                    >
                                        {{
                                            ($page.props.auth.user.name || "U")
                                                .slice(0, 1)
                                                .toUpperCase()
                                        }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate text-sm font-semibold text-ugel-guinda"
                                        >
                                            {{ $page.props.auth.user.name }}
                                        </span>
                                        <span
                                            class="block truncate text-xs text-gray-500"
                                        >
                                            {{ $page.props.auth.user.email }}
                                        </span>
                                    </span>
                                    <svg
                                        class="size-4 text-ugel-azul/70"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <!-- Account Management -->
                                <div
                                    class="block px-4 py-2 text-xs text-gray-400"
                                >
                                    Administrar cuenta
                                </div>

                                <DropdownLink :href="route('profile.show')">
                                    Mi perfil
                                </DropdownLink>

                                <DropdownLink
                                    v-if="$page.props.jetstream.hasApiFeatures"
                                    :href="route('api-tokens.index')"
                                >
                                    API Tokens
                                </DropdownLink>

                                <div class="border-t border-gray-200" />

                                <!-- Authentication -->
                                <form @submit.prevent="logout">
                                    <DropdownLink as="button">
                                        Cerrar sesión
                                    </DropdownLink>
                                </form>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </aside>

            <div class="flex-1 min-w-0">
                <div
                    class="md:hidden flex items-center justify-between h-16 px-4 bg-white border-b border-ugel-azul/10"
                >
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-ugel-azul hover:bg-ugel-azul hover:text-white transition"
                        @click="showingNavigationDropdown = true"
                    >
                        <svg
                            class="size-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                    <div class="font-bold text-ugel-guinda">{{ title }}</div>
                    <div class="size-10" />
                </div>

                <!-- Page Heading -->
                <header v-if="$slots.header" class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page Content -->
                <main>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
