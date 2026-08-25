import { Room, Track, createLocalTracks } from 'livekit-client';
import type { LocalTrack } from 'livekit-client';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import type { SelectedDevices, TestingMediaMode } from '../types';

type UseMediaDevicesOptions = {
    initialSelectedDevices?: SelectedDevices;
    autoSelectCameraAfterPermission?: boolean;
    testingMediaMode?: TestingMediaMode;
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
    const previewHasVideo = ref(false);
    const deviceErrorMessage = ref<string | null>(null);
    const previewHost = ref<HTMLElement | null>(null);
    const previewTracks = ref<LocalTrack[]>([]);
    let previewGeneration = 0;

    async function loadDevices(requestPermissions = false): Promise<void> {
        if (options.testingMediaMode === 'fake') {
            loadTestingDevices(requestPermissions);

            return;
        }

        if (options.testingMediaMode === 'error') {
            audioInputs.value = [];
            videoInputs.value = [];
            deviceErrorMessage.value = 'E2 permission denied';

            return;
        }

        try {
            const [nextAudioInputs, nextVideoInputs] = await Promise.all([
                Room.getLocalDevices('audioinput', requestPermissions),
                Room.getLocalDevices('videoinput', requestPermissions),
            ]);

            audioInputs.value = nextAudioInputs;
            videoInputs.value = nextVideoInputs;
            deviceErrorMessage.value = null;

            if (
                requestPermissions &&
                options.autoSelectCameraAfterPermission === true &&
                (selectedDevices.value.videoInputId === '' ||
                    !nextVideoInputs.some(
                        (device) =>
                            device.deviceId ===
                            selectedDevices.value.videoInputId,
                    )) &&
                nextVideoInputs.length > 0
            ) {
                selectedDevices.value = {
                    ...selectedDevices.value,
                    videoInputId: nextVideoInputs[0].deviceId,
                };
            }

            if (
                requestPermissions &&
                selectedDevices.value.videoInputId !== ''
            ) {
                void startPreview();
            }
        } catch (error) {
            if (
                requestPermissions ||
                (audioInputs.value.length === 0 &&
                    videoInputs.value.length === 0)
            ) {
                deviceErrorMessage.value = formatDeviceError(error);
            }
        }
    }

    async function startPreview(): Promise<void> {
        const generation = ++previewGeneration;

        await stopPreview(false);

        previewLoading.value = true;
        deviceErrorMessage.value = null;

        try {
            if (selectedDevices.value.videoInputId === '') {
                await stopPreview();

                return;
            }

            const nextPreviewTracks =
                options.testingMediaMode === 'fake'
                    ? testingPreviewTracks()
                    : await createLocalTracks({
                          audio: false,
                          video: selectedDevices.value.videoInputId
                              ? { deviceId: selectedDevices.value.videoInputId }
                              : false,
                      });

            if (generation !== previewGeneration) {
                nextPreviewTracks.forEach((track) => track.stop());

                return;
            }

            previewTracks.value = nextPreviewTracks;
            previewEnabled.value = true;

            await nextTick();

            if (generation === previewGeneration) {
                renderPreview();
            }
        } catch (error) {
            previewEnabled.value = false;
            deviceErrorMessage.value = formatDeviceError(error);
        } finally {
            previewLoading.value = false;
        }
    }

    async function stopPreview(invalidate = true): Promise<void> {
        if (invalidate) {
            previewGeneration++;
        }

        previewTracks.value.forEach((track) => track.stop());
        previewTracks.value = [];
        previewEnabled.value = false;
        previewHasVideo.value = false;
        previewHost.value?.replaceChildren();
    }

    function setPreviewHost(element: HTMLElement | null): void {
        if (previewHost.value === element) {
            return;
        }

        previewHost.value = element;
        renderPreview();
    }

    function setAudioInput(deviceId: string): void {
        selectedDevices.value = {
            ...selectedDevices.value,
            audioInputId: deviceId,
        };
    }

    function setVideoInput(deviceId: string): void {
        selectedDevices.value = {
            ...selectedDevices.value,
            videoInputId: deviceId,
        };

        if (deviceId !== '') {
            void startPreview();

            return;
        }

        void stopPreview();
        deviceErrorMessage.value = null;
    }

    function renderPreview(): void {
        if (!previewHost.value) {
            return;
        }

        previewHost.value.replaceChildren();
        let hasVideo = false;

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
            hasVideo = true;
        });

        if (previewHasVideo.value !== hasVideo) {
            previewHasVideo.value = hasVideo;
        }
    }

    function loadTestingDevices(requestPermissions: boolean): void {
        const nextAudioInputs = [
            testingDevice('audioinput', 'e2-mic', 'E2 Fake Microphone'),
        ];
        const nextVideoInputs = [
            testingDevice('videoinput', 'e2-camera', 'E2 Fake Camera'),
        ];

        audioInputs.value = nextAudioInputs;
        videoInputs.value = nextVideoInputs;
        deviceErrorMessage.value = null;

        if (
            requestPermissions &&
            options.autoSelectCameraAfterPermission === true &&
            selectedDevices.value.videoInputId === ''
        ) {
            selectedDevices.value = {
                ...selectedDevices.value,
                videoInputId: nextVideoInputs[0].deviceId,
            };
        }

        if (requestPermissions && selectedDevices.value.videoInputId !== '') {
            void startPreview();
        }
    }

    function testingDevice(
        kind: MediaDeviceKind,
        deviceId: string,
        label: string,
    ): MediaDeviceInfo {
        return {
            deviceId,
            groupId: 'e2-media',
            kind,
            label,
            toJSON() {
                return this;
            },
        } as MediaDeviceInfo;
    }

    function testingPreviewTracks(): LocalTrack[] {
        const video = document.createElement('video');
        video.dataset.testid = 'fake-preview-video';
        video.autoplay = true;
        video.muted = true;
        video.playsInline = true;

        const track = {
            kind: Track.Kind.Video,
            attach: () => video,
            stop: () => {},
        };

        return [track as unknown as LocalTrack];
    }

    function formatDeviceError(error: unknown): string {
        if (error instanceof Error) {
            return error.message;
        }

        return 'Не удалось получить доступ к устройствам.';
    }

    onBeforeUnmount(() => {
        navigator.mediaDevices?.removeEventListener(
            'devicechange',
            handleDeviceChange,
        );
        void stopPreview();
    });

    function handleDeviceChange(): void {
        void loadDevices();
    }

    onMounted(async () => {
        await loadDevices();
        navigator.mediaDevices?.addEventListener(
            'devicechange',
            handleDeviceChange,
        );

        if (selectedDevices.value.videoInputId !== '') {
            void startPreview();
        }
    });

    return {
        audioInputs,
        videoInputs,
        selectedDevices,
        previewEnabled,
        previewLoading,
        previewHasVideo,
        deviceErrorMessage,
        loadDevices,
        startPreview,
        stopPreview,
        setPreviewHost,
        setAudioInput,
        setVideoInput,
    };
}
