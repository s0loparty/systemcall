import { useConnectionStatus, useEcho } from '@laravel/echo-vue';
import { ref } from 'vue';
import type {
    RoomApplicationActivity,
    RoomMemberBannedEvent,
    RoomMemberJoinedEvent,
    RoomMemberMutedEvent,
    RoomMemberRemovedEvent,
    RoomMemberRoleChangedEvent,
    RoomMemberSummary,
    WaitingRoomEntry,
    WaitingRoomEntryDecidedEvent,
    WaitingRoomEntryRequestedEvent,
} from '../types';

type UseRoomApplicationEventsOptions = {
    roomMembers?: RoomMemberSummary[];
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
    const waitingRoomEntries = ref<WaitingRoomEntry[]>(
        options.waitingRoomEntries ?? [],
    );

    useEcho<RoomMemberJoinedEvent>(
        `room.${roomPublicId}`,
        '.room.member.joined',
        (event) => {
            upsertRoomMember({
                room_public_id: event.room_public_id,
                can_publish_audio: true,
                can_publish_video: true,
                can_publish_screen: true,
                ...event.member,
            });
            addActivity(
                `${event.type}:${event.member.id}:${event.occurred_at}`,
                `${event.member.display_name} joined the room`,
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
    }

    return {
        echoConnectionStatus,
        activities,
        roomMembers,
        upsertRoomMember,
        removeRoomMember,
        waitingRoomEntries,
        removeWaitingRoomEntry,
    };
}
