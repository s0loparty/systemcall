<script setup lang="ts">
import type { WaitingRoomEntry } from '../types';

type Props = {
    entry: WaitingRoomEntry | null;
};

defineProps<Props>();
</script>

<template>
    <section
        v-if="entry"
        data-test="waiting-room-status"
        :data-status="entry.status"
        class="rounded-3xl border border-amber-200 bg-amber-50 p-4 text-amber-950 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100"
    >
        <p class="text-sm font-medium">
            {{
                entry.status === 'pending'
                    ? 'Заявка в зал ожидания отправлена'
                    : entry.status === 'approved'
                      ? 'Заявка одобрена'
                      : 'Заявка отклонена'
            }}
        </p>
        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200/80">
            {{
                entry.status === 'pending'
                    ? 'Модератор должен одобрить вход, прежде чем будет выдан доступ к LiveKit.'
                    : entry.status === 'approved'
                      ? 'Одобрение получено. Звонок запросит медиа-токен.'
                      : 'Вы не сможете войти, пока модератор не изменит решение.'
            }}
        </p>
    </section>
</template>
