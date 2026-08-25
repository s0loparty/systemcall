<script setup lang="ts">
import {
    Ban,
    CastIcon,
    EarthIcon,
    Globe2,
    HandIcon,
    MicIcon,
    MicOffIcon,
    Pin,
    PinIcon,
    PinOff,
    UserMinus,
} from '@lucide/vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { ParticipantTile, ParticipantTileActions } from '../types';

type Props = {
    tile: ParticipantTile;
    active: boolean;
    personallyPinned: boolean;
    globallyPinned: boolean;
    actions?: ParticipantTileActions;
    actionsDisabled?: boolean;
    size?: 'default' | 'compact' | 'share';
};

withDefaults(defineProps<Props>(), {
    actions: () => ({
        canPersonalPin: false,
        canGlobalPin: false,
        canKick: false,
        canBan: false,
    }),
    actionsDisabled: false,
    size: 'default',
});

const emit = defineEmits<{
    'media-host': [identity: string, element: HTMLElement | null];
    'pin-personal': [identity: string];
    'pin-global': [identity: string];
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
        class="group relative overflow-hidden border border-slate-200/80 bg-[#161719] shadow-[0_24px_70px_rgba(15,23,42,0.14)] transition-[box-shadow,border-color] duration-200 ease-out"
        :class="[
            size === 'share'
                ? 'h-full min-h-96 rounded-md'
                : size === 'compact'
                  ? 'min-h-36 rounded-md'
                  : 'min-h-64 rounded-md',
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
        <div
            v-if="
                !tile.local &&
                (actions.canPersonalPin ||
                    actions.canGlobalPin ||
                    actions.canKick ||
                    actions.canBan)
            "
            data-test="participant-tile-actions"
            class="pointer-events-none absolute top-3 right-3 z-10 flex translate-y-1 items-center gap-1 rounded-md border border-white/10 bg-slate-950/82 p-1 opacity-0 shadow-[0_18px_45px_rgba(0,0,0,0.32)] backdrop-blur-xl transition duration-150 ease-out group-focus-within:pointer-events-auto group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100"
        >
            <TooltipProvider :delay-duration="120">
                <Tooltip v-if="actions.canPersonalPin">
                    <TooltipTrigger as-child>
                        <button
                            class="flex size-9 items-center justify-center rounded-md text-white/85 transition hover:bg-white/14 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                            type="button"
                            data-test="pin-personal-participant"
                            :disabled="actionsDisabled"
                            :aria-label="
                                personallyPinned
                                    ? 'Открепить для себя'
                                    : 'Закрепить для себя'
                            "
                            @click.stop="emit('pin-personal', tile.identity)"
                        >
                            <PinOff v-if="personallyPinned" class="size-4" />
                            <Pin v-else class="size-4" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">
                        <p>
                            {{
                                personallyPinned
                                    ? 'Открепить для себя'
                                    : 'Закрепить для себя'
                            }}
                        </p>
                    </TooltipContent>
                </Tooltip>

                <Tooltip v-if="actions.canGlobalPin">
                    <TooltipTrigger as-child>
                        <button
                            class="flex size-9 items-center justify-center rounded-md text-sky-100 transition hover:bg-sky-400/20 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                            type="button"
                            data-test="pin-global-participant"
                            :disabled="actionsDisabled"
                            :aria-label="
                                globallyPinned
                                    ? 'Открепить для всех'
                                    : 'Закрепить для всех'
                            "
                            @click.stop="emit('pin-global', tile.identity)"
                        >
                            <PinOff v-if="globallyPinned" class="size-4" />
                            <Globe2 v-else class="size-4" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">
                        <p>
                            {{
                                globallyPinned
                                    ? 'Открепить для всех'
                                    : 'Закрепить для всех'
                            }}
                        </p>
                    </TooltipContent>
                </Tooltip>

                <Tooltip v-if="actions.canKick">
                    <TooltipTrigger as-child>
                        <button
                            class="flex size-9 items-center justify-center rounded-md text-orange-100 transition hover:bg-orange-500/25 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                            type="button"
                            data-test="kick-participant"
                            :disabled="actionsDisabled"
                            aria-label="Кикнуть участника"
                            @click.stop="emit('kick', tile.identity)"
                        >
                            <UserMinus class="size-4" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">
                        <p>Кикнуть участника</p>
                    </TooltipContent>
                </Tooltip>

                <Tooltip v-if="actions.canBan">
                    <TooltipTrigger as-child>
                        <button
                            class="flex size-9 items-center justify-center rounded-md text-red-100 transition hover:bg-red-500/25 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                            type="button"
                            data-test="ban-participant"
                            :disabled="actionsDisabled"
                            aria-label="Забанить участника"
                            @click.stop="emit('ban', tile.identity)"
                        >
                            <Ban class="size-4" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">
                        <p>Забанить участника</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
        <div
            class="absolute inset-0 flex items-center justify-center bg-[radial-gradient(circle_at_center,#30343b,#151618_62%)] text-5xl font-semibold text-slate-300 empty:hidden"
        >
            <span
                v-if="!tile.camera && !(tile.screenShare && size === 'share')"
                data-test="participant-fallback-avatar"
            >
                {{ tile.name.slice(0, 1).toUpperCase() }}
            </span>
        </div>
        <div
            class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 bg-linear-to-t from-black/82 via-black/35 to-transparent p-4 text-white"
        >
            <div>
                <p class="text-sm font-medium drop-shadow">
                    {{ tile.name }}
                    <span v-if="tile.local" class="text-white/65">(вы)</span>
                </p>
            </div>
            <div class="flex flex-wrap justify-end gap-1.5">
                <div class="rounded-md bg-black/50 p-2">
                    <component
                        :is="tile.microphone ? MicIcon : MicOffIcon"
                        class="size-5"
                    />
                </div>
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
                    v-if="tile.handRaised"
                    data-test="participant-hand-raised-badge"
                    class="rounded-md bg-[#fbbc04]/90 p-2 text-black"
                >
                    <!-- 🤚 -->
                    <HandIcon class="size-5" />
                </div>
                <div
                    v-if="tile.screenShare"
                    class="rounded-md bg-sky-300 p-2 text-black"
                >
                    <CastIcon class="size-5" />
                </div>
                <!-- <span
                    v-if="active || tile.speaking"
                    class="rounded-full bg-emerald-400 px-2 py-1 text-xs font-medium text-emerald-950"
                >
                    говорит
                </span> -->
            </div>
        </div>
    </article>
</template>
