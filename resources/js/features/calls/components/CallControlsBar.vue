<script setup lang="ts">
import {
    Hand,
    MessageSquare,
    Mic,
    MicOff,
    MonitorUp,
    PhoneOff,
    Video,
    VideoOff,
} from '@lucide/vue';

type Props = {
    microphoneEnabled: boolean;
    cameraEnabled: boolean;
    screenShareEnabled: boolean;
    canShareScreen: boolean;
    handRaised: boolean;
    chatOpen: boolean;
    busy: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    toggleMicrophone: [];
    toggleCamera: [];
    toggleScreenShare: [];
    toggleHand: [];
    toggleChat: [];
    leave: [];
}>();
</script>

<template>
    <nav
        class="pointer-events-auto fixed inset-x-0 bottom-5 z-30 mx-auto flex w-fit max-w-[calc(100%-2rem)] items-center gap-2 rounded-full bg-[#202124]/95 p-2 text-white shadow-[0_18px_70px_rgba(0,0,0,0.42),inset_0_0_0_1px_rgba(255,255,255,0.08)] backdrop-blur-xl"
        aria-label="Управление звонком"
    >
        <button
            class="group flex h-11 min-w-11 items-center justify-center gap-2 rounded-full px-3 text-sm font-medium transition-[background-color,scale] duration-150 ease-out active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
            :class="
                microphoneEnabled
                    ? 'bg-[#3c4043] hover:bg-[#4a4d51]'
                    : 'bg-[#ea4335] hover:bg-[#f05246]'
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
            <span class="hidden pr-1 sm:inline">
                {{ microphoneEnabled ? 'Микрофон' : 'Выкл.' }}
            </span>
        </button>

        <button
            class="group flex h-11 min-w-11 items-center justify-center gap-2 rounded-full px-3 text-sm font-medium transition-[background-color,scale] duration-150 ease-out active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
            :class="
                cameraEnabled
                    ? 'bg-[#3c4043] hover:bg-[#4a4d51]'
                    : 'bg-[#ea4335] hover:bg-[#f05246]'
            "
            type="button"
            :disabled="busy"
            :aria-label="cameraEnabled ? 'Выключить камеру' : 'Включить камеру'"
            @click="emit('toggleCamera')"
        >
            <Video v-if="props.cameraEnabled" class="size-5" />
            <VideoOff v-else class="size-5" />
            <span class="hidden pr-1 sm:inline">
                {{ cameraEnabled ? 'Камера' : 'Выкл.' }}
            </span>
        </button>

        <button
            class="flex size-11 items-center justify-center rounded-full transition-[background-color,scale] duration-150 ease-out active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
            :class="
                handRaised
                    ? 'bg-[#fbbc04] text-[#202124] hover:bg-[#ffd15c]'
                    : 'bg-[#3c4043] hover:bg-[#4a4d51]'
            "
            type="button"
            :disabled="busy"
            :aria-label="handRaised ? 'Опустить руку' : 'Поднять руку'"
            @click="emit('toggleHand')"
        >
            <Hand class="size-5" />
        </button>

        <button
            v-if="canShareScreen"
            class="flex size-11 items-center justify-center rounded-full transition-[background-color,scale] duration-150 ease-out active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
            :class="
                screenShareEnabled
                    ? 'bg-[#8ab4f8] text-[#202124] hover:bg-[#a8c7fa]'
                    : 'bg-[#3c4043] hover:bg-[#4a4d51]'
            "
            type="button"
            :disabled="busy"
            :aria-label="
                screenShareEnabled
                    ? 'Остановить показ экрана'
                    : 'Показать экран'
            "
            @click="emit('toggleScreenShare')"
        >
            <MonitorUp class="size-5" />
        </button>

        <button
            class="flex size-11 items-center justify-center rounded-full transition-[background-color,scale] duration-150 ease-out active:scale-[0.96]"
            :class="
                chatOpen
                    ? 'bg-[#8ab4f8] text-[#202124] hover:bg-[#a8c7fa]'
                    : 'bg-[#3c4043] hover:bg-[#4a4d51]'
            "
            type="button"
            :aria-label="chatOpen ? 'Закрыть чат' : 'Открыть чат'"
            @click="emit('toggleChat')"
        >
            <MessageSquare class="size-5" />
        </button>

        <button
            class="flex h-11 min-w-14 items-center justify-center rounded-full bg-[#ea4335] px-4 transition-[background-color,scale] duration-150 ease-out hover:bg-[#f05246] active:scale-[0.96]"
            type="button"
            aria-label="Отключиться"
            @click="emit('leave')"
        >
            <PhoneOff class="size-5" />
        </button>
    </nav>
</template>
