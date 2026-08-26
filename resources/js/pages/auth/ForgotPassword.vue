<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

import { login } from '@/routes';

import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Восстановление пароля',
        description: 'Отправим ссылку для безопасного возвращения в аккаунт.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Восстановление пароля" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full bg-gradient-to-r from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-100 hover:from-violet-500 hover:to-blue-500"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Отправить ссылку для сброса пароля
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Или вернитесь ко</span>
            <TextLink :href="login()">входу</TextLink>
        </div>
    </div>
</template>
