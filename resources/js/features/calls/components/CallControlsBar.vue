<script setup lang="ts">
import {
    DoorOpen,
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
import { Button } from '@/components/ui/button';
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

import type {
    RoomMemberSummary,
    RoomSubroomSummary,
    WaitingRoomEntry,
} from '../types';
import SubroomManagementSheet from './SubroomManagementSheet.vue';
import WaitingRoomModeratorPanel from './WaitingRoomModeratorPanel.vue';

type Props = {
    roomPublicId: string;
    microphoneEnabled: boolean;
    cameraEnabled: boolean;
    screenShareEnabled: boolean;
    canShareScreen: boolean;
    waitingRoomEnabled: boolean;
    canModerateWaitingRoom: boolean;
    canManageSubrooms: boolean;
    waitingRoomEntries: WaitingRoomEntry[];
    roomMembers: RoomMemberSummary[];
    roomSubrooms: RoomSubroomSummary[];
    handRaised: boolean;
    chatOpen: boolean;
    busy: boolean;
    subroomsBusy: boolean;
};

const props = defineProps<Props>();
const waitingRoomDialogOpen = ref(false);
const subroomsOpen = ref(false);

const emit = defineEmits<{
    toggleMicrophone: [];
    toggleCamera: [];
    toggleScreenShare: [];
    toggleHand: [];
    toggleChat: [];
    waitingRoomEntryDecided: [entryId: number];
    createSubroom: [name: string];
    moveMemberSubroom: [memberId: number, subroomPublicId: string | null];
    leave: [];
}>();
</script>

<template>
    <nav
        class="pointer-events-auto fixed inset-x-0 bottom-5 z-30 mx-auto flex w-fit max-w-[calc(100%-2rem)] items-center gap-2 rounded-md border border-slate-200/80 bg-white/92 p-2 text-slate-950 max-[460px]:grid max-[460px]:grid-cols-4"
        aria-label="Управление звонком"
    >
        <TooltipProvider :delay-duration="120">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        type="button"
                        data-test="toggle-microphone"
                        :disabled="busy"
                        :aria-label="
                            microphoneEnabled
                                ? 'Выключить микрофон'
                                : 'Включить микрофон'
                        "
                        :variant="microphoneEnabled ? 'outline' : 'destructive'"
                        size="icon-lg"
                        @click="emit('toggleMicrophone')"
                    >
                        <Mic v-if="props.microphoneEnabled" />
                        <MicOff v-else />
                    </Button>
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

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        type="button"
                        data-test="toggle-camera"
                        :disabled="busy"
                        :aria-label="
                            cameraEnabled
                                ? 'Выключить камеру'
                                : 'Включить камеру'
                        "
                        :variant="cameraEnabled ? 'outline' : 'destructive'"
                        size="icon-lg"
                        @click="emit('toggleCamera')"
                    >
                        <Video v-if="props.cameraEnabled" />
                        <VideoOff v-else />
                    </Button>
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
                    <Button
                        type="button"
                        data-test="toggle-subrooms-sheet"
                        :data-count="roomSubrooms.length"
                        :disabled="subroomsBusy"
                        aria-label="Открыть подкомнаты"
                        variant="outline"
                        size="icon-lg"
                        @click="subroomsOpen = !subroomsOpen"
                    >
                        <DoorOpen />
                    </Button>
                </TooltipTrigger>

                <TooltipContent> Подкомнаты </TooltipContent>
            </Tooltip>

            <Tooltip v-if="waitingRoomEnabled && canModerateWaitingRoom">
                <TooltipTrigger as-child>
                    <Button
                        data-test="toggle-waiting-room-dialog"
                        :data-count="waitingRoomEntries.length"
                        aria-label="Открыть зал ожидания"
                        variant="outline"
                        size="icon-lg"
                        class="relative"
                        @click="waitingRoomDialogOpen = true"
                    >
                        <UserRoundCheck />
                        <Badge
                            v-if="waitingRoomEntries.length > 0"
                            data-test="call-waiting-room-count"
                            :data-count="waitingRoomEntries.length"
                            class="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-violet-600 px-1.5 text-[11px] leading-none text-white"
                        >
                            {{ waitingRoomEntries.length }}
                        </Badge>
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>Зал ожидания</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        :class="
                            handRaised ? 'bg-amber-300 hover:bg-amber-300' : ''
                        "
                        data-test="toggle-hand"
                        :data-hand-raised="handRaised ? 'true' : 'false'"
                        :disabled="busy"
                        :aria-label="
                            handRaised ? 'Опустить руку' : 'Поднять руку'
                        "
                        variant="outline"
                        size="icon-lg"
                        @click="emit('toggleHand')"
                    >
                        <Hand />
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>{{ handRaised ? 'Опустить руку' : 'Поднять руку' }}</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip v-if="canShareScreen">
                <TooltipTrigger as-child>
                    <Button
                        :class="
                            screenShareEnabled
                                ? 'bg-violet-600 text-white hover:bg-violet-500'
                                : ''
                        "
                        data-test="toggle-screen-share"
                        :disabled="busy"
                        :aria-label="
                            screenShareEnabled
                                ? 'Остановить показ экрана'
                                : 'Показать экран'
                        "
                        variant="outline"
                        size="icon-lg"
                        @click="emit('toggleScreenShare')"
                    >
                        <MonitorUp />
                    </Button>
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
                    <Button
                        :class="
                            chatOpen
                                ? 'bg-violet-600 text-white hover:text-white hover:bg-violet-500'
                                : ''
                        "
                        type="button"
                        data-test="toggle-chat"
                        :aria-label="chatOpen ? 'Закрыть чат' : 'Открыть чат'"
                        variant="outline"
                        size="icon-lg"
                        @click="emit('toggleChat')"
                    >
                        <MessageSquare />
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>{{ chatOpen ? 'Закрыть чат' : 'Открыть чат' }}</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        type="button"
                        data-test="leave-call"
                        aria-label="Отключиться"
                        variant="destructive"
                        size="icon-lg"
                        @click="emit('leave')"
                    >
                        <PhoneOff />
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="top">
                    <p>Отключиться</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </nav>

    <SubroomManagementSheet
        v-if="canManageSubrooms"
        v-model="subroomsOpen"
        :members="roomMembers"
        :subrooms="roomSubrooms"
        :disabled="subroomsBusy"
        @create="emit('createSubroom', $event)"
        @move="
            (memberId, subroomPublicId) =>
                emit('moveMemberSubroom', memberId, subroomPublicId)
        "
    />

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
