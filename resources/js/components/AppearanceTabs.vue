<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';

import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Светлая' },
    { value: 'dark', Icon: Moon, label: 'Темная' },
    { value: 'system', Icon: Monitor, label: 'Системная' },
] as const;
</script>

<template>
    <div
        class="inline-flex w-full flex-col gap-1 rounded-md border border-slate-200 bg-slate-50 p-1 sm:w-auto sm:flex-row dark:border-neutral-700 dark:bg-neutral-800"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            :aria-pressed="appearance === value"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center justify-center rounded-md px-3.5 py-2 text-sm font-medium transition-colors focus-visible:ring-4 focus-visible:ring-violet-100 focus-visible:outline-none dark:focus-visible:ring-violet-500/20',
                appearance === value
                    ? 'bg-white text-slate-950 shadow-sm dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-slate-500 hover:bg-white/80 hover:text-slate-950 dark:text-neutral-400 dark:hover:bg-neutral-700/60 dark:hover:text-neutral-100',
            ]"
        >
            <component :is="Icon" class="-ml-1 h-4 w-4" />
            <span class="ml-1.5">{{ label }}</span>
        </button>
    </div>
</template>
