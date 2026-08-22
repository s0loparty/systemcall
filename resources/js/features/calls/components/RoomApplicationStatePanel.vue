<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { computed } from 'vue';
import { update as updateGlobalPin } from '@/routes/rooms/global_pin';
import { update as updateHand } from '@/routes/rooms/hand';
import { store as storeMessage } from '@/routes/rooms/messages';
import type { RoomMemberSummary, RoomMessageSummary } from '../types';

type Props = {
    roomPublicId: string;
    currentMemberId: number | null;
    canModerateMembers: boolean;
    members: RoomMemberSummary[];
    messages: RoomMessageSummary[];
    globalPinnedMemberId: number | null;
    personalPinnedMemberId: number | null;
};

type MessageResponse = {
    message: RoomMessageSummary;
};

type MemberResponse = {
    member: RoomMemberSummary;
};

type GlobalPinResponse = {
    global_pinned_room_member_id: number | null;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    message: [message: RoomMessageSummary];
    member: [member: RoomMemberSummary];
    globalPin: [memberId: number | null];
    personalPin: [memberId: number | null];
}>();

const messageRequest = useHttp<{ body: string }, MessageResponse>({
    body: '',
});
const handRequest = useHttp<{ raised: boolean }, MemberResponse>({
    raised: false,
});
const globalPinRequest = useHttp<
    { room_member_id: number | null },
    GlobalPinResponse
>({
    room_member_id: null,
});

const currentMember = computed(() =>
    props.members.find((member) => member.id === props.currentMemberId),
);
const raisedMembers = computed(() =>
    props.members.filter((member) => member.hand_raised_at),
);
const globalPinnedMember = computed(() =>
    props.members.find((member) => member.id === props.globalPinnedMemberId),
);
const personalPinnedMember = computed(() =>
    props.members.find((member) => member.id === props.personalPinnedMemberId),
);

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

async function setRaisedHand(raised: boolean): Promise<void> {
    handRequest.raised = raised;

    const response = await handRequest.patch(
        updateHand.url(props.roomPublicId),
    );

    if (response?.member) {
        emit('member', response.member);
    }
}

async function setGlobalPin(memberId: number | null): Promise<void> {
    globalPinRequest.room_member_id = memberId;

    const response = await globalPinRequest.patch(
        updateGlobalPin.url(props.roomPublicId),
    );

    emit('globalPin', response?.global_pinned_room_member_id ?? null);
}
</script>

<template>
    <section
        class="grid gap-4 rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm lg:grid-cols-[1.4fr_1fr] dark:border-neutral-800 dark:bg-neutral-950"
    >
        <div class="flex min-h-80 flex-col">
            <div>
                <p class="text-sm font-medium text-neutral-950 dark:text-white">
                    Чат комнаты
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Только простой текст. Сообщения ограничены по частоте и
                    синхронизируются через Reverb.
                </p>
            </div>

            <ol
                class="mt-3 flex max-h-72 flex-1 flex-col gap-2 overflow-auto rounded-2xl bg-neutral-50 p-3 dark:bg-neutral-900/60"
            >
                <li
                    v-if="messages.length === 0"
                    class="text-sm text-neutral-500 dark:text-neutral-400"
                >
                    Сообщений пока нет.
                </li>
                <li
                    v-for="message in messages"
                    :key="message.id"
                    class="rounded-2xl bg-white p-3 text-sm shadow-xs dark:bg-neutral-950"
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

            <form class="mt-3 flex gap-2" @submit.prevent="sendMessage">
                <textarea
                    v-model="messageRequest.body"
                    class="min-h-11 flex-1 resize-none rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-950 transition outline-none focus:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-950 dark:text-white"
                    maxlength="1000"
                    placeholder="Напишите сообщение"
                    :disabled="
                        currentMemberId === null || messageRequest.processing
                    "
                />
                <button
                    class="rounded-2xl bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
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
        </div>

        <div class="flex flex-col gap-4">
            <div
                class="rounded-2xl border border-neutral-200 p-3 dark:border-neutral-800"
            >
                <p class="text-sm font-medium text-neutral-950 dark:text-white">
                    Руки
                </p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button
                        class="rounded-full bg-amber-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="
                            currentMemberId === null || handRequest.processing
                        "
                        @click="setRaisedHand(!currentMember?.hand_raised_at)"
                    >
                        {{
                            currentMember?.hand_raised_at
                                ? 'Опустить руку'
                                : 'Поднять руку'
                        }}
                    </button>
                    <span
                        class="rounded-full bg-neutral-100 px-3 py-1.5 text-xs text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300"
                    >
                        Подняли руку: {{ raisedMembers.length }}
                    </span>
                </div>
                <ol
                    v-if="raisedMembers.length > 0"
                    class="mt-3 flex flex-col gap-1"
                >
                    <li
                        v-for="member in raisedMembers"
                        :key="member.id"
                        class="text-xs text-neutral-600 dark:text-neutral-300"
                    >
                        {{ member.display_name }}
                    </li>
                </ol>
            </div>

            <div
                class="rounded-2xl border border-neutral-200 p-3 dark:border-neutral-800"
            >
                <p class="text-sm font-medium text-neutral-950 dark:text-white">
                    Закрепления
                </p>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                    Личное закрепление видно только в этом браузере. Общее
                    закрепление синхронизируется для всех и требует прав
                    модератора.
                </p>
                <div class="mt-3 grid gap-2">
                    <p class="text-xs text-neutral-600 dark:text-neutral-300">
                        Лично:
                        {{ personalPinnedMember?.display_name ?? 'нет' }}
                    </p>
                    <p class="text-xs text-neutral-600 dark:text-neutral-300">
                        Для всех:
                        {{ globalPinnedMember?.display_name ?? 'нет' }}
                    </p>
                </div>
                <ol class="mt-3 flex max-h-48 flex-col gap-2 overflow-auto">
                    <li
                        v-for="member in members"
                        :key="member.id"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-neutral-50 p-2 dark:bg-neutral-900/60"
                    >
                        <span
                            class="text-xs font-medium text-neutral-700 dark:text-neutral-200"
                        >
                            {{ member.display_name }}
                        </span>
                        <div class="flex gap-2">
                            <button
                                class="rounded-full bg-neutral-200 px-2.5 py-1 text-xs font-medium text-neutral-700 transition hover:bg-neutral-300 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
                                type="button"
                                @click="
                                    emit(
                                        'personalPin',
                                        personalPinnedMemberId === member.id
                                            ? null
                                            : member.id,
                                    )
                                "
                            >
                                {{
                                    personalPinnedMemberId === member.id
                                        ? 'Открепить лично'
                                        : 'Закрепить лично'
                                }}
                            </button>
                            <button
                                v-if="canModerateMembers"
                                class="rounded-full bg-blue-600 px-2.5 py-1 text-xs font-medium text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60"
                                type="button"
                                :disabled="globalPinRequest.processing"
                                @click="
                                    setGlobalPin(
                                        globalPinnedMemberId === member.id
                                            ? null
                                            : member.id,
                                    )
                                "
                            >
                                {{
                                    globalPinnedMemberId === member.id
                                        ? 'Открепить для всех'
                                        : 'Закрепить для всех'
                                }}
                            </button>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </section>
</template>
