import { useConnectionStatus, useEcho } from '@laravel/echo-vue';
import { ref } from 'vue';
import type {
    RoomApplicationActivity,
    RoomGlobalPinUpdatedEvent,
    RoomMemberBannedEvent,
    RoomMemberHandUpdatedEvent,
    RoomMemberJoinedEvent,
    RoomMemberMutedEvent,
    RoomMemberRemovedEvent,
    RoomMemberRoleChangedEvent,
    RoomMemberSubroomChangedEvent,
    RoomMemberSummary,
    RoomMessageCreatedEvent,
    RoomMessageSummary,
    RoomSubroomCreatedEvent,
    RoomSubroomSummary,
    WaitingRoomEntry,
    WaitingRoomEntryDecidedEvent,
    WaitingRoomEntryRequestedEvent,
} from '../types';

type UseRoomApplicationEventsOptions = {
    roomMembers?: RoomMemberSummary[];
    roomMessages?: RoomMessageSummary[];
    roomSubrooms?: RoomSubroomSummary[];
    globalPinnedRoomMemberId?: number | null;
    waitingRoomEntries?: WaitingRoomEntry[];
    onWaitingRoomDecision?: (event: WaitingRoomEntryDecidedEvent) => void;
};

export function useRoomApplicationEvents(
    roomPublicId: string,
    options: UseRoomApplicationEventsOptions = {},
) {
    const echoConnectionStatus = useConnectionStatus();
    const activities = ref<RoomApplicationActivity[]>([]);
    const roomMembers = ref<RoomMemberSummary[]>(options.roomMembers ?? []);
    const roomMessages = ref<RoomMessageSummary[]>(options.roomMessages ?? []);
    const roomSubrooms = ref<RoomSubroomSummary[]>(options.roomSubrooms ?? []);
    const globalPinnedRoomMemberId = ref<number | null>(
        options.globalPinnedRoomMemberId ?? null,
    );
    const waitingRoomEntries = ref<WaitingRoomEntry[]>(
        options.waitingRoomEntries ?? [],
    );

    useEcho<RoomMemberJoinedEvent>(
        `room.${roomPublicId}`,
        '.room.member.joined',
        (event) => {
            upsertRoomMember(event.member);
            addActivity(
                `${event.type}:${event.member.id}:${event.occurred_at}`,
                `${event.member.display_name} joined the room`,
                event.occurred_at,
            );
        },
    );

    useEcho<RoomMessageCreatedEvent>(
        `room.${roomPublicId}`,
        '.room.message.created',
        (event) => {
            appendRoomMessage(event.message);
        },
    );

    useEcho<RoomMemberHandUpdatedEvent>(
        `room.${roomPublicId}`,
        '.room.member.hand-updated',
        (event) => {
            upsertRoomMember(event.member);
            addActivity(
                `${event.type}:${event.member.id}:${event.occurred_at}`,
                event.member.hand_raised_at
                    ? `${event.member.display_name} raised a hand`
                    : `${event.member.display_name} lowered a hand`,
                event.occurred_at,
            );
        },
    );

    useEcho<RoomGlobalPinUpdatedEvent>(
        `room.${roomPublicId}`,
        '.room.global-pin.updated',
        (event) => {
            globalPinnedRoomMemberId.value = event.global_pinned_room_member_id;
            addActivity(
                `${event.type}:${event.global_pinned_room_member_id ?? 'none'}:${event.occurred_at}`,
                event.member
                    ? `${event.member.display_name} was globally pinned`
                    : 'Global pin was cleared',
                event.occurred_at,
            );
        },
    );

    useEcho<RoomMemberRoleChangedEvent>(
        `room.${roomPublicId}`,
        '.room.member.role-changed',
        (event) => {
            upsertRoomMember(event.member);
            addActivity(
                `${event.type}:${event.member.id}:${event.occurred_at}`,
                `${event.member.display_name} is now ${event.member.role}`,
                event.occurred_at,
            );
        },
    );

    useEcho<RoomMemberMutedEvent>(
        `room.${roomPublicId}`,
        '.room.member.muted',
        (event) => {
            upsertRoomMember(event.member);
            addActivity(
                `${event.type}:${event.member.id}:${event.occurred_at}`,
                `${event.member.display_name} was muted by a moderator`,
                event.occurred_at,
            );
        },
    );

    useEcho<RoomMemberRemovedEvent>(
        `room.${roomPublicId}`,
        '.room.member.removed',
        (event) => {
            removeRoomMember(event.member.id);
            addActivity(
                `${event.type}:${event.member.id}:${event.occurred_at}`,
                `${event.member.display_name} was removed from the room`,
                event.occurred_at,
            );
        },
    );

    useEcho<RoomMemberBannedEvent>(
        `room.${roomPublicId}`,
        '.room.member.banned',
        (event) => {
            removeRoomMember(event.member.id);
            addActivity(
                `${event.type}:${event.member.id}:${event.occurred_at}`,
                `${event.member.display_name} was banned from the room`,
                event.occurred_at,
            );
        },
    );

    useEcho<RoomSubroomCreatedEvent>(
        `room.${roomPublicId}`,
        '.room.subroom.created',
        (event) => {
            upsertRoomSubroom(event.subroom);
            addActivity(
                `${event.type}:${event.subroom.id}:${event.occurred_at}`,
                `${event.subroom.name} subroom was created`,
                event.occurred_at,
            );
        },
    );

    useEcho<RoomMemberSubroomChangedEvent>(
        `room.${roomPublicId}`,
        '.room.member.subroom-changed',
        (event) => {
            upsertRoomMember(event.member);
            addActivity(
                `${event.type}:${event.member.id}:${event.occurred_at}`,
                event.subroom
                    ? `${event.member.display_name} moved to ${event.subroom.name}`
                    : `${event.member.display_name} returned to main room`,
                event.occurred_at,
            );
        },
    );

    useEcho<WaitingRoomEntryRequestedEvent>(
        `room.${roomPublicId}`,
        '.waiting-room.entry.requested',
        (event) => {
            upsertWaitingRoomEntry(event.waiting_room);
            addActivity(
                `${event.type}:${event.waiting_room.id}:${event.occurred_at}`,
                `${event.waiting_room.display_name} is waiting for approval`,
                event.occurred_at,
            );
        },
    );

    useEcho<WaitingRoomEntryDecidedEvent>(
        `room.${roomPublicId}`,
        '.waiting-room.entry.decided',
        (event) => {
            removeWaitingRoomEntry(event.waiting_room.id);

            if (event.member) {
                upsertRoomMember(event.member);
            }

            addActivity(
                `${event.type}:${event.waiting_room.id}:${event.occurred_at}`,
                `${event.waiting_room.display_name} was ${event.waiting_room.status}`,
                event.occurred_at,
            );
            options.onWaitingRoomDecision?.(event);
        },
    );

    function addActivity(
        id: string,
        message: string,
        occurredAt: string,
    ): void {
        activities.value = [
            {
                id,
                message,
                occurredAt,
            },
            ...activities.value,
        ].slice(0, 5);
    }

    function upsertWaitingRoomEntry(entry: WaitingRoomEntry): void {
        waitingRoomEntries.value = [
            entry,
            ...waitingRoomEntries.value.filter(
                (current) => current.id !== entry.id,
            ),
        ];
    }

    function removeWaitingRoomEntry(entryId: number): void {
        waitingRoomEntries.value = waitingRoomEntries.value.filter(
            (entry) => entry.id !== entryId,
        );
    }

    function upsertRoomMember(member: RoomMemberSummary): void {
        roomMembers.value = [
            member,
            ...roomMembers.value.filter((current) => current.id !== member.id),
        ];
    }

    function removeRoomMember(memberId: number): void {
        roomMembers.value = roomMembers.value.filter(
            (member) => member.id !== memberId,
        );

        if (globalPinnedRoomMemberId.value === memberId) {
            globalPinnedRoomMemberId.value = null;
        }
    }

    function appendRoomMessage(message: RoomMessageSummary): void {
        if (roomMessages.value.some((current) => current.id === message.id)) {
            return;
        }

        roomMessages.value = [...roomMessages.value, message].slice(-50);
    }

    function upsertRoomSubroom(subroom: RoomSubroomSummary): void {
        roomSubrooms.value = [
            subroom,
            ...roomSubrooms.value.filter(
                (current) => current.id !== subroom.id,
            ),
        ].sort((left, right) => left.position - right.position);
    }

    function setGlobalPinnedRoomMemberId(memberId: number | null): void {
        globalPinnedRoomMemberId.value = memberId;
    }

    return {
        echoConnectionStatus,
        activities,
        roomMembers,
        upsertRoomMember,
        removeRoomMember,
        roomMessages,
        appendRoomMessage,
        roomSubrooms,
        upsertRoomSubroom,
        globalPinnedRoomMemberId,
        setGlobalPinnedRoomMemberId,
        waitingRoomEntries,
        removeWaitingRoomEntry,
    };
}
