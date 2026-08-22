<script setup lang="ts">
import { Head, router, useHttp, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import CallControlsBar from '@/features/calls/components/CallControlsBar.vue';
import CallError from '@/features/calls/components/CallError.vue';
import ParticipantGrid from '@/features/calls/components/ParticipantGrid.vue';
import RoomChatSidebar from '@/features/calls/components/RoomChatSidebar.vue';
import RoomPasswordPanel from '@/features/calls/components/RoomPasswordPanel.vue';
import { useCallDeviceQuery } from '@/features/calls/composables/useCallDeviceQuery';
import { useCallSession } from '@/features/calls/composables/useCallSession';
import { useRoomApplicationEvents } from '@/features/calls/composables/useRoomApplicationEvents';
import type { CallPageProps, RoomMemberSummary } from '@/features/calls/types';
import {
    prepare as showPrepare,
    waiting as showWaiting,
} from '@/routes/rooms/call';
import { update as updateHand } from '@/routes/rooms/hand';

type MemberResponse = {
    member: RoomMemberSummary;
};

const props = defineProps<CallPageProps>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const defaultDisplayName = computed(() => currentUser.value?.name ?? 'Гость');
const deviceQuery = useCallDeviceQuery();
const chatOpen = ref(false);
const personalPinnedRoomMemberId = ref<number | null>(null);
const handRequest = useHttp<{ raised: boolean }, MemberResponse>({
    raised: false,
});

const call = useCallSession({
    roomPublicId: props.room.public_id,
    displayName: defaultDisplayName.value,
    initialSelectedDevices: deviceQuery.query.value,
});
call.waitingRoomEntry.value = props.current_waiting_room_entry;

const realtime = useRoomApplicationEvents(props.room.public_id, {
    roomMembers: props.room_members,
    roomMessages: props.room_messages,
    roomSubrooms: props.room_subrooms,
    globalPinnedRoomMemberId: props.global_pinned_room_member_id,
    waitingRoomEntries: props.waiting_room_entries,
    onWaitingRoomDecision: (event) => {
        void call.handleWaitingRoomDecision(event);
    },
});

const {
    joinRequest,
    connected,
    connectionState,
    busy,
    microphoneEnabled,
    cameraEnabled,
    waitingRoomEntry,
    joinedMember,
    activeSpeakerIdentity,
    tiles,
    errorMessage,
    deviceErrorMessage,
    selectedDevices,
    connect,
    disconnect,
    toggleMicrophone,
    toggleCamera,
    setMediaHost,
} = call;
const {
    roomMembers,
    upsertRoomMember,
    roomMessages,
    appendRoomMessage,
    globalPinnedRoomMemberId,
} = realtime;

const needsPassword = computed(
    () => props.room.has_password && !props.can_join_without_password,
);
const currentRoomMemberId = computed(
    () => joinedMember.value?.id ?? props.current_room_member_id,
);
const currentRoomMember = computed(() =>
    roomMembers.value.find((member) => member.id === currentRoomMemberId.value),
);
const handRaised = computed(() =>
    Boolean(currentRoomMember.value?.hand_raised_at),
);
const raisedHandsCount = computed(
    () => roomMembers.value.filter((member) => member.hand_raised_at).length,
);
const personalPinnedIdentity = computed(
    () =>
        roomMembers.value.find(
            (member) => member.id === personalPinnedRoomMemberId.value,
        )?.livekit_identity ?? null,
);
const globalPinnedIdentity = computed(
    () =>
        roomMembers.value.find(
            (member) => member.id === globalPinnedRoomMemberId.value,
        )?.livekit_identity ?? null,
);

async function connectToCall(): Promise<void> {
    await connect();

    if (waitingRoomEntry.value?.status === 'pending') {
        router.visit(
            showWaiting.url(props.room.public_id, {
                query: deviceQuery.toQuery(selectedDevices.value),
            }),
        );

        return;
    }

    if (joinedMember.value) {
        upsertRoomMember(joinedMember.value);
    }
}

async function toggleHand(): Promise<void> {
    if (currentRoomMemberId.value === null || handRequest.processing) {
        return;
    }

    handRequest.raised = !handRaised.value;

    const response = await handRequest.patch(
        updateHand.url(props.room.public_id),
    );

    if (response?.member) {
        upsertRoomMember(response.member);
    }
}

async function leaveCall(): Promise<void> {
    await disconnect();
    router.visit(
        showPrepare.url(props.room.public_id, {
            query: deviceQuery.toQuery(selectedDevices.value),
        }),
    );
}

onMounted(() => {
    if (!needsPassword.value) {
        void connectToCall();
    }
});
</script>

<template>
    <Head :title="`Звонок: ${room.name}`" />

    <main class="relative min-h-full overflow-hidden bg-neutral-950 text-white">
        <header
            class="pointer-events-none fixed inset-x-0 top-0 z-20 bg-linear-to-b from-black/70 to-transparent p-4"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p
                        class="text-xs tracking-[0.24em] text-white/50 uppercase"
                    >
                        LiveKit: {{ room.livekit_room_name }}
                    </p>
                    <h1 class="mt-1 text-xl font-semibold tracking-tight">
                        {{ room.name }}
                    </h1>
                </div>
                <div class="text-right text-xs text-white/60">
                    <p>{{ connectionState }}</p>
                    <p>Подняли руку: {{ raisedHandsCount }}</p>
                </div>
            </div>
        </header>

        <section class="flex min-h-screen p-3 pt-20 pb-24">
            <ParticipantGrid
                class="min-h-0 w-full"
                :tiles="tiles"
                :active-speaker-identity="activeSpeakerIdentity"
                :personal-pinned-identity="personalPinnedIdentity"
                :global-pinned-identity="globalPinnedIdentity"
                @media-host="setMediaHost"
            />
        </section>

        <div
            v-if="!connected"
            class="fixed inset-0 z-10 flex items-center justify-center bg-neutral-950/70 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-[2rem] border border-white/10 bg-neutral-900 p-5 shadow-2xl"
            >
                <h2 class="text-lg font-semibold">
                    {{
                        busy
                            ? 'Подключаемся к звонку...'
                            : needsPassword
                              ? 'Введите пароль комнаты'
                              : 'Подготовка соединения'
                    }}
                </h2>
                <p class="mt-2 text-sm text-white/60">
                    Камеру и микрофон можно оставить выключенными.
                </p>

                <RoomPasswordPanel
                    v-if="needsPassword"
                    v-model:password="joinRequest.password"
                    class="mt-4 border-white/10 bg-neutral-950"
                    :error="joinRequest.errors.password"
                />

                <button
                    v-if="needsPassword"
                    class="mt-4 w-full rounded-2xl bg-white px-5 py-3 text-sm font-medium text-neutral-950 transition hover:bg-neutral-200 disabled:cursor-not-allowed disabled:opacity-60"
                    type="button"
                    :disabled="busy"
                    @click="connectToCall"
                >
                    {{ busy ? 'Подключаемся...' : 'Войти в звонок' }}
                </button>

                <CallError
                    v-if="errorMessage"
                    class="mt-4"
                    :message="errorMessage"
                />
                <CallError
                    v-if="deviceErrorMessage"
                    class="mt-4"
                    :message="deviceErrorMessage"
                />
            </div>
        </div>

        <RoomChatSidebar
            v-if="chatOpen"
            :room-public-id="room.public_id"
            :current-member-id="currentRoomMemberId"
            :messages="roomMessages"
            @close="chatOpen = false"
            @message="appendRoomMessage"
        />

        <CallControlsBar
            :microphone-enabled="microphoneEnabled"
            :camera-enabled="cameraEnabled"
            :hand-raised="handRaised"
            :chat-open="chatOpen"
            :busy="busy || handRequest.processing"
            @toggle-microphone="toggleMicrophone"
            @toggle-camera="toggleCamera"
            @toggle-hand="toggleHand"
            @toggle-chat="chatOpen = !chatOpen"
            @leave="leaveCall"
        />
    </main>
</template>
