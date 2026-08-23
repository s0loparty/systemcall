<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import ApplicationRealtimePanel from '@/features/calls/components/ApplicationRealtimePanel.vue';
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
const realtime = useRoomApplicationEvents(props.room.public_id, {
    waitingRoomEntries: props.waiting_room_entries,
    onWaitingRoomDecision: (event) => {
        if (event.waiting_room.id !== props.current_waiting_room_entry?.id) {
            return;
        }

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
        (entry) => entry.id !== props.current_waiting_room_entry?.id,
    ),
);
const rejected = computed(
    () => props.current_waiting_room_entry?.status === 'rejected',
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

function goToCall(): void {
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
        class="min-h-dvh bg-[#f8fafd] bg-[radial-gradient(circle_at_18%_0%,rgba(251,188,4,0.22),transparent_34%),radial-gradient(circle_at_84%_100%,rgba(138,180,248,0.22),transparent_32%)] p-4 text-neutral-950 antialiased dark:bg-[#202124]"
    >
        <div class="mx-auto grid max-w-6xl gap-5 lg:grid-cols-[1fr_24rem]">
            <section class="grid gap-5">
                <div
                    class="rounded-[2rem] bg-white/92 p-6 shadow-[0_24px_90px_rgba(60,64,67,0.16),inset_0_0_0_1px_rgba(60,64,67,0.08)] backdrop-blur dark:bg-[#2b2c2f] dark:text-white"
                >
                    <p class="text-sm font-medium text-amber-600">
                        Зал ожидания
                    </p>
                    <h1
                        class="mt-2 text-3xl font-semibold tracking-tight text-neutral-950 dark:text-white"
                    >
                        {{ room.name }}
                    </h1>
                    <p
                        class="mt-3 text-sm leading-6 text-neutral-500 dark:text-neutral-400"
                    >
                        Модератор должен подтвердить вход. Пока вы ожидаете,
                        можно проверить выбранные устройства.
                    </p>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div
                            class="rounded-2xl bg-neutral-100 p-4 dark:bg-neutral-900"
                        >
                            <p class="text-xs text-neutral-500">Микрофон</p>
                            <p
                                class="mt-1 text-sm font-medium text-neutral-950 dark:text-white"
                            >
                                {{ selectedAudioLabel }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl bg-neutral-100 p-4 dark:bg-neutral-900"
                        >
                            <p class="text-xs text-neutral-500">Камера</p>
                            <p
                                class="mt-1 text-sm font-medium text-neutral-950 dark:text-white"
                            >
                                {{ selectedVideoLabel }}
                            </p>
                        </div>
                    </div>
                </div>

                <WaitingRoomStatusPanel :entry="current_waiting_room_entry" />
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
                    :preview-enabled="devices.previewEnabled.value"
                    :preview-loading="devices.previewLoading.value"
                    :error="devices.deviceErrorMessage.value"
                    @refresh="devices.loadDevices(true)"
                    @preview="devices.startPreview"
                    @stop-preview="devices.stopPreview"
                    @update-audio-input="devices.setAudioInput"
                    @update-video-input="devices.setVideoInput"
                    @preview-host="devices.setPreviewHost"
                />

                <button
                    v-if="current_waiting_room_entry?.status === 'approved'"
                    class="w-full rounded-2xl bg-neutral-950 px-5 py-3 text-sm font-medium text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                    type="button"
                    @click="goToCall"
                >
                    Войти в звонок
                </button>
            </section>

            <aside class="grid content-start gap-4">
                <ApplicationRealtimePanel
                    :connection-state="realtime.echoConnectionStatus.value"
                    :activities="realtime.activities.value"
                />

                <section
                    class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h2
                            class="text-sm font-medium text-neutral-950 dark:text-white"
                        >
                            Другие ожидающие
                        </h2>
                        <span
                            class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300"
                        >
                            {{ otherWaitingEntries.length }}
                        </span>
                    </div>

                    <p
                        v-if="otherWaitingEntries.length === 0"
                        class="mt-3 text-sm text-neutral-500 dark:text-neutral-400"
                    >
                        Пока больше никто не ожидает.
                    </p>

                    <ol v-else class="mt-3 grid gap-2">
                        <li
                            v-for="entry in otherWaitingEntries"
                            :key="entry.id"
                            class="rounded-2xl bg-neutral-100 p-3 text-sm dark:bg-neutral-900"
                        >
                            <p
                                class="font-medium text-neutral-950 dark:text-white"
                            >
                                {{ entry.display_name }}
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ entry.guest ? 'Гость' : 'Пользователь' }}
                            </p>
                        </li>
                    </ol>
                </section>

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
