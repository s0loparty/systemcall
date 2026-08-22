<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

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
            title="Настройки внешнего вида"
            description="Измените оформление вашего аккаунта"
        />
        <AppearanceTabs />
    </div>
</template>
