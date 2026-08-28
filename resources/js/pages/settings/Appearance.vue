<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import { edit } from '@/routes/appearance';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Настройки внешнего вида',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Настройки внешнего вида" />

    <h1 class="sr-only">Настройки внешнего вида</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Внешний вид"
            description="Выберите оформление интерфейса для текущего устройства."
        />

        <Card
            class="overflow-hidden rounded-md dark:bg-neutral-900 dark:border-neutral-800 border-slate-200/80 bg-white/90 shadow-sm"
        >
            <CardHeader>
                <CardTitle>Тема интерфейса</CardTitle>
                <CardDescription>
                    Настройка сохраняется в браузере и применяется сразу.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AppearanceTabs />
            </CardContent>
        </Card>
    </div>
</template>
