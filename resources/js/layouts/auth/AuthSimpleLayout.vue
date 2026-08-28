<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { ArrowLeft, RadioTower } from '@lucide/vue';

import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#fafaf9] p-4 dark:bg-neutral-950 sm:p-6"
    >
        <div class="w-full max-w-md space-y-6">
            <!-- Back to home -->
            <div>
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="h-4 w-4" />
                    На главную
                </Link>
            </div>

            <!-- Main card -->
            <div
                class="space-y-6 rounded-3xl border border-border/60 bg-white/70 p-6 shadow-sm backdrop-blur-[1px] dark:border-neutral-800 dark:bg-neutral-900/70 sm:p-8"
            >
                <!-- Header -->
                <div class="space-y-2 text-center">
                    <div class="flex justify-center">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg border border-border bg-white dark:border-neutral-700 dark:bg-neutral-950"
                        >
                            <RadioTower class="h-6 w-6 text-foreground" />
                        </div>
                    </div>
                    <h1 class="text-2xl font-light tracking-tight">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <hr class="border-border/60 dark:border-neutral-800" />

                <!-- Slot for form -->
                <slot />
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-muted-foreground/60">
                Systemcall · групповые видеокомнаты
            </div>
        </div>
    </div>
</template>
