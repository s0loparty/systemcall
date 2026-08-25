<script setup lang="ts">
import {
    Hand,
    MessageSquare,
    Mic,
    MicOff,
    MonitorUp,
    PhoneOff,
    UserRoundCheck,
    Video,
    VideoOff,
} from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { WaitingRoomEntry } from '../types';
import WaitingRoomModeratorPanel from './WaitingRoomModeratorPanel.vue';

type Props = {
    roomPublicId: string;
    microphoneEnabled: boolean;
    cameraEnabled: boolean;
    screenShareEnabled: boolean;
    canShareScreen: boolean;
    waitingRoomEnabled: boolean;
    canModerateWaitingRoom: boolean;
    waitingRoomEntries: WaitingRoomEntry[];
    handRaised: boolean;
    chatOpen: boolean;
    busy: boolean;
};

const props = defineProps<Props>();
const waitingRoomDialogOpen = ref(false);

const emit = defineEmits<{
    toggleMicrophone: [];
    toggleCamera: [];
    toggleScreenShare: [];
    toggleHand: [];
    toggleChat: [];
    waitingRoomEntryDecided: [entryId: number];
    leave: [];
}>();
</script>

<template>
    <nav
        class="pointer-events-auto fixed inset-x-0 bottom-5 z-30 mx-auto flex w-fit max-w-[calc(100%-2rem)] items-center gap-2 rounded-md border border-slate-200/80 bg-white/92 p-2 text-slate-950 shadow-[0_18px_70px_rgba(15,23,42,0.18)] backdrop-blur-xl"
        aria-label="Управление звонком"
    >
        <TooltipProvider :delay-duration="120">
            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        class="group flex h-11 min-w-11 cursor-pointer items-center justify-center gap-2 rounded-md px-3 text-sm font-semibold transition-[background-color,scale] duration-150 ease-out active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
                        :class="
                            microphoneEnabled
                                ? 'bg-slate-100 text-slate-950 hover:bg-slate-200'
                                : 'bg-red-500 text-white hover:bg-red-600'
                        "
                        type="button"
                        data-test="toggle-microphone"
                        :disabled="busy"
                        :aria-label="
                            microphoneEnabled
                                ? 'Выключить микрофон'
                                : 'Включить микрофон'
                        "
                        @click="emit('toggleMicrophone')"
                    >
                        <Mic v-if="props.microphoneEnabled" class="size-5" />
                        <MicOff v-else class="size-5" />
                        <span class="hidden pr-1 sm:inline">
                            {{ microphoneEnabled ? 'Микрофон' : 'Выкл.' }}
                        </span>
                    </button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>
                        {{
                            microphoneEnabled
                                ? 'Выключить микрофон'
                                : 'Включить микрофон'
                        }}
                    </p>
                </TooltipContent>
            </Tooltip>

            <Tooltip v-if="waitingRoomEnabled && canModerateWaitingRoom">
                <TooltipTrigger as-child>
                    <button
                        class="relative flex size-11 cursor-pointer items-center justify-center rounded-md bg-slate-100 text-slate-950 transition-[background-color,scale] duration-150 ease-out hover:bg-slate-200 active:scale-[0.96]"
                        type="button"
                        data-test="toggle-waiting-room-dialog"
                        :data-count="waitingRoomEntries.length"
                        aria-label="Открыть зал ожидания"
                        @click="waitingRoomDialogOpen = true"
                    >
                        <UserRoundCheck class="size-5" />
                        <Badge
                            v-if="waitingRoomEntries.length > 0"
                            data-test="call-waiting-room-count"
                            :data-count="waitingRoomEntries.length"
                            class="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-violet-600 px-1.5 text-[11px] leading-none text-white"
                        >
                            {{ waitingRoomEntries.length }}
                        </Badge>
                    </button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>Зал ожидания</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        class="group flex h-11 min-w-11 cursor-pointer items-center justify-center gap-2 rounded-md px-3 text-sm font-semibold transition-[background-color,scale] duration-150 ease-out active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
                        :class="
                            cameraEnabled
                                ? 'bg-slate-100 text-slate-950 hover:bg-slate-200'
                                : 'bg-red-500 text-white hover:bg-red-600'
                        "
                        type="button"
                        data-test="toggle-camera"
                        :disabled="busy"
                        :aria-label="
                            cameraEnabled
                                ? 'Выключить камеру'
                                : 'Включить камеру'
                        "
                        @click="emit('toggleCamera')"
                    >
                        <Video v-if="props.cameraEnabled" class="size-5" />
                        <VideoOff v-else class="size-5" />
                        <span class="hidden pr-1 sm:inline">
                            {{ cameraEnabled ? 'Камера' : 'Выкл.' }}
                        </span>
                    </button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>
                        {{
                            cameraEnabled
                                ? 'Выключить камеру'
                                : 'Включить камеру'
                        }}
                    </p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        class="flex size-11 cursor-pointer items-center justify-center rounded-md transition-[background-color,scale] duration-150 ease-out active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
                        :class="
                            handRaised
                                ? 'bg-amber-300 text-slate-950 hover:bg-amber-200'
                                : 'bg-slate-100 text-slate-950 hover:bg-slate-200'
                        "
                        type="button"
                        data-test="toggle-hand"
                        :data-hand-raised="handRaised ? 'true' : 'false'"
                        :disabled="busy"
                        :aria-label="
                            handRaised ? 'Опустить руку' : 'Поднять руку'
                        "
                        @click="emit('toggleHand')"
                    >
                        <Hand class="size-5" />
                    </button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>{{ handRaised ? 'Опустить руку' : 'Поднять руку' }}</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip v-if="canShareScreen">
                <TooltipTrigger as-child>
                    <button
                        class="flex size-11 cursor-pointer items-center justify-center rounded-md transition-[background-color,scale] duration-150 ease-out active:scale-[0.96] disabled:cursor-not-allowed disabled:opacity-60"
                        :class="
                            screenShareEnabled
                                ? 'bg-violet-600 text-white hover:bg-violet-500'
                                : 'bg-slate-100 text-slate-950 hover:bg-slate-200'
                        "
                        type="button"
                        data-test="toggle-screen-share"
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
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>
                        {{
                            screenShareEnabled
                                ? 'Остановить показ экрана'
                                : 'Показать экран'
                        }}
                    </p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        class="flex size-11 cursor-pointer items-center justify-center rounded-md transition-[background-color,scale] duration-150 ease-out active:scale-[0.96]"
                        :class="
                            chatOpen
                                ? 'bg-violet-600 text-white hover:bg-violet-500'
                                : 'bg-slate-100 text-slate-950 hover:bg-slate-200'
                        "
                        type="button"
                        data-test="toggle-chat"
                        :aria-label="chatOpen ? 'Закрыть чат' : 'Открыть чат'"
                        @click="emit('toggleChat')"
                    >
                        <MessageSquare class="size-5" />
                    </button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>{{ chatOpen ? 'Закрыть чат' : 'Открыть чат' }}</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        class="flex h-11 min-w-14 cursor-pointer items-center justify-center rounded-md bg-red-500 px-4 text-white transition-[background-color,scale] duration-150 ease-out hover:bg-red-600 active:scale-[0.96]"
                        type="button"
                        data-test="leave-call"
                        aria-label="Отключиться"
                        @click="emit('leave')"
                    >
                        <PhoneOff class="size-5" />
                    </button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>Отключиться</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </nav>

    <Dialog v-model:open="waitingRoomDialogOpen">
        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-md border-slate-200 bg-white p-0 sm:max-w-xl"
        >
            <DialogHeader class="px-5 pt-5">
                <DialogTitle>Зал ожидания</DialogTitle>
                <DialogDescription>
                    Проверьте заявки и решите, кого пустить в звонок.
                </DialogDescription>
            </DialogHeader>

            <div class="px-5 pb-5">
                <WaitingRoomModeratorPanel
                    :room-public-id="roomPublicId"
                    :entries="waitingRoomEntries"
                    @decided="emit('waitingRoomEntryDecided', $event)"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>
