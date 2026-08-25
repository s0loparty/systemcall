<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    RadioTower,
    ShieldCheck,
    Video,
} from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col overflow-hidden bg-[#fbfaff] text-slate-950"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(124,92,255,0.18),transparent_28%),radial-gradient(circle_at_82%_8%,rgba(59,130,246,0.13),transparent_24%),radial-gradient(circle_at_50%_88%,rgba(16,185,129,0.10),transparent_24%)]"
        />

        <header
            class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-5 py-6 sm:px-8"
        >
            <Link
                :href="home()"
                class="inline-flex items-center gap-2 rounded-md px-2 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white/80 hover:text-slate-950"
            >
                <ArrowLeft class="size-4" />
                На главную
            </Link>

            <Link :href="home()" class="flex items-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-md bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-200"
                >
                    <AppLogoIcon class="size-5" />
                </div>
                <span class="sr-only">Systemcall</span>
            </Link>
        </header>

        <main
            class="relative z-10 mx-auto grid w-full max-w-6xl flex-1 items-center gap-8 px-5 pb-10 sm:px-8 lg:grid-cols-[0.95fr_1.05fr]"
        >
            <section class="hidden lg:block">
                <div
                    class="inline-flex items-center gap-2 rounded-md border border-emerald-100 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 shadow-sm"
                >
                    <span class="size-2 rounded-full bg-emerald-500" />
                    Безопасный вход
                </div>

                <h1
                    class="mt-6 max-w-lg text-5xl font-semibold tracking-tight text-balance"
                >
                    Управляйте встречами в одном рабочем пространстве.
                </h1>

                <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">
                    Войдите, чтобы создавать комнаты, приглашать гостей и
                    контролировать доступ к звонкам.
                </p>

                <div class="mt-8 grid max-w-xl gap-3 sm:grid-cols-3">
                    <div
                        class="rounded-md border border-slate-200 bg-white/75 p-4 shadow-sm"
                    >
                        <Video class="size-5 text-violet-600" />
                        <p class="mt-3 text-sm font-semibold">Комнаты</p>
                    </div>
                    <div
                        class="rounded-md border border-slate-200 bg-white/75 p-4 shadow-sm"
                    >
                        <ShieldCheck class="size-5 text-blue-600" />
                        <p class="mt-3 text-sm font-semibold">Доступ</p>
                    </div>
                    <div
                        class="rounded-md border border-slate-200 bg-white/75 p-4 shadow-sm"
                    >
                        <RadioTower class="size-5 text-emerald-600" />
                        <p class="mt-3 text-sm font-semibold">Связь</p>
                    </div>
                </div>

                <div
                    class="mt-8 inline-flex items-center gap-2 text-sm font-medium text-slate-500"
                >
                    <CheckCircle2 class="size-4 text-emerald-500" />
                    Камера и микрофон выбираются перед входом в комнату.
                </div>
            </section>

            <section class="mx-auto w-full max-w-md">
                <div class="mb-8 text-center lg:hidden">
                    <div
                        class="mx-auto flex size-14 items-center justify-center rounded-md bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-200"
                    >
                        <AppLogoIcon class="size-7" />
                    </div>
                </div>

                <div
                    class="rounded-md border border-slate-200/80 bg-white/90 p-6 shadow-2xl shadow-violet-100/70 backdrop-blur sm:p-8"
                >
                    <div class="mb-7 space-y-2 text-center">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ title }}
                        </h1>
                        <p class="text-sm leading-6 text-slate-500">
                            {{ description }}
                        </p>
                    </div>

                    <slot />
                </div>
            </section>
        </main>
    </div>
</template>
