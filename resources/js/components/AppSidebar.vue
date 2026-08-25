<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Video } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as roomsIndex } from '@/routes/rooms';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Панель',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Комнаты',
        href: roomsIndex(),
        icon: Video,
    },
];
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="border-r border-black/5 bg-white/86 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-950/86"
    >
        <SidebarHeader class="px-3 pt-4 pb-2">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="gap-3 px-3 pb-4">
            <div
                class="rounded-md border border-indigo-100 bg-indigo-50/70 p-3 text-xs text-indigo-950 group-data-[collapsible=icon]:hidden dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-100"
            >
                <p class="font-medium">Готовы к звонку</p>
                <p class="mt-1 text-indigo-900/70 dark:text-indigo-100/70">
                    Создайте комнату и отправьте ссылку участникам.
                </p>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
