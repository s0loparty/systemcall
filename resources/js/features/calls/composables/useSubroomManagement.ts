import { useHttp } from '@inertiajs/vue3';

import { update as moveMemberSubroom } from '@/routes/rooms/members/subroom';
import { store as createSubroom } from '@/routes/rooms/subrooms';

import type { RoomMemberSummary, RoomSubroomSummary } from '../types';

type CreateSubroomResponse = {
    subroom: RoomSubroomSummary;
};

type MoveMemberSubroomResponse = {
    member: RoomMemberSummary;
    subroom: RoomSubroomSummary | null;
};

export function useSubroomManagement(roomPublicId: string) {
    const createRequest = useHttp<{ name: string }, CreateSubroomResponse>({
        name: '',
    });
    const moveRequest = useHttp<
        { room_subroom_public_id: string | null },
        MoveMemberSubroomResponse
    >({
        room_subroom_public_id: null,
    });

    async function create(name: string): Promise<CreateSubroomResponse> {
        createRequest.name = name;

        return await createRequest.post(createSubroom.url(roomPublicId));
    }

    async function moveMember(
        memberId: number,
        subroomPublicId: string | null,
    ): Promise<MoveMemberSubroomResponse> {
        moveRequest.room_subroom_public_id = subroomPublicId;

        return await moveRequest.patch(
            moveMemberSubroom.url({
                room: roomPublicId,
                roomMember: memberId,
            }),
        );
    }

    return {
        createRequest,
        moveRequest,
        create,
        moveMember,
    };
}
