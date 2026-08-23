import { Room, RoomEvent, Track } from 'livekit-client';
import type {
    ConnectionState,
    LocalTrackPublication,
    Participant,
    RemoteParticipant,
    RemoteTrack,
    RemoteTrackPublication,
} from 'livekit-client';
import { markRaw, nextTick, ref, shallowRef } from 'vue';
import type {
    AttachableTrack,
    CallConnectionState,
    ParticipantTile,
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

    async function connect(
        url: string,
        token: string,
        selectedDevices: SelectedDevices,
        initialMedia: InitialMediaState = {},
    ): Promise<void> {
        const liveKitRoom = new Room({
            adaptiveStream: true,
            dynacast: true,
        });

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
            microphoneEnabled.value = enabled;
            patchLocalTile({ microphone: enabled });

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
            cameraEnabled.value = enabled;
            patchLocalTile({ camera: enabled });

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

    async function toggleScreenShare(): Promise<void> {
        if (!activeRoom.value) {
            return;
        }

        const enabled = !screenShareEnabled.value;
        await activeRoom.value.localParticipant.setScreenShareEnabled(
            enabled,
            { audio: true },
            { simulcast: true },
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
                (participant: RemoteParticipant) => {
                    upsertParticipant(participant);
                },
            )
            .on(
                RoomEvent.ParticipantDisconnected,
                (participant: RemoteParticipant) => {
                    removeParticipant(participant.identity);
                },
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
            .on(RoomEvent.TrackUnmuted, (_publication, participant) => {
                syncParticipantState(participant);
            })
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
            .on(RoomEvent.Disconnected, () => {
                clearState();
            });
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

    function attachMedia(
        identity: string,
        track: AttachableTrack | null | undefined,
        muted = false,
        source = track?.source,
    ): void {
        if (!track) {
            return;
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
        mediaElements.forEach((elements) => {
            elements.forEach((element) => element.remove());
        });
        mediaElements.clear();
        mediaHosts.clear();
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
