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
        class="fixed inset-y-3 right-3 z-40 flex w-[calc(100%-1.5rem)] max-w-[26rem] flex-col overflow-hidden rounded-[1.75rem] bg-[#2b2c2f] text-white shadow-[0_24px_90px_rgba(0,0,0,0.45),inset_0_0_0_1px_rgba(255,255,255,0.08)]"
    >
        <header
            class="flex items-center justify-between gap-3 border-b border-white/8 p-4"
        >
            <div>
                <h2 class="font-medium text-white">Чат комнаты</h2>
                <p class="text-xs text-white/55">
                    Сообщения синхронизируются через Reverb.
                </p>
            </div>
            <button
                class="flex size-10 items-center justify-center rounded-full bg-white/8 text-white/75 transition-[background-color,scale] duration-150 hover:bg-white/14 active:scale-[0.96]"
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
                class="rounded-2xl border border-dashed border-white/12 p-4 text-sm text-white/55"
            >
                Сообщений пока нет.
            </li>
            <li
                v-for="message in messages"
                :key="message.id"
                data-test="chat-message"
                :data-message-id="message.id"
                class="rounded-2xl bg-white/8 p-3 text-sm"
            >
                <div class="flex items-center justify-between gap-3">
                    <p class="font-medium text-white">
                        {{ message.display_name }}
                    </p>
                    <time class="text-xs text-white/40">
                        {{ message.created_at ?? '' }}
                    </time>
                </div>
                <p class="mt-1 whitespace-pre-wrap text-white/78">
                    {{ message.body }}
                </p>
            </li>
        </ol>

        <form class="border-t border-white/8 p-4" @submit.prevent="sendMessage">
            <textarea
                v-model="messageRequest.body"
                data-test="chat-message-input"
                class="min-h-24 w-full resize-none rounded-2xl bg-[#202124] px-3 py-2 text-sm text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.08)] transition-[box-shadow] outline-none placeholder:text-white/35 focus:shadow-[inset_0_0_0_1px_rgba(138,180,248,0.65)]"
                maxlength="1000"
                placeholder="Напишите сообщение"
                :disabled="
                    currentMemberId === null || messageRequest.processing
                "
            />
            <button
                class="mt-2 w-full rounded-2xl bg-[#8ab4f8] px-4 py-2 text-sm font-medium text-[#202124] transition-[background-color,scale] hover:bg-[#a8c7fa] active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
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
