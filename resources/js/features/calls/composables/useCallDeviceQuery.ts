import { computed } from 'vue';

import type { SelectedDevices } from '../types';

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

    function toQuery(devices: SelectedDevices): Record<string, string> {
        const testingMediaMode =
            typeof window === 'undefined'
                ? null
                : new URLSearchParams(window.location.search).get('_e2e_media');

        return {
            ...(devices.audioInputId ? { audio: devices.audioInputId } : {}),
            ...(devices.videoInputId ? { video: devices.videoInputId } : {}),
            ...(testingMediaMode ? { _e2e_media: testingMediaMode } : {}),
        };
    }

    return {
        query,
        toQuery,
    };
}
