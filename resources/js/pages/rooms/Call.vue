<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApplicationRealtimePanel from '@/features/calls/components/ApplicationRealtimePanel.vue';
import CallError from '@/features/calls/components/CallError.vue';
import CallHeader from '@/features/calls/components/CallHeader.vue';
import DeviceSetupPanel from '@/features/calls/components/DeviceSetupPanel.vue';
import ParticipantGrid from '@/features/calls/components/ParticipantGrid.vue';
import RoomApplicationStatePanel from '@/features/calls/components/RoomApplicationStatePanel.vue';
import RoomModerationPanel from '@/features/calls/components/RoomModerationPanel.vue';
import RoomPasswordPanel from '@/features/calls/components/RoomPasswordPanel.vue';
import WaitingRoomModeratorPanel from '@/features/calls/components/WaitingRoomModeratorPanel.vue';
import WaitingRoomStatusPanel from '@/features/calls/components/WaitingRoomStatusPanel.vue';
import { useCallSession } from '@/features/calls/composables/useCallSession';
import { useRoomApplicationEvents } from '@/features/calls/composables/useRoomApplicationEvents';
import type {
    CallRoom,
    RoomMessageSummary,
    RoomMemberSummary,
    WaitingRoomEntry,
} from '@/features/calls/types';

type RoomPageProps = {
    room: CallRoom;
    can_join_without_password: boolean;
    can_moderate_waiting_room: boolean;
    can_moderate_members: boolean;
    current_room_member_id: number | null;
    global_pinned_room_member_id: number | null;
    room_members: RoomMemberSummary[];
    room_messages: RoomMessageSummary[];
    waiting_room_entries: WaitingRoomEntry[];
    current_waiting_room_entry: WaitingRoomEntry | null;
};

const props = defineProps<RoomPageProps>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const defaultDisplayName = computed(() => currentUser.value?.name ?? 'Guest');
const personalPinnedRoomMemberId = ref<number | null>(null);
const call = useCallSession({
    roomPublicId: props.room.public_id,
    displayName: defaultDisplayName.value,
});
call.waitingRoomEntry.value = props.current_waiting_room_entry;

const realtime = useRoomApplicationEvents(props.room.public_id, {
    roomMembers: props.room_members,
    roomMessages: props.room_messages,
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
    audioInputs,
    videoInputs,
    selectedDevices,
    previewEnabled,
    previewLoading,
    loadDevices,
    startPreview,
    stopPreview,
    setPreviewHost,
    setAudioInput,
    setVideoInput,
    connect,
    disconnect,
    toggleMicrophone,
    toggleCamera,
    toggleScreenShare,
    setMediaHost,
} = call;
const {
    echoConnectionStatus,
    activities,
    roomMembers,
    upsertRoomMember,
    removeRoomMember,
    roomMessages,
    appendRoomMessage,
    globalPinnedRoomMemberId,
    setGlobalPinnedRoomMemberId,
    waitingRoomEntries,
    removeWaitingRoomEntry,
} = realtime;

const needsPassword = computed(
    () => props.room.has_password && !props.can_join_without_password,
);
const currentRoomMemberId = computed(
    () => joinedMember.value?.id ?? props.current_room_member_id,
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

    if (joinedMember.value) {
        upsertRoomMember(joinedMember.value);
    }
}

function setPersonalPin(memberId: number | null): void {
    personalPinnedRoomMemberId.value = memberId;
}
</script>

<template>
    <Head :title="`${room.name} call`" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-hidden p-4">
        <CallHeader
            :room="room"
            :connected="connected"
            :connection-state="connectionState"
            :busy="busy"
            :microphone-enabled="microphoneEnabled"
            :camera-enabled="cameraEnabled"
            :screen-share-enabled="screenShareEnabled"
            :waiting-room-status="waitingRoomEntry?.status ?? null"
            @join="connectToCall"
            @mute="toggleMicrophone"
            @unmute="toggleMicrophone"
            @start-camera="toggleCamera"
            @stop-camera="toggleCamera"
            @start-screen-share="toggleScreenShare"
            @stop-screen-share="toggleScreenShare"
            @leave="disconnect"
        />

        <ApplicationRealtimePanel
            :connection-state="echoConnectionStatus"
            :activities="activities"
        />

        <WaitingRoomStatusPanel :entry="waitingRoomEntry" />

        <RoomApplicationStatePanel
            :room-public-id="room.public_id"
            :current-member-id="currentRoomMemberId"
            :can-moderate-members="can_moderate_members"
            :members="roomMembers"
            :messages="roomMessages"
            :global-pinned-member-id="globalPinnedRoomMemberId"
            :personal-pinned-member-id="personalPinnedRoomMemberId"
            @message="appendRoomMessage"
            @member="upsertRoomMember"
            @global-pin="setGlobalPinnedRoomMemberId"
            @personal-pin="setPersonalPin"
        />

        <WaitingRoomModeratorPanel
            v-if="can_moderate_waiting_room && room.waiting_room_enabled"
            :room-public-id="room.public_id"
            :entries="waitingRoomEntries"
            @decided="removeWaitingRoomEntry"
        />

        <RoomModerationPanel
            v-if="can_moderate_members"
            :room-public-id="room.public_id"
            :current-member-id="currentRoomMemberId"
            :members="roomMembers"
            @updated="upsertRoomMember"
            @removed="removeRoomMember"
        />

        <DeviceSetupPanel
            v-if="!connected"
            :audio-inputs="audioInputs"
            :video-inputs="videoInputs"
            :selected-audio-input-id="selectedDevices.audioInputId"
            :selected-video-input-id="selectedDevices.videoInputId"
            :preview-enabled="previewEnabled"
            :preview-loading="previewLoading"
            :error="deviceErrorMessage"
            @refresh="loadDevices(true)"
            @preview="startPreview"
            @stop-preview="stopPreview"
            @update-audio-input="setAudioInput"
            @update-video-input="setVideoInput"
            @preview-host="setPreviewHost"
        />

        <RoomPasswordPanel
            v-if="needsPassword && !connected"
            v-model:password="joinRequest.password"
            :error="joinRequest.errors.password"
        />

        <CallError v-if="errorMessage" :message="errorMessage" />
        <CallError
            v-if="deviceErrorMessage && connected"
            :message="deviceErrorMessage"
        />

        <ParticipantGrid
            :tiles="tiles"
            :active-speaker-identity="activeSpeakerIdentity"
            :personal-pinned-identity="personalPinnedIdentity"
            :global-pinned-identity="globalPinnedIdentity"
            @media-host="setMediaHost"
        />
    </div>
</template>
