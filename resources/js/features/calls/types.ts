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

export type WaitingRoomEntry = {
    id: number;
    room_public_id: string;
    display_name: string;
    status: 'pending' | 'approved' | 'rejected';
    guest: boolean;
    created_at?: string | null;
    decided_at?: string | null;
};

export type RoomMemberSummary = {
    id: number;
    room_public_id: string;
    display_name: string;
    role: 'owner' | 'moderator' | 'participant';
    guest: boolean;
    can_publish_audio: boolean;
    can_publish_video: boolean;
    can_publish_screen: boolean;
    left_at?: string | null;
};

export type JoinRoomResponse =
    | {
          member: {
              id: number;
              room_public_id: string;
              display_name: string;
              role: string;
              livekit_identity: string;
              guest: boolean;
          };
      }
    | {
          waiting_room: WaitingRoomEntry;
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
    member: Pick<RoomMemberSummary, 'id' | 'display_name' | 'role' | 'guest'>;
    occurred_at: string;
};

export type RoomMemberRoleChangedEvent = {
    type: 'member_role_changed';
    room_public_id: string;
    member: RoomMemberSummary;
    moderator_user_id: number;
    occurred_at: string;
};

export type RoomMemberMutedEvent = {
    type: 'member_muted';
    room_public_id: string;
    member: RoomMemberSummary;
    moderator_user_id: number;
    occurred_at: string;
};

export type RoomMemberRemovedEvent = {
    type: 'member_removed';
    room_public_id: string;
    member: RoomMemberSummary;
    moderator_user_id: number;
    occurred_at: string;
};

export type RoomMemberBannedEvent = {
    type: 'member_banned';
    room_public_id: string;
    member: RoomMemberSummary;
    ban: {
        id: number;
        reason: string | null;
        expires_at: string | null;
    };
    moderator_user_id: number;
    occurred_at: string;
};

export type WaitingRoomEntryRequestedEvent = {
    type: 'waiting_room_entry_requested';
    room_public_id: string;
    waiting_room: WaitingRoomEntry;
    occurred_at: string;
};

export type WaitingRoomEntryDecidedEvent = {
    type: 'waiting_room_entry_decided';
    room_public_id: string;
    waiting_room: WaitingRoomEntry;
    member: RoomMemberJoinedEvent['member'] | null;
    occurred_at: string;
};

export type RoomApplicationActivity = {
    id: string;
    message: string;
    occurredAt: string;
};
