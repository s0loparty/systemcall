<script setup lang="ts">
import { CheckCircle2, Clock3, XCircle } from '@lucide/vue';

import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import type { WaitingRoomEntry } from '../types';

type Props = {
    entry: WaitingRoomEntry | null;
};

const props = defineProps<Props>();

const statusContent = computed(() => {
    if (props.entry?.status === 'approved') {
        return {
            icon: CheckCircle2,
            badge: 'Одобрено',
            title: 'Заявка одобрена',
            description: 'Доступ открыт. Сейчас можно войти в звонок.',
            classes: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        };
    }

    if (props.entry?.status === 'rejected') {
        return {
            icon: XCircle,
            badge: 'Отклонено',
            title: 'Заявка отклонена',
            description:
                'Вход закрыт. Свяжитесь с модератором комнаты, если это ошибка.',
            classes: 'border-red-200 bg-red-50 text-red-800',
        };
    }

    return {
        icon: Clock3,
        badge: 'Ожидание',
        title: 'Заявка в зал ожидания отправлена',
        description: 'Модератор увидит заявку и примет решение.',
        classes: 'border-amber-200 bg-amber-50 text-amber-800',
    };
});
</script>

<template>
    <Card
        v-if="entry"
        data-test="waiting-room-status"
        :data-status="entry.status"
        class="border-none shadow-none p-0"
    >
        <CardHeader class="flex flex-row items-start gap-4 p-0">
            <div
                :class="[
                    'flex size-11 shrink-0 items-center justify-center rounded-md border',
                    statusContent.classes,
                ]"
            >
                <component :is="statusContent.icon" class="size-5" />
            </div>
            <div>
                <Badge variant="outline" class="rounded-md">
                    {{ statusContent.badge }}
                </Badge>
                <CardTitle class="mt-3 text-xl">
                    {{ statusContent.title }}
                </CardTitle>
                <CardDescription class="mt-1 leading-6">
                    {{ statusContent.description }}
                </CardDescription>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            <p class="rounded-md bg-slate-50 p-3 text-sm text-slate-500">
                Страница обновится автоматически после решения модератора.
            </p>
        </CardContent>
    </Card>
</template>
