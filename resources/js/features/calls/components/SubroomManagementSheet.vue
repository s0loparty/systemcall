<script setup lang="ts">
import { DoorOpen, Plus, Users } from '@lucide/vue';

import { computed, ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

import type { RoomMemberSummary, RoomSubroomSummary } from '../types';

type Props = {
    members: RoomMemberSummary[];
    subrooms: RoomSubroomSummary[];
    disabled: boolean;
};

const props = defineProps<Props>();
const open = ref(false);
const newSubroomName = ref('');

const emit = defineEmits<{
    create: [name: string];
    move: [memberId: number, subroomPublicId: string | null];
}>();

const activeMembers = computed(() =>
    props.members.filter((member) => member.left_at === null),
);
const canCreate = computed(() => newSubroomName.value.trim() !== '');

function createSubroom(): void {
    const name = newSubroomName.value.trim();

    if (!name) {
        return;
    }

    emit('create', name);
    newSubroomName.value = '';
}

function subroomName(member: RoomMemberSummary): string {
    if (member.room_subroom_id === null) {
        return 'Основная комната';
    }

    return (
        props.subrooms.find((subroom) => subroom.id === member.room_subroom_id)
            ?.name ?? 'Подкомната'
    );
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>
        <SheetContent
            class="w-full gap-0 overflow-y-auto border-slate-200 bg-white p-0 sm:max-w-xl"
            data-test="subrooms-sheet"
        >
            <SheetHeader class="border-b border-slate-200 px-5 py-5">
                <SheetTitle class="flex items-center gap-2">
                    <DoorOpen class="size-5 text-violet-600" />
                    Подкомнаты
                </SheetTitle>
                <SheetDescription>
                    Создавайте отдельные комнаты и перемещайте участников.
                </SheetDescription>
            </SheetHeader>

            <div class="grid gap-5 px-5 py-5 min-h-0">
                <form class="grid gap-2" @submit.prevent="createSubroom">
                    <label
                        class="text-sm font-medium text-slate-900"
                        for="new-subroom-name"
                    >
                        Новая подкомната
                    </label>
                    <div class="flex gap-2">
                        <Input
                            id="new-subroom-name"
                            v-model="newSubroomName"
                            data-test="new-subroom-name"
                            maxlength="80"
                            placeholder="Например: Команда A"
                            type="text"
                        />
                        <Button
                            class="shrink-0 rounded-md"
                            data-test="create-subroom"
                            type="submit"
                            :disabled="disabled || !canCreate"
                        >
                            <Plus class="size-4" />
                            Создать
                        </Button>
                    </div>
                </form>

                <section class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-950">
                            Подкомнаты
                        </h3>
                        <Badge
                            variant="secondary"
                            class="rounded-md bg-slate-100"
                        >
                            {{ subrooms.length }}
                        </Badge>
                    </div>
                    <div class="grid gap-2">
                        <div
                            class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm"
                        >
                            Основная комната
                        </div>
                        <div
                            v-for="subroom in subrooms"
                            :key="subroom.id"
                            data-test="subroom-sheet-room"
                            :data-subroom-id="subroom.public_id"
                            class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                        >
                            {{ subroom.name }}
                        </div>
                    </div>
                </section>

                <section class="grid gap-2 h-full min-h-0">
                    <div class="flex items-center justify-between gap-3">
                        <h3
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <Users class="size-4 text-slate-500" />
                            Участники
                        </h3>
                        <Badge
                            variant="secondary"
                            class="rounded-md bg-slate-100"
                        >
                            {{ activeMembers.length }}
                        </Badge>
                    </div>

                    <ScrollArea
                        class="h-full min-h-0 border border-slate-200 rounded-md"
                    >
                        <div class="grid gap-2">
                            <article
                                v-for="member in activeMembers"
                                :key="member.id"
                                data-test="subroom-member-row"
                                :data-member-id="member.id"
                                class="grid gap-3 bg-white p-3 border-b border-b-slate-200 last:border-b-0"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-slate-950"
                                    >
                                        {{ member.display_name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ subroomName(member) }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="rounded-md"
                                        data-test="move-member-main"
                                        :disabled="
                                            disabled ||
                                            member.room_subroom_id === null
                                        "
                                        @click="emit('move', member.id, null)"
                                    >
                                        Основная
                                    </Button>
                                    <Button
                                        v-for="subroom in subrooms"
                                        :key="subroom.id"
                                        size="sm"
                                        variant="outline"
                                        class="rounded-md"
                                        data-test="move-member-subroom"
                                        :data-subroom-id="subroom.public_id"
                                        :disabled="
                                            disabled ||
                                            member.room_subroom_id ===
                                                subroom.id
                                        "
                                        @click="
                                            emit(
                                                'move',
                                                member.id,
                                                subroom.public_id,
                                            )
                                        "
                                    >
                                        {{ subroom.name }}
                                    </Button>
                                </div>
                            </article>
                        </div>
                    </ScrollArea>
                </section>
            </div>
        </SheetContent>
    </Sheet>
</template>
