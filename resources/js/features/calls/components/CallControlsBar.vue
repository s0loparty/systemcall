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

import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import type {
    RoomMemberSummary,
    RoomSubroomSummary,
    ScreenSharePreset,
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
const screenShareQualityOpen = ref(false);
const screenSharePreset = ref<ScreenSharePreset>('720p');
const controlsHidden = ref(false);
const isMobileViewport = ref(false);
let hideTimer: ReturnType<typeof setTimeout> | null = null;
let mobileMediaQuery: MediaQueryList | null = null;

const emit = defineEmits<{
    toggleMicrophone: [];
    toggleCamera: [];
    toggleScreenShare: [preset?: ScreenSharePreset];
    toggleHand: [];
    toggleChat: [];
    waitingRoomEntryDecided: [entryId: number];
    createSubroom: [name: string];
    moveMemberSubroom: [memberId: number, subroomPublicId: string | null];
    leave: [];
}>();

const controlsAreHidden = computed(
    () =>
        controlsHidden.value &&
        !waitingRoomDialogOpen.value &&
        !subroomsOpen.value &&
        !screenShareQualityOpen.value,
);

function clearHideTimer(): void {
    if (hideTimer === null) return;
    clearTimeout(hideTimer);
    hideTimer = null;
}

function scheduleAutoHide(): void {
    clearHideTimer();
    hideTimer = setTimeout(() => {
        controlsHidden.value = true;
    }, 10_000);
}

function showControls(): void {
    controlsHidden.value = false;
    scheduleAutoHide();
}

function updateMobileViewport(): void {
    isMobileViewport.value = Boolean(
        mobileMediaQuery?.matches ?? window.innerWidth < 768,
    );
}

function handleDesktopMouseMove(): void {
    if (!isMobileViewport.value) showControls();
}

function handleUserActivity(): void {
    showControls();
}

function handleScreenShareClick(): void {
    if (props.screenShareEnabled) {
        screenShareQualityOpen.value = false;
        emit('toggleScreenShare');
        return;
    }

    screenShareQualityOpen.value = true;
    showControls();
}

function startScreenShare(): void {
    screenShareQualityOpen.value = false;
    emit('toggleScreenShare', screenSharePreset.value);
}

onMounted(() => {
    mobileMediaQuery = window.matchMedia('(pointer: coarse), (max-width: 767px)');
    updateMobileViewport();
    mobileMediaQuery.addEventListener('change', updateMobileViewport);
    window.addEventListener('resize', updateMobileViewport);
    window.addEventListener('mousemove', handleDesktopMouseMove, { passive: true });
    window.addEventListener('pointerdown', handleUserActivity, { passive: true });
    window.addEventListener('touchstart', handleUserActivity, { passive: true });
    window.addEventListener('keydown', handleUserActivity);
    scheduleAutoHide();
});

onBeforeUnmount(() => {
    clearHideTimer();
    mobileMediaQuery?.removeEventListener('change', updateMobileViewport);
    window.removeEventListener('resize', updateMobileViewport);
    window.removeEventListener('mousemove', handleDesktopMouseMove);
    window.removeEventListener('pointerdown', handleUserActivity);
    window.removeEventListener('touchstart', handleUserActivity);
    window.removeEventListener('keydown', handleUserActivity);
});
</script>

<template>
    <nav
        data-test="call-controls-bar"
        :data-hidden="controlsAreHidden ? 'true' : 'false'"
        :data-mobile="isMobileViewport ? 'true' : 'false'"
        class="pointer-events-auto fixed inset-x-0 bottom-5 z-30 mx-auto flex w-fit max-w-[calc(100%-2rem)] items-center gap-2 rounded-md border border-slate-200/80 bg-white/92 p-2 text-slate-950 transition-[opacity,transform] duration-300 ease-out dark:border-black/82 dark:bg-black/92 max-[460px]:grid max-[460px]:grid-cols-4"
        :class="controlsAreHidden ? 'pointer-events-none translate-y-8 opacity-0 md:translate-y-12' : 'translate-y-0 opacity-100'"
        aria-label="Управление звонком"
    >
        <TooltipProvider :delay-duration="120">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button type="button" data-test="toggle-microphone" :disabled="busy" :aria-label="microphoneEnabled ? 'Выключить микрофон' : 'Включить микрофон'" :variant="microphoneEnabled ? 'outline' : 'destructive'" size="icon-lg" @click="emit('toggleMicrophone')">
                        <Mic v-if="props.microphoneEnabled" />
                        <MicOff v-else />
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="top"><p>{{ microphoneEnabled ? 'Выключить микрофон' : 'Включить микрофон' }}</p></TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button type="button" data-test="toggle-camera" :disabled="busy" :aria-label="cameraEnabled ? 'Выключить камеру' : 'Включить камеру'" :variant="cameraEnabled ? 'outline' : 'destructive'" size="icon-lg" @click="emit('toggleCamera')">
                        <Video v-if="props.cameraEnabled" />
                        <VideoOff v-else />
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="top"><p>{{ cameraEnabled ? 'Выключить камеру' : 'Включить камеру' }}</p></TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button type="button" data-test="toggle-subrooms-sheet" :data-count="roomSubrooms.length" :disabled="subroomsBusy" aria-label="Открыть подкомнаты" variant="outline" size="icon-lg" @click="subroomsOpen = !subroomsOpen"><DoorOpen /></Button>
                </TooltipTrigger>
                <TooltipContent>Подкомнаты</TooltipContent>
            </Tooltip>

            <Tooltip v-if="waitingRoomEnabled && canModerateWaitingRoom">
                <TooltipTrigger as-child>
                    <Button data-test="toggle-waiting-room-dialog" :data-count="waitingRoomEntries.length" aria-label="Открыть зал ожидания" variant="outline" size="icon-lg" class="relative" @click="waitingRoomDialogOpen = true">
                        <UserRoundCheck />
                        <Badge v-if="waitingRoomEntries.length > 0" data-test="call-waiting-room-count" :data-count="waitingRoomEntries.length" class="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-violet-600 px-1.5 text-[11px] leading-none text-white">{{ waitingRoomEntries.length }}</Badge>
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="top"><p>Зал ожидания</p></TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button :class="handRaised ? 'bg-amber-300 hover:bg-amber-300' : ''" data-test="toggle-hand" :data-hand-raised="handRaised ? 'true' : 'false'" :disabled="busy" :aria-label="handRaised ? 'Опустить руку' : 'Поднять руку'" variant="outline" size="icon-lg" @click="emit('toggleHand')"><Hand /></Button>
                </TooltipTrigger>
                <TooltipContent side="top"><p>{{ handRaised ? 'Опустить руку' : 'Поднять руку' }}</p></TooltipContent>
            </Tooltip>

            <div v-if="canShareScreen" class="relative">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            :class="screenShareEnabled ? 'bg-violet-600 text-white hover:bg-violet-500' : ''"
                            data-test="toggle-screen-share"
                            :disabled="busy"
                            :aria-label="screenShareEnabled ? 'Остановить показ экрана' : 'Показать экран'"
                            variant="outline"
                            size="icon-lg"
                            @click="handleScreenShareClick"
                        >
                            <MonitorUp />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent side="top"><p>{{ screenShareEnabled ? 'Остановить показ экрана' : 'Показать экран' }}</p></TooltipContent>
                </Tooltip>

                <div
                    v-if="screenShareQualityOpen && !screenShareEnabled"
                    data-test="screen-share-quality-selector"
                    class="absolute bottom-[calc(100%+0.75rem)] left-1/2 w-56 -translate-x-1/2 rounded-md border border-slate-200 bg-white p-3 shadow-xl dark:border-neutral-700 dark:bg-neutral-900"
                >
                    <p class="mb-2 text-xs font-medium text-slate-600 dark:text-slate-300">Качество демонстрации</p>
                    <Select v-model="screenSharePreset">
                        <SelectTrigger data-test="screen-share-quality" class="w-full">
                            <SelectValue placeholder="Выберите качество" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="480p">480p · экономно</SelectItem>
                            <SelectItem value="720p">720p · рекомендуется</SelectItem>
                            <SelectItem value="1024p">1024p · высокое качество</SelectItem>
                        </SelectContent>
                    </Select>
                    <div class="mt-3 flex justify-end gap-2">
                        <Button type="button" size="sm" variant="ghost" @click="screenShareQualityOpen = false">Отмена</Button>
                        <Button type="button" data-test="start-screen-share" size="sm" @click="startScreenShare">Поделиться</Button>
                    </div>
                </div>
            </div>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button :class="chatOpen ? 'bg-violet-600 text-white hover:bg-violet-500 hover:text-white' : ''" type="button" data-test="toggle-chat" :aria-label="chatOpen ? 'Закрыть чат' : 'Открыть чат'" variant="outline" size="icon-lg" @click="emit('toggleChat')"><MessageSquare /></Button>
                </TooltipTrigger>
                <TooltipContent side="top"><p>{{ chatOpen ? 'Закрыть чат' : 'Открыть чат' }}</p></TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button type="button" data-test="leave-call" aria-label="Отключиться" variant="destructive" size="icon-lg" @click="emit('leave')"><PhoneOff /></Button>
                </TooltipTrigger>
                <TooltipContent side="top"><p>Отключиться</p></TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </nav>

    <button v-if="isMobileViewport && controlsAreHidden" type="button" data-test="show-call-controls-tab" class="fixed inset-x-0 bottom-0 z-30 mx-auto h-7 w-24 rounded-t-md border border-b-0 border-slate-200/80 bg-white/92 text-xs font-medium text-slate-600 shadow-[0_-8px_28px_rgba(15,23,42,0.12)] active:scale-[0.96]" aria-label="Показать управление звонком" @click="showControls">Управление</button>

    <SubroomManagementSheet
        v-if="canManageSubrooms"
        v-model="subroomsOpen"
        :members="roomMembers"
        :subrooms="roomSubrooms"
        :disabled="subroomsBusy"
        @create="emit('createSubroom', $event)"
        @move="(memberId, subroomPublicId) => emit('moveMemberSubroom', memberId, subroomPublicId)"
    />

    <Dialog v-model:open="waitingRoomDialogOpen">
        <DialogContent class="max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-md border-slate-200 bg-white p-0 dark:border-neutral-800 dark:bg-neutral-900 sm:max-w-xl">
            <DialogHeader class="px-5 pt-5">
                <DialogTitle>Зал ожидания</DialogTitle>
                <DialogDescription>Проверьте заявки и решите, кого пустить в звонок.</DialogDescription>
            </DialogHeader>
            <div class="px-5 pb-5">
                <WaitingRoomModeratorPanel :room-public-id="roomPublicId" :entries="waitingRoomEntries" @decided="emit('waitingRoomEntryDecided', $event)" />
            </div>
        </DialogContent>
    </Dialog>
</template>
