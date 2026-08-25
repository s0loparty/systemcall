<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
} & ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Настройки безопасности',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Настройки безопасности" />

    <h1 class="sr-only">Настройки безопасности</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Безопасность"
            description="Пароль и дополнительные способы защиты аккаунта."
        />

        <Card
            class="overflow-hidden rounded-md border-slate-200/80 bg-white/90 shadow-sm"
        >
            <CardHeader>
                <CardTitle>Обновить пароль</CardTitle>
                <CardDescription>
                    Используйте длинный уникальный пароль для защиты аккаунта.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="SecurityController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">Текущий пароль</Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            class="block w-full rounded-md"
                            autocomplete="current-password"
                            placeholder="Текущий пароль"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Новый пароль</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            class="block w-full rounded-md"
                            autocomplete="new-password"
                            placeholder="Новый пароль"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">
                            Подтвердите пароль
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            class="block w-full rounded-md"
                            autocomplete="new-password"
                            placeholder="Подтвердите пароль"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <Button
                            class="rounded-md"
                            :disabled="processing"
                            data-test="update-password-button"
                        >
                            Сохранить пароль
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>

    <ManageTwoFactor
        :canManageTwoFactor="canManageTwoFactor"
        :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled"
    />
</template>
