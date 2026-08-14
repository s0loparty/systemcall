import { useConnectionStatus, useEcho } from '@laravel/echo-vue';
import { ref } from 'vue';
import type {
    RoomApplicationActivity,
    RoomMemberJoinedEvent,
    WaitingRoomEntry,
    WaitingRoomEntryDecidedEvent,
    WaitingRoomEntryRequestedEvent,
} from '../types';

type UseRoomApplicationEventsOptions = {
    waitingRoomEntries?: WaitingRoomEntry[];
    onWaitingRoomDecision?: (event: WaitingRoomEntryDecidedEvent) => void;
};

export function useRoomApplicationEvents(
    roomPublicId: string,
    options: UseRoomApplicationEventsOptions = {},
) {
    const echoConnectionStatus = useConnectionStatus();
    const activities = ref<RoomApplicationActivity[]>([]);
    const waitingRoomEntries = ref<WaitingRoomEntry[]>(
        options.waitingRoomEntries ?? [],
    );

    useEcho<RoomMemberJoinedEvent>(
        `room.${roomPublicId}`,
        '.room.member.joined',
        (event) => {
            activities.value = [
                {
                    id: `${event.type}:${event.member.id}:${event.occurred_at}`,
                    message: `${event.member.display_name} joined the room`,
                    occurredAt: event.occurred_at,
                },
                ...activities.value,
            ].slice(0, 5);
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

    return {
        echoConnectionStatus,
        activities,
        waitingRoomEntries,
        removeWaitingRoomEntry,
    };
}
