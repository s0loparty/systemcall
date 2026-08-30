import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import { Track, createLocalTracks } from 'livekit-client';
import type { LocalTrack, LocalVideoTrack } from 'livekit-client';

import type { SelectedDevices, TestingMediaMode } from '../types';

type UseMediaDevicesOptions = {
    initialSelectedDevices?: SelectedDevices;
    autoSelectCameraAfterPermission?: boolean;
    testingMediaMode?: TestingMediaMode;
    processPreviewVideoTrack?: (track: LocalVideoTrack) => Promise<void>;
};

export function useMediaDevices(options: UseMediaDevicesOptions = {}) {
    const audioInputs = ref<MediaDeviceInfo[]>([]);
    const videoInputs = ref<MediaDeviceInfo[]>([]);
    const selectedDevices = ref<SelectedDevices>({
        audioInputId: '',
        videoInputId: '',
    });
    const preferredDevices = ref<SelectedDevices>({
        audioInputId: options.initialSelectedDevices?.audioInputId ?? '',
        videoInputId: options.initialSelectedDevices?.videoInputId ?? '',
    });

    const audioPermissionRequested = ref(false);
    const videoPermissionRequested = ref(false);
    const audioPermissionLoading = ref(false);
    const videoPermissionLoading = ref(false);
    const permissionsRequested = computed(
        () => audioPermissionRequested.value || videoPermissionRequested.value,
    );
    const permissionsLoading = computed(
        () => audioPermissionLoading.value || videoPermissionLoading.value,
    );

    const previewEnabled = ref(false);
    const previewLoading = ref(false);
    const previewHasVideo = ref(false);
    const deviceErrorMessage = ref<string | null>(null);

    const previewHost = ref<HTMLElement | null>(null);
    const previewTracks = ref<LocalTrack[]>([]);

    let previewGeneration = 0;

    /**
     * Enumerates devices only.
     *
     * IMPORTANT:
     * This function never calls getUserMedia() and therefore never owns
     * the browser permission request flow.
     */
    async function loadDevices(): Promise<void> {
        if (options.testingMediaMode === 'fake') {
            if (audioPermissionRequested.value) {
                loadTestingDevices('audio');
            } else {
                audioInputs.value = [];
            }

            if (videoPermissionRequested.value) {
                loadTestingDevices('video');
            } else {
                videoInputs.value = [];
            }

            return;
        }

        if (options.testingMediaMode === 'error') {
            audioInputs.value = [];
            videoInputs.value = [];
            deviceErrorMessage.value = 'E2 permission denied';

            return;
        }

        if (!navigator.mediaDevices?.enumerateDevices) {
            audioInputs.value = [];
            videoInputs.value = [];
            deviceErrorMessage.value =
                'Браузер не поддерживает получение списка устройств.';

            return;
        }

        try {
            const devices = await navigator.mediaDevices.enumerateDevices();

            const nextAudioInputs = devices.filter(
                (device) => device.kind === 'audioinput',
            );
            const nextVideoInputs = devices.filter(
                (device) => device.kind === 'videoinput',
            );

            audioInputs.value = audioPermissionRequested.value
                ? nextAudioInputs
                : [];
            videoInputs.value = videoPermissionRequested.value
                ? nextVideoInputs
                : [];

            deviceErrorMessage.value = null;
        } catch (error) {
            audioInputs.value = [];
            videoInputs.value = [];
            deviceErrorMessage.value = formatDeviceError(error);
        }
    }

    /**
     * The ONLY entry point that is allowed to request camera/microphone
     * permission from the browser.
     *
     * Call this only from an explicit user action.
     */
    async function requestAudioPermission(
        event?: MouseEvent | KeyboardEvent,
    ): Promise<void> {
        if (!isTrustedPermissionEvent(event)) {
            return;
        }

        if (audioPermissionLoading.value) {
            return;
        }

        audioPermissionLoading.value = true;
        deviceErrorMessage.value = null;

        try {
            if (options.testingMediaMode === 'fake') {
                audioPermissionRequested.value = true;
                loadTestingDevices('audio');
                selectAudioAfterPermission();

                return;
            }

            if (options.testingMediaMode === 'error') {
                audioPermissionRequested.value = true;
                audioInputs.value = [];
                deviceErrorMessage.value = 'E2 permission denied';

                return;
            }

            await requestMediaPermission('audio');
            audioPermissionRequested.value = true;

            await loadDevices();
            selectAudioAfterPermission();
        } catch (error) {
            audioPermissionRequested.value = true;
            deviceErrorMessage.value = formatDeviceError(error);
            await loadDevices();
        } finally {
            audioPermissionLoading.value = false;
        }
    }

    async function requestVideoPermission(
        event?: MouseEvent | KeyboardEvent,
    ): Promise<void> {
        if (!isTrustedPermissionEvent(event)) {
            return;
        }

        if (videoPermissionLoading.value) {
            return;
        }

        videoPermissionLoading.value = true;
        deviceErrorMessage.value = null;

        try {
            if (options.testingMediaMode === 'fake') {
                videoPermissionRequested.value = true;
                loadTestingDevices('video');
                selectCameraAfterPermission();
                await startPreviewIfSelected();

                return;
            }

            if (options.testingMediaMode === 'error') {
                videoPermissionRequested.value = true;
                videoInputs.value = [];
                deviceErrorMessage.value = 'E2 permission denied';

                return;
            }

            await requestMediaPermission('video');
            videoPermissionRequested.value = true;

            await loadDevices();
            selectCameraAfterPermission();
            await startPreviewIfSelected();
        } catch (error) {
            videoPermissionRequested.value = true;
            deviceErrorMessage.value = formatDeviceError(error);
            await loadDevices();
        } finally {
            videoPermissionLoading.value = false;
        }
    }

    async function requestMediaPermission(
        kind: 'audio' | 'video',
    ): Promise<void> {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error(
                'Браузер не поддерживает доступ к камере и микрофону.',
            );
        }

        const stream = await navigator.mediaDevices.getUserMedia({
            audio: kind === 'audio',
            video: kind === 'video',
        });

        try {
            await Promise.resolve();
        } finally {
            stream.getTracks().forEach((track) => track.stop());
        }
    }

    function isTrustedPermissionEvent(
        event?: MouseEvent | KeyboardEvent,
    ): boolean {
        if (!event?.isTrusted) {
            return false;
        }

        return event.type === 'click' || event.type === 'keydown';
    }

    function selectCameraAfterPermission(): void {
        if (
            options.autoSelectCameraAfterPermission !== true ||
            videoInputs.value.length === 0
        ) {
            return;
        }

        const preferredVideoInputId =
            selectedDevices.value.videoInputId ||
            preferredDevices.value.videoInputId;
        const selectedVideoExists = videoInputs.value.some(
            (device) => device.deviceId === preferredVideoInputId,
        );

        if (preferredVideoInputId !== '' && selectedVideoExists) {
            selectedDevices.value = {
                ...selectedDevices.value,
                videoInputId: preferredVideoInputId,
            };

            return;
        }

        selectedDevices.value = {
            ...selectedDevices.value,
            videoInputId: videoInputs.value[0].deviceId,
        };
    }

    function selectAudioAfterPermission(): void {
        if (audioInputs.value.length === 0) {
            return;
        }

        const preferredAudioInputId =
            selectedDevices.value.audioInputId ||
            preferredDevices.value.audioInputId;
        const selectedAudioExists = audioInputs.value.some(
            (device) => device.deviceId === preferredAudioInputId,
        );

        if (preferredAudioInputId !== '' && selectedAudioExists) {
            selectedDevices.value = {
                ...selectedDevices.value,
                audioInputId: preferredAudioInputId,
            };

            return;
        }

        selectedDevices.value = {
            ...selectedDevices.value,
            audioInputId: audioInputs.value[0].deviceId,
        };
    }

    async function startPreviewIfSelected(): Promise<void> {
        if (selectedDevices.value.videoInputId === '') {
            return;
        }

        await startPreview();
    }

    async function startPreview(): Promise<void> {
        // Prevent a persisted videoInputId or any accidental caller from
        // triggering createLocalTracks before an explicit permission request.
        if (!videoPermissionRequested.value) {
            return;
        }

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
                              ? {
                                    deviceId:
                                        selectedDevices.value.videoInputId,
                                }
                              : false,
                      });

            if (generation !== previewGeneration) {
                nextPreviewTracks.forEach((track) => track.stop());

                return;
            }

            previewTracks.value = nextPreviewTracks;
            previewEnabled.value = true;
            await processPreviewVideoTrack();

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
        preferredDevices.value = {
            ...preferredDevices.value,
            audioInputId: deviceId,
        };
        selectedDevices.value = {
            ...selectedDevices.value,
            audioInputId: deviceId,
        };
    }

    function setVideoInput(deviceId: string): void {
        preferredDevices.value = {
            ...preferredDevices.value,
            videoInputId: deviceId,
        };
        selectedDevices.value = {
            ...selectedDevices.value,
            videoInputId: deviceId,
        };

        if (deviceId !== '' && videoPermissionRequested.value) {
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

    async function processPreviewVideoTrack(): Promise<void> {
        if (!options.processPreviewVideoTrack) {
            return;
        }

        const videoTrack = previewTracks.value.find(
            (track): track is LocalVideoTrack =>
                track.kind === Track.Kind.Video &&
                'setProcessor' in track &&
                'stopProcessor' in track,
        );

        if (!videoTrack) {
            return;
        }

        await options.processPreviewVideoTrack(videoTrack);
        renderPreview();
    }

    function loadTestingDevices(kind?: 'audio' | 'video'): void {
        if (kind === undefined || kind === 'audio') {
            audioInputs.value = [
                testingDevice('audioinput', 'e2-mic', 'E2 Fake Microphone'),
            ];
        }

        if (kind === undefined || kind === 'video') {
            videoInputs.value = [
                testingDevice('videoinput', 'e2-camera', 'E2 Fake Camera'),
            ];
        }

        deviceErrorMessage.value = null;
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
        if (error instanceof DOMException) {
            if (
                error.name === 'NotAllowedError' ||
                error.name === 'PermissionDeniedError'
            ) {
                return 'Доступ к камере и микрофону запрещён в браузере.';
            }

            if (error.name === 'NotFoundError') {
                return 'Камера или микрофон не найдены.';
            }

            if (error.name === 'NotReadableError') {
                return 'Не удалось открыть устройство. Возможно, оно используется другим приложением.';
            }
        }

        if (error instanceof Error) {
            return error.message;
        }

        return 'Не удалось получить доступ к устройствам.';
    }

    function handleDeviceChange(): void {
        // Do not enumerate or touch media devices before the user has
        // explicitly interacted with the permission button.
        if (!permissionsRequested.value) {
            return;
        }

        void loadDevices();
    }

    onMounted(() => {
        // Intentionally no loadDevices(), no getUserMedia(), no
        // createLocalTracks() here.
        navigator.mediaDevices?.addEventListener(
            'devicechange',
            handleDeviceChange,
        );
    });

    onBeforeUnmount(() => {
        navigator.mediaDevices?.removeEventListener(
            'devicechange',
            handleDeviceChange,
        );
        void stopPreview();
    });

    return {
        audioInputs,
        videoInputs,
        selectedDevices,
        permissionsRequested,
        permissionsLoading,
        audioPermissionRequested,
        videoPermissionRequested,
        audioPermissionLoading,
        videoPermissionLoading,
        previewEnabled,
        previewLoading,
        previewHasVideo,
        deviceErrorMessage,
        loadDevices,
        requestAudioPermission,
        requestVideoPermission,
        startPreview,
        stopPreview,
        processPreviewVideoTrack,
        setPreviewHost,
        setAudioInput,
        setVideoInput,
    };
}
