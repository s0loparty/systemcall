<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import {
    ArrowRight,
    CheckCircle2,
    MessageSquare,
    ShieldCheck,
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
</script>

<template>
    <Head title="Панель" />

    <div class="p-3 sm:p-5">
        <div class="pt-5">
            <!-- Hero -->
            <Card class="border-border/60 bg-white/70 shadow-sm">
                <CardContent
                    class="grid gap-8 p-6 md:p-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center"
                >
                    <div>
                        <Badge
                            variant="outline"
                            class="gap-1.5 border-border/60 bg-white px-3 py-1.5"
                        >
                            <span class="size-2 rounded-full bg-emerald-500" />
                            Готово к встречам
                        </Badge>

                        <h1
                            class="mt-5 max-w-3xl text-3xl font-light tracking-tight text-balance sm:text-4xl"
                        >
                            Управляйте групповыми звонками из одного места.
                        </h1>

                        <p
                            class="mt-4 max-w-2xl text-base leading-7 text-muted-foreground"
                        >
                            Создавайте комнаты, приглашайте команду и гостей,
                            управляйте доступом и проводите встречи без сложной
                            подготовки.
                        </p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            <Button
                                as-child
                                class="h-11 rounded-full bg-neutral-900 px-6 text-white hover:bg-neutral-700"
                            >
                                <Link
                                    :href="roomsIndex()"
                                    class="flex items-center gap-2"
                                >
                                    Открыть комнаты
                                    <ArrowRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Командная комната -->
                    <div
                        class="rounded-xl border border-border/60 bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-semibold">Командная комната</p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    звонок для команд и гостей
                                </p>
                            </div>
                            <Badge
                                class="rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-50"
                            >
                                активна
                            </Badge>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div
                                class="flex items-center gap-2 rounded-lg border border-border/60 bg-muted/30 p-3"
                            >
                                <Users class="size-4 text-muted-foreground" />
                                <span class="text-sm font-medium"
                                    >Участники готовы</span
                                >
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-lg border border-border/60 bg-muted/30 p-3"
                            >
                                <CheckCircle2 class="size-4 text-emerald-500" />
                                <span class="text-sm font-medium"
                                    >Доступ настроен</span
                                >
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <div class="flex -space-x-2">
                                <div
                                    class="flex size-8 items-center justify-center rounded-full bg-neutral-200 text-xs font-medium text-neutral-700 ring-2 ring-white"
                                >
                                    А
                                </div>
                                <div
                                    class="flex size-8 items-center justify-center rounded-full bg-neutral-200 text-xs font-medium text-neutral-700 ring-2 ring-white"
                                >
                                    М
                                </div>
                                <div
                                    class="flex size-8 items-center justify-center rounded-full bg-neutral-200 text-xs font-medium text-neutral-700 ring-2 ring-white"
                                >
                                    К
                                </div>
                                <div
                                    class="flex size-8 items-center justify-center rounded-full bg-neutral-200 text-xs font-medium text-neutral-700 ring-2 ring-white"
                                >
                                    +3
                                </div>
                            </div>
                            <a
                                href="#"
                                class="text-sm font-medium text-foreground hover:underline"
                                >Присоединиться →</a
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Features -->
            <section class="mt-6 grid gap-4 md:grid-cols-3">
                <Card
                    v-for="feature in featureCards"
                    :key="feature.title"
                    class="border-border/60 bg-white/70 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                >
                    <CardHeader>
                        <div
                            class="flex size-11 items-center justify-center rounded-full border border-border/60 bg-white"
                        >
                            <component
                                :is="feature.icon"
                                class="size-5 text-foreground"
                            />
                        </div>
                        <CardTitle class="text-lg font-semibold">
                            {{ feature.title }}
                        </CardTitle>
                        <CardDescription
                            class="leading-6 text-muted-foreground"
                        >
                            {{ feature.description }}
                        </CardDescription>
                    </CardHeader>
                </Card>
            </section>

            <!-- Call to action -->
            <div
                class="mt-6 rounded-xl border border-border/60 bg-neutral-900 p-6 text-white"
            >
                <div
                    class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3 class="text-lg font-light">Готовы начать?</h3>
                        <p
                            class="mt-1 text-sm text-neutral-300 leading-relaxed"
                        >
                            Создайте комнату, настройте доступ и отправьте
                            ссылку участникам.
                        </p>
                    </div>
                    <Link
                        :href="roomsIndex()"
                        class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-2.5 text-sm font-medium text-neutral-900 transition hover:bg-neutral-100"
                    >
                        Создать комнату
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-8 border-t border-border/60 pt-6">
                <div
                    class="flex flex-col items-start justify-between gap-3 text-xs text-muted-foreground sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1.5">
                            <span
                                class="inline-block size-2 rounded-full bg-emerald-500"
                            ></span>
                            онлайн
                        </span>
                        <span>•</span>
                        <span>готово для команд</span>
                    </div>
                    <div class="flex gap-4">
                        <a
                            href="#"
                            class="transition hover:text-foreground hover:underline underline-offset-2"
                            >Помощь</a
                        >
                        <a
                            href="#"
                            class="transition hover:text-foreground hover:underline underline-offset-2"
                            >API</a
                        >
                        <a
                            href="#"
                            class="transition hover:text-foreground hover:underline underline-offset-2"
                            >Блог</a
                        >
                    </div>
                </div>
                <div
                    class="mt-4 text-center text-[11px] text-muted-foreground/60"
                >
                    Systemcall · групповые видеокомнаты · минимализм
                </div>
            </footer>
        </div>
    </div>
</template>
