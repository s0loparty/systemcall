<script setup lang="ts">
import { Ban, Globe2, Pin, PinOff, ShieldPlus, UserMinus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { ParticipantTileActions } from '../types';

type ConfirmAction = 'kick' | 'ban';

type Props = {
    actions: ParticipantTileActions;
    disabled: boolean;
    personallyPinned: boolean;
    globallyPinned: boolean;
};

const props = defineProps<Props>();
const confirmAction = ref<ConfirmAction | null>(null);

const emit = defineEmits<{
    'pin-personal': [];
    'pin-global': [];
    'promote-moderator': [];
    kick: [];
    ban: [];
}>();

const hasActions = computed(
    () =>
        props.actions.canPersonalPin ||
        props.actions.canGlobalPin ||
        props.actions.canPromoteModerator ||
        props.actions.canKick ||
        props.actions.canBan,
);

const confirmTitle = computed(() =>
    confirmAction.value === 'ban'
        ? 'Забанить участника?'
        : 'Удалить участника из звонка?',
);
const confirmDescription = computed(() =>
    confirmAction.value === 'ban'
        ? 'Участник будет удален из звонка и не сможет подключиться к этой комнате снова.'
        : 'Участник будет удален из текущего звонка. Он сможет подключиться снова, если доступ к комнате не заблокирован.',
);
const confirmButtonLabel = computed(() =>
    confirmAction.value === 'ban' ? 'Забанить' : 'Удалить',
);

function confirm(): void {
    if (confirmAction.value === 'ban') {
        emit('ban');
    }

    if (confirmAction.value === 'kick') {
        emit('kick');
    }

    confirmAction.value = null;
}
</script>

<template>
    <div
        v-if="hasActions"
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
                        :disabled="disabled"
                        :aria-label="
                            personallyPinned
                                ? 'Открепить для себя'
                                : 'Закрепить для себя'
                        "
                        @click.stop="emit('pin-personal')"
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
                        :disabled="disabled"
                        :aria-label="
                            globallyPinned
                                ? 'Открепить для всех'
                                : 'Закрепить для всех'
                        "
                        @click.stop="emit('pin-global')"
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

            <Tooltip v-if="actions.canPromoteModerator">
                <TooltipTrigger as-child>
                    <button
                        class="flex size-9 items-center justify-center rounded-md text-emerald-100 transition hover:bg-emerald-500/25 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        type="button"
                        data-test="promote-moderator-participant"
                        :disabled="disabled"
                        aria-label="Назначить модератором"
                        @click.stop="emit('promote-moderator')"
                    >
                        <ShieldPlus class="size-4" />
                    </button>
                </TooltipTrigger>
                <TooltipContent side="bottom">
                    <p>Назначить модератором</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip v-if="actions.canKick">
                <TooltipTrigger as-child>
                    <button
                        class="flex size-9 items-center justify-center rounded-md text-orange-100 transition hover:bg-orange-500/25 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        type="button"
                        data-test="kick-participant"
                        :disabled="disabled"
                        aria-label="Кикнуть участника"
                        @click.stop="confirmAction = 'kick'"
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
                        :disabled="disabled"
                        aria-label="Забанить участника"
                        @click.stop="confirmAction = 'ban'"
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

    <Dialog :open="confirmAction !== null" @update:open="confirmAction = null">
        <DialogContent class="rounded-md border-slate-200 bg-white sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ confirmTitle }}</DialogTitle>
                <DialogDescription>
                    {{ confirmDescription }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <DialogClose as-child>
                    <Button
                        class="rounded-md"
                        type="button"
                        variant="outline"
                        data-test="cancel-participant-moderation"
                    >
                        Отмена
                    </Button>
                </DialogClose>
                <Button
                    class="rounded-md bg-red-600 text-white hover:bg-red-500"
                    type="button"
                    data-test="confirm-participant-moderation"
                    :disabled="disabled"
                    @click="confirm"
                >
                    {{ confirmButtonLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
