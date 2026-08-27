<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import { Lock, Mail } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

import { register } from '@/routes';

import { store } from '@/routes/login';
import { request } from '@/routes/password';

import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';

defineOptions({
    layout: {
        title: 'Вход в аккаунт',
        description: 'Откройте свои комнаты и продолжите работу со встречами.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Вход" />

    <div class="space-y-4">
        <!-- Status message -->
        <div
            v-if="status"
            class="text-center text-sm font-medium text-emerald-600 bg-emerald-50 rounded-xl px-4 py-2"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
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
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="pl-9"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password">Пароль</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm text-muted-foreground hover:text-foreground"
                        :tabindex="5"
                    >
                        Забыли пароль?
                    </TextLink>
                </div>
                <div class="relative">
                    <Lock
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Пароль"
                        class="pl-9"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Remember me -->
            <div class="flex items-center justify-between">
                <Label
                    for="remember"
                    class="flex items-center space-x-3 cursor-pointer"
                >
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span class="text-sm text-muted-foreground"
                        >Запомнить меня</span
                    >
                </Label>
            </div>

            <!-- Submit -->
            <Button
                type="submit"
                class="mt-2 w-full rounded-full bg-neutral-900 text-white hover:bg-neutral-700 transition-all"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                {{ processing ? 'Вход...' : 'Войти' }}
            </Button>

            <!-- Register link -->
            <div class="text-center text-sm text-muted-foreground">
                Нет аккаунта?
                <TextLink
                    :href="register()"
                    :tabindex="5"
                    class="font-medium text-foreground hover:underline"
                >
                    Зарегистрироваться
                </TextLink>
            </div>
        </Form>
    </div>
</template>
