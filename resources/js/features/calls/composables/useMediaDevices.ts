import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import { Room, Track, createLocalTracks } from 'livekit-client';
import type { LocalTrack } from 'livekit-client';

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

        let permissionError: unknown = null;

        try {
            if (requestPermissions) {
                await requestMediaPermissions();
            }
        } catch (error) {
            permissionError = error;
        }

        const [nextAudioInputs, nextVideoInputs] = await Promise.allSettled([
            Room.getLocalDevices('audioinput', false),
            Room.getLocalDevices('videoinput', false),
        ]);

        const loadedAudioInputs =
            nextAudioInputs.status === 'fulfilled' ? nextAudioInputs.value : [];
        const loadedVideoInputs =
            nextVideoInputs.status === 'fulfilled' ? nextVideoInputs.value : [];

        audioInputs.value = loadedAudioInputs;
        videoInputs.value = loadedVideoInputs;

        if (loadedAudioInputs.length > 0 || loadedVideoInputs.length > 0) {
            deviceErrorMessage.value = null;
        } else if (permissionError) {
            deviceErrorMessage.value = formatDeviceError(permissionError);
        } else {
            const deviceError =
                nextAudioInputs.status === 'rejected'
                    ? nextAudioInputs.reason
                    : nextVideoInputs.status === 'rejected'
                      ? nextVideoInputs.reason
                      : null;

            deviceErrorMessage.value = deviceError
                ? formatDeviceError(deviceError)
                : null;
        }

        if (
            requestPermissions &&
            options.autoSelectCameraAfterPermission === true &&
            (selectedDevices.value.videoInputId === '' ||
                !loadedVideoInputs.some(
                    (device) =>
                        device.deviceId === selectedDevices.value.videoInputId,
                )) &&
            loadedVideoInputs.length > 0
        ) {
            selectedDevices.value = {
                ...selectedDevices.value,
                videoInputId: loadedVideoInputs[0].deviceId,
            };
        }

        if (requestPermissions && selectedDevices.value.videoInputId !== '') {
            void startPreview();
        }
    }

    async function requestMediaPermissions(): Promise<void> {
        if (!navigator.mediaDevices?.getUserMedia) {
            return;
        }

        const requestedStreams: MediaStream[] = [];

        try {
            requestedStreams.push(
                await navigator.mediaDevices.getUserMedia({
                    audio: true,
                    video: true,
                }),
            );
        } catch (combinedError) {
            const fallbackResults = await Promise.allSettled([
                navigator.mediaDevices.getUserMedia({ audio: true }),
                navigator.mediaDevices.getUserMedia({ video: true }),
            ]);

            if (
                fallbackResults.every((result) => result.status === 'rejected')
            ) {
                throw combinedError;
            }

            fallbackResults.forEach((result) => {
                if (result.status === 'fulfilled') {
                    requestedStreams.push(result.value);
                }
            });
        } finally {
            requestedStreams.forEach((stream) => {
                stream.getTracks().forEach((track) => track.stop());
            });
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
        await loadDevices(options.autoSelectCameraAfterPermission === true);
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
