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

export type CallPageProps = {
    room: CallRoom;
    can_join_without_password: boolean;
    can_moderate_waiting_room: boolean;
    can_moderate_members: boolean;
    current_room_member_id: number | null;
    global_pinned_room_member_id: number | null;
    room_members: RoomMemberSummary[];
    room_subrooms: RoomSubroomSummary[];
    room_messages: RoomMessageSummary[];
    waiting_room_entries: WaitingRoomEntry[];
    current_waiting_room_entry: WaitingRoomEntry | null;
    testing_media_mode: TestingMediaMode;
};

export type TestingMediaMode = 'fake' | 'error' | null;

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

export type RoomSubroomSummary = {
    id: number;
    room_public_id: string;
    public_id: string;
    name: string;
    position: number;
    is_default: boolean;
};

export type SubroomTokenResponse = LiveKitTokenResponse & {
    member: RoomMemberSummary;
    subroom: RoomSubroomSummary | null;
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
    room_subroom_id: number | null;
    display_name: string;
    role: 'owner' | 'moderator' | 'participant';
    livekit_identity: string;
    guest: boolean;
    can_publish_audio: boolean;
    can_publish_video: boolean;
    can_publish_screen: boolean;
    hand_raised_at?: string | null;
    left_at?: string | null;
};

export type RoomMessageSummary = {
    id: number;
    room_public_id: string;
    room_member_id: number | null;
    display_name: string;
    body: string;
    message_type: 'text' | 'system';
    created_at: string | null;
};

export type JoinRoomResponse =
    | {
          member: RoomMemberSummary;
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
    handRaised: boolean;
};

export type ParticipantTileActions = {
    canPersonalPin: boolean;
    canGlobalPin: boolean;
    canPromoteModerator: boolean;
    canKick: boolean;
    canBan: boolean;
};

export type AttachableTrack = {
    kind: Track.Kind;
    source?: Track.Source;
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
    member: RoomMemberSummary;
    occurred_at: string;
};

export type RoomMessageCreatedEvent = {
    type: 'message_created';
    room_public_id: string;
    message: RoomMessageSummary;
    occurred_at: string;
};

export type RoomMemberHandUpdatedEvent = {
    type: 'member_hand_updated';
    room_public_id: string;
    member: RoomMemberSummary;
    occurred_at: string;
};

export type RoomGlobalPinUpdatedEvent = {
    type: 'global_pin_updated';
    room_public_id: string;
    global_pinned_room_member_id: number | null;
    member: {
        id: number;
        display_name: string;
    } | null;
    moderator_room_member_id: number;
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

export type RoomSubroomCreatedEvent = {
    type: 'subroom_created';
    room_public_id: string;
    subroom: RoomSubroomSummary;
    occurred_at: string;
};

export type RoomMemberSubroomChangedEvent = {
    type: 'member_subroom_changed';
    room_public_id: string;
    member: RoomMemberSummary;
    subroom: RoomSubroomSummary | null;
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
    member: RoomMemberSummary | null;
    occurred_at: string;
};

export type RoomApplicationActivity = {
    id: string;
    message: string;
    occurredAt: string;
};
