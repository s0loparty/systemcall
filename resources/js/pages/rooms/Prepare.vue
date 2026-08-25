<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

    <main
        class="relative min-h-dvh overflow-hidden bg-[#fbfaff] text-slate-950 antialiased"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_10%,rgba(124,92,255,0.17),transparent_28%),radial-gradient(circle_at_82%_8%,rgba(59,130,246,0.12),transparent_24%),radial-gradient(circle_at_50%_88%,rgba(16,185,129,0.09),transparent_24%)]"
        />

        <div
            class="relative mx-auto grid min-h-dvh max-w-6xl gap-6 px-5 py-6 sm:px-8"
        >
            <section class="mx-auto grid w-full max-w-5xl gap-5">
                <div class="py-4 text-center sm:pt-8 sm:pb-2">
                    <h1
                        class="text-4xl font-semibold tracking-tight text-balance text-slate-950"
                    >
                        {{ room.name }}
                    </h1>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        {{
                            room.description ??
                            'Проверьте устройства перед входом в комнату.'
                        }}
                    </p>
                </div>

                <div class="grid gap-5">
                    <Card
                        class="w-full flex-row gap-0 rounded-md border-slate-200/80 bg-white/90 p-0 shadow-sm"
                    >
                        <CardContent
                            class="grid w-full gap-5 p-4 lg:grid-cols-[1fr_20rem]"
                        >
                            <div v-if="isGuest" class="grid gap-2">
                                <Label for="guest-display-name">Никнейм</Label>
                                <Input
                                    id="guest-display-name"
                                    v-model="roomJoin.joinRequest.display_name"
                                    data-test="guest-display-name"
                                    maxlength="120"
                                    placeholder="Как вас показать участникам?"
                                    type="text"
                                />
                                <p
                                    v-if="
                                        roomJoin.joinRequest.errors
                                            .display_name ||
                                        guestDisplayNameMissing
                                    "
                                    class="text-sm text-red-600"
                                >
                                    {{
                                        roomJoin.joinRequest.errors
                                            .display_name ??
                                        'Введите никнейм, чтобы войти в комнату.'
                                    }}
                                </p>
                            </div>
                            <div v-else>
                                <CardTitle>Готовы войти?</CardTitle>
                                <p class="mt-2 text-sm text-slate-600">
                                    Вы подключаетесь как
                                    {{ defaultDisplayName }}.
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Button
                                    data-test="continue-to-room"
                                    class="h-12 w-full rounded-md bg-linear-to-r from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-100 hover:from-violet-500 hover:to-blue-500 disabled:opacity-60"
                                    type="button"
                                    :disabled="continueDisabled"
                                    @click="continueToRoom"
                                >
                                    {{ continueButtonLabel }}
                                    <ArrowRight
                                        v-if="!continueDisabled"
                                        class="size-4"
                                    />
                                </Button>
                                <Button
                                    as-child
                                    variant="outline"
                                    class="h-12 w-full rounded-md"
                                    type="button"
                                >
                                    <Link :href="backHref">
                                        <ArrowLeft class="size-4" />
                                        Вернуться назад
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <RoomPasswordPanel
                        v-if="needsPassword"
                        v-model:password="roomJoin.joinRequest.password"
                        :error="roomJoin.joinRequest.errors.password"
                    />

                    <CallError
                        v-if="hasJoinErrors"
                        message="Проверьте пароль или параметры входа."
                    />
                    <CallError
                        v-if="joinErrorMessage"
                        :message="joinErrorMessage"
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
                </div>
            </section>
        </div>
    </main>
</template>
