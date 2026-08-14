<script setup lang="ts">
import type { CallRoom } from '../types';

type Props = {
    room: CallRoom;
    connected: boolean;
    busy: boolean;
    microphoneEnabled: boolean;
    cameraEnabled: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    join: [];
    mute: [];
    unmute: [];
    'start-camera': [];
    'stop-camera': [];
    leave: [];
}>();

function emitMicrophoneIntent(): void {
    if (props.microphoneEnabled) {
        emit('mute');

        return;
    }

    emit('unmute');
}

function emitCameraIntent(): void {
    if (props.cameraEnabled) {
        emit('stop-camera');

        return;
    }

    emit('start-camera');
}
</script>

<template>
    <section
        class="relative overflow-hidden rounded-3xl border border-neutral-200 bg-neutral-950 text-white shadow-sm dark:border-neutral-800"
    >
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(34,197,94,0.25),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.22),transparent_34%)]"
        />
        <div
            class="relative flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="space-y-2">
                <p class="text-sm text-neutral-300">
                    LiveKit room: {{ room.livekit_room_name }}
                </p>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ room.name }}
                </h1>
                <p
                    v-if="room.description"
                    class="max-w-2xl text-sm text-neutral-300"
                >
                    {{ room.description }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-if="!connected"
                    class="rounded-full bg-white px-5 py-2 text-sm font-medium text-neutral-950 transition hover:bg-neutral-200 disabled:cursor-not-allowed disabled:opacity-60"
                    type="button"
                    :disabled="busy"
                    @click="emit('join')"
                >
                    {{ busy ? 'Connecting...' : 'Join call' }}
                </button>
                <template v-else>
                    <button
                        class="rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/15 transition hover:bg-white/20"
                        type="button"
                        @click="emitMicrophoneIntent"
                    >
                        {{ microphoneEnabled ? 'Mute mic' : 'Unmute mic' }}
                    </button>
                    <button
                        class="rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/15 transition hover:bg-white/20"
                        type="button"
                        @click="emitCameraIntent"
                    >
                        {{ cameraEnabled ? 'Stop camera' : 'Start camera' }}
                    </button>
                    <button
                        class="rounded-full bg-red-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-400"
                        type="button"
                        @click="emit('leave')"
                    >
                        Leave
                    </button>
                </template>
            </div>
        </div>
    </section>
</template>
