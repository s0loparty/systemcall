<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import { SidebarProvider } from '@/components/ui/sidebar';

import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <div
        v-if="variant === 'header'"
        class="flex min-h-screen w-full flex-col bg-[#fafaf9] dark:bg-neutral-950"
    >
        <slot />
    </div>
    <SidebarProvider
        v-else
        :default-open="isOpen"
        class="min-h-screen bg-[#fbfbff] bg-[radial-gradient(circle_at_18%_0%,rgba(99,102,241,0.08),transparent_28%),radial-gradient(circle_at_80%_100%,rgba(59,130,246,0.06),transparent_30%)] dark:bg-[#0f1015]"
    >
        <slot />
    </SidebarProvider>
</template>
