<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, RadioTower, Sparkles, Users, Video } from '@lucide/vue';
import { dashboard } from '@/routes';
import { index as roomsIndex } from '@/routes/rooms';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Панель',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Панель" />

    <div class="min-h-full bg-neutral-50 p-4 dark:bg-neutral-950">
        <div class="mx-auto grid max-w-7xl gap-5">
            <section
                class="relative overflow-hidden rounded-md border border-neutral-200 bg-white p-6 shadow-sm md:p-8 dark:border-neutral-800 dark:bg-neutral-950"
            >
                <div
                    class="absolute -top-24 right-0 size-72 rounded-full bg-emerald-400/20 blur-3xl"
                />
                <div class="relative max-w-3xl">
                    <div
                        class="mb-4 flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
                    >
                        <Sparkles class="size-3.5" />
                        Готово к локальной проверке звонков
                    </div>
                    <h1
                        class="text-3xl font-semibold tracking-tight text-neutral-950 md:text-5xl dark:text-white"
                    >
                        Управляйте групповыми звонками из одного места.
                    </h1>
                    <p
                        class="mt-4 max-w-2xl text-sm leading-6 text-neutral-500 md:text-base dark:text-neutral-400"
                    >
                        Создайте комнату, откройте вторую сессию браузера и
                        проверьте LiveKit-медиа, Reverb-события, зал ожидания,
                        модерацию, чат, закрепления и подкомнаты.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="roomsIndex()"
                            class="inline-flex items-center gap-2 rounded-2xl bg-neutral-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                        >
                            Открыть комнаты
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <div
                    class="rounded-[1.5rem] border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                >
                    <Video class="size-6 text-emerald-500" />
                    <p
                        class="mt-4 font-medium text-neutral-950 dark:text-white"
                    >
                        Медиа-комнаты
                    </p>
                    <p
                        class="mt-2 text-sm text-neutral-500 dark:text-neutral-400"
                    >
                        LiveKit отвечает за микрофон, камеру, показ экрана,
                        активного говорящего и WebRTC-маршрутизацию.
                    </p>
                </div>
                <div
                    class="rounded-[1.5rem] border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                >
                    <RadioTower class="size-6 text-blue-500" />
                    <p
                        class="mt-4 font-medium text-neutral-950 dark:text-white"
                    >
                        События приложения
                    </p>
                    <p
                        class="mt-2 text-sm text-neutral-500 dark:text-neutral-400"
                    >
                        Reverb синхронизирует зал ожидания, чат, поднятые руки,
                        общие закрепления, модерацию и события подкомнат.
                    </p>
                </div>
                <div
                    class="rounded-[1.5rem] border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                >
                    <Users class="size-6 text-amber-500" />
                    <p
                        class="mt-4 font-medium text-neutral-950 dark:text-white"
                    >
                        Проверка в браузере
                    </p>
                    <p
                        class="mt-2 text-sm text-neutral-500 dark:text-neutral-400"
                    >
                        Используйте два браузера или приватное окно, чтобы
                        локально проверить владельца и гостевой сценарий.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
