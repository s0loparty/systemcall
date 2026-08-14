<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { computed } from 'vue';
import { store as storeSubroom } from '@/routes/rooms/subrooms';
import type { RoomMemberSummary, RoomSubroomSummary } from '../types';

type Props = {
    roomPublicId: string;
    currentMemberId: number | null;
    connected: boolean;
    switching: boolean;
    canModerateMembers: boolean;
    members: RoomMemberSummary[];
    subrooms: RoomSubroomSummary[];
};

type StoreSubroomResponse = {
    subroom: RoomSubroomSummary;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    created: [subroom: RoomSubroomSummary];
    switch: [subroomPublicId: string | null];
}>();

const createRequest = useHttp<{ name: string }, StoreSubroomResponse>({
    name: '',
});

const currentMember = computed(() =>
    props.members.find((member) => member.id === props.currentMemberId),
);
const currentSubroomId = computed(
    () => currentMember.value?.room_subroom_id ?? null,
);
const orderedSubrooms = computed(() =>
    [...props.subrooms].sort((left, right) => left.position - right.position),
);
const subroomCount = computed(() => orderedSubrooms.value.length);

async function createSubroom(): Promise<void> {
    createRequest.name = createRequest.name.trim();

    if (createRequest.name === '' || subroomCount.value >= 5) {
        return;
    }

    const response = await createRequest.post(
        storeSubroom.url(props.roomPublicId),
    );

    if (response?.subroom) {
        emit('created', response.subroom);
        createRequest.name = '';
    }
}
</script>

<template>
    <section
        class="rounded-3xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
    >
        <div
            class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
        >
            <div>
                <p class="text-sm font-medium text-neutral-950 dark:text-white">
                    Subrooms
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Switching requests a new authorized LiveKit token and
                    reconnects media to the target room.
                </p>
            </div>
            <span
                class="w-fit rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300"
            >
                {{ subroomCount }} / 5
            </span>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <button
                class="rounded-full px-3 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-60"
                :class="
                    currentSubroomId === null
                        ? 'bg-neutral-950 text-white dark:bg-white dark:text-neutral-950'
                        : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800'
                "
                type="button"
                :disabled="!connected || switching || currentSubroomId === null"
                @click="emit('switch', null)"
            >
                Main room
            </button>
            <button
                v-for="subroom in orderedSubrooms"
                :key="subroom.id"
                class="rounded-full px-3 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-60"
                :class="
                    currentSubroomId === subroom.id
                        ? 'bg-neutral-950 text-white dark:bg-white dark:text-neutral-950'
                        : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800'
                "
                type="button"
                :disabled="
                    !connected || switching || currentSubroomId === subroom.id
                "
                @click="emit('switch', subroom.public_id)"
            >
                {{ subroom.name }}
            </button>
        </div>

        <form
            v-if="canModerateMembers"
            class="mt-4 flex flex-col gap-2 sm:flex-row"
            @submit.prevent="createSubroom"
        >
            <input
                v-model="createRequest.name"
                class="min-h-10 flex-1 rounded-2xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-950 transition outline-none focus:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-950 dark:text-white"
                maxlength="80"
                placeholder="New subroom name"
                :disabled="createRequest.processing || subroomCount >= 5"
            />
            <button
                class="rounded-2xl bg-neutral-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                type="submit"
                :disabled="
                    createRequest.processing ||
                    subroomCount >= 5 ||
                    createRequest.name.trim() === ''
                "
            >
                Create
            </button>
        </form>

        <p
            v-if="createRequest.errors.name"
            class="mt-2 text-xs text-red-600 dark:text-red-400"
        >
            {{ createRequest.errors.name }}
        </p>
    </section>
</template>
