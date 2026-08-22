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
        return {
            ...(devices.audioInputId ? { audio: devices.audioInputId } : {}),
            ...(devices.videoInputId ? { video: devices.videoInputId } : {}),
        };
    }

    return {
        query,
        toQuery,
    };
}
