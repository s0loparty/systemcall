<script setup lang="ts">
import { computed } from 'vue';

import { ScrollArea } from '@/components/ui/scroll-area';

import type {
    ParticipantTileActions,
    ParticipantTile as ParticipantTileData,
    RoomSubroomSummary,
} from '../types';
import ParticipantTile from './ParticipantTile.vue';

type Props = {
    tiles: ParticipantTileData[];
    activeSpeakerIdentity: string | null;
    personalPinnedIdentity: string | null;
    globalPinnedIdentity: string | null;
    tileActionsByIdentity?: Record<string, ParticipantTileActions | undefined>;
    subrooms?: RoomSubroomSummary[];
    actionsDisabled?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    tileActionsByIdentity: () => ({}),
    subrooms: () => [],
    actionsDisabled: false,
});

const emit = defineEmits<{
    'media-host': [identity: string, element: HTMLElement | null];
    'pin-personal': [identity: string];
    'pin-global': [identity: string];
    'promote-moderator': [identity: string];
    'move-subroom': [identity: string, subroomPublicId: string | null];
    kick: [identity: string];
    ban: [identity: string];
}>();

const orderedTiles = computed(() => {
    const pinnedIdentity =
        props.personalPinnedIdentity ?? props.globalPinnedIdentity;

    return props.tiles
        .map((tile, index) => ({ tile, index }))
        .sort((left, right) => {
            const leftPinned = left.tile.identity === pinnedIdentity;
            const rightPinned = right.tile.identity === pinnedIdentity;

            if (leftPinned !== rightPinned) {
                return leftPinned ? -1 : 1;
            }

            if (left.tile.handRaised !== right.tile.handRaised) {
                return left.tile.handRaised ? -1 : 1;
            }

            return left.index - right.index;
        })
        .map(({ tile }) => tile);
});

const gridClass = computed(() => {
    if (orderedTiles.value.length <= 1) {
        return 'grid-cols-1 max-w-5xl';
    }

    if (orderedTiles.value.length === 2) {
        return 'grid-cols-1 lg:grid-cols-2 max-w-6xl';
    }

    if (orderedTiles.value.length <= 4) {
        return 'grid-cols-1 sm:grid-cols-2 max-w-6xl';
    }

    return 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 max-w-[96rem]';
});

const screenShareTile = computed(
    () => orderedTiles.value.find((tile) => tile.screenShare) ?? null,
);
const pinnedTile = computed(() => {
    const pinnedIdentity =
        props.personalPinnedIdentity ?? props.globalPinnedIdentity;

    return pinnedIdentity
        ? (orderedTiles.value.find(
              (tile) => tile.identity === pinnedIdentity,
          ) ?? null)
        : null;
});
const focusedTile = computed(() => screenShareTile.value ?? pinnedTile.value);
const sideTiles = computed(() =>
    focusedTile.value
        ? orderedTiles.value.filter(
              (tile) => tile.identity !== focusedTile.value?.identity,
          )
        : [],
);
</script>

<template>
    <section
        v-if="focusedTile"
        :data-test="
            screenShareTile ? 'screen-share-layout' : 'focused-tile-layout'
        "
        class="mx-auto grid h-full w-full max-w-[96rem] flex-1 gap-3 overflow-hidden p-1 lg:grid-cols-[minmax(0,2.45fr)_minmax(15rem,0.72fr)] xl:grid-cols-[minmax(0,2.7fr)_minmax(18rem,0.72fr)]"
    >
        <ParticipantTile
            :tile="focusedTile"
            :active="focusedTile.identity === activeSpeakerIdentity"
            :personally-pinned="focusedTile.identity === personalPinnedIdentity"
            :globally-pinned="focusedTile.identity === globalPinnedIdentity"
            :actions="tileActionsByIdentity[focusedTile.identity]"
            :subrooms="subrooms"
            :actions-disabled="actionsDisabled"
            size="share"
            @media-host="
                (identity, element) => emit('media-host', identity, element)
            "
            @pin-personal="(identity) => emit('pin-personal', identity)"
            @pin-global="(identity) => emit('pin-global', identity)"
            @promote-moderator="
                (identity) => emit('promote-moderator', identity)
            "
            @move-subroom="
                (identity, subroomPublicId) =>
                    emit('move-subroom', identity, subroomPublicId)
            "
            @kick="(identity) => emit('kick', identity)"
            @ban="(identity) => emit('ban', identity)"
        />

        <aside
            data-test="participant-side-rail"
            class="flex min-h-0 flex-col gap-3 overflow-y-auto rounded-md bg-neutral-950 p-2"
            aria-label="Участники звонка"
        >
            <ParticipantTile
                v-for="tile in sideTiles"
                :key="tile.identity"
                :tile="tile"
                :active="tile.identity === activeSpeakerIdentity"
                :personally-pinned="tile.identity === personalPinnedIdentity"
                :globally-pinned="tile.identity === globalPinnedIdentity"
                :actions="tileActionsByIdentity[tile.identity]"
                :subrooms="subrooms"
                :actions-disabled="actionsDisabled"
                size="compact"
                @media-host="
                    (identity, element) => emit('media-host', identity, element)
                "
                @pin-personal="(identity) => emit('pin-personal', identity)"
                @pin-global="(identity) => emit('pin-global', identity)"
                @promote-moderator="
                    (identity) => emit('promote-moderator', identity)
                "
                @move-subroom="
                    (identity, subroomPublicId) =>
                        emit('move-subroom', identity, subroomPublicId)
                "
                @kick="(identity) => emit('kick', identity)"
                @ban="(identity) => emit('ban', identity)"
            />

            <div
                v-if="sideTiles.length === 0"
                class="flex min-h-36 items-center justify-center rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 text-center text-sm text-slate-500"
            >
                Остальные участники появятся здесь.
            </div>
        </aside>
    </section>

    <ScrollArea v-else class="[&_[data-reka-scroll-area-viewport]>div]:h-full">
        <section
            data-test="participant-grid"
            class="mx-auto grid sm:h-full w-full flex-1 auto-rows-fr content-center gap-3 p-1 md:gap-4 md:overflow-hidden"
            :class="gridClass"
        >
            <ParticipantTile
                v-for="tile in orderedTiles"
                :key="tile.identity"
                :tile="tile"
                :active="tile.identity === activeSpeakerIdentity"
                :personally-pinned="tile.identity === personalPinnedIdentity"
                :globally-pinned="tile.identity === globalPinnedIdentity"
                :actions="tileActionsByIdentity[tile.identity]"
                :subrooms="subrooms"
                :actions-disabled="actionsDisabled"
                :class="
                    (tile.identity === personalPinnedIdentity ||
                        tile.identity === globalPinnedIdentity) &&
                    tiles.length > 1
                        ? 'sm:col-span-2 sm:row-span-2'
                        : ''
                "
                @media-host="
                    (identity, element) => emit('media-host', identity, element)
                "
                @pin-personal="(identity) => emit('pin-personal', identity)"
                @pin-global="(identity) => emit('pin-global', identity)"
                @promote-moderator="
                    (identity) => emit('promote-moderator', identity)
                "
                @move-subroom="
                    (identity, subroomPublicId) =>
                        emit('move-subroom', identity, subroomPublicId)
                "
                @kick="(identity) => emit('kick', identity)"
                @ban="(identity) => emit('ban', identity)"
            />

            <div
                v-if="tiles.length === 0"
                class="flex min-h-96 items-center justify-center rounded-md border border-dashed border-slate-200 bg-white/75 text-center text-sm text-slate-500 shadow-sm"
            >
                Войдите в звонок. Камеру и микрофон можно оставить выключенными.
            </div>
        </section>
    </ScrollArea>
</template>
