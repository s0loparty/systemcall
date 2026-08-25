<script setup lang="ts">
import { Camera, Mic, RefreshCw, Video, VideoOff } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';

type Props = {
    audioInputs: MediaDeviceInfo[];
    videoInputs: MediaDeviceInfo[];
    selectedAudioInputId: string;
    selectedVideoInputId: string;
    previewEnabled: boolean;
    previewLoading: boolean;
    error: string | null;
};

defineProps<Props>();

const emit = defineEmits<{
    refresh: [];
    preview: [];
    'stop-preview': [];
    'update-audio-input': [deviceId: string];
    'update-video-input': [deviceId: string];
    'preview-host': [element: HTMLElement | null];
}>();
</script>

<template>
    <Card class="rounded-md border-slate-200/80 bg-white/90 shadow-sm">
        <CardHeader
            class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start"
        >
            <div>
                <div
                    class="flex size-11 items-center justify-center rounded-md bg-emerald-50 text-emerald-600"
                >
                    <Video class="size-5" />
                </div>
                <CardTitle class="mt-4">Устройства</CardTitle>
                <CardDescription class="mt-1 leading-6">
                    Камера и микрофон необязательны. Можно войти без них и
                    включить позже.
                </CardDescription>
            </div>

            <div
                class="relative min-h-56 overflow-hidden rounded-md bg-slate-950 shadow-inner"
            >
                <div
                    :ref="
                        (element) =>
                            emit('preview-host', element as HTMLElement | null)
                    "
                    data-test="preview-host"
                    class="absolute inset-0 [&_video]:h-full [&_video]:w-full [&_video]:scale-x-[-1] [&_video]:object-cover"
                />
                <div
                    v-if="!previewEnabled"
                    data-test="preview-empty-state"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center text-sm text-slate-300"
                >
                    <VideoOff class="size-8 text-slate-500" />
                    <span>
                        Выберите камеру и запустите предпросмотр, чтобы
                        проверить изображение.
                    </span>
                </div>
            </div>
        </CardHeader>

        <CardContent class="grid gap-5">
            <div class="grid gap-3 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label
                        for="audio-input-select"
                        class="flex items-center gap-2 text-sm font-semibold"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-md bg-emerald-50 text-emerald-600"
                        >
                            <Mic class="size-4" />
                        </span>
                        Микрофон
                    </Label>
                    <select
                        id="audio-input-select"
                        data-test="audio-input-select"
                        class="h-10 w-full rounded-md border border-input bg-white px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                        :value="selectedAudioInputId"
                        @change="
                            emit(
                                'update-audio-input',
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="">Без микрофона</option>
                        <option
                            v-for="device in audioInputs"
                            :key="device.deviceId"
                            :value="device.deviceId"
                        >
                            {{ device.label || 'Микрофон' }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-2">
                    <Label
                        for="video-input-select"
                        class="flex items-center gap-2 text-sm font-semibold"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-md bg-violet-50 text-violet-600"
                        >
                            <Camera class="size-4" />
                        </span>
                        Камера
                    </Label>
                    <select
                        id="video-input-select"
                        data-test="video-input-select"
                        class="h-10 w-full rounded-md border border-input bg-white px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                        :value="selectedVideoInputId"
                        @change="
                            emit(
                                'update-video-input',
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="">Без камеры</option>
                        <option
                            v-for="device in videoInputs"
                            :key="device.deviceId"
                            :value="device.deviceId"
                        >
                            {{ device.label || 'Камера' }}
                        </option>
                    </select>
                </div>
            </div>

            <p
                v-if="error"
                class="rounded-md border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700"
            >
                {{ error }}
            </p>

            <div class="flex flex-col gap-2 sm:flex-row">
                <Button
                    data-test="refresh-devices"
                    class="rounded-md"
                    type="button"
                    variant="outline"
                    @click="emit('refresh')"
                >
                    <RefreshCw class="size-4" />
                    Обновить устройства
                </Button>
                <Button
                    data-test="toggle-preview"
                    class="rounded-md bg-slate-950 text-white hover:bg-slate-800"
                    type="button"
                    :disabled="previewLoading"
                    @click="
                        previewEnabled ? emit('stop-preview') : emit('preview')
                    "
                >
                    {{
                        previewLoading
                            ? 'Запускаем предпросмотр...'
                            : previewEnabled
                              ? 'Остановить предпросмотр'
                              : 'Предпросмотр'
                    }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
