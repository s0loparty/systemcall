<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div
        class="flex aspect-square size-9 items-center justify-center rounded-md bg-linear-to-br from-indigo-500 to-violet-600 text-white shadow-sm shadow-indigo-500/25"
    >
        <AppLogoIcon class="size-5 fill-current" />
    </div>
    <div class="ml-1 grid flex-1 text-left">
        <span class="truncate text-sm leading-tight font-semibold">
            {{ name }}
        </span>
        <span class="truncate text-xs text-sidebar-foreground/55">
            Видеокомнаты
        </span>
    </div>
</template>
