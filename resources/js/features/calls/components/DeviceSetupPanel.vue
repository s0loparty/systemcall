<script setup lang="ts">
import {
    Camera,
    Loader2,
    Mic,
    ShieldCheck,
    Sparkles,
    VideoOff,
} from '@lucide/vue';

import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
    backgroundBlurEnabled?: boolean;
    backgroundBlurDisabled?: boolean;
    backgroundBlurProcessing?: boolean;
    error: string | null;
    classContent?: HTMLAttributes['class'];
};

const props = withDefaults(defineProps<Props>(), {
    audioPermissionRequested: false,
    videoPermissionRequested: false,
    audioPermissionLoading: false,
    videoPermissionLoading: false,
    backgroundBlurEnabled: false,
    backgroundBlurDisabled: false,
    backgroundBlurProcessing: false,
});

const emit = defineEmits<{
    'update-audio-input': [deviceId: string];
    'update-video-input': [deviceId: string];
    'preview-host': [element: HTMLElement | null];
    'request-audio-permission': [event: MouseEvent | KeyboardEvent];
    'request-video-permission': [event: MouseEvent | KeyboardEvent];
    'update-background-blur': [enabled: boolean];
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

function updateBackgroundBlur(value: boolean | 'indeterminate'): void {
    emit('update-background-blur', value === true);
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
                cn(
                    'border-border/60 bg-white/70 shadow-sm p-0 dark:border-neutral-800 dark:bg-neutral-900/70',
                    $attrs.class,
                )
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
                                class="w-full rounded-lg border-border/60 bg-white/80 dark:border-neutral-700 dark:bg-neutral-950"
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
                            class="w-full rounded-lg bg-white dark:bg-neutral-950"
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
                                class="w-full rounded-lg border-border/60 bg-white/80 dark:border-neutral-700 dark:bg-neutral-950"
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
                            class="w-full rounded-lg bg-white dark:bg-neutral-950"
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

                <div
                    class="flex items-start gap-3 rounded-md border border-border/60 bg-white/60 p-3 dark:border-neutral-800 dark:bg-neutral-950/50"
                >
                    <Checkbox
                        id="background-blur"
                        data-test="background-blur-checkbox"
                        :model-value="backgroundBlurEnabled"
                        :disabled="backgroundBlurDisabled"
                        class="mt-0.5"
                        @update:model-value="updateBackgroundBlur"
                    />
                    <Label
                        for="background-blur"
                        class="grid cursor-pointer gap-1 text-sm leading-none"
                    >
                        <span class="flex items-center gap-2 font-medium">
                            <Loader2
                                v-if="backgroundBlurProcessing"
                                class="size-4 animate-spin text-violet-500"
                            />
                            <Sparkles v-else class="size-4 text-violet-500" />
                            {{
                                backgroundBlurProcessing
                                    ? 'Применяем размытие...'
                                    : 'Размыть фон'
                            }}
                        </span>
                        <span class="text-xs leading-5 text-muted-foreground">
                            Фон будет размыт для всех участников, лицо останется
                            в фокусе.
                        </span>
                    </Label>
                </div>

                <!-- Error message -->
                <div
                    v-if="error"
                    class="rounded-xl border border-red-200/60 bg-red-50/50 p-3 dark:border-red-500/30 dark:bg-red-500/10"
                >
                    <p class="text-sm text-red-700 dark:text-red-300">
                        {{ error }}
                    </p>
                </div>

                <!-- Camera preview -->
                <div>
                    <div
                        class="relative aspect-video overflow-hidden rounded-xl border border-border/60 bg-slate-950 dark:border-neutral-800"
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
