<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, RadioTower, Video } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { store as storeRoom } from '@/routes/rooms';
import { prepare as prepareCall } from '@/routes/rooms/call';

type RoomSummary = {
    public_id: string;
    name: string;
    description: string | null;
    visibility: 'public' | 'private';
    status: string;
    has_password: boolean;
    waiting_room_enabled: boolean;
    max_participants: number | null;
    ended_at: string | null;
};

const props = defineProps<{
    rooms: RoomSummary[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Панель',
                href: dashboard(),
            },
            {
                title: 'Комнаты',
                href: '/rooms',
            },
        ],
    },
});

const activeRooms = computed(() =>
    props.rooms.filter((room) => room.status === 'active'),
);
const waitingRoomCount = computed(
    () => props.rooms.filter((room) => room.waiting_room_enabled).length,
);

function visibilityLabel(visibility: RoomSummary['visibility']): string {
    return visibility === 'public' ? 'публичная' : 'приватная';
}
</script>

<template>
    <Head title="Комнаты" />

    <div class="p-3 sm:p-5">
        <div class="grid gap-4 xl:grid-cols-[24rem_minmax(0,1fr)]">
            <section
                class="rounded-md border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div
                            class="flex w-fit items-center gap-2 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200"
                        >
                            <RadioTower class="size-3.5" />
                            Видеокомнаты
                        </div>
                        <h1
                            class="mt-3 text-2xl font-semibold tracking-tight text-neutral-950 dark:text-white"
                        >
                            Создать комнату
                        </h1>
                        <p
                            class="mt-1 text-sm leading-5 text-neutral-500 dark:text-neutral-400"
                        >
                            Быстрый старт звонка с паролем и залом ожидания.
                        </p>
                    </div>
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-md bg-neutral-950 text-white dark:bg-white dark:text-neutral-950"
                    >
                        <Plus class="size-5" />
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div
                        class="rounded-md bg-neutral-100 p-3 dark:bg-neutral-900"
                    >
                        <p
                            class="text-xl font-semibold text-neutral-950 dark:text-white"
                        >
                            {{ rooms.length }}
                        </p>
                        <p class="text-[11px] text-neutral-500">комнат</p>
                    </div>
                    <div
                        class="rounded-md bg-neutral-100 p-3 dark:bg-neutral-900"
                    >
                        <p
                            class="text-xl font-semibold text-neutral-950 dark:text-white"
                        >
                            {{ activeRooms.length }}
                        </p>
                        <p class="text-[11px] text-neutral-500">активны</p>
                    </div>
                    <div
                        class="rounded-md bg-neutral-100 p-3 dark:bg-neutral-900"
                    >
                        <p
                            class="text-xl font-semibold text-neutral-950 dark:text-white"
                        >
                            {{ waitingRoomCount }}
                        </p>
                        <p class="text-[11px] text-neutral-500">ожидание</p>
                    </div>
                </div>

                <Form
                    v-bind="storeRoom.form()"
                    :reset-on-success="true"
                    class="mt-4 grid gap-3"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-1.5">
                        <Label for="name">Название</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            maxlength="120"
                            placeholder="Ежедневный созвон"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="description">Описание</Label>
                        <textarea
                            id="description"
                            name="description"
                            maxlength="2000"
                            class="min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                            placeholder="Коротко о встрече"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="grid gap-1.5">
                            <Label for="visibility">Доступность</Label>
                            <select
                                id="visibility"
                                name="visibility"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                required
                            >
                                <option value="public">Публичная</option>
                                <option value="private">Приватная</option>
                            </select>
                            <InputError :message="errors.visibility" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="max_participants">Участники</Label>
                            <Input
                                id="max_participants"
                                name="max_participants"
                                type="number"
                                min="2"
                                max="500"
                                placeholder="Без лимита"
                            />
                            <InputError :message="errors.max_participants" />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="password">Пароль</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            minlength="8"
                            maxlength="255"
                            autocomplete="new-password"
                            placeholder="Необязательно"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <label
                        class="flex items-start gap-3 rounded-md border border-neutral-200 bg-neutral-50 p-3 text-sm dark:border-neutral-800 dark:bg-neutral-900/60"
                    >
                        <input
                            class="mt-1 size-4 rounded border-neutral-300 text-neutral-950"
                            type="checkbox"
                            name="waiting_room_enabled"
                            value="1"
                        />
                        <span>
                            <span
                                class="block font-medium text-neutral-950 dark:text-white"
                            >
                                Зал ожидания
                            </span>
                            <span
                                class="block text-xs leading-5 text-neutral-500 dark:text-neutral-400"
                            >
                                Вход только после одобрения.
                            </span>
                        </span>
                    </label>

                    <Button
                        class="h-10 w-full rounded-md bg-neutral-950 text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200"
                        type="submit"
                        :disabled="processing"
                    >
                        {{ processing ? 'Создаем...' : 'Создать и войти' }}
                    </Button>
                </Form>
            </section>

            <section
                class="min-w-0 rounded-md border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
            >
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold text-neutral-950 dark:text-white"
                        >
                            Ваши комнаты
                        </h2>
                        <p
                            class="text-sm text-neutral-500 dark:text-neutral-400"
                        >
                            Откройте подготовку и выберите устройства.
                        </p>
                    </div>
                    <div
                        class="flex w-fit items-center gap-2 rounded-md bg-neutral-100 px-3 py-1 text-xs text-neutral-600 dark:bg-neutral-900 dark:text-neutral-300"
                    >
                        <Video class="size-3.5" />
                        {{ rooms.length }}
                    </div>
                </div>

                <div
                    v-if="rooms.length === 0"
                    class="mt-4 rounded-md border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-800"
                >
                    <Video
                        class="mx-auto size-9 text-neutral-400 dark:text-neutral-600"
                    />
                    <p class="mt-3 font-medium text-neutral-950 dark:text-white">
                        Комнат пока нет
                    </p>
                    <p class="mt-1 text-sm text-neutral-500">
                        Создайте первую комнату слева.
                    </p>
                </div>

                <div v-else class="mt-4 grid gap-2">
                    <article
                        v-for="room in rooms"
                        :key="room.public_id"
                        class="group grid gap-3 rounded-md border border-neutral-200 bg-neutral-50 p-3 transition hover:border-neutral-300 hover:bg-white sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center dark:border-neutral-800 dark:bg-neutral-900/60 dark:hover:border-neutral-700 dark:hover:bg-neutral-900"
                    >
                        <div class="min-w-0">
                            <div
                                class="flex min-w-0 flex-wrap items-center gap-1.5"
                            >
                                <h3
                                    class="truncate text-sm font-semibold text-neutral-950 dark:text-white"
                                >
                                    {{ room.name }}
                                </h3>
                                <span
                                    class="rounded-md bg-neutral-200 px-2 py-0.5 text-[11px] font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
                                >
                                    {{ visibilityLabel(room.visibility) }}
                                </span>
                                <span
                                    v-if="room.waiting_room_enabled"
                                    class="rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-200"
                                >
                                    ожидание
                                </span>
                                <span
                                    v-if="room.has_password"
                                    class="rounded-md bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-200"
                                >
                                    пароль
                                </span>
                            </div>
                            <p
                                class="mt-1 line-clamp-1 text-sm text-neutral-500 dark:text-neutral-400"
                            >
                                {{
                                    room.description ??
                                    'Описание пока не добавлено.'
                                }}
                            </p>
                        </div>

                        <Button
                            as-child
                            class="h-9 rounded-md sm:w-auto"
                            size="sm"
                        >
                            <Link
                                class="w-full sm:w-auto"
                                :href="prepareCall(room.public_id)"
                            >
                                Подготовиться
                            </Link>
                        </Button>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>
