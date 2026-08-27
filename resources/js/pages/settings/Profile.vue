<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

import { computed } from 'vue';

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

import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Настройки профиля',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Настройки профиля" />

    <h1 class="sr-only">Настройки профиля</h1>

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="Профиль"
            description="Данные, по которым вас видят в аккаунте и уведомлениях."
        />

        <Card
            class="overflow-hidden rounded-md border-slate-200/80 bg-white/90 shadow-sm"
        >
            <CardHeader>
                <CardTitle>Основная информация</CardTitle>
                <CardDescription>
                    Обновите имя и email для входа и уведомлений.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Имя</Label>
                        <Input
                            id="name"
                            class="block w-full rounded-md"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Полное имя"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            class="block w-full rounded-md"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div
                        v-if="
                            page.props.mustVerifyEmail &&
                            !user.email_verified_at
                        "
                        class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                    >
                        <p>
                            Email не подтвержден.
                            <Link
                                :href="send()"
                                as="button"
                                class="font-semibold underline underline-offset-4"
                            >
                                Отправить письмо повторно.
                            </Link>
                        </p>

                        <div
                            v-if="
                                page.props.status === 'verification-link-sent'
                            "
                            class="mt-2 font-medium text-emerald-700"
                        >
                            Новая ссылка отправлена на ваш email.
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <Button
                            class="rounded-md"
                            :disabled="processing"
                            data-test="update-profile-button"
                        >
                            Сохранить изменения
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>

    <DeleteUser />
</template>
