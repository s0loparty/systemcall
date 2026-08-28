<script setup lang="ts">
import { Camera, Loader2, Mic, ShieldCheck, VideoOff } from '@lucide/vue';

import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { cn } from '@/lib/utils';
import type { AcceptableValue } from 'reka-ui';

type Props = {
    audioInputs: MediaDeviceInfo[];
    videoInputs: MediaDeviceInfo[];
    selectedAudioInputId: string;
    selectedVideoInputId: string;
    audioPermissionRequested: boolean;
    videoPermissionRequested: boolean;
    audioPermissionLoading: boolean;
    videoPermissionLoading: boolean;
    previewHasVideo: boolean;
    previewLoading: boolean;
    error: string | null;
    classContent?: HTMLAttributes['class'];
};

const props = withDefaults(defineProps<Props>(), {
    audioPermissionRequested: false,
    videoPermissionRequested: false,
    audioPermissionLoading: false,
    videoPermissionLoading: false,
});

const emit = defineEmits<{
    'update-audio-input': [deviceId: string];
    'update-video-input': [deviceId: string];
    'preview-host': [element: HTMLElement | null];
    'request-audio-permission': [event: MouseEvent | KeyboardEvent];
    'request-video-permission': [event: MouseEvent | KeyboardEvent];
}>();

const disabledDeviceValue = 'none';
const selectedAudioInputValue = computed(
    () => props.selectedAudioInputId || disabledDeviceValue,
);
const selectedVideoInputValue = computed(
    () => props.selectedVideoInputId || disabledDeviceValue,
);
const selectableAudioInputs = computed(() =>
    props.audioInputs.filter((device) => device.deviceId !== ''),
);
const selectableVideoInputs = computed(() =>
    props.videoInputs.filter((device) => device.deviceId !== ''),
);

function normalizeDeviceValue(value: AcceptableValue): string {
    if (typeof value !== 'string' || value === disabledDeviceValue) {
        return '';
    }

    return value;
}

function updateAudioInput(value: AcceptableValue): void {
    emit('update-audio-input', normalizeDeviceValue(value));
}

function updateVideoInput(value: AcceptableValue): void {
    emit('update-video-input', normalizeDeviceValue(value));
}
</script>

<template>
    <section
        data-test="device-setup-panel"
        :data-audio-input-count="selectableAudioInputs.length"
        :data-video-input-count="selectableVideoInputs.length"
        :data-selected-audio-input-id="selectedAudioInputId"
        :data-selected-video-input-id="selectedVideoInputId"
    >
        <Card
            :class="
                cn('border-border/60 bg-white/70 shadow-sm p-0', $attrs.class)
            "
        >
            <CardContent
                :class="cn('p-6 sm:p-8 space-y-6', props.classContent)"
            >
                <!-- Header -->
                <div>
                    <CardTitle class="text-lg font-semibold"
                        >Устройства</CardTitle
                    >
                    <p class="mt-1 text-sm text-muted-foreground">
                        Камера и микрофон необязательны. Можно войти без них и
                        включить позже.
                    </p>
                </div>

                <!-- Device selects -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <!-- Microphone -->
                    <div class="space-y-1.5">
                        <Label
                            class="text-sm font-medium flex items-center gap-2"
                        >
                            <Mic class="h-4 w-4" />
                            Микрофон
                        </Label>
                        <Select
                            :model-value="selectedAudioInputValue"
                            :disabled="
                                !audioPermissionRequested ||
                                audioPermissionLoading
                            "
                            @update:model-value="updateAudioInput"
                        >
                            <SelectTrigger
                                data-test="audio-input-select"
                                class="rounded-lg border-border/60 bg-white/80 w-full"
                            >
                                <SelectValue placeholder="Выберите микрофон" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="disabledDeviceValue">
                                    <span class="flex items-center gap-2">
                                        <Mic class="h-4 w-4" />
                                        Без микрофона
                                    </span>
                                </SelectItem>
                                <SelectItem
                                    v-for="device in selectableAudioInputs"
                                    :key="device.deviceId"
                                    :value="device.deviceId"
                                >
                                    {{
                                        device.label ||
                                        `Микрофон ${device.deviceId.slice(0, 6)}`
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full rounded-lg bg-white"
                            data-test="request-audio-permission"
                            :disabled="audioPermissionLoading"
                            @click="emit('request-audio-permission', $event)"
                        >
                            <Loader2
                                v-if="audioPermissionLoading"
                                class="size-4 animate-spin"
                            />
                            <ShieldCheck v-else class="size-4" />
                            {{
                                audioPermissionLoading
                                    ? 'Запрашиваем...'
                                    : audioPermissionRequested
                                      ? 'Обновить микрофоны'
                                      : 'Разрешить микрофон'
                            }}
                        </Button>
                    </div>

                    <!-- Camera -->
                    <div class="space-y-1.5">
                        <Label
                            class="text-sm font-medium flex items-center gap-2"
                        >
                            <Camera class="h-4 w-4" />
                            Камера
                        </Label>
                        <Select
                            :model-value="selectedVideoInputValue"
                            :disabled="
                                !videoPermissionRequested ||
                                videoPermissionLoading
                            "
                            @update:model-value="updateVideoInput"
                        >
                            <SelectTrigger
                                data-test="video-input-select"
                                class="rounded-lg border-border/60 bg-white/80 w-full"
                            >
                                <SelectValue placeholder="Выберите камеру" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="disabledDeviceValue">
                                    <span class="flex items-center gap-2">
                                        <Camera class="h-4 w-4" />
                                        Без камеры
                                    </span>
                                </SelectItem>
                                <SelectItem
                                    v-for="device in selectableVideoInputs"
                                    :key="device.deviceId"
                                    :value="device.deviceId"
                                >
                                    {{
                                        device.label ||
                                        `Камера ${device.deviceId.slice(0, 6)}`
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full rounded-lg bg-white"
                            data-test="request-video-permission"
                            :disabled="videoPermissionLoading"
                            @click="emit('request-video-permission', $event)"
                        >
                            <Loader2
                                v-if="videoPermissionLoading"
                                class="size-4 animate-spin"
                            />
                            <ShieldCheck v-else class="size-4" />
                            {{
                                videoPermissionLoading
                                    ? 'Запрашиваем...'
                                    : videoPermissionRequested
                                      ? 'Обновить камеры'
                                      : 'Разрешить камеру'
                            }}
                        </Button>
                    </div>
                </div>

                <!-- Error message -->
                <div
                    v-if="error"
                    class="rounded-xl border border-red-200/60 bg-red-50/50 p-3"
                >
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>

                <!-- Camera preview -->
                <div>
                    <div
                        class="relative aspect-video overflow-hidden rounded-xl border border-border/60 bg-slate-950"
                    >
                        <div
                            :ref="
                                (element) =>
                                    emit(
                                        'preview-host',
                                        element as HTMLElement | null,
                                    )
                            "
                            data-test="preview-host"
                            class="absolute inset-0 [&_video]:h-full [&_video]:w-full [&_video]:scale-x-[-1] [&_video]:object-cover"
                        />
                        <div
                            v-if="!previewHasVideo"
                            data-test="preview-empty-state"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center"
                        >
                            <VideoOff class="h-12 w-12 text-white/40" />
                            <span class="text-sm text-white/70">{{
                                videoPermissionRequested
                                    ? 'Выберите камеру, чтобы увидеть превью.'
                                    : 'Разрешите камеру, чтобы увидеть превью.'
                            }}</span>
                        </div>
                        <div
                            v-if="previewLoading"
                            class="absolute bottom-0 left-0 right-0 bg-black/50 px-3 py-2 text-center text-xs font-medium text-white"
                        >
                            Обновляем превью...
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </section>
</template>
