<script setup lang="ts">
import { computed } from 'vue';
import type { ParticipantTile as ParticipantTileData } from '../types';
import ParticipantTile from './ParticipantTile.vue';

type Props = {
    tiles: ParticipantTileData[];
    activeSpeakerIdentity: string | null;
    personalPinnedIdentity: string | null;
    globalPinnedIdentity: string | null;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'media-host': [identity: string, element: HTMLElement | null];
}>();

const orderedTiles = computed(() => {
    const pinnedIdentity =
        props.personalPinnedIdentity ?? props.globalPinnedIdentity;

    if (pinnedIdentity === null) {
        return props.tiles;
    }

    return [...props.tiles].sort((left, right) => {
        if (left.identity === pinnedIdentity) {
            return -1;
        }

        if (right.identity === pinnedIdentity) {
            return 1;
        }

        return 0;
    });
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
</script>

<template>
    <section
        class="mx-auto grid h-full w-full flex-1 auto-rows-fr content-center gap-3 overflow-hidden p-1 md:gap-4"
        :class="gridClass"
    >
        <ParticipantTile
            v-for="tile in orderedTiles"
            :key="tile.identity"
            :tile="tile"
            :active="tile.identity === activeSpeakerIdentity"
            :personally-pinned="tile.identity === personalPinnedIdentity"
            :globally-pinned="tile.identity === globalPinnedIdentity"
            :class="
                (tile.identity === personalPinnedIdentity ||
                    tile.identity === globalPinnedIdentity ||
                    tile.identity === activeSpeakerIdentity) &&
                tiles.length > 1
                    ? 'sm:col-span-2 sm:row-span-2'
                    : ''
            "
            @media-host="
                (identity, element) => emit('media-host', identity, element)
            "
        />

        <div
            v-if="tiles.length === 0"
            class="flex min-h-96 items-center justify-center rounded-[1.75rem] bg-[#1f1f1f] text-center text-sm text-neutral-400 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.06)]"
        >
            Войдите в звонок. Камеру и микрофон можно оставить выключенными.
        </div>
    </section>
</template>
