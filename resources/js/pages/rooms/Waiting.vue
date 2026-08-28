<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';

import { ArrowLeft, ArrowRight, User, Users } from '@lucide/vue';

import { computed, ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import { show as showCall } from '@/routes/rooms/call';
import { index as showRooms } from '@/routes/rooms/index';

import CallError from '@/features/calls/components/CallError.vue';
import DeviceSetupPanel from '@/features/calls/components/DeviceSetupPanel.vue';
import WaitingRoomModeratorPanel from '@/features/calls/components/WaitingRoomModeratorPanel.vue';
import WaitingRoomStatusPanel from '@/features/calls/components/WaitingRoomStatusPanel.vue';
import { useCallDeviceQuery } from '@/features/calls/composables/useCallDeviceQuery';
import { useMediaDevices } from '@/features/calls/composables/useMediaDevices';
import { useRoomApplicationEvents } from '@/features/calls/composables/useRoomApplicationEvents';
import type { CallPageProps } from '@/features/calls/types';

const page = usePage();
const currentUser = computed(() => page.props.auth.user ?? null);
const props = defineProps<CallPageProps>();
const deviceQuery = useCallDeviceQuery();
const devices = useMediaDevices({
    initialSelectedDevices: deviceQuery.query.value,
    testingMediaMode: props.testing_media_mode,
});
const {
    audioInputs,
    videoInputs,
    selectedDevices,
    audioPermissionRequested,
    videoPermissionRequested,
    audioPermissionLoading,
    videoPermissionLoading,
    previewHasVideo,
    previewLoading,
    deviceErrorMessage,
    setAudioInput,
    setVideoInput,
    setPreviewHost,
    requestAudioPermission,
    requestVideoPermission,
} = devices;
const currentWaitingRoomEntry = ref(props.current_waiting_room_entry);
const realtime = useRoomApplicationEvents(props.room.public_id, {
    waitingRoomEntries: props.waiting_room_entries,
    onWaitingRoomDecision: (event) => {
        if (event.waiting_room.id !== currentWaitingRoomEntry.value?.id) {
            return;
        }

        currentWaitingRoomEntry.value = event.waiting_room;

        if (event.waiting_room.status === 'approved') {
            goToCall();
        }
    },
});

const otherWaitingEntries = computed(() =>
    realtime.waitingRoomEntries.value.filter(
        (entry) => entry.id !== currentWaitingRoomEntry.value?.id,
    ),
);
const rejected = computed(
    () => currentWaitingRoomEntry.value?.status === 'rejected',
);

async function goToCall(): Promise<void> {
    await devices.stopPreview();

    router.visit(
        showCall.url(props.room.public_id, {
            query: deviceQuery.toQuery(devices.selectedDevices.value),
        }),
    );
}
</script>

<template>
    <Head :title="`Ожидание: ${room.name}`" />

    <div class="min-h-screen bg-[#fafaf9] p-4 sm:p-6">
        <div
            data-test="application-realtime-panel"
            :data-echo-state="realtime.echoConnectionStatus.value"
            class="hidden"
        />

        <div class="mx-auto max-w-7xl">
            <!-- Back to rooms -->
            <Button
                v-if="currentUser"
                :as="Link"
                :href="showRooms.url()"
                variant="ghost"
                class="mb-2"
            >
                <ArrowLeft />
                Вернуться к комнатам
            </Button>

            <div class="grid gap-4 lg:grid-cols-[1fr_400px]">
                <!-- Left column -->
                <div class="space-y-4">
                    <!-- Combined: Room info + Waiting room status -->
                    <Card class="border-border/60 bg-white/70 shadow-sm p-0">
                        <CardContent class="p-5 sm:p-6 space-y-4">
                            <!-- Room info -->
                            <div class="space-y-1.5">
                                <h1 class="text-xl font-light tracking-tight">
                                    {{ room.name }}
                                </h1>
                            </div>

                            <!-- <hr class="border-border/60" /> -->

                            <!-- Waiting room status -->
                            <WaitingRoomStatusPanel
                                :entry="currentWaitingRoomEntry"
                                compact
                            />
                            <p
                                class="rounded-md bg-slate-100 p-3 text-sm text-slate-500"
                            >
                                Страница обновится автоматически после решения
                                модератора.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Error -->
                    <CallError
                        v-if="rejected"
                        message="Заявка отклонена. Обратитесь к модератору комнаты."
                    />

                    <!-- Devices setup -->
                    <DeviceSetupPanel
                        :audio-inputs="audioInputs"
                        :video-inputs="videoInputs"
                        :selected-audio-input-id="selectedDevices.audioInputId"
                        :selected-video-input-id="selectedDevices.videoInputId"
                        :audio-permission-requested="audioPermissionRequested"
                        :video-permission-requested="videoPermissionRequested"
                        :audio-permission-loading="audioPermissionLoading"
                        :video-permission-loading="videoPermissionLoading"
                        :preview-has-video="previewHasVideo"
                        :preview-loading="previewLoading"
                        :error="deviceErrorMessage"
                        @update-audio-input="setAudioInput"
                        @update-video-input="setVideoInput"
                        @preview-host="setPreviewHost"
                        @request-audio-permission="requestAudioPermission"
                        @request-video-permission="requestVideoPermission"
                    />

                    <!-- Join button -->
                    <Button
                        v-if="currentWaitingRoomEntry?.status === 'approved'"
                        class="w-full rounded-full bg-neutral-900 text-white hover:bg-neutral-700 py-2.5 text-sm font-medium"
                        type="button"
                        @click="goToCall"
                    >
                        Войти в звонок
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>

                <!-- Right column -->
                <aside class="space-y-4">
                    <!-- Other waiting -->
                    <Card class="border-border/60 bg-white/70 shadow-sm">
                        <CardHeader class="pb-2 pt-4 px-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <CardTitle class="text-sm font-semibold"
                                        >Другие ожидающие</CardTitle
                                    >
                                    <CardDescription
                                        class="text-xs text-muted-foreground"
                                    >
                                        Участники, которые ждут решения.
                                    </CardDescription>
                                </div>
                                <Badge
                                    variant="secondary"
                                    class="bg-white/50 border-border/60 text-xs"
                                >
                                    {{ otherWaitingEntries.length }}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-0 px-5 pb-4">
                            <div
                                v-if="otherWaitingEntries.length === 0"
                                class="rounded-lg border border-dashed border-border/60 bg-white/50 p-4 text-center"
                            >
                                <Users
                                    class="h-6 w-6 text-muted-foreground/40 mx-auto"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Пока больше никто не ожидает.
                                </p>
                            </div>

                            <div v-else class="space-y-1.5">
                                <div
                                    v-for="entry in otherWaitingEntries"
                                    :key="entry.id"
                                    data-test="other-waiting-entry"
                                    :data-entry-id="entry.id"
                                    class="flex items-center gap-2.5 rounded-lg border border-border/60 bg-white/50 p-2.5"
                                >
                                    <div
                                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-neutral-100"
                                    >
                                        <User class="size-4 text-foreground" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm font-medium text-foreground truncate"
                                        >
                                            {{ entry.display_name }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                entry.guest
                                                    ? 'Гость'
                                                    : 'Пользователь'
                                            }}
                                        </p>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        class="border-amber-200/60 bg-amber-50 text-amber-700 text-[10px] px-2 py-0.5"
                                    >
                                        ожидает
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Moderator panel -->
                    <WaitingRoomModeratorPanel
                        v-if="
                            can_moderate_waiting_room &&
                            room.waiting_room_enabled
                        "
                        :room-public-id="room.public_id"
                        :entries="realtime.waitingRoomEntries.value"
                        @decided="realtime.removeWaitingRoomEntry"
                    />
                </aside>
            </div>
        </div>
    </div>
</template>
