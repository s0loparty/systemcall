<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { Separator } from '@/components/ui/separator';

import { cn } from '@/lib/utils';
</script>

<template>
    <footer
        :class="cn('px-4 py-4 sm:px-6 sm:py-5 lg:px-8 w-full', $attrs.class)"
    >
        <Separator class="mb-4" />

        <div
            class="flex justify-between flex-wrap gap-3 text-xs text-muted-foreground gap-y-3 gap-x-10 items-center"
        >
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                        ></span>
                    </span>
                    онлайн
                </span>
                <span>•</span>
                <span>готово для созвонов</span>
            </div>
            <div class="flex gap-4">
                <Link
                    href="#"
                    class="underline-offset-2 transition-colors hover:text-foreground hover:underline"
                >
                    API
                </Link>
                <a
                    href="https://suetovlabs.ru"
                    class="underline decoration-dotted underline-offset-2 transition-colors hover:text-foreground"
                    target="_blank"
                >
                    Создано suetovlabs.ru
                </a>
            </div>
        </div>

        <!-- Micro footer -->
        <p
            class="text-center text-[11px] mt-4 tracking-wide text-muted-foreground/60"
        >
            Systemcall · групповые видеокомнаты
        </p>
    </footer>
</template>
