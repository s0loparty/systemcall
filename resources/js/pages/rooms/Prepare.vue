<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CallError from '@/features/calls/components/CallError.vue';
import DeviceSetupPanel from '@/features/calls/components/DeviceSetupPanel.vue';
import RoomPasswordPanel from '@/features/calls/components/RoomPasswordPanel.vue';
import { useCallDeviceQuery } from '@/features/calls/composables/useCallDeviceQuery';
import { useMediaDevices } from '@/features/calls/composables/useMediaDevices';
import { useRoomJoin } from '@/features/calls/composables/useRoomJoin';
import type { CallPageProps } from '@/features/calls/types';
import { show as showCall, waiting as showWaiting } from '@/routes/rooms/call';

const props = defineProps<CallPageProps>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const defaultDisplayName = computed(() => currentUser.value?.name ?? 'Гость');
const deviceQuery = useCallDeviceQuery();
const devices = useMediaDevices({
    initialSelectedDevices: deviceQuery.query.value,
    autoSelectCameraAfterPermission: true,
    testingMediaMode: props.testing_media_mode,
});
const roomJoin = useRoomJoin(props.room.public_id, defaultDisplayName.value);

const needsPassword = computed(
    () => props.room.has_password && !props.can_join_without_password,
);
const continueDisabled = computed(
    () => roomJoin.joinRequest.processing || roomJoin.tokenRequest.processing,
);
const hasJoinErrors = computed(
    () => Object.keys(roomJoin.joinRequest.errors).length > 0,
);

async function continueToRoom(): Promise<void> {
    const query = deviceQuery.toQuery(devices.selectedDevices.value);

    if (!props.room.waiting_room_enabled && !needsPassword.value) {
        router.visit(showCall.url(props.room.public_id, { query }));

        return;
    }

    const response = await roomJoin.joinAndIssueToken();

    if (response.kind === 'waiting-room') {
        router.visit(showWaiting.url(props.room.public_id, { query }));

        return;
    }

    router.visit(showCall.url(props.room.public_id, { query }));
}
</script>

<template>
    <Head :title="`Подготовка: ${room.name}`" />

    <main
        class="min-h-dvh bg-[#f8fafd] bg-[radial-gradient(circle_at_18%_0%,rgba(138,180,248,0.28),transparent_34%),radial-gradient(circle_at_84%_100%,rgba(129,201,149,0.20),transparent_32%)] p-4 text-neutral-950 antialiased dark:bg-[#202124]"
    >
        <div class="mx-auto grid max-w-2xl items-center gap-5 pt-[10vh]">
            <section
                class="rounded-[2rem] bg-white/92 p-6 shadow-[0_24px_90px_rgba(60,64,67,0.16),inset_0_0_0_1px_rgba(60,64,67,0.08)] backdrop-blur dark:bg-[#2b2c2f] dark:text-white"
            >
                <p class="text-sm font-medium text-emerald-600">
                    Подготовка к звонку
                </p>
                <h1
                    class="mt-2 text-3xl font-semibold tracking-tight text-neutral-950 dark:text-white"
                >
                    {{ room.name }}
                </h1>
                <p
                    v-if="room.description"
                    class="mt-3 text-sm leading-6 text-neutral-500 dark:text-neutral-400"
                >
                    {{ room.description }}
                </p>
                <p class="mt-5 text-sm text-neutral-500 dark:text-neutral-400">
                    Выберите устройства перед входом. Камера и микрофон
                    необязательны: можно продолжить без них и включить позже.
                </p>

                <RoomPasswordPanel
                    v-if="needsPassword"
                    v-model:password="roomJoin.joinRequest.password"
                    class="mt-5"
                    :error="roomJoin.joinRequest.errors.password"
                />

                <CallError
                    v-if="hasJoinErrors"
                    class="mt-5"
                    message="Проверьте пароль или параметры входа."
                />

                <button
                    class="mt-6 w-full rounded-2xl bg-neutral-950 px-5 py-3 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                    type="button"
                    :disabled="continueDisabled"
                    @click="continueToRoom"
                >
                    {{
                        continueDisabled
                            ? 'Проверяем доступ...'
                            : room.waiting_room_enabled
                              ? 'Отправить заявку'
                              : 'Войти в звонок'
                    }}
                </button>
            </section>

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
        </div>
    </main>
</template>
