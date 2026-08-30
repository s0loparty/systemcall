import { ref, shallowRef } from 'vue';

import type { BackgroundProcessorWrapper } from '@livekit/track-processors';
import { Track } from 'livekit-client';
import type { LocalVideoTrack } from 'livekit-client';

import type { TestingMediaMode, VideoBackgroundPreferences } from '../types';

type UseVideoBackgroundOptions = {
    initialEnabled?: boolean;
    initialBlurRadius?: number;
    testingMediaMode?: TestingMediaMode;
};

const defaultBlurRadius = 30;

export function useVideoBackground(options: UseVideoBackgroundOptions = {}) {
    const enabled = ref(options.initialEnabled ?? false);
    const blurRadius = ref(options.initialBlurRadius ?? defaultBlurRadius);
    const processing = ref(false);
    const supported = ref(options.testingMediaMode === 'fake');
    const errorMessage = ref<string | null>(null);
    const processor = shallowRef<BackgroundProcessorWrapper | null>(null);
    const processedTrack = shallowRef<LocalVideoTrack | null>(null);

    async function setEnabled(value: boolean): Promise<void> {
        enabled.value = value;
    }

    async function setBlurRadius(value: number): Promise<void> {
        blurRadius.value = value;

        if (enabled.value && processedTrack.value) {
            await setProcessor(processedTrack.value);
        }
    }

    async function setProcessor(track: LocalVideoTrack): Promise<void> {
        const previousTrack = processedTrack.value;

        if (previousTrack && previousTrack !== track) {
            await removeProcessor(previousTrack);
        }

        processedTrack.value = track;
        errorMessage.value = null;

        if (options.testingMediaMode === 'fake') {
            supported.value = true;

            return;
        }

        if (!enabled.value) {
            await removeProcessor(track);

            return;
        }

        processing.value = true;

        try {
            const processors = await import('@livekit/track-processors');

            supported.value = processors.supportsBackgroundProcessors();

            if (!supported.value) {
                throw new Error(
                    'Браузер не поддерживает размытие фона для видео.',
                );
            }

            if (!processor.value) {
                processor.value = processors.BackgroundProcessor({
                    mode: 'background-blur',
                    blurRadius: blurRadius.value,
                });
                await track.setProcessor(processor.value);

                return;
            }

            if (track.getProcessor() !== processor.value) {
                await track.setProcessor(processor.value);
            }

            await processor.value.switchTo({
                mode: 'background-blur',
                blurRadius: blurRadius.value,
            });
        } catch (error) {
            enabled.value = false;
            errorMessage.value =
                error instanceof Error
                    ? error.message
                    : 'Не удалось включить размытие фона.';
            await removeProcessor(track);
        } finally {
            processing.value = false;
        }
    }

    async function removeProcessor(
        track = processedTrack.value,
    ): Promise<void> {
        if (!track) {
            return;
        }

        if (options.testingMediaMode === 'fake') {
            processedTrack.value = null;

            return;
        }

        const currentProcessor = track.getProcessor();

        if (currentProcessor && currentProcessor === processor.value) {
            await track.stopProcessor();
        }

        if (processedTrack.value === track) {
            processedTrack.value = null;
        }

        processor.value = null;
    }

    function preferences(): VideoBackgroundPreferences {
        return {
            enabled: enabled.value,
            blurRadius: blurRadius.value,
        };
    }

    function isCameraTrack(track: unknown): track is LocalVideoTrack {
        return (
            typeof track === 'object' &&
            track !== null &&
            'kind' in track &&
            track.kind === Track.Kind.Video &&
            'setProcessor' in track &&
            'stopProcessor' in track
        );
    }

    return {
        enabled,
        blurRadius,
        processing,
        supported,
        errorMessage,
        preferences,
        isCameraTrack,
        setEnabled,
        setBlurRadius,
        setProcessor,
        removeProcessor,
    };
}
