import { useHttp } from '@inertiajs/vue3';
import { store as issueLiveKitToken } from '@/routes/rooms/livekit_token';
import { store as joinRoom } from '@/routes/rooms/memberships';
import type { JoinForm, LiveKitTokenResponse } from '../types';

export function useRoomJoin(roomPublicId: string, displayName: string) {
    const joinRequest = useHttp<JoinForm, unknown>({
        display_name: displayName,
        password: '',
    });
    const tokenRequest = useHttp<Record<string, never>, LiveKitTokenResponse>(
        {},
    );

    async function joinAndIssueToken(): Promise<LiveKitTokenResponse> {
        await joinRequest.post(joinRoom.url(roomPublicId));

        return tokenRequest.post(issueLiveKitToken.url(roomPublicId));
    }

    return {
        joinRequest,
        tokenRequest,
        joinAndIssueToken,
    };
}
