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
import type {
    CallPageProps,
    ParticipantTile,
    ParticipantTileActions,
    RoomMemberSummary,
} from '@/features/calls/types';
import {
    prepare as showPrepare,
    waiting as showWaiting,
} from '@/routes/rooms/call';
import { update as updateGlobalPin } from '@/routes/rooms/global_pin';
import { update as updateHand } from '@/routes/rooms/hand';
import { ban, kick } from '@/routes/rooms/members';

type MemberResponse = {
    member: RoomMemberSummary;
};

type GlobalPinResponse = {
    global_pinned_room_member_id: number | null;
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
const globalPinRequest = useHttp<
    { room_member_id: number | null },
    GlobalPinResponse
>({
    room_member_id: null,
});
const kickRequest = useHttp<Record<string, never>, MemberResponse>({});
const banRequest = useHttp<Record<string, never>, unknown>({});

const call = useCallSession({
    roomPublicId: props.room.public_id,
    displayName: defaultDisplayName.value,
    initialSelectedDevices: deviceQuery.query.value,
    testingMediaMode: props.testing_media_mode,
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
    screenShareEnabled,
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
    toggleScreenShare,
    setMediaHost,
} = call;
const {
    roomMembers,
    upsertRoomMember,
    roomMessages,
    appendRoomMessage,
    globalPinnedRoomMemberId,
    echoConnectionStatus,
    waitingRoomEntries,
    removeRoomMember,
    removeWaitingRoomEntry,
    setGlobalPinnedRoomMemberId,
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
const canShareScreen = computed(
    () =>
        currentRoomMember.value?.can_publish_screen ??
        joinedMember.value?.can_publish_screen ??
        false,
);
const raisedHandsCount = computed(
    () => roomMembers.value.filter((member) => member.hand_raised_at).length,
);
const pendingWaitingRoomEntries = computed(() =>
    waitingRoomEntries.value.filter((entry) => entry.status === 'pending'),
);
const tilesWithApplicationState = computed<ParticipantTile[]>(() =>
    tiles.value.map((tile) => {
        const member = roomMembers.value.find(
            (roomMember) => roomMember.livekit_identity === tile.identity,
        );

        return {
            ...tile,
            handRaised: Boolean(member?.hand_raised_at),
        };
    }),
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
const roomMemberByIdentity = computed(
    () =>
        new Map(
            roomMembers.value.map((member) => [
                member.livekit_identity,
                member,
            ]),
        ),
);
const tileActionsByIdentity = computed(() =>
    tilesWithApplicationState.value.reduce<
        Record<string, ParticipantTileActions>
    >((actions, tile) => {
        const member = roomMemberByIdentity.value.get(tile.identity);
        const canTarget = !tile.local && member !== undefined;
        const canModerateTarget = member
            ? canTarget && canModerateRoomMember(member)
            : false;

        actions[tile.identity] = {
            canPersonalPin: canTarget,
            canGlobalPin: canTarget && props.can_moderate_members,
            canKick: canModerateTarget,
            canBan: canModerateTarget,
        };

        return actions;
    }, {}),
);
const tileActionsBusy = computed(
    () =>
        globalPinRequest.processing ||
        kickRequest.processing ||
        banRequest.processing,
);

function canModerateRoomMember(member: RoomMemberSummary): boolean {
    const currentMember = currentRoomMember.value;

    if (
        !props.can_moderate_members ||
        !currentMember ||
        member.id === currentMember.id ||
        member.left_at !== null
    ) {
        return false;
    }

    if (currentMember.role === 'owner') {
        return member.role !== 'owner';
    }

    if (currentMember.role === 'moderator') {
        return member.role === 'participant';
    }

    return false;
}

function memberByIdentity(identity: string): RoomMemberSummary | null {
    return roomMemberByIdentity.value.get(identity) ?? null;
}

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

function togglePersonalPin(identity: string): void {
    const member = memberByIdentity(identity);

    if (!member || member.id === currentRoomMemberId.value) {
        return;
    }

    personalPinnedRoomMemberId.value =
        personalPinnedRoomMemberId.value === member.id ? null : member.id;
}

async function toggleGlobalPin(identity: string): Promise<void> {
    const member = memberByIdentity(identity);

    if (
        !member ||
        member.id === currentRoomMemberId.value ||
        !props.can_moderate_members ||
        globalPinRequest.processing
    ) {
        return;
    }

    globalPinRequest.room_member_id =
        globalPinnedRoomMemberId.value === member.id ? null : member.id;

    const response = await globalPinRequest.patch(
        updateGlobalPin.url(props.room.public_id),
    );

    setGlobalPinnedRoomMemberId(response?.global_pinned_room_member_id ?? null);
}

async function kickParticipant(identity: string): Promise<void> {
    const member = memberByIdentity(identity);

    if (!member || !canModerateRoomMember(member) || kickRequest.processing) {
        return;
    }

    const response = await kickRequest.delete(
        kick.url({
            room: props.room.public_id,
            roomMember: member.id,
        }),
    );

    removeRoomMember(response?.member?.id ?? member.id);
}

async function banParticipant(identity: string): Promise<void> {
    const member = memberByIdentity(identity);

    if (!member || !canModerateRoomMember(member) || banRequest.processing) {
        return;
    }

    await banRequest.post(
        ban.url({
            room: props.room.public_id,
            roomMember: member.id,
        }),
    );

    removeRoomMember(member.id);
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

    <main
        class="relative h-dvh overflow-hidden bg-[#f7f7fb] text-slate-950 antialiased"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_8%,rgba(124,92,255,0.12),transparent_28%),radial-gradient(circle_at_82%_0%,rgba(59,130,246,0.08),transparent_26%),radial-gradient(circle_at_50%_100%,rgba(16,185,129,0.08),transparent_30%)]"
        />
        <header
            class="pointer-events-none fixed inset-x-0 top-0 z-20 bg-linear-to-b from-[#f7f7fb]/96 via-[#f7f7fb]/76 to-transparent px-5 py-4"
        >
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1
                        class="truncate text-lg font-semibold tracking-tight text-slate-950"
                    >
                        {{ room.name }}
                    </h1>
                </div>
                <div
                    class="flex items-center gap-2 text-xs text-slate-600 tabular-nums"
                >
                    <span
                        data-test="raised-hands-count"
                        :data-count="raisedHandsCount"
                        class="rounded-md border border-slate-200 bg-white/85 px-3 py-1 shadow-sm"
                    >
                        Руки: {{ raisedHandsCount }}
                    </span>
                </div>
            </div>
            <div class="hidden">
                <span data-test="connection-state">
                    {{ connectionState }}
                </span>
                <span data-test="echo-connection-state">
                    {{ echoConnectionStatus }}
                </span>
            </div>
        </header>

        <section
            class="relative flex h-full p-4 pt-20 pb-24 transition-[padding-right] duration-200 ease-out"
            :class="chatOpen ? 'lg:pr-[28.5rem]' : ''"
        >
            <ParticipantGrid
                class="min-h-0 w-full"
                :tiles="tilesWithApplicationState"
                :active-speaker-identity="activeSpeakerIdentity"
                :personal-pinned-identity="personalPinnedIdentity"
                :global-pinned-identity="globalPinnedIdentity"
                :tile-actions-by-identity="tileActionsByIdentity"
                :actions-disabled="tileActionsBusy"
                @media-host="setMediaHost"
                @pin-personal="togglePersonalPin"
                @pin-global="toggleGlobalPin"
                @kick="kickParticipant"
                @ban="banParticipant"
            />
        </section>

        <div
            v-if="!connected"
            class="fixed inset-0 z-10 flex items-center justify-center bg-white/72 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-md border border-slate-200 bg-white p-5 shadow-[0_24px_90px_rgba(15,23,42,0.16)]"
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
                <p class="mt-2 text-sm text-slate-500">
                    Камеру и микрофон можно оставить выключенными.
                </p>

                <RoomPasswordPanel
                    v-if="needsPassword"
                    v-model:password="joinRequest.password"
                    class="mt-4"
                    :error="joinRequest.errors.password"
                />

                <button
                    v-if="needsPassword"
                    class="mt-4 w-full rounded-md bg-violet-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-60"
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
            :room-public-id="room.public_id"
            :microphone-enabled="microphoneEnabled"
            :camera-enabled="cameraEnabled"
            :screen-share-enabled="screenShareEnabled"
            :can-share-screen="canShareScreen"
            :waiting-room-enabled="room.waiting_room_enabled"
            :can-moderate-waiting-room="can_moderate_waiting_room"
            :waiting-room-entries="pendingWaitingRoomEntries"
            :hand-raised="handRaised"
            :chat-open="chatOpen"
            :busy="busy || handRequest.processing"
            @toggle-microphone="toggleMicrophone"
            @toggle-camera="toggleCamera"
            @toggle-screen-share="toggleScreenShare"
            @toggle-hand="toggleHand"
            @toggle-chat="chatOpen = !chatOpen"
            @waiting-room-entry-decided="removeWaitingRoomEntry"
            @leave="leaveCall"
        />
    </main>
</template>
