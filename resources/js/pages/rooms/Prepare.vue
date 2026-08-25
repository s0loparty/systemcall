<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    DoorOpen,
    UserRound,
    Video,
} from '@lucide/vue';
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
            <header class="flex items-center justify-between gap-4">
                <Button as-child variant="ghost" class="rounded-md">
                    <Link :href="backHref">
                        <ArrowLeft class="size-4" />
                        Вернуться назад
                    </Link>
                </Button>

                <Badge
                    variant="outline"
                    class="rounded-md border-emerald-100 bg-emerald-50 px-3 py-1.5 text-emerald-700"
                >
                    <span class="size-2 rounded-full bg-emerald-500" />
                    Подготовка к звонку
                </Badge>
            </header>

            <section
                class="grid gap-8 lg:grid-cols-[0.92fr_1.08fr] lg:items-start"
            >
                <div class="grid gap-5">
                    <Card
                        class="overflow-hidden rounded-md border-slate-200/80 bg-white/90 shadow-2xl shadow-violet-100/60 backdrop-blur"
                    >
                        <CardHeader class="text-center">
                            <div
                                class="mx-auto flex size-16 items-center justify-center rounded-md bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-200"
                            >
                                <Video class="size-7" />
                            </div>
                            <Badge
                                variant="outline"
                                class="mx-auto mt-4 rounded-md bg-violet-50 text-violet-700"
                            >
                                Комната готова
                            </Badge>
                            <CardTitle
                                class="mt-2 text-3xl font-semibold tracking-tight"
                            >
                                {{ room.name }}
                            </CardTitle>
                            <CardDescription
                                class="mx-auto max-w-xl text-base leading-7"
                            >
                                {{
                                    room.description ??
                                    'Проверьте устройства перед входом в комнату.'
                                }}
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-md border border-slate-200 bg-slate-50 p-4"
                            >
                                <CheckCircle2 class="size-5 text-emerald-500" />
                                <p class="mt-3 text-sm font-semibold">
                                    Устройства необязательны
                                </p>
                                <p
                                    class="mt-1 text-xs leading-5 text-slate-500"
                                >
                                    Можно войти без камеры и микрофона, затем
                                    включить их позже.
                                </p>
                            </div>
                            <div
                                class="rounded-md border border-slate-200 bg-slate-50 p-4"
                            >
                                <DoorOpen class="size-5 text-violet-500" />
                                <p class="mt-3 text-sm font-semibold">
                                    Вход через подготовку
                                </p>
                                <p
                                    class="mt-1 text-xs leading-5 text-slate-500"
                                >
                                    Участники сначала проверяют имя и
                                    устройства.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        v-if="isGuest"
                        class="rounded-md border-slate-200/80 bg-white/90 shadow-sm"
                    >
                        <CardHeader>
                            <div
                                class="flex size-11 items-center justify-center rounded-md bg-violet-50 text-violet-600"
                            >
                                <UserRound class="size-5" />
                            </div>
                            <CardTitle>Как вас представить?</CardTitle>
                            <CardDescription>
                                Никнейм нужен, чтобы участники понимали, кто
                                подключается.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Label for="guest-display-name">Никнейм</Label>
                            <Input
                                id="guest-display-name"
                                v-model="roomJoin.joinRequest.display_name"
                                data-test="guest-display-name"
                                class="mt-2"
                                maxlength="120"
                                placeholder="Как вас показать участникам?"
                                type="text"
                            />
                            <p
                                v-if="
                                    roomJoin.joinRequest.errors.display_name ||
                                    guestDisplayNameMissing
                                "
                                class="mt-2 text-sm text-red-600"
                            >
                                {{
                                    roomJoin.joinRequest.errors.display_name ??
                                    'Введите никнейм, чтобы войти в комнату.'
                                }}
                            </p>
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
                </div>

                <div class="grid gap-5">
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

                    <Card
                        class="rounded-md border-slate-200/80 bg-white/90 shadow-sm"
                    >
                        <CardContent class="p-4">
                            <Button
                                data-test="continue-to-room"
                                class="h-12 w-full rounded-md bg-gradient-to-r from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-100 hover:from-violet-500 hover:to-blue-500 disabled:opacity-60"
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
                            <p class="mt-3 text-center text-xs text-slate-500">
                                Перед входом можно изменить выбор устройств.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>
    </main>
</template>
