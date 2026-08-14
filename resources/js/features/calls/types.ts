import type { Track } from 'livekit-client';

export type CallRoom = {
    public_id: string;
    name: string;
    description: string | null;
    visibility: string;
    has_password: boolean;
    waiting_room_enabled: boolean;
    livekit_room_name: string;
};

export type JoinForm = {
    display_name: string;
    password: string;
};

export type LiveKitTokenResponse = {
    livekit: {
        url: string;
        token: string;
        room_name: string;
        identity: string;
        expires_in: number;
    };
};

export type ParticipantTile = {
    identity: string;
    name: string;
    local: boolean;
    camera: boolean;
    microphone: boolean;
    speaking: boolean;
};

export type AttachableTrack = {
    kind: Track.Kind;
    attach(): HTMLMediaElement;
    detach(): HTMLMediaElement[];
};
