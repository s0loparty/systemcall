<script setup lang="ts">
import {
    Hand,
    MessageSquare,
    Mic,
    MicOff,
    PhoneOff,
    Video,
    VideoOff,
} from '@lucide/vue';

type Props = {
    microphoneEnabled: boolean;
    cameraEnabled: boolean;
    handRaised: boolean;
    chatOpen: boolean;
    busy: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    toggleMicrophone: [];
    toggleCamera: [];
    toggleHand: [];
    toggleChat: [];
    leave: [];
}>();
</script>

<template>
    <nav
        class="pointer-events-auto fixed inset-x-0 bottom-4 z-30 mx-auto flex w-fit max-w-[calc(100%-2rem)] items-center gap-2 rounded-full border border-white/10 bg-neutral-950/90 p-2 text-white shadow-2xl shadow-black/40 backdrop-blur"
        aria-label="Управление звонком"
    >
        <button
            class="flex size-11 items-center justify-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60"
            :class="
                microphoneEnabled
                    ? 'bg-white/10 hover:bg-white/20'
                    : 'bg-red-500 hover:bg-red-400'
            "
            type="button"
            :disabled="busy"
            :aria-label="
                microphoneEnabled ? 'Выключить микрофон' : 'Включить микрофон'
            "
            @click="emit('toggleMicrophone')"
        >
            <Mic v-if="props.microphoneEnabled" class="size-5" />
            <MicOff v-else class="size-5" />
        </button>

        <button
            class="flex size-11 items-center justify-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60"
            :class="
                cameraEnabled
                    ? 'bg-white/10 hover:bg-white/20'
                    : 'bg-red-500 hover:bg-red-400'
            "
            type="button"
            :disabled="busy"
            :aria-label="cameraEnabled ? 'Выключить камеру' : 'Включить камеру'"
            @click="emit('toggleCamera')"
        >
            <Video v-if="props.cameraEnabled" class="size-5" />
            <VideoOff v-else class="size-5" />
        </button>

        <button
            class="flex size-11 items-center justify-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-60"
            :class="
                handRaised
                    ? 'bg-amber-400 text-amber-950 hover:bg-amber-300'
                    : 'bg-white/10 hover:bg-white/20'
            "
            type="button"
            :disabled="busy"
            :aria-label="handRaised ? 'Опустить руку' : 'Поднять руку'"
            @click="emit('toggleHand')"
        >
            <Hand class="size-5" />
        </button>

        <button
            class="flex size-11 items-center justify-center rounded-full transition"
            :class="
                chatOpen
                    ? 'bg-sky-400 text-sky-950 hover:bg-sky-300'
                    : 'bg-white/10 hover:bg-white/20'
            "
            type="button"
            :aria-label="chatOpen ? 'Закрыть чат' : 'Открыть чат'"
            @click="emit('toggleChat')"
        >
            <MessageSquare class="size-5" />
        </button>

        <button
            class="flex size-11 items-center justify-center rounded-full bg-red-600 transition hover:bg-red-500"
            type="button"
            aria-label="Отключиться"
            @click="emit('leave')"
        >
            <PhoneOff class="size-5" />
        </button>
    </nav>
</template>
