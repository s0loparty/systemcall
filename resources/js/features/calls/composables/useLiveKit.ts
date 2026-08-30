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
} from '../types';

type InitialMediaState = {
    microphone?: boolean;
    camera?: boolean;
};

type UseLiveKitOptions = {
    testingMediaMode?: TestingMediaMode;
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
    const mediaHosts = new Map<string, HTMLElement>();
    const mediaElements = new Map<string, HTMLMediaElement[]>();
    let fakeMicrophoneTrack: MediaStreamTrack | null = null;
    let fakeCameraTrack: MediaStreamTrack | null = null;
    let fakeScreenShareTrack: MediaStreamTrack | null = null;
    let fakeCameraFrameTimer: number | null = null;
    let fakeScreenShareFrameTimer: number | null = null;

    async function connect(
        url: string,
        token: string,
        selectedDevices: SelectedDevices,
        initialMedia: InitialMediaState = {},
    ): Promise<void> {
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
        activeRoom.value?.disconnect();
        clearState();
    }

    async function toggleMicrophone(): Promise<void> {
        if (!activeRoom.value) {
return;
}

        const enabled = !microphoneEnabled.value;

        if (options.testingMediaMode === 'fake') {
            await setFakeMicrophoneEnabled(enabled);

            return;
        }

        await activeRoom.value.localParticipant.setMicrophoneEnabled(enabled);
        microphoneEnabled.value =
            activeRoom.value.localParticipant.isMicrophoneEnabled;
        syncParticipantState(activeRoom.value.localParticipant);
    }

    async function toggleCamera(): Promise<void> {
        if (!activeRoom.value) {
return;
}

        const enabled = !cameraEnabled.value;

        if (options.testingMediaMode === 'fake') {
            await setFakeCameraEnabled(enabled);

            return;
        }

        await activeRoom.value.localParticipant.setCameraEnabled(
            enabled,
            undefined,
            { simulcast: true },
        );
        cameraEnabled.value = activeRoom.value.localParticipant.isCameraEnabled;
        syncParticipantState(activeRoom.value.localParticipant);
    }

    async function toggleScreenShare(
        preset: ScreenSharePreset = '720p',
    ): Promise<void> {
        if (!activeRoom.value) {
return;
}

        const enabled = !screenShareEnabled.value;
        const quality = SCREEN_SHARE_PRESETS[preset];

        if (options.testingMediaMode === 'fake') {
            await setFakeScreenShareEnabled(enabled, quality);

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
        const localIdentity = activeRoom.value?.localParticipant.identity;

        if (!localIdentity) {
return;
}

        tiles.value = tiles.value.map((tile) =>
            tile.identity === localIdentity ? { ...tile, ...partial } : tile,
        );
    }

    async function setFakeMicrophoneEnabled(enabled: boolean): Promise<void> {
        if (!activeRoom.value) {
return;
}

        if (enabled && !fakeMicrophoneTrack) {
            fakeMicrophoneTrack = createFakeAudioTrack();
            await activeRoom.value.localParticipant.publishTrack(
                fakeMicrophoneTrack,
                { source: Track.Source.Microphone },
            );
        }

        if (!enabled && fakeMicrophoneTrack) {
            await activeRoom.value.localParticipant.unpublishTrack(
                fakeMicrophoneTrack,
                true,
            );
            fakeMicrophoneTrack.stop();
            fakeMicrophoneTrack = null;
        }

        microphoneEnabled.value = enabled;
        patchLocalTile({ microphone: enabled });
    }

    async function setFakeCameraEnabled(enabled: boolean): Promise<void> {
        if (!activeRoom.value) {
return;
}

        if (enabled && !fakeCameraTrack) {
            fakeCameraTrack = createFakeVideoTrack();
            await activeRoom.value.localParticipant.publishTrack(
                fakeCameraTrack,
                { source: Track.Source.Camera, simulcast: true },
            );
        }

        if (!enabled && fakeCameraTrack) {
            await activeRoom.value.localParticipant.unpublishTrack(
                fakeCameraTrack,
                true,
            );
            fakeCameraTrack.stop();
            fakeCameraTrack = null;
        }

        cameraEnabled.value = enabled;
        patchLocalTile({ camera: enabled });
    }

    async function setFakeScreenShareEnabled(
        enabled: boolean,
        quality: ScreenShareQuality,
    ): Promise<void> {
        if (!activeRoom.value) {
return;
}

        if (enabled && !fakeScreenShareTrack) {
            fakeScreenShareTrack = createFakeScreenShareTrack();
            await activeRoom.value.localParticipant.publishTrack(
                fakeScreenShareTrack,
                {
                    source: Track.Source.ScreenShare,
                    simulcast: false,
                    degradationPreference: 'maintain-resolution',
                    screenShareEncoding: {
                        maxBitrate: quality.maxBitrate,
                        maxFramerate: quality.frameRate,
                    },
                },
            );
        }

        if (!enabled && fakeScreenShareTrack) {
            await activeRoom.value.localParticipant.unpublishTrack(
                fakeScreenShareTrack,
                true,
            );
            fakeScreenShareTrack.stop();
            fakeScreenShareTrack = null;
        }

        screenShareEnabled.value = enabled;
        patchLocalTile({ screenShare: enabled });
    }

    function createFakeAudioTrack(): MediaStreamTrack {
        const audioContext = new AudioContext();
        const oscillator = audioContext.createOscillator();
        const destination = audioContext.createMediaStreamDestination();
        oscillator.frequency.value = 440;
        oscillator.connect(destination);
        oscillator.start();
        const [track] = destination.stream.getAudioTracks();
        track.addEventListener(
            'ended',
            () => {
                oscillator.stop();
                void audioContext.close();
            },
            { once: true },
        );

        return track;
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
        fakeMicrophoneTrack?.stop();
        fakeMicrophoneTrack = null;
        fakeCameraTrack?.stop();
        fakeCameraTrack = null;
        fakeScreenShareTrack?.stop();
        fakeScreenShareTrack = null;

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
        setMediaHost,
    };
}
