<script setup lang="ts">
import { Head, useHttp, usePage } from '@inertiajs/vue3';
import { Room, RoomEvent, Track } from 'livekit-client';
import type {
    LocalTrackPublication,
    Participant,
    RemoteParticipant,
    RemoteTrack,
    RemoteTrackPublication,
} from 'livekit-client';
import {
    computed,
    markRaw,
    nextTick,
    onBeforeUnmount,
    ref,
    shallowRef,
} from 'vue';
import { store as issueLiveKitToken } from '@/routes/rooms/livekit_token';
import { store as joinRoom } from '@/routes/rooms/memberships';

type RoomPageProps = {
    room: {
        public_id: string;
        name: string;
        description: string | null;
        visibility: string;
        has_password: boolean;
        waiting_room_enabled: boolean;
        livekit_room_name: string;
    };
    can_join_without_password: boolean;
};

type JoinForm = {
    display_name: string;
    password: string;
};

type LiveKitTokenResponse = {
    livekit: {
        url: string;
        token: string;
        room_name: string;
        identity: string;
        expires_in: number;
    };
};

type ParticipantTile = {
    identity: string;
    name: string;
    local: boolean;
    camera: boolean;
    microphone: boolean;
    speaking: boolean;
};

type AttachableTrack = {
    kind: Track.Kind;
    attach(): HTMLMediaElement;
    detach(): HTMLMediaElement[];
};

const props = defineProps<RoomPageProps>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const defaultDisplayName = computed(() => currentUser.value?.name ?? 'Guest');

const joinRequest = useHttp<JoinForm, unknown>({
    display_name: defaultDisplayName.value,
    password: '',
});
const tokenRequest = useHttp<Record<string, never>, LiveKitTokenResponse>({});

const activeRoom = shallowRef<Room | null>(null);
const connected = ref(false);
const connecting = ref(false);
const microphoneEnabled = ref(false);
const cameraEnabled = ref(false);
const errorMessage = ref<string | null>(null);
const tiles = ref<ParticipantTile[]>([]);
const mediaHosts = new Map<string, HTMLElement>();
const mediaElements = new Map<string, HTMLMediaElement[]>();

const needsPassword = computed(
    () => props.room.has_password && !props.can_join_without_password,
);

async function connect(): Promise<void> {
    if (connecting.value || connected.value) {
        return;
    }

    connecting.value = true;
    errorMessage.value = null;

    try {
        await joinRequest.post(joinRoom.url(props.room.public_id));

        const response = await tokenRequest.post(
            issueLiveKitToken.url(props.room.public_id),
        );

        const liveKitRoom = new Room({
            adaptiveStream: true,
            dynacast: true,
        });

        bindRoomEvents(liveKitRoom);

        await liveKitRoom.connect(response.livekit.url, response.livekit.token);
        activeRoom.value = markRaw(liveKitRoom);
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
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : 'Could not connect to the room.';
        await disconnect();
    } finally {
        connecting.value = false;
    }
}

async function disconnect(): Promise<void> {
    activeRoom.value?.disconnect();
    activeRoom.value = null;
    connected.value = false;
    microphoneEnabled.value = false;
    cameraEnabled.value = false;
    tiles.value = [];
    detachAllMedia();
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
            (publication: LocalTrackPublication, participant: Participant) => {
                if (
                    publication.track &&
                    publication.track.kind === Track.Kind.Video
                ) {
                    attachMedia(participant.identity, publication.track, true);
                }

                syncParticipantState(participant);
            },
        )
        .on(
            RoomEvent.LocalTrackUnpublished,
            (publication: LocalTrackPublication, participant: Participant) => {
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
            activeRoom.value = null;
            connected.value = false;
            microphoneEnabled.value = false;
            cameraEnabled.value = false;
            tiles.value = [];
            detachAllMedia();
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

function setMediaHost(identity: string, element: HTMLElement | null): void {
    if (element === null) {
        mediaHosts.delete(identity);

        return;
    }

    mediaHosts.set(identity, element);
    renderMedia(identity);
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

onBeforeUnmount(() => {
    void disconnect();
});
</script>

<template>
    <Head :title="`${room.name} call`" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-hidden p-4">
        <section
            class="relative overflow-hidden rounded-3xl border border-neutral-200 bg-neutral-950 text-white shadow-sm dark:border-neutral-800"
        >
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(34,197,94,0.25),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.22),transparent_34%)]"
            />
            <div
                class="relative flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="space-y-2">
                    <p class="text-sm text-neutral-300">
                        LiveKit room: {{ room.livekit_room_name }}
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ room.name }}
                    </h1>
                    <p
                        v-if="room.description"
                        class="max-w-2xl text-sm text-neutral-300"
                    >
                        {{ room.description }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="!connected"
                        class="rounded-full bg-white px-5 py-2 text-sm font-medium text-neutral-950 transition hover:bg-neutral-200 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="
                            connecting ||
                            tokenRequest.processing ||
                            joinRequest.processing
                        "
                        @click="connect"
                    >
                        {{ connecting ? 'Connecting...' : 'Join call' }}
                    </button>
                    <template v-else>
                        <button
                            class="rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/15 transition hover:bg-white/20"
                            type="button"
                            @click="toggleMicrophone"
                        >
                            {{ microphoneEnabled ? 'Mute mic' : 'Unmute mic' }}
                        </button>
                        <button
                            class="rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/15 transition hover:bg-white/20"
                            type="button"
                            @click="toggleCamera"
                        >
                            {{ cameraEnabled ? 'Stop camera' : 'Start camera' }}
                        </button>
                        <button
                            class="rounded-full bg-red-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-400"
                            type="button"
                            @click="disconnect"
                        >
                            Leave
                        </button>
                    </template>
                </div>
            </div>
        </section>

        <section
            v-if="needsPassword && !connected"
            class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-950"
        >
            <label class="text-sm font-medium" for="room-password">
                Room password
            </label>
            <input
                id="room-password"
                v-model="joinRequest.password"
                class="mt-2 w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm transition outline-none focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:focus:border-neutral-100"
                type="password"
                autocomplete="current-password"
            />
            <p
                v-if="joinRequest.errors.password"
                class="mt-2 text-sm text-red-600 dark:text-red-400"
            >
                {{ joinRequest.errors.password }}
            </p>
        </section>

        <p
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300"
        >
            {{ errorMessage }}
        </p>

        <section
            class="grid flex-1 auto-rows-fr grid-cols-1 gap-4 overflow-auto md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="tile in tiles"
                :key="tile.identity"
                class="relative min-h-64 overflow-hidden rounded-3xl border bg-neutral-950 shadow-sm transition"
                :class="
                    tile.speaking
                        ? 'border-emerald-400 ring-2 ring-emerald-400/40'
                        : 'border-neutral-200 dark:border-neutral-800'
                "
            >
                <div
                    :ref="
                        (element) =>
                            setMediaHost(
                                tile.identity,
                                element as HTMLElement | null,
                            )
                    "
                    class="absolute inset-0 [&_audio]:hidden [&_video]:h-full [&_video]:w-full [&_video]:object-cover"
                />
                <div
                    class="absolute inset-0 flex items-center justify-center bg-neutral-900 text-4xl font-semibold text-neutral-500 empty:hidden"
                >
                    <span v-if="!tile.camera">
                        {{ tile.name.slice(0, 1).toUpperCase() }}
                    </span>
                </div>
                <div
                    class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-3 bg-linear-to-t from-black/80 to-transparent p-4 text-white"
                >
                    <div>
                        <p class="font-medium">
                            {{ tile.name }}
                            <span v-if="tile.local" class="text-neutral-300"
                                >(you)</span
                            >
                        </p>
                        <p class="text-xs text-neutral-300">
                            {{ tile.microphone ? 'mic on' : 'mic off' }}
                        </p>
                    </div>
                    <span
                        v-if="tile.speaking"
                        class="rounded-full bg-emerald-400 px-2 py-1 text-xs font-medium text-emerald-950"
                    >
                        speaking
                    </span>
                </div>
            </article>

            <div
                v-if="tiles.length === 0"
                class="flex min-h-96 items-center justify-center rounded-3xl border border-dashed border-neutral-300 bg-neutral-50 text-center text-sm text-neutral-500 dark:border-neutral-800 dark:bg-neutral-950"
            >
                Join the call to start publishing microphone and camera.
            </div>
        </section>
    </div>
</template>
