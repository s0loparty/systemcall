<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, RadioTower, ShieldCheck, Users, Video } from '@lucide/vue';
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

    <div class="p-5">
        <div class="grid grid-cols-2 gap-5">
            <section
                class="relative overflow-hidden rounded-[2rem] border border-neutral-200 bg-neutral-950 p-6 text-white shadow-sm md:p-8 dark:border-neutral-800"
            >
                <div
                    class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_center,rgba(34,197,94,0.22),transparent_60%)]"
                />
                <div class="relative grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                    <div class="flex flex-col justify-between gap-8">
                        <div>
                            <div
                                class="mb-4 flex w-fit items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-emerald-100"
                            >
                                <RadioTower class="size-3.5" />
                                Локально готово: LiveKit + Reverb
                            </div>
                            <h1
                                class="max-w-3xl text-3xl font-semibold tracking-tight md:text-5xl"
                            >
                                Создайте комнату, поделитесь ссылкой и проверьте
                                звонок.
                            </h1>
                            <p
                                class="mt-4 max-w-2xl text-sm leading-6 text-neutral-300 md:text-base"
                            >
                                Это рабочая панель для текущих возможностей:
                                вход в медиа, зал ожидания, модерация, чат,
                                руки, закрепления и подкомнаты.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div
                                class="rounded-2xl border border-white/10 bg-white/10 p-4"
                            >
                                <p class="text-2xl font-semibold">
                                    {{ rooms.length }}
                                </p>
                                <p class="text-xs text-neutral-300">
                                    доступных комнат
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/10 p-4"
                            >
                                <p class="text-2xl font-semibold">
                                    {{ activeRooms.length }}
                                </p>
                                <p class="text-xs text-neutral-300">
                                    активны сейчас
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-white/10 bg-white/10 p-4"
                            >
                                <p class="text-2xl font-semibold">
                                    {{ waitingRoomCount }}
                                </p>
                                <p class="text-xs text-neutral-300">
                                    с залом ожидания
                                </p>
                            </div>
                        </div>
                    </div>

                    <Form
                        v-bind="storeRoom.form()"
                        :reset-on-success="true"
                        class="rounded-[1.5rem] border border-white/10 bg-white p-4 text-neutral-950 shadow-2xl shadow-black/30 dark:bg-neutral-950 dark:text-white"
                        v-slot="{ errors, processing }"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-2xl bg-emerald-500 text-white"
                            >
                                <Plus class="size-5" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold">
                                    Новая комната
                                </p>
                                <p
                                    class="text-xs text-neutral-500 dark:text-neutral-400"
                                >
                                    После создания вы сразу попадете в звонок.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <div class="grid gap-2">
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

                            <div class="grid gap-2">
                                <Label for="description">Описание</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    maxlength="2000"
                                    class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                    placeholder="Для чего эта комната?"
                                />
                                <InputError :message="errors.description" />
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="visibility">Доступность</Label>
                                    <select
                                        id="visibility"
                                        name="visibility"
                                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                        required
                                    >
                                        <option value="public">
                                            Публичная
                                        </option>
                                        <option value="private">
                                            Приватная
                                        </option>
                                    </select>
                                    <InputError :message="errors.visibility" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="max_participants">
                                        Максимум участников
                                    </Label>
                                    <Input
                                        id="max_participants"
                                        name="max_participants"
                                        type="number"
                                        min="2"
                                        max="500"
                                        placeholder="Необязательно"
                                    />
                                    <InputError
                                        :message="errors.max_participants"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="password">
                                    Пароль, необязательно
                                </Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    minlength="8"
                                    maxlength="255"
                                    autocomplete="new-password"
                                    placeholder="Оставьте пустым для открытого доступа"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <label
                                class="flex items-start gap-3 rounded-2xl border border-neutral-200 p-3 text-sm dark:border-neutral-800"
                            >
                                <input
                                    class="mt-1 size-4 rounded border-neutral-300 text-neutral-950"
                                    type="checkbox"
                                    name="waiting_room_enabled"
                                    value="1"
                                />
                                <span>
                                    <span class="block font-medium">
                                        Включить зал ожидания
                                    </span>
                                    <span
                                        class="block text-xs text-neutral-500 dark:text-neutral-400"
                                    >
                                        Участники ждут, пока модератор одобрит
                                        вход.
                                    </span>
                                </span>
                            </label>

                            <Button
                                class="h-11 w-full rounded-2xl bg-emerald-500 text-white hover:bg-emerald-400"
                                type="submit"
                                :disabled="processing"
                            >
                                {{
                                    processing
                                        ? 'Создаем...'
                                        : 'Создать и войти'
                                }}
                            </Button>
                        </div>
                    </Form>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-[1fr_20rem]">
                <div
                    class="rounded-[2rem] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
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
                                Откройте страницу звонка в двух браузерах, чтобы
                                локально проверить медиа и realtime-функции.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="rooms.length === 0"
                        class="mt-4 rounded-3xl border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-800"
                    >
                        <Video
                            class="mx-auto size-10 text-neutral-400 dark:text-neutral-600"
                        />
                        <p
                            class="mt-3 font-medium text-neutral-950 dark:text-white"
                        >
                            Комнат пока нет
                        </p>
                        <p class="mt-1 text-sm text-neutral-500">
                            Создайте первую комнату выше и сразу войдите в
                            звонок.
                        </p>
                    </div>

                    <div v-else class="mt-4 grid gap-3">
                        <article
                            v-for="room in rooms"
                            :key="room.public_id"
                            class="group grid gap-4 rounded-3xl border border-neutral-200 bg-neutral-50 p-4 transition hover:border-neutral-300 hover:bg-white md:grid-cols-[1fr_auto] md:items-center dark:border-neutral-800 dark:bg-neutral-900/60 dark:hover:border-neutral-700 dark:hover:bg-neutral-900"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="truncate text-base font-semibold text-neutral-950 dark:text-white"
                                    >
                                        {{ room.name }}
                                    </h3>
                                    <span
                                        class="rounded-full bg-neutral-200 px-2 py-0.5 text-[11px] font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
                                    >
                                        {{ visibilityLabel(room.visibility) }}
                                    </span>
                                    <span
                                        v-if="room.waiting_room_enabled"
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-200"
                                    >
                                        зал ожидания
                                    </span>
                                    <span
                                        v-if="room.has_password"
                                        class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-200"
                                    >
                                        пароль
                                    </span>
                                </div>
                                <p
                                    class="mt-1 line-clamp-2 text-sm text-neutral-500 dark:text-neutral-400"
                                >
                                    {{
                                        room.description ??
                                        'Описание пока не добавлено.'
                                    }}
                                </p>
                                <p
                                    class="mt-2 text-xs text-neutral-400 dark:text-neutral-500"
                                >
                                    Public ID: {{ room.public_id }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 md:justify-end">
                                <Button as-child class="rounded-2xl">
                                    <Link :href="prepareCall(room.public_id)">
                                        Подготовиться
                                    </Link>
                                </Button>
                            </div>
                        </article>
                    </div>
                </div>

                <aside class="grid gap-4">
                    <div
                        class="rounded-[2rem] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        <div class="flex items-start gap-3">
                            <ShieldCheck
                                class="mt-0.5 size-5 text-emerald-500"
                            />
                            <div>
                                <p
                                    class="font-medium text-neutral-950 dark:text-white"
                                >
                                    Локальный чек-лист
                                </p>
                                <ul
                                    class="mt-3 grid gap-2 text-sm text-neutral-500 dark:text-neutral-400"
                                >
                                    <li>Laravel: http://localhost</li>
                                    <li>Reverb: ws://localhost:8080</li>
                                    <li>LiveKit: ws://localhost:7880</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-[2rem] border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                    >
                        <div class="flex items-start gap-3">
                            <Users class="mt-0.5 size-5 text-blue-500" />
                            <div>
                                <p
                                    class="font-medium text-neutral-950 dark:text-white"
                                >
                                    Smoke test
                                </p>
                                <p
                                    class="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400"
                                >
                                    Откройте одну комнату в обычном браузере и
                                    приватном окне. Войдите как владелец и
                                    гость, затем проверьте микрофон, камеру,
                                    чат, руку, закрепление и переключение
                                    подкомнат.
                                </p>
                            </div>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</template>
