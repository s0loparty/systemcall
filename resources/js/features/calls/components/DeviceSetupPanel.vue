<script setup lang="ts">
import { Camera, Mic, VideoOff } from '@lucide/vue';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';

type Props = {
    audioInputs: MediaDeviceInfo[];
    videoInputs: MediaDeviceInfo[];
    selectedAudioInputId: string;
    selectedVideoInputId: string;
    previewHasVideo: boolean;
    previewLoading: boolean;
    error: string | null;
};

defineProps<Props>();

const emit = defineEmits<{
    'update-audio-input': [deviceId: string];
    'update-video-input': [deviceId: string];
    'preview-host': [element: HTMLElement | null];
}>();
</script>

<template>
    <Card
        class="overflow-hidden rounded-md border-slate-200/80 bg-white/95 shadow-sm"
    >
        <CardContent class="grid gap-8 p-6 lg:grid-cols-[1fr_0.95fr]">
            <div class="grid content-start gap-6">
                <div>
                    <CardTitle>Устройства</CardTitle>
                    <p class="mt-2 max-w-md text-sm leading-6 text-slate-600">
                        Камера и микрофон необязательны. Можно войти без них и
                        включить позже.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label
                            for="audio-input-select"
                            class="flex items-center gap-3 text-sm font-semibold"
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
                            class="h-11 w-full rounded-md border border-input bg-white px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
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
                            class="flex items-center gap-3 text-sm font-semibold"
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
                            class="h-11 w-full rounded-md border border-input bg-white px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
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
            </div>

            <div
                class="relative min-h-64 overflow-hidden rounded-md bg-slate-950 shadow-inner"
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
                    v-if="!previewHasVideo"
                    data-test="preview-empty-state"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center text-sm text-slate-300"
                >
                    <VideoOff class="size-8 text-slate-500" />
                    <span>Выберите камеру, чтобы увидеть превью.</span>
                </div>
                <div
                    v-if="previewLoading"
                    class="absolute inset-x-0 bottom-0 bg-black/50 px-3 py-2 text-center text-xs font-medium text-white"
                >
                    Обновляем превью...
                </div>
            </div>
        </CardContent>
    </Card>
</template>
