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
    screenShare: boolean;
};

export type AttachableTrack = {
    kind: Track.Kind;
    attach(): HTMLMediaElement;
    detach(): HTMLMediaElement[];
};

export type CallConnectionState =
    'disconnected' | 'connecting' | 'connected' | 'reconnecting';

export type SelectedDevices = {
    audioInputId: string;
    videoInputId: string;
};

export type EchoConnectionState =
    'connected' | 'connecting' | 'reconnecting' | 'disconnected' | 'failed';

export type RoomMemberJoinedEvent = {
    type: 'member_joined';
    room_public_id: string;
    member: {
        id: number;
        display_name: string;
        role: string;
        guest: boolean;
    };
    occurred_at: string;
};

export type RoomApplicationActivity = {
    id: string;
    message: string;
    occurredAt: string;
};
