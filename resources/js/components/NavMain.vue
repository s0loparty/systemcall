<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-3 py-4">
        <SidebarGroupLabel
            class="px-3 text-[0.68rem] font-semibold tracking-[0.18em] text-sidebar-foreground/45 uppercase"
        >
            Навигация
        </SidebarGroupLabel>
        <SidebarMenu class="mt-2 gap-1">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="h-11 rounded-md text-sidebar-foreground/72 transition hover:bg-indigo-50 hover:text-indigo-700 data-[active=true]:bg-indigo-50 data-[active=true]:text-indigo-700 data-[active=true]:shadow-sm dark:hover:bg-indigo-500/10 dark:hover:text-indigo-200 dark:data-[active=true]:bg-indigo-500/15 dark:data-[active=true]:text-indigo-100"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" class="size-4" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
