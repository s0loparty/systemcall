import { computed } from 'vue';

import type { SelectedDevices, VideoBackgroundPreferences } from '../types';

export function useCallDeviceQuery() {
    const query = computed(() => {
        if (typeof window === 'undefined') {
            return {
                audioInputId: '',
                videoInputId: '',
            } satisfies SelectedDevices;
        }

        const params = new URLSearchParams(window.location.search);

        return {
            audioInputId: params.get('audio') ?? '',
            videoInputId: params.get('video') ?? '',
        } satisfies SelectedDevices;
    });

    const videoBackground = computed(() => {
        if (typeof window === 'undefined') {
            return {
                enabled: false,
                blurRadius: 30,
            } satisfies VideoBackgroundPreferences;
        }

        const params = new URLSearchParams(window.location.search);
        const blurRadius = Number(params.get('background_blur_radius') ?? 30);

        return {
            enabled: params.get('background_blur') === '1',
            blurRadius:
                Number.isFinite(blurRadius) && blurRadius > 0 ? blurRadius : 30,
        } satisfies VideoBackgroundPreferences;
    });

    function toQuery(
        devices: SelectedDevices,
        background?: VideoBackgroundPreferences,
    ): Record<string, string> {
        const testingMediaMode =
            typeof window === 'undefined'
                ? null
                : new URLSearchParams(window.location.search).get('_e2e_media');

        return {
            ...(devices.audioInputId ? { audio: devices.audioInputId } : {}),
            ...(devices.videoInputId ? { video: devices.videoInputId } : {}),
            ...(background?.enabled ? { background_blur: '1' } : {}),
            ...(background?.enabled
                ? { background_blur_radius: String(background.blurRadius) }
                : {}),
            ...(testingMediaMode ? { _e2e_media: testingMediaMode } : {}),
        };
    }

    return {
        query,
        videoBackground,
        toQuery,
    };
}
