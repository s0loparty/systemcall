<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    Clock3,
    MessageSquare,
    Plus,
    ShieldCheck,
    Sparkles,
    Users,
    Video,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

const featureCards = [
    {
        icon: Video,
        title: 'Видеокомнаты',
        description:
            'Создавайте комнаты, приглашайте участников и проверяйте устройства перед входом.',
    },
    {
        icon: ShieldCheck,
        title: 'Доступ под контролем',
        description:
            'Настраивайте приватность, пароль и зал ожидания для каждой встречи.',
    },
    {
        icon: MessageSquare,
        title: 'Работа во время звонка',
        description:
            'Используйте чат, поднятые руки, закрепления участников и показ экрана.',
    },
];

const quickActions = [
    {
        icon: Plus,
        title: 'Создать комнату',
        description: 'Откройте раздел комнат и запустите новую встречу.',
    },
    {
        icon: Users,
        title: 'Пригласить гостей',
        description:
            'Поделитесь ссылкой и пустите участников через подготовку.',
    },
    {
        icon: Clock3,
        title: 'Проверить встречу',
        description:
            'Зайдите в комнату заранее и проверьте камеру с микрофоном.',
    },
];
</script>

<template>
    <Head title="Панель" />

    <div class="p-3 sm:p-5">
        <div class="mx-auto grid max-w-7xl gap-5">
            <Card
                class="relative overflow-hidden rounded-md border-slate-200/80 bg-white/90 shadow-sm"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_78%_10%,rgba(124,92,255,0.18),transparent_30%),radial-gradient(circle_at_90%_70%,rgba(59,130,246,0.12),transparent_24%)]"
                />

                <CardContent
                    class="relative grid gap-8 p-6 md:p-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center"
                >
                    <div>
                        <Badge
                            variant="outline"
                            class="rounded-md border-emerald-100 bg-emerald-50 px-3 py-1.5 text-emerald-700"
                        >
                            <span class="size-2 rounded-full bg-emerald-500" />
                            Готово к встречам
                        </Badge>

                        <h1
                            class="mt-5 max-w-3xl text-4xl font-semibold tracking-tight text-balance text-slate-950 md:text-5xl"
                        >
                            Управляйте групповыми звонками из одного места.
                        </h1>

                        <p
                            class="mt-4 max-w-2xl text-base leading-7 text-slate-600"
                        >
                            Создавайте комнаты, приглашайте команду и гостей,
                            управляйте доступом и проводите встречи без сложной
                            подготовки.
                        </p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            <Button
                                as-child
                                class="h-11 rounded-md bg-gradient-to-r from-violet-600 to-blue-600 px-5 text-white shadow-lg shadow-violet-100 hover:from-violet-500 hover:to-blue-500"
                            >
                                <Link :href="roomsIndex()">
                                    Открыть комнаты
                                    <ArrowRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div
                        class="rounded-md border border-slate-200 bg-white/90 p-4 shadow-lg shadow-violet-100/60"
                    >
                        <div
                            class="rounded-md bg-gradient-to-br from-slate-950 to-slate-800 p-5 text-white"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold">
                                        Командная комната
                                    </p>
                                    <p class="mt-1 text-sm text-slate-300">
                                        звонок для команды и гостей
                                    </p>
                                </div>
                                <Badge
                                    class="rounded-md bg-emerald-400 text-slate-950 hover:bg-emerald-400"
                                >
                                    активна
                                </Badge>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div
                                    class="rounded-md border border-white/10 bg-white/10 p-4"
                                >
                                    <Users class="size-5 text-violet-200" />
                                    <p class="mt-8 text-sm font-semibold">
                                        Участники готовы
                                    </p>
                                </div>
                                <div
                                    class="rounded-md border border-white/10 bg-white/10 p-4"
                                >
                                    <CheckCircle2
                                        class="size-5 text-emerald-200"
                                    />
                                    <p class="mt-8 text-sm font-semibold">
                                        Доступ настроен
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <section class="grid gap-4 md:grid-cols-3">
                <Card
                    v-for="feature in featureCards"
                    :key="feature.title"
                    class="rounded-md border-slate-200/80 bg-white/90 shadow-sm transition hover:-translate-y-1 hover:shadow-lg hover:shadow-violet-100"
                >
                    <CardHeader>
                        <div
                            class="flex size-11 items-center justify-center rounded-md bg-violet-50 text-violet-600"
                        >
                            <component :is="feature.icon" class="size-5" />
                        </div>
                        <CardTitle class="text-lg">
                            {{ feature.title }}
                        </CardTitle>
                        <CardDescription class="leading-6">
                            {{ feature.description }}
                        </CardDescription>
                    </CardHeader>
                </Card>
            </section>

            <Card class="rounded-md border-slate-200/80 bg-white/90 shadow-sm">
                <CardHeader
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <CardTitle>Быстрые действия</CardTitle>
                        <CardDescription>
                            Основной сценарий начинается в разделе комнат.
                        </CardDescription>
                    </div>
                    <Button variant="outline" as-child class="rounded-md">
                        <Link :href="roomsIndex()">
                            Перейти к комнатам
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </CardHeader>

                <CardContent class="grid gap-3 md:grid-cols-3">
                    <article
                        v-for="action in quickActions"
                        :key="action.title"
                        class="rounded-md border border-slate-200 bg-slate-50/80 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-md bg-white text-slate-700 shadow-sm"
                            >
                                <component :is="action.icon" class="size-5" />
                            </div>
                            <div>
                                <h2 class="font-semibold text-slate-950">
                                    {{ action.title }}
                                </h2>
                                <p
                                    class="mt-1 text-sm leading-6 text-slate-600"
                                >
                                    {{ action.description }}
                                </p>
                            </div>
                        </div>
                    </article>
                </CardContent>
            </Card>

            <div
                class="rounded-md border border-slate-200/80 bg-white/70 p-4 text-sm leading-6 text-slate-500"
            >
                <Sparkles class="mr-2 inline size-4 text-violet-500" />
                Создайте комнату, настройте доступ и отправьте ссылку
                участникам.
            </div>
        </div>
    </div>
</template>
