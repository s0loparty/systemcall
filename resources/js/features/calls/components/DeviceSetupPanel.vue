<script setup lang="ts">
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
    <section
        class="grid gap-4 rounded-md border border-neutral-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_18rem] dark:border-neutral-800 dark:bg-neutral-950"
    >
        <div class="space-y-4">
            <div>
                <h2
                    class="text-base font-semibold text-neutral-950 dark:text-white"
                >
                    Устройства
                </h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    Камера и микрофон необязательны. Можно войти без них и
                    включить позже.
                </p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1 text-sm font-medium">
                    <span class="text-neutral-700 dark:text-neutral-300">
                        Микрофон
                    </span>
                    <select
                        data-test="audio-input-select"
                        class="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-950 shadow-xs transition outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-neutral-800 dark:bg-neutral-900 dark:text-white"
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
                </label>

                <label class="space-y-1 text-sm font-medium">
                    <span class="text-neutral-700 dark:text-neutral-300">
                        Камера
                    </span>
                    <select
                        data-test="video-input-select"
                        class="w-full rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-950 shadow-xs transition outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-neutral-800 dark:bg-neutral-900 dark:text-white"
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
                </label>
            </div>

            <p
                v-if="error"
                class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-200"
            >
                {{ error }}
            </p>

            <div class="flex flex-wrap gap-2">
                <button
                    data-test="refresh-devices"
                    class="rounded-md bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                    type="button"
                    @click="emit('refresh')"
                >
                    Обновить устройства
                </button>
                <button
                    data-test="toggle-preview"
                    class="rounded-md border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-800 transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-900"
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
                </button>
            </div>
        </div>

        <div
            class="relative min-h-48 overflow-hidden rounded-3xl bg-neutral-950 shadow-inner"
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
                class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-neutral-400"
            >
                Выберите камеру и запустите предпросмотр, чтобы проверить
                доступ.
            </div>
        </div>
    </section>
</template>
