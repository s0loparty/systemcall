<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Lock, RadioTower, Users, Video } from '@lucide/vue';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';
import { index as roomsIndex } from '@/routes/rooms';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Systemcall" />

    <main
        class="min-h-screen overflow-hidden bg-neutral-950 text-white selection:bg-emerald-300 selection:text-neutral-950"
    >
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(16,185,129,0.22),transparent_32%),radial-gradient(circle_at_80%_0%,rgba(59,130,246,0.20),transparent_30%),linear-gradient(135deg,rgba(255,255,255,0.06),transparent_45%)]"
        />
        <div
            class="absolute inset-x-0 bottom-0 h-80 bg-gradient-to-t from-neutral-950 to-transparent"
        />

        <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col p-6">
            <header class="flex items-center justify-between gap-4">
                <Link
                    :href="user ? dashboard() : '/'"
                    class="flex items-center gap-3"
                >
                    <div
                        class="flex size-10 items-center justify-center rounded-2xl bg-white text-neutral-950"
                    >
                        <RadioTower class="size-5" />
                    </div>
                    <div>
                        <p class="leading-none font-semibold">Systemcall</p>
                        <p class="mt-1 text-xs text-neutral-400">
                            group video rooms
                        </p>
                    </div>
                </Link>

                <nav class="flex items-center gap-2">
                    <template v-if="user">
                        <Link
                            :href="roomsIndex()"
                            class="rounded-full bg-white px-4 py-2 text-sm font-medium text-neutral-950 transition hover:bg-neutral-200"
                        >
                            Rooms
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-full px-4 py-2 text-sm font-medium text-neutral-200 transition hover:bg-white/10 hover:text-white"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-full bg-white px-4 py-2 text-sm font-medium text-neutral-950 transition hover:bg-neutral-200"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </header>

            <section
                class="grid flex-1 items-center gap-10 py-16 lg:grid-cols-[1.05fr_0.95fr]"
            >
                <div>
                    <div
                        class="mb-6 flex w-fit items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-medium text-emerald-100"
                    >
                        <span class="size-2 rounded-full bg-emerald-400" />
                        Local smoke ready with Sail + LiveKit
                    </div>
                    <h1
                        class="max-w-4xl text-5xl font-semibold tracking-tight md:text-7xl"
                    >
                        Video calls you can actually test today.
                    </h1>
                    <p
                        class="mt-6 max-w-2xl text-base leading-7 text-neutral-300 md:text-lg"
                    >
                        Create rooms, join as a guest, approve waiting-room
                        entries, moderate participants, chat, raise hands, pin
                        speakers and switch LiveKit subrooms.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="user ? roomsIndex() : register()"
                            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-neutral-950 transition hover:bg-emerald-300"
                        >
                            {{ user ? 'Open rooms' : 'Start testing' }}
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            :href="user ? dashboard() : login()"
                            class="inline-flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/15"
                        >
                            {{ user ? 'Dashboard' : 'Log in' }}
                        </Link>
                    </div>
                </div>

                <div
                    class="rounded-[2rem] border border-white/10 bg-white/10 p-4 shadow-2xl shadow-black/30 backdrop-blur"
                >
                    <div class="grid gap-3">
                        <div
                            class="rounded-[1.5rem] bg-neutral-950/80 p-4 ring-1 ring-white/10"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium">Local demo room</p>
                                    <p class="mt-1 text-xs text-neutral-400">
                                        Main room · Subroom A · Subroom B
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-emerald-400 px-2.5 py-1 text-xs font-semibold text-neutral-950"
                                >
                                    live
                                </span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div
                                    class="aspect-video rounded-2xl bg-gradient-to-br from-emerald-300 to-blue-400 p-3 text-neutral-950"
                                >
                                    <Video class="size-5" />
                                    <p class="mt-10 text-sm font-semibold">
                                        Owner camera
                                    </p>
                                </div>
                                <div
                                    class="aspect-video rounded-2xl bg-neutral-800 p-3"
                                >
                                    <Users class="size-5 text-neutral-300" />
                                    <p class="mt-10 text-sm font-semibold">
                                        Guest joined
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-2xl border border-white/10 bg-white/10 p-4"
                            >
                                <Video class="size-5 text-emerald-300" />
                                <p class="mt-3 text-sm font-medium">Media</p>
                                <p class="mt-1 text-xs text-neutral-400">
                                    LiveKit WebRTC
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/10 p-4"
                            >
                                <RadioTower class="size-5 text-blue-300" />
                                <p class="mt-3 text-sm font-medium">Realtime</p>
                                <p class="mt-1 text-xs text-neutral-400">
                                    Reverb events
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/10 p-4"
                            >
                                <Lock class="size-5 text-amber-300" />
                                <p class="mt-3 text-sm font-medium">Access</p>
                                <p class="mt-1 text-xs text-neutral-400">
                                    Laravel auth
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
