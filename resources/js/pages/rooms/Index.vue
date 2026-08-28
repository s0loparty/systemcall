<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import {
    ArrowRight,
    DoorOpen,
    Lock,
    ShieldCheck,
    Trash2,
    Video,
} from '@lucide/vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';

import { dashboard } from '@/routes';

import { destroy as destroyRoom, store as storeRoom } from '@/routes/rooms';
import { prepare as prepareCall } from '@/routes/rooms/call';

import InputError from '@/components/InputError.vue';

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
    can_delete: boolean;
};

defineProps<{
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

function visibilityLabel(visibility: RoomSummary['visibility']): string {
    return visibility === 'public' ? 'публичная' : 'приватная';
}
</script>

<template>
    <Head title="Комнаты" />

    <div class="px-4 py-4 sm:px-6 sm:py-5 lg:px-8">
        <!-- Page header -->
        <div class="mb-6 sm:mb-8">
            <h1
                class="text-2xl font-light tracking-tight sm:text-3xl lg:text-4xl"
            >
                Создавайте встречи и приглашайте участников.
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Настройте доступ, пароль и зал ожидания. Перед входом участники
                попадут на подготовку устройств.
            </p>
        </div>

        <div class="grid gap-6 grid-cols-1 lg:grid-cols-[380px_1fr]">
            <!-- Left column: Create room form -->
            <div class="order-2 lg:order-1">
                <Card class="border-border/60 bg-white/70 shadow-sm">
                    <CardHeader class="pb-3 sm:pb-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <CardTitle
                                    class="text-lg font-semibold sm:text-xl"
                                    >Создать комнату</CardTitle
                                >
                                <CardDescription class="mt-1 text-sm leading-6">
                                    Быстрый старт звонка с настройками доступа.
                                </CardDescription>
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
                            <!-- Room name -->
                            <div class="grid gap-1.5">
                                <Label for="name" class="text-sm"
                                    >Название комнаты</Label
                                >
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    maxlength="120"
                                    placeholder="Ежедневный созвон"
                                    class="rounded-lg border-border/60 bg-white/80 text-sm"
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <!-- Description -->
                            <div class="grid gap-1.5">
                                <Label for="description" class="text-sm"
                                    >Описание</Label
                                >
                                <textarea
                                    id="description"
                                    name="description"
                                    maxlength="2000"
                                    class="min-h-20 w-full rounded-lg border border-border/60 bg-white/80 px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Коротко о встрече"
                                />
                                <InputError :message="errors.description" />
                            </div>

                            <!-- Visibility & Participants -->
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <Label for="visibility" class="text-sm"
                                        >Доступность</Label
                                    >
                                    <select
                                        id="visibility"
                                        name="visibility"
                                        class="h-9 rounded-lg border border-border/60 bg-white/80 px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
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
                                    <Label
                                        for="max_participants"
                                        class="text-sm"
                                        >Участники</Label
                                    >
                                    <Input
                                        id="max_participants"
                                        name="max_participants"
                                        type="number"
                                        min="2"
                                        max="500"
                                        placeholder="Без лимита"
                                        class="rounded-lg border-border/60 bg-white/80 text-sm"
                                    />
                                    <InputError
                                        :message="errors.max_participants"
                                    />
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="grid gap-1.5">
                                <Label for="password" class="text-sm"
                                    >Пароль</Label
                                >
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    minlength="8"
                                    maxlength="255"
                                    autocomplete="new-password"
                                    placeholder="Необязательно"
                                    class="rounded-lg border-border/60 bg-white/80 text-sm"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <!-- Waiting room -->
                            <label
                                class="flex items-start gap-3 rounded-lg border border-border/60 bg-white/80 p-3 text-sm"
                            >
                                <Checkbox
                                    name="waiting_room_enabled"
                                    value="1"
                                    class="mt-0.5 shrink-0"
                                />
                                <span>
                                    <span
                                        class="block font-medium text-foreground"
                                        >Зал ожидания</span
                                    >
                                    <span
                                        class="block text-xs leading-5 text-muted-foreground"
                                    >
                                        Участники войдут после одобрения.
                                    </span>
                                </span>
                            </label>

                            <Button
                                class="h-10 w-full rounded-full bg-neutral-900 text-white hover:bg-neutral-700 text-sm"
                                type="submit"
                                :disabled="processing"
                            >
                                {{
                                    processing
                                        ? 'Создаем...'
                                        : 'Создать и войти'
                                }}
                                <ArrowRight
                                    v-if="!processing"
                                    class="ml-2 size-4"
                                />
                            </Button>
                        </Form>
                    </CardContent>
                </Card>
            </div>

            <!-- Right column: Rooms list -->
            <div class="order-1 lg:order-2">
                <Card class="border-border/60 bg-white/70 shadow-sm">
                    <CardHeader class="pb-3 sm:pb-4">
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <CardTitle class="text-lg sm:text-xl"
                                    >Ваши комнаты</CardTitle
                                >
                                <CardDescription class="mt-1 text-sm leading-6">
                                    Откройте подготовку, проверьте устройства и
                                    войдите в звонок.
                                </CardDescription>
                            </div>
                            <div class="flex flex-wrap gap-1.5 sm:gap-2">
                                <Badge
                                    variant="outline"
                                    class="border-border/60 bg-white/50 text-xs"
                                >
                                    <Video class="size-3 mr-1" />
                                    {{ rooms.length }}
                                </Badge>
                                <Badge
                                    v-if="
                                        rooms.filter(
                                            (r) => r.visibility === 'private',
                                        ).length > 0
                                    "
                                    variant="outline"
                                    class="border-border/60 bg-white/50 text-xs"
                                >
                                    <Lock class="size-3 mr-1" />
                                    {{
                                        rooms.filter(
                                            (r) => r.visibility === 'private',
                                        ).length
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <!-- Empty state -->
                        <div
                            v-if="rooms.length === 0"
                            class="rounded-xl border border-dashed border-border/60 bg-white/50 p-6 sm:p-8 text-center"
                        >
                            <div
                                class="mx-auto flex size-12 items-center justify-center rounded-full border border-border/60 bg-white"
                            >
                                <Video class="size-6 text-muted-foreground" />
                            </div>
                            <p class="mt-4 font-semibold text-foreground">
                                Комнат пока нет
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Создайте первую комнату в форме рядом.
                            </p>
                        </div>

                        <!-- Rooms list -->
                        <ScrollArea style="height: 480px" v-else>
                            <div class="space-y-3">
                                <article
                                    v-for="room in rooms"
                                    :key="room.public_id"
                                    class="group rounded-xl border border-border/60 bg-white/80 p-3 sm:p-4 transition hover:-translate-y-0.5 hover:shadow-xs"
                                >
                                    <div
                                        class="flex flex-col gap-3 sm:grid sm:grid-cols-[1fr_auto] sm:items-center"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <div
                                                    class="flex size-9 shrink-0 items-center justify-center rounded-full border border-border/60 bg-white sm:size-10"
                                                >
                                                    <Video
                                                        class="size-4 text-foreground sm:size-5"
                                                    />
                                                </div>
                                                <h3
                                                    class="font-semibold text-foreground truncate text-sm sm:text-base"
                                                >
                                                    {{ room.name }}
                                                </h3>
                                            </div>

                                            <div
                                                class="mt-2 flex flex-wrap gap-1.5"
                                            >
                                                <Badge
                                                    variant="secondary"
                                                    class="bg-white/50 border-border/60 text-[10px] sm:text-xs"
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
                                                    class="bg-amber-50 text-amber-700 border-amber-200/60 text-[10px] sm:text-xs"
                                                >
                                                    <DoorOpen
                                                        class="size-2.5 mr-0.5 sm:size-3 sm:mr-1"
                                                    />
                                                    зал ожидания
                                                </Badge>
                                                <Badge
                                                    v-if="room.has_password"
                                                    class="bg-blue-50 text-blue-700 border-blue-200/60 text-[10px] sm:text-xs"
                                                >
                                                    <ShieldCheck
                                                        class="size-2.5 mr-0.5 sm:size-3 sm:mr-1"
                                                    />
                                                    пароль
                                                </Badge>
                                            </div>

                                            <p
                                                class="mt-2 line-clamp-2 text-xs leading-5 text-muted-foreground sm:text-sm sm:leading-6"
                                            >
                                                {{
                                                    room.description ??
                                                    'Описание пока не добавлено.'
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
                                        >
                                            <Button
                                                as-child
                                                class="w-full rounded-full bg-neutral-900 px-4 py-2 text-white hover:bg-neutral-700 sm:w-auto sm:px-5"
                                                size="sm"
                                            >
                                                <Link
                                                    :href="
                                                        prepareCall(
                                                            room.public_id,
                                                        )
                                                    "
                                                    class="flex items-center justify-center gap-1.5 sm:gap-2 text-xs sm:text-sm"
                                                >
                                                    Подготовиться
                                                    <ArrowRight
                                                        class="size-3.5 sm:size-4"
                                                    />
                                                </Link>
                                            </Button>

                                            <Dialog v-if="room.can_delete">
                                                <DialogTrigger as-child>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        class="h-9 w-full rounded-md border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 sm:w-9"
                                                        :aria-label="`Удалить комнату ${room.name}`"
                                                        :data-test="`delete-room-${room.public_id}`"
                                                    >
                                                        <Trash2
                                                            class="size-4"
                                                        />
                                                    </Button>
                                                </DialogTrigger>

                                                <DialogContent
                                                    class="rounded-md sm:max-w-md"
                                                >
                                                    <Form
                                                        v-bind="
                                                            destroyRoom.form(
                                                                room.public_id,
                                                            )
                                                        "
                                                        :options="{
                                                            preserveScroll: true,
                                                        }"
                                                        v-slot="{ processing }"
                                                        class="space-y-6"
                                                    >
                                                        <DialogHeader
                                                            class="space-y-3"
                                                        >
                                                            <DialogTitle>
                                                                Удалить комнату?
                                                            </DialogTitle>
                                                            <DialogDescription>
                                                                Комната «{{
                                                                    room.name
                                                                }}» будет
                                                                удалена. Это
                                                                действие нельзя
                                                                отменить.
                                                            </DialogDescription>
                                                        </DialogHeader>

                                                        <DialogFooter
                                                            class="gap-2"
                                                        >
                                                            <DialogClose
                                                                as-child
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    variant="secondary"
                                                                    class="rounded-md"
                                                                >
                                                                    Отмена
                                                                </Button>
                                                            </DialogClose>

                                                            <Button
                                                                type="submit"
                                                                variant="destructive"
                                                                class="rounded-md"
                                                                :disabled="
                                                                    processing
                                                                "
                                                                :data-test="`confirm-delete-room-${room.public_id}`"
                                                            >
                                                                Удалить
                                                            </Button>
                                                        </DialogFooter>
                                                    </Form>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </ScrollArea>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
