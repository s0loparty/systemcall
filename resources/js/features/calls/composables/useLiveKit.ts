import { markRaw, nextTick, ref, shallowRef } from 'vue';

import { Room, RoomEvent, Track } from 'livekit-client';
import type {
    ConnectionState,
    LocalTrackPublication,
    Participant,
    RemoteParticipant,
    RemoteTrack,
    RemoteTrackPublication,
} from 'livekit-client';

import type {
    AttachableTrack,
    CallConnectionState,
    ParticipantTile,
    ScreenSharePreset,
    SelectedDevices,
    TestingMediaMode,
    VideoBackgroundPreferences,
} from '../types';
import { useVideoBackground } from './useVideoBackground';

type InitialMediaState = {
    microphone?: boolean;
    camera?: boolean;
};

type LiveKitConnectContext = {
    identity: string;
    name: string;
    roomName: string;
};

type UseLiveKitOptions = {
    testingMediaMode?: TestingMediaMode;
    initialVideoBackground?: VideoBackgroundPreferences;
};

type FakeParticipantState = {
    identity: string;
    name: string;
    camera: boolean;
    microphone: boolean;
    screenShare: boolean;
    backgroundBlur: boolean;
    updatedAt: number;
};

type ScreenShareQuality = {
    width: number;
    height: number;
    frameRate: number;
    maxBitrate: number;
};

const SCREEN_SHARE_PRESETS: Record<ScreenSharePreset, ScreenShareQuality> = {
    '480p': { width: 854, height: 480, frameRate: 15, maxBitrate: 1_000_000 },
    '720p': { width: 1280, height: 720, frameRate: 15, maxBitrate: 2_000_000 },
    '1024p': {
        width: 1820,
        height: 1024,
        frameRate: 15,
        maxBitrate: 3_000_000,
    },
};

export function useLiveKit(options: UseLiveKitOptions = {}) {
    const activeRoom = shallowRef<Room | null>(null);
    const connected = ref(false);
    const connectionState = ref<CallConnectionState>('disconnected');
    const microphoneEnabled = ref(false);
    const cameraEnabled = ref(false);
    const screenShareEnabled = ref(false);
    const activeSpeakerIdentity = ref<string | null>(null);
    const deviceErrorMessage = ref<string | null>(null);
    const tiles = ref<ParticipantTile[]>([]);
    const videoBackground = useVideoBackground({
        initialEnabled: options.initialVideoBackground?.enabled,
        initialBlurRadius: options.initialVideoBackground?.blurRadius,
        testingMediaMode: options.testingMediaMode,
    });
    const mediaHosts = new Map<string, HTMLElement>();
    const mediaElements = new Map<string, HTMLMediaElement[]>();
    let fakeCameraTrack: MediaStreamTrack | null = null;
    let fakeScreenShareTrack: MediaStreamTrack | null = null;
    let fakeCameraFrameTimer: number | null = null;
    let fakeScreenShareFrameTimer: number | null = null;
    let fakeRoomStorageKey: string | null = null;
    let fakeLocalIdentity: string | null = null;
    let fakeStorageListener: ((event: StorageEvent) => void) | null = null;
    let fakeUnloadListener: (() => void) | null = null;

    async function connect(
        url: string,
        token: string,
        selectedDevices: SelectedDevices,
        initialMedia: InitialMediaState = {},
        context?: LiveKitConnectContext,
    ): Promise<void> {
        if (options.testingMediaMode === 'fake') {
            connectFake(selectedDevices, initialMedia, context);

            return;
        }

        const liveKitRoom = new Room({ adaptiveStream: true, dynacast: true });
        bindRoomEvents(liveKitRoom);
        activeRoom.value = markRaw(liveKitRoom);
        connectionState.value = 'connecting';
        deviceErrorMessage.value = null;
        await liveKitRoom.connect(url, token);
        connected.value = true;
        connectionState.value = 'connected';
        upsertParticipant(liveKitRoom.localParticipant);
        liveKitRoom.remoteParticipants.forEach((participant) => {
            upsertParticipant(participant);
            attachExistingTracks(participant);
        });
        const shouldEnableMicrophone =
            initialMedia.microphone ?? selectedDevices.audioInputId !== '';
        const shouldEnableCamera =
            initialMedia.camera ?? selectedDevices.videoInputId !== '';
        const mediaResults = await Promise.allSettled([
            liveKitRoom.localParticipant.setMicrophoneEnabled(
                shouldEnableMicrophone,
                selectedDevices.audioInputId
                    ? { deviceId: selectedDevices.audioInputId }
                    : undefined,
            ),
            liveKitRoom.localParticipant.setCameraEnabled(
                shouldEnableCamera,
                selectedDevices.videoInputId
                    ? { deviceId: selectedDevices.videoInputId }
                    : undefined,
                { simulcast: true },
            ),
        ]);

        if (activeRoom.value !== liveKitRoom) {
            return;
        }

        await applyVideoBackgroundToLocalCamera();

        const failedMediaRequest = mediaResults.find(
            (result) => result.status === 'rejected',
        );

        if (failedMediaRequest) {
            deviceErrorMessage.value = mediaErrorMessage(
                failedMediaRequest.reason,
            );
        }

        microphoneEnabled.value =
            liveKitRoom.localParticipant.isMicrophoneEnabled;
        cameraEnabled.value = liveKitRoom.localParticipant.isCameraEnabled;
        screenShareEnabled.value = hasActiveScreenShare(
            liveKitRoom.localParticipant,
        );
        syncParticipantState(liveKitRoom.localParticipant);
    }

    async function disconnect(): Promise<void> {
        if (options.testingMediaMode === 'fake') {
            removeFakeLocalParticipant();
        } else {
            activeRoom.value?.disconnect();
        }

        clearState();
    }

    async function toggleMicrophone(): Promise<void> {
        if (options.testingMediaMode === 'fake') {
            if (!fakeLocalIdentity) {
                return;
            }

            await setFakeMicrophoneEnabled(!microphoneEnabled.value);

            return;
        }

        const enabled = !microphoneEnabled.value;

        if (!activeRoom.value) {
            return;
        }

        await activeRoom.value.localParticipant.setMicrophoneEnabled(enabled);
        microphoneEnabled.value =
            activeRoom.value.localParticipant.isMicrophoneEnabled;
        syncParticipantState(activeRoom.value.localParticipant);
    }

    async function toggleCamera(): Promise<void> {
        if (options.testingMediaMode === 'fake') {
            if (!fakeLocalIdentity) {
                return;
            }

            await setFakeCameraEnabled(!cameraEnabled.value);

            return;
        }

        const enabled = !cameraEnabled.value;

        if (!activeRoom.value) {
            return;
        }

        if (!enabled) {
            await videoBackground.removeProcessor();
        }

        await activeRoom.value.localParticipant.setCameraEnabled(
            enabled,
            undefined,
            { simulcast: true },
        );
        await applyVideoBackgroundToLocalCamera();
        cameraEnabled.value = activeRoom.value.localParticipant.isCameraEnabled;
        syncParticipantState(activeRoom.value.localParticipant);
    }

    async function toggleScreenShare(
        preset: ScreenSharePreset = '720p',
    ): Promise<void> {
        if (options.testingMediaMode === 'fake') {
            if (!fakeLocalIdentity) {
                return;
            }

            await setFakeScreenShareEnabled(
                !screenShareEnabled.value,
                SCREEN_SHARE_PRESETS[preset],
            );

            return;
        }

        const enabled = !screenShareEnabled.value;
        const quality = SCREEN_SHARE_PRESETS[preset];

        if (!activeRoom.value) {
            return;
        }

        await activeRoom.value.localParticipant.setScreenShareEnabled(
            enabled,
            {
                audio: true,
                contentHint: 'detail',
                resolution: {
                    width: quality.width,
                    height: quality.height,
                    frameRate: quality.frameRate,
                },
            },
            {
                simulcast: false,
                degradationPreference: 'maintain-resolution',
                screenShareEncoding: {
                    maxBitrate: quality.maxBitrate,
                    maxFramerate: quality.frameRate,
                },
            },
        );
        screenShareEnabled.value = hasActiveScreenShare(
            activeRoom.value.localParticipant,
        );
        syncParticipantState(activeRoom.value.localParticipant);
    }

    function setMediaHost(identity: string, element: HTMLElement | null): void {
        if (element === null) {
            mediaHosts.delete(identity);

            return;
        }

        mediaHosts.set(identity, element);
        renderMedia(identity);
    }

    function bindRoomEvents(liveKitRoom: Room): void {
        liveKitRoom
            .on(
                RoomEvent.ParticipantConnected,
                (participant: RemoteParticipant) =>
                    upsertParticipant(participant),
            )
            .on(
                RoomEvent.ParticipantDisconnected,
                (participant: RemoteParticipant) =>
                    removeParticipant(participant.identity),
            )
            .on(
                RoomEvent.TrackPublished,
                (
                    _publication: RemoteTrackPublication,
                    participant: RemoteParticipant,
                ) => syncParticipantState(participant),
            )
            .on(
                RoomEvent.TrackSubscribed,
                (
                    track: RemoteTrack,
                    publication: RemoteTrackPublication,
                    participant: RemoteParticipant,
                ) => {
                    attachMedia(
                        participant.identity,
                        track,
                        false,
                        publication.source,
                    );
                    syncParticipantState(participant);
                },
            )
            .on(
                RoomEvent.TrackUnpublished,
                (
                    publication: RemoteTrackPublication,
                    participant: RemoteParticipant,
                ) => {
                    detachMedia(
                        participant.identity,
                        publication.track,
                        publication.source,
                    );
                    syncParticipantState(participant);
                },
            )
            .on(
                RoomEvent.TrackUnsubscribed,
                (
                    track: RemoteTrack,
                    publication: RemoteTrackPublication,
                    participant: RemoteParticipant,
                ) => {
                    detachMedia(
                        participant.identity,
                        track,
                        publication.source,
                    );
                    syncParticipantState(participant);
                },
            )
            .on(
                RoomEvent.LocalTrackPublished,
                (
                    publication: LocalTrackPublication,
                    participant: Participant,
                ) => {
                    if (
                        publication.track &&
                        publication.track.kind === Track.Kind.Video
                    ) {
                        attachMedia(
                            participant.identity,
                            publication.track,
                            true,
                            publication.source,
                        );
                    }

                    syncParticipantState(participant);
                },
            )
            .on(
                RoomEvent.LocalTrackUnpublished,
                (
                    publication: LocalTrackPublication,
                    participant: Participant,
                ) => {
                    detachMedia(
                        participant.identity,
                        publication.track,
                        publication.source,
                    );
                    syncParticipantState(participant);
                },
            )
            .on(RoomEvent.TrackMuted, (publication, participant) => {
                if (publication.source === Track.Source.ScreenShare) {
                    detachMedia(
                        participant.identity,
                        publication.track,
                        publication.source,
                    );
                }

                syncParticipantState(participant);
            })
            .on(RoomEvent.TrackUnmuted, (_publication, participant) =>
                syncParticipantState(participant),
            )
            .on(RoomEvent.ActiveSpeakersChanged, (speakers: Participant[]) => {
                const activeIdentities = new Set(
                    speakers.map((participant) => participant.identity),
                );
                activeSpeakerIdentity.value = speakers[0]?.identity ?? null;
                tiles.value = tiles.value.map((tile) => ({
                    ...tile,
                    speaking: activeIdentities.has(tile.identity),
                }));
            })
            .on(RoomEvent.ConnectionStateChanged, (state: ConnectionState) => {
                connectionState.value = normalizeConnectionState(state);
                connected.value = state !== 'disconnected';
            })
            .on(RoomEvent.Reconnecting, () => {
                connectionState.value = 'reconnecting';
            })
            .on(RoomEvent.Reconnected, () => {
                connectionState.value = 'connected';
            })
            .on(RoomEvent.MediaDevicesError, (error: Error) => {
                deviceErrorMessage.value = error.message;
            })
            .on(RoomEvent.Disconnected, () => clearState());
    }

    function attachExistingTracks(participant: Participant): void {
        participant.getTrackPublications().forEach((publication) => {
            if (publication.track) {
                attachMedia(
                    participant.identity,
                    publication.track,
                    false,
                    publication.source,
                );
            }
        });
    }

    function upsertParticipant(participant: Participant): void {
        const nextTile: ParticipantTile = {
            identity: participant.identity,
            name: participant.name || participant.identity,
            local: participant.isLocal,
            camera: participant.isCameraEnabled,
            microphone: participant.isMicrophoneEnabled,
            speaking: participant.isSpeaking,
            screenShare: hasActiveScreenShare(participant),
            backgroundBlur:
                participant.isLocal &&
                participant.isCameraEnabled &&
                videoBackground.enabled.value,
            handRaised: false,
        };
        const index = tiles.value.findIndex(
            (tile) => tile.identity === participant.identity,
        );

        if (index === -1) {
            tiles.value = [...tiles.value, nextTile];

            return;
        }

        tiles.value = tiles.value.map((tile) =>
            tile.identity === participant.identity ? nextTile : tile,
        );
    }

    function syncParticipantState(participant: Participant): void {
        if (participant.isLocal) {
            screenShareEnabled.value = hasActiveScreenShare(participant);
        }

        upsertParticipant(participant);
    }

    function removeParticipant(identity: string): void {
        detachIdentityMedia(identity);
        tiles.value = tiles.value.filter((tile) => tile.identity !== identity);
    }

    function patchLocalTile(partial: Partial<ParticipantTile>): void {
        const localIdentity =
            options.testingMediaMode === 'fake'
                ? fakeLocalIdentity
                : activeRoom.value?.localParticipant.identity;

        if (!localIdentity) {
            return;
        }

        tiles.value = tiles.value.map((tile) =>
            tile.identity === localIdentity ? { ...tile, ...partial } : tile,
        );
    }

    async function setFakeMicrophoneEnabled(enabled: boolean): Promise<void> {
        if (!fakeLocalIdentity) {
            return;
        }

        microphoneEnabled.value = enabled;
        patchLocalTile({ microphone: enabled });
        await updateFakeLocalParticipant({ microphone: enabled });
    }

    async function setFakeCameraEnabled(enabled: boolean): Promise<void> {
        if (!fakeLocalIdentity) {
            return;
        }

        if (enabled && !fakeCameraTrack) {
            fakeCameraTrack = createFakeVideoTrack();
            attachFakeVideoElement(
                fakeLocalIdentity,
                fakeCameraTrack,
                Track.Source.Camera,
            );
        }

        if (!enabled && fakeCameraTrack) {
            detachMedia(fakeLocalIdentity, undefined, Track.Source.Camera);
            fakeCameraTrack.stop();
            fakeCameraTrack = null;
        }

        cameraEnabled.value = enabled;
        patchLocalTile({
            camera: enabled,
            backgroundBlur: enabled && videoBackground.enabled.value,
        });
        await updateFakeLocalParticipant({
            camera: enabled,
            backgroundBlur: enabled && videoBackground.enabled.value,
        });
    }

    async function setFakeScreenShareEnabled(
        enabled: boolean,
        quality: ScreenShareQuality,
    ): Promise<void> {
        if (!fakeLocalIdentity) {
            return;
        }

        if (enabled && !fakeScreenShareTrack) {
            fakeScreenShareTrack = createFakeScreenShareTrack();
            void quality;
            attachFakeVideoElement(
                fakeLocalIdentity,
                fakeScreenShareTrack,
                Track.Source.ScreenShare,
            );
        }

        if (!enabled && fakeScreenShareTrack) {
            detachMedia(fakeLocalIdentity, undefined, Track.Source.ScreenShare);
            fakeScreenShareTrack.stop();
            fakeScreenShareTrack = null;
        }

        screenShareEnabled.value = enabled;
        patchLocalTile({ screenShare: enabled });
        await updateFakeLocalParticipant({ screenShare: enabled });
    }

    function connectFake(
        selectedDevices: SelectedDevices,
        initialMedia: InitialMediaState,
        context?: LiveKitConnectContext,
    ): void {
        if (!context) {
            throw new Error('Fake LiveKit connection requires member context.');
        }

        removeFakeLocalParticipant();
        connectionState.value = 'connecting';
        deviceErrorMessage.value = null;
        fakeLocalIdentity = context.identity;
        fakeRoomStorageKey = `systemcall:e2e-livekit:${context.roomName}`;
        bindFakePresenceEvents();

        const shouldEnableMicrophone =
            initialMedia.microphone ?? selectedDevices.audioInputId !== '';
        const shouldEnableCamera =
            initialMedia.camera ?? selectedDevices.videoInputId !== '';

        connected.value = true;
        connectionState.value = 'connected';
        microphoneEnabled.value = shouldEnableMicrophone;
        cameraEnabled.value = shouldEnableCamera;
        screenShareEnabled.value = false;

        void updateFakeLocalParticipant({
            identity: context.identity,
            name: context.name,
            microphone: shouldEnableMicrophone,
            camera: shouldEnableCamera,
            screenShare: false,
            backgroundBlur: shouldEnableCamera && videoBackground.enabled.value,
        });

        if (shouldEnableCamera) {
            void setFakeCameraEnabled(true);
        }
    }

    function bindFakePresenceEvents(): void {
        if (!fakeRoomStorageKey || typeof window === 'undefined') {
            return;
        }

        fakeStorageListener = (event: StorageEvent) => {
            if (event.key === fakeRoomStorageKey) {
                syncFakeParticipants();
            }
        };
        fakeUnloadListener = () => removeFakeLocalParticipant();
        window.addEventListener('storage', fakeStorageListener);
        window.addEventListener('beforeunload', fakeUnloadListener);
    }

    function unbindFakePresenceEvents(): void {
        if (fakeStorageListener) {
            window.removeEventListener('storage', fakeStorageListener);
            fakeStorageListener = null;
        }

        if (fakeUnloadListener) {
            window.removeEventListener('beforeunload', fakeUnloadListener);
            fakeUnloadListener = null;
        }
    }

    async function updateFakeLocalParticipant(
        partial: Partial<FakeParticipantState>,
    ): Promise<void> {
        if (!fakeRoomStorageKey || !fakeLocalIdentity) {
            return;
        }

        const participants = readFakeParticipants();
        const current = participants[fakeLocalIdentity];

        participants[fakeLocalIdentity] = {
            identity: fakeLocalIdentity,
            name: current?.name ?? fakeLocalIdentity,
            camera: current?.camera ?? false,
            microphone: current?.microphone ?? false,
            screenShare: current?.screenShare ?? false,
            backgroundBlur: current?.backgroundBlur ?? false,
            updatedAt: Date.now(),
            ...partial,
        };

        writeFakeParticipants(participants);
        syncFakeParticipants();
    }

    function removeFakeLocalParticipant(): void {
        if (!fakeRoomStorageKey || !fakeLocalIdentity) {
            unbindFakePresenceEvents();

            return;
        }

        const participants = readFakeParticipants();
        delete participants[fakeLocalIdentity];
        writeFakeParticipants(participants);
        unbindFakePresenceEvents();
        fakeLocalIdentity = null;
        fakeRoomStorageKey = null;
    }

    function readFakeParticipants(): Record<string, FakeParticipantState> {
        if (!fakeRoomStorageKey) {
            return {};
        }

        try {
            const stored = window.localStorage.getItem(fakeRoomStorageKey);

            return stored
                ? (JSON.parse(stored) as Record<string, FakeParticipantState>)
                : {};
        } catch {
            return {};
        }
    }

    function writeFakeParticipants(
        participants: Record<string, FakeParticipantState>,
    ): void {
        if (!fakeRoomStorageKey) {
            return;
        }

        window.localStorage.setItem(
            fakeRoomStorageKey,
            JSON.stringify(participants),
        );
    }

    function syncFakeParticipants(): void {
        const participants = Object.values(readFakeParticipants()).sort(
            (a, b) => a.identity.localeCompare(b.identity),
        );

        tiles.value = participants.map((participant) => ({
            identity: participant.identity,
            name: participant.name,
            local: participant.identity === fakeLocalIdentity,
            camera: participant.camera,
            microphone: participant.microphone,
            speaking: false,
            screenShare: participant.screenShare,
            backgroundBlur: participant.backgroundBlur,
            handRaised: false,
        }));

        participants.forEach((participant) => {
            syncFakeParticipantMedia(participant);
        });

        [...mediaElements.keys()]
            .filter(
                (identity) =>
                    !participants.some(
                        (participant) => participant.identity === identity,
                    ),
            )
            .forEach((identity) => detachIdentityMedia(identity));
    }

    function syncFakeParticipantMedia(participant: FakeParticipantState): void {
        const isLocal = participant.identity === fakeLocalIdentity;

        if (!isLocal) {
            syncFakeRemoteVideoElement(
                participant.identity,
                Track.Source.Camera,
                participant.camera,
            );
            syncFakeRemoteVideoElement(
                participant.identity,
                Track.Source.ScreenShare,
                participant.screenShare,
            );

            return;
        }

        if (!participant.camera) {
            detachMedia(participant.identity, undefined, Track.Source.Camera);
        }

        if (!participant.screenShare) {
            detachMedia(
                participant.identity,
                undefined,
                Track.Source.ScreenShare,
            );
        }
    }

    function syncFakeRemoteVideoElement(
        identity: string,
        source: Track.Source,
        enabled: boolean,
    ): void {
        const sourceKey = source.replaceAll('_', '-');
        const existing = (mediaElements.get(identity) ?? []).some(
            (element) => element.dataset.source === sourceKey,
        );

        if (!enabled) {
            detachMedia(identity, undefined, source);

            return;
        }

        if (existing) {
            return;
        }

        const element = document.createElement('video');
        element.autoplay = true;
        element.playsInline = true;
        element.muted = true;
        element.dataset.identity = identity;
        element.dataset.source = sourceKey;
        mediaElements.set(identity, [
            ...(mediaElements.get(identity) ?? []),
            element,
        ]);
        nextTick(() => renderMedia(identity));
    }

    function attachFakeVideoElement(
        identity: string,
        track: MediaStreamTrack,
        source: Track.Source,
    ): void {
        detachMedia(identity, undefined, source);

        const element = document.createElement('video');
        element.autoplay = true;
        element.playsInline = true;
        element.muted = true;
        element.srcObject = new MediaStream([track]);
        element.dataset.identity = identity;
        element.dataset.source = source.replaceAll('_', '-');
        mediaElements.set(identity, [
            ...(mediaElements.get(identity) ?? []),
            element,
        ]);
        void element.play().catch(() => undefined);
        nextTick(() => renderMedia(identity));
    }

    function createFakeVideoTrack(): MediaStreamTrack {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        const width = 640;
        const height = 360;
        let frame = 0;
        canvas.width = width;
        canvas.height = height;
        const drawFrame = () => {
            if (!context) {
                return;
            }

            context.fillStyle = '#1f2937';
            context.fillRect(0, 0, width, height);
            context.fillStyle = '#38bdf8';
            context.fillRect((frame % width) - 80, 120, 160, 120);
            context.fillStyle = '#ffffff';
            context.font = '28px sans-serif';
            context.fillText('E2E camera', 32, 54);
            frame += 8;
        };
        drawFrame();
        fakeCameraFrameTimer = window.setInterval(drawFrame, 200);
        const [track] = canvas.captureStream(5).getVideoTracks();
        track.addEventListener(
            'ended',
            () => {
                if (fakeCameraFrameTimer !== null) {
                    window.clearInterval(fakeCameraFrameTimer);
                    fakeCameraFrameTimer = null;
                }
            },
            { once: true },
        );

        return track;
    }

    function createFakeScreenShareTrack(): MediaStreamTrack {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        const width = 1280;
        const height = 720;
        let frame = 0;
        canvas.width = width;
        canvas.height = height;
        const drawFrame = () => {
            if (!context) {
                return;
            }

            context.fillStyle = '#020617';
            context.fillRect(0, 0, width, height);
            context.fillStyle = '#0ea5e9';
            context.fillRect(80, 84, width - 160, 84);
            context.fillStyle = '#1e293b';
            context.fillRect(80, 208, width - 160, 380);
            context.fillStyle = '#38bdf8';
            context.fillRect(120 + (frame % 760), 320, 240, 120);
            context.fillStyle = '#ffffff';
            context.font = '48px sans-serif';
            context.fillText('E2E screen share', 120, 142);
            context.font = '32px sans-serif';
            context.fillText('Synthetic display media', 120, 268);
            frame += 12;
        };
        drawFrame();
        fakeScreenShareFrameTimer = window.setInterval(drawFrame, 200);
        const [track] = canvas.captureStream(5).getVideoTracks();
        track.addEventListener(
            'ended',
            () => {
                if (fakeScreenShareFrameTimer !== null) {
                    window.clearInterval(fakeScreenShareFrameTimer);
                    fakeScreenShareFrameTimer = null;
                }
            },
            { once: true },
        );

        return track;
    }

    function attachMedia(
        identity: string,
        track: AttachableTrack | null | undefined,
        muted = false,
        source = track?.source,
    ): void {
        if (!track) {
            return;
        }

        if (source) {
            detachMedia(identity, undefined, source);
        }

        const element = track.attach();
        element.autoplay = true;

        if (element instanceof HTMLVideoElement) {
            element.playsInline = true;
        }

        element.muted = muted;
        element.dataset.identity = identity;

        if (source) {
            element.dataset.source = source.replaceAll('_', '-');
        }

        mediaElements.set(identity, [
            ...(mediaElements.get(identity) ?? []),
            element,
        ]);
        nextTick(() => renderMedia(identity));
    }

    function detachMedia(
        identity: string,
        track: AttachableTrack | null | undefined,
        source = track?.source,
    ): void {
        const normalizedSource = source?.replaceAll('_', '-');
        const currentElements = mediaElements.get(identity) ?? [];
        const detachedElements = track?.detach() ?? [];
        const detachedSet = new Set(detachedElements);
        detachedElements.forEach((element) => element.remove());

        if (normalizedSource) {
            currentElements
                .filter(
                    (element) => element.dataset.source === normalizedSource,
                )
                .forEach((element) => {
                    element.remove();
                    detachedSet.add(element);
                });
        }

        mediaElements.set(
            identity,
            currentElements.filter((element) => !detachedSet.has(element)),
        );
    }

    function hasActiveScreenShare(participant: Participant): boolean {
        const publication = participant.getTrackPublication(
            Track.Source.ScreenShare,
        );

        return Boolean(
            publication && !publication.isMuted && publication.track,
        );
    }

    async function setVideoBackgroundEnabled(enabled: boolean): Promise<void> {
        await videoBackground.setEnabled(enabled);
        await applyVideoBackgroundToLocalCamera();
    }

    async function applyVideoBackgroundToLocalCamera(): Promise<void> {
        const publication =
            activeRoom.value?.localParticipant.getTrackPublication(
                Track.Source.Camera,
            );
        const track = publication?.track;

        if (!track || !videoBackground.isCameraTrack(track)) {
            patchLocalTile({ backgroundBlur: false });

            return;
        }

        if (videoBackground.enabled.value) {
            await videoBackground.setProcessor(track);
        } else {
            await videoBackground.removeProcessor(track);
        }

        patchLocalTile({
            backgroundBlur:
                Boolean(publication) &&
                !publication.isMuted &&
                videoBackground.enabled.value,
        });
    }

    function detachIdentityMedia(identity: string): void {
        mediaElements.get(identity)?.forEach((element) => element.remove());
        mediaElements.delete(identity);
    }
    function detachAllMedia(): void {
        stopFakeMediaTracks();
        mediaElements.forEach((elements) =>
            elements.forEach((element) => element.remove()),
        );
        mediaElements.clear();
        mediaHosts.clear();
    }
    function stopFakeMediaTracks(): void {
        fakeCameraTrack?.stop();
        fakeCameraTrack = null;
        fakeScreenShareTrack?.stop();
        fakeScreenShareTrack = null;
        void videoBackground.removeProcessor();

        if (fakeCameraFrameTimer !== null) {
            window.clearInterval(fakeCameraFrameTimer);
            fakeCameraFrameTimer = null;
        }

        if (fakeScreenShareFrameTimer !== null) {
            window.clearInterval(fakeScreenShareFrameTimer);
            fakeScreenShareFrameTimer = null;
        }
    }
    function renderMedia(identity: string): void {
        const host = mediaHosts.get(identity);

        if (!host) {
            return;
        }

        host.replaceChildren(...(mediaElements.get(identity) ?? []));
    }
    function clearState(): void {
        activeRoom.value = null;
        connected.value = false;
        connectionState.value = 'disconnected';
        microphoneEnabled.value = false;
        cameraEnabled.value = false;
        screenShareEnabled.value = false;
        activeSpeakerIdentity.value = null;
        videoBackground.enabled.value =
            options.initialVideoBackground?.enabled ?? false;
        tiles.value = [];
        detachAllMedia();
    }
    function normalizeConnectionState(
        state: ConnectionState,
    ): CallConnectionState {
        if (state === 'connected') {
            return 'connected';
        }

        if (state === 'connecting') {
            return 'connecting';
        }

        if (state === 'disconnected') {
            return 'disconnected';
        }

        return 'reconnecting';
    }
    function mediaErrorMessage(error: unknown): string {
        if (error instanceof Error) {
            return error.message;
        }

        return 'Вы подключены к комнате, но камеру или микрофон включить не удалось.';
    }

    return {
        connected,
        connectionState,
        microphoneEnabled,
        cameraEnabled,
        screenShareEnabled,
        activeSpeakerIdentity,
        deviceErrorMessage,
        tiles,
        connect,
        disconnect,
        toggleMicrophone,
        toggleCamera,
        toggleScreenShare,
        videoBackgroundEnabled: videoBackground.enabled,
        videoBackgroundProcessing: videoBackground.processing,
        videoBackgroundErrorMessage: videoBackground.errorMessage,
        setVideoBackgroundEnabled,
        setMediaHost,
    };
}
