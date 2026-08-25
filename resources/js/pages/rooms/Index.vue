<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    DoorOpen,
    Lock,
    Plus,
    ShieldCheck,
    Video,
} from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
const privateRoomsCount = computed(
    () => props.rooms.filter((room) => room.visibility === 'private').length,
);

function visibilityLabel(visibility: RoomSummary['visibility']): string {
    return visibility === 'public' ? 'публичная' : 'приватная';
}
</script>

<template>
    <Head title="Комнаты" />

    <div class="p-3 sm:p-5">
        <div class="mx-auto grid max-w-7xl gap-5">
            <section
                class="grid gap-4 rounded-md border border-slate-200/80 bg-white/90 p-4 shadow-sm lg:grid-cols-[1fr_auto] lg:items-center"
            >
                <div>
                    <Badge
                        variant="outline"
                        class="rounded-md border-violet-100 bg-violet-50 px-3 py-1.5 text-violet-700"
                    >
                        <Video class="size-3.5" />
                        Комнаты
                    </Badge>
                    <h1
                        class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl"
                    >
                        Создавайте встречи и приглашайте участников.
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Настройте доступ, пароль и зал ожидания. Перед входом
                        участники попадут на подготовку устройств.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:min-w-96">
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-2xl font-semibold text-slate-950">
                            {{ rooms.length }}
                        </p>
                        <p class="text-xs text-slate-500">всего</p>
                    </div>
                    <div class="rounded-md bg-emerald-50 p-3">
                        <p class="text-2xl font-semibold text-emerald-700">
                            {{ activeRooms.length }}
                        </p>
                        <p class="text-xs text-emerald-700/75">активны</p>
                    </div>
                    <div class="rounded-md bg-amber-50 p-3">
                        <p class="text-2xl font-semibold text-amber-700">
                            {{ waitingRoomCount }}
                        </p>
                        <p class="text-xs text-amber-700/75">ожидание</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[25rem_minmax(0,1fr)]">
                <Card
                    class="rounded-md border-slate-200/80 bg-white/90 shadow-sm"
                >
                    <CardHeader class="pb-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <CardTitle class="text-2xl">
                                    Создать комнату
                                </CardTitle>
                                <CardDescription class="mt-1 leading-6">
                                    Быстрый старт звонка с настройками доступа.
                                </CardDescription>
                            </div>
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-100"
                            >
                                <Plus class="size-5" />
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <Form
                            v-bind="storeRoom.form()"
                            :reset-on-success="true"
                            class="grid gap-4"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid gap-1.5">
                                <Label for="name">Название комнаты</Label>
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
                                    class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Коротко о встрече"
                                />
                                <InputError :message="errors.description" />
                            </div>

                            <div
                                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1"
                            >
                                <div class="grid gap-1.5">
                                    <Label for="visibility">Доступность</Label>
                                    <select
                                        id="visibility"
                                        name="visibility"
                                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
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

                                <div class="grid gap-1.5">
                                    <Label for="max_participants">
                                        Участники
                                    </Label>
                                    <Input
                                        id="max_participants"
                                        name="max_participants"
                                        type="number"
                                        min="2"
                                        max="500"
                                        placeholder="Без лимита"
                                    />
                                    <InputError
                                        :message="errors.max_participants"
                                    />
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
                                class="flex items-start gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 text-sm"
                            >
                                <input
                                    class="mt-1 size-4 rounded border-slate-300 text-violet-600"
                                    type="checkbox"
                                    name="waiting_room_enabled"
                                    value="1"
                                />
                                <span>
                                    <span
                                        class="block font-medium text-slate-950"
                                    >
                                        Зал ожидания
                                    </span>
                                    <span
                                        class="block text-xs leading-5 text-slate-500"
                                    >
                                        Участники войдут после одобрения.
                                    </span>
                                </span>
                            </label>

                            <Button
                                class="h-10 w-full rounded-md bg-gradient-to-r from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-100 hover:from-violet-500 hover:to-blue-500"
                                type="submit"
                                :disabled="processing"
                            >
                                {{
                                    processing
                                        ? 'Создаем...'
                                        : 'Создать и войти'
                                }}
                                <ArrowRight v-if="!processing" class="size-4" />
                            </Button>
                        </Form>
                    </CardContent>
                </Card>

                <Card
                    class="min-w-0 rounded-md border-slate-200/80 bg-white/90 shadow-sm"
                >
                    <CardHeader
                        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <CardTitle>Ваши комнаты</CardTitle>
                            <CardDescription class="mt-1 leading-6">
                                Откройте подготовку, проверьте устройства и
                                войдите в звонок.
                            </CardDescription>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Badge
                                variant="outline"
                                class="rounded-md bg-slate-50"
                            >
                                <Video class="size-3.5" />
                                {{ rooms.length }}
                            </Badge>
                            <Badge
                                v-if="privateRoomsCount > 0"
                                variant="outline"
                                class="rounded-md bg-blue-50 text-blue-700"
                            >
                                <Lock class="size-3.5" />
                                {{ privateRoomsCount }}
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div
                            v-if="rooms.length === 0"
                            class="rounded-md border border-dashed border-slate-300 bg-slate-50/70 p-8 text-center"
                        >
                            <div
                                class="mx-auto flex size-12 items-center justify-center rounded-md bg-white text-slate-500 shadow-sm"
                            >
                                <Video class="size-6" />
                            </div>
                            <p class="mt-4 font-semibold text-slate-950">
                                Комнат пока нет
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Создайте первую комнату в форме рядом.
                            </p>
                        </div>

                        <div v-else class="grid gap-3">
                            <article
                                v-for="room in rooms"
                                :key="room.public_id"
                                class="group grid gap-3 rounded-md border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-lg hover:shadow-violet-100/70 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="flex min-w-0 flex-wrap items-center gap-2"
                                    >
                                        <div
                                            class="flex size-10 shrink-0 items-center justify-center rounded-md bg-violet-50 text-violet-600"
                                        >
                                            <Video class="size-5" />
                                        </div>
                                        <div class="min-w-0">
                                            <h3
                                                class="truncate font-semibold text-slate-950"
                                            >
                                                {{ room.name }}
                                            </h3>
                                            <div
                                                class="mt-1 flex flex-wrap gap-1.5"
                                            >
                                                <Badge
                                                    variant="secondary"
                                                    class="rounded-md"
                                                >
                                                    {{
                                                        visibilityLabel(
                                                            room.visibility,
                                                        )
                                                    }}
                                                </Badge>
                                                <Badge
                                                    v-if="
                                                        room.waiting_room_enabled
                                                    "
                                                    class="rounded-md bg-amber-100 text-amber-700 hover:bg-amber-100"
                                                >
                                                    <DoorOpen class="size-3" />
                                                    зал ожидания
                                                </Badge>
                                                <Badge
                                                    v-if="room.has_password"
                                                    class="rounded-md bg-blue-100 text-blue-700 hover:bg-blue-100"
                                                >
                                                    <ShieldCheck
                                                        class="size-3"
                                                    />
                                                    пароль
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>

                                    <p
                                        class="mt-3 line-clamp-2 text-sm leading-6 text-slate-500"
                                    >
                                        {{
                                            room.description ??
                                            'Описание пока не добавлено.'
                                        }}
                                    </p>
                                </div>

                                <Button
                                    as-child
                                    class="h-10 rounded-md bg-slate-950 px-4 text-white hover:bg-slate-800 sm:w-auto"
                                    size="sm"
                                >
                                    <Link
                                        class="w-full sm:w-auto"
                                        :href="prepareCall(room.public_id)"
                                    >
                                        Подготовиться
                                        <ArrowRight class="size-4" />
                                    </Link>
                                </Button>
                            </article>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
