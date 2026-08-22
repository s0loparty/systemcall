<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { ban, kick, mute, mute_all } from '@/routes/rooms/members';
import { update as updateRole } from '@/routes/rooms/members/role';
import type { RoomMemberSummary } from '../types';

type Props = {
    roomPublicId: string;
    currentMemberId: number | null;
    members: RoomMemberSummary[];
};

type MemberResponse = {
    member: RoomMemberSummary;
};

type MembersResponse = {
    members: RoomMemberSummary[];
};

const props = defineProps<Props>();
const emit = defineEmits<{
    updated: [member: RoomMemberSummary];
    removed: [memberId: number];
}>();

const roleRequest = useHttp<
    { role: 'moderator' | 'participant' },
    MemberResponse
>({
    role: 'participant',
});
const muteRequest = useHttp<Record<string, never>, MemberResponse>({});
const muteAllRequest = useHttp<Record<string, never>, MembersResponse>({});
const kickRequest = useHttp<Record<string, never>, MemberResponse>({});
const banRequest = useHttp<Record<string, never>, unknown>({});

async function changeRole(
    member: RoomMemberSummary,
    role: 'moderator' | 'participant',
): Promise<void> {
    roleRequest.role = role;

    const response = await roleRequest.patch(
        updateRole.url({
            room: props.roomPublicId,
            roomMember: member.id,
        }),
    );

    if (response?.member) {
        emit('updated', response.member);
    }
}

async function muteMember(member: RoomMemberSummary): Promise<void> {
    const response = await muteRequest.post(
        mute.url({
            room: props.roomPublicId,
            roomMember: member.id,
        }),
    );

    if (response?.member) {
        emit('updated', response.member);
    }
}

async function muteAllMembers(): Promise<void> {
    const response = await muteAllRequest.post(
        mute_all.url({
            room: props.roomPublicId,
        }),
    );

    response?.members?.forEach((member) => emit('updated', member));
}

async function kickMember(member: RoomMemberSummary): Promise<void> {
    const response = await kickRequest.delete(
        kick.url({
            room: props.roomPublicId,
            roomMember: member.id,
        }),
    );

    emit('removed', response?.member?.id ?? member.id);
}

async function banMember(member: RoomMemberSummary): Promise<void> {
    await banRequest.post(
        ban.url({
            room: props.roomPublicId,
            roomMember: member.id,
        }),
    );

    emit('removed', member.id);
}

function canModerate(member: RoomMemberSummary): boolean {
    return member.role !== 'owner' && member.id !== props.currentMemberId;
}

function roleLabel(role: RoomMemberSummary['role']): string {
    return (
        {
            owner: 'владелец',
            moderator: 'модератор',
            participant: 'участник',
        } satisfies Record<RoomMemberSummary['role'], string>
    )[role];
}
</script>

<template>
    <section
        class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
    >
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <p class="text-sm font-medium text-neutral-950 dark:text-white">
                    Модерация комнаты
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Управляйте ролями, принудительно выключайте микрофон,
                    удаляйте или блокируйте активных участников.
                </p>
            </div>
            <button
                class="rounded-full bg-neutral-950 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                type="button"
                :disabled="muteAllRequest.processing"
                @click="muteAllMembers"
            >
                Выключить микрофон всем
            </button>
        </div>

        <p
            v-if="members.length === 0"
            class="mt-3 text-sm text-neutral-500 dark:text-neutral-400"
        >
            Сейчас нет активных участников.
        </p>

        <ol v-else class="mt-3 flex flex-col gap-2">
            <li
                v-for="member in members"
                :key="member.id"
                class="flex flex-col gap-3 rounded-2xl border border-neutral-200 p-3 md:flex-row md:items-center md:justify-between dark:border-neutral-800"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p
                            class="truncate text-sm font-medium text-neutral-950 dark:text-white"
                        >
                            {{ member.display_name }}
                        </p>
                        <span
                            class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300"
                        >
                            {{ roleLabel(member.role) }}
                        </span>
                        <span
                            v-if="member.id === currentMemberId"
                            class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-200"
                        >
                            вы
                        </span>
                    </div>
                    <p
                        class="mt-1 text-xs text-neutral-500 dark:text-neutral-400"
                    >
                        {{ member.guest ? 'Гость' : 'Пользователь' }}
                        · mic
                        {{ member.can_publish_audio ? 'разрешен' : 'выключен' }}
                        · camera
                        {{
                            member.can_publish_video ? 'разрешена' : 'выключена'
                        }}
                    </p>
                </div>

                <div
                    v-if="canModerate(member)"
                    class="flex flex-wrap gap-2 md:justify-end"
                >
                    <button
                        v-if="member.role === 'participant'"
                        class="rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-medium text-neutral-700 transition hover:bg-neutral-200 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800"
                        type="button"
                        :disabled="roleRequest.processing"
                        @click="changeRole(member, 'moderator')"
                    >
                        Назначить модератором
                    </button>
                    <button
                        v-if="member.role === 'moderator'"
                        class="rounded-full bg-neutral-100 px-3 py-1.5 text-xs font-medium text-neutral-700 transition hover:bg-neutral-200 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800"
                        type="button"
                        :disabled="roleRequest.processing"
                        @click="changeRole(member, 'participant')"
                    >
                        Снять модератора
                    </button>
                    <button
                        class="rounded-full bg-amber-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="
                            muteRequest.processing || !member.can_publish_audio
                        "
                        @click="muteMember(member)"
                    >
                        Выключить микрофон
                    </button>
                    <button
                        class="rounded-full bg-orange-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-orange-500 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="kickRequest.processing"
                        @click="kickMember(member)"
                    >
                        Удалить
                    </button>
                    <button
                        class="rounded-full bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="banRequest.processing"
                        @click="banMember(member)"
                    >
                        Заблокировать
                    </button>
                </div>
                <p
                    v-else
                    class="text-xs text-neutral-500 dark:text-neutral-400"
                >
                    Защищенный участник
                </p>
            </li>
        </ol>
    </section>
</template>
