<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { ArrowRight, MessageSquare, ShieldCheck, Video } from '@lucide/vue';

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
            <Card
                class="border-border/60 bg-white/70 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70"
            >
                <CardContent class="p-6 md:p-8">
                    <div>
                        <Badge
                            variant="outline"
                            class="gap-1.5 border-border/60 bg-white px-3 py-1.5 dark:border-neutral-700 dark:bg-neutral-800"
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
                                class="h-11 rounded-full bg-neutral-900 px-6 text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
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
                </CardContent>
            </Card>

            <!-- Features -->
            <section class="mt-6 grid gap-4 md:grid-cols-3">
                <Card
                    v-for="feature in featureCards"
                    :key="feature.title"
                    class="border-border/60 bg-white/70 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900/70"
                >
                    <CardHeader>
                        <div
                            class="flex size-11 items-center justify-center rounded-full border border-border/60 bg-white dark:border-neutral-800 dark:bg-neutral-950"
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
                class="mt-6 rounded-xl border border-border/60 bg-neutral-900 p-6 text-white dark:border-neutral-800 dark:bg-white dark:text-neutral-950"
            >
                <div
                    class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3 class="text-lg font-light">Готовы начать?</h3>
                        <p
                            class="mt-1 text-sm leading-relaxed text-neutral-300 dark:text-neutral-600"
                        >
                            Создайте комнату, настройте доступ и отправьте
                            ссылку участникам.
                        </p>
                    </div>
                    <Link
                        :href="roomsIndex()"
                        class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-2.5 text-sm font-medium text-neutral-900 transition hover:bg-neutral-100 dark:bg-neutral-950 dark:text-white dark:hover:bg-neutral-800"
                    >
                        Создать комнату
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
