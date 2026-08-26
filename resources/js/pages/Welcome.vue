<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import { ArrowRight, RadioTower } from '@lucide/vue';

import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import { dashboard, login, register } from '@/routes';

import { index as roomsIndex } from '@/routes/rooms';

import AppFooter from '@/layouts/AppFooter.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Systemcall" />

    <main class="min-h-screen bg-gray-100">
        <div
            class="relative container mx-auto flex flex-col items-center justify-center gap-8 px-4 py-8 md:py-12"
        >
            <!-- Header -->
            <header
                class="flex w-full max-w-4xl flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
            >
                <Link
                    :href="user ? dashboard() : '/'"
                    class="flex items-center gap-3 transition-opacity hover:opacity-80"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-background"
                    >
                        <RadioTower class="h-5 w-5 text-foreground" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight">
                            Systemcall
                        </h1>
                        <p class="text-xs text-muted-foreground">
                            групповые видеокомнаты
                        </p>
                    </div>
                </Link>

                <nav class="flex items-center gap-2">
                    <template v-if="user">
                        <Button as-child variant="default" size="sm">
                            <Link
                                :href="roomsIndex()"
                                class="flex items-center gap-2"
                            >
                                Комнаты
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </Button>
                    </template>
                    <template v-else>
                        <div class="hidden gap-2 sm:flex">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="login()">Войти</Link>
                            </Button>
                            <Button as-child size="sm">
                                <Link :href="register()">Регистрация</Link>
                            </Button>
                        </div>
                    </template>
                </nav>
            </header>

            <!-- Main content -->
            <div class="w-full max-w-4xl space-y-8">
                <!-- Hero -->
                <div class="space-y-4">
                    <h2
                        class="text-3xl font-light tracking-tight text-balance sm:text-5xl"
                    >
                        Видеозвонки, в которые легко пригласить команду и
                        гостей.
                    </h2>
                    <p
                        class="max-w-2xl text-sm leading-relaxed text-muted-foreground sm:text-base"
                    >
                        Создавайте комнаты, проверяйте устройства перед входом,
                        управляйте доступом и проводите встречи без сложной
                        подготовки.
                    </p>
                </div>

                <!-- Action buttons -->
                <div class="flex flex-wrap items-center gap-3">
                    <Button as-child size="default">
                        <Link
                            :href="user ? roomsIndex() : register()"
                            class="flex items-center gap-2"
                        >
                            {{ user ? 'Открыть комнаты' : 'Создать комнату' }}
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button as-child variant="outline" size="default">
                        <Link :href="user ? dashboard() : login()">
                            {{ user ? 'Панель управления' : 'Войти' }}
                        </Link>
                    </Button>
                </div>

                <!-- Features grid -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-muted"
                            >
                                <span class="text-lg">🤝</span>
                            </div>
                            <CardTitle class="text-base"
                                >Совместная работа</CardTitle
                            >
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                Общайтесь в чате, поднимайте руки, закрепляйте
                                участников и показывайте экран.
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <Badge variant="secondary" class="bg-muted"
                                    >чат</Badge
                                >
                                <Badge variant="secondary" class="bg-muted"
                                    >поднять руку</Badge
                                >
                                <Badge variant="secondary" class="bg-muted"
                                    >закрепить</Badge
                                >
                                <Badge variant="secondary" class="bg-muted"
                                    >демонстрация</Badge
                                >
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-muted"
                            >
                                <span class="text-lg">🔗</span>
                            </div>
                            <CardTitle class="text-base"
                                >Комнаты по ссылке</CardTitle
                            >
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                Создавайте встречи за пару секунд и приглашайте
                                участников без лишней настройки.
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <code
                                    class="relative rounded bg-muted px-[0.6rem] py-[0.2rem] font-mono text-xs font-semibold"
                                >
                                    systemcall.ru/room/abc
                                </code>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                >
                                    копировать
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Footer -->
            <AppFooter class="max-w-4xl p-0 sm:p-0 lg:p-0" />
        </div>
    </main>
</template>
