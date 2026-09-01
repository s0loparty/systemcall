<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import {
    ArrowDownToLine,
    ArrowRight,
    GitBranch,
    Loader2,
    Smartphone,
} from '@lucide/vue';

import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import { dashboard, login, register } from '@/routes';

import { index as roomsIndex } from '@/routes/rooms';

import AppLogo from '@/components/AppLogo.vue';
import { useGitHubRelease } from '@/composables/useGitHubRelease';
import AppFooter from '@/layouts/AppFooter.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const { VITE_APP_NAME } = import.meta.env;

const { apkUrl, isLoading, error, downloadApk, fetchLatestRelease } =
    useGitHubRelease({
        repo: 's0loparty/systemcall-mobile',
        // Кастомный фильтр, если нужен (опционально)
        apkFilter: (asset) =>
            asset.name.endsWith('.apk') &&
            (asset.name.includes('arm64') || asset.name.includes('release')),
    });

const handleDownloadClick = async () => {
    // Если ссылка ещё не загружена - пробуем получить
    if (!apkUrl.value && !error.value) {
        await fetchLatestRelease();
    }

    // Если есть ошибка - ничего не делаем
    if (error.value) {
        return;
    }

    // Скачиваем
    downloadApk();
};
</script>

<template>
    <Head :title="VITE_APP_NAME" />

    <main class="min-h-screen bg-gray-100 dark:bg-neutral-950">
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
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-background dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <AppLogo />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight">
                            {{ VITE_APP_NAME }}
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

                <!-- Android app -->
                <section
                    class="overflow-hidden rounded-md border border-border/60 bg-white/80 shadow-sm shadow-slate-950/5 dark:border-neutral-800 dark:bg-neutral-900/70 dark:shadow-black/20 relative"
                >
                    <div
                        class="grid gap-6 p-5 sm:grid-cols-[1fr_auto] sm:items-center sm:p-6"
                    >
                        <div class="flex gap-4">
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                            >
                                <Smartphone class="size-6" />
                            </div>
                            <div class="space-y-1">
                                <h3
                                    class="text-xl font-semibold tracking-tight"
                                >
                                    Установите приложение на Android
                                </h3>
                                <p
                                    class="max-w-xl text-sm leading-relaxed text-muted-foreground"
                                >
                                    Открывайте комнаты, подключайтесь к звонкам
                                    и проверяйте устройства прямо с телефона.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button
                                as-child
                                size="default"
                                class="grow sm:w-auto"
                                :disabled="isLoading || !!error"
                            >
                                <a
                                    :href="apkUrl || '#'"
                                    class="flex items-center justify-center gap-2"
                                    @click.prevent="handleDownloadClick"
                                >
                                    <template v-if="isLoading">
                                        <Loader2 class="size-4 animate-spin" />
                                        Загрузка...
                                    </template>
                                    <template v-else-if="error">
                                        ⚠️ Ошибка
                                    </template>
                                    <template v-else>
                                        Скачать .apk
                                        <ArrowDownToLine class="size-4" />
                                    </template>
                                </a>
                            </Button>

                            <Button
                                :as="Link"
                                href="https://github.com/s0loparty/systemcall-mobile"
                                target="_blank"
                                variant="outline"
                                size="icon"
                            >
                                <GitBranch />
                            </Button>
                        </div>
                    </div>
                </section>

                <!-- Features grid -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <Card
                        class="border-border/60 bg-white/80 dark:border-neutral-800 dark:bg-neutral-900/70"
                    >
                        <CardHeader>
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-muted dark:border-neutral-700 dark:bg-neutral-950"
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
                                <Badge
                                    variant="secondary"
                                    class="bg-muted dark:bg-neutral-800"
                                    >чат</Badge
                                >
                                <Badge
                                    variant="secondary"
                                    class="bg-muted dark:bg-neutral-800"
                                    >поднять руку</Badge
                                >
                                <Badge
                                    variant="secondary"
                                    class="bg-muted dark:bg-neutral-800"
                                    >закрепить</Badge
                                >
                                <Badge
                                    variant="secondary"
                                    class="bg-muted dark:bg-neutral-800"
                                    >демонстрация</Badge
                                >
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        class="border-border/60 bg-white/80 dark:border-neutral-800 dark:bg-neutral-900/70"
                    >
                        <CardHeader>
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-muted dark:border-neutral-700 dark:bg-neutral-950"
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
                                    class="relative rounded bg-muted px-[0.6rem] py-[0.2rem] font-mono text-xs font-semibold dark:bg-neutral-800"
                                >
                                    https://example.com/room/abc
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
