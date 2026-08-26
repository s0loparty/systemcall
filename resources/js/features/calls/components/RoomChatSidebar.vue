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
        data-test="chat-sidebar"
        class="fixed inset-y-3 right-3 z-40 flex w-[calc(100%-1.5rem)] max-w-[26rem] flex-col overflow-hidden rounded-md border border-slate-200/80 bg-white/95 text-slate-950 shadow-[0_24px_90px_rgba(15,23,42,0.18)] backdrop-blur-xl"
    >
        <header
            class="flex items-center justify-between gap-3 border-b border-slate-200/80 p-4"
        >
            <div>
                <h2 class="font-semibold text-slate-950">Чат комнаты</h2>
                <p class="text-xs text-slate-500">
                    Сообщения видят все участники встречи.
                </p>
            </div>
            <button
                class="flex size-10 items-center justify-center rounded-md bg-slate-100 text-slate-600 transition-[background-color,scale] duration-150 hover:bg-slate-200 hover:text-slate-950 active:scale-[0.96]"
                type="button"
                data-test="close-chat"
                aria-label="Закрыть чат"
                @click="emit('close')"
            >
                <X class="size-4" />
            </button>
        </header>

        <ol
            data-test="chat-message-list"
            class="flex flex-1 flex-col gap-2 overflow-auto p-4"
        >
            <li
                v-if="messages.length === 0"
                data-test="chat-empty-state"
                class="rounded-md border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500"
            >
                Сообщений пока нет.
            </li>
            <li
                v-for="message in messages"
                :key="message.id"
                data-test="chat-message"
                :data-message-id="message.id"
                class="rounded-md border border-slate-200 bg-slate-50 p-3 text-sm"
            >
                <div class="flex items-center justify-between gap-3">
                    <p class="font-semibold text-slate-950">
                        {{ message.display_name }}
                    </p>
                    <time class="text-xs text-slate-400">
                        {{ message.created_at ?? '' }}
                    </time>
                </div>
                <p class="mt-1 whitespace-pre-wrap text-slate-700">
                    {{ message.body }}
                </p>
            </li>
        </ol>

        <form
            class="border-t border-slate-200/80 p-4"
            @submit.prevent="sendMessage"
        >
            <textarea
                v-model="messageRequest.body"
                data-test="chat-message-input"
                class="min-h-24 w-full resize-none rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm transition-[border-color,box-shadow] outline-none placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100"
                maxlength="1000"
                placeholder="Напишите сообщение"
                :disabled="
                    currentMemberId === null || messageRequest.processing
                "
            />
            <button
                class="mt-2 w-full rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white transition-[background-color,scale] hover:bg-violet-500 active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
                type="submit"
                data-test="send-chat-message"
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
