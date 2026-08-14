<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CallError from '@/features/calls/components/CallError.vue';
import CallHeader from '@/features/calls/components/CallHeader.vue';
import ParticipantGrid from '@/features/calls/components/ParticipantGrid.vue';
import RoomPasswordPanel from '@/features/calls/components/RoomPasswordPanel.vue';
import { useCallSession } from '@/features/calls/composables/useCallSession';
import type { CallRoom } from '@/features/calls/types';

type RoomPageProps = {
    room: CallRoom;
    can_join_without_password: boolean;
};

const props = defineProps<RoomPageProps>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const defaultDisplayName = computed(() => currentUser.value?.name ?? 'Guest');
const call = useCallSession({
    roomPublicId: props.room.public_id,
    displayName: defaultDisplayName.value,
});
const {
    joinRequest,
    connected,
    busy,
    microphoneEnabled,
    cameraEnabled,
    tiles,
    errorMessage,
    connect,
    disconnect,
    toggleMicrophone,
    toggleCamera,
    setMediaHost,
} = call;

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
            :busy="busy"
            :microphone-enabled="microphoneEnabled"
            :camera-enabled="cameraEnabled"
            @join="connect"
            @mute="toggleMicrophone"
            @unmute="toggleMicrophone"
            @start-camera="toggleCamera"
            @stop-camera="toggleCamera"
            @leave="disconnect"
        />

        <RoomPasswordPanel
            v-if="needsPassword && !connected"
            v-model:password="joinRequest.password"
            :error="joinRequest.errors.password"
        />

        <CallError v-if="errorMessage" :message="errorMessage" />

        <ParticipantGrid :tiles="tiles" @media-host="setMediaHost" />
    </div>
</template>
