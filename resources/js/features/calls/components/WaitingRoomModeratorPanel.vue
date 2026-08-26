<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';

import { Check, ShieldCheck, X } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';

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
    <Card
        data-test="waiting-room-moderator-panel"
        class="rounded-md border-slate-200/80 bg-white/90 shadow-sm"
    >
        <CardHeader class="flex flex-row items-center gap-3">
            <div
                class="flex size-10 items-center justify-center rounded-md bg-violet-50 text-violet-600 grow-0"
            >
                <ShieldCheck class="size-5" />
            </div>
            <div>
                <CardTitle class="text-base"> Очередь входа </CardTitle>
                <CardDescription class="leading-4">
                    Одобряйте участников, которых готовы пустить в комнату.
                </CardDescription>
            </div>
        </CardHeader>

        <CardContent>
            <p
                v-if="entries.length === 0"
                class="rounded-md border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500"
            >
                Сейчас никто не ожидает.
            </p>

            <ScrollArea v-else>
                <ol class="flex flex-col gap-2">
                    <li
                        v-for="entry in entries"
                        :key="entry.id"
                        data-test="waiting-room-entry"
                        :data-entry-id="entry.id"
                        class="rounded-md border border-slate-200 bg-slate-50 p-3 flex items-center justify-between"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-slate-950"
                                >
                                    {{ entry.display_name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ entry.guest ? 'Гость' : 'Пользователь' }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <Button
                                class="text-xs rounded-md bg-emerald-600 text-white hover:bg-emerald-500"
                                type="button"
                                data-test="approve-waiting-entry"
                                :data-entry-id="entry.id"
                                :disabled="
                                    approveRequest.processing ||
                                    rejectRequest.processing
                                "
                                @click="approveEntry(entry)"
                            >
                                <Check class="size-4" />
                                Одобрить
                            </Button>
                            <Button
                                class="text-xs rounded-md bg-red-600 text-white hover:bg-red-500"
                                type="button"
                                data-test="reject-waiting-entry"
                                :data-entry-id="entry.id"
                                :disabled="
                                    approveRequest.processing ||
                                    rejectRequest.processing
                                "
                                @click="rejectEntry(entry)"
                            >
                                <X class="size-4" />
                                Отклонить
                            </Button>
                        </div>
                    </li>
                </ol>
            </ScrollArea>
        </CardContent>
    </Card>
</template>
