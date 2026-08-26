<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

import { logout } from '@/routes';

import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Подтверждение email',
        description: 'Проверьте почту, чтобы завершить настройку аккаунта.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Подтверждение email" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        Новая ссылка для подтверждения отправлена на email, указанный при
        регистрации.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button
            :disabled="processing"
            class="bg-gradient-to-r from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-100 hover:from-violet-500 hover:to-blue-500"
        >
            <Spinner v-if="processing" />
            Отправить письмо повторно
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Выйти
        </TextLink>
    </Form>
</template>
