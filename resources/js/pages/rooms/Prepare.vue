<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';

import { ArrowLeft, ArrowRight } from '@lucide/vue';

import { computed, ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';

import { home } from '@/routes';

import { show as showCall, waiting as showWaiting } from '@/routes/rooms/call';
import { index as showRooms } from '@/routes/rooms/index';

import CallError from '@/features/calls/components/CallError.vue';
import DeviceSetupPanel from '@/features/calls/components/DeviceSetupPanel.vue';
import { useCallDeviceQuery } from '@/features/calls/composables/useCallDeviceQuery';
import { useMediaDevices } from '@/features/calls/composables/useMediaDevices';
import { useRoomJoin } from '@/features/calls/composables/useRoomJoin';
import { useVideoBackground } from '@/features/calls/composables/useVideoBackground';
import type { CallPageProps } from '@/features/calls/types';
import type { LocalVideoTrack } from 'livekit-client';

const props = defineProps<CallPageProps>();
type PrepareStep = 'identity' | 'password';

const page = usePage();
const currentUser = computed(() => page.props.auth.user ?? null);
const isGuest = computed(() => currentUser.value === null);
const defaultDisplayName = computed(() => currentUser.value?.name ?? '');
const deviceQuery = useCallDeviceQuery();
const joinErrorMessage = ref<string | null>(null);
const videoBackground = useVideoBackground({
    initialEnabled: deviceQuery.videoBackground.value.enabled,
    initialBlurRadius: deviceQuery.videoBackground.value.blurRadius,
    testingMediaMode: props.testing_media_mode,
});
const devices = useMediaDevices({
    initialSelectedDevices: deviceQuery.query.value,
    autoSelectCameraAfterPermission: true,
    testingMediaMode: props.testing_media_mode,
    processPreviewVideoTrack: processPreviewTrackBackground,
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
    processPreviewVideoTrack,
} = devices;
const roomJoin = useRoomJoin(props.room.public_id, defaultDisplayName.value);

const needsPassword = computed(
    () => props.room.has_password && !props.can_join_without_password,
);
const prepareStep = ref<PrepareStep>(
    !isGuest.value && needsPassword.value ? 'password' : 'identity',
);
const isIdentityStep = computed(() => prepareStep.value === 'identity');
const isPasswordStep = computed(() => prepareStep.value === 'password');
const guestDisplayNameMissing = computed(
    () => isGuest.value && roomJoin.joinRequest.display_name.trim() === '',
);
const passwordMissing = computed(
    () => isPasswordStep.value && roomJoin.joinRequest.password.trim() === '',
);
const backHref = computed(() => (isGuest.value ? home.url() : showRooms.url()));
const continueDisabled = computed(
    () =>
        guestDisplayNameMissing.value ||
        passwordMissing.value ||
        roomJoin.joinRequest.processing ||
        roomJoin.tokenRequest.processing,
);
const hasJoinErrors = computed(
    () => Object.keys(roomJoin.joinRequest.errors).length > 0,
);
const previewErrorMessage = computed(
    () => deviceErrorMessage.value ?? videoBackground.errorMessage.value,
);
const continueButtonLabel = computed(() => {
    if (guestDisplayNameMissing.value) {
        return 'Введите никнейм';
    }

    if (passwordMissing.value) {
        return 'Введите пароль';
    }

    if (roomJoin.joinRequest.processing || roomJoin.tokenRequest.processing) {
        return 'Проверяем доступ...';
    }

    if (isIdentityStep.value && isGuest.value && needsPassword.value) {
        return 'Дальше';
    }

    return props.room.waiting_room_enabled ? 'Подключиться' : 'Войти в звонок';
});

async function continueToRoom(): Promise<void> {
    if (continueDisabled.value) {
        return;
    }

    joinErrorMessage.value = null;
    roomJoin.joinRequest.clearErrors();

    const query = deviceQuery.toQuery(
        devices.selectedDevices.value,
        videoBackground.preferences(),
    );

    if (isIdentityStep.value && isGuest.value && needsPassword.value) {
        prepareStep.value = 'password';

        return;
    }

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

    if (!response) {
        return;
    }

    if ('waiting_room' in response) {
        router.visit(showWaiting.url(props.room.public_id, { query }));

        return;
    }

    router.visit(showCall.url(props.room.public_id, { query }));
}

async function processPreviewTrackBackground(
    track: LocalVideoTrack,
): Promise<void> {
    if (videoBackground.enabled.value) {
        await videoBackground.setProcessor(track);

        return;
    }

    await videoBackground.removeProcessor(track);
}

async function updateBackgroundBlur(enabled: boolean): Promise<void> {
    await videoBackground.setEnabled(enabled);
    await processPreviewVideoTrack();
}
</script>

<template>
    <Head :title="`Подготовка: ${room.name}`" />

    <div class="flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-xl">
            <!-- Back to rooms -->
            <Button
                v-if="currentUser"
                :as="Link"
                :href="backHref"
                variant="ghost"
                class="mb-2"
            >
                <ArrowLeft />
                Вернуться назад
            </Button>

            <!-- Main card -->
            <Card
                class="border-border/60 bg-white/70 p-0 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70"
            >
                <CardContent class="p-6 sm:p-8 space-y-6">
                    <h1 class="text-2xl text-center font-bold tracking-tight">
                        Готовы войти?
                    </h1>
                    <div class="space-y-4">
                        <div>
                            <!-- Room info -->
                            <div class="space-y-1">
                                <p class="text-muted-foreground">
                                    Вы подключаетесь как
                                    <span class="font-medium text-foreground">
                                        {{
                                            defaultDisplayName || 'Гость'
                                        }} </span
                                    >.
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-muted-foreground"
                                        >Комната</span
                                    >
                                    <span class="font-medium text-foreground">{{
                                        room.name
                                    }}</span>
                                    <Badge
                                        v-if="room.has_password"
                                        variant="outline"
                                        class="border-border/60 bg-white/50 text-xs dark:border-neutral-700 dark:bg-neutral-800"
                                    >
                                        🔒 с паролем
                                    </Badge>
                                </div>
                            </div>

                            <div class="my-4">
                                <div v-if="isIdentityStep" class="space-y-4">
                                    <div v-if="isGuest" class="space-y-1">
                                        <Label
                                            for="guest-display-name"
                                            class="text-sm"
                                        >
                                            Ваше имя
                                        </Label>
                                        <Input
                                            id="guest-display-name"
                                            v-model="
                                                roomJoin.joinRequest
                                                    .display_name
                                            "
                                            data-test="guest-display-name"
                                            maxlength="120"
                                            placeholder="Будет видно участникам"
                                            type="text"
                                            class="h-12 rounded-md border-neutral-200 bg-neutral-100 text-2xl dark:border-neutral-700 dark:bg-neutral-950"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-else-if="isPasswordStep"
                                    class="space-y-4"
                                >
                                    <div class="space-y-1.5">
                                        <Label
                                            class="text-sm"
                                            for="room-password"
                                        >
                                            Пароль комнаты
                                        </Label>
                                        <Input
                                            id="room-password"
                                            v-model="
                                                roomJoin.joinRequest.password
                                            "
                                            data-test="room-password"
                                            type="password"
                                            autocomplete="current-password"
                                            class="h-12 rounded-md border-neutral-200 bg-neutral-100 text-2xl dark:border-neutral-700 dark:bg-neutral-950"
                                            placeholder="Введите пароль комнаты"
                                        />
                                    </div>
                                </div>

                                <p
                                    v-if="
                                        (isIdentityStep &&
                                            roomJoin.joinRequest.errors
                                                .display_name) ||
                                        (isIdentityStep &&
                                            guestDisplayNameMissing)
                                    "
                                    class="text-sm text-red-600 mt-1"
                                >
                                    {{
                                        roomJoin.joinRequest.errors
                                            .display_name ??
                                        'Введите никнейм, чтобы войти в комнату.'
                                    }}
                                </p>
                            </div>

                            <!-- Join button -->
                            <Button
                                data-test="continue-to-room"
                                class="w-full rounded-full bg-neutral-900 py-6 text-sm font-medium text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                                type="button"
                                :disabled="continueDisabled"
                                @click="continueToRoom"
                            >
                                {{ continueButtonLabel }}
                                <ArrowRight
                                    v-if="!continueDisabled"
                                    class="ml-2 h-4 w-4"
                                />
                            </Button>

                            <!-- Errors -->
                            <div class="mt-2 space-y-1">
                                <CallError
                                    v-if="roomJoin.joinRequest.errors.password"
                                    :message="
                                        roomJoin.joinRequest.errors.password
                                    "
                                />
                                <CallError
                                    v-if="hasJoinErrors"
                                    message="Проверьте пароль или параметры входа."
                                />
                                <CallError
                                    v-if="joinErrorMessage"
                                    :message="joinErrorMessage"
                                />
                            </div>
                        </div>

                        <Separator class="my-4" />

                        <DeviceSetupPanel
                            class="border-none bg-transparent shadow-none"
                            class-content="p-0 sm:p-0"
                            :audio-inputs="audioInputs"
                            :video-inputs="videoInputs"
                            :selected-audio-input-id="
                                selectedDevices.audioInputId
                            "
                            :selected-video-input-id="
                                selectedDevices.videoInputId
                            "
                            :audio-permission-requested="
                                audioPermissionRequested
                            "
                            :video-permission-requested="
                                videoPermissionRequested
                            "
                            :audio-permission-loading="audioPermissionLoading"
                            :video-permission-loading="videoPermissionLoading"
                            :preview-has-video="previewHasVideo"
                            :preview-loading="previewLoading"
                            :background-blur-enabled="
                                videoBackground.enabled.value
                            "
                            :background-blur-disabled="
                                videoBackground.processing.value
                            "
                            :background-blur-processing="
                                videoBackground.processing.value
                            "
                            :error="previewErrorMessage"
                            @update-audio-input="setAudioInput"
                            @update-video-input="setVideoInput"
                            @update-background-blur="
                                (enabled) => void updateBackgroundBlur(enabled)
                            "
                            @preview-host="setPreviewHost"
                            @request-audio-permission="requestAudioPermission"
                            @request-video-permission="requestVideoPermission"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
