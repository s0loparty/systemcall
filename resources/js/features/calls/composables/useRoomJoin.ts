import { useHttp } from '@inertiajs/vue3';
import { store as issueLiveKitToken } from '@/routes/rooms/livekit_token';
import { store as joinRoom } from '@/routes/rooms/memberships';
import type {
    JoinForm,
    JoinRoomResponse,
    LiveKitTokenResponse,
    RoomMemberSummary,
    WaitingRoomEntry,
} from '../types';

type WaitingRoomJoinResponse = {
    waiting_room: WaitingRoomEntry;
};

export function useRoomJoin(roomPublicId: string, displayName: string) {
    const joinRequest = useHttp<JoinForm, JoinRoomResponse>({
        display_name: displayName,
        password: '',
    });
    const tokenRequest = useHttp<Record<string, never>, LiveKitTokenResponse>(
        {},
    );

    async function joinAndIssueToken(): Promise<
        | {
              kind: 'livekit';
              response: LiveKitTokenResponse;
              member: RoomMemberSummary;
          }
        | { kind: 'waiting-room'; response: WaitingRoomJoinResponse }
    > {
        const joinResponse = await joinRequest.post(joinRoom.url(roomPublicId));

        if ('waiting_room' in joinResponse) {
            return {
                kind: 'waiting-room',
                response: joinResponse,
            };
        }

        return {
            ...(await issueToken()),
            member: joinResponse.member,
        };
    }

    async function issueToken(): Promise<{
        kind: 'livekit';
        response: LiveKitTokenResponse;
    }> {
        return {
            kind: 'livekit',
            response: await tokenRequest.post(
                issueLiveKitToken.url(roomPublicId),
            ),
        };
    }

    return {
        joinRequest,
        tokenRequest,
        joinAndIssueToken,
        issueToken,
    };
}
