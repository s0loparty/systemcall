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
import RoomPasswordPanel from '@/features/calls/components/RoomPasswordPanel.vue';
import { useCallDeviceQuery } from '@/features/calls/composables/useCallDeviceQuery';
import { useMediaDevices } from '@/features/calls/composables/useMediaDevices';
import { useRoomJoin } from '@/features/calls/composables/useRoomJoin';
import type { CallPageProps } from '@/features/calls/types';

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

    if (!response) {
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

    <div
        class="min-h-screen bg-[#fafaf9] flex items-center justify-center p-4 sm:p-6"
    >
        <div class="w-full max-w-4xl space-y-6">
            <!-- Back to rooms -->
            <div>
                <Link
                    :href="backHref"
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Вернуться назад
                </Link>
            </div>

            <!-- Main card -->
            <Card class="border-border/60 bg-white/70 shadow-sm p-0">
                <CardContent class="p-6 sm:p-8 space-y-6">
                    <h1 class="text-2xl text-center font-light tracking-tight">
                        Готовы войти?
                    </h1>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div>
                            <!-- Room info -->
                            <div class="space-y-2">
                                <p class="text-sm text-muted-foreground">
                                    Вы подключаетесь как
                                    <span class="font-medium text-foreground">
                                        {{
                                            defaultDisplayName || 'Гость'
                                        }} </span
                                    >.
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm text-muted-foreground"
                                        >Комната</span
                                    >
                                    <span
                                        class="text-sm font-medium text-foreground"
                                        >{{ room.name }}</span
                                    >
                                    <Badge
                                        v-if="room.has_password"
                                        variant="outline"
                                        class="border-border/60 bg-white/50 text-xs"
                                    >
                                        🔒 с паролем
                                    </Badge>
                                </div>
                            </div>

                            <Separator class="my-5" />

                            <div>
                                <div
                                    class="grid md:grid-cols-2 w-full gap-4 items-center"
                                >
                                    <!-- Guest name input -->
                                    <div v-if="isGuest" class="space-y-1.5">
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
                                            class="rounded-lg border-border/60 bg-white/80"
                                        />
                                    </div>

                                    <!-- Password panel -->
                                    <RoomPasswordPanel
                                        v-if="needsPassword"
                                        v-model:password="
                                            roomJoin.joinRequest.password
                                        "
                                    />
                                </div>
                                <p
                                    v-if="
                                        roomJoin.joinRequest.errors
                                            .display_name ||
                                        guestDisplayNameMissing
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
                                class="w-full rounded-full mt-4 bg-neutral-900 text-white hover:bg-neutral-700 py-3 text-sm font-medium"
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

                        <DeviceSetupPanel
                            class="shadow-none bg-neutral-50"
                            class-content="p-4 sm:p-4"
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
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
