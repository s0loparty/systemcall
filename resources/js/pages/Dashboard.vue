<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, RadioTower, Sparkles, Users, Video } from '@lucide/vue';
import { dashboard } from '@/routes';
import { index as roomsIndex } from '@/routes/rooms';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-full bg-neutral-50 p-4 dark:bg-neutral-950">
        <div class="mx-auto grid max-w-7xl gap-5">
            <section
                class="relative overflow-hidden rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-sm md:p-8 dark:border-neutral-800 dark:bg-neutral-950"
            >
                <div
                    class="absolute -top-24 right-0 size-72 rounded-full bg-emerald-400/20 blur-3xl"
                />
                <div class="relative max-w-3xl">
                    <div
                        class="mb-4 flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
                    >
                        <Sparkles class="size-3.5" />
                        Ready for local call testing
                    </div>
                    <h1
                        class="text-3xl font-semibold tracking-tight text-neutral-950 md:text-5xl dark:text-white"
                    >
                        Run group calls from one place.
                    </h1>
                    <p
                        class="mt-4 max-w-2xl text-sm leading-6 text-neutral-500 md:text-base dark:text-neutral-400"
                    >
                        Create a room, invite another browser session and test
                        the implemented milestones: LiveKit media, Reverb
                        realtime state, waiting room, moderation, chat, pins and
                        subrooms.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="roomsIndex()"
                            class="inline-flex items-center gap-2 rounded-2xl bg-neutral-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                        >
                            Open rooms
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
                        Media rooms
                    </p>
                    <p
                        class="mt-2 text-sm text-neutral-500 dark:text-neutral-400"
                    >
                        LiveKit owns mic, camera, screen share, active speaker
                        and WebRTC media routing.
                    </p>
                </div>
                <div
                    class="rounded-[1.5rem] border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                >
                    <RadioTower class="size-6 text-blue-500" />
                    <p
                        class="mt-4 font-medium text-neutral-950 dark:text-white"
                    >
                        Application realtime
                    </p>
                    <p
                        class="mt-2 text-sm text-neutral-500 dark:text-neutral-400"
                    >
                        Reverb syncs waiting room, chat, hand state, global pin,
                        moderation and subroom events.
                    </p>
                </div>
                <div
                    class="rounded-[1.5rem] border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                >
                    <Users class="size-6 text-amber-500" />
                    <p
                        class="mt-4 font-medium text-neutral-950 dark:text-white"
                    >
                        Browser smoke flow
                    </p>
                    <p
                        class="mt-2 text-sm text-neutral-500 dark:text-neutral-400"
                    >
                        Use two browsers or a private window to validate owner
                        and guest behavior locally.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
