<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowRight, Camera, Clock3, Mic, Users } from '@lucide/vue';
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
import CallError from '@/features/calls/components/CallError.vue';
import DeviceSetupPanel from '@/features/calls/components/DeviceSetupPanel.vue';
import WaitingRoomModeratorPanel from '@/features/calls/components/WaitingRoomModeratorPanel.vue';
import WaitingRoomStatusPanel from '@/features/calls/components/WaitingRoomStatusPanel.vue';
import { useCallDeviceQuery } from '@/features/calls/composables/useCallDeviceQuery';
import { useMediaDevices } from '@/features/calls/composables/useMediaDevices';
import { useRoomApplicationEvents } from '@/features/calls/composables/useRoomApplicationEvents';
import type { CallPageProps } from '@/features/calls/types';
import { show as showCall } from '@/routes/rooms/call';

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

const selectedAudioLabel = computed(() =>
    deviceLabel(
        devices.audioInputs.value,
        devices.selectedDevices.value.audioInputId,
        'Без микрофона',
    ),
);
const selectedVideoLabel = computed(() =>
    deviceLabel(
        devices.videoInputs.value,
        devices.selectedDevices.value.videoInputId,
        'Без камеры',
    ),
);
const otherWaitingEntries = computed(() =>
    realtime.waitingRoomEntries.value.filter(
        (entry) => entry.id !== currentWaitingRoomEntry.value?.id,
    ),
);
const rejected = computed(
    () => currentWaitingRoomEntry.value?.status === 'rejected',
);

function deviceLabel(
    devicesList: MediaDeviceInfo[],
    selectedDeviceId: string,
    fallback: string,
): string {
    if (selectedDeviceId === '') {
        return fallback;
    }

    return (
        devicesList.find((device) => device.deviceId === selectedDeviceId)
            ?.label ?? fallback
    );
}

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

    <main
        class="relative min-h-dvh overflow-hidden bg-[#fbfaff] p-4 text-slate-950 antialiased sm:p-6"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_8%,rgba(251,191,36,0.18),transparent_30%),radial-gradient(circle_at_84%_6%,rgba(124,92,255,0.14),transparent_24%),radial-gradient(circle_at_50%_88%,rgba(16,185,129,0.10),transparent_24%)]"
        />

        <div
            data-test="application-realtime-panel"
            :data-echo-state="realtime.echoConnectionStatus.value"
            class="hidden"
        />

        <div
            class="relative mx-auto grid max-w-6xl gap-5 lg:grid-cols-[minmax(0,1fr)_24rem]"
        >
            <section class="grid gap-5">
                <Card
                    class="overflow-hidden rounded-md border-slate-200/80 bg-white/90 shadow-2xl shadow-violet-100/60 backdrop-blur"
                >
                    <CardHeader>
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <Badge
                                    variant="outline"
                                    class="rounded-md border-amber-100 bg-amber-50 px-3 py-1.5 text-amber-700"
                                >
                                    <Clock3 class="size-3.5" />
                                    Зал ожидания
                                </Badge>
                                <CardTitle
                                    class="mt-4 text-3xl tracking-tight md:text-4xl"
                                >
                                    {{ room.name }}
                                </CardTitle>
                                <CardDescription
                                    class="mt-2 max-w-2xl text-base leading-7"
                                >
                                    Модератор проверит заявку и откроет доступ к
                                    комнате. Пока можно изменить устройства и
                                    убедиться, что всё готово.
                                </CardDescription>
                            </div>

                            <div
                                class="flex size-14 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-amber-400 to-violet-600 text-white shadow-lg shadow-amber-100"
                            >
                                <Users class="size-6" />
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="grid gap-3 sm:grid-cols-2">
                        <div
                            data-test="selected-audio-device"
                            class="rounded-md border border-slate-200 bg-slate-50 p-4"
                        >
                            <Mic class="size-5 text-emerald-600" />
                            <p class="mt-3 text-xs font-medium text-slate-500">
                                Микрофон
                            </p>
                            <p
                                class="mt-1 text-sm font-semibold text-slate-950"
                            >
                                {{ selectedAudioLabel }}
                            </p>
                        </div>
                        <div
                            data-test="selected-video-device"
                            class="rounded-md border border-slate-200 bg-slate-50 p-4"
                        >
                            <Camera class="size-5 text-violet-600" />
                            <p class="mt-3 text-xs font-medium text-slate-500">
                                Камера
                            </p>
                            <p
                                class="mt-1 text-sm font-semibold text-slate-950"
                            >
                                {{ selectedVideoLabel }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <WaitingRoomStatusPanel :entry="currentWaitingRoomEntry" />

                <CallError
                    v-if="rejected"
                    message="Заявка отклонена. Обратитесь к модератору комнаты."
                />

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

                <Button
                    v-if="currentWaitingRoomEntry?.status === 'approved'"
                    class="h-11 w-full rounded-md bg-gradient-to-r from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-100 hover:from-violet-500 hover:to-blue-500"
                    type="button"
                    @click="goToCall"
                >
                    Войти в звонок
                    <ArrowRight class="size-4" />
                </Button>
            </section>

            <aside class="grid content-start gap-4">
                <Card
                    data-test="other-waiting-list"
                    class="rounded-md border-slate-200/80 bg-white/90 shadow-sm"
                >
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-3"
                    >
                        <div>
                            <CardTitle class="text-base">
                                Другие ожидающие
                            </CardTitle>
                            <CardDescription>
                                Участники, которые тоже ждут решения.
                            </CardDescription>
                        </div>
                        <Badge variant="secondary" class="rounded-md">
                            {{ otherWaitingEntries.length }}
                        </Badge>
                    </CardHeader>

                    <CardContent>
                        <p
                            v-if="otherWaitingEntries.length === 0"
                            class="rounded-md border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500"
                        >
                            Пока больше никто не ожидает.
                        </p>

                        <ol v-else class="grid gap-2">
                            <li
                                v-for="entry in otherWaitingEntries"
                                :key="entry.id"
                                data-test="other-waiting-entry"
                                :data-entry-id="entry.id"
                                class="rounded-md border border-slate-200 bg-slate-50 p-3 text-sm"
                            >
                                <p class="font-semibold text-slate-950">
                                    {{ entry.display_name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ entry.guest ? 'Гость' : 'Пользователь' }}
                                </p>
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <WaitingRoomModeratorPanel
                    v-if="
                        can_moderate_waiting_room && room.waiting_room_enabled
                    "
                    :room-public-id="room.public_id"
                    :entries="realtime.waitingRoomEntries.value"
                    @decided="realtime.removeWaitingRoomEntry"
                />
            </aside>
        </div>
    </main>
</template>
