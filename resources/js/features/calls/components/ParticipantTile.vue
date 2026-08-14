<script setup lang="ts">
import type { ParticipantTile } from '../types';

type Props = {
    tile: ParticipantTile;
    active: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'media-host': [identity: string, element: HTMLElement | null];
}>();
</script>

<template>
    <article
        class="relative min-h-64 overflow-hidden rounded-3xl border bg-neutral-950 shadow-sm transition"
        :class="
            tile.speaking
                ? 'border-emerald-400 ring-2 ring-emerald-400/40'
                : 'border-neutral-200 dark:border-neutral-800'
        "
    >
        <div
            :ref="
                (element) =>
                    emit(
                        'media-host',
                        tile.identity,
                        element as HTMLElement | null,
                    )
            "
            class="absolute inset-0 [&_audio]:hidden [&_video]:h-full [&_video]:w-full [&_video]:object-cover"
        />
        <div
            class="absolute inset-0 flex items-center justify-center bg-neutral-900 text-4xl font-semibold text-neutral-500 empty:hidden"
        >
            <span v-if="!tile.camera">
                {{ tile.name.slice(0, 1).toUpperCase() }}
            </span>
        </div>
        <div
            class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-3 bg-linear-to-t from-black/80 to-transparent p-4 text-white"
        >
            <div>
                <p class="font-medium">
                    {{ tile.name }}
                    <span v-if="tile.local" class="text-neutral-300"
                        >(you)</span
                    >
                </p>
                <p class="text-xs text-neutral-300">
                    {{ tile.microphone ? 'mic on' : 'mic off' }}
                </p>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <span
                    v-if="tile.screenShare"
                    class="rounded-full bg-sky-300 px-2 py-1 text-xs font-medium text-sky-950"
                >
                    sharing
                </span>
                <span
                    v-if="active || tile.speaking"
                    class="rounded-full bg-emerald-400 px-2 py-1 text-xs font-medium text-emerald-950"
                >
                    speaking
                </span>
            </div>
        </div>
    </article>
</template>
