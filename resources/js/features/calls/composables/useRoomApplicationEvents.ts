import { useConnectionStatus, useEcho } from '@laravel/echo-vue';
import { ref } from 'vue';
import type { RoomApplicationActivity, RoomMemberJoinedEvent } from '../types';

export function useRoomApplicationEvents(roomPublicId: string) {
    const echoConnectionStatus = useConnectionStatus();
    const activities = ref<RoomApplicationActivity[]>([]);

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

    return {
        echoConnectionStatus,
        activities,
    };
}
