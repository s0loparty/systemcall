import { Room, RoomEvent, Track } from 'livekit-client';
import type {
    LocalTrackPublication,
    Participant,
    RemoteParticipant,
    RemoteTrack,
    RemoteTrackPublication,
} from 'livekit-client';
import { markRaw, nextTick, ref, shallowRef } from 'vue';
import type { AttachableTrack, ParticipantTile } from '../types';

export function useLiveKit() {
    const activeRoom = shallowRef<Room | null>(null);
    const connected = ref(false);
    const microphoneEnabled = ref(false);
    const cameraEnabled = ref(false);
    const tiles = ref<ParticipantTile[]>([]);
    const mediaHosts = new Map<string, HTMLElement>();
    const mediaElements = new Map<string, HTMLMediaElement[]>();

    async function connect(url: string, token: string): Promise<void> {
        const liveKitRoom = new Room({
            adaptiveStream: true,
            dynacast: true,
        });

        bindRoomEvents(liveKitRoom);
        activeRoom.value = markRaw(liveKitRoom);

        await liveKitRoom.connect(url, token);
        connected.value = true;

        upsertParticipant(liveKitRoom.localParticipant);
        liveKitRoom.remoteParticipants.forEach((participant) => {
            upsertParticipant(participant);
            attachExistingTracks(participant);
        });

        await liveKitRoom.localParticipant.enableCameraAndMicrophone();
        microphoneEnabled.value =
            liveKitRoom.localParticipant.isMicrophoneEnabled;
        cameraEnabled.value = liveKitRoom.localParticipant.isCameraEnabled;
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
        await activeRoom.value.localParticipant.setCameraEnabled(enabled);
        cameraEnabled.value = activeRoom.value.localParticipant.isCameraEnabled;
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
                    _publication: RemoteTrackPublication,
                    participant: RemoteParticipant,
                ) => {
                    attachMedia(participant.identity, track);
                    syncParticipantState(participant);
                },
            )
            .on(
                RoomEvent.TrackUnsubscribed,
                (
                    track: RemoteTrack,
                    _publication: RemoteTrackPublication,
                    participant: RemoteParticipant,
                ) => {
                    detachMedia(participant.identity, track);
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
                    if (publication.track) {
                        detachMedia(participant.identity, publication.track);
                    }

                    syncParticipantState(participant);
                },
            )
            .on(RoomEvent.TrackMuted, (_publication, participant) => {
                syncParticipantState(participant);
            })
            .on(RoomEvent.TrackUnmuted, (_publication, participant) => {
                syncParticipantState(participant);
            })
            .on(RoomEvent.ActiveSpeakersChanged, (speakers: Participant[]) => {
                const activeIdentities = new Set(
                    speakers.map((participant) => participant.identity),
                );

                tiles.value = tiles.value.map((tile) => ({
                    ...tile,
                    speaking: activeIdentities.has(tile.identity),
                }));
            })
            .on(RoomEvent.Disconnected, () => {
                clearState();
            });
    }

    function attachExistingTracks(participant: Participant): void {
        participant.getTrackPublications().forEach((publication) => {
            if (publication.track) {
                attachMedia(participant.identity, publication.track);
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
        upsertParticipant(participant);
    }

    function removeParticipant(identity: string): void {
        detachIdentityMedia(identity);
        tiles.value = tiles.value.filter((tile) => tile.identity !== identity);
    }

    function attachMedia(
        identity: string,
        track: AttachableTrack | null | undefined,
        muted = false,
    ): void {
        if (!track) {
            return;
        }

        const element = track.attach();
        element.autoplay = true;

        if (element instanceof HTMLVideoElement) {
            element.playsInline = true;
        }

        element.muted = muted || track.kind === Track.Kind.Audio;
        element.dataset.identity = identity;

        mediaElements.set(identity, [
            ...(mediaElements.get(identity) ?? []),
            element,
        ]);

        nextTick(() => renderMedia(identity));
    }

    function detachMedia(
        identity: string,
        track: AttachableTrack | null | undefined,
    ): void {
        if (!track) {
            return;
        }

        const detachedElements = track.detach();
        detachedElements.forEach((element) => element.remove());
        mediaElements.set(
            identity,
            (mediaElements.get(identity) ?? []).filter(
                (element) => !detachedElements.includes(element),
            ),
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
        microphoneEnabled.value = false;
        cameraEnabled.value = false;
        tiles.value = [];
        detachAllMedia();
    }

    return {
        connected,
        microphoneEnabled,
        cameraEnabled,
        tiles,
        connect,
        disconnect,
        toggleMicrophone,
        toggleCamera,
        setMediaHost,
    };
}
