<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationRealtimePanel from '@/features/calls/components/ApplicationRealtimePanel.vue';
import CallError from '@/features/calls/components/CallError.vue';
import CallHeader from '@/features/calls/components/CallHeader.vue';
import DeviceSetupPanel from '@/features/calls/components/DeviceSetupPanel.vue';
import ParticipantGrid from '@/features/calls/components/ParticipantGrid.vue';
import RoomPasswordPanel from '@/features/calls/components/RoomPasswordPanel.vue';
import WaitingRoomModeratorPanel from '@/features/calls/components/WaitingRoomModeratorPanel.vue';
import WaitingRoomStatusPanel from '@/features/calls/components/WaitingRoomStatusPanel.vue';
import { useCallSession } from '@/features/calls/composables/useCallSession';
import { useRoomApplicationEvents } from '@/features/calls/composables/useRoomApplicationEvents';
import type { CallRoom, WaitingRoomEntry } from '@/features/calls/types';

type RoomPageProps = {
    room: CallRoom;
    can_join_without_password: boolean;
    can_moderate_waiting_room: boolean;
    waiting_room_entries: WaitingRoomEntry[];
    current_waiting_room_entry: WaitingRoomEntry | null;
};

const props = defineProps<RoomPageProps>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const defaultDisplayName = computed(() => currentUser.value?.name ?? 'Guest');
const call = useCallSession({
    roomPublicId: props.room.public_id,
    displayName: defaultDisplayName.value,
});
call.waitingRoomEntry.value = props.current_waiting_room_entry;

const realtime = useRoomApplicationEvents(props.room.public_id, {
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
    waitingRoomEntries,
    removeWaitingRoomEntry,
} = realtime;

const needsPassword = computed(
    () => props.room.has_password && !props.can_join_without_password,
);
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
            @join="connect"
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

        <WaitingRoomModeratorPanel
            v-if="can_moderate_waiting_room && room.waiting_room_enabled"
            :room-public-id="room.public_id"
            :entries="waitingRoomEntries"
            @decided="removeWaitingRoomEntry"
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
            @media-host="setMediaHost"
        />
    </div>
</template>
