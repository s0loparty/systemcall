<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { approve, reject } from '@/routes/rooms/waiting_room';
import type { WaitingRoomEntry } from '../types';

type Props = {
    roomPublicId: string;
    entries: WaitingRoomEntry[];
};

const props = defineProps<Props>();
const emit = defineEmits<{
    decided: [entryId: number];
}>();

const approveRequest = useHttp<Record<string, never>, unknown>({});
const rejectRequest = useHttp<Record<string, never>, unknown>({});

async function approveEntry(entry: WaitingRoomEntry): Promise<void> {
    await approveRequest.post(
        approve.url({
            room: props.roomPublicId,
            waitingRoomMember: entry.id,
        }),
    );
    emit('decided', entry.id);
}

async function rejectEntry(entry: WaitingRoomEntry): Promise<void> {
    await rejectRequest.post(
        reject.url({
            room: props.roomPublicId,
            waitingRoomMember: entry.id,
        }),
    );
    emit('decided', entry.id);
}
</script>

<template>
    <section
        class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
    >
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-neutral-950 dark:text-white">
                    Зал ожидания
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Подтверждайте или отклоняйте ожидающих участников.
                </p>
            </div>
            <span
                class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300"
            >
                Ожидают: {{ entries.length }}
            </span>
        </div>

        <p
            v-if="entries.length === 0"
            class="mt-3 text-sm text-neutral-500 dark:text-neutral-400"
        >
            Сейчас никто не ожидает.
        </p>

        <ol v-else class="mt-3 flex flex-col gap-2">
            <li
                v-for="entry in entries"
                :key="entry.id"
                class="flex flex-col gap-2 rounded-2xl border border-neutral-200 p-3 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800"
            >
                <div>
                    <p
                        class="text-sm font-medium text-neutral-950 dark:text-white"
                    >
                        {{ entry.display_name }}
                    </p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                        {{ entry.guest ? 'Гость' : 'Пользователь' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        class="rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="
                            approveRequest.processing ||
                            rejectRequest.processing
                        "
                        @click="approveEntry(entry)"
                    >
                        Одобрить
                    </button>
                    <button
                        class="rounded-full bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="
                            approveRequest.processing ||
                            rejectRequest.processing
                        "
                        @click="rejectEntry(entry)"
                    >
                        Отклонить
                    </button>
                </div>
            </li>
        </ol>
    </section>
</template>
