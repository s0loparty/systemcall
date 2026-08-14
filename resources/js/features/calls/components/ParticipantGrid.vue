<script setup lang="ts">
import type { ParticipantTile as ParticipantTileData } from '../types';
import ParticipantTile from './ParticipantTile.vue';

type Props = {
    tiles: ParticipantTileData[];
    activeSpeakerIdentity: string | null;
};

defineProps<Props>();

const emit = defineEmits<{
    'media-host': [identity: string, element: HTMLElement | null];
}>();
</script>

<template>
    <section
        class="grid flex-1 auto-rows-fr grid-cols-1 gap-4 overflow-auto md:grid-cols-2 xl:grid-cols-3"
    >
        <ParticipantTile
            v-for="tile in tiles"
            :key="tile.identity"
            :tile="tile"
            :active="tile.identity === activeSpeakerIdentity"
            :class="
                tile.identity === activeSpeakerIdentity && tiles.length > 1
                    ? 'md:col-span-2 md:row-span-2'
                    : ''
            "
            @media-host="
                (identity, element) => emit('media-host', identity, element)
            "
        />

        <div
            v-if="tiles.length === 0"
            class="flex min-h-96 items-center justify-center rounded-3xl border border-dashed border-neutral-300 bg-neutral-50 text-center text-sm text-neutral-500 dark:border-neutral-800 dark:bg-neutral-950"
        >
            Join the call to start publishing microphone and camera.
        </div>
    </section>
</template>
