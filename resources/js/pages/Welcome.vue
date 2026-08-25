<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    MessageSquare,
    RadioTower,
    ShieldCheck,
    Sparkles,
    Users,
    Video,
} from '@lucide/vue';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';
import { index as roomsIndex } from '@/routes/rooms';

const page = usePage();
const user = computed(() => page.props.auth.user);

const features = [
    {
        icon: Video,
        title: 'Комнаты по ссылке',
        description:
            'Создавайте встречи за пару секунд и приглашайте участников без лишней настройки.',
    },
    {
        icon: ShieldCheck,
        title: 'Контроль доступа',
        description:
            'Используйте пароль, зал ожидания и модерацию, чтобы управлять входом.',
    },
    {
        icon: MessageSquare,
        title: 'Совместная работа',
        description:
            'Общайтесь в чате, поднимайте руки, закрепляйте участников и показывайте экран.',
    },
];
</script>

<template>
    <Head title="Systemcall" />

    <main
        class="min-h-screen overflow-hidden bg-[#fbfaff] text-slate-950 selection:bg-violet-200"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_16%,rgba(124,92,255,0.16),transparent_28%),radial-gradient(circle_at_82%_8%,rgba(59,130,246,0.13),transparent_25%),radial-gradient(circle_at_52%_82%,rgba(16,185,129,0.10),transparent_24%)]"
        />

        <div
            class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-5 py-6 sm:px-8"
        >
            <header class="flex items-center justify-between gap-4">
                <Link
                    :href="user ? dashboard() : '/'"
                    class="flex items-center gap-3"
                >
                    <div
                        class="flex size-11 items-center justify-center rounded-md bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-200"
                    >
                        <RadioTower class="size-5" />
                    </div>
                    <div>
                        <p class="leading-none font-semibold">Systemcall</p>
                        <p class="mt-1 text-xs text-slate-500">
                            групповые видеокомнаты
                        </p>
                    </div>
                </Link>

                <nav class="flex items-center gap-2">
                    <template v-if="user">
                        <Link
                            :href="roomsIndex()"
                            class="inline-flex items-center gap-2 rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800"
                        >
                            Комнаты
                            <ArrowRight class="size-4" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-white hover:text-slate-950"
                        >
                            Войти
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800"
                        >
                            Регистрация
                        </Link>
                    </template>
                </nav>
            </header>

            <section
                class="grid flex-1 items-center gap-12 py-14 lg:grid-cols-[1.02fr_0.98fr] lg:py-20"
            >
                <div>
                    <div
                        class="mb-6 inline-flex items-center gap-2 rounded-md border border-emerald-100 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 shadow-sm"
                    >
                        <span class="size-2 rounded-full bg-emerald-500" />
                        Готово для командных встреч
                    </div>

                    <h1
                        class="max-w-4xl text-5xl font-semibold tracking-tight text-balance sm:text-6xl lg:text-7xl"
                    >
                        Видеозвонки, в которые легко пригласить команду и
                        гостей.
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                        Создавайте комнаты, проверяйте устройства перед входом,
                        управляйте доступом и проводите встречи без сложной
                        подготовки.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="user ? roomsIndex() : register()"
                            class="inline-flex items-center gap-2 rounded-md bg-gradient-to-r from-violet-600 to-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-200 transition hover:-translate-y-0.5"
                        >
                            {{ user ? 'Открыть комнаты' : 'Создать комнату' }}
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            :href="user ? dashboard() : login()"
                            class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300"
                        >
                            {{ user ? 'Панель управления' : 'Войти' }}
                        </Link>
                    </div>
                </div>

                <div
                    class="rounded-md border border-slate-200/80 bg-white/85 p-4 shadow-2xl shadow-violet-100/70 backdrop-blur"
                >
                    <div
                        class="rounded-md border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-lg font-semibold">
                                    Командная встреча
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Подготовка устройств · зал ожидания · чат
                                </p>
                            </div>
                            <span
                                class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                            >
                                идет звонок
                            </span>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div
                                class="aspect-video rounded-md bg-gradient-to-br from-slate-950 to-slate-800 p-4 text-white shadow-inner"
                            >
                                <Video class="size-5 text-violet-200" />
                                <div class="mt-16">
                                    <p class="font-semibold">
                                        Анна делится экраном
                                    </p>
                                    <p class="mt-1 text-xs text-slate-300">
                                        видят все участники
                                    </p>
                                </div>
                            </div>
                            <div
                                class="aspect-video rounded-md border border-slate-200 bg-slate-50 p-4"
                            >
                                <Users class="size-5 text-slate-500" />
                                <div class="mt-16">
                                    <p class="font-semibold">
                                        Гости подключены
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        доступ подтвержден
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-md border border-slate-200 bg-white p-4"
                            >
                                <CheckCircle2 class="size-5 text-emerald-500" />
                                <p class="mt-3 text-sm font-semibold">
                                    Устройства
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    проверка перед входом
                                </p>
                            </div>
                            <div
                                class="rounded-md border border-slate-200 bg-white p-4"
                            >
                                <Sparkles class="size-5 text-violet-500" />
                                <p class="mt-3 text-sm font-semibold">
                                    Модерация
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    роли и действия
                                </p>
                            </div>
                            <div
                                class="rounded-md border border-slate-200 bg-white p-4"
                            >
                                <CalendarDays class="size-5 text-blue-500" />
                                <p class="mt-3 text-sm font-semibold">
                                    Встречи
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    быстрый старт
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 pb-10 md:grid-cols-3">
                <article
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-md border border-slate-200/80 bg-white/80 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg hover:shadow-violet-100"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-md bg-violet-50 text-violet-600"
                    >
                        <component :is="feature.icon" class="size-5" />
                    </div>
                    <h2 class="mt-5 text-lg font-semibold">
                        {{ feature.title }}
                    </h2>
                    <p class="mt-2 leading-7 text-slate-600">
                        {{ feature.description }}
                    </p>
                </article>
            </section>
        </div>
    </main>
</template>
