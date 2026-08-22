<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { store as storeMessage } from '@/routes/rooms/messages';
import type { RoomMessageSummary } from '../types';

type Props = {
    roomPublicId: string;
    currentMemberId: number | null;
    messages: RoomMessageSummary[];
};

type MessageResponse = {
    message: RoomMessageSummary;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    message: [message: RoomMessageSummary];
}>();

const messageRequest = useHttp<{ body: string }, MessageResponse>({
    body: '',
});

async function sendMessage(): Promise<void> {
    if (messageRequest.body.trim() === '') {
        return;
    }

    messageRequest.body = messageRequest.body.trim();

    const response = await messageRequest.post(
        storeMessage.url(props.roomPublicId),
    );

    if (response?.message) {
        emit('message', response.message);
        messageRequest.body = '';
    }
}
</script>

<template>
    <aside
        class="fixed inset-y-0 right-0 z-40 flex w-full max-w-md flex-col border-l border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-950"
    >
        <header
            class="flex items-center justify-between gap-3 border-b border-neutral-200 p-4 dark:border-neutral-800"
        >
            <div>
                <h2 class="font-semibold text-neutral-950 dark:text-white">
                    Чат комнаты
                </h2>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Сообщения синхронизируются через Reverb.
                </p>
            </div>
            <button
                class="flex size-9 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 transition hover:bg-neutral-200 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800"
                type="button"
                aria-label="Закрыть чат"
                @click="emit('close')"
            >
                <X class="size-4" />
            </button>
        </header>

        <ol class="flex flex-1 flex-col gap-2 overflow-auto p-4">
            <li
                v-if="messages.length === 0"
                class="rounded-2xl border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 dark:border-neutral-800 dark:text-neutral-400"
            >
                Сообщений пока нет.
            </li>
            <li
                v-for="message in messages"
                :key="message.id"
                class="rounded-2xl bg-neutral-100 p-3 text-sm dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between gap-3">
                    <p class="font-medium text-neutral-950 dark:text-white">
                        {{ message.display_name }}
                    </p>
                    <time class="text-xs text-neutral-400">
                        {{ message.created_at ?? '' }}
                    </time>
                </div>
                <p
                    class="mt-1 whitespace-pre-wrap text-neutral-700 dark:text-neutral-200"
                >
                    {{ message.body }}
                </p>
            </li>
        </ol>

        <form
            class="border-t border-neutral-200 p-4 dark:border-neutral-800"
            @submit.prevent="sendMessage"
        >
            <textarea
                v-model="messageRequest.body"
                class="min-h-24 w-full resize-none rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-950 transition outline-none focus:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-950 dark:text-white"
                maxlength="1000"
                placeholder="Напишите сообщение"
                :disabled="
                    currentMemberId === null || messageRequest.processing
                "
            />
            <button
                class="mt-2 w-full rounded-2xl bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                type="submit"
                :disabled="
                    currentMemberId === null ||
                    messageRequest.processing ||
                    messageRequest.body.trim() === ''
                "
            >
                Отправить
            </button>
        </form>
    </aside>
</template>
