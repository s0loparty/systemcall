<script setup lang="ts">
import { CastIcon, EarthIcon, MicIcon, MicOffIcon, PinIcon } from '@lucide/vue';

import type {
    ParticipantTile,
    ParticipantTileActions,
    RoomSubroomSummary,
} from '../types';
import ParticipantTileActionsMenu from './ParticipantTileActionsMenu.vue';

type Props = {
    tile: ParticipantTile;
    active: boolean;
    personallyPinned: boolean;
    globallyPinned: boolean;
    actions?: ParticipantTileActions;
    subrooms?: RoomSubroomSummary[];
    actionsDisabled?: boolean;
    size?: 'default' | 'compact' | 'share';
};

withDefaults(defineProps<Props>(), {
    actions: () => ({
        canPersonalPin: false,
        canGlobalPin: false,
        canPromoteModerator: false,
        canMoveSubroom: false,
        canKick: false,
        canBan: false,
    }),
    subrooms: () => [],
    actionsDisabled: false,
    size: 'default',
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
</script>

<template>
    <article
        data-test="participant-tile"
        :data-identity="tile.identity"
        :data-local="tile.local ? 'true' : 'false'"
        :data-microphone-enabled="tile.microphone ? 'true' : 'false'"
        :data-camera-enabled="tile.camera ? 'true' : 'false'"
        :data-screen-share-enabled="tile.screenShare ? 'true' : 'false'"
        :data-hand-raised="tile.handRaised ? 'true' : 'false'"
        class="group relative overflow-hidden bg-neutral-800 transition-colors duration-200 ease-out @container"
        :class="[
            size === 'share'
                ? 'h-full min-h-96 rounded-md'
                : size === 'compact'
                  ? 'min-h-36 rounded-md'
                  : 'min-h-48 rounded-md md:min-h-64',
            personallyPinned || globallyPinned
                ? 'border-violet-300 ring-2 ring-violet-400/55'
                : tile.speaking
                  ? 'border-emerald-300 ring-2 ring-emerald-400/65'
                  : '',
        ]"
    >
        <div
            data-test="participant-media-host"
            :ref="
                (element) =>
                    emit(
                        'media-host',
                        tile.identity,
                        element as HTMLElement | null,
                    )
            "
            class="absolute inset-0 [&_audio]:hidden [&_video]:h-full [&_video]:w-full"
            :class="
                tile.screenShare && size === 'share'
                    ? '[&_video:not([data-source=screen-share])]:hidden [&_video[data-source=screen-share]]:bg-black [&_video[data-source=screen-share]]:object-contain'
                    : '[&_video]:object-cover'
            "
        />
        <ParticipantTileActionsMenu
            v-if="
                actions.canPersonalPin ||
                actions.canGlobalPin ||
                actions.canPromoteModerator ||
                actions.canMoveSubroom ||
                actions.canKick ||
                actions.canBan
            "
            :actions="actions"
            :subrooms="subrooms"
            :disabled="actionsDisabled"
            :personally-pinned="personallyPinned"
            :globally-pinned="globallyPinned"
            @pin-personal="emit('pin-personal', tile.identity)"
            @pin-global="emit('pin-global', tile.identity)"
            @promote-moderator="emit('promote-moderator', tile.identity)"
            @move-subroom="
                (subroomPublicId) =>
                    emit('move-subroom', tile.identity, subroomPublicId)
            "
            @kick="emit('kick', tile.identity)"
            @ban="emit('ban', tile.identity)"
        />

        <div
            data-test="participant-hand-raised-badge"
            class="text-2xl @md:text-3xl absolute top-4 left-4 z-10"
            :class="[tile.handRaised ? 'opacity-100' : 'opacity-0']"
        >
            <!-- <HandIcon class="size-5" /> -->
            ✋
        </div>

        <div
            class="absolute inset-0 flex items-center justify-center bg-neutral-800 empty:hidden @container-normal"
        >
            <span
                v-if="!tile.camera && !(tile.screenShare && size === 'share')"
                data-test="participant-fallback-avatar"
                class="text-center @sm:text-2xl @lg:text-3xl @xl:text-4xl font-semibold text-slate-300"
            >
                {{ tile.name }}
            </span>
        </div>
        <div
            class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-4 text-white"
        >
            <div class="py-1 px-1.5 bg-black/60 rounded">
                <p
                    class="text-sm font-medium drop-shadow flex items-center gap-x-1"
                >
                    <component
                        :is="tile.microphone ? MicIcon : MicOffIcon"
                        :class="[
                            'size-4',
                            tile.microphone ? '' : 'text-red-400',
                        ]"
                    />
                    {{ tile.name }}
                    <span v-if="tile.local" class="text-white/65">(вы)</span>
                </p>
            </div>
            <div class="flex flex-wrap justify-end gap-1.5">
                <div
                    v-if="personallyPinned"
                    class="rounded-md bg-sky-300 p-2 text-black"
                >
                    <!-- локально закреплен -->
                    <PinIcon class="size-5" />
                </div>
                <div
                    v-if="globallyPinned"
                    class="rounded-md bg-indigo-300 p-2 text-black"
                >
                    <EarthIcon class="size-5" />
                </div>
                <div
                    v-if="tile.screenShare"
                    class="rounded-md bg-sky-300 p-2 text-black"
                >
                    <CastIcon class="size-5" />
                </div>
            </div>
        </div>
    </article>
</template>
