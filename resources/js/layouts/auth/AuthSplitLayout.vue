<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

import { CheckCircle2, RadioTower, ShieldCheck, Video } from '@lucide/vue';

import AppLogoIcon from '@/components/AppLogoIcon.vue';

import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid min-h-dvh overflow-hidden bg-[#fbfaff] px-6 text-slate-950 sm:px-8 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_18%,rgba(124,92,255,0.16),transparent_28%),radial-gradient(circle_at_82%_12%,rgba(59,130,246,0.12),transparent_24%)]"
        />

        <div
            class="relative hidden h-full flex-col justify-between border-r border-slate-200/80 bg-white/70 p-10 lg:flex"
        >
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-semibold"
            >
                <span
                    class="mr-3 flex size-10 items-center justify-center rounded-md bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-200"
                >
                    <AppLogoIcon class="size-5 fill-current text-white" />
                </span>
                {{ name }}
            </Link>

            <div class="relative z-20 max-w-md">
                <div
                    class="inline-flex items-center gap-2 rounded-md border border-emerald-100 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 shadow-sm"
                >
                    <span class="size-2 rounded-full bg-emerald-500" />
                    Рабочее пространство встреч
                </div>
                <h2 class="mt-6 text-4xl font-semibold tracking-tight">
                    Комнаты, гости и доступ под контролем.
                </h2>
                <p class="mt-4 leading-7 text-slate-600">
                    Создавайте звонки, приглашайте участников и управляйте
                    встречами из одного аккаунта.
                </p>
            </div>

            <div class="relative z-20 grid grid-cols-3 gap-3">
                <div class="rounded-md border border-slate-200 bg-white p-4">
                    <Video class="size-5 text-violet-600" />
                    <p class="mt-3 text-sm font-semibold">Комнаты</p>
                </div>
                <div class="rounded-md border border-slate-200 bg-white p-4">
                    <ShieldCheck class="size-5 text-blue-600" />
                    <p class="mt-3 text-sm font-semibold">Доступ</p>
                </div>
                <div class="rounded-md border border-slate-200 bg-white p-4">
                    <RadioTower class="size-5 text-emerald-600" />
                    <p class="mt-3 text-sm font-semibold">Связь</p>
                </div>
            </div>
        </div>

        <div class="relative z-10 flex items-center lg:p-8">
            <div
                class="mx-auto flex w-full max-w-md flex-col justify-center rounded-md border border-slate-200/80 bg-white/90 p-6 shadow-2xl shadow-violet-100/70 backdrop-blur sm:p-8"
            >
                <div class="mb-7 flex flex-col space-y-2 text-center">
                    <CheckCircle2 class="mx-auto size-5 text-emerald-500" />
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
