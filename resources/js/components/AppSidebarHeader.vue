<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';

import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-black/5 bg-white/72 px-4 backdrop-blur-xl transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6 dark:border-white/10 dark:bg-neutral-950/70"
    >
        <div class="flex min-w-0 items-center gap-3">
            <SidebarTrigger
                class="-ml-1 rounded-md border border-border/70 bg-white shadow-sm hover:bg-indigo-50 hover:text-indigo-700 dark:bg-neutral-950 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-200"
            />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>
