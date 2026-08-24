<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import CallError from '@/features/calls/components/CallError.vue';
import DeviceSetupPanel from '@/features/calls/components/DeviceSetupPanel.vue';
import RoomPasswordPanel from '@/features/calls/components/RoomPasswordPanel.vue';
import { useCallDeviceQuery } from '@/features/calls/composables/useCallDeviceQuery';
import { useMediaDevices } from '@/features/calls/composables/useMediaDevices';
import { useRoomJoin } from '@/features/calls/composables/useRoomJoin';
import type { CallPageProps } from '@/features/calls/types';
import { home } from '@/routes';
import { show as showCall, waiting as showWaiting } from '@/routes/rooms/call';
import { index as showRooms } from '@/routes/rooms/index';

const props = defineProps<CallPageProps>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user ?? null);
const isGuest = computed(() => currentUser.value === null);
const defaultDisplayName = computed(() => currentUser.value?.name ?? '');
const deviceQuery = useCallDeviceQuery();
const joinErrorMessage = ref<string | null>(null);
const devices = useMediaDevices({
    initialSelectedDevices: deviceQuery.query.value,
    autoSelectCameraAfterPermission: true,
    testingMediaMode: props.testing_media_mode,
});
const roomJoin = useRoomJoin(props.room.public_id, defaultDisplayName.value);

const needsPassword = computed(
    () => props.room.has_password && !props.can_join_without_password,
);
const guestDisplayNameMissing = computed(
    () => isGuest.value && roomJoin.joinRequest.display_name.trim() === '',
);
const backHref = computed(() => (isGuest.value ? home.url() : showRooms.url()));
const continueDisabled = computed(
    () =>
        guestDisplayNameMissing.value ||
        roomJoin.joinRequest.processing ||
        roomJoin.tokenRequest.processing,
);
const hasJoinErrors = computed(
    () => Object.keys(roomJoin.joinRequest.errors).length > 0,
);
const continueButtonLabel = computed(() => {
    if (guestDisplayNameMissing.value) {
        return 'Введите никнейм';
    }

    if (roomJoin.joinRequest.processing || roomJoin.tokenRequest.processing) {
        return 'Проверяем доступ...';
    }

    return props.room.waiting_room_enabled ? 'Подключиться' : 'Войти в звонок';
});

async function continueToRoom(): Promise<void> {
    if (continueDisabled.value) {
        return;
    }

    joinErrorMessage.value = null;

    const query = deviceQuery.toQuery(devices.selectedDevices.value);

    if (
        !isGuest.value &&
        !props.room.waiting_room_enabled &&
        !needsPassword.value
    ) {
        router.visit(showCall.url(props.room.public_id, { query }));

        return;
    }

    const response = await roomJoin.joinOnly().catch((error: unknown) => {
        joinErrorMessage.value =
            error instanceof Error
                ? error.message
                : 'Не удалось отправить заявку на подключение.';

        return null;
    });

    if (response === null) {
        return;
    }

    if ('waiting_room' in response) {
        router.visit(showWaiting.url(props.room.public_id, { query }));

        return;
    }

    router.visit(showCall.url(props.room.public_id, { query }));
}
</script>

<template>
    <Head :title="`Подготовка: ${room.name}`" />

    <main class="min-h-dvh bg-gray-200 antialiased dark:bg-[#202124]">
        <div class="mx-auto grid max-w-2xl items-center gap-5 pt-[10vh]">
            <Button :as="Link" :href="backHref" class="w-max" variant="ghost">
                <ArrowLeftIcon class="size-4" />
                <span>Вернуться назад</span>
            </Button>
            <section
                class="rounded-md bg-white/92 p-6 shadow backdrop-blur dark:bg-[#2b2c2f] dark:text-white"
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

                <label v-if="isGuest" class="mt-5 block space-y-2">
                    <span
                        class="text-sm font-medium text-neutral-800 dark:text-neutral-200"
                    >
                        Никнейм
                    </span>
                    <input
                        v-model="roomJoin.joinRequest.display_name"
                        data-test="guest-display-name"
                        class="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-950 shadow-xs transition outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-neutral-800 dark:bg-neutral-900 dark:text-white"
                        maxlength="120"
                        placeholder="Как вас показать участникам?"
                        type="text"
                    />
                    <span
                        v-if="
                            roomJoin.joinRequest.errors.display_name ||
                            guestDisplayNameMissing
                        "
                        class="block text-sm text-red-600 dark:text-red-300"
                    >
                        {{
                            roomJoin.joinRequest.errors.display_name ??
                            'Введите никнейм, чтобы войти в комнату.'
                        }}
                    </span>
                </label>

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
                <CallError
                    v-if="joinErrorMessage"
                    class="mt-5"
                    :message="joinErrorMessage"
                />

                <button
                    data-test="continue-to-room"
                    class="mt-6 w-full cursor-pointer rounded-md bg-green-700 px-5 py-3 text-sm font-medium text-white transition hover:bg-green-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                    type="button"
                    :disabled="continueDisabled"
                    @click="continueToRoom"
                >
                    {{ continueButtonLabel }}
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
