<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';

import { ArrowRight, Clock3, User, Users } from '@lucide/vue';

import { computed, onMounted, ref } from 'vue';

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

import CallError from '@/features/calls/components/CallError.vue';
import DeviceSetupPanel from '@/features/calls/components/DeviceSetupPanel.vue';
import WaitingRoomModeratorPanel from '@/features/calls/components/WaitingRoomModeratorPanel.vue';
import WaitingRoomStatusPanel from '@/features/calls/components/WaitingRoomStatusPanel.vue';
import { useCallDeviceQuery } from '@/features/calls/composables/useCallDeviceQuery';
import { useMediaDevices } from '@/features/calls/composables/useMediaDevices';
import { useRoomApplicationEvents } from '@/features/calls/composables/useRoomApplicationEvents';
import type { CallPageProps } from '@/features/calls/types';

const props = defineProps<CallPageProps>();
const deviceQuery = useCallDeviceQuery();
const devices = useMediaDevices({
    initialSelectedDevices: deviceQuery.query.value,
    testingMediaMode: props.testing_media_mode,
});
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

onMounted(() => {
    if (devices.selectedDevices.value.videoInputId !== '') {
        void devices.startPreview();
    }
});
</script>

<template>
    <Head :title="`Ожидание: ${room.name}`" />

    <div class="min-h-screen bg-[#fafaf9] p-4 sm:p-6">
        <div
            data-test="application-realtime-panel"
            :data-echo-state="realtime.echoConnectionStatus.value"
            class="hidden"
        />

        <div class="mx-auto max-w-7xl space-y-4">
            <!-- Back to rooms -->
            <div>
                <a
                    href="/rooms"
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                    Вернуться к комнатам
                </a>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr_400px]">
                <!-- Left column -->
                <div class="space-y-4">
                    <!-- Combined: Room info + Waiting room status -->
                    <Card class="border-border/60 bg-white/70 shadow-sm p-0">
                        <CardContent class="p-5 sm:p-6 space-y-4">
                            <!-- Room info -->
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant="outline"
                                        class="border-amber-200/60 bg-amber-50 text-amber-700 text-xs"
                                    >
                                        <Clock3 class="size-3 mr-1" />
                                        Зал ожидания
                                    </Badge>
                                    <Badge
                                        v-if="room.has_password"
                                        variant="outline"
                                        class="border-border/60 bg-white/50 text-xs"
                                    >
                                        🔒 с паролем
                                    </Badge>
                                </div>
                                <h1 class="text-xl font-light tracking-tight">
                                    {{ room.name }}
                                </h1>
                                <p class="text-sm text-muted-foreground">
                                    Модератор проверит заявку и откроет доступ к
                                    комнате.
                                </p>
                            </div>

                            <hr class="border-border/60" />

                            <!-- Waiting room status -->
                            <WaitingRoomStatusPanel
                                :entry="currentWaitingRoomEntry"
                                compact
                            />
                        </CardContent>
                    </Card>

                    <!-- Error -->
                    <CallError
                        v-if="rejected"
                        message="Заявка отклонена. Обратитесь к модератору комнаты."
                    />

                    <!-- Devices setup -->
                    <DeviceSetupPanel
                        :audio-inputs="devices.audioInputs.value"
                        :video-inputs="devices.videoInputs.value"
                        :selected-audio-input-id="
                            devices.selectedDevices.value.audioInputId
                        "
                        :selected-video-input-id="
                            devices.selectedDevices.value.videoInputId
                        "
                        :preview-has-video="devices.previewHasVideo.value"
                        :preview-loading="devices.previewLoading.value"
                        :error="devices.deviceErrorMessage.value"
                        @update-audio-input="devices.setAudioInput"
                        @update-video-input="devices.setVideoInput"
                        @preview-host="devices.setPreviewHost"
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

            <!-- Footer -->
            <footer class="border-t border-border/60 pt-4 mt-2">
                <div
                    class="flex flex-col items-start justify-between gap-2 text-xs text-muted-foreground sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1.5">
                            <span
                                class="inline-block size-2 rounded-full bg-emerald-500"
                            ></span>
                            онлайн
                        </span>
                        <span>•</span>
                        <span>готово для команд</span>
                    </div>
                    <div class="flex gap-4">
                        <a
                            href="#"
                            class="transition hover:text-foreground hover:underline underline-offset-2"
                            >Помощь</a
                        >
                        <a
                            href="#"
                            class="transition hover:text-foreground hover:underline underline-offset-2"
                            >API</a
                        >
                        <a
                            href="#"
                            class="transition hover:text-foreground hover:underline underline-offset-2"
                            >Блог</a
                        >
                    </div>
                </div>
                <div
                    class="mt-3 text-center text-[10px] text-muted-foreground/60"
                >
                    Systemcall · групповые видеокомнаты · минимализм
                </div>
            </footer>
        </div>
    </div>
</template>
