import { Room, Track, createLocalTracks } from 'livekit-client';
import type { LocalTrack } from 'livekit-client';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import type { SelectedDevices } from '../types';

type UseMediaDevicesOptions = {
    initialSelectedDevices?: SelectedDevices;
};

export function useMediaDevices(options: UseMediaDevicesOptions = {}) {
    const audioInputs = ref<MediaDeviceInfo[]>([]);
    const videoInputs = ref<MediaDeviceInfo[]>([]);
    const selectedDevices = ref<SelectedDevices>({
        audioInputId: options.initialSelectedDevices?.audioInputId ?? '',
        videoInputId: options.initialSelectedDevices?.videoInputId ?? '',
    });
    const previewEnabled = ref(false);
    const previewLoading = ref(false);
    const deviceErrorMessage = ref<string | null>(null);
    const previewHost = ref<HTMLElement | null>(null);
    const previewTracks = ref<LocalTrack[]>([]);

    async function loadDevices(requestPermissions = false): Promise<void> {
        try {
            const [nextAudioInputs, nextVideoInputs] = await Promise.all([
                Room.getLocalDevices('audioinput', requestPermissions),
                Room.getLocalDevices('videoinput', requestPermissions),
            ]);

            audioInputs.value = nextAudioInputs;
            videoInputs.value = nextVideoInputs;
        } catch (error) {
            deviceErrorMessage.value = formatDeviceError(error);
        }
    }

    async function startPreview(): Promise<void> {
        await stopPreview();

        previewLoading.value = true;
        deviceErrorMessage.value = null;

        try {
            if (
                selectedDevices.value.audioInputId === '' &&
                selectedDevices.value.videoInputId === ''
            ) {
                deviceErrorMessage.value =
                    'Выберите камеру или микрофон для предпросмотра.';

                return;
            }

            previewTracks.value = await createLocalTracks({
                audio: selectedDevices.value.audioInputId
                    ? { deviceId: selectedDevices.value.audioInputId }
                    : false,
                video: selectedDevices.value.videoInputId
                    ? { deviceId: selectedDevices.value.videoInputId }
                    : false,
            });
            previewEnabled.value = true;

            await nextTick();
            renderPreview();
            await loadDevices();
        } catch (error) {
            previewEnabled.value = false;
            deviceErrorMessage.value = formatDeviceError(error);
        } finally {
            previewLoading.value = false;
        }
    }

    async function stopPreview(): Promise<void> {
        previewTracks.value.forEach((track) => track.stop());
        previewTracks.value = [];
        previewEnabled.value = false;
        previewHost.value?.replaceChildren();
    }

    function setPreviewHost(element: HTMLElement | null): void {
        previewHost.value = element;
        renderPreview();
    }

    function setAudioInput(deviceId: string): void {
        selectedDevices.value = {
            ...selectedDevices.value,
            audioInputId: deviceId,
        };

        if (previewEnabled.value) {
            void startPreview();
        }
    }

    function setVideoInput(deviceId: string): void {
        selectedDevices.value = {
            ...selectedDevices.value,
            videoInputId: deviceId,
        };

        if (previewEnabled.value) {
            void startPreview();
        }
    }

    function renderPreview(): void {
        if (!previewHost.value) {
            return;
        }

        previewHost.value.replaceChildren();

        previewTracks.value.forEach((track) => {
            if (track.kind !== Track.Kind.Video) {
                return;
            }

            const element = track.attach();
            element.autoplay = true;
            element.muted = true;

            if (element instanceof HTMLVideoElement) {
                element.playsInline = true;
            }

            previewHost.value?.append(element);
        });
    }

    function formatDeviceError(error: unknown): string {
        if (error instanceof Error) {
            return error.message;
        }

        return 'Не удалось получить доступ к устройствам.';
    }

    onBeforeUnmount(() => {
        void stopPreview();
    });

    onMounted(() => {
        void loadDevices();
    });

    return {
        audioInputs,
        videoInputs,
        selectedDevices,
        previewEnabled,
        previewLoading,
        deviceErrorMessage,
        loadDevices,
        startPreview,
        stopPreview,
        setPreviewHost,
        setAudioInput,
        setVideoInput,
    };
}
