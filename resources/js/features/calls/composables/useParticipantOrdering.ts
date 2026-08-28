import { type Ref, computed, readonly, ref, watch } from 'vue';

import type {
    ParticipantTile,
    RoomMemberSummary,
} from '@/features/calls/types';

type UseParticipantOrderingOptions = {
    tiles: Readonly<Ref<ParticipantTile[]>>;
    members: Readonly<Ref<RoomMemberSummary[]>>;
};

/**
 * Maintains a stable visual participant order.
 *
 * Rules:
 * - raised hands form a FIFO queue at the beginning of the grid;
 * - lowering a hand does not restore the old position;
 * - a participant who lowers their hand is placed immediately after
 *   the remaining raised-hand queue;
 * - pinning and screen sharing do not mutate visualOrder;
 * - visualOrder is stored by RoomMember.id, not LiveKit identity.
 */
export function useParticipantOrdering({
    tiles,
    members,
}: UseParticipantOrderingOptions) {
    const visualOrder = ref<number[]>([]);
    const raisedQueue = ref<number[]>([]);

    const memberByIdentity = computed(
        () =>
            new Map(
                members.value.map((member) => [
                    member.livekit_identity,
                    member,
                ]),
            ),
    );

    const tileByMemberId = computed(() => {
        const result = new Map<number, ParticipantTile>();

        for (const tile of tiles.value) {
            const member = memberByIdentity.value.get(tile.identity);

            if (member) {
                result.set(member.id, tile);
            }
        }

        return result;
    });

    const orderedTiles = computed<ParticipantTile[]>(() => {
        const ordered: ParticipantTile[] = [];
        const usedIdentities = new Set<string>();

        for (const memberId of visualOrder.value) {
            const tile = tileByMemberId.value.get(memberId);

            if (!tile) {
                continue;
            }

            ordered.push(tile);
            usedIdentities.add(tile.identity);
        }

        // A LiveKit tile can briefly exist before its RoomMember state arrives.
        // Keep such tiles visible without polluting the persistent member order.
        for (const tile of tiles.value) {
            if (!usedIdentities.has(tile.identity)) {
                ordered.push(tile);
            }
        }

        return ordered;
    });

    function syncOrder(): void {
        const visibleMembers = tiles.value
            .map((tile) => memberByIdentity.value.get(tile.identity))
            .filter(
                (member): member is RoomMemberSummary =>
                    member !== undefined && member.left_at === null,
            );

        const visibleIds = visibleMembers.map((member) => member.id);
        const visibleIdSet = new Set(visibleIds);

        // Remove participants who are no longer represented by a LiveKit tile.
        let nextOrder = visualOrder.value.filter((id) => visibleIdSet.has(id));

        // New participants enter at the end of the stable order first.
        for (const id of visibleIds) {
            if (!nextOrder.includes(id)) {
                nextOrder.push(id);
            }
        }

        const previousQueue = raisedQueue.value.filter((id) =>
            visibleIdSet.has(id),
        );
        const previousQueueSet = new Set(previousQueue);

        const currentRaisedMembers = visibleMembers
            .filter((member) => Boolean(member.hand_raised_at))
            .sort((left, right) => {
                const leftTime = handRaisedTimestamp(left);
                const rightTime = handRaisedTimestamp(right);

                if (leftTime !== rightTime) {
                    return leftTime - rightTime;
                }

                const leftPreviousIndex = previousQueue.indexOf(left.id);
                const rightPreviousIndex = previousQueue.indexOf(right.id);

                if (leftPreviousIndex !== -1 || rightPreviousIndex !== -1) {
                    if (leftPreviousIndex === -1) {
                        return 1;
                    }

                    if (rightPreviousIndex === -1) {
                        return -1;
                    }

                    return leftPreviousIndex - rightPreviousIndex;
                }

                return (
                    visibleIds.indexOf(left.id) - visibleIds.indexOf(right.id)
                );
            });

        const nextQueue = currentRaisedMembers.map((member) => member.id);
        const nextQueueSet = new Set(nextQueue);

        // Participants that just lowered their hand are intentionally kept
        // immediately after the remaining raised-hand queue.
        const loweredIds = previousQueue.filter((id) => !nextQueueSet.has(id));

        // Rebuild only the priority prefix. The rest keeps its current
        // visual order, so tiles do not jump back to their original places.
        const priorityIds = new Set([...nextQueue, ...loweredIds]);

        nextOrder = [
            ...nextQueue,
            ...loweredIds,
            ...nextOrder.filter((id) => !priorityIds.has(id)),
        ];

        visualOrder.value = nextOrder;
        raisedQueue.value = nextQueue;

        // Silence an otherwise tempting "simplification": the previous queue
        // is part of the transition semantics, not redundant state.
        void previousQueueSet;
    }

    function handRaisedTimestamp(member: RoomMemberSummary): number {
        if (!member.hand_raised_at) {
            return Number.POSITIVE_INFINITY;
        }

        const timestamp = Date.parse(member.hand_raised_at);

        return Number.isNaN(timestamp) ? Number.POSITIVE_INFINITY : timestamp;
    }

    watch(
        [
            () => tiles.value.map((tile) => tile.identity),
            () =>
                members.value.map((member) => [
                    member.id,
                    member.livekit_identity,
                    member.hand_raised_at ?? null,
                    member.left_at,
                ]),
        ],
        syncOrder,
        {
            immediate: true,
            deep: true,
        },
    );

    return {
        orderedTiles,
        visualOrder: readonly(visualOrder),
        raisedQueue: readonly(raisedQueue),
    };
}
