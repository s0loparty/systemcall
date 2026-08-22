<script setup lang="ts">
import type { ParticipantTile } from '../types';

type Props = {
    tile: ParticipantTile;
    active: boolean;
    personallyPinned: boolean;
    globallyPinned: boolean;
};

defineProps<Props>();

const emit = defineEmits<{
    'media-host': [identity: string, element: HTMLElement | null];
}>();
</script>

<template>
    <article
        class="relative min-h-64 overflow-hidden rounded-[1.75rem] bg-[#1f1f1f] shadow-[0_20px_70px_rgba(0,0,0,0.28),inset_0_0_0_1px_rgba(255,255,255,0.08)] transition-[box-shadow,scale] duration-200 ease-out active:scale-[0.995]"
        :class="
            personallyPinned || globallyPinned
                ? 'ring-2 ring-sky-400/70'
                : tile.speaking
                  ? 'ring-2 ring-emerald-400/70'
                  : ''
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
            class="absolute inset-0 flex items-center justify-center bg-[radial-gradient(circle_at_center,#343434,#1f1f1f_62%)] text-5xl font-semibold text-neutral-400 empty:hidden"
        >
            <span v-if="!tile.camera">
                {{ tile.name.slice(0, 1).toUpperCase() }}
            </span>
        </div>
        <div
            class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 bg-linear-to-t from-black/80 via-black/35 to-transparent p-4 text-white"
        >
            <div>
                <p class="text-sm font-medium drop-shadow">
                    {{ tile.name }}
                    <span v-if="tile.local" class="text-neutral-300">(вы)</span>
                </p>
                <p class="text-xs text-neutral-300">
                    {{
                        tile.microphone
                            ? 'микрофон включен'
                            : 'микрофон выключен'
                    }}
                </p>
            </div>
            <div class="flex flex-wrap justify-end gap-1.5">
                <span
                    v-if="personallyPinned"
                    class="rounded-full bg-sky-300 px-2 py-1 text-xs font-medium text-sky-950"
                >
                    локально закреплен
                </span>
                <span
                    v-if="globallyPinned"
                    class="rounded-full bg-indigo-300 px-2 py-1 text-xs font-medium text-indigo-950"
                >
                    закреплен для всех
                </span>
                <span
                    v-if="tile.screenShare"
                    class="rounded-full bg-sky-300 px-2 py-1 text-xs font-medium text-sky-950"
                >
                    показ экрана
                </span>
                <span
                    v-if="active || tile.speaking"
                    class="rounded-full bg-emerald-400 px-2 py-1 text-xs font-medium text-emerald-950"
                >
                    говорит
                </span>
            </div>
        </div>
    </article>
</template>
