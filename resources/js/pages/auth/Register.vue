<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import { Lock, Mail, User } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

import { login } from '@/routes';

import { store } from '@/routes/register';

import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Создание аккаунта',
        description: 'Создайте рабочее пространство.',
    },
});
</script>

<template>
    <Head title="Регистрация" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <!-- Name -->
        <div class="space-y-1.5">
            <Label for="name">Имя</Label>
            <div class="relative">
                <User
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Полное имя"
                    class="pl-9"
                />
            </div>
            <InputError :message="errors.name" />
        </div>

        <!-- Email -->
        <div class="space-y-1.5">
            <Label for="email">Email</Label>
            <div class="relative">
                <Mail
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                    class="pl-9"
                />
            </div>
            <InputError :message="errors.email" />
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <Label for="password">Пароль</Label>
            <div class="relative">
                <Lock
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Пароль"
                    :passwordrules="passwordRules"
                    class="pl-9"
                />
            </div>
            <InputError :message="errors.password" />
        </div>

        <!-- Password confirmation -->
        <div class="space-y-1.5">
            <Label for="password_confirmation">Подтвердите пароль</Label>
            <div class="relative">
                <Lock
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Подтвердите пароль"
                    :passwordrules="passwordRules"
                    class="pl-9"
                />
            </div>
            <InputError :message="errors.password_confirmation" />
        </div>

        <!-- Submit -->
        <Button
            type="submit"
            class="mt-2 w-full rounded-full bg-neutral-900 text-white hover:bg-neutral-700 transition-all"
            tabindex="5"
            :disabled="processing"
            data-test="register-user-button"
        >
            <Spinner v-if="processing" class="mr-2 h-4 w-4" />
            {{ processing ? 'Создание...' : 'Создать аккаунт' }}
        </Button>

        <!-- Login link -->
        <div class="text-center text-sm text-muted-foreground">
            Уже есть аккаунт?
            <TextLink
                :href="login()"
                class="font-medium text-foreground hover:underline"
                :tabindex="6"
            >
                Войти
            </TextLink>
        </div>
    </Form>
</template>
