<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { ArrowLeft } from '@lucide/vue';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-[#fbfaff] p-6 text-slate-950 md:p-10"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_22%_18%,rgba(124,92,255,0.16),transparent_28%),radial-gradient(circle_at_82%_10%,rgba(59,130,246,0.12),transparent_24%)]"
        />

        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="relative z-10 flex items-center gap-2 self-center rounded-md px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-white/80 hover:text-slate-950"
            >
                <ArrowLeft class="size-4" />
                <span>На главную</span>
                <div
                    class="ml-2 flex size-9 items-center justify-center rounded-md bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-200"
                >
                    <AppLogoIcon
                        class="size-5 fill-current text-white dark:text-white"
                    />
                </div>
            </Link>

            <div class="relative z-10 flex flex-col gap-6">
                <Card
                    class="rounded-md border-slate-200/80 bg-white/90 shadow-2xl shadow-violet-100/70 backdrop-blur"
                >
                    <CardHeader class="px-8 pt-8 pb-0 text-center">
                        <CardTitle class="text-2xl">{{ title }}</CardTitle>
                        <CardDescription class="leading-6">
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-8 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
