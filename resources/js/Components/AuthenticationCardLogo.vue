<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <Link :href="'/'">
        <img
            :src="$page.props.app_url + '/logo.png'"
            alt="Logo Ugel Huánuco - Inventario"
            class="h-20 w-auto"
        />
    </Link>
</template>
